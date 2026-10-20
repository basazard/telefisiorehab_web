{{-- "Catatan System & Disclaimer UI" of the Clinical Assessment PRD (section 3.4) --}}
<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-muted/40 p-4 text-xs text-muted-foreground']) }}>
    <p class="flex items-center gap-2 font-medium text-foreground"><x-lucide-info class="size-4" /> Catatan</p>
    <ul class="mt-2 list-disc space-y-1 pl-5">
        <li>Instrumen assessment dipilih otomatis sesuai domain dan tujuan Anda.</li>
        <li>Hasil assessment menjadi dasar klasifikasi risiko dan rekomendasi intervensi.</li>
        <li>TeleFisioRehab tidak menggantikan pemeriksaan langsung oleh tenaga kesehatan.</li>
    </ul>
</div>
