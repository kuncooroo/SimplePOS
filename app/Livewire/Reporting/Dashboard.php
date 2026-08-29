<?php

declare(strict_types=1);

namespace App\Livewire\Reporting;

use App\Queries\Reporting\DashboardMetricsQuery;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $this->authorize('viewDashboard');
    }

    public function render(DashboardMetricsQuery $metricsQuery): View
    {
        return view('livewire.reporting.dashboard', [
            'metrics' => $metricsQuery->execute(),
        ])->extends('layouts.app', [
            'heading' => 'Dashboard',
            'title' => 'Dashboard — '.config('app.name'),
        ])->section('content');
    }
}
