<?php

use App\Models\RedFlagCheck;
use App\Models\Screening;
use App\Support\Onboarding\BodyRegions;
use App\Support\Onboarding\RedFlags;
use App\Support\Onboarding\ScreeningRules;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.onboarding')] class extends Component {
    /** 1 complaint, 2 body map, 3 characteristics, 4 red flags, 5 special groups. Locked: the browser cannot jump steps. */
    #[Locked]
    public int $step = 1;

    public array $complaints = [];
    public string $complaintOther = '';
    public array $regions = [];
    public array $painLevels = [];      // region id (or 'overall') => 0-10

    public array $char = [
        'onset' => '', 'onsetUnknown' => false, 'onsetType' => '',
        'aggravators' => [], 'relievers' => [], 'characters' => [],
        'dailyImpact' => null, 'radiating' => null, 'sleepDisturbance' => null,
        'stiffness' => null, 'tingling' => null, 'notes' => '',
    ];

    public array $rf = [];              // red-flag key => 'yes' | 'no'
    public string $rfOther = '';

    public function mount(): void
    {
        $screening = $this->screening();

        if (! $screening) {
            return;
        }

        $this->step = max(1, min(5, $screening->current_step));
        $main = $screening->main_complaints ?? [];
        $this->complaints = $main['keys'] ?? [];
        $this->complaintOther = $main['other'] ?? '';
        $this->regions = $screening->regions ?? [];
        $this->painLevels = $screening->pain_levels ?? [];
        $this->char = array_replace($this->char, $screening->characteristics ?? []);

        if ($check = $screening->redFlagCheck) {
            $this->rf = $check->answers ?? [];
            $this->rfOther = $check->other_text ?? '';
        }
    }

    private function screening(): ?Screening
    {
        return auth()->user()->patient->screenings()->where('status', 'in_progress')->latest()->first();
    }

    #[Computed]
    public function groups(): array
    {
        $patient = auth()->user()->patient;

        return ScreeningRules::specialGroups(
            $patient->age,
            $patient->special_conditions?->map->value->all() ?? [],
            $this->complaints,
            $patient->gender?->value,
        );
    }

    /** Where a pain level is asked: each selected area, or one overall level when no area was chosen. */
    private function painTargets(): array
    {
        if (! in_array('pain', $this->complaints, true)) {
            return [];
        }

        return $this->regions ?: ['overall'];
    }

    // ---------- chip / button actions (all inputs are whitelisted) ----------

    public function toggleComplaint(string $key): void
    {
        if (! array_key_exists($key, ScreeningRules::COMPLAINTS)) {
            return;
        }

        $this->complaints = in_array($key, $this->complaints, true)
            ? array_values(array_diff($this->complaints, [$key]))
            : [...$this->complaints, $key];
    }

    public function setPain(string $key, int $value): void
    {
        if (in_array($key, $this->painTargets(), true) && $value >= 0 && $value <= 10) {
            $this->painLevels[$key] = $value;
        }
    }

    public function toggleChar(string $field, string $value): void
    {
        $allowed = match ($field) {
            'aggravators' => array_keys(ScreeningRules::AGGRAVATORS),
            'relievers' => array_keys(ScreeningRules::RELIEVERS),
            'characters' => array_keys(ScreeningRules::PAIN_CHARACTERS),
            default => [],
        };

        if (! in_array($value, $allowed, true)) {
            return;
        }

        $list = $this->char[$field] ?? [];
        $this->char[$field] = in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }

    public function setChoice(string $field, string $value): void
    {
        $allowed = [
            'onsetType' => ['sudden', 'gradual'],
            'dailyImpact' => ['yes', 'no'],
            'radiating' => ['yes', 'no'],
            'sleepDisturbance' => ['yes', 'no'],
            'stiffness' => ['yes', 'no'],
            'tingling' => ['yes', 'no'],
        ];

        if (in_array($value, $allowed[$field] ?? [], true)) {
            $this->char[$field] = $value;
        }
    }

    public function setRf(string $key, string $value): void
    {
        if (array_key_exists($key, RedFlags::ITEMS) && in_array($value, ['yes', 'no'], true)) {
            $this->rf[$key] = $value;
        }
    }

    // ---------- navigation ----------

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function next(): void
    {
        match ($this->step) {
            1 => $this->completeComplaints(),
            2 => $this->completeLocation(),
            3 => $this->completeCharacteristics(),
            4 => $this->completeRedFlags(),
            5 => $this->finish(),
        };
    }

    private function completeComplaints(): void
    {
        $this->validate([
            'complaints' => ['required', 'array', 'min:1'],
            'complaints.*' => [Rule::in(array_keys(ScreeningRules::COMPLAINTS))],
            'complaintOther' => [in_array('other', $this->complaints, true) ? 'required' : 'nullable', 'string', 'max:150'],
        ], $this->messages());

        $this->persist(2);
        $this->step = 2;
    }

    private function completeLocation(): void
    {
        $this->regions = array_values(array_unique($this->regions));
        $required = ScreeningRules::locationRequired($this->complaints);

        $this->validate([
            'regions' => $required ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'regions.*' => [Rule::in(BodyRegions::IDS)],
        ], $this->messages());

        $this->painLevels = array_intersect_key($this->painLevels, array_flip($this->painTargets()));

        $this->persist(3);
        $this->step = 3;
    }

    private function completeCharacteristics(): void
    {
        $q = ScreeningRules::questions($this->complaints);
        $yesNo = fn (bool $needed): array => [$needed ? 'required' : 'nullable', 'in:yes,no'];

        $rules = [
            'char.onset' => ($this->char['onsetUnknown'] ?? false) ? ['nullable', 'string', 'max:100'] : ['required', 'string', 'max:100'],
            'char.onsetType' => ['required', 'in:sudden,gradual'],
            'char.aggravators.*' => [Rule::in(array_keys(ScreeningRules::AGGRAVATORS))],
            'char.relievers.*' => [Rule::in(array_keys(ScreeningRules::RELIEVERS))],
            'char.characters.*' => [Rule::in(array_keys(ScreeningRules::PAIN_CHARACTERS))],
            'char.dailyImpact' => $yesNo(true),
            'char.radiating' => $yesNo($q['radiating']),
            'char.sleepDisturbance' => $yesNo($q['sleepDisturbance']),
            'char.stiffness' => $yesNo($q['stiffness']),
            'char.tingling' => $yesNo($q['tingling']),
            'char.notes' => ['nullable', 'string', 'max:500'],
        ];

        foreach ($this->painTargets() as $target) {
            $rules["painLevels.$target"] = ['required', 'integer', 'between:0,10'];
        }

        $this->validate($rules, $this->messages());

        $screening = $this->persist(4);

        // A very high pain score is a red flag on its own: stop here, no need to ask the other questions.
        $result = RedFlags::evaluate([], $this->maxPain());
        if ($result['stop']) {
            $this->recordRedFlagCheck($screening, $result);

            return;
        }

        $this->step = 4;
    }

    private function completeRedFlags(): void
    {
        $rules = [];
        foreach (RedFlags::keys() as $key) {
            $rules["rf.$key"] = ['required', 'in:yes,no'];
        }
        $rules['rfOther'] = ($this->rf['other'] ?? null) === 'yes'
            ? ['required', 'string', 'min:3', 'max:200']
            : ['nullable', 'string', 'max:200'];

        $this->validate($rules, $this->messages());

        // The decision is made HERE, on the server, from the raw answers and the pain score.
        $result = RedFlags::evaluate($this->rf, $this->maxPain());
        $screening = $this->persist(4);

        $this->recordRedFlagCheck($screening, $result);

        if ($result['stop']) {
            return;
        }

        $screening->update(['current_step' => 5]);
        $this->step = 5;
    }

    private function maxPain(): int
    {
        return $this->painLevels ? max(array_map('intval', $this->painLevels)) : 0;
    }

    private function recordRedFlagCheck(Screening $screening, array $result): void
    {
        RedFlagCheck::updateOrCreate(
            ['screening_id' => $screening->id],
            [
                'patient_id' => $screening->patient_id,
                'answers' => $this->rf,
                'other_text' => ($this->rf['other'] ?? null) === 'yes' ? $this->rfOther : null,
                'flagged' => $result['flagged'],
                'cleared' => ! $result['stop'],
                'stopped_at' => $result['stop'] ? now() : null,
                'unlocked_at' => null,
                'unlocked_by' => null,
                'unlock_note' => null,
            ],
        );

        if ($result['stop']) {
            $screening->update(['status' => 'stopped']);
            $this->redirectRoute('patient.red-flag.stop');
        }
    }

    private function finish(): void
    {
        $this->persist(5, [
            'special_groups' => array_map(fn (string $g) => ['key' => $g, 'status' => 'pending'], $this->groups),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->redirectRoute('patient.domain', navigate: true);
    }

    private function persist(int $step, array $extra = []): Screening
    {
        $attributes = [
            'main_complaints' => [
                'keys' => array_values($this->complaints),
                'other' => in_array('other', $this->complaints, true) ? $this->complaintOther : null,
            ],
            'regions' => array_values($this->regions),
            'non_specific_location' => $this->regions === [],
            'pain_levels' => $this->painLevels,
            'max_pain' => $this->maxPain(),
            'characteristics' => $this->char,
            'current_step' => $step,
        ] + $extra;

        $screening = $this->screening();

        if ($screening) {
            $screening->update($attributes);

            return $screening;
        }

        return auth()->user()->patient->screenings()->create($attributes + ['status' => 'in_progress']);
    }

    private function messages(): array
    {
        return [
            'complaints.required' => 'Pilih minimal satu keluhan.',
            'complaints.min' => 'Pilih minimal satu keluhan.',
            'complaintOther.required' => 'Tuliskan keluhan lainnya.',
            'regions.required' => 'Pilih minimal satu area tubuh.',
            'regions.min' => 'Pilih minimal satu area tubuh.',
            'char.onset.required' => 'Isi sejak kapan, atau centang "Tidak tahu".',
            'char.onsetType.required' => 'Pilih tiba-tiba atau bertahap.',
            'char.*.required' => 'Pertanyaan ini wajib dijawab.',
            'painLevels.*.required' => 'Pilih tingkat nyeri 0–10.',
            'rf.*.required' => 'Jawab semua pertanyaan.',
            'rfOther.required' => 'Jelaskan tanda bahaya lainnya.',
        ];
    }
}; ?>

@php
    $stages = ['Keluhan', 'Lokasi', 'Karakteristik', 'Red flag', 'Kelompok'];
    $q = \App\Support\Onboarding\ScreeningRules::questions($complaints);
    $locationRequired = \App\Support\Onboarding\ScreeningRules::locationRequired($complaints);
    $painTargets = in_array('pain', $complaints, true) ? ($regions ?: ['overall']) : [];

    $yesNoQuestions = [
        'dailyImpact' => ['Apakah keluhan mengganggu aktivitas sehari-hari?', true],
        'radiating' => ['Apakah nyeri menjalar ke bagian tubuh lain?', $q['radiating']],
        'sleepDisturbance' => ['Apakah keluhan mengganggu tidur?', $q['sleepDisturbance']],
        'stiffness' => ['Apakah Anda merasa kaku?', $q['stiffness']],
        'tingling' => ['Apakah ada kesemutan atau baal?', $q['tingling']],
    ];

    $nrsWords = fn (int $v) => match (true) {
        $v === 0 => 'Tidak nyeri', $v <= 3 => 'Nyeri ringan', $v <= 6 => 'Nyeri sedang', $v <= 8 => 'Nyeri berat', default => 'Nyeri terburuk',
    };
@endphp

<div class="space-y-6">
    <x-page-header title="Screening awal" subtitle="Ceritakan keluhan Anda agar program latihan sesuai dan aman." />

    <ol class="flex items-center gap-2" aria-label="Tahap screening">
        @foreach ($stages as $idx => $label)
            @php $n = $idx + 1; @endphp
            <li class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
                <span @class([
                    'grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold transition-colors',
                    'bg-primary text-primary-foreground' => $n < $step,
                    'bg-primary text-primary-foreground ring-4 ring-primary/20' => $n === $step,
                    'bg-muted text-muted-foreground' => $n > $step,
                ]) @if ($n === $step) aria-current="step" @endif>
                    @if ($n < $step) <x-lucide-check class="size-4" /> @else {{ $n }} @endif
                </span>
                <span @class(['hidden text-sm sm:block', 'font-medium' => $n === $step, 'text-muted-foreground' => $n !== $step])>{{ $label }}</span>
                @unless ($loop->last)
                    <span @class(['h-0.5 flex-1 rounded-full transition-colors', 'bg-primary' => $n < $step, 'bg-border' => $n >= $step])></span>
                @endunless
            </li>
        @endforeach
    </ol>

    <x-panel class="sm:p-8">
        <form wire:submit="next" novalidate>

            {{-- STEP 1: main complaints --}}
            @if ($step === 1)
                <div wire:key="s1" class="space-y-5 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div>
                        <h2 class="text-lg font-bold">Apa keluhan utama Anda?</h2>
                        <p class="text-sm text-muted-foreground">Pilih satu atau lebih yang Anda rasakan.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Support\Onboarding\ScreeningRules::COMPLAINTS as $key => $label)
                            <x-onboarding.chip :active="in_array($key, $complaints, true)" wire:click="toggleComplaint('{{ $key }}')">{{ $label }}</x-onboarding.chip>
                        @endforeach
                    </div>
                    @error('complaints') <p class="text-xs text-destructive">{{ $message }}</p> @enderror

                    @if (in_array('other', $complaints, true))
                        <x-onboarding.field label="Keluhan lainnya">
                            <x-onboarding.input type="text" placeholder="Tuliskan keluhan Anda" maxlength="150" wire:model.blur="complaintOther" />
                        </x-onboarding.field>
                    @endif
                </div>
            @endif

            {{-- STEP 2: body map --}}
            @if ($step === 2)
                <div wire:key="s2" class="space-y-5 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div>
                        <h2 class="text-lg font-bold">Di mana lokasi keluhan?</h2>
                        <p class="text-sm text-muted-foreground">
                            @if ($locationRequired)
                                Ketuk satu atau beberapa area tubuh yang terasa bermasalah.
                            @else
                                Keluhan Anda tidak terkait satu lokasi tertentu. Anda boleh melewati langkah ini, atau tetap memilih area jika ada.
                            @endif
                        </p>
                    </div>

                    <x-body-map wire:model="regions" />
                    @error('regions') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- STEP 3: characteristics (questions depend on the chosen complaints) --}}
            @if ($step === 3)
                <div wire:key="s3" class="space-y-7 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div>
                        <h2 class="text-lg font-bold">Karakteristik keluhan</h2>
                        <p class="text-sm text-muted-foreground">Beberapa pertanyaan menyesuaikan dengan keluhan yang Anda pilih.</p>
                    </div>

                    @foreach ($painTargets as $t)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <x-ui.label>Tingkat nyeri: {{ $t === 'overall' ? 'keseluruhan' : \App\Support\Onboarding\BodyRegions::label($t) }}</x-ui.label>
                                @if (isset($painLevels[$t]))
                                    <x-ui.badge variant="secondary">{{ $painLevels[$t] }}/10 · {{ $nrsWords((int) $painLevels[$t]) }}</x-ui.badge>
                                @endif
                            </div>
                            <div class="grid grid-cols-11 gap-1">
                                @foreach (range(0, 10) as $v)
                                    <button type="button" wire:click="setPain('{{ $t }}', {{ $v }})" aria-label="Nyeri {{ $v }}"
                                        @class([
                                            'h-10 rounded-lg border text-sm font-medium transition-colors',
                                            'border-primary bg-primary text-primary-foreground' => ($painLevels[$t] ?? null) === $v,
                                            'border-border bg-card hover:bg-secondary' => ($painLevels[$t] ?? null) !== $v,
                                        ])>{{ $v }}</button>
                                @endforeach
                            </div>
                            <p class="flex justify-between text-[11px] text-muted-foreground"><span>Tidak nyeri</span><span>Terburuk</span></p>
                            @if (($painLevels[$t] ?? -1) >= \App\Support\Onboarding\RedFlags::PAIN_STOP_THRESHOLD)
                                <p class="flex items-start gap-2 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive">
                                    <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0" />
                                    <span>Nyeri {{ \App\Support\Onboarding\RedFlags::PAIN_STOP_THRESHOLD }}–10 dianggap tanda bahaya. Jika Anda lanjut, program latihan dihentikan dan Anda diarahkan ke fasilitas kesehatan. Ubah skala jika salah memilih.</span>
                                </p>
                            @endif
                            @error("painLevels.$t") <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-ui.input label="Kapan mulai terjadi?" placeholder="mis. 2 minggu lalu" maxlength="100"
                                        wire:model.blur="char.onset" :disabled="$char['onsetUnknown']" />
                            <label class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                                <x-ui.checkbox wire:model.live="char.onsetUnknown" /> Tidak tahu
                            </label>
                        </div>
                        <div>
                            <x-ui.label>Terjadi secara</x-ui.label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-onboarding.chip :active="$char['onsetType'] === 'sudden'" wire:click="setChoice('onsetType', 'sudden')">Tiba-tiba</x-onboarding.chip>
                                <x-onboarding.chip :active="$char['onsetType'] === 'gradual'" wire:click="setChoice('onsetType', 'gradual')">Bertahap</x-onboarding.chip>
                            </div>
                            @error('char.onsetType') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <x-ui.label>Apa yang memperberat? (opsional)</x-ui.label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (\App\Support\Onboarding\ScreeningRules::AGGRAVATORS as $k => $label)
                                <x-onboarding.chip :active="in_array($k, $char['aggravators'], true)" wire:click="toggleChar('aggravators', '{{ $k }}')">{{ $label }}</x-onboarding.chip>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <x-ui.label>Apa yang mengurangi? (opsional)</x-ui.label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (\App\Support\Onboarding\ScreeningRules::RELIEVERS as $k => $label)
                                <x-onboarding.chip :active="in_array($k, $char['relievers'], true)" wire:click="toggleChar('relievers', '{{ $k }}')">{{ $label }}</x-onboarding.chip>
                            @endforeach
                        </div>
                    </div>

                    @if ($q['painCharacter'])
                        <div>
                            <x-ui.label>Sifat nyeri (opsional)</x-ui.label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (\App\Support\Onboarding\ScreeningRules::PAIN_CHARACTERS as $k => $label)
                                    <x-onboarding.chip :active="in_array($k, $char['characters'], true)" wire:click="toggleChar('characters', '{{ $k }}')">{{ $label }}</x-onboarding.chip>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach ($yesNoQuestions as $field => [$text, $shown])
                        @if ($shown)
                            <div>
                                <p class="text-sm font-medium">{{ $text }}</p>
                                <div class="mt-2 grid max-w-xs grid-cols-2 gap-3">
                                    @foreach (['yes' => 'Ya', 'no' => 'Tidak'] as $val => $label)
                                        <button type="button" wire:click="setChoice('{{ $field }}', '{{ $val }}')"
                                            @class([
                                                'rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors',
                                                'border-primary bg-primary/10 text-primary' => $char[$field] === $val,
                                                'border-border bg-card hover:bg-secondary' => $char[$field] !== $val,
                                            ])>{{ $label }}</button>
                                    @endforeach
                                </div>
                                @error("char.$field") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    @endforeach

                    <x-ui.textarea label="Catatan tambahan (opsional)" rows="3" maxlength="500" wire:model.blur="char.notes" />
                </div>
            @endif

            {{-- STEP 4: red flags (any "Ya" = hard stop) --}}
            @if ($step === 4)
                <div wire:key="s4" class="space-y-5 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-4">
                        <x-lucide-shield-alert class="mt-0.5 size-5 shrink-0 text-destructive" />
                        <div>
                            <h2 class="font-bold">Pemeriksaan tanda bahaya</h2>
                            <p class="text-sm text-muted-foreground">Jawab dengan jujur. Jika ada jawaban "Ya", program latihan dihentikan demi keselamatan Anda dan Anda akan diarahkan ke fasilitas kesehatan.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach (\App\Support\Onboarding\RedFlags::ITEMS as $key => $text)
                            <div class="rounded-xl border border-border p-4">
                                <p class="text-sm font-medium">{{ $text }}</p>
                                <div class="mt-3 grid max-w-xs grid-cols-2 gap-3">
                                    @foreach (['yes' => 'Ya', 'no' => 'Tidak'] as $val => $label)
                                        <button type="button" wire:click="setRf('{{ $key }}', '{{ $val }}')"
                                            @class([
                                                'rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors',
                                                'border-destructive bg-destructive/10 text-destructive' => ($rf[$key] ?? null) === 'yes' && $val === 'yes',
                                                'border-primary bg-primary/10 text-primary' => ($rf[$key] ?? null) === 'no' && $val === 'no',
                                                'border-border bg-card hover:bg-secondary' => ($rf[$key] ?? null) !== $val,
                                            ])>{{ $label }}</button>
                                    @endforeach
                                </div>
                                @if ($key === 'other' && ($rf['other'] ?? null) === 'yes')
                                    <div class="mt-3">
                                        <x-onboarding.input type="text" placeholder="Jelaskan tanda bahaya tersebut" maxlength="200" wire:model.blur="rfOther" />
                                    </div>
                                @endif
                                @error("rf.$key") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- STEP 5: special groups --}}
            @if ($step === 5)
                <div wire:key="s5" class="space-y-5 animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-success/20 text-foreground"><x-lucide-circle-check class="size-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold">Tidak ada tanda bahaya</h2>
                            <p class="text-sm text-muted-foreground">Berdasarkan profil dan jawaban Anda, berikut skrining lanjutan yang akan dijalani sebelum penentuan domain fisioterapi.</p>
                        </div>
                    </div>

                    <ul class="space-y-3">
                        @foreach ($this->groups as $g)
                            <li class="flex items-center gap-3 rounded-xl border border-border p-4">
                                <x-lucide-clipboard-check class="size-5 shrink-0 text-primary" />
                                <span class="text-sm font-medium">{{ \App\Support\Onboarding\ScreeningRules::GROUPS[$g] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Navigation --}}
            <div class="mt-8 flex items-center justify-between gap-3 border-t border-border pt-6">
                @if ($step > 1)
                    <x-onboarding.ghost-button wire:click="back"><x-lucide-arrow-left class="size-4" /> Kembali</x-onboarding.ghost-button>
                @else
                    <span></span>
                @endif

                <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="next">
                    <span wire:loading.remove wire:target="next">
                        @if ($step === 4) Periksa tanda bahaya @elseif ($step === 5) Lanjut ke Identifikasi Domain Fisioterapi @else Lanjut @endif
                    </span>
                    <span wire:loading wire:target="next">Menyimpan…</span>
                </x-onboarding.primary-button>
            </div>
        </form>
    </x-panel>
</div>
