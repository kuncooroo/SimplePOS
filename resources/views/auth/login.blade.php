@extends('layouts.guest')

@section('title', 'Sign in — '.config('app.name'))
@section('heading', 'Sign in')

@section('content')
    @if (session('installation_complete'))
        <x-alert type="success">Installation complete. Sign in as Owner.</x-alert>
    @endif

    @if ($errors->has('credentials'))
        <x-alert type="error">{{ $errors->first('credentials') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input
                label="Email"
                type="email"
                name="email"
                id="email"
                :value="old('email')"
                autocomplete="username"
                required
                autofocus
            />
            @error('email')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="Password"
                type="password"
                name="password"
                id="password"
                autocomplete="current-password"
                required
            />
            @error('password')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <x-button type="submit" class="w-full">Sign in</x-button>
    </form>
@endsection
