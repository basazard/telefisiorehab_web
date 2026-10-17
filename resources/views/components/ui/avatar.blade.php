{{-- Usage: <x-ui.avatar :src="$user->photo_url" fallback="BA" /> --}}
@props(['src' => null, 'fallback' => null])

<span x-data="{ failed: {{ $src ? 'false' : 'true' }} }"
      {{ $attributes->merge(['class' => 'relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="" class="aspect-square h-full w-full" x-show="!failed" x-on:error="failed = true">
    @endif
    <span x-show="failed" class="flex h-full w-full items-center justify-center rounded-full bg-muted">{{ $fallback ?? $slot }}</span>
</span>
