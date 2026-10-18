<?php

use App\Models\RedFlagCheck;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.onboarding')] class extends Component {
    public function mount(): void
    {
        // Nothing to show once the stop has been lifted by the clinic.
        if (! $this->check) {
            $this->redirectRoute('patient.screening', navigate: true);
        }
    }

    #[Computed]
    public function check(): ?RedFlagCheck
    {
        return auth()->user()->patient->redFlagChecks()
            ->whereNotNull('stopped_at')->whereNull('unlocked_at')
            ->latest('stopped_at')->first();
    }

    #[Computed]
    public function hasClinic(): bool
    {
        return auth()->user()->clinic_id !== null;
    }
}; ?>

<div class="space-y-6">
    @if ($check = $this->check)
        <div role="alert" class="rounded-2xl border-2 border-destructive bg-destructive/10 p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-destructive text-destructive-foreground">
                    <x-lucide-triangle-alert class="size-6" />
                </span>
                <div class="space-y-2">
                    <h1 class="text-xl font-bold text-destructive">Program latihan dihentikan</h1>
                    <p class="text-sm text-foreground">
                        Berdasarkan jawaban Anda, ada tanda bahaya yang perlu diperiksa tenaga medis. Anda
                        <strong>belum dapat melanjutkan program latihan</strong>. Segera periksakan diri ke
                        fasilitas kesehatan (puskesmas, klinik, atau rumah sakit).
                    </p>
                </div>
            </div>
        </div>

        <x-panel title="Tanda bahaya yang Anda laporkan">
            <ul class="space-y-2">
                @foreach ($check->flagged as $key)
                    <li class="flex items-start gap-2 text-sm">
                        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0 text-destructive" />
                        <span>
                            {{ \App\Support\Onboarding\RedFlags::labels([$key])[0] }}
                            @if ($key === 'other' && $check->other_text): {{ $check->other_text }}@endif
                            @if ($key === 'pain_score_high' && $check->screening) ({{ $check->screening->max_pain }}/10)@endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-panel>

        <x-panel title="Apa yang perlu dilakukan?">
            <ol class="list-decimal space-y-2 pl-5 text-sm">
                <li>Periksakan diri ke fasilitas kesehatan terdekat sesegera mungkin.</li>
                <li>Jika kondisi terasa sangat berat atau memburuk, segera hubungi layanan darurat (112) atau ambulans (119), atau datang ke IGD.</li>
                <li>
                    @if ($this->hasClinic)
                        Setelah diperiksa, sampaikan hasilnya kepada terapis di klinik Anda. Hanya terapis klinik yang dapat membuka kembali akses program latihan Anda.
                    @else
                        Setelah diperiksa, hubungi penyedia layanan TeleFisioRehab untuk membuka kembali akses program latihan Anda.
                    @endif
                </li>
            </ol>
        </x-panel>

        <p class="text-xs text-muted-foreground">
            Informasi ini bukan diagnosis. Halaman ini tidak dapat ditutup sampai akses Anda dibuka kembali oleh tenaga yang berwenang.
        </p>
    @endif
</div>
