<h3 class="flex">
    <button type="button" x-on:click="open = open === id ? null : id" x-bind:aria-expanded="open === id ? 'true' : 'false'"
        {{ $attributes->merge(['class' => 'flex flex-1 items-center justify-between py-4 text-sm font-medium cursor-pointer transition-all hover:underline text-left']) }}>
        {{ $slot }}
        <x-lucide-chevron-down class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" x-bind:class="open === id && 'rotate-180'" />
    </button>
</h3>
