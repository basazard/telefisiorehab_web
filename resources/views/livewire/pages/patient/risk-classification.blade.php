<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Placeholder for "Klasifikasi Risiko" (waiting for its PRD). Reached from the assessment result when a limitation was found.
new #[Layout('layouts.onboarding')] class extends Component {
}; ?>

<div class="space-y-6">
    <x-panel title="Klasifikasi Risiko" sub="Assessment klinis selesai.">
        <p class="text-sm text-muted-foreground">Modul ini akan segera tersedia. Hasil assessment Anda sudah tersimpan dan akan dipakai untuk menentukan tingkat risiko.</p>
        <div class="mt-6 flex flex-wrap justify-between gap-3 border-t border-border pt-6">
            <a href="{{ route('patient.assessment.result') }}" wire:navigate class="text-sm text-muted-foreground underline">Lihat hasil assessment</a>
            <a href="{{ route('patient.intervention') }}" wire:navigate class="text-sm font-medium text-primary underline">Lanjut ke Rekomendasi Intervensi</a>
        </div>
    </x-panel>
</div>
