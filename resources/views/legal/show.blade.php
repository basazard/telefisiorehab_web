{{-- Public privacy and terms pages (PRD 11 E4), rendered from resources/legal/*.md. --}}
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · TeleFisioRehab</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="border-b border-border px-4 py-4 md:px-8">
            <a href="{{ url('/') }}" class="font-display font-bold text-primary">TeleFisioRehab</a>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8 md:py-12">
            @if ($placeholder)
                <div role="note" class="mb-6 rounded-xl border-2 border-warning/60 bg-warning/10 p-4 text-sm">
                    <strong>Teks sementara.</strong> Isi halaman ini belum ditinjau pengacara dan dapat berubah sebelum layanan dibuka untuk umum.
                </div>
            @endif

            <article class="text-sm leading-relaxed text-foreground [&_h1]:mb-2 [&_h1]:font-display [&_h1]:text-2xl [&_h1]:font-bold
                            [&_h2]:mt-8 [&_h2]:mb-2 [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-bold [&_p]:mb-3 [&_p]:text-muted-foreground
                            [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5 [&_li]:text-muted-foreground [&_strong]:text-foreground">
                {!! $html !!}
            </article>

            <nav class="mt-10 flex flex-wrap gap-4 border-t border-border pt-6 text-sm">
                <a href="{{ route('legal.privacy') }}" class="text-primary hover:underline">Kebijakan Privasi</a>
                <a href="{{ route('legal.terms') }}" class="text-primary hover:underline">Syarat Penggunaan</a>
                <a href="{{ url('/') }}" class="text-muted-foreground hover:underline">Beranda</a>
            </nav>
        </main>
    </body>
</html>
