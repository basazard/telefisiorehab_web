<?php

use App\Models\Program;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// Exercise / Telefisioterapi Recommendation PRD: the patient's program, exercise log, evaluation reminder.
new class extends Component {
    /** @var array<int, int|string|null> pain after the exercise, per program item id */
    public array $pain = [];

    #[Computed]
    public function program(): ?Program
    {
        return auth()->user()->patient->currentProgram()?->load('items.exercise', 'assessment');
    }

    /** @return int[] program item ids logged today */
    #[Computed]
    public function doneToday(): array
    {
        $program = $this->program;

        return $program
            ? $program->logs()->whereDate('performed_at', today())->pluck('program_exercise_id')->unique()->all()
            : [];
    }

    #[Computed]
    public function latestTier()
    {
        return auth()->user()->patient->assessments()->where('status', 'completed')->latest('completed_at')->first()?->tier;
    }

    public function complete(int $itemId): void
    {
        $program = $this->program;
        abort_unless($program?->isActive(), 403);                     // paused / ended programs cannot be logged

        $item = $program->items->firstWhere('id', $itemId);
        abort_unless($item, 404);

        $this->validate(["pain.$itemId" => ['nullable', 'integer', 'between:0,10']], ["pain.$itemId.between" => 'Pilih 0–10.']);

        if (in_array($itemId, $this->doneToday, true)) {
            return;
        }

        $pain = ($this->pain[$itemId] ?? '') === '' ? null : (int) $this->pain[$itemId];

        $item->logs()->create([
            'clinic_id' => $program->clinic_id,
            'patient_id' => $program->patient_id,
            'performed_at' => now(),
            'sets_done' => $item->sets,
            'reps_done' => $item->reps,
            'duration_seconds' => $item->minutes ? $item->minutes * 60 : null,
            'pain_score' => $pain,
        ]);

        unset($this->doneToday);

        if ($pain !== null && $pain >= 7) {
            $this->dispatch('toast', message: 'Nyeri berat setelah latihan', description: 'Hentikan latihan ini dan pertimbangkan reassessment atau konsultasi.', type: 'error');
        } else {
            $this->dispatch('toast', message: 'Latihan dicatat', type: 'success');
        }
    }

    public function requestReconsultation(): void
    {
        $program = $this->program;
        abort_unless($program, 404);
        $patient = auth()->user()->patient;

        if (! $patient->consultationRequests()->where('type', 'teleconsultation')->where('status', 'open')->exists()) {
            $patient->consultationRequests()->create([
                'clinic_id' => $patient->clinic_id,
                'assessment_id' => $program->assessment_id,
                'type' => 'teleconsultation',
                'reason' => 'Konsultasi ulang: evaluasi program menunjukkan tidak ada perbaikan.',
                'status' => 'open',
            ]);
        }

        $this->dispatch('toast', message: 'Permintaan konsultasi ulang terkirim.', type: 'success');
    }
}; ?>

