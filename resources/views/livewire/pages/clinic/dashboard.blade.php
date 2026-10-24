<?php

use App\Models\Clinic;
use App\Models\User;
use App\Support\Staff\Assignment;
use App\Support\Staff\ClinicStats;
use App\Support\Staff\PatientOverview;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

// Clinic dashboard for the clinic admin (PRD 09 S4.1): numbers, risk tiers, therapist workload, unassigned patients.
new class extends Component {
    /** @var array<int, string> chosen therapist id per unassigned patient id */
    public array $assignTo = [];

    public function mount(): void
    {
        Gate::authorize('manage', $this->clinic);
    }

    #[Computed]
    public function clinic(): Clinic
    {
        return auth()->user()->clinic ?? abort(403);
    }

    #[Computed]
    public function rows()
    {
        return PatientOverview::rows(PatientOverview::visibleTo(auth()->user()), auth()->user());
    }

    #[Computed]
    public function therapists()
    {
        return Assignment::therapists($this->clinic->id);
    }

    public function assign(int $patientId): void
    {
        Gate::authorize('manage', $this->clinic);

        $therapist = User::find((int) ($this->assignTo[$patientId] ?? 0));
        if (! $therapist) {
            $this->addError("assignTo.$patientId", 'Pilih fisioterapis.');

            return;
        }

        Assignment::assign(auth()->user(), Assignment::find($patientId), $therapist);

        unset($this->assignTo[$patientId], $this->rows);
        $this->dispatch('toast', message: 'Pasien ditugaskan ke '.$therapist->name, type: 'success');
    }

    public function with(): array
    {
        $rows = $this->rows;
        $tiers = ClinicStats::tiers($rows);

        return [
            'stats' => ClinicStats::summary($rows),
            'workload' => ClinicStats::workload($rows, $this->therapists),
            'unassigned' => $rows->reject(fn ($r) => $r['assigned'])->sortBy('name')->values(),
            'tierChart' => [
                'type' => 'doughnut',
                'data' => [
                    'labels' => ['Risiko Rendah', 'Risiko Sedang', 'Risiko Tinggi', 'Belum diklasifikasi'],
                    'datasets' => [[
                        'data' => [$tiers['low'], $tiers['moderate'], $tiers['high'], $tiers['none']],
                        'backgroundColor' => ['--accent', '--primary', '--destructive', '--border'],
                    ]],
                ],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]],
            ],
            'hasTiers' => array_sum($tiers) > 0,
        ];
    }
}; ?>

<div class="space-y-6">
    <x-page-header :title="$this->clinic->name" subtitle="Ringkasan pasien, program dan beban kerja fisioterapis klinik Anda." />

    <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
        <x-stat label="Pasien aktif" :value="$stats['active']" sub="dengan program berjalan" />
        <x-stat label="Pasien baru" :value="$stats['new']" sub="30 hari terakhir" />
        <x-stat label="Rata-rata kepatuhan" :value="$stats['adherence'] !== null ? $stats['adherence'].'%' : '-'" sub="7 hari terakhir" />
        <x-stat label="Program perlu ditinjau" :value="$stats['review']" sub="dijeda atau belum ditinjau" />
        <x-stat label="Konsultasi terbuka" :value="$stats['consultations']" />
        <x-stat label="Red flag aktif" :value="$stats['locked']" sub="program dihentikan" />
        <x-stat label="Evaluasi terlambat" :value="$stats['overdue']" />
        <x-stat label="Belum ditugaskan" :value="$unassigned->count()" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-panel title="Pasien per kategori risiko">
            @if ($hasTiers)
                <div class="h-64" wire:ignore x-data="chart(@js($tierChart))"><canvas x-ref="canvas" aria-label="Grafik pasien per kategori risiko"></canvas></div>
            @else
                <p class="text-sm text-muted-foreground">Belum ada pasien.</p>
            @endif
        </x-panel>

        <x-panel title="Beban kerja fisioterapis" class="lg:col-span-2">
            @if ($workload === [])
                <p class="text-sm text-muted-foreground">Belum ada fisioterapis aktif. Undang lewat halaman Tim.</p>
            @else
                <x-ui.table>
                    <x-ui.table.header>
                        <x-ui.table.row>
                            <x-ui.table.head>Fisioterapis</x-ui.table.head>
                            <x-ui.table.head>Pasien</x-ui.table.head>
                            <x-ui.table.head>Perlu perhatian</x-ui.table.head>
                            <x-ui.table.head>Rata-rata kepatuhan</x-ui.table.head>
                        </x-ui.table.row>
                    </x-ui.table.header>
                    <x-ui.table.body>
                        @foreach ($workload as $w)
                            <x-ui.table.row wire:key="w-{{ $w['id'] }}">
                                <x-ui.table.cell class="font-medium">{{ $w['name'] }}</x-ui.table.cell>
                                <x-ui.table.cell>{{ $w['patients'] }}</x-ui.table.cell>
                                <x-ui.table.cell>{{ $w['attention'] }}</x-ui.table.cell>
                                <x-ui.table.cell>{{ $w['adherence'] !== null ? $w['adherence'].'%' : '-' }}</x-ui.table.cell>
                            </x-ui.table.row>
                        @endforeach
                    </x-ui.table.body>
                </x-ui.table>
            @endif
        </x-panel>
    </div>

    <x-panel title="Pasien belum ditugaskan" sub="Tugaskan ke fisioterapis klinik.">
        @forelse ($unassigned as $row)
            <div wire:key="u-{{ $row['id'] }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 last:border-0">
                <div>
                    <a href="{{ route('therapist.patients.show', $row['id']) }}" wire:navigate class="font-medium hover:underline">{{ $row['name'] }}</a>
                    <p class="text-xs text-muted-foreground">
                        {{ $row['age'] !== null ? $row['age'].' th' : 'Usia -' }}
                        · {{ $row['tier']?->label() ?? 'Belum diklasifikasi' }}
                        · terdaftar {{ $row['joined_at']?->translatedFormat('d M Y') }}
                    </p>
                </div>
                <div class="flex items-start gap-2">
                    <x-ui.select wire:model="assignTo.{{ $row['id'] }}" aria-label="Pilih fisioterapis" class="min-w-40">
                        <option value="">Pilih fisioterapis</option>
                        @foreach ($this->therapists as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.button size="sm" wire:click="assign({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="assign({{ $row['id'] }})">Tugaskan</x-ui.button>
                </div>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Semua pasien sudah ditugaskan.</p>
        @endforelse
    </x-panel>
</div>
