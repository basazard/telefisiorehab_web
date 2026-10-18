<div>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold">Lupa kata sandi?</h1>
        <p class="mt-1 text-sm text-muted-foreground">Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="mt-1.5" type="email" name="email" placeholder="nama@email.com" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="sendPasswordResetLink">
            <span wire:loading.remove wire:target="sendPasswordResetLink">Kirim tautan reset</span>
            <span wire:loading wire:target="sendPasswordResetLink">Mengirim…</span>
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center gap-1 font-medium text-primary hover:underline">
            <x-lucide-arrow-left class="size-3.5" /> Kembali ke halaman masuk
        </a>
    </p>
</div>
