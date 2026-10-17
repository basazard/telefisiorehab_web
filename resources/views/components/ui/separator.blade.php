@props(['orientation' => 'horizontal'])

<div role="separator" aria-orientation="{{ $orientation }}"
     {{ $attributes->merge(['class' => 'shrink-0 bg-border '.($orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]')]) }}></div>
