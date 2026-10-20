<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="font-display text-lg font-bold">Informasi akun</h2>
        <p class="mt-1 text-sm text-muted-foreground">Perbarui nama dan alamat email akun Anda.</p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-5">
        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1.5" required autofocus autocomplete="name" />
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1.5" required autocomplete="username" />
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 rounded-xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm">
                    <p>
                        Email Anda belum diverifikasi.
                        <button wire:click.prevent="sendVerification" class="font-medium text-primary hover:underline">Kirim ulang email verifikasi.</button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium">Tautan verifikasi baru telah dikirim ke email Anda.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Simpan</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                Tersimpan.
            </x-action-message>
        </div>
    </form>
</section>
