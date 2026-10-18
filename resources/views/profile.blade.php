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

        <x-panel>
            <livewire:profile.delete-user-form />
        </x-panel>
    </div>
</x-app-layout>
