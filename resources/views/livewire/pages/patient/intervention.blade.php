<?php

use App\Models\AssessmentResult;
use App\Models\ConsultationRequest;
use App\Support\Onboarding\Intervention;
use App\Support\Onboarding\Programs;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Exercise / Telefisioterapi Recommendation PRD: pathway by risk category, program start, consultation / referral requests.
new #[Layout('layouts.onboarding')] class extends Component {
    public string $reason = '';

    public function mount(): void
    {
        $assessment = $this->assessment;

        if (! $assessment) {
            $this->redirectRoute('patient.domain', navigate: true);
        } elseif (! $assessment->isStratified()) {
            $this->redirectRoute('patient.risk-classification', navigate: true);
        }
    }

    #[Computed]
    public function assessment(): ?AssessmentResult
    {
        return auth()->user()->patient->assessments()->where('status', 'completed')->latest('completed_at')->first();
    }

    #[Computed]
    public function requests()
    {
        return auth()->user()->patient->consultationRequests()->latest()->take(5)->get();
    }

    public function startProgram(): void
    {
        $assessment = $this->assessment;

        // Safety block (acceptance criterion 2): decided on the server, whatever the browser sends.
        abort_unless($assessment?->tier && Intervention::canSelfExercise($assessment->tier, $assessment->domain), 403);

        Programs::build($assessment);

        $this->redirectRoute('patient.program', navigate: true);
    }

    public function requestConsultation(string $type): void
    {
        $this->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        validator(['type' => $type], ['type' => ['required', Rule::in(array_keys(ConsultationRequest::TYPES))]])->validate();

        if (! auth()->user()->patient->requestConsultation($type, $this->assessment?->id, $this->reason)) {
            $this->dispatch('toast', message: 'Permintaan Anda masih diproses.');

            return;
        }

        $this->reason = '';
        unset($this->requests);
        $this->dispatch('toast', message: 'Permintaan terkirim. Tim kami akan menghubungi Anda.', type: 'success');
    }
}; ?>

