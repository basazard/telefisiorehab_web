<button type="button"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-6 py-3 text-sm font-medium hover:bg-secondary disabled:opacity-40']) }}>
    {{ $slot }}
</button>
