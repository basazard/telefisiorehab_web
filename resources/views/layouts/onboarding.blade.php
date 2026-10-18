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
    <body class="min-h-screen bg-[image:var(--gradient-soft)] bg-fixed font-sans antialiased text-foreground">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-6">
            <div class="flex items-center gap-3">
                <div class="grid size-9 place-items-center rounded-xl bg-[image:var(--gradient-hero)] text-primary-foreground">
                    <x-lucide-activity class="size-5" />
                </div>
                <div>
                    <p class="font-display font-bold leading-tight">TeleFisioRehab</p>
                    <p class="text-[11px] text-muted-foreground">Home Rehab Platform</p>
                </div>
            </div>
            <div class="w-28"><livewire:layout.logout-button /></div>
        </div>

        <main class="mx-auto max-w-3xl px-4 pb-16">
            {{ $slot }}
        </main>

        <x-ui.toaster />
    </body>
</html>
