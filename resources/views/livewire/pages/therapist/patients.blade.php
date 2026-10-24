<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use App\Support\Onboarding\Intervention;
use App\Support\Staff\Assignment;
use App\Support\Staff\Attention;
use App\Support\Staff\PatientOverview;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

// Staff patient list (PRD 09 S1): search, filters, needs-attention first, claim and assign.
new class extends Component {
    use WithPagination;

    public const PER_PAGE = 15;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $tier = '';

    #[Url(except: '')]
    public string $pathway = '';

    /** '' (all), mine, unassigned, attention */
    #[Url(except: '')]
    public string $scope = '';

    /** @var array<int, string> chosen therapist id per patient id (clinic admin) */
    public array $assignTo = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Patient::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'tier', 'pathway', 'scope'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return in_array(auth()->user()->role, [Role::ClinicAdmin, Role::PlatformAdmin], true);
    }

    /** Filtered rows, needs-attention first, then by name. */
    #[Computed]
    public function filtered()
    {
        $viewer = auth()->user();
        $query = PatientOverview::visibleTo($viewer);

        if (trim($this->search) !== '') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.trim($this->search).'%'));
        }

        return PatientOverview::rows($query, $viewer)
            ->when($this->tier !== '', fn ($rows) => $rows->filter(fn ($r) => $r['tier']?->value === $this->tier))
            ->when($this->pathway !== '', fn ($rows) => $rows->filter(fn ($r) => ! $r['limited'] && $r['pathway'] === $this->pathway))
            ->when($this->scope === 'mine', fn ($rows) => $rows->filter(fn ($r) => $r['mine']))
            ->when($this->scope === 'unassigned', fn ($rows) => $rows->filter(fn ($r) => ! $r['assigned']))
            ->when($this->scope === 'attention', fn ($rows) => $rows->filter(fn ($r) => $r['reasons'] !== []))
            ->sortBy([
                fn ($a, $b) => ($b['reasons'] !== []) <=> ($a['reasons'] !== []),
                fn ($a, $b) => strcasecmp($a['name'], $b['name']),
            ])
            ->values();
    }

    #[Computed]
    public function pageRows()
    {
        return $this->filtered->forPage($this->currentPage(), self::PER_PAGE)->values();
    }

    #[Computed]
    public function lastPage(): int
    {
        return max(1, (int) ceil($this->filtered->count() / self::PER_PAGE));
    }

    public function currentPage(): int
    {
        return min(max(1, (int) $this->getPage()), $this->lastPage);
    }

    /** Therapists of the viewer's clinic, for the assign dropdown. */
    #[Computed]
    public function therapists()
    {
        if (! $this->isAdmin) {
            return collect();
        }

        return Assignment::therapists(auth()->user()->role === Role::PlatformAdmin ? null : auth()->user()->clinic_id);
    }

    public function claim(int $patientId): void
    {
        $claimed = Assignment::claim(auth()->user(), Assignment::find($patientId));

        $this->refresh();
        $this->dispatch('toast', message: $claimed ? 'Pasien sekarang ditangani oleh Anda' : 'Pasien sudah diambil fisioterapis lain',
            type: $claimed ? 'success' : 'error');
    }

    public function assign(int $patientId): void
    {
        $patient = Assignment::find($patientId);
        $therapist = User::find((int) ($this->assignTo[$patientId] ?? 0));

        if (! $therapist) {
            $this->addError("assignTo.$patientId", 'Pilih fisioterapis.');

            return;
        }

        Assignment::assign(auth()->user(), $patient, $therapist);

        unset($this->assignTo[$patientId]);
        $this->refresh();
        $this->dispatch('toast', message: 'Pasien ditugaskan ke '.$therapist->name, type: 'success');
    }

    private function refresh(): void
    {
        unset($this->filtered, $this->pageRows, $this->lastPage);
    }

    public function with(): array
    {
        return [
            'tiers' => RiskTier::cases(),
            'pathways' => [...array_values(Intervention::PATHWAYS), Intervention::MAINTENANCE],
        ];
    }
}; ?>

