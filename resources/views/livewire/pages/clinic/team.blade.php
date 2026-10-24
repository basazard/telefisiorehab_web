<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Support\Staff\Assignment;
use App\Support\Staff\Invitations;
use App\Support\Staff\TeamActions;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// Clinic team for the clinic admin (PRD 09 S4.2, S4.3): staff list, invitations, deactivate / reactivate.
new class extends Component {
    public string $email = '';

    /** The last invitation link, shown to copy when the email did not go out. */
    public ?string $inviteLink = null;

    /** Therapist being deactivated, and where their patients go ('' = release them). */
    public ?int $deactivating = null;
    public string $moveTo = '';

    public function mount(): void
    {
        Gate::authorize('manage', $this->clinic);
    }

    #[Computed]
    public function clinic(): Clinic
    {
        return auth()->user()->clinic ?? abort(403);
    }

    #[Computed]
    public function staff()
    {
        return User::query()
            ->where('clinic_id', $this->clinic->id)
            ->whereIn('role', [Role::Therapist, Role::ClinicAdmin])
            ->withCount(['assignedPatients as patients_count' => fn ($q) => $q->where('clinic_id', $this->clinic->id)])
            ->orderBy('role')->orderBy('name')
            ->get();
    }

    #[Computed]
    public function invitations()
    {
        return StaffInvitation::query()->where('clinic_id', $this->clinic->id)
            ->whereNull('accepted_at')->where('expires_at', '>', now())
            ->latest()->with('inviter:id,name')->get();
    }

    public function invite(): void
    {
        Gate::authorize('invite', $this->clinic);
        $this->validate(['email' => ['required', 'email', 'max:255']], ['email.required' => 'Isi email fisioterapis.', 'email.email' => 'Format email tidak valid.']);

        $result = Invitations::invite(auth()->user(), $this->clinic, $this->email);

        $this->inviteLink = $result['mailed'] ? null : $result['url'];
        $this->reset('email');
        unset($this->invitations);
        $this->dispatch('toast', message: $result['mailed'] ? 'Undangan dikirim ke '.$result['invitation']->email : 'Undangan dibuat. Salin tautannya untuk dikirim.', type: 'success');
    }

    public function cancelInvitation(int $id): void
    {
        Gate::authorize('invite', $this->clinic);

        Invitations::cancel(StaffInvitation::query()->where('clinic_id', $this->clinic->id)->findOrFail($id));

        unset($this->invitations);
        $this->dispatch('toast', message: 'Undangan dibatalkan', type: 'success');
    }

    public function startDeactivate(int $userId): void
    {
        Gate::authorize('deactivate', $this->member($userId));

        $this->deactivating = $userId;
        $this->moveTo = '';
    }

    public function deactivate(): void
    {
        $therapist = $this->member((int) $this->deactivating);
        $moveTo = $this->moveTo === '' ? null : User::find((int) $this->moveTo);

        $count = TeamActions::deactivate(auth()->user(), $therapist, $moveTo);

        $this->reset('deactivating', 'moveTo');
        unset($this->staff);
        $this->dispatch('toast', message: $therapist->name.' dinonaktifkan. '.$count.' pasien '.($moveTo ? 'dipindahkan ke '.$moveTo->name : 'dilepas (belum ditugaskan)').'.', type: 'success');
    }

    public function reactivate(int $userId): void
    {
        TeamActions::reactivate(auth()->user(), $this->member($userId));

        unset($this->staff);
        $this->dispatch('toast', message: 'Akun diaktifkan kembali', type: 'success');
    }

    /** Any user id from the browser; UserPolicy decides (403 for another clinic). */
    private function member(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function with(): array
    {
        return [
            'targets' => Assignment::therapists($this->clinic->id)->reject(fn ($t) => $t->id === $this->deactivating),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-page-header title="Tim klinik" :subtitle="'Fisioterapis dan admin '.$this->clinic->name.'.'" />

    <x-panel title="Undang fisioterapis" :sub="'Tautan undangan berlaku '.Invitations::EXPIRES_DAYS.' hari dan hanya bisa dipakai sekali.'">
        <form wire:submit="invite" class="flex flex-wrap items-end gap-2">
            <div class="min-w-64 flex-1"><x-ui.input type="email" label="Email" placeholder="nama@klinik.id" wire:model="email" /></div>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="invite"><x-lucide-mail class="size-4" /> Kirim undangan</x-ui.button>
        </form>

        @if ($inviteLink)
            <div class="mt-4 rounded-xl border border-border bg-muted/40 p-3" x-data="{ copied: false }">
                <p class="text-xs text-muted-foreground">Email belum terkirim (mail belum dikonfigurasi). Salin tautan ini dan kirim ke fisioterapis:</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <code class="break-all text-xs">{{ $inviteLink }}</code>
                    <x-ui.button size="sm" variant="outline" x-on:click="navigator.clipboard.writeText(@js($inviteLink)); copied = true">
                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                    </x-ui.button>
                </div>
            </div>
        @endif

        @if ($this->invitations->isNotEmpty())
            <div class="mt-5">
                <p class="text-sm font-medium">Undangan menunggu</p>
                @foreach ($this->invitations as $inv)
                    <div wire:key="inv-{{ $inv->id }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-border py-2 text-sm last:border-0">
                        <span>{{ $inv->email }} <span class="text-xs text-muted-foreground">· berlaku sampai {{ $inv->expires_at->translatedFormat('d M Y H:i') }}</span></span>
                        <x-ui.button size="sm" variant="outline" wire:click="cancelInvitation({{ $inv->id }})" wire:confirm="Batalkan undangan ini?">Batalkan</x-ui.button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-panel>

    <x-panel title="Anggota tim">
        <x-ui.table>
            <x-ui.table.header>
                <x-ui.table.row>
                    <x-ui.table.head>Nama</x-ui.table.head>
                    <x-ui.table.head>Email</x-ui.table.head>
                    <x-ui.table.head>Peran</x-ui.table.head>
                    <x-ui.table.head>Status</x-ui.table.head>
                    <x-ui.table.head>Pasien</x-ui.table.head>
                    <x-ui.table.head></x-ui.table.head>
                </x-ui.table.row>
            </x-ui.table.header>
            <x-ui.table.body>
                @foreach ($this->staff as $member)
                    <x-ui.table.row wire:key="m-{{ $member->id }}">
                        <x-ui.table.cell class="font-medium">{{ $member->name }}@if ($member->id === auth()->id()) <span class="text-xs text-muted-foreground">(Anda)</span>@endif</x-ui.table.cell>
                        <x-ui.table.cell>{{ $member->email }}</x-ui.table.cell>
                        <x-ui.table.cell>{{ $member->role === Role::ClinicAdmin ? 'Admin klinik' : 'Fisioterapis' }}</x-ui.table.cell>
                        <x-ui.table.cell>
                            <x-ui.badge :variant="$member->isDeactivated() ? 'destructive' : 'secondary'">{{ $member->isDeactivated() ? 'Nonaktif' : 'Aktif' }}</x-ui.badge>
                        </x-ui.table.cell>
                        <x-ui.table.cell>{{ $member->role === Role::Therapist ? $member->patients_count : '-' }}</x-ui.table.cell>
                        <x-ui.table.cell>
                            @can('deactivate', $member)
                                <x-ui.button size="sm" variant="outline" wire:click="startDeactivate({{ $member->id }})">Nonaktifkan</x-ui.button>
                            @endcan
                            @can('reactivate', $member)
                                <x-ui.button size="sm" variant="outline" wire:click="reactivate({{ $member->id }})">Aktifkan kembali</x-ui.button>
                            @endcan
                        </x-ui.table.cell>
                    </x-ui.table.row>

                    @if ($deactivating === $member->id)
                        <x-ui.table.row wire:key="d-{{ $member->id }}">
                            <x-ui.table.cell colspan="6">
                                <div class="space-y-3 rounded-xl border border-destructive/40 p-4">
                                    <p class="text-sm">
                                        Nonaktifkan <strong>{{ $member->name }}</strong>? Akun tidak bisa masuk lagi.
                                        {{ $member->patients_count }} pasien perlu dipindahkan atau dilepas.
                                    </p>
                                    <x-ui.select label="Pasien dipindahkan ke" wire:model="moveTo" class="max-w-72">
                                        <option value="">Lepaskan (belum ditugaskan)</option>
                                        @foreach ($targets as $t)
                                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                    @error('moveTo') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                                    <div class="flex gap-2">
                                        <x-ui.button variant="destructive" wire:click="deactivate" wire:loading.attr="disabled">Nonaktifkan</x-ui.button>
                                        <x-ui.button variant="outline" wire:click="$set('deactivating', null)">Batal</x-ui.button>
                                    </div>
                                </div>
                            </x-ui.table.cell>
                        </x-ui.table.row>
                    @endif
                @endforeach
            </x-ui.table.body>
        </x-ui.table>
    </x-panel>
</div>
