@extends('layouts.install')

@section('title', 'Owner — Setup')
@section('heading', 'Protected Owner account')
@section('subheading', 'Create the single protected Owner for this store. This is not an Administrator account.')

@section('content')
    @if (session('status') === 'owner-saved')
        <x-alert type="success">Owner details saved. They will be created when you finish installation.</x-alert>
    @endif

    @if ($ownerSaved ?? false)
        <x-alert type="success">Owner details are ready. Continue to store settings.</x-alert>
        <a
            href="{{ route('install.settings') }}"
            class="mt-6 inline-flex w-full items-center justify-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand/90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            Continue to store settings
        </a>
    @elseif ($ownerExists ?? false)
        <x-alert type="success">An Owner account already exists. Continue to store settings.</x-alert>
        <a
            href="{{ route('install.settings') }}"
            class="mt-6 inline-flex w-full items-center justify-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand/90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            Continue to store settings
        </a>
    @else
        <form method="POST" action="{{ route('install.owner.store') }}" class="space-y-4">
            @csrf

            <div>
                <x-input
                    label="Full name"
                    type="text"
                    name="name"
                    id="name"
                    :value="old('name')"
                    required
                />
                @error('name')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-input
                    label="Email"
                    type="email"
                    name="email"
                    id="email"
                    :value="old('email')"
                    autocomplete="username"
                    required
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
                    autocomplete="new-password"
                    required
                />
                @error('password')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-input
                    label="Confirm password"
                    type="password"
                    name="password_confirmation"
                    id="password_confirmation"
                    autocomplete="new-password"
                    required
                />
            </div>

            <x-button type="submit" class="w-full">Save Owner details</x-button>
        </form>
    @endif

    <a
        href="{{ route('install.migrate') }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
    >
        Back to migrations
    </a>
@endsection
