<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-canvas text-ink">
        <div class="flex min-h-screen items-center justify-center p-6">
            <div class="w-full max-w-md">
                <div class="mb-6 text-center">
                    <p class="text-sm font-semibold tracking-wide text-brand uppercase">{{ config('app.name') }}</p>
                    <h1 class="mt-1 text-xl font-semibold text-ink">@yield('heading', 'Sign in')</h1>
                </div>
                <div class="rounded-md border border-line bg-surface p-6 shadow-sm">
                    @yield('content')
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