<div class="space-y-6">
    @if (($a = $this->assessment) && $a->tier)
    @php
        $tier = $a->tier;
        $path = \App\Support\Onboarding\Intervention::pathway($tier);
        $canExercise = \App\Support\Onboarding\Intervention::canSelfExercise($tier, $a->domain);
        $types = \App\Support\Onboarding\Intervention::exerciseTypes($tier, $a->domain, \App\Support\Onboarding\Intervention::areas($a->screening?->regions ?? []));
        $program = $a->program;
        $btn = 'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-medium transition-opacity';
    @endphp

    <x-page-header title="Rekomendasi Intervensi" :subtitle="$tier->label().' · Domain '.$a->domain->label()" />

    <x-panel class="sm:p-8">
        <p class="text-xs uppercase tracking-wide text-muted-foreground">Jalur layanan Anda</p>
        <h2 class="font-display text-2xl font-bold">{{ $path['label'] }}</h2>
        <p class="mt-1 text-sm text-muted-foreground">{{ $path['approach'] }}</p>

        <ul class="mt-5 space-y-2">
            @foreach ($path['recommendations'] as $r)
                <li class="flex items-start gap-2 text-sm"><x-lucide-circle-check class="mt-0.5 size-4 shrink-0 text-primary" /> {{ $r }}</li>
            @endforeach
        </ul>

        @if ($types)
            <div class="mt-6">
                <p class="text-sm font-medium">Jenis latihan</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($types as $type)
                        <x-ui.badge variant="secondary">{{ $type->label() }}</x-ui.badge>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($tier === \App\Enums\RiskTier::High)
            <div role="alert" class="mt-6 flex items-start gap-3 rounded-xl border-2 border-destructive bg-destructive/10 p-4 text-sm">
                <x-lucide-shield-alert class="mt-0.5 size-5 shrink-0 text-destructive" />
                <p><strong class="text-destructive">Tidak diberikan latihan mandiri.</strong> Konsultasikan kondisi Anda dengan fisioterapis atau fasilitas kesehatan terlebih dahulu.</p>
            </div>
        @elseif (! $canExercise)
            <p class="mt-6 rounded-xl border border-border bg-muted/40 p-4 text-sm">Program latihan untuk anak disusun langsung oleh fisioterapis. Ajukan telekonsultasi di bawah.</p>
        @endif

        <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-border pt-6">
            @if ($canExercise)
                @if ($program)
                    <a href="{{ route('patient.program') }}" wire:navigate class="{{ $btn }} bg-primary text-primary-foreground hover:opacity-95">
                        Buka program latihan <x-lucide-arrow-right class="size-4" />
                    </a>
                @else
                    <x-onboarding.primary-button wire:click="startProgram" wire:loading.attr="disabled" wire:target="startProgram">
                        {{ $tier === \App\Enums\RiskTier::Low ? 'Mulai program latihan mandiri' : 'Mulai program latihan terstruktur' }}
                        <x-lucide-arrow-right class="size-4" />
                    </x-onboarding.primary-button>
                @endif
            @endif
        </div>
    </x-panel>

    @if ($tier !== \App\Enums\RiskTier::Low || ! $canExercise)
        <x-panel title="{{ $tier === \App\Enums\RiskTier::High ? 'Tindakan' : 'Konsultasi dengan fisioterapis' }}"
                 sub="Permintaan Anda diteruskan ke fisioterapis{{ auth()->user()->clinic_id ? ' klinik Anda' : '' }}.">
            <x-ui.textarea label="Keterangan singkat (opsional)" rows="2" maxlength="500" placeholder="mis. waktu yang nyaman untuk dihubungi" wire:model="reason" />
            <div @class(['mt-4 grid gap-3', 'sm:grid-cols-3' => $tier === \App\Enums\RiskTier::High, 'sm:grid-cols-2' => $tier !== \App\Enums\RiskTier::High])>
                <button type="button" wire:click="requestConsultation('teleconsultation')" class="{{ $btn }} border border-border bg-card hover:bg-secondary">
                    <x-lucide-video class="size-4" /> Telekonsultasi fisioterapis
                </button>
                @if ($tier === \App\Enums\RiskTier::High)
                    <button type="button" wire:click="requestConsultation('referral')" class="{{ $btn }} border border-border bg-card hover:bg-secondary">
                        <x-lucide-hospital class="size-4" /> Rujukan ke fasilitas kesehatan
                    </button>
                @endif
                <a href="#edukasi" class="{{ $btn }} border border-border bg-card hover:bg-secondary">
                    <x-lucide-book-open class="size-4" /> {{ $tier === \App\Enums\RiskTier::High ? 'Edukasi kondisi aman' : 'Edukasi' }}
                </a>
            </div>
        </x-panel>
    @endif

    @if ($this->requests->isNotEmpty())
        <x-panel title="Permintaan Anda">
            <ul class="divide-y divide-border">
                @foreach ($this->requests as $req)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span>{{ $req->typeLabel() }} <span class="text-xs text-muted-foreground">· {{ $req->created_at->translatedFormat('d M Y') }}</span></span>
                        <x-ui.badge :variant="$req->isOpen() ? 'outline' : 'secondary'">{{ $req->isOpen() ? 'Diproses' : 'Ditangani' }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </x-panel>
    @endif

    <div id="edukasi" class="grid gap-4 sm:grid-cols-2">
        @foreach (\App\Support\Onboarding\Intervention::EDUCATION[$tier->value] as $title => $points)
            <div class="rounded-2xl border border-border bg-card p-5 shadow-[var(--shadow-card)]">
                <p class="flex items-center gap-2 font-display font-bold"><x-lucide-lightbulb class="size-4 text-primary" /> {{ $title }}</p>
                <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-muted-foreground">
                    @foreach ($points as $p)
                        <li>{{ $p }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-border bg-muted/40 p-4 text-xs text-muted-foreground">
        <p class="flex items-center gap-2 font-medium text-foreground"><x-lucide-info class="size-4" /> Catatan</p>
        {{-- @clinical P-TX-05 --}}
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Jenis dan dosis latihan disesuaikan dengan hasil assessment dan domain fisioterapi Anda.</li>
            <li>Jika muncul keluhan baru atau kondisi memburuk, lakukan reassessment lebih awal.</li>
            <li>TeleFisioRehab tidak menggantikan pemeriksaan langsung oleh tenaga kesehatan.</li>
        </ul>
        {{-- @endclinical --}}
    </div>
    @endif
</div>
