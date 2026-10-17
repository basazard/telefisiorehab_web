{{-- Usage: <x-ui.switch wire:model.live="form.has_special_condition" /> --}}
<label class="inline-flex cursor-pointer items-center">
    <input type="checkbox" role="switch" {{ $attributes->merge(['class' => 'peer sr-only']) }}>
    <span class="relative h-5 w-9 shrink-0 rounded-full bg-input shadow-sm transition-colors peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-background peer-disabled:cursor-not-allowed peer-disabled:opacity-50 after:absolute after:left-0.5 after:top-0.5 after:block after:h-4 after:w-4 after:rounded-full after:bg-background after:shadow-lg after:transition-transform peer-checked:after:translate-x-4"></span>
</label>
