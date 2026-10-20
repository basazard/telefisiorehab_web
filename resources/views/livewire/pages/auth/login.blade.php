<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold">Selamat datang kembali</h1>
        <p class="mt-1 text-sm text-muted-foreground">Masuk untuk melanjutkan program rehabilitasi Anda.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="form.email" id="email" class="mt-1.5" type="email" name="email" placeholder="nama@email.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Kata sandi" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-primary hover:underline" href="{{ route('password.request') }}" wire:navigate>Lupa kata sandi?</a>
                @endif
            </div>
            <x-password-input wire:model="form.password" id="password" class="mt-1.5" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <label for="remember" class="flex items-center gap-2 text-sm text-muted-foreground">
            <x-ui.checkbox wire:model="form.remember" id="remember" name="remember" />
            Ingat saya
        </label>

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses…</span>
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-muted-foreground">
            Belum punya akun?
            <a href="{{ route('register') }}" wire:navigate class="font-medium text-primary hover:underline">Daftar</a>
        </p>
    @endif
</div>
