@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-xl border border-success/40 bg-success/10 px-4 py-3 text-sm text-foreground']) }}>
        <x-lucide-circle-check class="mt-0.5 size-4 shrink-0 text-success" />
        <span>{{ $status }}</span>
    </div>
@endif
