@extends('layouts.guest')

@section('title', 'Access denied — '.config('app.name'))
@section('heading', 'Access denied')

@section('content')
    <p class="text-sm text-muted">You do not have permission to view this page.</p>
    <p class="mt-4">
        <a href="{{ url('/') }}" class="text-sm font-medium text-brand hover:text-brand-hover">Continue</a>
    </p>
@endsection
