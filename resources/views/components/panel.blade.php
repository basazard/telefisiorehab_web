@props(['title' => null, 'sub' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-border bg-card p-6 shadow-[var(--shadow-card)]']) }}>
    @if ($title)
        <h3 class="font-display font-bold">{{ $title }}</h3>
    @endif
    @if ($sub)
        <p class="text-xs text-muted-foreground">{{ $sub }}</p>
    @endif
    <div class="{{ $title ? 'mt-4' : '' }}">{{ $slot }}</div>
</div>
