{{--
    Converted from BodyMap.tsx (React state -> Alpine).
    Usage inside a Livewire component:
        <x-body-map wire:model="form.pain_areas" />     (public array $pain_areas = [] on the Livewire component or form)
    Without wire:model it works standalone (selection stays in the browser).
--}}
@php
    $model = $attributes->whereStartsWith('wire:model')->first();

    $views = [
        'front' => [
        ['id' => 'Head', 'label' => 'Head', 'd' => 'M100 8 a16 16 0 1 1 0 32 a16 16 0 1 1 0 -32'],
        ['id' => 'Neck', 'label' => 'Neck', 'd' => 'M90 41 h20 v12 h-20 z'],
        ['id' => 'Right Shoulder', 'label' => 'Right Shoulder', 'd' => 'M62 55 h22 v20 h-22 z'],
        ['id' => 'Left Shoulder', 'label' => 'Left Shoulder', 'd' => 'M116 55 h22 v20 h-22 z'],
        ['id' => 'Chest', 'label' => 'Chest', 'd' => 'M86 55 h28 v30 h-28 z'],
        ['id' => 'Abdomen', 'label' => 'Abdomen', 'd' => 'M86 87 h28 v34 h-28 z'],
        ['id' => 'Right Elbow', 'label' => 'Right Elbow', 'd' => 'M58 78 h22 v18 h-22 z'],
        ['id' => 'Left Elbow', 'label' => 'Left Elbow', 'd' => 'M120 78 h22 v18 h-22 z'],
        ['id' => 'Right Wrist', 'label' => 'Right Wrist / Hand', 'd' => 'M54 99 h20 v22 h-20 z'],
        ['id' => 'Left Wrist', 'label' => 'Left Wrist / Hand', 'd' => 'M126 99 h20 v22 h-20 z'],
        ['id' => 'Right Hip', 'label' => 'Right Hip', 'd' => 'M84 123 h15 v20 h-15 z'],
        ['id' => 'Left Hip', 'label' => 'Left Hip', 'd' => 'M101 123 h15 v20 h-15 z'],
        ['id' => 'Right Knee', 'label' => 'Right Knee', 'd' => 'M84 176 h15 v20 h-15 z'],
        ['id' => 'Left Knee', 'label' => 'Left Knee', 'd' => 'M101 176 h15 v20 h-15 z'],
        ['id' => 'Right Thigh', 'label' => 'Right Thigh', 'd' => 'M84 145 h15 v29 h-15 z'],
        ['id' => 'Left Thigh', 'label' => 'Left Thigh', 'd' => 'M101 145 h15 v29 h-15 z'],
        ['id' => 'Right Ankle', 'label' => 'Right Ankle / Foot', 'd' => 'M84 226 h15 v20 h-15 z'],
        ['id' => 'Left Ankle', 'label' => 'Left Ankle / Foot', 'd' => 'M101 226 h15 v20 h-15 z'],
        ['id' => 'Right Shin', 'label' => 'Right Shin', 'd' => 'M84 198 h15 v26 h-15 z'],
        ['id' => 'Left Shin', 'label' => 'Left Shin', 'd' => 'M101 198 h15 v26 h-15 z'],
        ],
        'back' => [
        ['id' => 'Back of Head', 'label' => 'Back of Head', 'd' => 'M100 8 a16 16 0 1 1 0 32 a16 16 0 1 1 0 -32'],
        ['id' => 'Cervical Spine', 'label' => 'Neck / Cervical Spine', 'd' => 'M90 41 h20 v12 h-20 z'],
        ['id' => 'Upper Back', 'label' => 'Upper Back', 'd' => 'M80 55 h40 v34 h-40 z'],
        ['id' => 'Lower Back', 'label' => 'Lower Back', 'd' => 'M82 91 h36 v30 h-36 z'],
        ['id' => 'Right Scapula', 'label' => 'Right Scapula', 'd' => 'M58 58 h20 v30 h-20 z'],
        ['id' => 'Left Scapula', 'label' => 'Left Scapula', 'd' => 'M122 58 h20 v30 h-20 z'],
        ['id' => 'Right Forearm', 'label' => 'Right Forearm', 'd' => 'M54 92 h20 v30 h-20 z'],
        ['id' => 'Left Forearm', 'label' => 'Left Forearm', 'd' => 'M126 92 h20 v30 h-20 z'],
        ['id' => 'Glutes', 'label' => 'Glutes', 'd' => 'M82 123 h36 v24 h-36 z'],
        ['id' => 'Right Hamstring', 'label' => 'Right Hamstring', 'd' => 'M84 149 h15 v32 h-15 z'],
        ['id' => 'Left Hamstring', 'label' => 'Left Hamstring', 'd' => 'M101 149 h15 v32 h-15 z'],
        ['id' => 'Right Calf', 'label' => 'Right Calf', 'd' => 'M84 185 h15 v40 h-15 z'],
        ['id' => 'Left Calf', 'label' => 'Left Calf', 'd' => 'M101 185 h15 v40 h-15 z'],
        ['id' => 'Right Heel', 'label' => 'Right Heel', 'd' => 'M84 228 h15 v18 h-15 z'],
        ['id' => 'Left Heel', 'label' => 'Left Heel', 'd' => 'M101 228 h15 v18 h-15 z'],
        ],
    ];

    $on  = 'fill-[var(--color-primary)] stroke-[var(--color-primary)]';
    $off = 'fill-[var(--color-card)] stroke-[var(--color-border)] hover:fill-[var(--color-secondary)] focus-visible:fill-[var(--color-secondary)]';
