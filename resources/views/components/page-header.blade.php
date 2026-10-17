@props(['title', 'subtitle' => null])

<div {{ $attributes }}>
    <h1 class="text-2xl md:text-3xl font-display font-bold">{{ $title }}</h1>
    @if ($subtitle)
        <p class="text-sm text-muted-foreground mt-1">{{ $subtitle }}</p>
    @endif
</div>
