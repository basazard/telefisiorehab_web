<?php

use App\Models\Clinic;
use App\Models\User;
use App\Support\Registration;
use App\Support\Throttle;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /** Kode klinik (PRD 11 E3). `/register?clinic=CODE` fills it. */
    #[Url(as: 'clinic', except: '')]
    public string $clinicCode = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        abort_unless(Registration::allowed(), 403);         // closed: invitations only

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'clinicCode' => [Registration::codeRequired() ? 'required' : 'nullable', 'string', 'max:20'],
        ], ['clinicCode.required' => 'Masukkan kode klinik dari klinik Anda.'], ['clinicCode' => 'kode klinik']);

        // counted before the code is checked, so codes cannot be guessed by trying many (DRAFT limit)
        if ($limited = Throttle::hit('registration', request()->ip())) {
            $this->addError('email', $limited);

            return;
        }

        $clinic = null;
        if (filled($this->clinicCode)) {
            $clinic = Clinic::findByJoinCode($this->clinicCode);
            if (! $clinic) {
                $this->addError('clinicCode', 'Kode klinik tidak ditemukan. Periksa kembali kode dari klinik Anda.');

                return;
            }
        }

        $user = DB::transaction(function () use ($validated, $clinic) {
            $user = new User([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->forceFill(['clinic_id' => $clinic?->id])->save();
            $user->patient()->forceCreate(['clinic_id' => $clinic?->id]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if (! Registration::allowed())
        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold">Pendaftaran ditutup</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Saat ini akun hanya dibuat lewat undangan. Hubungi klinik Anda untuk mendapatkan undangan.
            </p>
        </div>
        <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">Ke halaman masuk</a>
    @else
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold">Buat akun Anda</h1>
        <p class="mt-1 text-sm text-muted-foreground">Mulai program fisioterapi di rumah dalam beberapa menit.</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <!-- Clinic code -->
        <div>
            <x-input-label for="clinicCode" :value="Registration::codeRequired() ? 'Kode klinik' : 'Kode klinik (jika ada)'" />
            <x-text-input wire:model="clinicCode" id="clinicCode" class="mt-1.5 uppercase" type="text" name="clinicCode" placeholder="Contoh: K7M4Q2RX"
                          :required="Registration::codeRequired()" autocomplete="off" autocapitalize="characters" spellcheck="false" />
            <p class="mt-1.5 text-xs text-muted-foreground">Kode diberikan oleh klinik tempat Anda berobat.</p>
            <x-input-error :messages="$errors->get('clinicCode')" class="mt-1.5" />
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nama lengkap" />
            <x-text-input wire:model="name" id="name" class="mt-1.5" type="text" name="name" placeholder="Nama sesuai identitas" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="mt-1.5" type="email" name="email" placeholder="nama@email.com" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-password-input wire:model="password" id="password" class="mt-1.5" name="password" required autocomplete="new-password" />
            <p class="mt-1.5 text-xs text-muted-foreground">Gunakan minimal 8 karakter.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="mt-1.5" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <p class="text-xs text-muted-foreground">
            Dengan mendaftar, Anda menyetujui <a href="{{ route('legal.terms') }}" target="_blank" class="text-primary hover:underline">Syarat Penggunaan</a>
            dan <a href="{{ route('legal.privacy') }}" target="_blank" class="text-primary hover:underline">Kebijakan Privasi</a>.
        </p>

        <x-primary-button class="w-full" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Daftar</span>
            <span wire:loading wire:target="register">Memproses…</span>
        </x-primary-button>
    </form>
    @endif

    <p class="mt-6 text-center text-sm text-muted-foreground">
        Sudah punya akun?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-primary hover:underline">Masuk</a>
    </p>
</div>
