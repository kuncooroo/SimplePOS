@extends('layouts.install')

@section('title', 'Database — Setup')
@section('heading', 'Database connection')
@section('subheading', 'Enter MySQL credentials. The connection is tested before anything is saved.')

@section('content')
    @if (session('status') === 'database-verified')
        <x-alert type="success">Database connection verified.</x-alert>
    @endif

    @if ($errors->has('database'))
        <x-alert type="error">{{ $errors->first('database') }}</x-alert>
    @endif

    @if ($connectionVerified ?? false)
        <p class="mb-4 rounded-md border border-line bg-canvas px-3 py-2 text-sm text-muted">
            A database connection was verified in this setup session. Submit again to update credentials or continue to the environment step.
        </p>
        <a
            href="{{ route('install.environment') }}"
            class="mb-4 inline-flex w-full items-center justify-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand/90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            Continue to environment
        </a>
    @endif

    <form method="POST" action="{{ route('install.database.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input
                label="Host"
                type="text"
                name="db_host"
                id="db_host"
                :value="old('db_host', '127.0.0.1')"
                required
            />
            @error('db_host')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="Port"
                type="number"
                name="db_port"
                id="db_port"
                :value="old('db_port', '3306')"
                min="1"
                max="65535"
                required
            />
            @error('db_port')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="Database name"
                type="text"
                name="db_database"
                id="db_database"
                :value="old('db_database', 'simplepos')"
                required
            />
            @error('db_database')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="Username"
                type="text"
                name="db_username"
                id="db_username"
                :value="old('db_username', 'root')"
                autocomplete="off"
                required
            />
            @error('db_username')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input
                label="Password"
                type="password"
                name="db_password"
                id="db_password"
                autocomplete="new-password"
            />
            @error('db_password')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <x-button type="submit" class="w-full">Test connection and continue</x-button>
    </form>

    <a
        href="{{ route('install.permissions') }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
    >
        Back to folder permissions
    </a>
@endsection
