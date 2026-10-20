<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <span class="mb-4 grid size-11 place-items-center rounded-xl bg-primary/10 text-primary"><x-lucide-mail class="size-5" /></span>
        <h1 class="font-display text-2xl font-bold">Verifikasi email Anda</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Terima kasih sudah mendaftar. Klik tautan yang kami kirim ke email Anda untuk memverifikasi akun.
            Belum menerima email? Kami dapat mengirim ulang.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-4" status="Tautan verifikasi baru telah dikirim ke email yang Anda gunakan saat mendaftar." />
    @endif

    <div class="flex items-center justify-between gap-4">
        <x-primary-button wire:click="sendVerification" wire:loading.attr="disabled" wire:target="sendVerification">
            Kirim ulang email verifikasi
        </x-primary-button>

        <button wire:click="logout" type="button" class="text-sm font-medium text-muted-foreground hover:text-foreground hover:underline">
            Keluar
        </button>
    </div>
</div>
