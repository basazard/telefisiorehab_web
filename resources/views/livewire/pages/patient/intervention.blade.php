<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Placeholder for "Rekomendasi Intervensi" (waiting for its PRD): exercise program, education, preserving function.
new #[Layout('layouts.onboarding')] class extends Component {
}; ?>

<div class="space-y-6">
    <x-panel title="Rekomendasi Intervensi" sub="Program latihan, edukasi, dan preservasi fungsi.">
        <p class="text-sm text-muted-foreground">Modul ini akan segera tersedia. Rekomendasi akan disusun dari hasil assessment Anda.</p>
        <div class="mt-6 border-t border-border pt-6">
            <a href="{{ route('patient.assessment.result') }}" wire:navigate class="text-sm text-muted-foreground underline">Lihat hasil assessment</a>
        </div>
    </x-panel>
</div>
