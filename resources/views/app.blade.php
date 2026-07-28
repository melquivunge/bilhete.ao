<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Lida pelo Inertia para assinar os <style> que injeta em runtime. --}}
    <meta name="csp-nonce" content="{{ Illuminate\Support\Facades\Vite::cspNonce() }}">

    <title inertia>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full bg-white font-sans text-slate-900">
    @inertia
</body>
</html>
