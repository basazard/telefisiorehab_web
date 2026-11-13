<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Profil akun" subtitle="Kelola informasi akun, kata sandi, dan data Anda." />

        @if (auth()->user()->role === \App\Enums\Role::Patient)
            <x-panel title="Data kesehatan" sub="Tanggal lahir, tinggi dan berat badan, aktivitas, kondisi khusus, dan tujuan Anda.">
                <x-ui.button :href="route('patient.profile.setup')" variant="outline">
                    <x-lucide-pencil class="size-4" /> Perbarui data kesehatan
                </x-ui.button>
            </x-panel>
        @endif

        <x-panel>
            <livewire:profile.update-profile-information-form />
        </x-panel>

        <x-panel>
            <livewire:profile.update-password-form />
        </x-panel>

        {{-- PRD 11 E4: no self-service deletion during the pilot (it would erase the health history); requests go to support.
             The delete-user-form component is kept, only not shown. --}}
        <x-panel title="Permintaan data saya" sub="Anda dapat meminta salinan data Anda, perbaikan data yang keliru, atau penghapusan akun dan data Anda.">
            <p class="text-sm">
                Selama uji coba, permintaan diproses secara manual dan dijawab paling lambat {{ config('legal.data_request_days') }} hari.
                Kami akan memastikan identitas Anda sebelum memproses permintaan.
            </p>
            <p class="mt-2 text-sm">
                @if (config('pilot.support_email'))
                    Kirim permintaan ke <a href="mailto:{{ config('pilot.support_email') }}" class="font-medium text-primary hover:underline">{{ config('pilot.support_email') }}</a>
                    dari alamat email akun Anda.
                @else
                    Hubungi klinik tempat Anda terdaftar untuk mengajukan permintaan.
                @endif
            </p>
            <p class="mt-2 text-xs text-muted-foreground">
                Lihat <a href="{{ route('legal.privacy') }}" class="text-primary hover:underline">Kebijakan Privasi</a> untuk data apa yang kami simpan.
            </p>
        </x-panel>
    </div>
</x-app-layout>
