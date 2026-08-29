@extends('layouts.install')

@section('title', 'Requirements — Setup')
@section('heading', 'System requirements')
@section('subheading', 'Verify the server runtime before collecting any secrets.')

@section('content')
    @include('install.partials.check-list', [
        'checks' => $report->checks,
        'canContinue' => $canContinue,
        'continueRoute' => route('install.requirements.continue'),
        'backRoute' => route('install.welcome'),
        'backLabel' => 'Back to welcome',
    ])
@endsection
