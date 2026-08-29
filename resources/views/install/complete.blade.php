@extends('layouts.install')



@section('title', 'Complete — Setup')

@section('heading', 'Setup complete')

@section('subheading', 'Finish installation to lock the wizard and sign in as Owner.')



@section('content')

    @if (session('status') === 'demo-seeded')

        <x-alert type="success">Demo catalog data was loaded.</x-alert>

    @elseif (session('status') === 'demo-skipped-transactions')

        <x-alert type="error">Demo data was skipped because completed sales already exist in this database.</x-alert>

    @else

        <x-alert type="success">Core setup steps are complete.</x-alert>

    @endif



    <form method="POST" action="{{ route('install.complete.store') }}" class="mt-6">

        @csrf



        <x-button type="submit" class="w-full">Finish and go to sign in</x-button>

    </form>



    <a

        href="{{ route('install.demo') }}"

        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"

    >

        Back to demo data

    </a>

@endsection

