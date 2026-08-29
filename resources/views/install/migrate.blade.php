@extends('layouts.install')

@section('title', 'Migrations — Setup')
@section('heading', 'Database migrations')
@section('subheading', 'Create the application tables in your MySQL database.')

@section('content')
    @if (session('status') === 'migrate-complete')
        <x-alert type="success">Database migrations completed successfully.</x-alert>
    @endif

    @if ($errors->has('migrate'))
        <x-alert type="error">{{ $errors->first('migrate') }}</x-alert>
    @endif

    <p class="text-sm text-muted">
        This runs a standard migration pass only. It will not drop or reset existing tables.
    </p>

    <form method="POST" action="{{ route('install.migrate.store') }}" class="mt-6">
        @csrf
        <x-button type="submit" class="w-full">
            {{ ($migrationsCompleted ?? false) ? 'Run migrations again' : 'Run migrations' }}
        </x-button>
    </form>

    @if ($migrationsCompleted ?? false)
        <a
            href="{{ route('install.owner') }}"
            class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            Continue to Owner account
        </a>
    @endif

    <a
        href="{{ route('install.environment') }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
    >
        Back to environment
    </a>
@endsection
