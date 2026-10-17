@props(['value'])

<button type="button" role="tab"
    x-on:click="tab = @js($value)"
    x-bind:aria-selected="tab === @js($value) ? 'true' : 'false'"
    x-bind:data-state="tab === @js($value) ? 'active' : 'inactive'"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background cursor-pointer transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 disabled:cursor-not-allowed data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow']) }}>
    {{ $slot }}
</button>
