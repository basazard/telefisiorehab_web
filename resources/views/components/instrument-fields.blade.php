{{-- One self-report instrument (title, intro, safety note and its questions). Used by the Clinical Assessment and by
     Monitoring. The parent Livewire component must have `public array $answers` and a `setAnswer(path, value)` action. --}}
@props(['instrument', 'a' => []])

@php
    $key = $instrument;
    $pick = fn (bool $on) => $on ? 'border-primary bg-primary/10 text-primary' : 'border-border bg-card hover:bg-secondary';
@endphp

<div class="space-y-6">
            <div>
                <h2 class="text-lg font-bold">{{ \App\Support\Onboarding\Instruments::TITLES[$key] }}</h2>
                <p class="text-sm text-muted-foreground">{{ \App\Support\Onboarding\Instruments::INTROS[$key] }}</p>
            </div>

            @if (in_array($key, \App\Support\Onboarding\Instruments::SUPERVISED, true))
                <p class="flex items-start gap-2 rounded-lg border border-warning/40 bg-warning/10 p-3 text-xs">
                    <x-lucide-shield-alert class="mt-0.5 size-4 shrink-0" />
                    <span>Lakukan hanya jika ada pendamping di dekat Anda. Jika tidak aman, pilih "Tidak dapat / tidak aman melakukan".</span>
                </p>
            @endif

        {{-- LEFS / ODI: one scale per item --}}
        @if (in_array($key, ['lefs', 'odi'], true))
            @php
                $items = $key === 'lefs' ? \App\Support\Onboarding\Instruments::LEFS_ITEMS : \App\Support\Onboarding\Instruments::ODI_ITEMS;
                $choices = $key === 'lefs' ? \App\Support\Onboarding\Instruments::LEFS_CHOICES : \App\Support\Onboarding\Instruments::ODI_CHOICES;
            @endphp
            <div class="space-y-3">
                @foreach ($items as $i => $text)
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm font-medium">{{ $i + 1 }}. {{ $text }}</p>
                        <div @class(['mt-3 grid gap-2', 'sm:grid-cols-5' => $key === 'lefs', 'sm:grid-cols-6' => $key === 'odi'])>
                            @foreach ($choices as $v => $label)
                                <button type="button" wire:click="setAnswer('{{ $key }}.{{ $i }}', {{ $v }})"
                                    class="rounded-lg border px-2 py-2 text-xs font-medium transition-colors {{ $pick(($a[$i] ?? null) === $v) }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        @if ($key === 'odi' && in_array($i, \App\Support\Onboarding\Instruments::ODI_OPTIONAL, true))
                            <button type="button" wire:click="setAnswer('odi.{{ $i }}', null)" class="mt-2 text-xs text-muted-foreground underline">Lewati pertanyaan ini</button>
                        @endif
                        @error("answers.$key.$i") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        @endif

        {{-- SPADI: 0-10 per item, pain then disability --}}
        @if ($key === 'spadi')
            @foreach ([['Nyeri (0 = tidak nyeri, 10 = nyeri terburuk)', \App\Support\Onboarding\Instruments::SPADI_PAIN, 0],
                       ['Kesulitan (0 = tidak sulit, 10 = sangat sulit / butuh bantuan)', \App\Support\Onboarding\Instruments::SPADI_DISABILITY, count(\App\Support\Onboarding\Instruments::SPADI_PAIN)]] as [$heading, $items, $offset])
                <div class="space-y-3">
                    <p class="text-sm font-semibold">{{ $heading }}</p>
                    @foreach ($items as $n => $text)
                        @php $i = $offset + $n; @endphp
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-sm font-medium">{{ $text }}</p>
                            <div class="mt-3 grid grid-cols-11 gap-1">
                                @foreach (range(0, 10) as $v)
                                    <button type="button" wire:click="setAnswer('spadi.{{ $i }}', {{ $v }})"
                                        class="h-9 rounded-lg border text-sm font-medium transition-colors {{ $pick(($a[$i] ?? null) === $v) }}">{{ $v }}</button>
                                @endforeach
                            </div>
                            @error("answers.spadi.$i") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif

        {{-- PSFS: up to 3 activities, each rated 0-10 --}}
        @if ($key === 'psfs')
            @foreach (range(0, \App\Support\Onboarding\Instruments::PSFS_SLOTS - 1) as $i)
                <div class="space-y-3 rounded-xl border border-border p-4">
                    <x-ui.input :label="'Aktivitas '.($i + 1).($i === 0 ? '' : ' (opsional)')" placeholder="mis. naik tangga, bermain bulu tangkis" maxlength="100"
                                wire:model.blur="answers.psfs.{{ $i }}.activity" />
                    <div class="grid grid-cols-11 gap-1">
                        @foreach (range(0, 10) as $v)
                            <button type="button" wire:click="setAnswer('psfs.{{ $i }}.score', {{ $v }})"
                                class="h-9 rounded-lg border text-sm font-medium transition-colors {{ $pick(($a[$i]['score'] ?? null) === $v) }}">{{ $v }}</button>
                        @endforeach
                    </div>
                    <p class="flex justify-between text-[11px] text-muted-foreground"><span>Tidak mampu</span><span>Mampu seperti sebelum keluhan</span></p>
                    @error("answers.psfs.$i.score") <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                </div>
            @endforeach
        @endif

        {{-- Barthel Index --}}
        @if ($key === 'barthel')
            <div class="space-y-3">
                @foreach (\App\Support\Onboarding\Instruments::BARTHEL as $item => [$label, $choices])
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm font-medium">{{ $label }}</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($choices as $v => $text)
                                <button type="button" wire:click="setAnswer('barthel.{{ $item }}', {{ $v }})"
                                    class="rounded-lg border px-3 py-2 text-left text-xs font-medium transition-colors {{ $pick(($a[$item] ?? null) === $v) }}">{{ $text }}</button>
                            @endforeach
                        </div>
                        @error("answers.barthel.$item") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Fall-risk checklist (CDC STEADI) --}}
        @if ($key === 'steadi')
            <div class="space-y-3">
                @foreach (\App\Support\Onboarding\Instruments::STEADI as $i => [$text])
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm font-medium">{{ $text }}</p>
                        <div class="mt-3 grid max-w-xs grid-cols-2 gap-3">
                            @foreach (['yes' => 'Ya', 'no' => 'Tidak'] as $v => $label)
                                <button type="button" wire:click="setAnswer('steadi.{{ $i }}', '{{ $v }}')"
                                    class="rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors {{ $pick(($a[$i] ?? null) === $v) }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        @error("answers.steadi.$i") <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        @endif

        {{-- TUG --}}
        @if ($key === 'tug')
            <div class="space-y-4">
                <label class="flex items-center gap-2 text-sm">
                    <x-ui.checkbox wire:model.live="answers.tug.unable" /> Tidak dapat / tidak aman melakukan
                </label>
                @unless ($a['unable'] ?? false)
                    <x-ui.input label="Waktu (detik)" type="number" step="0.1" min="1" max="300" inputmode="decimal" class="max-w-40"
                                wire:model.blur="answers.tug.seconds" />
                @endunless
            </div>
        @endif

        {{-- mMRC --}}
        @if ($key === 'mmrc')
            <div class="space-y-2">
                @foreach (\App\Support\Onboarding\Instruments::MMRC as $v => $text)
                    <button type="button" wire:click="setAnswer('mmrc.grade', {{ $v }})"
                        class="flex w-full items-start gap-3 rounded-xl border px-4 py-3 text-left text-sm transition-colors {{ $pick(($a['grade'] ?? null) === $v) }}">
                        <span class="font-semibold">{{ $v }}</span><span>{{ $text }}</span>
                    </button>
                @endforeach
                @error('answers.mmrc.grade') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- 1-minute sit to stand + Borg --}}
        @if ($key === 'sts')
            <div class="space-y-5">
                <label class="flex items-center gap-2 text-sm">
                    <x-ui.checkbox wire:model.live="answers.sts.unable" /> Tidak dapat / tidak aman melakukan
                </label>
                @unless ($a['unable'] ?? false)
                    <x-ui.input label="Jumlah berdiri-duduk dalam 1 menit" type="number" min="0" max="100" inputmode="numeric" class="max-w-40"
                                wire:model.blur="answers.sts.reps" />
                    <div>
                        <x-ui.label>Seberapa sesak Anda setelah tes? (Borg 0–10)</x-ui.label>
                        <div class="mt-2 grid grid-cols-11 gap-1">
                            @foreach (\App\Support\Onboarding\Instruments::BORG as $v => $label)
                                <button type="button" wire:click="setAnswer('sts.borg', {{ $v }})" title="{{ $label }}"
                                    class="h-9 rounded-lg border text-sm font-medium transition-colors {{ $pick(($a['borg'] ?? null) === $v) }}">{{ $v }}</button>
                            @endforeach
                        </div>
                        <p class="mt-1 flex justify-between text-[11px] text-muted-foreground"><span>Tidak sesak</span><span>Maksimal</span></p>
                        @error('answers.sts.borg') <p class="mt-1.5 text-xs text-destructive">{{ $message }}</p> @enderror
                    </div>
                @endunless
            </div>
        @endif

        {{-- Vital signs (optional) --}}
        @if ($key === 'vitals')
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.input label="SpO2 (%)" type="number" min="50" max="100" inputmode="numeric" wire:model.blur="answers.vitals.spo2" />
                <x-ui.input label="Nadi (per menit)" type="number" min="30" max="220" inputmode="numeric" wire:model.blur="answers.vitals.hr" />
                <x-ui.input label="Napas (per menit)" type="number" min="5" max="60" inputmode="numeric" wire:model.blur="answers.vitals.rr" />
            </div>
        @endif

        {{-- Edema --}}
        @if ($key === 'edema')
            <div class="space-y-5">
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Support\Onboarding\Instruments::EDEMA as $v => $label)
                        <x-onboarding.chip :active="($a['level'] ?? null) === $v" wire:click="setAnswer('edema.level', '{{ $v }}')">{{ $label }}</x-onboarding.chip>
                    @endforeach
                </div>
                @error('answers.edema.level') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                <div>
                    <p class="text-sm font-medium">Lingkar area (opsional, pakai meteran kain)</p>
                    <div class="mt-2 grid gap-4 sm:grid-cols-2">
                        <x-ui.input label="Sisi yang sakit (cm)" type="number" step="0.1" min="5" max="200" inputmode="decimal" wire:model.blur="answers.edema.affected_cm" />
                        <x-ui.input label="Sisi yang sehat (cm)" type="number" step="0.1" min="5" max="200" inputmode="decimal" wire:model.blur="answers.edema.healthy_cm" />
                    </div>
                </div>
            </div>
        @endif

        {{-- Wound status --}}
        @if ($key === 'wound')
            <div class="space-y-2">
                @foreach (\App\Support\Onboarding\Instruments::WOUND as $v => $label)
                    <button type="button" wire:click="setAnswer('wound.status', '{{ $v }}')"
                        class="w-full rounded-xl border px-4 py-3 text-left text-sm transition-colors {{ $pick(($a['status'] ?? null) === $v) }}">{{ $label }}</button>
                @endforeach
                @error('answers.wound.status') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
            </div>
        @endif
</div>
