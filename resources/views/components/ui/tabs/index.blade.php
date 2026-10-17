{{-- Usage:
<x-ui.tabs default="program">
    <x-ui.tabs.list>
        <x-ui.tabs.trigger value="program">Program</x-ui.tabs.trigger>
        <x-ui.tabs.trigger value="history">History</x-ui.tabs.trigger>
    </x-ui.tabs.list>
    <x-ui.tabs.content value="program">...</x-ui.tabs.content>
</x-ui.tabs>
--}}
@props(['default'])

<div x-data="{ tab: @js($default) }" {{ $attributes }}>{{ $slot }}</div>
