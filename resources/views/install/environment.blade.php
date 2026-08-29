@extends('layouts.install')

@section('title', 'Environment — Setup')
@section('heading', 'Application environment')
@section('subheading', 'Set the public URL and runtime defaults before migrations run.')

@section('content')
    @if (session('status') === 'environment-saved')
        <x-alert type="success">Environment saved and the application key is ready for the next setup step.</x-alert>
    @endif

    @if ($errors->has('environment'))
        <x-alert type="error">{{ $errors->first('environment') }}</x-alert>
    @endif

    @if ($environmentWritten ?? false)
        <p class="mb-4 rounded-md border border-line bg-canvas px-3 py-2 text-sm text-muted">
            The environment file was written in this setup session. Continue to database migrations next.
        </p>
    @endif

    <form method="POST" action="{{ route('install.environment.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input
                label="Application URL"
                type="url"
                name="app_url"
                id="app_url"
                :value="old('app_url', 'http://localhost:8000')"
                required
            />
            @error('app_url')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="timezone" class="mb-1 block text-sm font-medium text-ink">Timezone</label>
            <select
                id="timezone"
                name="timezone"
                class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand"
                required
            >
                @foreach (timezone_identifiers_list() as $timezone)
                    <option value="{{ $timezone }}" @selected(old('timezone', 'Asia/Jakarta') === $timezone)>
                        {{ $timezone }}
                    </option>
                @endforeach
            </select>
            @error('timezone')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="app_env" class="mb-1 block text-sm font-medium text-ink">Application environment</label>
            <select
                id="app_env"
                name="app_env"
                class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand"
                required
            >
                <option value="production" @selected(old('app_env', 'production') === 'production')>Production</option>
                <option value="local" @selected(old('app_env') === 'local')>Local</option>
            </select>
            @error('app_env')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-md border border-line bg-canvas px-3 py-3">
            <label class="flex items-start gap-3 text-sm text-ink">
                <input
                    type="checkbox"
                    name="app_debug"
                    value="1"
                    class="mt-1 rounded border-line text-brand focus:ring-brand"
                    @checked(old('app_debug'))
                />
                <span>
                    Enable debug mode
                    <span class="mt-1 block text-muted">Leave this off for a live shop. Debug mode can expose sensitive details to visitors.</span>
                </span>
            </label>
            @error('app_debug')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <x-button type="submit" class="w-full">Save environment</x-button>
    </form>

    <a
        href="{{ route('install.database') }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
    >
        Back to database connection
    </a>
@endsection
