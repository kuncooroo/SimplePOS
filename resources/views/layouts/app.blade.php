<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', $title ?? config('app.name'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-canvas text-ink">
        <div class="flex min-h-screen">
            <aside class="hidden w-56 shrink-0 border-r border-line bg-surface md:flex md:flex-col">
                <div class="border-b border-line px-4 py-4">
                    <p class="text-sm font-semibold text-ink">{{ config('app.name') }}</p>
                    <p class="text-xs text-muted">Point of sale</p>
                </div>
                <x-sidebar />
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-14 items-center justify-between border-b border-line bg-surface px-4">
                    <p class="text-sm font-medium text-ink">@yield('heading', $heading ?? 'Dashboard')</p>
                    <div class="flex items-center gap-3 text-sm">
                        @auth
                            <span class="hidden text-muted sm:inline">{{ auth()->user()->name }}</span>
                            <a href="{{ route('profile.edit') }}" class="font-medium text-ink hover:text-brand">
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="font-medium text-ink hover:text-brand">
                                    Sign out
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-muted hover:text-ink">Sign in</a>
                        @endauth
                    </div>
                </header>

                <main class="flex-1 p-4 md:p-6">
                    <x-toast />
                    @yield('content')
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
