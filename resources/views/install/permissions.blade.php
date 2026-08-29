@extends('layouts.install')

@section('title', 'Permissions — Setup')
@section('heading', 'Folder permissions')
@section('subheading', 'The PHP process must be able to write cache, session, and log folders.')

@section('content')
    @include('install.partials.check-list', [
        'checks' => $report->checks,
        'canContinue' => $canContinue,
        'continueRoute' => route('install.permissions.continue'),
        'backRoute' => route('install.extensions'),
        'backLabel' => 'Back to PHP extensions',
    ])
@endsection
