@extends('layouts.install')

@section('title', 'Store settings — Setup')
@section('heading', 'Store settings')
@section('subheading', 'Set the store name shown on receipts. Currency defaults to Indonesian Rupiah (IDR).')

@section('content')
    @if (session('status') === 'settings-saved')
        <x-alert type="success">Store settings saved.</x-alert>
    @endif

    <form method="POST" action="{{ route('install.settings.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input
                label="Store name"
                type="text"
                name="store_name"
                id="store_name"
                :value="old('store_name', 'SimplePOS Store')"
                required
            />
            @error('store_name')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="address" class="mb-1 block text-sm font-medium text-ink">Address (optional)</label>
            <textarea
                id="address"
                name="address"
                rows="3"
                class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand"
            >{{ old('address') }}</textarea>
            @error('address')
                <p class="mt-1 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <p class="rounded-md border border-line bg-canvas px-3 py-2 text-sm text-muted">
            Currency: IDR (Rp). Logo upload remains available later in store settings.
        </p>

        <x-button type="submit" class="w-full">Save store settings</x-button>
    </form>

    <a
        href="{{ route('install.owner') }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
    >
        Back to Owner account
    </a>
@endsection
