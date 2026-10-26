<?php

use App\Enums\PhysioDomain;
use App\Models\AssessmentResult;
use App\Models\Screening;
use App\Support\Onboarding\ClinicalAssessment;
use App\Support\Onboarding\Instruments;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Clinical Assessment PRD, step "Identifikasi Domain Fisioterapi (Berdasarkan Keluhan Utama)".
new #[Layout('layouts.onboarding')] class extends Component {
    public function mount(): void
    {
        if (! $this->screening) {
            $this->redirectRoute('patient.screening', navigate: true);
        }
    }

    #[Computed]
    public function screening(): ?Screening
    {
        return auth()->user()->patient->screenings()->where('status', 'completed')->latest('completed_at')->first();
    }

    #[Computed]
    public function assessment(): ?AssessmentResult
    {
        return $this->screening?->assessment;
    }

    /** @return array{domain: PhysioDomain, reason: string, instruments: string[]} */
    #[Computed]
    public function identification(): array
    {
        if ($a = $this->assessment) {
            return ['domain' => $a->domain, 'reason' => $a->domain_reason, 'instruments' => $a->instruments ?? []];
        }

        $patient = auth()->user()->patient;
        $picked = ClinicalAssessment::pickDomain(
            $patient->age,
            $patient->special_conditions?->map->value->all() ?? [],
            $this->screening->main_complaints['keys'] ?? [],
            $patient->occupation,
        );

        return $picked + ['instruments' => ClinicalAssessment::instruments($picked['domain'], $this->screening->regions ?? [])];
    }

    public function start(): void
    {
        $screening = $this->screening;
        abort_unless($screening, 404);

        if (! $screening->assessment) {
            // The domain is decided here, on the server, and stored as a snapshot.
            $patient = auth()->user()->patient;
            $id = $this->identification;

            $screening->assessment()->create([
                'clinic_id' => $patient->clinic_id,
                'patient_id' => $patient->id,
                'domain' => $id['domain'],
                'domain_reason' => $id['reason'],
                'track' => $patient->module?->value,
                'instruments' => $id['instruments'],
                'answers' => array_combine($id['instruments'], array_map([Instruments::class, 'blank'], $id['instruments'])) ?: [],
                'pain' => $screening->max_pain,
                'status' => 'in_progress',
                'current_step' => 1,
            ]);
        }

        $this->redirectRoute($screening->fresh()->assessment->isCompleted() ? 'patient.assessment.result' : 'patient.assessment', navigate: true);
    }
}; ?>

<div class="space-y-6">
    @if ($this->screening)
    @php
        ['domain' => $domain, 'reason' => $reason, 'instruments' => $instruments] = $this->identification;
        $assessment = $this->assessment;
    @endphp

    <x-page-header title="Identifikasi Domain Fisioterapi" subtitle="Ditentukan dari keluhan utama dan profil Anda." />

    <x-panel class="sm:p-8">
        <div class="flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                <x-dynamic-component :component="'lucide-'.$domain->icon()" class="size-6" />
            </span>
            <div>
                <p class="text-xs uppercase tracking-wide text-muted-foreground">Domain Anda</p>
                <h2 class="font-display text-2xl font-bold">{{ $domain->label() }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">Alasan: {{ $reason }}.</p>
            </div>
        </div>

        <div class="mt-6">
            <p class="text-sm font-medium">Parameter fokus</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($domain->focus() as $f)
                    <x-ui.badge variant="secondary">{{ $f }}</x-ui.badge>
                @endforeach
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-border p-4">
                <p class="flex items-center gap-2 text-sm font-medium"><x-lucide-clipboard-list class="size-4 text-primary" /> Anda isi sekarang</p>
                @if ($instruments)
                    <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                        <li>Skala nyeri (NRS) dari screening awal</li>
                        @foreach ($instruments as $key)
                            <li>{{ \App\Support\Onboarding\Instruments::title($key) }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm text-muted-foreground">Tidak ada kuesioner mandiri untuk domain ini. Penilaian dilakukan oleh fisioterapis.</p>
                @endif
            </div>
            <div class="rounded-xl border border-border p-4">
                <p class="flex items-center gap-2 text-sm font-medium"><x-lucide-stethoscope class="size-4 text-primary" /> Dinilai oleh fisioterapis</p>
                <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                    @foreach ($domain->clinicianInstruments() as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-8 flex justify-end border-t border-border pt-6">
            <x-onboarding.primary-button wire:click="start" wire:loading.attr="disabled" wire:target="start">
                @if ($assessment?->isCompleted()) Lihat hasil assessment
                @elseif ($assessment) Lanjutkan assessment
                @else Mulai assessment @endif
                <x-lucide-arrow-right class="size-4" />
            </x-onboarding.primary-button>
        </div>
    </x-panel>

    <x-clinical-disclaimer />
    @endif
</div>
