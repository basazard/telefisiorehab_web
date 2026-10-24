<?php

use App\Models\StaffInvitation;
use App\Support\Staff\Invitations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

// A new therapist accepts the clinic's invitation (PRD 09 S4.2): name and password, then they are signed in.
new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';

    public string $name = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function invitation(): ?StaffInvitation
    {
        return Invitations::find($this->token)?->load('clinic:id,name');
    }

    public function accept(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Invitations::accept($this->token, $this->name, $this->password);

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    @php $invitation = $this->invitation(); @endphp

    @if (! $invitation)
        <div class="mb-6">
            <span class="mb-4 grid size-11 place-items-center rounded-xl bg-destructive/10 text-destructive"><x-lucide-link-2-off class="size-5" /></span>
            <h1 class="font-display text-2xl font-bold">Undangan tidak berlaku</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Tautan ini sudah dipakai, sudah kedaluwarsa, atau dibatalkan. Minta admin klinik Anda mengirim undangan baru.
            </p>
        </div>
        <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">Ke halaman masuk</a>
    @else
        <div class="mb-8">
            <h1 class="font-display text-2xl font-bold">Bergabung dengan {{ $invitation->clinic->name }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">Buat akun fisioterapis untuk <strong>{{ $invitation->email }}</strong>.</p>
        </div>

        @error('token') <x-auth-session-status class="mb-4" :status="$message" /> @enderror

        <form wire:submit="accept" class="space-y-5">
            <div>
                <x-input-label for="name" value="Nama lengkap" />
                <x-text-input wire:model="name" id="name" class="mt-1.5" type="text" name="name" placeholder="Nama dan gelar, mis. Ani Lestari, S.Ft." required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="password" value="Kata sandi" />
                <x-password-input wire:model="password" id="password" class="mt-1.5" name="password" required autocomplete="new-password" />
                <p class="mt-1.5 text-xs text-muted-foreground">Gunakan minimal 8 karakter.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
                <x-password-input wire:model="password_confirmation" id="password_confirmation" class="mt-1.5" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>

            <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="accept">
                <span wire:loading.remove wire:target="accept">Terima undangan</span>
                <span wire:loading wire:target="accept">Memproses…</span>
            </x-primary-button>
        </form>
    @endif
</div>
