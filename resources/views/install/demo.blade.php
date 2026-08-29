@extends('layouts.install')

@section('title', 'Demo data — Setup')
@section('heading', 'Optional demo data')
@section('subheading', 'Load sample products for testing. Leave this off for a production shop.')

@section('content')
    <form method="POST" action="{{ route('install.demo.store') }}" class="space-y-4">
        @csrf

        <div class="rounded-md border border-line bg-canvas px-3 py-3">
            <label class="flex items-start gap-3 text-sm text-ink">
                <input
                    type="checkbox"
                    name="demo"
                    value="1"
                    class="mt-1 rounded border-line text-brand focus:ring-brand"
                    @checked(old('demo'))
                />
                <span>
                    Load sample products and demo staff accounts
                    <span class="mt-1 block text-muted">Sample products, not production. Does not replace the Owner you just created.</span>
                </span>
            </label>
            @error('demo')
                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <x-button type="submit" class="w-full">Continue</x-button>
    </form>

    <a
        href="{{ route('install.settings') }}"
        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
    >
        Back to store settings
    </a>
@endsection
