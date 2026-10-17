{{-- Converted from ui/input.tsx, plus optional label/hint and automatic Livewire error display --}}
@props(['label' => null, 'hint' => null])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $inputId = $attributes->get('id') ?? 'f-'.substr(md5(($model ?? '').($attributes->get('name') ?? '').($label ?? '')), 0, 8);
@endphp

<div class="space-y-1.5">
    @if ($label)
        <x-ui.label :for="$inputId">{{ $label }}</x-ui.label>
    @endif

    <input id="{{ $inputId }}" {{ $attributes->except('id')->merge(['class' => 'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm']) }}>

    @if ($hint)
        <p class="text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
    @if ($model && $errors->has($model))
        <p class="text-xs text-destructive">{{ $errors->first($model) }}</p>
    @endif
</div>
