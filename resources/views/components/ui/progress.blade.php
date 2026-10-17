@props(['value' => 0])

<div role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $value }}"
     {{ $attributes->merge(['class' => 'relative h-2 w-full overflow-hidden rounded-full bg-primary/20']) }}>
    <div class="h-full w-full flex-1 bg-primary transition-all" style="transform: translateX(-{{ 100 - max(0, min(100, (float) $value)) }}%)"></div>
</div>
