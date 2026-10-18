<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TeleFisioRehab') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            {{-- brand panel (large screens) --}}
            <aside class="relative hidden overflow-hidden bg-[image:var(--gradient-hero)] p-12 text-primary-foreground lg:flex lg:flex-col lg:justify-between">
                <a href="/" wire:navigate class="relative z-10 flex items-center gap-2">
                    <span class="grid size-9 place-items-center rounded-xl bg-white/20"><x-lucide-activity class="size-5" /></span>
                    <span class="font-display text-lg font-bold">TeleFisioRehab</span>
                </a>

                <div class="relative z-10 max-w-md space-y-6">
                    <h2 class="font-display text-4xl font-extrabold leading-tight">Fisioterapi di rumah, dipandu oleh tubuh Anda.</h2>
                    <ul class="space-y-3 text-sm text-white/90">
                        <li class="flex items-center gap-3"><x-lucide-trending-up class="size-4 shrink-0" /> Program latihan yang menyesuaikan pemulihan Anda</li>
                        <li class="flex items-center gap-3"><x-lucide-video class="size-4 shrink-0" /> Video latihan terpandu dengan pencatat repetisi</li>
                        <li class="flex items-center gap-3"><x-lucide-shield-check class="size-4 shrink-0" /> Dipantau fisioterapis dan klinik Anda</li>
                    </ul>
                </div>

                <p class="relative z-10 text-xs text-white/70">&copy; {{ date('Y') }} TeleFisioRehab</p>

                <span class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-white/10"></span>
                <span class="pointer-events-none absolute -bottom-32 -left-16 size-96 rounded-full bg-white/10"></span>
            </aside>

            {{-- form column --}}
            <main class="flex flex-col justify-center px-6 py-10 sm:px-12">
                <a href="/" wire:navigate class="mb-8 flex items-center gap-2 lg:hidden">
                    <x-application-logo />
                    <span class="font-display text-lg font-bold">TeleFisioRehab</span>
                </a>

                <div class="mx-auto w-full max-w-md">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
