@extends('layouts.app')

@section('title', 'POS — '.config('app.name'))
@section('heading', 'Point of sale')

@section('content')
    <x-page-header title="Point of sale">
        <x-slot:description>Checkout will be added in a later task. You can still sign in and out from here.</x-slot:description>
    </x-page-header>

    <x-empty-state title="POS is not ready yet">
        Product search, cart, and checkout will appear on this screen.
    </x-empty-state>
@endsection
