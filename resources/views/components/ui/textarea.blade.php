@props(['label' => null])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $inputId = $attributes->get('id') ?? 'f-'.substr(md5(($model ?? '').($attributes->get('name') ?? '').($label ?? '')), 0, 8);
@endphp

<div class="space-y-1.5">
    @if ($label)
        <x-ui.label :for="$inputId">{{ $label }}</x-ui.label>
    @endif

    <textarea id="{{ $inputId }}" {{ $attributes->except('id')->merge(['class' => 'flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm']) }}>{{ $slot }}</textarea>

    @if ($model && $errors->has($model))
        <p class="text-xs text-destructive">{{ $errors->first($model) }}</p>
    @endif
</div>
