@props(['label', 'value', 'sub' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]']) }}>
    <p class="text-xs text-muted-foreground">{{ $label }}</p>
    <p class="text-2xl font-display font-bold mt-1">{{ $value }}</p>
    @if ($sub)
        <p class="text-[11px] text-accent mt-1">{{ $sub }}</p>
    @endif
</div>
