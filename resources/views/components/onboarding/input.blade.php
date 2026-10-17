{{-- legacy `inputClass` string as a component; shows Livewire validation errors automatically --}}
@php $model = $attributes->whereStartsWith('wire:model')->first(); @endphp

<div>
    <input {{ $attributes->merge(['class' => 'w-full rounded-xl border border-input bg-card px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring']) }}>
    @if ($model && $errors->has($model))
        <p class="mt-1 text-xs text-destructive">{{ $errors->first($model) }}</p>
    @endif
</div>
