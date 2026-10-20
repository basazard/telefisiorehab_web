<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// Log latihan, evaluations and assessments of the signed-in patient (always through the patient's relations).
new class extends Component {
    #[Computed]
    public function assessments()
    {
        return auth()->user()->patient->assessments()->where('status', 'completed')->latest('completed_at')->take(10)->get();
    }

    #[Computed]
    public function evaluations()
    {
        return auth()->user()->patient->evaluations()->latest()->take(10)->get();
    }

    #[Computed]
    public function logs()
    {
        return auth()->user()->patient->exerciseLogs()->with('programExercise.exercise')->latest('performed_at')->take(30)->get();
    }
}; ?>

<div class="space-y-6">
    <x-page-header title="Riwayat" subtitle="Assessment, evaluasi, dan log latihan Anda." />

    <x-panel title="Evaluasi program">
        @forelse ($this->evaluations as $e)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 text-sm last:border-0">
                <span>{{ $e->created_at->translatedFormat('d M Y') }} · nyeri {{ $e->pain }}/10 · fungsi {{ $e->function }}/10 · kepatuhan {{ $e->adherence }}%</span>
                <x-ui.badge :variant="$e->improved ? 'secondary' : 'outline'">{{ $e->improved ? 'Ada perbaikan' : 'Belum ada perbaikan' }}</x-ui.badge>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Belum ada evaluasi.</p>
        @endforelse
    </x-panel>

    <x-panel title="Log latihan" sub="30 catatan terakhir">
        @forelse ($this->logs as $log)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 text-sm last:border-0">
                <span>{{ $log->programExercise?->exercise?->name ?? 'Latihan' }}</span>
                <span class="text-muted-foreground">{{ $log->performed_at->translatedFormat('d M Y, H:i') }}@if ($log->pain_score !== null) · nyeri {{ $log->pain_score }}/10 @endif</span>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Belum ada latihan yang dicatat.</p>
        @endforelse
    </x-panel>

    <x-panel title="Assessment">
        @forelse ($this->assessments as $a)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 text-sm last:border-0">
                <span>{{ $a->completed_at->translatedFormat('d M Y') }} · {{ $a->domain->label() }}</span>
                @if ($a->tier) <x-ui.badge :variant="$a->tier->badge()">{{ $a->tier->label() }}</x-ui.badge> @endif
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Belum ada assessment.</p>
        @endforelse
    </x-panel>
</div>
