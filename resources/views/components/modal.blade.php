@props(['name', 'show' => false, 'maxWidth' => '2xl'])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth];
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
    @if (! $show) style="display: none;" @endif
>
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/60" x-on:click="show = false"></div>

    <div x-show="show" x-transition x-trap.noscroll="show"
         class="relative w-full overflow-hidden rounded-2xl border border-border bg-card shadow-xl {{ $maxWidth }}">
        {{ $slot }}
    </div>
</div>
