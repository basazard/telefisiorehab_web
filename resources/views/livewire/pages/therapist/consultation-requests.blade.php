<?php

use App\Models\ConsultationRequest;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// Telekonsultasi / rujukan requests from the Exercise Recommendation module, for clinic staff.
new class extends Component {
    /** @var array<int, string> note per request id */
    public array $notes = [];

    #[Computed]
    public function open()
    {
        $query = ConsultationRequest::query()
            ->with('patient.user', 'assessment', 'clinic')
            ->where('status', 'open')
            ->oldest();

        // never rely on the global scope alone for a patient-data screen
        if (auth()->user()->role !== \App\Enums\Role::PlatformAdmin) {
            $query->where('clinic_id', auth()->user()->clinic_id);
        }

        return $query->get();
    }

    public function handle(int $id): void
    {
        $request = ConsultationRequest::findOrFail($id);

        Gate::authorize('handle', $request);

        $this->validate(
            ["notes.$id" => ['required', 'string', 'min:10', 'max:500']],
            ["notes.$id.required" => 'Isi catatan tindak lanjut.', "notes.$id.min" => 'Catatan minimal 10 karakter.'],
        );

        $request->update([
            'status' => 'handled',
            'handled_by' => auth()->id(),
            'handled_at' => now(),
            'staff_note' => $this->notes[$id],
        ]);

        unset($this->notes[$id], $this->open);

        $this->dispatch('toast', message: 'Permintaan ditandai sudah ditangani', type: 'success');
    }
}; ?>

<div class="space-y-6">
    <x-page-header title="Konsultasi & rujukan" subtitle="Permintaan telekonsultasi dan rujukan dari pasien. Hubungi pasien, lalu catat tindak lanjutnya." />

    @forelse ($this->open as $req)
        <x-panel wire:key="cr-{{ $req->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-display font-bold">{{ $req->patient->user->name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ $req->clinic?->name ?? 'Tanpa klinik (mandiri)' }} · diajukan {{ $req->created_at->diffForHumans() }}
                        @if ($req->assessment?->tier) · {{ $req->assessment->tier->label() }} · {{ $req->assessment->domain->label() }} @endif
                    </p>
                </div>
                <x-ui.badge :variant="$req->type === 'referral' ? 'destructive' : 'outline'">{{ $req->typeLabel() }}</x-ui.badge>
            </div>
            @if ($req->reason) <p class="mt-3 text-sm">{{ $req->reason }}</p> @endif

            <div class="mt-5 space-y-3">
                <x-ui.textarea label="Catatan tindak lanjut (wajib)" rows="2" maxlength="500"
                               placeholder="mis. Telekonsultasi dijadwalkan Kamis 10.00 via video call." wire:model="notes.{{ $req->id }}" />
                <x-ui.button wire:click="handle({{ $req->id }})" wire:loading.attr="disabled" wire:target="handle({{ $req->id }})">
                    <x-lucide-check class="size-4" /> Tandai ditangani
                </x-ui.button>
            </div>
        </x-panel>
    @empty
        <x-panel>
            <p class="text-sm text-muted-foreground">Tidak ada permintaan yang menunggu.</p>
        </x-panel>
    @endforelse
</div>
