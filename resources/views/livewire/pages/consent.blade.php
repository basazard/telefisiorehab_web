<?php

use App\Livewire\Actions\Logout;
use App\Support\Legal;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Consent to the current terms, privacy and data statements (PRD 11 E4). Every statement must be ticked.
new #[Layout('layouts.guest')] class extends Component
{
    /** @var array<string, bool> statement key => ticked */
    public array $agreed = [];

    public function mount(): void
    {
        if (Legal::hasConsented(auth()->user())) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
        }
    }

    public function accept(): void
    {
        $items = Legal::items(auth()->user());
        $missing = array_keys(array_filter($items, fn ($text, $key) => ! ($this->agreed[$key] ?? false), ARRAY_FILTER_USE_BOTH));

        if ($missing) {
            foreach ($missing as $key) {
                $this->addError("agreed.$key", 'Centang pernyataan ini untuk melanjutkan.');
            }

            return;
        }

        if (! Legal::hasConsented(auth()->user())) {
            Legal::accept(auth()->user());
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function with(): array
    {
        return ['items' => Legal::items(auth()->user())];
    }
}; ?>

<div>
    <div class="mb-6">
        <span class="mb-4 grid size-11 place-items-center rounded-xl bg-primary/10 text-primary"><x-lucide-shield-check class="size-5" /></span>
        <h1 class="font-display text-2xl font-bold">Persetujuan penggunaan</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Sebelum melanjutkan, baca
            <a href="{{ route('legal.terms') }}" target="_blank" class="font-medium text-primary hover:underline">Syarat Penggunaan</a>
            dan
            <a href="{{ route('legal.privacy') }}" target="_blank" class="font-medium text-primary hover:underline">Kebijakan Privasi</a>,
            lalu centang setiap pernyataan di bawah.
        </p>
    </div>

    <form wire:submit="accept" class="space-y-4">
        @foreach ($items as $key => $text)
            <div wire:key="item-{{ $key }}">
                <label class="flex items-start gap-3 rounded-xl border border-border bg-card p-4 text-sm">
                    <input type="checkbox" wire:model="agreed.{{ $key }}" class="mt-0.5 size-4 shrink-0 rounded border-input text-primary">
                    <span>{{ $text }}</span>
                </label>
                @error("agreed.$key") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
            </div>
        @endforeach

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="accept">Setuju dan lanjutkan</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Tidak setuju?
        <button type="button" wire:click="logout" class="font-medium text-primary hover:underline">Keluar</button>
    </p>
</div>
