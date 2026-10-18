<?php

use App\Enums\Goal;
use App\Enums\SpecialCondition;
use App\Livewire\Forms\ProfileForm;
use App\Support\Onboarding\Assessment;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.onboarding')] class extends Component {
    public const LAST_STEP = 6;   // 1-5 = input steps, 6 = review

    public ProfileForm $form;
    public int $step = 1;

    public function mount(): void
    {
        $patient = auth()->user()->patient;

        if ($patient?->isProfileComplete()) {
            $this->form->fill([
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'gender' => $patient->gender?->value,
                'height_cm' => $patient->height_cm,
                'weight_kg' => $patient->weight_kg,
                'occupation' => $patient->occupation,
                'activity_hours_per_day' => $patient->activity_hours_per_day,
                'has_special_condition' => $patient->has_special_condition,
                'special_conditions' => $patient->special_conditions?->map->value->all() ?? [],
                'special_condition_other' => $patient->special_condition_other,
                'goals' => $patient->goals?->map->value->all() ?? [],
                'goal_other' => $patient->goal_other,
            ]);
        }
    }

    #[Computed]
    public function bmi(): ?array
    {
        return Assessment::bmi($this->form->height_cm, $this->form->weight_kg);
    }

    public function setHasCondition(bool $value): void
    {
        $this->form->has_special_condition = $value;

        if (! $value) {
            $this->form->special_conditions = [];
            $this->form->special_condition_other = null;
        }
    }

    public function toggleCondition(string $value): void
    {
        if (SpecialCondition::tryFrom($value) === null) {
            return;
        }

        if ($value === 'pregnancy' && $this->form->gender !== 'female') {
            return;
        }

        $list = $this->form->special_conditions;
        $this->form->special_conditions = in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : [...$list, $value];
    }

    public function updatedFormGender(): void
    {
        if ($this->form->gender !== 'female') {
            $this->form->special_conditions = array_values(array_diff($this->form->special_conditions, ['pregnancy']));
        }
    }

    public function toggleGoal(string $value): void
    {
        if (Goal::tryFrom($value) === null) {
            return;
        }

        $list = $this->form->goals;

        if (in_array($value, $list, true)) {
            $this->form->goals = array_values(array_diff($list, [$value]));
        } elseif (count($list) < 3) {                    // PRD: max 3 goals
            $this->form->goals = [...$list, $value];
        }
    }

    public function goTo(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    public function next(): void
    {
        $this->validate($this->stepRules($this->step), $this->messages());
        $this->step = min(self::LAST_STEP, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function save(): void
    {
        // PRD step 7: re-validate everything; jump back to the first step with a problem
        for ($s = 1; $s < self::LAST_STEP; $s++) {
            try {
                $this->validate($this->stepRules($s), $this->messages());
            } catch (ValidationException $e) {
                $this->step = $s;
                throw $e;
            }
        }

        $f = $this->form;
        $hasCondition = $f->has_special_condition;

        auth()->user()->patient->update([
            'date_of_birth' => $f->date_of_birth,
            'gender' => $f->gender,
            'height_cm' => $f->height_cm,
            'weight_kg' => $f->weight_kg,
            'bmi' => $this->bmi['value'] ?? null,
            'occupation' => $f->occupation,
            'activity_hours_per_day' => $f->activity_hours_per_day,
            'has_special_condition' => $hasCondition,
            'special_conditions' => $hasCondition ? $f->special_conditions : [],
            'special_condition_other' => $hasCondition && in_array('other', $f->special_conditions, true)
                ? $f->special_condition_other : null,
            'goals' => $f->goals,
            'goal_other' => in_array('other', $f->goals, true) ? $f->goal_other : null,
            'profile_completed_at' => now(),
        ]);

        $this->redirectRoute('patient.screening', navigate: true);
    }

    private function stepRules(int $step): array
    {
        return collect($this->form->rulesForStep($step))
            ->mapWithKeys(fn ($rules, $field) => ["form.$field" => $rules])
            ->all();
    }

    private function messages(): array
    {
        return [
            'form.date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'form.date_of_birth.date_format' => 'Format tanggal tidak valid.',
            'form.date_of_birth.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'form.date_of_birth.after_or_equal' => 'Tanggal lahir tidak valid.',
            'form.gender.required' => 'Pilih jenis kelamin.',
            'form.gender.in' => 'Pilih jenis kelamin.',
            'form.height_cm.required' => 'Tinggi badan wajib diisi.',
            'form.height_cm.numeric' => 'Tinggi badan harus berupa angka.',
            'form.height_cm.between' => 'Tinggi badan harus antara 30 dan 250 cm.',
            'form.weight_kg.required' => 'Berat badan wajib diisi.',
            'form.weight_kg.numeric' => 'Berat badan harus berupa angka.',
            'form.weight_kg.between' => 'Berat badan harus antara 2 dan 300 kg.',
            'form.occupation.required' => 'Pilih pekerjaan atau aktivitas utama.',
            'form.activity_hours_per_day.required' => 'Durasi aktivitas wajib diisi.',
            'form.activity_hours_per_day.numeric' => 'Durasi harus berupa angka.',
            'form.activity_hours_per_day.between' => 'Durasi harus antara 0 dan 24 jam.',
            'form.special_conditions.required' => 'Pilih minimal satu kondisi khusus.',
            'form.special_condition_other.required' => 'Tuliskan kondisi khusus lainnya.',
            'form.goals.required' => 'Pilih minimal satu tujuan.',
            'form.goals.min' => 'Pilih minimal satu tujuan.',
            'form.goals.max' => 'Pilih maksimal 3 tujuan.',
            'form.goal_other.required' => 'Tuliskan tujuan lainnya.',
        ];
    }
}; ?>


@php
$stepMeta = [
1 => ['Identitas Anda', 'Data dasar untuk menentukan modul layanan yang tepat.', 'user-round'],
2 => ['Data fisik', 'Tinggi dan berat badan, BMI dihitung otomatis.', 'ruler'],
3 => ['Pekerjaan & aktivitas', 'Membantu kami memahami beban gerak harian Anda.', 'briefcase'],
4 => ['Kondisi khusus', 'Agar program latihan aman dan sesuai kondisi Anda.', 'heart-pulse'],
5 => ['Tujuan Anda', 'Pilih hingga 3 tujuan utama menggunakan TeleFisioRehab.', 'target'],
6 => ['Tinjau & simpan', 'Pastikan semua data sudah benar sebelum melanjutkan.', 'clipboard-check'],
];

$occupations = [
'Pekerja kantor', 'Pekerja lapangan / fisik', 'Tenaga kesehatan', 'Guru', 'Pengemudi',
'Pelajar / Mahasiswa', 'Ibu rumah tangga', 'Pensiunan', 'Atlet', 'Lainnya',
];

$moduleMeta = [
'pediatric' => ['emoji' => '🧒', 'focus' => 'Fokus: tumbuh kembang dan aktivitas'],
'adult' => ['emoji' => '🧑', 'focus' => 'Fokus: screening umum'],
'geriatric' => ['emoji' => '🧓', 'focus' => 'Fokus: mobilitas, keseimbangan, dan risiko jatuh'],
];

$bmiLabels = ['Underweight' => 'Berat badan kurang', 'Normal' => 'Normal', 'Overweight' => 'Berat badan berlebih',
'Obese' => 'Obesitas'];
$bmiDots = ['Underweight' => 'bg-chart-3', 'Normal' => 'bg-success', 'Overweight' => 'bg-warning', 'Obese' =>
'bg-destructive'];

[$title, $description, $icon] = $stepMeta[$step];
$m = $form->module();
@endphp

<div class="space-y-6">
    <x-page-header title="Lengkapi profil Anda"
        subtitle="Hanya sekitar 2 menit. Data ini membantu kami menyiapkan program rehabilitasi yang tepat." />

    {{-- Stepper --}}
    <div>
        <ol class="flex items-center gap-2" aria-label="Langkah profil">
            @foreach ($stepMeta as $n => $meta)
            <li class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
                <span
                    @class([ 'grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold transition-colors'
                    , 'bg-primary text-primary-foreground'=> $n <
                        $step, 'bg-primary text-primary-foreground ring-4 ring-primary/20'=> $n === $step,
                        'bg-muted text-muted-foreground' => $n > $step,
                        ]) @if ($n === $step) aria-current="step" @endif>
                        @if ($n
                        < $step) <x-lucide-check class="size-4" />
                        @else
                        {{ $n }}
                        @endif
                </span>
                @unless ($loop->last)
                <span @class(['h-0.5 flex-1 rounded-full transition-colors', 'bg-primary'=> $n < $step, 'bg-border'=> $n
                        >= $step])></span>
                @endunless
            </li>
            @endforeach
        </ol>
        <p class="mt-3 text-xs text-muted-foreground">Langkah {{ $step }} dari {{ $this::LAST_STEP }}</p>
    </div>

    <x-panel class="sm:p-8">
        <form wire:submit="{{ $step < $this::LAST_STEP ? 'next' : 'save' }}" novalidate>
            {{-- Step heading --}}
            <div class="mb-6 flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                    <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
                </span>
                <div>
                    <h2 class="text-lg font-bold">{{ $title }}</h2>
                    <p class="text-sm text-muted-foreground">{{ $description }}</p>
                </div>
            </div>

            {{-- STEP 1: identity --}}
            @if ($step === 1)
            <div wire:key="step-1" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                <x-ui.input label="Nama" :value="auth()->user()->name" disabled />

                <x-ui.input type="date" label="Tanggal lahir" :max="now()->toDateString()"
                    wire:model.live="form.date_of_birth" />

                <div>
                    <x-ui.label>Jenis kelamin</x-ui.label>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        @foreach (\App\Enums\Gender::cases() as $g)
                        <label class="cursor-pointer">
                            <input type="radio" class="peer sr-only" value="{{ $g->value }}"
                                wire:model.live="form.gender">
                            <div
                                class="flex items-center justify-center rounded-xl border border-border bg-card px-4 py-3 text-sm font-medium transition-colors hover:bg-secondary peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring">
                                {{ $g->label() }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('form.gender') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                @if ($m)
                <div class="flex items-start gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
                    <span class="text-2xl">{{ $moduleMeta[$m->value]['emoji'] }}</span>
                    <div>
                        <p class="text-sm font-semibold">{{ $m->label() }} akan diaktifkan</p>
                        <p class="text-xs text-muted-foreground">Usia {{ $form->age() }} tahun · {{
                            $moduleMeta[$m->value]['focus'] }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- STEP 2: physical data + live BMI --}}
            @if ($step === 2)
            <div wire:key="step-2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input type="number" step="0.1" inputmode="decimal" label="Tinggi badan (cm)"
                        placeholder="mis. 165" wire:model.live.debounce.400ms="form.height_cm" />
                    <x-ui.input type="number" step="0.1" inputmode="decimal" label="Berat badan (kg)"
                        placeholder="mis. 60" wire:model.live.debounce.400ms="form.weight_kg" />
                </div>

                @if ($this->bmi)
                @php
                $b = $this->bmi;
                $pos = max(0, min(100, (($b['value'] - 15) / 25) * 100));
                @endphp
                <div class="rounded-xl border border-border bg-[image:var(--gradient-soft)] p-5">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground">Indeks Massa Tubuh (BMI)</p>
                            <p class="font-display text-4xl font-bold">{{ number_format($b['value'], 1) }}</p>
                        </div>
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1 text-sm font-medium">
                            <span class="size-2 rounded-full {{ $bmiDots[$b['category']] }}"></span>
                            {{ $bmiLabels[$b['category']] }}
                        </span>
                    </div>

                    <div class="relative mt-5">
                        <div class="flex h-2 overflow-hidden rounded-full">
                            <span class="w-[14%] bg-chart-3"></span>
                            <span class="w-[26%] bg-success"></span>
                            <span class="w-[20%] bg-warning"></span>
                            <span class="w-[40%] bg-destructive"></span>
                        </div>
                        <span class="absolute -top-1 h-4 w-1 -translate-x-1/2 rounded bg-foreground"
                            style="left: {{ $pos }}%"></span>
                    </div>
                    <div class="mt-2 flex justify-between text-[11px] text-muted-foreground">
                        <span>15</span><span>18,5</span><span>25</span><span>30</span><span>40</span>
                    </div>
                </div>
                @else
                <p class="text-sm text-muted-foreground">Isi tinggi dan berat badan untuk melihat BMI Anda.</p>
                @endif
            </div>
            @endif

            {{-- STEP 3: occupation + activity --}}
            @if ($step === 3)
            <div wire:key="step-3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                <div>
                    <x-ui.label>Pekerjaan / aktivitas utama</x-ui.label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($occupations as $o)
                        <x-onboarding.chip :active="$form->occupation === $o"
                            wire:click="$set('form.occupation', '{{ $o }}')">{{ $o }}</x-onboarding.chip>
                        @endforeach
                    </div>
                    @error('form.occupation') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-ui.input type="number" step="0.5" min="0" max="24" inputmode="decimal"
                        label="Durasi aktivitas per hari (jam)" placeholder="mis. 6"
                        wire:model.live.debounce.300ms="form.activity_hours_per_day" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ([1, 2, 4, 6, 8, 10] as $h)
                        <x-onboarding.chip
                            :active="(float) $form->activity_hours_per_day === (float) $h && $form->activity_hours_per_day !== null"
                            wire:click="$set('form.activity_hours_per_day', {{ $h }})">{{ $h }} jam</x-onboarding.chip>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- STEP 4: special conditions --}}
            @if ($step === 4)
            @foreach (\App\Enums\SpecialCondition::cases() as $c)
            @continue($c->value === 'pregnancy' && $form->gender !== 'female')
            <x-onboarding.chip :active="in_array($c->value, $form->special_conditions, true)"
                wire:click="toggleCondition('{{ $c->value }}')">{{ $c->label() }}</x-onboarding.chip>
            @endforeach
            <div wire:key="step-4" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                <div>
                    <p class="text-sm font-medium">Apakah Anda memiliki kondisi khusus yang perlu diperhatikan?</p>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ([['v' => false, 'label' => 'Tidak ada'], ['v' => true, 'label' => 'Ya, ada']] as $opt)
                        <button type="button" wire:click="setHasCondition({{ $opt['v'] ? 'true' : 'false' }})"
                            @class([ 'rounded-xl border px-4 py-3 text-sm font-medium transition-colors'
                            , 'border-primary bg-primary/10 text-primary'=> $form->has_special_condition === $opt['v'],
                            'border-border bg-card hover:bg-secondary' => $form->has_special_condition !== $opt['v'],
                            ])>
                            {{ $opt['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                @if ($form->has_special_condition)
                <div class="animate-in fade-in duration-200">
                    <x-ui.label>Pilih semua yang sesuai</x-ui.label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Enums\SpecialCondition::cases() as $c)
                        <x-onboarding.chip :active="in_array($c->value, $form->special_conditions, true)"
                            wire:click="toggleCondition('{{ $c->value }}')">{{ $c->label() }}</x-onboarding.chip>
                        @endforeach
                    </div>
                    @error('form.special_conditions') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p>
                    @enderror

                    @if (in_array('other', $form->special_conditions, true))
                    <div class="mt-4">
                        <x-onboarding.field label="Kondisi lainnya">
                            <x-onboarding.input type="text" placeholder="Tuliskan kondisi Anda"
                                wire:model.blur="form.special_condition_other" />
                        </x-onboarding.field>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

            {{-- STEP 5: goals (max 3) --}}
            @if ($step === 5)
            @php $goalsFull = count($form->goals) >= 3; @endphp
            <div wire:key="step-5" class="space-y-4 animate-in fade-in slide-in-from-right-4 duration-300">
                <div class="flex items-center justify-between">
                    <x-ui.label>Apa yang ingin Anda capai?</x-ui.label>
                    <x-ui.badge variant="secondary">{{ count($form->goals) }}/3 dipilih</x-ui.badge>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Enums\Goal::cases() as $g)
                    <x-onboarding.chip :active="in_array($g->value, $form->goals, true)"
                        :disabled="$goalsFull && ! in_array($g->value, $form->goals, true)"
                        wire:click="toggleGoal('{{ $g->value }}')">{{ $g->label() }}</x-onboarding.chip>
                    @endforeach
                </div>
                @error('form.goals') <p class="text-xs text-destructive">{{ $message }}</p> @enderror

                @if (in_array('other', $form->goals, true))
                <x-onboarding.field label="Tujuan lainnya">
                    <x-onboarding.input type="text" placeholder="Tuliskan tujuan Anda"
                        wire:model.blur="form.goal_other" />
                </x-onboarding.field>
                @endif
            </div>
            @endif

            {{-- STEP 6: review --}}
            @if ($step === 6)
            @php
            $sections = [
            1 => 'Identitas',
            2 => 'Data fisik',
            3 => 'Pekerjaan & aktivitas',
            4 => 'Kondisi khusus',
            5 => 'Tujuan',
            ];
            @endphp
            <div wire:key="step-6" class="space-y-3 animate-in fade-in slide-in-from-right-4 duration-300">
                @foreach ($sections as $n => $label)
                <div class="rounded-xl border border-border p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold">{{ $label }}</p>
                        <button type="button" wire:click="goTo({{ $n }})"
                            class="text-xs font-medium text-primary hover:underline">Ubah</button>
                    </div>

                    @if ($n === 1)
                    <dl class="grid grid-cols-2 gap-y-1 text-sm">
                        <dt class="text-muted-foreground">Nama</dt>
                        <dd>{{ auth()->user()->name }}</dd>
                        <dt class="text-muted-foreground">Tanggal lahir</dt>
                        <dd>{{ \Illuminate\Support\Carbon::parse($form->date_of_birth)->format('d M Y') }} ({{
                            $form->age() }} th)</dd>
                        <dt class="text-muted-foreground">Jenis kelamin</dt>
                        <dd>{{ \App\Enums\Gender::from($form->gender)->label() }}</dd>
                        <dt class="text-muted-foreground">Modul</dt>
                        <dd>{{ $m?->label() }}</dd>
                    </dl>
                    @elseif ($n === 2)
                    <dl class="grid grid-cols-2 gap-y-1 text-sm">
                        <dt class="text-muted-foreground">Tinggi / Berat</dt>
                        <dd>{{ $form->height_cm }} cm / {{ $form->weight_kg }} kg</dd>
                        @if ($this->bmi)
                        <dt class="text-muted-foreground">BMI</dt>
                        <dd>{{ number_format($this->bmi['value'], 1) }} · {{ $bmiLabels[$this->bmi['category']] }}</dd>
                        @endif
                    </dl>
                    @elseif ($n === 3)
                    <dl class="grid grid-cols-2 gap-y-1 text-sm">
                        <dt class="text-muted-foreground">Aktivitas utama</dt>
                        <dd>{{ $form->occupation }}</dd>
                        <dt class="text-muted-foreground">Durasi per hari</dt>
                        <dd>{{ $form->activity_hours_per_day }} jam</dd>
                    </dl>
                    @elseif ($n === 4)
                    @if ($form->has_special_condition && $form->special_conditions)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($form->special_conditions as $v)
                        <x-ui.badge variant="secondary">{{ $v === 'other' && $form->special_condition_other ?
                            $form->special_condition_other : \App\Enums\SpecialCondition::from($v)->label() }}
                        </x-ui.badge>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-muted-foreground">Tidak ada</p>
                    @endif
                    @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($form->goals as $v)
                        <x-ui.badge variant="secondary">{{ $v === 'other' && $form->goal_other ? $form->goal_other :
                            \App\Enums\Goal::from($v)->label() }}</x-ui.badge>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            {{-- Navigation --}}
            <div class="mt-8 flex items-center justify-between gap-3 border-t border-border pt-6">
                @if ($step > 1)
                <x-onboarding.ghost-button wire:click="back">
                    <x-lucide-arrow-left class="size-4" /> Kembali
                </x-onboarding.ghost-button>
                @else
                <span></span>
                @endif

                @if ($step < $this::LAST_STEP) <x-onboarding.primary-button type="submit" wire:loading.attr="disabled"
                    wire:target="next">
                    Lanjut
                    <x-lucide-arrow-right class="size-4" />
                    </x-onboarding.primary-button>
                    @else
                    <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Simpan dan lanjut ke Initial Screening</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </x-onboarding.primary-button>
                    @endif
            </div>
        </form>
    </x-panel>
</div>