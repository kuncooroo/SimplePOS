@extends('layouts.install')

@section('title', 'Extensions — Setup')
@section('heading', 'PHP extensions')
@section('subheading', 'Required extensions must be loaded before database setup begins.')

@section('content')
    @include('install.partials.check-list', [
        'checks' => $report->checks,
        'canContinue' => $canContinue,
        'continueRoute' => route('install.extensions.continue'),
        'backRoute' => route('install.requirements'),
        'backLabel' => 'Back to system requirements',
    ])
@endsection
