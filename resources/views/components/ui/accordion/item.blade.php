@props(['value'])

<div x-data="{ id: @js($value) }" {{ $attributes->merge(['class' => 'border-b']) }}>{{ $slot }}</div>
