<section class="space-y-6">
    <header>
        <h2 class="font-display text-lg font-bold text-destructive">Hapus akun</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            Setelah akun dihapus, seluruh data dan sumber dayanya ikut terhapus permanen. Unduh data yang ingin Anda simpan sebelum melanjutkan.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="font-display text-lg font-bold">Yakin ingin menghapus akun Anda?</h2>

            <p class="mt-2 text-sm text-muted-foreground">
                Setelah akun dihapus, seluruh data dan sumber dayanya ikut terhapus permanen. Masukkan kata sandi Anda untuk mengonfirmasi.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata sandi" class="sr-only" />

                <x-text-input wire:model="password" id="password" name="password" type="password" class="block w-full sm:w-3/4" placeholder="Kata sandi" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>

                <x-danger-button>Hapus akun</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
