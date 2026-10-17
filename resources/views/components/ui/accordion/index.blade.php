{{-- Single-open accordion. <x-ui.accordion><x-ui.accordion.item value="a"><x-ui.accordion.trigger>Q</x-ui.accordion.trigger><x-ui.accordion.content>A</x-ui.accordion.content></x-ui.accordion.item></x-ui.accordion> --}}
<div x-data="{ open: null }" {{ $attributes }}>{{ $slot }}</div>
