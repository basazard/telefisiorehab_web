<section>
    <header>
        <h2 class="font-display text-lg font-bold">Ubah kata sandi</h2>
        <p class="mt-1 text-sm text-muted-foreground">Gunakan kata sandi yang panjang dan acak agar akun Anda tetap aman.</p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-5">
        <div>
            <x-input-label for="update_password_current_password" value="Kata sandi saat ini" />
            <x-password-input wire:model="current_password" id="update_password_current_password" name="current_password" class="mt-1.5" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Kata sandi baru" />
            <x-password-input wire:model="password" id="update_password_password" name="password" class="mt-1.5" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Ulangi kata sandi baru" />
            <x-password-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" class="mt-1.5" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Simpan</x-primary-button>

            <x-action-message class="me-3" on="password-updated">
                Tersimpan.
            </x-action-message>
        </div>
    </form>
</section>
