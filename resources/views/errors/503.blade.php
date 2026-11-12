@extends('errors.layout')

@php
    $retry = (int) (($exception ?? null)?->getHeaders()['Retry-After'] ?? 0);
@endphp

@section('code', '503')
@section('title', 'Sedang dalam pemeliharaan')
@section('message')
    <p>
        {{ config('app.name') }} sedang diperbarui.
        @if ($retry > 0)
            Silakan coba lagi dalam {{ $retry >= 120 ? ceil($retry / 60).' menit' : $retry.' detik' }}.
        @else
            Silakan coba lagi beberapa saat lagi.
        @endif
    </p>
@endsection
@section('action', 'Muat ulang')
