{{-- Usage: <x-onboarding.chip :active="in_array('x', $form->goals)" wire:click="toggleGoal('x')" :disabled="..."> --}}
@props(['active' => false])

<button type="button" aria-pressed="{{ $active ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => 'rounded-full border px-4 py-2 text-sm transition-colors disabled:opacity-40 '
        .($active ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-foreground hover:bg-secondary')]) }}>
    {{ $slot }}
</button>
