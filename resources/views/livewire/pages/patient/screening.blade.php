<?php

use App\Support\Onboarding\Assessment;
use App\Support\Onboarding\BodyRegions;
use App\Support\Onboarding\Questionnaires;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.onboarding')] class extends Component {
    public int $step = 1;               // 1 = pick body regions, 2 = describe each complaint

    /** @var string[] */
    public array $regions = [];

    /** @var array<int, array{region:string,onset:string,onsetUnknown:bool,onsetType:string,characters:array,aggravators:array,nrs:?int}> */
    public array $complaints = [];

    public function mount(): void
    {
        $screening = auth()->user()->patient->screenings()->whereNull('completed_at')->latest()->first();

        if ($screening) {
            $this->regions = $screening->regions ?? [];

            foreach ($screening->complaints ?? [] as $region => $c) {
                $this->complaints[] = ['region' => $region] + $c;
            }

            $this->step = $this->complaints ? 2 : 1;
        }
    }

    private function blankComplaint(string $region): array
    {
        return [
            'region' => $region,
            'onset' => '',
            'onsetUnknown' => false,
            'onsetType' => '',
            'characters' => [],
            'aggravators' => [],
            'nrs' => null,            // must be chosen explicitly (0 = no pain is a valid answer)
        ];
    }

    private function syncComplaints(): void
    {
        $existing = collect($this->complaints)->keyBy('region');

        $this->complaints = collect($this->regions)
            ->map(fn (string $r) => $existing->get($r) ?? $this->blankComplaint($r))
            ->values()
            ->all();
    }

    public function next(): void
    {
        $this->regions = array_values(array_unique($this->regions));

        $this->validate([
            'regions' => ['required', 'array', 'min:1'],
            'regions.*' => [Rule::in(BodyRegions::IDS)],
        ], [
            'regions.required' => 'Pilih minimal satu area tubuh yang bermasalah.',
            'regions.min' => 'Pilih minimal satu area tubuh yang bermasalah.',
        ]);

        $this->syncComplaints();
        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = 1;
    }

    public function setNrs(int $i, int $value): void
    {
        if (isset($this->complaints[$i]) && $value >= 0 && $value <= 10) {
            $this->complaints[$i]['nrs'] = $value;
        }
    }

    public function setOnsetType(int $i, string $type): void
    {
        if (isset($this->complaints[$i]) && in_array($type, ['sudden', 'gradual'], true)) {
            $this->complaints[$i]['onsetType'] = $type;
        }
    }

    public function toggle(int $i, string $field, string $value): void
    {
        $allowed = match ($field) {
            'characters' => Questionnaires::PAIN_CHARACTERS,
            'aggravators' => Questionnaires::AGGRAVATORS,
            default => null,
        };

        if ($allowed === null || ! isset($this->complaints[$i]) || ! in_array($value, $allowed, true)) {
            return;
        }

        $list = $this->complaints[$i][$field];

        $this->complaints[$i][$field] = in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }

    public function save(): void
    {
        // public properties can be tampered with from the browser: re-check everything against whitelists
        abort_unless(count($this->complaints) === count($this->regions), 422);

        $rules = [
            'regions' => ['required', 'array', 'min:1'],
            'regions.*' => [Rule::in(BodyRegions::IDS)],
        ];

        foreach ($this->complaints as $i => $c) {
            $rules["complaints.$i.region"] = ['required', Rule::in($this->regions)];
            $rules["complaints.$i.characters.*"] = [Rule::in(Questionnaires::PAIN_CHARACTERS)];
            $rules["complaints.$i.aggravators.*"] = [Rule::in(Questionnaires::AGGRAVATORS)];
            $rules["complaints.$i.nrs"] = ['required', 'integer', 'between:0,10'];
            $rules["complaints.$i.onsetType"] = ['required', 'in:sudden,gradual'];
            $rules["complaints.$i.characters"] = ['required', 'array', 'min:1'];
            $rules["complaints.$i.onset"] = ($c['onsetUnknown'] ?? false)
                ? ['nullable', 'string', 'max:100']
                : ['required', 'string', 'max:100'];
        }

        $this->validate($rules, [
            'complaints.*.nrs.required' => 'Pilih tingkat nyeri 0–10.',
            'complaints.*.onsetType.required' => 'Pilih apakah muncul tiba-tiba atau bertahap.',
            'complaints.*.onsetType.in' => 'Pilih apakah muncul tiba-tiba atau bertahap.',
            'complaints.*.characters.required' => 'Pilih minimal satu sifat nyeri.',
            'complaints.*.characters.min' => 'Pilih minimal satu sifat nyeri.',
            'complaints.*.onset.required' => 'Isi sejak kapan, atau centang "Tidak tahu".',
        ]);

        // stored keyed by region, like the legacy app
        $keyed = collect($this->complaints)
            ->mapWithKeys(fn (array $c) => [$c['region'] => Arr::except($c, 'region')])
            ->all();

        $attributes = [
            'regions' => $this->regions,
            'complaints' => $keyed,
            'max_pain' => Assessment::maxPain($keyed),
        ];

        $patient = auth()->user()->patient;
        $screening = $patient->screenings()->whereNull('completed_at')->latest()->first();

        $screening ? $screening->update($attributes) : $patient->screenings()->create($attributes);

        $this->redirectRoute('patient.red-flags', navigate: true);
    }
}; ?>

