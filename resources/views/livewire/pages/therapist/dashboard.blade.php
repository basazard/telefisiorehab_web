<?php

use App\Enums\Role;
use App\Models\Patient;
use App\Support\Staff\Attention;
use App\Support\Staff\PatientOverview;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// Staff home (PRD 09 S1): who needs attention. Therapists see their own patients, clinic admins the whole clinic.
new class extends Component {
    public function mount(): void
    {
        Gate::authorize('viewAny', Patient::class);
    }

    /** Patients whose detail the viewer may see (a therapist's own, every clinic patient for an admin). */
    #[Computed]
    public function rows()
    {
        return PatientOverview::rows(PatientOverview::visibleTo(auth()->user()), auth()->user())
            ->reject(fn (array $row) => $row['limited'])
            ->values();
    }

    #[Computed]
    public function attention()
    {
        return $this->rows
            ->filter(fn (array $row) => $row['reasons'] !== [])
            ->sortBy([
                fn (array $a, array $b) => array_search($a['reasons'][0], array_keys(Attention::LABELS)) <=> array_search($b['reasons'][0], array_keys(Attention::LABELS)),
                fn (array $a, array $b) => count($b['reasons']) <=> count($a['reasons']),
            ])
            ->values();
    }

    #[Computed]
    public function unassigned(): int
    {
        return PatientOverview::visibleTo(auth()->user())->whereNull('therapist_id')->count();
    }

    public function with(): array
    {
        $rows = $this->rows;
        $weekEnd = today()->addDays(7);

        return [
            'isAdmin' => auth()->user()->role === Role::ClinicAdmin,
            'patients' => $rows->count(),
            'needAttention' => $this->attention->count(),
            'openConsultations' => $rows->filter(fn ($r) => in_array(Attention::OPEN_CONSULTATION, $r['reasons'], true))->count(),
            'evaluationsDue' => $rows->filter(fn ($r) => $r['next_evaluation'] && $r['next_evaluation']->lte($weekEnd))->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-page-header :title="'Halo, '.auth()->user()->name"
                   :subtitle="$isAdmin ? 'Ringkasan pasien klinik Anda dan siapa yang perlu perhatian.' : 'Ringkasan pasien Anda dan siapa yang perlu perhatian hari ini.'" />

    <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
        <x-stat :label="$isAdmin ? 'Pasien klinik' : 'Pasien saya'" :value="$patients"
                :sub="$this->unassigned ? $this->unassigned.' belum ditugaskan' : null" />
        <x-stat label="Perlu perhatian" :value="$needAttention" />
        <x-stat label="Konsultasi terbuka" :value="$openConsultations" />
        <x-stat label="Evaluasi minggu ini" :value="$evaluationsDue" sub="termasuk yang terlambat" />
    </div>

    <x-panel title="Perlu perhatian" sub="10 pasien teratas, yang paling mendesak lebih dulu">
        @forelse ($this->attention->take(10) as $row)
            <div wire:key="att-{{ $row['id'] }}" class="flex flex-wrap items-start justify-between gap-3 border-b border-border py-3 last:border-0">
                <div>
                    <a href="{{ route('therapist.patients.show', $row['id']) }}" wire:navigate class="font-medium hover:underline">{{ $row['name'] }}</a>
                    <p class="text-xs text-muted-foreground">
                        {{ $row['age'] !== null ? $row['age'].' th' : 'Usia -' }}
                        @if ($row['tier']) · {{ $row['tier']->label() }} @endif
                        @if ($isAdmin) · {{ $row['therapist'] ?? 'Belum ditugaskan' }} @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($row['reasons'] as $reason)
                        <x-ui.badge :variant="in_array($reason, ['red_flag_locked', 'worsening'], true) ? 'destructive' : 'outline'">{{ Attention::label($reason) }}</x-ui.badge>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Tidak ada pasien yang perlu perhatian saat ini.</p>
        @endforelse

        <div class="mt-4">
            <x-ui.button variant="outline" size="sm" :href="route('therapist.patients')" wire:navigate>
                <x-lucide-users class="size-4" /> Lihat semua pasien
            </x-ui.button>
        </div>
    </x-panel>
</div>
