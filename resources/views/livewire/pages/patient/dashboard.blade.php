<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// "Today": next step in the flow, today's exercises and the in-app evaluation reminder.
new class extends Component {
    #[Computed]
    public function patient()
    {
        return auth()->user()->patient;
    }

    #[Computed]
    public function program()
    {
        return $this->patient->currentProgram()?->load('items');
    }

    #[Computed]
    public function assessment()
    {
        return $this->patient->assessments()->where('status', 'completed')->latest('completed_at')->first();
    }

    /** The last program a physiotherapist ended, when no assessment followed it yet: the patient should reassess. */
    #[Computed]
    public function endedByStaff()
    {
        $ended = $this->patient->programs()->whereNotNull('ended_by')->latest('ended_at')->first();

        return $ended && ! ($this->assessment?->completed_at?->gt($ended->ended_at)) ? $ended : null;
    }

    #[Computed]
    public function doneToday(): int
    {
        return $this->program ? $this->program->logs()->whereDate('performed_at', today())->distinct()->count('program_exercise_id') : 0;
    }

    #[Computed]
    public function openRequests()
    {
        return $this->patient->consultationRequests()->where('status', 'open')->get();
    }
}; ?>

@php
    $program = $this->program;
    $assessment = $this->assessment;
    $link = 'inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground';
@endphp

<div class="space-y-6">
    <x-page-header :title="'Halo, '.auth()->user()->name" subtitle="Ringkasan program Anda hari ini." />

    @if ($program?->isActive())
        @php $total = $program->items->count(); @endphp

        @if ($program->isEvaluationDue())
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border-2 border-primary/40 bg-primary/5 p-4">
                <p class="flex items-center gap-2 text-sm font-medium"><x-lucide-calendar-check class="size-5 text-primary" /> Pengingat: saatnya monitoring perkembangan Anda.</p>
                <a href="{{ route('patient.evaluation') }}" wire:navigate class="{{ $link }}">Isi monitoring</a>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat label="Latihan hari ini" :value="$this->doneToday.'/'.$total" :sub="$this->doneToday >= $total ? 'Semua selesai, bagus!' : 'Belum selesai'" />
            <x-stat label="Monitoring berikutnya" :value="$program->next_evaluation_on?->translatedFormat('d M')" sub="monitoring berkala" />
            <x-stat label="Kategori risiko" :value="str_replace('Risiko ', '', $program->assessment?->tier?->label() ?? '-')" sub="dari assessment terakhir" />
        </div>

        <x-panel :title="$program->title">
            <p class="text-sm text-muted-foreground">{{ $this->doneToday >= $total ? 'Latihan hari ini sudah lengkap.' : 'Masih ada latihan yang belum dikerjakan hari ini.' }}</p>
            <a href="{{ route('patient.program') }}" wire:navigate class="mt-4 {{ $link }}"><x-lucide-dumbbell class="size-4" /> Buka program</a>
        </x-panel>
    @elseif ($program)
        <div role="alert" class="rounded-2xl border-2 border-warning/60 bg-warning/10 p-5">
            <p class="font-semibold">Program dijeda: belum ada perbaikan</p>
            <p class="mt-1 text-sm">Lakukan reassessment atau ajukan konsultasi ulang.</p>
            <a href="{{ route('patient.program') }}" wire:navigate class="mt-4 {{ $link }}">Lihat pilihan</a>
        </div>
    @elseif ($this->endedByStaff)
        <x-panel title="Program diakhiri oleh fisioterapis">
            <p class="text-sm">{{ $this->endedByStaff->end_reason }}</p>
            <p class="mt-1 text-sm text-muted-foreground">Lakukan reassessment agar program baru bisa disusun.</p>
            <a href="{{ route('patient.screening') }}" wire:navigate class="mt-4 {{ $link }}">Mulai reassessment</a>
        </x-panel>
    @elseif ($assessment?->tier === \App\Enums\RiskTier::High)
        <x-panel title="Konsultasi diperlukan">
            <p class="text-sm">Hasil klasifikasi risiko Anda tinggi. Latihan mandiri tidak diberikan; ajukan telekonsultasi atau rujukan.</p>
            <a href="{{ route('patient.intervention') }}" wire:navigate class="mt-4 {{ $link }}">Lihat rekomendasi</a>
        </x-panel>
    @elseif ($assessment)
        <x-panel title="Langkah berikutnya">
            <p class="text-sm text-muted-foreground">Assessment selesai. Lihat rekomendasi intervensi untuk memulai program.</p>
            <a href="{{ route('patient.intervention') }}" wire:navigate class="mt-4 {{ $link }}">Rekomendasi intervensi</a>
        </x-panel>
    @else
        <x-panel title="Langkah berikutnya">
            <p class="text-sm text-muted-foreground">Selesaikan screening dan assessment agar program latihan bisa disusun.</p>
            <a href="{{ route('patient.screening') }}" wire:navigate class="mt-4 {{ $link }}">Lanjutkan screening</a>
        </x-panel>
    @endif

    @if ($this->openRequests->isNotEmpty())
        <x-panel title="Permintaan yang sedang diproses">
            <ul class="space-y-2 text-sm">
                @foreach ($this->openRequests as $req)
                    <li class="flex items-center gap-2"><x-lucide-message-square class="size-4 text-primary" /> {{ $req->typeLabel() }} · {{ $req->created_at->translatedFormat('d M Y') }}</li>
                @endforeach
            </ul>
        </x-panel>
    @endif
</div>
