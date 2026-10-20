<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        $user->patient()->create();

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold">Buat akun Anda</h1>
        <p class="mt-1 text-sm text-muted-foreground">Mulai program fisioterapi di rumah dalam beberapa menit.</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nama lengkap" />
            <x-text-input wire:model="name" id="name" class="mt-1.5" type="text" name="name" placeholder="Nama sesuai identitas" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="mt-1.5" type="email" name="email" placeholder="nama@email.com" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-password-input wire:model="password" id="password" class="mt-1.5" name="password" required autocomplete="new-password" />
            <p class="mt-1.5 text-xs text-muted-foreground">Gunakan minimal 8 karakter.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="mt-1.5" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Daftar</span>
            <span wire:loading wire:target="register">Memproses…</span>
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary hover:underline">Masuk</a>
    </p>
</div>
