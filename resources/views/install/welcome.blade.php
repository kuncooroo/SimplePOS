@extends('layouts.install')

@section('title', 'Welcome — Setup')
@section('heading', 'Welcome')
@section('subheading', 'This one-time setup prepares your store before anyone can sign in.')

@section('content')
    <p class="text-sm text-muted">
        You will configure the server, database, and the protected Owner account for this single-store installation.
    </p>

    <form method="POST" action="{{ route('install.welcome.continue') }}" class="mt-6">
        @csrf
        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand/90 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2"
        >
            Continue
        </button>
    </form>
@endsection
