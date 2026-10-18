<?php

use App\Models\RedFlagCheck;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    /** @var array<int, string> unlock note per red-flag check id */
    public array $notes = [];

    #[Computed]
    public function locked()
    {
        $query = RedFlagCheck::query()
            ->with('patient.user', 'screening', 'clinic')
            ->whereNotNull('stopped_at')
            ->whereNull('unlocked_at')
            ->latest('stopped_at');

        // never rely on the global scope alone for a safety screen
        if (auth()->user()->role !== \App\Enums\Role::PlatformAdmin) {
            $query->where('clinic_id', auth()->user()->clinic_id);
        }

        return $query->get();
    }

    public function unlock(int $id): void
    {
        $check = RedFlagCheck::findOrFail($id);

        Gate::authorize('unlock', $check);

        $this->validate(
            ["notes.$id" => ['required', 'string', 'min:10', 'max:500']],
            ["notes.$id.required" => 'Isi catatan: apa hasil pemeriksaan medis pasien?',
             "notes.$id.min" => 'Catatan minimal 10 karakter.'],
        );

        $check->update([
            'unlocked_at' => now(),
            'unlocked_by' => auth()->id(),
            'unlock_note' => $this->notes[$id],
        ]);

        unset($this->notes[$id], $this->locked);

        $this->dispatch('toast', message: 'Akses pasien dibuka kembali', type: 'success');
    }
}; ?>

<div class="space-y-6">
    <x-page-header title="Tanda bahaya (red flag)" subtitle="Pasien di bawah ini dihentikan dari program latihan. Buka kunci hanya setelah pasien diperiksa tenaga medis." />

    @forelse ($this->locked as $check)
        <x-panel wire:key="rf-{{ $check->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-display font-bold">{{ $check->patient->user->name }}</p>
                    <p class="text-xs text-muted-foreground">
                        @if ($check->patient->age !== null) {{ $check->patient->age }} th · @endif
                        {{ $check->clinic?->name ?? 'Tanpa klinik (mandiri)' }} ·
                        dihentikan {{ $check->stopped_at->diffForHumans() }}
                    </p>
                </div>
                <x-ui.badge variant="destructive">Dihentikan</x-ui.badge>
            </div>

            <ul class="mt-4 flex flex-wrap gap-2">
                @foreach ($check->flagged as $key)
                    <x-ui.badge variant="outline" class="gap-1">
                        <x-lucide-circle-alert class="size-3 text-destructive" />
                        {{ \App\Support\Onboarding\RedFlags::labels([$key])[0] }}@if ($key === 'other' && $check->other_text): {{ $check->other_text }}@endif@if ($key === 'pain_score_high' && $check->screening) ({{ $check->screening->max_pain }}/10)@endif
                    </x-ui.badge>
                @endforeach
            </ul>

            <div class="mt-5 space-y-3">
                <x-ui.textarea label="Catatan pembukaan kunci (wajib)" rows="2" maxlength="500"
                               placeholder="mis. Pasien sudah diperiksa dokter di RS, hasil normal, boleh lanjut latihan."
                               wire:model="notes.{{ $check->id }}" />
                <x-ui.button wire:click="unlock({{ $check->id }})"
                             wire:confirm="Yakin membuka kunci? Pastikan pasien sudah diperiksa tenaga medis."
                             wire:loading.attr="disabled" wire:target="unlock({{ $check->id }})">
                    <x-lucide-lock-open class="size-4" /> Buka kunci
                </x-ui.button>
            </div>
        </x-panel>
    @empty
        <x-panel>
            <p class="text-sm text-muted-foreground">Tidak ada pasien yang sedang dihentikan.</p>
        </x-panel>
    @endforelse
</div>
