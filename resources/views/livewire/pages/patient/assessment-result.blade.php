<?php

use App\Models\AssessmentResult;
use App\Support\Onboarding\ClinicalAssessment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Clinical Assessment PRD sections 3.2 and 3.3: interpretation, the Ya/Tidak branch, and the "Selesai" status.
new #[Layout('layouts.onboarding')] class extends Component {
    public function mount(): void
    {
        if (! $this->assessment) {
            $this->redirectRoute('patient.domain', navigate: true);
        }
    }

    #[Computed]
    public function assessment(): ?AssessmentResult
    {
        return auth()->user()->patient->assessments()->where('status', 'completed')->latest('completed_at')->first();
    }
}; ?>

<div class="space-y-6">
    @if ($a = $this->assessment)
    <div class="flex flex-wrap items-end justify-between gap-3">
        <x-page-header title="Hasil Clinical Assessment" :subtitle="'Domain '.$a->domain->label().' · '.$a->completed_at->translatedFormat('d M Y')" />
        <div class="flex gap-2">
            @if ($a->tier) <x-ui.badge :variant="$a->tier->badge()">{{ $a->tier->label() }}</x-ui.badge> @endif
            <x-ui.badge variant="secondary"><x-lucide-circle-check class="mr-1 size-3.5" /> Selesai</x-ui.badge>
        </div>
    </div>

    @foreach ($a->warnings ?? [] as $warning)
        <div role="alert" class="flex items-start gap-3 rounded-xl border-2 border-destructive bg-destructive/10 p-4 text-sm text-destructive">
            <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0" />
            <span>{{ $warning }}</span>
        </div>
    @endforeach

    <x-panel title="Analisis hasil assessment" sub="Skor dihitung otomatis dari jawaban Anda.">
        <ul class="divide-y divide-border">
            <li class="flex items-center justify-between gap-3 py-3 text-sm">
                <span>Skala nyeri (NRS, dari screening)</span>
                <span class="flex items-center gap-2 font-medium">{{ $a->pain }}/10
                    @if ($a->pain >= \App\Support\Onboarding\ClinicalAssessment::PAIN_LIMIT) <x-ui.badge variant="destructive">Perlu perhatian</x-ui.badge> @endif
                </span>
            </li>
            @foreach ($a->scores ?? [] as $key => $score)
                <li class="flex items-center justify-between gap-3 py-3 text-sm">
                    <span>{{ \App\Support\Onboarding\Instruments::title($key) }}</span>
                    <span class="flex items-center gap-2 text-right font-medium">{{ $score['display'] }}
                        @if ($score['limited']) <x-ui.badge variant="destructive">Perlu perhatian</x-ui.badge> @endif
                    </span>
                </li>
            @endforeach
        </ul>
        @if ($a->domain->clinicianInstruments())
            <p class="mt-4 text-xs text-muted-foreground">Menunggu penilaian fisioterapis: {{ implode(', ', $a->domain->clinicianInstruments()) }}.</p>
        @endif
    </x-panel>

    <x-panel title="Apakah ditemukan keterbatasan fungsi atau risiko?">
        @if ($a->has_limitation)
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-warning/20"><x-lucide-list-checks class="size-5" /></span>
                <div class="space-y-3">
                    <p class="font-semibold">Ya. Lanjut ke klasifikasi risiko.</p>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        @foreach ($a->findings ?? [] as $finding)
                            <li>{{ $finding }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="mt-6 flex justify-end border-t border-border pt-6">
                <a href="{{ route('patient.risk-classification') }}" wire:navigate
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-[var(--shadow-soft)] hover:opacity-95">
                    Lanjut ke Klasifikasi Risiko <x-lucide-arrow-right class="size-4" />
                </a>
            </div>
        @else
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-success/20"><x-lucide-circle-check class="size-5" /></span>
                <div class="space-y-3">
                    <p class="font-semibold">Tidak. Berikut edukasi pencegahan dan latihan mandiri.</p>
                    <ul class="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        @foreach (\App\Support\Onboarding\ClinicalAssessment::EDUCATION as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="mt-6 flex justify-end border-t border-border pt-6">
                <a href="{{ route('patient.intervention') }}" wire:navigate
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-[var(--shadow-soft)] hover:opacity-95">
                    Lanjut ke Rekomendasi Intervensi <x-lucide-arrow-right class="size-4" />
                </a>
            </div>
        @endif
    </x-panel>

    <x-clinical-disclaimer />
    @endif
</div>
