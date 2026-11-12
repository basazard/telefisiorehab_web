{{--
    Error pages (PRD 11 E2). Self-contained on purpose: inline CSS, no database, no session, no Vite, so they still
    render when those are the problem. Never show exception details here.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        :root { --bg: #f6f9f9; --card: #ffffff; --text: #18282b; --muted: #5b6b6e; --primary: #0f8a8f; --border: #dbe7e8; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0f1a1c; --card: #16262a; --text: #e6f0f1; --muted: #9db0b3; --primary: #3fb7b7; --border: #24393d; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px;
               background: var(--bg); color: var(--text); font: 16px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { width: 100%; max-width: 480px; background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 32px; }
        .brand { font-weight: 700; color: var(--primary); margin: 0 0 24px; }
        .code { font-size: 14px; color: var(--muted); margin: 0; }
        h1 { font-size: 24px; line-height: 1.3; margin: 4px 0 12px; }
        p { margin: 0 0 12px; color: var(--muted); }
        a.button { display: inline-block; margin-top: 12px; padding: 10px 20px; border-radius: 12px; background: var(--primary);
                   color: #fff; text-decoration: none; font-weight: 600; }
        .note { font-size: 13px; margin-top: 24px; }
    </style>
</head>
<body>
<main>
    <p class="brand">{{ config('app.name') }}</p>
    <p class="code">Kode @yield('code')</p>
    <h1>@yield('title')</h1>
    @yield('message')
    <a class="button" href="{{ url('/') }}">@yield('action', 'Kembali ke beranda')</a>
    <p class="note">Bukan untuk keadaan darurat. Jika kondisi Anda berat atau memburuk, hubungi 112 atau 119.</p>
</main>
</body>
</html>