@endphp

<div
    x-data="{
        view: 'front',
        hover: null,
        selected: {{ $model ? '$wire.entangle(\''.$model.'\')' : '[]' }},
        toggle(id) {
            this.selected = this.selected.includes(id)
                ? this.selected.filter(i => i !== id)
                : [...this.selected, id];
        },
    }"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div class="flex items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-border bg-card p-1">
            @foreach (['front', 'back'] as $v)
                <button type="button" x-on:click="view = '{{ $v }}'"
                    class="px-4 py-1.5 text-sm rounded-lg capitalize transition-colors"
                    x-bind:class="view === '{{ $v }}' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'">
                    {{ $v }} view
                </button>
            @endforeach
        </div>
        <p class="text-xs text-muted-foreground" x-text="hover ?? 'Tap an area to select'"></p>
    </div>

    <div class="mt-4 rounded-2xl border border-border bg-[image:var(--gradient-soft)] p-4">
        <svg viewBox="0 0 200 252" class="mx-auto h-[420px] w-full max-w-[280px] touch-pan-y" role="group">
            @foreach ($views as $viewName => $regions)
                <g x-show="view === '{{ $viewName }}'" @if ($viewName !== 'front') x-cloak @endif>
                    @foreach ($regions as $r)
                        <path d="{{ $r['d'] }}"
                              role="button" tabindex="0"
                              aria-label="{{ $r['label'] }}"
                              x-bind:aria-pressed="selected.includes(@js($r['id'])) ? 'true' : 'false'"
                              x-on:click="toggle(@js($r['id']))"
                              x-on:keydown.enter.prevent="toggle(@js($r['id']))"
                              x-on:keydown.space.prevent="toggle(@js($r['id']))"
                              x-on:mouseenter="hover = @js($r['label'])"
                              x-on:mouseleave="hover = null"
                              x-on:focus="hover = @js($r['label'])"
                              x-on:blur="hover = null"
                              stroke-width="1.5"
                              class="cursor-pointer outline-none transition-[fill,stroke]"
                              x-bind:class="selected.includes(@js($r['id'])) ? @js($on) : @js($off)" />
                    @endforeach
                </g>
            @endforeach
        </svg>
    </div>
</div>
