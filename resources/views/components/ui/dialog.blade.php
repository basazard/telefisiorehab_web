{{-- Usage:
<x-ui.dialog name="confirm-delete" title="Delete program?" description="This cannot be undone.">
    <x-slot:trigger><x-ui.button variant="destructive">Delete</x-ui.button></x-slot:trigger>
    ...body...
    <x-slot:footer><x-ui.button wire:click="delete">Confirm</x-ui.button></x-slot:footer>
</x-ui.dialog>
Open from anywhere:  $dispatch('open-dialog', 'confirm-delete')   |  from Livewire: $this->dispatch('open-dialog', 'confirm-delete')
Close: $dispatch('close-dialog')
--}}
@props(['name', 'title' => null, 'description' => null])

<div x-data="{ open: false }"
     x-on:open-dialog.window="if ($event.detail === @js($name) || $event.detail?.name === @js($name)) open = true"
     x-on:close-dialog.window="open = false"
     x-on:keydown.escape.window="open = false">

    @isset($trigger)
        <div x-on:click="open = true" class="contents">{{ $trigger }}</div>
    @endisset

    <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80" x-on:click="open = false" x-transition.opacity></div>

        <div x-show="open" x-transition x-trap.noscroll="open"
             {{ $attributes->merge(['class' => 'fixed left-1/2 top-1/2 z-50 grid w-full max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 border bg-background p-6 shadow-lg sm:rounded-lg']) }}>
            @if ($title || $description)
                <div class="flex flex-col space-y-1.5 text-center sm:text-left">
                    @if ($title)<h2 class="text-lg font-semibold leading-none tracking-tight">{{ $title }}</h2>@endif
                    @if ($description)<p class="text-sm text-muted-foreground">{{ $description }}</p>@endif
                </div>
            @endif

            {{ $slot }}

            @isset($footer)
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">{{ $footer }}</div>
            @endisset

            <button type="button" x-on:click="open = false"
                class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background cursor-pointer transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                <x-lucide-x class="h-4 w-4" />
                <span class="sr-only">Close</span>
            </button>
        </div>
    </div>
</div>
