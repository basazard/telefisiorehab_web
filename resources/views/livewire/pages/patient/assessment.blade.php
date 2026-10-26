<?php

use App\Models\AssessmentResult;
use App\Support\Onboarding\ClinicalAssessment;
use App\Support\Onboarding\Instruments;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

// Clinical Assessment PRD: one self-report instrument per step, chosen by the domain stored on the assessment.
new #[Layout('layouts.onboarding')] class extends Component {
    /** 1-based index into the instruments. Locked: the browser cannot jump steps. */
    #[Locked]
    public int $step = 1;

    public array $answers = [];

    public function mount(): void
    {
        $assessment = $this->assessment;

        if (! $assessment) {
            $completed = auth()->user()->patient->assessments()->where('status', 'completed')->exists();
            $this->redirectRoute($completed ? 'patient.assessment.result' : 'patient.domain', navigate: true);

            return;
        }

        // an instrument switched off since the assessment started (licence, PRD 10 W6) is replaced before the patient goes on
        $available = Instruments::available($assessment->instruments ?? []);
        if ($available !== ($assessment->instruments ?? [])) {
            $assessment->update(['instruments' => $available]);
            unset($this->assessment, $this->instruments);
        }

        $this->answers = $assessment->answers ?? [];
        foreach ($this->instruments as $key) {
            $this->answers[$key] = array_replace(Instruments::blank($key), $this->answers[$key] ?? []);
        }
        $this->step = max(1, min($this->total(), $assessment->current_step));
    }

    #[Computed]
    public function assessment(): ?AssessmentResult
    {
        return auth()->user()->patient->assessments()->where('status', 'in_progress')->latest()->first();
    }

    /** @return string[] */
    #[Computed]
    public function instruments(): array
    {
        return $this->assessment?->instruments ?? [];
    }

    public function total(): int
    {
        return max(1, count($this->instruments));
    }

    public function current(): ?string
    {
        return $this->instruments[$this->step - 1] ?? null;
    }

    /** Chip/scale buttons. Only paths that exist in the current instrument can be set; values are validated on "next". */
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
        if ($key = $this->current()) {
            $this->validate(Instruments::rules($key, $this->answers[$key]), $this->messages());
        }

        if ($this->step < $this->total()) {
            $this->step++;
            $this->persist();

            return;
        }

        $this->finish();
    }

    private function persist(array $extra = []): void
    {
        $this->assessment->update([
            'answers' => Arr::only($this->answers, $this->instruments),
            'current_step' => $this->step,
        ] + $extra);
    }

    private function finish(): void
    {
        $assessment = $this->assessment;

        // Re-validate every instrument: the analysis only ever runs on complete, valid answers.
        foreach ($this->instruments as $key) {
            $this->validate(Instruments::rules($key, $this->answers[$key]), $this->messages());
        }

        $result = ClinicalAssessment::analyse($this->instruments, $this->answers, (int) $assessment->pain);
        $odi = isset($result['scores']['odi']) ? array_sum(array_map('intval', array_filter($this->answers['odi'], 'is_numeric'))) : null;

        $this->persist([
            'scores' => $result['scores'],
            'findings' => $result['findings'],
            'warnings' => $result['warnings'],
            'has_limitation' => $result['has_limitation'],
            'outcome' => $result['outcome'],
            'lefs' => $result['scores']['lefs']['value'] ?? null,
            'odi' => $odi,
            'odi_percent' => $result['scores']['odi']['value'] ?? null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Scoring & Risk Stratification runs right away; an emergency stops the program like a red flag.
        $assessment->refresh()->stratify();

        $this->redirectRoute(auth()->user()->patient->isRedFlagLocked() ? 'patient.red-flag.stop' : 'patient.assessment.result', navigate: true);
    }

    private function messages(): array
    {
        return [
            'required' => 'Pertanyaan ini wajib dijawab.',
            'required_with' => 'Isi kedua ukuran lingkar, atau kosongkan keduanya.',
            'integer' => 'Isi dengan bilangan bulat.',
            'numeric' => 'Isi dengan angka.',
            'between' => 'Nilai harus antara :min dan :max.',
            'max' => 'Maksimal :max karakter.',
            'in' => 'Pilih salah satu jawaban.',
        ];
    }
}; ?>

@php
    $key = $this->current();
    $total = $this->total();
    $a = $key ? ($answers[$key] ?? []) : [];
@endphp

<div class="space-y-6">
    @if ($this->assessment)
    <x-page-header title="Clinical Assessment" :subtitle="'Domain '.$this->assessment->domain->label()" />

    <div>
        <div class="flex justify-between text-xs text-muted-foreground">
            <span>Langkah {{ $step }} dari {{ $total }}</span>
            <span>{{ $key ? \App\Support\Onboarding\Instruments::title($key) : 'Penilaian oleh fisioterapis' }}</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
            <div class="h-full rounded-full bg-primary transition-all" style="width: {{ (int) round($step / $total * 100) }}%"></div>
        </div>
    </div>

    <x-panel class="sm:p-8">
        <form wire:submit="next" novalidate>
            <div wire:key="step-{{ $step }}" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                @if (! $key)
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><x-lucide-stethoscope class="size-5" /></span>
                        <div>
                            <h2 class="text-lg font-bold">Penilaian oleh fisioterapis</h2>
                            <p class="text-sm text-muted-foreground">Instrumen untuk domain ini ({{ implode(', ', $this->assessment->domain->clinicianInstruments()) }}) dilakukan oleh fisioterapis. Selesaikan langkah ini agar Anda diarahkan ke tahap berikutnya.</p>
                        </div>
                    </div>
                @else
                    <x-instrument-fields :instrument="$key" :a="$a" />
                @endif
            </div>

            <div class="mt-8 flex items-center justify-between gap-3 border-t border-border pt-6">
                @if ($step > 1)
                    <x-onboarding.ghost-button wire:click="back"><x-lucide-arrow-left class="size-4" /> Kembali</x-onboarding.ghost-button>
                @else
                    <a href="{{ route('patient.domain') }}" wire:navigate
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-6 py-3 text-sm font-medium hover:bg-secondary"><x-lucide-arrow-left class="size-4" /> Domain</a>
                @endif

                <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="next">
                    <span wire:loading.remove wire:target="next">{{ $step < $total ? 'Lanjut' : 'Analisis hasil' }}</span>
                    <span wire:loading wire:target="next">Menyimpan…</span>
                </x-onboarding.primary-button>
            </div>
        </form>
    </x-panel>

    <x-clinical-disclaimer />
    @endif
</div>
