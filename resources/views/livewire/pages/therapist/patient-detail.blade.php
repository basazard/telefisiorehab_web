<?php

use App\Models\Patient;
use App\Models\PatientNote;
use App\Support\Onboarding\BodyRegions;
use App\Support\Onboarding\Intervention;
use App\Support\Onboarding\Monitoring;
use App\Support\Onboarding\RedFlags;
use App\Support\Onboarding\ScreeningRules;
use App\Support\Staff\Audit;
use App\Support\Staff\Progress;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

// Patient detail for staff (PRD 09 S2): summary, progress, history and staff notes.
new class extends Component {
    private const LOG_DAYS = 63;          // enough for 8 weeks of adherence and the pain chart
    private const LOG_LIST_DAYS = 30;

    #[Locked]
    public int $patientId;

    public string $note = '';

    public function mount(int $patient): void
    {
        $this->patientId = $patient;

        Gate::authorize('view', $this->patient);

        Audit::record(auth()->user(), $this->patient, Audit::VIEWED_DETAIL);
    }

    /** Loaded without the clinic scope so a refusal is a 403 from the policy, not a 404. */
    #[Computed]
    public function patient(): Patient
    {
        return Patient::query()->withoutGlobalScope('clinic')->with([
            'user:id,name,email,phone',
            'clinic:id,name',
            'therapist:id,name',
            'latestRiskAssessment.screening',
            'consultationRequests' => fn ($q) => $q->where('status', 'open')->latest(),
            'programs' => fn ($q) => $q->latest('id')->with('items.exercise'),
            'screenings' => fn ($q) => $q->latest('id')->with('assessment', 'redFlagCheck.unlockedBy:id,name'),
            'evaluations' => fn ($q) => $q->latest('id'),
            'exerciseLogs' => fn ($q) => $q->where('performed_at', '>=', today()->subDays(self::LOG_DAYS))
                ->latest('performed_at')->with('programExercise.exercise:id,name'),
            'staffNotes' => fn ($q) => $q->latest('id')->with('author:id,name'),
        ])->findOrFail($this->patientId);
    }

    public function addNote(): void
    {
        Gate::authorize('addNote', $this->patient);

        $this->validate(
            ['note' => ['required', 'string', 'min:10', 'max:2000']],
            ['note.required' => 'Tulis catatan terlebih dahulu.', 'note.min' => 'Catatan minimal 10 karakter.'],
        );

        PatientNote::query()->create([
            'patient_id' => $this->patient->id,
            'clinic_id' => $this->patient->clinic_id,
            'user_id' => auth()->id(),
            'body' => trim($this->note),
        ]);
        Audit::record(auth()->user(), $this->patient, Audit::NOTE_ADDED);

        $this->reset('note');
        unset($this->patient);
        $this->dispatch('toast', message: 'Catatan disimpan', type: 'success');
    }

    public function with(): array
    {
        $patient = $this->patient;
        $program = $patient->programs->first();
        $logs = $patient->exerciseLogs;

        $programLogs = $program ? $logs->filter(fn ($l) => $l->programExercise?->program_id === $program->id) : collect();
        $weeks = $program
            ? Progress::weeklyAdherence($programLogs->pluck('performed_at')->all(), $program->starts_on ?? $program->created_at, $program->ended_at ?? now())
            : [];
        $pain = Progress::painTrend(
            $logs->map(fn ($l) => ['date' => $l->performed_at, 'pain' => $l->pain_score])->all(),
            $patient->evaluations->map(fn ($e) => ['date' => $e->created_at, 'pain' => $e->pain])->all(),
        );

        $screening = $patient->latestRiskAssessment?->screening;

        return [
            'p' => $patient,
            'program' => $program,
            'assessment' => $patient->latestRiskAssessment,
            'locked' => $patient->screenings->contains(fn ($s) => $s->redFlagCheck?->stopped_at && ! $s->redFlagCheck->unlocked_at),
            'complaints' => collect($screening?->main_complaints['keys'] ?? [])->map(fn ($k) => ScreeningRules::COMPLAINTS[$k] ?? $k)
                ->when(filled($screening?->main_complaints['other'] ?? null), fn ($c) => $c->push($screening->main_complaints['other'])),
            'regions' => collect($screening?->regions ?? [])->map(fn ($r) => BodyRegions::label($r)),
            'weeks' => $weeks,
            'pain' => $pain,
            'recentLogs' => $logs->filter(fn ($l) => $l->performed_at->gte(today()->subDays(self::LOG_LIST_DAYS - 1))),
            'adherenceChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => array_map(fn ($w) => 'Minggu '.$w['week'], $weeks),
                    'datasets' => [['label' => 'Kepatuhan (%)', 'data' => array_column($weeks, 'percent'), 'backgroundColor' => '--primary', 'borderRadius' => 6]],
                ],
                'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['min' => 0, 'max' => 100]], 'plugins' => ['legend' => ['display' => false]]],
            ],
            'painChart' => [
                'type' => 'line',
                'data' => [
                    'labels' => array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M'), $pain['dates']),
                    'datasets' => [
                        ['label' => 'Nyeri saat latihan (rata-rata harian)', 'data' => $pain['logs'], 'borderColor' => '--primary', 'backgroundColor' => '--primary', 'spanGaps' => true, 'tension' => 0.3],
                        ['label' => 'Nyeri saat evaluasi', 'data' => $pain['evaluations'], 'borderColor' => '--destructive', 'backgroundColor' => '--destructive', 'showLine' => false, 'pointRadius' => 5],
                    ],
                ],
                'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['min' => 0, 'max' => 10]]],
            ],
        ];
    }
}; ?>

