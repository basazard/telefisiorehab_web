<?php

use App\Models\AssessmentResult;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Scoring & Risk Stratification PRD: per-instrument bands, the overall category, and the CTA to Rekomendasi Intervensi.
new #[Layout('layouts.onboarding')] class extends Component {
    public function mount(): void
    {
        $assessment = $this->assessment;

        if (! $assessment) {
            $this->redirectRoute('patient.domain', navigate: true);

            return;
        }

        // Assessments completed before this module existed are stratified on first view.
        if (! $assessment->isStratified()) {
            $assessment->stratify();
            unset($this->assessment);

            if (auth()->user()->patient->isRedFlagLocked()) {
                $this->redirectRoute('patient.red-flag.stop', navigate: true);
            }
        }
    }

    #[Computed]
    public function assessment(): ?AssessmentResult
    {
        return auth()->user()->patient->assessments()->where('status', 'completed')->latest('completed_at')->first();
    }
}; ?>

<div class="space-y-6">
    @if (($a = $this->assessment) && $a->tier)
    @php
        $tier = $a->tier;
        $tone = [
            'low' => 'border-success/50 bg-success/10',
            'moderate' => 'border-warning/60 bg-warning/10',
            'high' => 'border-destructive bg-destructive/10',
        ][$tier->value];
    @endphp

    <x-page-header title="Klasifikasi Risiko" :subtitle="'Domain '.$a->domain->label().' · berdasarkan hasil Clinical Assessment'" />

    <div class="rounded-2xl border-2 p-6 sm:p-8 {{ $tone }}">
        <p class="text-xs uppercase tracking-wide text-muted-foreground">Kategori risiko Anda</p>
        <h2 @class(['font-display text-3xl font-bold', 'text-destructive' => $tier === \App\Enums\RiskTier::High])>{{ $tier->label() }}</h2>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
            @foreach ($tier->criteria() as $c)
                <li>{{ $c }}</li>
            @endforeach
        </ul>
    </div>

    @if ($a->needsSupervision())
        <div role="alert" class="flex items-start gap-3 rounded-xl border-2 border-destructive bg-destructive/10 p-4 text-sm">
            <x-lucide-triangle-alert class="mt-0.5 size-5 shrink-0 text-destructive" />
            <div class="space-y-1">
                <p class="font-semibold text-destructive">Perlu perhatian tenaga kesehatan</p>
                <p>Hasil Anda masuk kategori risiko tinggi. Konsultasikan dengan fisioterapis atau fasilitas kesehatan sebelum memulai latihan. Program latihan Anda akan disusun dengan supervisi fisioterapis.</p>
                <p>Jika kondisi terasa berat atau memburuk, hubungi layanan darurat (112), ambulans (119), atau datang ke IGD.</p>
            </div>
        </div>
    @endif

    <x-panel title="Skor per instrumen" sub="Dicocokkan dengan ambang batas (cut-off) masing-masing instrumen.">
        <ul class="divide-y divide-border">
            @foreach ($a->risk_details ?? [] as $item)
                @php $itemTier = \App\Enums\RiskTier::from($item['tier']); @endphp
                <li class="flex items-center justify-between gap-3 py-3 text-sm">
                    <span>{{ $item['label'] }}</span>
                    <span class="flex items-center gap-2 text-right font-medium">{{ $item['display'] }}
                        <x-ui.badge :variant="$itemTier->badge()">{{ str_replace('Risiko ', '', $itemTier->label()) }}</x-ui.badge>
                    </span>
                </li>
            @endforeach
        </ul>
        @if ($a->domain->clinicianInstruments())
            <p class="mt-4 text-xs text-muted-foreground">Belum termasuk penilaian fisioterapis: {{ implode(', ', $a->domain->clinicianInstruments()) }}.</p>
        @endif
    </x-panel>

    <div class="flex justify-end">
        <a href="{{ route('patient.intervention') }}" wire:navigate
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-[var(--shadow-soft)] hover:opacity-95">
            Lanjut ke Modul Rekomendasi Intervensi <x-lucide-arrow-right class="size-4" />
        </a>
    </div>

    {{-- "Catatan Sistem & Disclaimers" (PRD 3.4) --}}
    <div class="rounded-xl border border-border bg-muted/40 p-4 text-xs text-muted-foreground">
        <p class="flex items-center gap-2 font-medium text-foreground"><x-lucide-info class="size-4" /> Catatan</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Cut-off setiap instrumen disesuaikan dengan populasi dan pedoman klinis yang berlaku.</li>
            <li>Klasifikasi risiko dipakai untuk menentukan prioritas intervensi, <strong>bukan</strong> diagnosis medis.</li>
            <li>Hasil selalu diinterpretasikan oleh fisioterapis.</li>
            <li>Jika muncul kondisi gawat darurat, segera ke fasilitas kesehatan terdekat.</li>
        </ul>
    </div>
    @endif
</div>
