<?php

use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Programs;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

// "Monitoring dan Evaluasi" (PRD 3.2): pain, function, mobility, global change; adherence comes from the exercise log.
new class extends Component {
    public ?int $pain = null;
    public ?int $function = null;
    public ?int $mobility = null;
    public string $globalChange = '';
    public ?string $newComplaint = null;

    /** Set after submitting; the result is shown on the same page. */
    #[Locked]
    public ?int $evaluationId = null;

    public function mount(): void
    {
        if (! $this->program?->isActive()) {
            $this->redirectRoute('patient.program', navigate: true);
        }
    }

    #[Computed]
    public function program(): ?Program
    {
        return auth()->user()->patient->currentProgram();
    }

    #[Computed]
    public function evaluation(): ?ProgramEvaluation
    {
        return $this->evaluationId ? auth()->user()->patient->evaluations()->find($this->evaluationId) : null;
    }

    public function choose(string $field, int|string $value): void
    {
        abort_unless(in_array($field, ['pain', 'function', 'mobility', 'globalChange', 'newComplaint'], true), 422);
        $this->{$field} = $value;
    }

    public function submit(): void
    {
        $program = $this->program;
        abort_unless($program?->isActive(), 403);

        $this->validate([
            'pain' => ['required', 'integer', 'between:0,10'],
            'function' => ['required', 'integer', 'between:0,10'],
            'mobility' => ['required', 'integer', 'between:0,10'],
            'globalChange' => ['required', Rule::in(array_keys(ProgramEvaluation::GLOBAL_CHANGE))],
            'newComplaint' => ['required', Rule::in(['yes', 'no'])],
        ], ['required' => 'Pertanyaan ini wajib dijawab.']);

        $evaluation = Programs::evaluate($program, [
            'pain' => $this->pain,
            'function' => $this->function,
            'mobility' => $this->mobility,
            'global_change' => $this->globalChange,
            'new_complaint' => $this->newComplaint === 'yes',
        ]);

        $this->evaluationId = $evaluation->id;
    }
}; ?>

@php
    $pick = fn (bool $on) => $on ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:bg-secondary';
    $scales = [
        'pain' => ['Nyeri saat ini', 'Tidak nyeri', 'Terburuk'],
        'function' => ['Kemampuan melakukan aktivitas sehari-hari', 'Tidak mampu', 'Seperti sebelum keluhan'],
        'mobility' => ['Kemudahan bergerak / berjalan', 'Sangat sulit', 'Tanpa kesulitan'],
    ];
@endphp

<div class="space-y-6">
    <x-page-header title="Evaluasi program" subtitle="Monitoring perkembangan: nyeri, fungsi, mobilitas, dan kepatuhan latihan." />

    @if ($e = $this->evaluation)
        <x-panel title="Apakah ada perbaikan?">
            @if ($e->improved)
                <p class="flex items-center gap-2 text-lg font-bold"><x-lucide-circle-check class="size-6 text-success" /> Ya, ada perbaikan</p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                    <li>Pertahankan latihan Anda.</li>
                    <li>Intensitas dinaikkan bertahap ke level {{ $e->program->intensity_level }}.</li>
                    <li>Monitoring berlanjut; evaluasi berikutnya {{ $e->program->next_evaluation_on?->translatedFormat('d M Y') }}.</li>
                </ul>
            @else
                <p class="flex items-center gap-2 text-lg font-bold"><x-lucide-refresh-cw class="size-6 text-warning" /> Belum ada perbaikan</p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
                    <li>Program dijeda untuk evaluasi ulang kondisi.</li>
                    <li>Program latihan akan dimodifikasi setelah reassessment.</li>
                    <li>Pertimbangkan konsultasi ulang atau rujukan medis.</li>
                </ul>
            @endif
            <p class="mt-4 text-xs text-muted-foreground">Kepatuhan latihan sejak evaluasi terakhir: {{ $e->adherence }}% hari.</p>
            <div class="mt-6 flex justify-end border-t border-border pt-6">
                <a href="{{ route('patient.program') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-medium text-primary-foreground">
                    {{ $e->improved ? 'Kembali ke program' : 'Lanjut: reassessment / konsultasi' }} <x-lucide-arrow-right class="size-4" />
                </a>
            </div>
        </x-panel>
    @else
        <x-panel class="sm:p-8">
            <form wire:submit="submit" novalidate class="space-y-7">
                @foreach ($scales as $field => [$question, $lowLabel, $highLabel])
                    <div class="space-y-2">
                        <p class="text-sm font-medium">{{ $question }} (0–10)</p>
                        <div class="grid grid-cols-11 gap-1">
                            @foreach (range(0, 10) as $v)
                                <button type="button" wire:click="choose('{{ $field }}', {{ $v }})"
                                    class="h-10 rounded-lg border text-sm font-medium transition-colors {{ $pick($this->{$field} === $v) }}">{{ $v }}</button>
                            @endforeach
                        </div>
                        <p class="flex justify-between text-[11px] text-muted-foreground"><span>{{ $lowLabel }}</span><span>{{ $highLabel }}</span></p>
                        @error($field) <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div>
                    <p class="text-sm font-medium">Dibandingkan awal program, kondisi Anda:</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (\App\Models\ProgramEvaluation::GLOBAL_CHANGE as $v => $label)
                            <x-onboarding.chip :active="$globalChange === $v" wire:click="choose('globalChange', '{{ $v }}')">{{ $label }}</x-onboarding.chip>
                        @endforeach
                    </div>
                    @error('globalChange') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div>
                    <p class="text-sm font-medium">Apakah muncul keluhan baru?</p>
                    <div class="mt-2 grid max-w-xs grid-cols-2 gap-3">
                        @foreach (['yes' => 'Ya', 'no' => 'Tidak'] as $v => $label)
                            <button type="button" wire:click="choose('newComplaint', '{{ $v }}')"
                                class="rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors {{ $pick($newComplaint === $v) }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    @error('newComplaint') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end border-t border-border pt-6">
                    <x-onboarding.primary-button type="submit" wire:loading.attr="disabled" wire:target="submit">Kirim evaluasi</x-onboarding.primary-button>
                </div>
            </form>
        </x-panel>
    @endif
</div>