<div class="space-y-6">
    <a href="{{ route('therapist.patients') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
        <x-lucide-arrow-left class="size-4" /> Daftar pasien
    </a>

    {{-- Header --}}
    <x-panel>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="font-display text-2xl font-bold">{{ $p->user->name }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ $p->age !== null ? $p->age.' th' : 'Usia -' }}
                    · {{ $p->gender?->label() ?? 'Jenis kelamin -' }}
                    · {{ $p->module?->label() ?? 'Modul -' }}
                    · {{ $p->clinic?->name ?? 'Tanpa klinik' }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Fisioterapis: {{ $p->therapist?->name ?? 'Belum ditugaskan' }}
                    · {{ $p->user->email }}@if ($p->user->phone) · {{ $p->user->phone }}@endif
                </p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @if ($locked) <x-ui.badge variant="destructive">Red flag: program dihentikan</x-ui.badge> @endif
                @if ($p->consultationRequests->isNotEmpty()) <x-ui.badge variant="outline">Konsultasi terbuka</x-ui.badge> @endif
                @if ($program?->status === \App\Enums\ProgramStatus::NeedsReview) <x-ui.badge variant="outline">Program perlu ditinjau</x-ui.badge> @endif
            </div>
        </div>
    </x-panel>

    <x-ui.tabs default="ringkasan">
        <x-ui.tabs.list class="flex-wrap h-auto">
            <x-ui.tabs.trigger value="ringkasan">Ringkasan</x-ui.tabs.trigger>
            <x-ui.tabs.trigger value="progres">Progres</x-ui.tabs.trigger>
            <x-ui.tabs.trigger value="program">Program</x-ui.tabs.trigger>
            <x-ui.tabs.trigger value="riwayat">Riwayat</x-ui.tabs.trigger>
            <x-ui.tabs.trigger value="catatan">Catatan ({{ $p->staffNotes->count() }})</x-ui.tabs.trigger>
        </x-ui.tabs.list>

        {{-- Ringkasan --}}
        <x-ui.tabs.content value="ringkasan" class="space-y-4">
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                <x-stat label="Kategori risiko" :value="$assessment?->tier?->label() ?? '-'" :sub="$assessment?->stratified_at?->translatedFormat('d M Y')" />
                <x-stat label="Jalur program" :value="$program ? Intervention::pathwayLabel($program->pathway) : '-'" :sub="$program?->status->label()" />
                <x-stat label="Domain" :value="$assessment?->domain?->label() ?? '-'" />
                <x-stat label="Evaluasi berikutnya" :value="$program?->next_evaluation_on?->translatedFormat('d M Y') ?? '-'" />
            </div>

            <x-panel title="Keluhan">
                @if ($complaints->isEmpty() && $regions->isEmpty())
                    <p class="text-sm text-muted-foreground">Belum ada screening yang selesai.</p>
                @else
                    <p class="text-sm">{{ $complaints->join(', ') ?: '-' }}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($regions as $region)
                            <x-ui.badge variant="secondary">{{ $region }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif
            </x-panel>

            <div class="grid gap-4 md:grid-cols-2">
                <x-panel title="Tujuan">
                    <p class="text-sm">
                        {{ collect($p->goals ?? [])->map->label()->when($p->goal_other, fn ($g) => $g->push($p->goal_other))->join(', ') ?: '-' }}
                    </p>
                </x-panel>
                <x-panel title="Kondisi khusus">
                    <p class="text-sm">
                        {{ collect($p->special_conditions ?? [])->map->label()->when($p->special_condition_other, fn ($c) => $c->push($p->special_condition_other))->join(', ') ?: 'Tidak ada' }}
                    </p>
                </x-panel>
            </div>

            @foreach ($p->consultationRequests as $req)
                <x-panel title="Permintaan konsultasi terbuka" :sub="$req->typeLabel().' · '.$req->created_at->translatedFormat('d M Y')">
                    <p class="text-sm">{{ $req->reason ?? 'Tanpa keterangan dari pasien.' }}</p>
                    <x-ui.button size="sm" variant="outline" class="mt-3" :href="route('staff.consultations')" wire:navigate>Buka daftar konsultasi</x-ui.button>
                </x-panel>
            @endforeach
        </x-ui.tabs.content>

        {{-- Progres --}}
        <x-ui.tabs.content value="progres" class="space-y-4">
            @if (! $program)
                <x-panel><p class="text-sm text-muted-foreground">Pasien belum memiliki program latihan.</p></x-panel>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-panel title="Kepatuhan per minggu" :sub="$program->title">
                        @if ($weeks === [])
                            <p class="text-sm text-muted-foreground">Program baru dimulai.</p>
                        @else
                            <div class="h-56" wire:ignore x-data="chart(@js($adherenceChart))"><canvas x-ref="canvas" aria-label="Grafik kepatuhan per minggu"></canvas></div>
                        @endif
                    </x-panel>
                    <x-panel title="Tren nyeri" sub="8 minggu terakhir, skala 0–10">
                        @if ($pain['dates'] === [])
                            <p class="text-sm text-muted-foreground">Belum ada data nyeri.</p>
                        @else
                            <div class="h-56" wire:ignore x-data="chart(@js($painChart))"><canvas x-ref="canvas" aria-label="Grafik tren nyeri"></canvas></div>
                        @endif
                    </x-panel>
                </div>
            @endif

            <x-panel title="Riwayat evaluasi">
                @forelse ($p->evaluations as $evaluation)
                    <div wire:key="ev-{{ $evaluation->id }}" class="border-b border-border py-3 last:border-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium">{{ $evaluation->created_at->translatedFormat('d M Y') }} · {{ $evaluation->outcomeLabel() }}</p>
                            <x-ui.badge :variant="$evaluation->global_change === 'worse' ? 'destructive' : 'outline'">
                                {{ \App\Models\ProgramEvaluation::GLOBAL_CHANGE[$evaluation->global_change] ?? $evaluation->global_change }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Nyeri {{ $evaluation->pain }}/10 · kepatuhan {{ $evaluation->adherence }}%</p>
                        @if ($evaluation->comparison)
                            <ul class="mt-2 space-y-0.5 text-xs">
                                @foreach ($evaluation->comparison as $item)
                                    <li>{{ $item['label'] }}: {{ $item['before'] }} → {{ $item['after'] }}
                                        @if ($item['significant'] ?? false) <span class="{{ $item['direction'] === 'worse' ? 'text-destructive' : 'text-accent' }}">({{ $item['direction'] === 'worse' ? 'memburuk' : 'membaik' }})</span> @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">Belum ada evaluasi.</p>
                @endforelse
            </x-panel>

            <x-panel title="Log latihan 30 hari terakhir">
                @if ($recentLogs->isEmpty())
                    <p class="text-sm text-muted-foreground">Belum ada latihan tercatat dalam 30 hari terakhir.</p>
                @else
                    <x-ui.table>
                        <x-ui.table.header>
                            <x-ui.table.row>
                                <x-ui.table.head>Tanggal</x-ui.table.head>
                                <x-ui.table.head>Latihan</x-ui.table.head>
                                <x-ui.table.head>Set × rep</x-ui.table.head>
                                <x-ui.table.head>Nyeri</x-ui.table.head>
                                <x-ui.table.head>Usaha</x-ui.table.head>
                                <x-ui.table.head>Catatan</x-ui.table.head>
                            </x-ui.table.row>
                        </x-ui.table.header>
                        <x-ui.table.body>
                            @foreach ($recentLogs as $log)
                                <x-ui.table.row wire:key="log-{{ $log->id }}">
                                    <x-ui.table.cell>{{ $log->performed_at->translatedFormat('d M, H:i') }}</x-ui.table.cell>
                                    <x-ui.table.cell>{{ $log->programExercise?->exercise?->name ?? '-' }}</x-ui.table.cell>
                                    <x-ui.table.cell>{{ $log->sets_done }} × {{ $log->reps_done }}</x-ui.table.cell>
                                    <x-ui.table.cell>{{ $log->pain_score ?? '-' }}</x-ui.table.cell>
                                    <x-ui.table.cell>{{ $log->effort ?? '-' }}</x-ui.table.cell>
                                    <x-ui.table.cell class="text-xs text-muted-foreground">{{ $log->notes ?? '' }}</x-ui.table.cell>
                                </x-ui.table.row>
                            @endforeach
                        </x-ui.table.body>
                    </x-ui.table>
                @endif
            </x-panel>
        </x-ui.tabs.content>

        {{-- Program (review, adjust, create) --}}
        <x-ui.tabs.content value="program">
            <livewire:staff.program-panel :patient-id="$p->id" />
        </x-ui.tabs.content>

        {{-- Riwayat --}}
        <x-ui.tabs.content value="riwayat" class="space-y-4">
            <x-panel title="Screening dan assessment">
                @forelse ($p->screenings as $s)
                    <div wire:key="sc-{{ $s->id }}" class="border-b border-border py-3 last:border-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium">Screening {{ $s->created_at->translatedFormat('d M Y') }}</p>
                            <span class="text-xs text-muted-foreground">{{ ['completed' => 'Selesai', 'stopped' => 'Dihentikan (red flag)', 'in_progress' => 'Belum selesai'][$s->status] ?? $s->status }}</span>
                        </div>
                        @if ($s->assessment)
                            <p class="mt-1 text-xs text-muted-foreground">
                                Assessment {{ $s->assessment->completed_at?->translatedFormat('d M Y') ?? 'belum selesai' }}
                                · {{ $s->assessment->domain?->label() ?? '-' }}
                                · {{ $s->assessment->tier?->label() ?? 'belum diklasifikasi' }}
                            </p>
                        @endif
                        @if ($s->redFlagCheck?->stopped_at)
                            <div class="mt-2 rounded-lg border border-destructive/40 p-3 text-xs">
                                <p class="font-medium text-destructive">Red flag: {{ collect($s->redFlagCheck->flagged)->map(fn ($k) => rtrim(RedFlags::ITEMS[$k] ?? $k, '?'))->join('; ') }}</p>
                                @if ($s->redFlagCheck->unlocked_at)
                                    <p class="mt-1">Dibuka {{ $s->redFlagCheck->unlocked_at->translatedFormat('d M Y') }} oleh {{ $s->redFlagCheck->unlockedBy?->name ?? '-' }}: {{ $s->redFlagCheck->unlock_note }}</p>
                                @else
                                    <p class="mt-1">Belum dibuka. Program pasien tetap dihentikan.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">Belum ada screening.</p>
                @endforelse
            </x-panel>
        </x-ui.tabs.content>

        {{-- Catatan --}}
        <x-ui.tabs.content value="catatan" class="space-y-4">
            <x-panel title="Catatan fisioterapis" sub="Hanya terlihat oleh staf klinik, tidak pernah oleh pasien. Catatan tidak bisa diubah atau dihapus.">
                <form wire:submit="addNote" class="space-y-3">
                    <x-ui.textarea label="Catatan baru" rows="3" maxlength="2000" placeholder="mis. Pasien dihubungi via telepon, latihan lutut dikurangi karena nyeri." wire:model="note" />
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addNote"><x-lucide-save class="size-4" /> Simpan catatan</x-ui.button>
                </form>

                <div class="mt-5">
                    @forelse ($p->staffNotes as $n)
                        <div wire:key="note-{{ $n->id }}" class="border-t border-border py-3">
                            <p class="text-xs text-muted-foreground">{{ $n->author?->name ?? 'Staf' }} · {{ $n->created_at->translatedFormat('d M Y, H:i') }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm">{{ $n->body }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">Belum ada catatan.</p>
                    @endforelse
                </div>
            </x-panel>
        </x-ui.tabs.content>
    </x-ui.tabs>
</div>
