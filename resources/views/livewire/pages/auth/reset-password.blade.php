<div>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold">Buat kata sandi baru</h1>
        <p class="mt-1 text-sm text-muted-foreground">Pilih kata sandi baru yang kuat untuk akun Anda.</p>
    </div>

    <form wire:submit="resetPassword" class="space-y-5">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="mt-1.5" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi baru" />
            <x-password-input wire:model="password" id="password" class="mt-1.5" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="mt-1.5" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">Simpan kata sandi baru</span>
            <span wire:loading wire:target="resetPassword">Menyimpan…</span>
        </x-primary-button>
    </form>
</div>
