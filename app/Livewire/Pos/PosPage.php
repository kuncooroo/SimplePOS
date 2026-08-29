<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PosPage extends Component
{
    public function mount(): void
    {
        Gate::authorize('accessPos');
    }

    public function render(): View
    {
        return view('livewire.pos.pos-page')->extends('layouts.app', [
            'heading' => 'Point of sale',
            'title' => 'POS — '.config('app.name'),
        ])->section('content');
    }
}
