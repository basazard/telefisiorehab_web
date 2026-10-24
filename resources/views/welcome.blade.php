<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>TeleFisioRehab — Fisioterapi Adaptif di Rumah</title>
        <meta name="description" content="Platform rehabilitasi digital dengan pemantauan adaptif untuk fisioterapi di rumah.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="flex items-center justify-between px-6 py-5 md:px-12">
            <a href="/" class="flex items-center gap-2">
                <x-application-logo />
                <span class="font-display text-lg font-bold">TeleFisioRehab</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm text-muted-foreground md:flex">
                <a href="#fitur" class="hover:text-foreground">Fitur</a>
                <a href="#peran" class="hover:text-foreground">Untuk Siapa</a>
                <a href="#tentang" class="hover:text-foreground">Tentang</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">Buka dasbor</a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-foreground hover:bg-secondary">Masuk</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">Daftar</a>
                    @endif
                @endauth
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-6 pb-24 pt-12 md:px-12">
                <div class="inline-flex items-center gap-2 rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <span class="size-1.5 rounded-full bg-accent"></span>
                    Pemantauan adaptif · v1.0
                </div>

                <h1 class="mt-6 max-w-4xl font-display text-5xl font-extrabold leading-[1.05] tracking-tight md:text-7xl">
                    Fisioterapi di rumah,
                    <br>
                    <span class="bg-[image:var(--gradient-hero)] bg-clip-text text-transparent">dipandu oleh tubuh Anda.</span>
                </h1>

                <p class="mt-6 max-w-2xl text-lg text-muted-foreground">
                    TeleFisioRehab menyesuaikan setiap sesi dengan pemulihan Anda: mencatat repetisi, durasi, dan kepatuhan,
                    sambil menjaga fisioterapis Anda tetap terhubung lewat telekonsultasi.
                </p>

                <div class="mt-10 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-primary-foreground shadow-[var(--shadow-soft)] hover:opacity-95">
                            <x-lucide-heart-pulse class="size-4" /> Lanjutkan program <x-lucide-arrow-right class="size-4" />
                        </a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-primary-foreground shadow-[var(--shadow-soft)] hover:opacity-95">
                                <x-lucide-heart-pulse class="size-4" /> Mulai sebagai pasien <x-lucide-arrow-right class="size-4" />
                            </a>
                        @endif
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-6 py-3 font-medium hover:bg-secondary">
                                <x-lucide-stethoscope class="size-4" /> Masuk sebagai fisioterapis
                            </a>
                        @endif
                    @endauth
                </div>

                <div id="fitur" class="mt-24 grid gap-5 md:grid-cols-3">
                    @foreach ([
                        ['trending-up', 'Analitik adaptif', 'Kepatuhan, skor fungsional, dan grafik tren yang menyesuaikan setiap pasien.'],
                        ['video', 'Sesi video terpandu', 'Video latihan dengan penghitung repetisi dan pencatat durasi bawaan.'],
                        ['shield-check', 'Pengawasan klinis', 'Fisioterapis meninjau sesi, menyesuaikan rencana, dan menjalankan telekonsultasi.'],
                    ] as [$icon, $title, $desc])
                        <div class="rounded-2xl border border-border bg-card p-6 shadow-[var(--shadow-card)]">
                            <div class="grid size-10 place-items-center rounded-lg bg-secondary text-secondary-foreground">
                                <x-dynamic-component :component="'lucide-'.$icon" class="size-5" />
                            </div>
                            <h3 class="mt-4 font-display text-lg font-bold">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-muted-foreground">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>

                <div id="peran" class="mt-24">
                    <h2 class="font-display text-3xl font-bold">Untuk siapa?</h2>
                    <div class="mt-6 grid gap-5 md:grid-cols-3">
                        @foreach ([
                            ['heart-pulse', 'Pasien', 'Ikuti program latihan terstruktur di rumah dan pantau kemajuan Anda dari hari ke hari.'],
                            ['stethoscope', 'Fisioterapis', 'Pantau pasien secara langsung lewat dasbor berbasis data dan atur program dengan cepat.'],
                            ['building-2', 'Klinik', 'Hubungkan layanan klinik dengan pasien di rumah melalui kemitraan yang dapat dikembangkan.'],
                        ] as [$icon, $title, $desc])
                            <div class="rounded-2xl border border-border bg-[image:var(--gradient-soft)] p-6">
                                <x-dynamic-component :component="'lucide-'.$icon" class="size-6 text-primary" />
                                <h3 class="mt-4 font-display text-lg font-bold">{{ $title }}</h3>
                                <p class="mt-2 text-sm text-muted-foreground">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="tentang" class="mt-24 rounded-2xl border border-border bg-card p-8 shadow-[var(--shadow-card)]">
                    <h2 class="font-display text-2xl font-bold">Tentang TeleFisioRehab</h2>
                    {{-- @clinical P-TX-07 --}}
                    <p class="mt-3 max-w-3xl text-muted-foreground">
                        TeleFisioRehab adalah platform rehabilitasi digital berbasis pemantauan adaptif untuk layanan fisioterapi di rumah
                        yang terintegrasi dengan klinik. Informasi di aplikasi ini bukan diagnosis dan tidak menggantikan pemeriksaan tenaga medis.
                    </p>
                    {{-- @endclinical --}}
                </div>
            </section>
        </main>

        <footer class="border-t border-border px-6 py-8 text-center text-xs text-muted-foreground md:px-12">
            &copy; {{ date('Y') }} TeleFisioRehab
        </footer>
    </body>
</html>
