{{--
    Body map v2: anatomical figure (front / back) with tappable regions.
    Usage inside a Livewire component:  <x-body-map wire:model="regions" />   (public array $regions = [])
    Without wire:model it works standalone (selection stays in the browser).
    "Kiri" / "Left" is the viewer's left in both views. Geometry: App\Support\Onboarding\BodyMapGeometry.
--}}
@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $geo = \App\Support\Onboarding\BodyMapGeometry::class;
    $labels = \App\Support\Onboarding\BodyRegions::LABELS;
    $uid = 'bm'.substr(md5($model ?? 'standalone'), 0, 6);
    $views = ['front' => $geo::front(), 'back' => $geo::back()];
    $viewLabels = ['front' => 'Tampak depan', 'back' => 'Tampak belakang'];

    $on  = 'fill-primary/50 stroke-primary stroke-[1.6]';
    $off = 'fill-transparent stroke-transparent hover:fill-primary/20 hover:stroke-primary/60 focus-visible:fill-primary/20 focus-visible:stroke-primary';
@endphp

<div
    x-data="{
        view: 'front',
        hover: null,
        labels: @js($labels),
        selected: {{ $model ? '$wire.entangle(\''.$model.'\')' : '[]' }},
        toggle(id) {
            this.selected = this.selected.includes(id)
                ? this.selected.filter(i => i !== id)
                : [...this.selected, id];
        },
        label(id) { return this.labels[id] ?? id; },
    }"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-border bg-card p-1">
            @foreach ($viewLabels as $v => $text)
                <button type="button" x-on:click="view = '{{ $v }}'"
                    class="rounded-lg px-4 py-1.5 text-sm transition-colors"
                    x-bind:class="view === '{{ $v }}' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground'">
                    {{ $text }}
                </button>
            @endforeach
        </div>
        <p class="text-xs text-muted-foreground" x-text="hover ?? 'Ketuk area tubuh yang bermasalah'"></p>
    </div>

    <div class="mt-4 rounded-2xl border border-border bg-[image:var(--gradient-soft)] p-4">
        <svg viewBox="0 0 200 440" class="mx-auto h-[480px] w-full max-w-[240px] touch-pan-y select-none" role="group" aria-label="Peta tubuh">
            <defs>
                <radialGradient id="{{ $uid }}-hl">
                    <stop offset="0" style="stop-color: var(--color-card)" stop-opacity=".85" />
                    <stop offset="1" style="stop-color: var(--color-card)" stop-opacity="0" />
                </radialGradient>
                <filter id="{{ $uid }}-blur" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3.2" />
                </filter>
                <clipPath id="{{ $uid }}-sil"><path d="{{ $geo::SILHOUETTE }}" /></clipPath>
            </defs>

            <text x="6" y="12" class="fill-muted-foreground" font-size="7" font-weight="600">KIRI</text>
            <text x="194" y="12" text-anchor="end" class="fill-muted-foreground" font-size="7" font-weight="600">KANAN</text>

            @foreach ($views as $name => $v)
                <g x-show="view === '{{ $name }}'" @if ($name !== 'front') x-cloak @endif>
                    {{-- body, edge shading and volume highlights --}}
                    <path d="{{ $geo::SILHOUETTE }}" class="fill-secondary" />
                    <g clip-path="url(#{{ $uid }}-sil)">
                        <path d="{{ $geo::SILHOUETTE }}" fill="none" class="stroke-foreground/25" stroke-width="9" filter="url(#{{ $uid }}-blur)" />
                        @foreach ($v['highlights'] as [$cx, $cy, $rx, $ry])
                            <ellipse cx="{{ $cx }}" cy="{{ $cy }}" rx="{{ $rx }}" ry="{{ $ry }}" fill="url(#{{ $uid }}-hl)" />
                            <ellipse cx="{{ 200 - $cx }}" cy="{{ $cy }}" rx="{{ $rx }}" ry="{{ $ry }}" fill="url(#{{ $uid }}-hl)" />
                        @endforeach
                    </g>
                    <path d="{{ $geo::SILHOUETTE }}" fill="none" class="stroke-foreground/35" stroke-width="1" stroke-linejoin="round" />

                    {{-- head --}}
                    <path d="{{ $geo::HEAD }}" class="fill-secondary stroke-foreground/35" stroke-width="1" />
                    @foreach ($geo::EARS as $e)
                        <path d="{{ $e }}" class="fill-secondary stroke-foreground/30" stroke-width=".8" />
                    @endforeach
                    <path d="{{ $v['hair'] }}" class="fill-foreground/60" />
                    @foreach ($v['face'] as $f)
                        <path d="{{ $f }}" fill="none" class="stroke-foreground/50" stroke-width=".9" stroke-linecap="round" />
                    @endforeach

                    {{-- anatomy lines --}}
                    @foreach ($v['details'] as $d)
                        <path d="{{ $d }}" fill="none" class="stroke-foreground/20" stroke-width=".9" stroke-linecap="round" stroke-linejoin="round" />
                    @endforeach

                    {{-- tappable regions --}}
                    @foreach ($v['regions'] as $r)
                        <path d="{{ $r['d'] }}"
                              role="button" tabindex="0"
                              aria-label="{{ $labels[$r['id']] }}"
                              x-bind:aria-pressed="selected.includes(@js($r['id'])) ? 'true' : 'false'"
                              x-on:click="toggle(@js($r['id']))"
                              x-on:keydown.enter.prevent="toggle(@js($r['id']))"
                              x-on:keydown.space.prevent="toggle(@js($r['id']))"
                              x-on:mouseenter="hover = @js($labels[$r['id']])"
                              x-on:mouseleave="hover = null"
                              x-on:focus="hover = @js($labels[$r['id']])"
                              x-on:blur="hover = null"
                              stroke-width="1" stroke-linejoin="round"
                              class="cursor-pointer outline-none transition-colors duration-150"
                              x-bind:class="selected.includes(@js($r['id'])) ? @js($on) : @js($off)" />
                    @endforeach
                </g>
            @endforeach
        </svg>
    </div>

    {{-- selected areas as removable chips --}}
    <div class="mt-3 flex flex-wrap gap-2" x-show="selected.length" x-cloak>
        <template x-for="id in selected" :key="id">
            <button type="button" x-on:click="toggle(id)"
                class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                <span x-text="label(id)"></span>
                <span aria-hidden="true">×</span>
            </button>
        </template>
    </div>
</div>