@php
    $stages = ['Keluhan', 'Red flag', 'Penilaian'];

    $characterLabels = [
        'Sharp' => 'Tajam', 'Dull' => 'Tumpul', 'Burning' => 'Panas / terbakar', 'Throbbing' => 'Berdenyut',
        'Stiffness' => 'Kaku', 'Numbness' => 'Mati rasa', 'Tingling' => 'Kesemutan',
    ];
    $aggravatorLabels = [
        'Sitting' => 'Duduk', 'Standing' => 'Berdiri', 'Walking' => 'Berjalan',
        'Lifting' => 'Mengangkat', 'Sleeping' => 'Tidur', 'Exercise' => 'Olahraga',
    ];
    $nrsLabels = [
        'No Pain' => 'Tidak nyeri', 'Mild Pain' => 'Nyeri ringan', 'Moderate Pain' => 'Nyeri sedang',
        'Severe Pain' => 'Nyeri berat', 'Worst Imaginable' => 'Nyeri terburuk',
    ];
@endphp

<div class="space-y-6">
    <x-page-header title="Screening awal" subtitle="Ceritakan keluhan Anda agar program latihan sesuai dan aman." />

    {{-- Stage stepper: 1 Keluhan > 2 Red flag > 3 Penilaian --}}
    <ol class="flex items-center gap-2" aria-label="Tahap screening">
        @foreach ($stages as $idx => $label)
            <li class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
                <span @class([
                    'grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold',
                    'bg-primary text-primary-foreground ring-4 ring-primary/20' => $idx === 0,
                    'bg-muted text-muted-foreground' => $idx > 0,
                ])>{{ $idx + 1 }}</span>
                <span @class(['text-sm', 'font-medium' => $idx === 0, 'text-muted-foreground' => $idx > 0])>{{ $label }}</span>
                @unless ($loop->last)
                    <span class="h-0.5 flex-1 rounded-full bg-border"></span>
                @endunless
            </li>
        @endforeach
    </ol>

    <x-panel class="sm:p-8">
        <form wire:submit="{{ $step === 1 ? 'next' : 'save' }}" novalidate>

            {{-- STEP 1: body map --}}
            @if ($step === 1)
                <div wire:key="screening-step-1" class="space-y-5 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                            <x-lucide-person-standing class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-bold">Di mana keluhan Anda?</h2>
                            <p class="text-sm text-muted-foreground">Ketuk satu atau beberapa area tubuh. Ketuk lagi untuk membatalkan.</p>
                        </div>
                    </div>

                    <x-body-map wire:model="regions" />

                    @error('regions') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- STEP 2: one card per selected region --}}
            @if ($step === 2)
                <div wire:key="screening-step-2" class="space-y-5 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                            <x-lucide-clipboard-list class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-bold">Ceritakan tiap keluhan</h2>
                            <p class="text-sm text-muted-foreground">{{ count($complaints) }} area dipilih. Skala nyeri 0 (tidak nyeri) sampai 10 (terburuk).</p>
                        </div>
                    </div>

                    @foreach ($complaints as $i => $c)
                        <div wire:key="complaint-{{ \Illuminate\Support\Str::slug($c['region']) }}" class="space-y-5 rounded-xl border border-border p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-semibold">{{ $c['region'] }}</h3>
                                @if ($c['nrs'] !== null)
                                    @php $nrs = \App\Support\Onboarding\Assessment::nrsLabel($c['nrs']); @endphp
                                    <x-ui.badge variant="secondary">{{ $c['nrs'] }}/10 · {{ $nrsLabels[$nrs] ?? $nrs }}</x-ui.badge>
                                @endif
                            </div>

                            {{-- NRS 0-10 --}}
                            <div>
                                <x-ui.label>Tingkat nyeri</x-ui.label>
                                <div class="mt-2 grid grid-cols-11 gap-1">
                                    @foreach (range(0, 10) as $n)
                                        <button type="button" wire:click="setNrs({{ $i }}, {{ $n }})" aria-label="Nyeri {{ $n }}"
                                            @class([
                                                'h-10 rounded-lg border text-sm font-medium transition-colors',
                                                'border-primary bg-primary text-primary-foreground' => $c['nrs'] === $n,
                                                'border-border bg-card hover:bg-secondary' => $c['nrs'] !== $n,
                                            ])>{{ $n }}</button>
                                    @endforeach
                                </div>
                                @error("complaints.$i.nrs") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>

                            {{-- Onset --}}
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-ui.input label="Sejak kapan?" placeholder="mis. 2 minggu lalu" maxlength="100"
                                                wire:model.blur="complaints.{{ $i }}.onset" :disabled="$c['onsetUnknown']" />
                                    <label class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                                        <x-ui.checkbox wire:model.live="complaints.{{ $i }}.onsetUnknown" />
                                        Tidak tahu
                                    </label>
                                </div>

                                <div>
                                    <x-ui.label>Muncul secara</x-ui.label>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-onboarding.chip :active="$c['onsetType'] === 'sudden'" wire:click="setOnsetType({{ $i }}, 'sudden')">Tiba-tiba</x-onboarding.chip>
                                        <x-onboarding.chip :active="$c['onsetType'] === 'gradual'" wire:click="setOnsetType({{ $i }}, 'gradual')">Bertahap</x-onboarding.chip>
                                    </div>
                                    @error("complaints.$i.onsetType") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            {{-- Character --}}
                            <div>
                                <x-ui.label>Sifat nyeri (boleh lebih dari satu)</x-ui.label>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach (\App\Support\Onboarding\Questionnaires::PAIN_CHARACTERS as $v)
                                        <x-onboarding.chip :active="in_array($v, $c['characters'], true)" wire:click="toggle({{ $i }}, 'characters', '{{ $v }}')">{{ $characterLabels[$v] ?? $v }}</x-onboarding.chip>
                                    @endforeach
                                </div>
                                @error("complaints.$i.characters") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>

                            {{-- Aggravators (optional) --}}
                            <div>
                                <x-ui.label>Memburuk saat (opsional)</x-ui.label>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach (\App\Support\Onboarding\Questionnaires::AGGRAVATORS as $v)
                                        <x-onboarding.chip :active="in_array($v, $c['aggravators'], true)" wire:click="toggle({{ $i }}, 'aggravators', '{{ $v }}')">{{ $aggravatorLabels[$v] ?? $v }}</x-onboarding.chip>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Navigation --}}
            <div class="mt-8 flex items-center justify-between gap-3 border-t border-border pt-6">
                @if ($step === 2)
                    <x-onboarding.ghost-button wire:click="back">
                        <x-lucide-arrow-left class="size-4" /> Ubah area
                    </x-onboarding.ghost-button>
                @else
                    <span></span>
                @endif

                @if ($step === 1)
                    <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="next">
                        Lanjut <x-lucide-arrow-right class="size-4" />
                    </x-onboarding.primary-button>
                @else
                    <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan dan lanjut ke Red Flag</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-onboarding.primary-button>
                @endif
            </div>
        </form>
    </x-panel>
</div>
