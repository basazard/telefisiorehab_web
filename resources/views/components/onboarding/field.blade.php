@props(['label'])

<label class="block">
    <span class="text-sm font-medium">{{ $label }}</span>
    <div class="mt-2">{{ $slot }}</div>
</label>
