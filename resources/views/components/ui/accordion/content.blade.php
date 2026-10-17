<div x-show="open === id" x-collapse x-cloak class="overflow-hidden text-sm">
    <div {{ $attributes->merge(['class' => 'pb-4 pt-0']) }}>{{ $slot }}</div>
</div>
