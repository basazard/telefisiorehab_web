<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TeleFisioRehab') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700,800&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-background text-foreground">
    <x-dashboard-shell :role="\Illuminate\Support\Str::headline(auth()->user()->role->value)"
        :user-name="auth()->user()->name" :nav="\App\Support\Navigation::for(auth()->user()->role)">
        {{ $slot }}
    </x-dashboard-shell>

    <x-ui.toaster />
</body>

</html>