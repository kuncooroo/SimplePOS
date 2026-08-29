<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    #[Url(except: '')]
    public string $invoice = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    #[Url(except: '')]
    public string $cashierId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Transaction::class);
    }

    public function updatedInvoice(): void
    {
        $this->validateOnly('invoice');
        $this->resetPage();
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

    public function updatedCashierId(): void
    {
        if (! $this->canFilterByCashier()) {
            $this->cashierId = '';

            return;
        }

        $this->validateOnly('cashierId');
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->invoice = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->cashierId = '';
        $this->resetPage();
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $this->validateDateRange();

        $invoiceTerm = trim($this->invoice);
        $hasFilters = $invoiceTerm !== ''
            || $this->dateFrom !== ''
            || $this->dateTo !== ''
            || ($this->canFilterByCashier() && $this->cashierId !== '');

        $transactions = $this->baseQuery()
            ->paginate(self::PER_PAGE);

        return view('livewire.sales.transaction-index', [
            'transactions' => $transactions,
            'cashiers' => $this->canFilterByCashier()
                ? User::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])
                : collect(),
            'hasFilters' => $hasFilters,
            'canFilterByCashier' => $this->canFilterByCashier(),
        ])->extends('layouts.app', [
            'heading' => 'Transactions',
            'title' => 'Transactions — '.config('app.name'),
        ])->section('content');
    }

    /**
     * @return Builder<Transaction>
     */
    private function baseQuery(): Builder
    {
        $query = Transaction::query()
            ->with('cashier')
            ->where('status', TransactionStatus::Completed)
            ->orderByDesc('completed_at')
            ->orderByDesc('id');

        if (! $this->canViewAll()) {
            $query->where('cashier_id', Auth::id());
        }

        $invoiceTerm = trim($this->invoice);
        if ($invoiceTerm !== '') {
            $like = '%'.addcslashes($invoiceTerm, '%_\\').'%';
            $query->where('invoice_number', 'like', $like);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('completed_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('completed_at', '<=', $this->dateTo);
        }

        if ($this->canFilterByCashier() && $this->cashierId !== '') {
            $query->where('cashier_id', $this->cashierId);
        }

        return $query;
    }

    private function canViewAll(): bool
    {
        return Auth::user()?->can('viewAllTransactions') === true;
    }

    private function canFilterByCashier(): bool
    {
        return $this->canViewAll();
    }

    private function validateDateRange(): void
    {
        $this->validateOnly('dateFrom');
        $this->validateOnly('dateTo');

        if ($this->dateFrom !== '' && $this->dateTo !== '' && $this->dateFrom > $this->dateTo) {
            $this->addError('dateTo', 'The end date must be on or after the start date.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'invoice' => ['nullable', 'string', 'max:64'],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date'],
            'cashierId' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
