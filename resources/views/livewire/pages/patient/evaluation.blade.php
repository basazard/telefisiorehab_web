<?php

use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Instruments;
use App\Support\Onboarding\Monitoring;
use App\Support\Onboarding\Programs;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

// Monitoring & Reassessment PRD: general questions, then the domain instruments of the baseline assessment;
// the server compares them with the baseline and decides the next step.
new class extends Component {
    /** 1 = general questions, 2.. = one instrument per step. Locked: the browser cannot jump steps. */
    #[Locked]
    public int $step = 1;

    public ?int $pain = null;
    public string $globalChange = '';
    public ?string $newComplaint = null;
    public ?string $sideEffect = null;
    public string $sideEffectText = '';
    public array $barriers = [];
    public array $answers = [];

    /** Set after submitting; the result is shown on the same page. */
    #[Locked]
    public ?int $evaluationId = null;

    public function mount(): void
    {
        if (! $this->program?->isActive()) {
            $this->redirectRoute('patient.program', navigate: true);

            return;
        }

        foreach ($this->instruments as $key) {
            $this->answers[$key] = Instruments::blank($key);
        }
    }

    #[Computed]
    public function program(): ?Program
    {
        return auth()->user()->patient->currentProgram()?->load('assessment');
    }

    /** @return string[] the self-report instruments of the baseline assessment */
    #[Computed]
    public function instruments(): array
    {
        return $this->program?->assessment?->instruments ?? [];
    }

    #[Computed]
    public function evaluation(): ?ProgramEvaluation
    {
        return $this->evaluationId ? auth()->user()->patient->evaluations()->with('program')->find($this->evaluationId) : null;
    }

    public function total(): int
    {
        return 1 + count($this->instruments);
    }

    public function current(): ?string
    {
        return $this->step > 1 ? ($this->instruments[$this->step - 2] ?? null) : null;
    }

    public function choose(string $field, int|string $value): void
    {
        abort_unless(in_array($field, ['pain', 'globalChange', 'newComplaint', 'sideEffect'], true), 422);
        $this->{$field} = $value;
    }

    public function toggleBarrier(string $key): void
    {
        abort_unless(isset(Monitoring::BARRIERS[$key]), 422);
        $this->barriers = in_array($key, $this->barriers, true)
            ? array_values(array_diff($this->barriers, [$key]))
            : [...$this->barriers, $key];
    }

    /** Same contract as the assessment: only paths of the instrument on screen can be set. */
    public function setAnswer(string $path, int|string|null $value): void
    {
        [$key] = explode('.', $path, 2);
        abort_unless($key === $this->current() && Arr::has($this->answers, $path), 422);

        data_set($this->answers, $path, $value);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function next(): void
    {
        abort_unless($this->program?->isActive() && ! $this->evaluationId, 403);

        $this->step === 1 ? $this->validateGeneral() : $this->validateInstrument($this->current());

        if ($this->step < $this->total()) {
            $this->step++;

            return;
        }

        $this->submit();
    }

    private function submit(): void
    {
        $this->validateGeneral();
        foreach ($this->instruments as $key) {
            $this->validateInstrument($key);
        }

        $evaluation = Programs::monitor($this->program, [
            'pain' => $this->pain,
            'global_change' => $this->globalChange,
            'new_complaint' => $this->newComplaint === 'yes',
            'side_effects' => $this->sideEffect === 'yes' ? $this->sideEffectText : null,
            'barriers' => $this->barriers,
        ], $this->answers);

        if (auth()->user()->patient->isRedFlagLocked()) {
            $this->redirectRoute('patient.red-flag.stop', navigate: true);

            return;
        }

        $this->evaluationId = $evaluation->id;
    }

    public function requestConsultation(string $type): void
    {
        abort_unless(isset(\App\Models\ConsultationRequest::TYPES[$type]) && $this->evaluation, 422);

        $created = auth()->user()->patient->requestConsultation($type, $this->evaluation->program->assessment_id,
            'Dari monitoring: '.Monitoring::OUTCOMES[$this->evaluation->outcome]);

        $this->dispatch('toast', message: $created ? 'Permintaan terkirim. Tim kami akan menghubungi Anda.' : 'Permintaan Anda masih diproses.', type: $created ? 'success' : 'default');
    }

    private function validateGeneral(): void
    {
        $this->validate([
            'pain' => ['required', 'integer', 'between:0,10'],
            'globalChange' => ['required', Rule::in(array_keys(ProgramEvaluation::GLOBAL_CHANGE))],
            'newComplaint' => ['required', Rule::in(['yes', 'no'])],
            'sideEffect' => ['required', Rule::in(['yes', 'no'])],
            'sideEffectText' => [Rule::requiredIf($this->sideEffect === 'yes'), 'nullable', 'string', 'max:300'],
            'barriers' => ['array'],
            'barriers.*' => [Rule::in(array_keys(Monitoring::BARRIERS))],
        ], ['required' => 'Pertanyaan ini wajib dijawab.', 'sideEffectText.required' => 'Ceritakan efek samping yang Anda alami.']);
    }

    private function validateInstrument(?string $key): void
    {
        if ($key) {
            $this->validate(Instruments::rules($key, $this->answers[$key]), [
                'required' => 'Pertanyaan ini wajib dijawab.',
                'required_with' => 'Isi kedua ukuran lingkar, atau kosongkan keduanya.',
                'integer' => 'Isi dengan bilangan bulat.',
                'numeric' => 'Isi dengan angka.',
                'between' => 'Nilai harus antara :min dan :max.',
                'max' => 'Maksimal :max karakter.',
                'in' => 'Pilih salah satu jawaban.',
            ]);
        }
    }
}; ?>

@php
    $key = $this->current();
    $total = $this->total();
    $pick = fn (bool $on) => $on ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:bg-secondary';
    $btn = 'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-medium';
@endphp

<div class="space-y-6">
    <x-page-header title="Monitoring & Reassessment" subtitle="Perkembangan Anda dibandingkan dengan hasil assessment awal." />

    @if ($e = $this->evaluation)
        @php $outcome = $e->outcome; @endphp

        <x-panel title="Perbandingan dengan data awal" sub="Perubahan bermakna = melewati selisih minimal yang berarti secara klinis (MCID).">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-muted-foreground">
                        <tr><th class="py-2 pr-3 font-medium">Parameter</th><th class="py-2 pr-3 font-medium">Awal</th><th class="py-2 pr-3 font-medium">Sekarang</th><th class="py-2 font-medium">Perubahan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($e->comparison ?? [] as $item)
                            <tr>
                                <td class="py-2.5 pr-3">{{ $item['label'] }}</td>
                                <td class="py-2.5 pr-3 text-muted-foreground">{{ $item['before'] }}</td>
                                <td class="py-2.5 pr-3 font-medium">{{ $item['after'] }}</td>
                                <td class="py-2.5">
                                    @php
                                        [$text, $variant] = match (true) {
                                            $item['direction'] === 'improved' && $item['significant'] => ['Membaik bermakna', 'secondary'],
                                            $item['direction'] === 'improved' => ['Sedikit membaik', 'outline'],
                                            $item['direction'] === 'worse' && $item['significant'] => ['Memburuk', 'destructive'],
                                            $item['direction'] === 'worse' => ['Sedikit memburuk', 'outline'],
                                            default => ['Tetap', 'outline'],
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$variant">{{ $text }}@if ($item['change'] !== null) ({{ $item['change'] > 0 ? '+' : '' }}{{ $item['change'] }})@endif</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">Kepatuhan latihan sejak monitoring terakhir: {{ $e->adherence }}% hari · Kategori risiko saat ini: {{ $e->tier?->label() }}.
                ROM dan kekuatan otot (MMT) dinilai fisioterapis saat telekonsultasi.</p>
        </x-panel>

        <x-panel title="Apakah ada perbaikan signifikan?">
            <p class="font-display text-lg font-bold">{{ $e->improved ? 'Ya' : 'Tidak' }} · {{ $e->outcomeLabel() }}</p>

            @switch ($outcome)
                @case(\App\Support\Onboarding\Monitoring::OUTCOME_PROGRESS)
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                        <li>Intensitas latihan dinaikkan bertahap ke level {{ $e->program->intensity_level }}.</li>
                        <li>Variasi latihan yang lebih menantang ditambahkan.</li>
                        <li>Monitoring berikutnya: {{ $e->program->next_evaluation_on?->translatedFormat('d M Y') }}.</li>
                    </ul>
                    @break
                @case(\App\Support\Onboarding\Monitoring::OUTCOME_MODIFY)
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                        @if ($e->adherence < \App\Support\Onboarding\Monitoring::LOW_ADHERENCE)
                            <li>Kepatuhan latihan masih rendah ({{ $e->adherence }}%). Usahakan berlatih setiap hari sebelum program diubah.</li>
                        @else
                            <li>Dosis latihan disesuaikan (level {{ $e->program->intensity_level }}).</li>
                        @endif
                        <li>Tinjau kembali teknik latihan pada petunjuk setiap gerakan.</li>
                        @foreach ($e->barriers ?? [] as $b)
                            <li><strong>{{ \App\Support\Onboarding\Monitoring::BARRIERS[$b] }}:</strong> {{ \App\Support\Onboarding\Monitoring::BARRIER_TIPS[$b] }}</li>
                        @endforeach
                        <li>Monitoring berikutnya: {{ $e->program->next_evaluation_on?->translatedFormat('d M Y') }}. Bila tetap tidak ada perbaikan dalam {{ \App\Support\Onboarding\Monitoring::REASSESS_AFTER_WEEKS }} minggu, program ditinjau ulang menyeluruh.</li>
                    </ul>
                    @break
                @case(\App\Support\Onboarding\Monitoring::OUTCOME_REASSESS)
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                        <li>Program dijeda untuk evaluasi ulang kondisi (reassessment).</li>
                        <li>Program latihan akan dimodifikasi setelah reassessment.</li>
                        <li>Jadwalkan konsultasi ulang dengan fisioterapis.</li>
                    </ul>
                    @break
                @case(\App\Support\Onboarding\Monitoring::OUTCOME_REFERRAL)
                    <div role="alert" class="mt-3 rounded-xl border-2 border-destructive bg-destructive/10 p-4 text-sm">
                        Kondisi Anda memburuk cukup banyak. Program dijeda. Periksakan diri ke fasilitas kesehatan; bila berat atau mendadak, hubungi 112 / 119 atau datang ke IGD.
                    </div>
                    @break
            @endswitch
        </x-panel>

        <x-panel title="Apakah tujuan fisioterapi tercapai?">
            @if ($e->goal_achieved)
                <p class="text-sm"><strong>Ya.</strong> Program Anda selesai dan dialihkan ke <strong>Maintenance Program</strong> untuk menjaga kondisi dan kebugaran.</p>
            @else
                <p class="text-sm">Belum. Anda tetap berada pada siklus monitoring.</p>
            @endif

            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-border pt-6">
                @if (in_array($outcome, ['modify', 'reassess'], true))
                    <button type="button" wire:click="requestConsultation('teleconsultation')" class="{{ $btn }} border border-border bg-card hover:bg-secondary"><x-lucide-video class="size-4" /> Konsultasi ulang</button>
                @endif
                @if ($outcome === 'referral')
                    <button type="button" wire:click="requestConsultation('referral')" class="{{ $btn }} border border-border bg-card hover:bg-secondary"><x-lucide-hospital class="size-4" /> Minta rujukan</button>
                @endif
                @if (in_array($outcome, ['reassess', 'referral'], true))
                    <a href="{{ route('patient.screening') }}" wire:navigate class="{{ $btn }} bg-primary text-primary-foreground">Mulai reassessment</a>
                @else
                    <a href="{{ route('patient.program') }}" wire:navigate class="{{ $btn }} bg-primary text-primary-foreground">
                        {{ $outcome === 'maintenance' ? 'Buka Maintenance Program' : 'Kembali ke program' }} <x-lucide-arrow-right class="size-4" />
                    </a>
                @endif
            </div>
        </x-panel>
    @elseif ($this->program)
        <div>
            <div class="flex justify-between text-xs text-muted-foreground">
                <span>Langkah {{ $step }} dari {{ $total }}</span>
                <span>{{ $key ? \App\Support\Onboarding\Instruments::TITLES[$key] : 'Kondisi umum' }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                <div class="h-full rounded-full bg-primary transition-all" style="width: {{ (int) round($step / $total * 100) }}%"></div>
            </div>
        </div>

        <x-panel class="sm:p-8">
            <form wire:submit="next" novalidate>
                <div wire:key="m-step-{{ $step }}" class="space-y-7">
                    @if ($step === 1)
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Skala nyeri saat ini (NRS 0–10)</p>
                            <div class="grid grid-cols-11 gap-1">
                                @foreach (range(0, 10) as $v)
                                    <button type="button" wire:click="choose('pain', {{ $v }})"
                                        class="h-10 rounded-lg border text-sm font-medium transition-colors {{ $pick($pain === $v) }}">{{ $v }}</button>
                                @endforeach
                            </div>
                            <p class="flex justify-between text-[11px] text-muted-foreground"><span>Tidak nyeri</span><span>Terburuk</span></p>
                            @error('pain') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <p class="text-sm font-medium">Dibandingkan awal program, kondisi dan kualitas hidup Anda secara umum:</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (\App\Models\ProgramEvaluation::GLOBAL_CHANGE as $v => $label)
                                    <x-onboarding.chip :active="$globalChange === $v" wire:click="choose('globalChange', '{{ $v }}')">{{ $label }}</x-onboarding.chip>
                                @endforeach
                            </div>
                            @error('globalChange') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        @foreach (['newComplaint' => 'Apakah muncul keluhan baru?', 'sideEffect' => 'Apakah ada efek samping setelah latihan (nyeri berlebih, bengkak, pusing)?'] as $field => $question)
                            <div>
                                <p class="text-sm font-medium">{{ $question }}</p>
                                <div class="mt-2 grid max-w-xs grid-cols-2 gap-3">
                                    @foreach (['yes' => 'Ya', 'no' => 'Tidak'] as $v => $label)
                                        <button type="button" wire:click="choose('{{ $field }}', '{{ $v }}')"
                                            class="rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors {{ $pick($this->{$field} === $v) }}">{{ $label }}</button>
                                    @endforeach
                                </div>
                                @error($field) <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                            </div>
                        @endforeach

                        @if ($sideEffect === 'yes')
                            <x-ui.textarea label="Efek samping yang dialami" rows="2" maxlength="300" wire:model.blur="sideEffectText" />
                        @endif

                        <div>
                            <p class="text-sm font-medium">Hambatan saat berlatih (opsional)</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (\App\Support\Onboarding\Monitoring::BARRIERS as $k => $label)
                                    <x-onboarding.chip :active="in_array($k, $barriers, true)" wire:click="toggleBarrier('{{ $k }}')">{{ $label }}</x-onboarding.chip>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <x-instrument-fields :instrument="$key" :a="$answers[$key] ?? []" />
                    @endif
                </div>

                <div class="mt-8 flex items-center justify-between gap-3 border-t border-border pt-6">
                    @if ($step > 1)
                        <x-onboarding.ghost-button wire:click="back"><x-lucide-arrow-left class="size-4" /> Kembali</x-onboarding.ghost-button>
                    @else
                        <span></span>
                    @endif
                    <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="next">
                        <span wire:loading.remove wire:target="next">{{ $step < $total ? 'Lanjut' : 'Analisis hasil' }}</span>
                        <span wire:loading wire:target="next">Menyimpan…</span>
                    </x-onboarding.primary-button>
                </div>
            </form>
        </x-panel>

        <p class="text-xs text-muted-foreground">Parameter monitoring disesuaikan dengan domain fisioterapi Anda. Layanan monitoring TeleFisioRehab tidak menggantikan pemeriksaan fisik langsung oleh tenaga kesehatan.</p>
    @endif
</div>