<div class="space-y-6">
    @php $program = $this->program; @endphp

    @if (! $program)
        <x-page-header title="Program latihan" />
        <x-panel>
            @if ($this->latestTier === \App\Enums\RiskTier::High)
                <p class="text-sm">Berdasarkan hasil klasifikasi risiko, Anda <strong>tidak diberikan latihan mandiri</strong>. Konsultasikan kondisi Anda dengan fisioterapis atau fasilitas kesehatan.</p>
                <a href="{{ route('patient.intervention') }}" wire:navigate class="mt-4 inline-flex text-sm font-medium text-primary underline">Lihat rekomendasi & ajukan konsultasi</a>
            @elseif ($this->latestTier)
                <p class="text-sm text-muted-foreground">Anda belum memulai program latihan.</p>
                <a href="{{ route('patient.intervention') }}" wire:navigate class="mt-4 inline-flex text-sm font-medium text-primary underline">Lihat rekomendasi intervensi</a>
            @else
                <p class="text-sm text-muted-foreground">Program latihan disusun setelah screening dan assessment selesai.</p>
                <a href="{{ route('patient.screening') }}" wire:navigate class="mt-4 inline-flex text-sm font-medium text-primary underline">Mulai screening</a>
            @endif
        </x-panel>
    @else
        @php
            $done = count($this->doneToday);
            $total = $program->items->count();
        @endphp

        <div class="flex flex-wrap items-end justify-between gap-3">
            <x-page-header :title="$program->title" :subtitle="'Level intensitas '.$program->intensity_level.' dari '.\App\Support\Onboarding\Intervention::MAX_LEVEL" />
            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="secondary">{{ $program->status->label() }}</x-ui.badge>
                @if ($program->needs_monitoring) <x-ui.badge variant="outline">Dipantau fisioterapis</x-ui.badge> @endif
            </div>
        </div>

        @if (! $program->isActive())
            <div role="alert" class="rounded-2xl border-2 border-warning/60 bg-warning/10 p-5 sm:p-6">
                <p class="flex items-center gap-2 font-semibold"><x-lucide-refresh-cw class="size-5" /> Evaluasi: belum ada perbaikan</p>
                <p class="mt-1 text-sm">Program dijeda. Lakukan evaluasi ulang kondisi agar program bisa dimodifikasi, dan pertimbangkan konsultasi atau rujukan medis.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('patient.screening') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">Mulai reassessment</a>
                    <button type="button" wire:click="requestReconsultation" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-5 py-2.5 text-sm font-medium hover:bg-secondary">
                        <x-lucide-video class="size-4" /> Ajukan konsultasi ulang
                    </button>
                </div>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat label="Latihan hari ini" :value="$done.'/'.$total" :sub="$done === $total ? 'Semua selesai' : 'Ayo lanjutkan'" />
                <x-stat label="Evaluasi berikutnya" :value="$program->next_evaluation_on?->translatedFormat('d M')" :sub="'setiap '.$program->evaluation_interval_weeks.' minggu'" />
                <x-stat label="Jalur" :value="\App\Support\Onboarding\Intervention::pathway($program->assessment->tier)['label']" sub="dari klasifikasi risiko" />
            </div>

            @if ($program->isEvaluationDue())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border-2 border-primary/40 bg-primary/5 p-4">
                    <p class="flex items-center gap-2 text-sm font-medium"><x-lucide-calendar-check class="size-5 text-primary" /> Saatnya evaluasi program Anda.</p>
                    <a href="{{ route('patient.evaluation') }}" wire:navigate class="rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground">Isi evaluasi</a>
                </div>
            @endif

            <div class="space-y-4">
                @foreach ($program->items as $item)
                    @php $isDone = in_array($item->id, $this->doneToday, true); @endphp
                    <x-panel wire:key="item-{{ $item->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-display font-bold">{{ $item->exercise->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ $item->exercise->type->label() }} · <span class="font-medium text-foreground">{{ $item->doseLabel() }}</span></p>
                            </div>
                            @if ($isDone) <x-ui.badge variant="secondary"><x-lucide-check class="mr-1 size-3.5" /> Selesai hari ini</x-ui.badge> @endif
                        </div>

                        @if ($item->exercise->video_url)
                            <a href="{{ $item->exercise->video_url }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm text-primary underline"><x-lucide-circle-play class="size-4" /> Tonton video</a>
                        @endif
                        <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm">
                            @foreach (explode("\n", $item->exercise->instructions) as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                        <p class="mt-3 flex items-start gap-2 text-xs text-muted-foreground"><x-lucide-triangle-alert class="mt-0.5 size-3.5 shrink-0" /> {{ $item->exercise->precautions }}</p>

                        @unless ($isDone)
                            <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-border pt-4">
                                <label class="text-sm">
                                    <span class="text-muted-foreground">Nyeri setelah latihan (opsional)</span>
                                    <select wire:model="pain.{{ $item->id }}" class="mt-1 block rounded-lg border border-input bg-card px-3 py-2 text-sm">
                                        <option value="">–</option>
                                        @foreach (range(0, 10) as $v) <option value="{{ $v }}">{{ $v }}</option> @endforeach
                                    </select>
                                </label>
                                <x-onboarding.primary-button wire:click="complete({{ $item->id }})" wire:loading.attr="disabled" wire:target="complete({{ $item->id }})">
                                    <x-lucide-check class="size-4" /> Tandai selesai
                                </x-onboarding.primary-button>
                            </div>
                            @error("pain.$item->id") <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                        @endunless
                    </x-panel>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-muted/40 p-4 text-sm">
                <span>Ada keluhan baru atau kondisi memburuk?</span>
                <div class="flex gap-3">
                    <a href="{{ route('patient.evaluation') }}" wire:navigate class="font-medium text-primary underline">Evaluasi sekarang</a>
                    <a href="{{ route('patient.screening') }}" wire:navigate class="font-medium text-primary underline">Reassessment</a>
                </div>
            </div>
        @endif
    @endif
</div>