<div class="space-y-6">
    <x-page-header title="Pasien" :subtitle="$this->isAdmin ? 'Semua pasien klinik. Pasien yang perlu perhatian tampil lebih dulu.' : 'Pasien Anda dan pasien klinik yang belum ditugaskan. Pasien yang perlu perhatian tampil lebih dulu.'" />

    <x-panel>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.input label="Cari nama" placeholder="Nama pasien" wire:model.live.debounce.300ms="search" />
            <x-ui.select label="Kategori risiko" wire:model.live="tier">
                <option value="">Semua</option>
                @foreach ($tiers as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select label="Jalur program" wire:model.live="pathway">
                <option value="">Semua</option>
                @foreach ($pathways as $p)
                    <option value="{{ $p['key'] }}">{{ $p['label'] }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select label="Tampilkan" wire:model.live="scope">
                <option value="">Semua pasien</option>
                @unless ($this->isAdmin) <option value="mine">Pasien saya</option> @endunless
                <option value="unassigned">Belum ditugaskan</option>
                <option value="attention">Perlu perhatian</option>
            </x-ui.select>
        </div>
    </x-panel>

    <x-panel>
        <x-ui.table>
            <x-ui.table.header>
                <x-ui.table.row>
                    <x-ui.table.head>Nama</x-ui.table.head>
                    <x-ui.table.head>Usia · modul</x-ui.table.head>
                    <x-ui.table.head>Risiko</x-ui.table.head>
                    <x-ui.table.head>Jalur</x-ui.table.head>
                    <x-ui.table.head>Kepatuhan 7 hari</x-ui.table.head>
                    <x-ui.table.head>Aktivitas terakhir</x-ui.table.head>
                    <x-ui.table.head>Evaluasi berikutnya</x-ui.table.head>
                    <x-ui.table.head>Perhatian</x-ui.table.head>
                    <x-ui.table.head>Fisioterapis</x-ui.table.head>
                </x-ui.table.row>
            </x-ui.table.header>
            <x-ui.table.body>
                @forelse ($this->pageRows as $row)
                    <x-ui.table.row wire:key="p-{{ $row['id'] }}">
                        <x-ui.table.cell class="font-medium">
                            @if ($row['limited'])
                                {{ $row['name'] }}
                            @else
                                <a href="{{ route('therapist.patients.show', $row['id']) }}" wire:navigate class="hover:underline">{{ $row['name'] }}</a>
                            @endif
                        </x-ui.table.cell>
                        <x-ui.table.cell>{{ $row['age'] !== null ? $row['age'].' th' : '-' }}@if ($row['module']) · {{ $row['module'] }}@endif</x-ui.table.cell>
                        <x-ui.table.cell>
                            @if ($row['tier'])
                                <x-ui.badge :variant="$row['tier']->badge()">{{ str_replace('Risiko ', '', $row['tier']->label()) }}</x-ui.badge>
                            @else
                                -
                            @endif
                        </x-ui.table.cell>

                        @if ($row['limited'])
                            <x-ui.table.cell colspan="5" class="text-xs text-muted-foreground">Detail tampil setelah pasien diambil.</x-ui.table.cell>
                            <x-ui.table.cell>
                                @if ($row['claimable'])
                                    <x-ui.button size="sm" wire:click="claim({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="claim({{ $row['id'] }})">
                                        <x-lucide-user-plus class="size-4" /> Ambil pasien
                                    </x-ui.button>
                                @endif
                            </x-ui.table.cell>
                        @else
                            <x-ui.table.cell>{{ $row['pathway'] ? Intervention::pathwayLabel($row['pathway']) : '-' }}</x-ui.table.cell>
                            <x-ui.table.cell>{{ $row['adherence'] !== null ? $row['adherence'].'%' : '-' }}</x-ui.table.cell>
                            <x-ui.table.cell>{{ $row['last_activity'] ? \Illuminate\Support\Carbon::parse($row['last_activity'])->translatedFormat('d M Y') : '-' }}</x-ui.table.cell>
                            <x-ui.table.cell>{{ $row['next_evaluation']?->translatedFormat('d M Y') ?? '-' }}</x-ui.table.cell>
                            <x-ui.table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($row['reasons'] as $reason)
                                        <x-ui.badge :variant="in_array($reason, ['red_flag_locked', 'worsening'], true) ? 'destructive' : 'outline'">{{ Attention::label($reason) }}</x-ui.badge>
                                    @empty
                                        <span class="text-xs text-muted-foreground">-</span>
                                    @endforelse
                                </div>
                            </x-ui.table.cell>
                            <x-ui.table.cell>
                                @if ($this->isAdmin)
                                    <div class="flex items-start gap-2">
                                        <x-ui.select wire:model="assignTo.{{ $row['id'] }}" aria-label="Pilih fisioterapis" class="min-w-36">
                                            <option value="">{{ $row['therapist'] ?? 'Belum ditugaskan' }}</option>
                                            @foreach ($this->therapists as $t)
                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                            @endforeach
                                        </x-ui.select>
                                        <x-ui.button size="sm" variant="outline" wire:click="assign({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="assign({{ $row['id'] }})">
                                            {{ $row['assigned'] ? 'Ganti' : 'Tugaskan' }}
                                        </x-ui.button>
                                    </div>
                                @else
                                    {{ $row['mine'] ? 'Anda' : ($row['therapist'] ?? 'Belum ditugaskan') }}
                                @endif
                            </x-ui.table.cell>
                        @endif
                    </x-ui.table.row>
                @empty
                    <x-ui.table.row>
                        <x-ui.table.cell colspan="9" class="py-6 text-center text-sm text-muted-foreground">Tidak ada pasien yang cocok.</x-ui.table.cell>
                    </x-ui.table.row>
                @endforelse
            </x-ui.table.body>
        </x-ui.table>

        @if ($this->lastPage > 1)
            <div class="mt-4 flex items-center justify-between text-sm">
                <x-ui.button size="sm" variant="outline" wire:click="previousPage" :disabled="$this->currentPage() <= 1">Sebelumnya</x-ui.button>
                <span class="text-muted-foreground">Halaman {{ $this->currentPage() }} dari {{ $this->lastPage }}</span>
                <x-ui.button size="sm" variant="outline" wire:click="nextPage" :disabled="$this->currentPage() >= $this->lastPage">Berikutnya</x-ui.button>
            </div>
        @endif
    </x-panel>
</div>
