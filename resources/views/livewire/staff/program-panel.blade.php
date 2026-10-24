<?php

use App\Enums\ExerciseType;
use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Models\Patient;
use App\Models\Program;
use App\Support\Onboarding\Intervention;
use App\Support\Staff\ProgramActions;
use App\Support\Staff\ProgramRules;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

// "Program" tab of the staff patient detail (PRD 09 S3): review, adjust, resume, end, goal achieved, create.
new class extends Component {
    #[Locked]
    public int $patientId;

    /** '' | adjust | create */
    public string $mode = '';

    public string $approveNote = '';

    // adjust
    public int $level = 1;
    /** @var array<int, array<string, int|string|null>> item id => dose */
    public array $doses = [];
    /** @var array<int, bool> item id => remove */
    public array $remove = [];
    /** @var int[] exercise ids to add */
    public array $add = [];
    public string $note = '';

    // needs review / end
    public string $resumeNote = '';
    public bool $lighter = false;
    public string $endReason = '';

    // create
    public string $title = 'Program latihan terbimbing';
    /** @var array<int, array<string, int|string|null>> exercise id => dose */
    public array $picked = [];
    public string $createNote = '';

    // library filter (adjust and create)
    public string $area = '';
    public string $type = '';

    public function mount(int $patientId): void
    {
        $this->patientId = $patientId;
        Gate::authorize('view', $this->patient);
    }

    #[Computed]
    public function patient(): Patient
    {
        return Patient::query()->withoutGlobalScopes()->findOrFail($this->patientId);
    }

    #[Computed]
    public function program(): ?Program
    {
        return Program::query()->withoutGlobalScopes()->where('patient_id', $this->patientId)->current()->latest('id')
            ->with(['items.exercise', 'reviewer:id,name'])->first();
    }

    #[Computed]
    public function history()
    {
        return Program::query()->withoutGlobalScopes()->where('patient_id', $this->patientId)->latest('id')
            ->with('allItems.exercise')->get();
    }

    #[Computed]
    public function library()
    {
        return ProgramActions::library($this->patient)
            ->when($this->area !== '', fn ($q) => $q->where('body_area', $this->area))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->get();
    }

    #[Computed]
    public function areas(): array
    {
        return ProgramActions::library($this->patient)->reorder()->distinct()->orderBy('body_area')->pluck('body_area')->filter()->values()->all();
    }

    #[Computed]
    public function createRefusal(): ?string
    {
        return ProgramRules::createRefusal(
            $this->patient->isRedFlagLocked(),
            $this->patient->latestRiskAssessment()->first()?->tier,
            $this->program !== null,
        );
    }

    // ---------------------------------------------------------------- actions

    public function approve(): void
    {
        Gate::authorize('update', $this->program ?? abort(404));
        ProgramActions::approve(auth()->user(), $this->program, $this->approveNote);
        $this->done('Program disetujui');
    }

    public function startAdjust(): void
    {
        Gate::authorize('update', $this->program ?? abort(404));

        $this->level = (int) $this->program->intensity_level;
        $this->doses = $this->program->items->mapWithKeys(fn ($i) => [$i->id => $i->only(['sets', 'reps', 'hold_seconds', 'minutes'])])->all();
        $this->remove = [];
        $this->add = [];
        $this->note = '';
        $this->mode = 'adjust';
    }

    public function toggleAdd(int $exerciseId): void
    {
        $this->add = in_array($exerciseId, array_map('intval', $this->add), true)
            ? array_values(array_diff(array_map('intval', $this->add), [$exerciseId]))
            : [...array_map('intval', $this->add), $exerciseId];
    }

    public function saveAdjust(): void
    {
        Gate::authorize('update', $this->program ?? abort(404));

        ProgramActions::adjust(auth()->user(), $this->program, $this->note, $this->level,
            $this->doses, array_keys(array_filter($this->remove)), array_map('intval', $this->add));

        $this->done('Program disesuaikan');
    }

    public function resume(): void
    {
        Gate::authorize('update', $this->program ?? abort(404));
        ProgramActions::resume(auth()->user(), $this->program, $this->resumeNote, $this->lighter);
        $this->done('Program dilanjutkan');
    }

    public function endProgram(): void
    {
        Gate::authorize('end', $this->program ?? abort(404));
        ProgramActions::end(auth()->user(), $this->program, $this->endReason);
        $this->done('Program diakhiri. Pasien diminta melakukan reassessment.');
    }

    public function goalAchieved(): void
    {
        Gate::authorize('end', $this->program ?? abort(404));
        ProgramActions::goalAchieved(auth()->user(), $this->program);
        $this->done('Tujuan tercapai. Program pemeliharaan dibuat.');
    }

    public function startCreate(): void
    {
        Gate::authorize('create', [Program::class, $this->patient]);
        $this->picked = [];
        $this->mode = 'create';
    }

    public function pick(int $exerciseId): void
    {
        $exercise = $this->library->firstWhere('id', $exerciseId) ?? ProgramActions::library($this->patient)->find($exerciseId);
        abort_unless($exercise, 404);

        if (isset($this->picked[$exerciseId])) {
            unset($this->picked[$exerciseId]);

            return;
        }

        $this->picked[$exerciseId] = array_intersect_key(
            Intervention::dose(RiskTier::Moderate, $exercise->dose_unit, $exercise->type->value, 1),
            array_flip(ProgramRules::fields($exercise->dose_unit)),
        );
    }

    public function saveCreate(): void
    {
        Gate::authorize('create', [Program::class, $this->patient]);
        ProgramActions::create(auth()->user(), $this->patient, $this->title, $this->picked, $this->createNote);
        $this->done('Program dibuat');
    }

    public function cancel(): void
    {
        $this->mode = '';
        $this->resetValidation();
    }

    private function done(string $message): void
    {
        $this->reset('mode', 'approveNote', 'note', 'resumeNote', 'lighter', 'endReason', 'picked', 'createNote', 'add', 'remove');
        unset($this->program, $this->history, $this->createRefusal);
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function with(): array
    {
        return ['types' => ExerciseType::cases()];
    }
}; ?>

<div class="space-y-4">
    @php $program = $this->program; @endphp

    @error('status') <x-ui.alert variant="destructive">{{ $message }}</x-ui.alert> @enderror

    @if ($program)
        <x-panel :title="$program->title" :sub="Intervention::pathwayLabel($program->pathway).' · level '.$program->intensity_level.' dari '.Intervention::MAX_LEVEL.' · '.$program->status->label()">
            <div class="flex flex-wrap gap-1.5">
                @if ($program->review_type === Program::REVIEW_APPROVED) <x-ui.badge variant="secondary">Disetujui fisioterapis</x-ui.badge>
                @elseif ($program->review_type === Program::REVIEW_ADJUSTED) <x-ui.badge variant="secondary">Disesuaikan fisioterapis</x-ui.badge>
                @else <x-ui.badge variant="outline">Belum ditinjau</x-ui.badge> @endif
                @if ($program->next_evaluation_on) <x-ui.badge variant="outline">Evaluasi {{ $program->next_evaluation_on->translatedFormat('d M Y') }}</x-ui.badge> @endif
            </div>
            @if ($program->review_note)
                <p class="mt-2 text-sm text-muted-foreground">“{{ $program->review_note }}” · {{ $program->reviewer?->name }}, {{ $program->reviewed_at?->translatedFormat('d M Y') }}</p>
            @endif

            @if ($mode !== 'adjust')
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($program->items as $item)
                        <li wire:key="it-{{ $item->id }}" class="flex flex-wrap justify-between gap-2 py-2 text-sm">
                            <span>{{ $item->exercise->name }}</span>
                            <span class="text-muted-foreground">{{ $item->doseLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- active program: approve, adjust, goal achieved, end --}}
            @if ($program->status === ProgramStatus::Active && $mode === '')
                <div class="mt-5 space-y-3">
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-60 flex-1"><x-ui.input label="Catatan persetujuan (opsional)" wire:model="approveNote" maxlength="500" /></div>
                        <x-ui.button wire:click="approve" wire:loading.attr="disabled"><x-lucide-check class="size-4" /> Setujui program</x-ui.button>
                        <x-ui.button variant="outline" wire:click="startAdjust"><x-lucide-sliders-horizontal class="size-4" /> Sesuaikan</x-ui.button>
                        @unless ($program->isMaintenance())
                            <x-ui.button variant="outline" wire:click="goalAchieved" wire:confirm="Tandai tujuan fisioterapi tercapai? Program diakhiri dan program pemeliharaan dibuat.">
                                <x-lucide-flag class="size-4" /> Tandai tujuan tercapai
                            </x-ui.button>
                        @endunless
                    </div>
                    @error('goal') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- adjust --}}
            @if ($mode === 'adjust')
                <div class="mt-4 space-y-4">
                    <x-ui.select label="Level intensitas" wire:model="level" class="max-w-40">
                        @foreach (range(1, Intervention::MAX_LEVEL) as $l) <option value="{{ $l }}">Level {{ $l }}</option> @endforeach
                    </x-ui.select>
                    <p class="text-xs text-muted-foreground">Mengubah level menghitung ulang dosis standar, lalu dosis yang Anda ubah di bawah dipakai.</p>

                    <div class="divide-y divide-border">
                        @foreach ($program->items as $item)
                            @php $limits = ProgramRules::limits($item->exercise->dose_unit, $item->exercise->type->value); @endphp
                            <div wire:key="adj-{{ $item->id }}" class="py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-medium">{{ $item->exercise->name }}</p>
                                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="remove.{{ $item->id }}"> Hapus dari program</label>
                                </div>
                                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    @foreach ($limits as $field => [$min, $max])
                                        <x-ui.input type="number" :min="$min" :max="$max" :label="ProgramRules::FIELD_LABELS[$field].' ('.$min.'–'.$max.')'" wire:model="doses.{{ $item->id }}.{{ $field }}" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('remove') <p class="text-xs text-destructive">{{ $message }}</p> @enderror

                    @include('staff.library-picker', ['action' => 'toggleAdd', 'selected' => $add])

                    <x-ui.textarea label="Catatan penyesuaian (wajib, minimal 10 karakter)" rows="2" maxlength="1000" wire:model="note" />
                    @error('adjust') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                    <div class="flex gap-2">
                        <x-ui.button wire:click="saveAdjust" wire:loading.attr="disabled">Simpan penyesuaian</x-ui.button>
                        <x-ui.button variant="outline" wire:click="cancel">Batal</x-ui.button>
                    </div>
                </div>
            @endif

            {{-- paused program --}}
            @if ($program->status === ProgramStatus::NeedsReview)
                <div class="mt-5 rounded-xl border border-border p-4 space-y-3">
                    <p class="text-sm font-medium">Program dijeda (perlu ditinjau). Pilih tindak lanjut:</p>
                    <x-ui.textarea label="Catatan (wajib, minimal 10 karakter)" rows="2" maxlength="1000" wire:model="resumeNote" />
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="lighter"> Turunkan satu level intensitas</label>
                    <x-ui.button wire:click="resume" wire:loading.attr="disabled"><x-lucide-play class="size-4" /> Lanjutkan dengan penyesuaian</x-ui.button>
                </div>
            @endif

            {{-- end --}}
            @if ($mode === '')
                <div class="mt-5 rounded-xl border border-destructive/30 p-4 space-y-3">
                    <x-ui.textarea label="Alasan mengakhiri program (wajib, minimal 10 karakter)" rows="2" maxlength="500" wire:model="endReason" />
                    <x-ui.button variant="outline" wire:click="endProgram" wire:confirm="Akhiri program ini? Pasien akan diminta melakukan reassessment.">
                        <x-lucide-square class="size-4" /> Akhiri program
                    </x-ui.button>
                </div>
            @endif
        </x-panel>
    @else
        <x-panel title="Belum ada program berjalan">
            @if ($this->createRefusal)
                <p class="text-sm text-muted-foreground">{{ $this->createRefusal }}</p>
            @elseif ($mode !== 'create')
                <p class="text-sm text-muted-foreground">Susun program terbimbing untuk pasien ini (misalnya pasien anak).</p>
                <x-ui.button class="mt-3" wire:click="startCreate"><x-lucide-plus class="size-4" /> Buat program</x-ui.button>
            @else
                <div class="space-y-4">
                    <x-ui.input label="Judul program" wire:model="title" maxlength="120" />
                    @include('staff.library-picker', ['action' => 'pick', 'selected' => array_keys($picked)])

                    @foreach ($picked as $exerciseId => $dose)
                        @php $exercise = $this->library->firstWhere('id', $exerciseId) ?? \App\Models\Exercise::withoutGlobalScopes()->find($exerciseId); @endphp
                        <div wire:key="pk-{{ $exerciseId }}" class="rounded-lg border border-border p-3">
                            <p class="text-sm font-medium">{{ $exercise->name }}</p>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach (ProgramRules::limits($exercise->dose_unit, $exercise->type->value) as $field => [$min, $max])
                                    <x-ui.input type="number" :min="$min" :max="$max" :label="ProgramRules::FIELD_LABELS[$field].' ('.$min.'–'.$max.')'" wire:model="picked.{{ $exerciseId }}.{{ $field }}" />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @error('items') <p class="text-xs text-destructive">{{ $message }}</p> @enderror

                    <x-ui.textarea label="Catatan untuk pasien (opsional)" rows="2" maxlength="1000" wire:model="createNote" />
                    <p class="text-xs text-muted-foreground">Program terbimbing, dipantau fisioterapis, evaluasi setiap {{ ProgramRules::CREATED_EVALUATION_WEEKS }} minggu.</p>
                    @error('create') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                    <div class="flex gap-2">
                        <x-ui.button wire:click="saveCreate" wire:loading.attr="disabled">Simpan program</x-ui.button>
                        <x-ui.button variant="outline" wire:click="cancel">Batal</x-ui.button>
                    </div>
                </div>
            @endif
        </x-panel>
    @endif

    <x-panel title="Riwayat program">
        @forelse ($this->history as $prog)
            <div wire:key="hp-{{ $prog->id }}" class="border-b border-border py-3 last:border-0">
                <p class="text-sm font-medium">{{ $prog->title }} <span class="text-xs text-muted-foreground">· {{ $prog->status->label() }}</span></p>
                <p class="text-xs text-muted-foreground">
                    {{ Intervention::pathwayLabel($prog->pathway) }} · level {{ $prog->intensity_level }}
                    · mulai {{ $prog->starts_on?->translatedFormat('d M Y') ?? '-' }}
                    @if ($prog->end_reason) · {{ $prog->end_reason }} @endif
                </p>
                <ul class="mt-1 text-xs">
                    @foreach ($prog->allItems as $item)
                        <li class="{{ $item->isRemoved() ? 'text-muted-foreground line-through' : '' }}">
                            {{ $item->exercise->name }} ({{ $item->doseLabel() }})@if ($item->isRemoved()) · dihapus {{ $item->removed_at->translatedFormat('d M Y') }}@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Belum ada program.</p>
        @endforelse
    </x-panel>
</div>
