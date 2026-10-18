@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-foreground']) }}>
    {{ $value ?? $slot }}
</label>
