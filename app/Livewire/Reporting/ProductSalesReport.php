<?php

declare(strict_types=1);

namespace App\Livewire\Reporting;

use App\Queries\Reporting\ProductSalesQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductSalesReport extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    public const MAX_RANGE_DAYS = 366;

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('viewReports');

        $today = now()->timezone((string) config('app.timezone'))->toDateString();

        if ($this->dateFrom === '') {
            $this->dateFrom = $today;
        }

        if ($this->dateTo === '') {
            $this->dateTo = $this->dateFrom;
        }
    }

    public function updatedDateFrom(): void
    {
        $this->validateDateRange();
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->validateDateRange();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $today = now()->timezone((string) config('app.timezone'))->toDateString();
        $this->dateFrom = $today;
        $this->dateTo = $today;
        $this->resetPage();
        $this->resetErrorBag();
    }

    public function render(ProductSalesQuery $productSalesQuery): View
    {
        $this->validateDateRange();

        $hasRangeError = $this->getErrorBag()->has('dateTo') || $this->getErrorBag()->has('dateFrom');

        $periodTotals = $hasRangeError
            ? null
            : $productSalesQuery->periodTotals($this->dateFrom, $this->dateTo);

        $rows = $hasRangeError
            ? null
            : $productSalesQuery->paginate($this->dateFrom, $this->dateTo, self::PER_PAGE);

        return view('livewire.reporting.product-sales-report', [
            'rows' => $rows,
            'periodTotals' => $periodTotals,
            'periodLabel' => $this->periodLabel(),
            'hasCustomRange' => $this->dateFrom !== $this->dateTo
                || $this->dateFrom !== now()->timezone((string) config('app.timezone'))->toDateString(),
        ])->extends('layouts.app', [
            'heading' => 'Product sales report',
            'title' => 'Product sales report — '.config('app.name'),
        ])->section('content');
    }

    private function periodLabel(): string
    {
        if ($this->dateFrom === $this->dateTo) {
            return Carbon::parse($this->dateFrom)->format('d M Y');
        }

        return Carbon::parse($this->dateFrom)->format('d M Y')
            .' — '
            .Carbon::parse($this->dateTo)->format('d M Y');
    }

    private function validateDateRange(): void
    {
        $this->validateOnly('dateFrom');
        $this->validateOnly('dateTo');

        if ($this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom > $this->dateTo) {
            $this->addError('dateTo', 'The end date must be on or after the start date.');
        }

        if ($this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom <= $this->dateTo) {
            $timezone = (string) config('app.timezone');
            $start = Carbon::parse($this->dateFrom, $timezone)->startOfDay();
            $end = Carbon::parse($this->dateTo, $timezone)->startOfDay();

            if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
                $this->addError('dateTo', 'The date range cannot exceed '.self::MAX_RANGE_DAYS.' days.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date'],
        ];
    }
}
