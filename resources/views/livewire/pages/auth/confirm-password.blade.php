<div>
    <div class="mb-8">
        <span class="mb-4 grid size-11 place-items-center rounded-xl bg-primary/10 text-primary"><x-lucide-lock class="size-5" /></span>
        <h1 class="font-display text-2xl font-bold">Konfirmasi kata sandi</h1>
        <p class="mt-1 text-sm text-muted-foreground">Ini area yang aman. Mohon konfirmasi kata sandi Anda sebelum melanjutkan.</p>
    </div>

    <form wire:submit="confirmPassword" class="space-y-5">
        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-password-input wire:model="password" id="password" class="mt-1.5" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="confirmPassword">
            <span wire:loading.remove wire:target="confirmPassword">Konfirmasi</span>
            <span wire:loading wire:target="confirmPassword">Memeriksa…</span>
        </x-primary-button>
    </form>
</div>
