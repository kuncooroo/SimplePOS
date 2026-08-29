<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MovementIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $movementType = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', StockMovement::class);
    }

    public function updatingSearch(): void
    {
        $this->validateOnly('search');
        $this->resetPage();
    }

    public function updatedMovementType(): void
    {
        $this->validateOnly('movementType');
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

    public function clearFilters(): void
    {
        $this->search = '';
        $this->movementType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $this->validateDateRange();

        $searchTerm = trim($this->search);
        $hasFilters = $searchTerm !== ''
            || $this->movementType !== ''
            || $this->dateFrom !== ''
            || $this->dateTo !== '';

        $movements = $this->baseQuery()
            ->paginate(self::PER_PAGE);

        return view('livewire.inventory.movement-index', [
            'movements' => $movements,
            'movementTypes' => StockMovementType::cases(),
            'hasFilters' => $hasFilters,
        ])->extends('layouts.app', [
            'heading' => 'Stock movements',
            'title' => 'Stock movements — '.config('app.name'),
        ])->section('content');
    }

    /**
     * @return Builder<StockMovement>
     */
    private function baseQuery(): Builder
    {
        $query = StockMovement::query()
            ->with([
                'product',
                'user',
                'transaction' => fn ($builder) => $builder->select(['id', 'invoice_number']),
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $searchTerm = trim($this->search);
        if ($searchTerm !== '') {
            $like = '%'.addcslashes($searchTerm, '%_\\').'%';
            $query->whereHas('product', function (Builder $builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        if ($this->movementType !== '') {
            $query->where('movement_type', $this->movementType);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('occurred_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('occurred_at', '<=', $this->dateTo);
        }

        return $query;
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
            'search' => ['nullable', 'string', 'max:100'],
            'movementType' => ['nullable', 'string', Rule::in(array_map(
                fn (StockMovementType $type): string => $type->value,
                StockMovementType::cases(),
            ))],
            'dateFrom' => ['nullable', 'date'],
            'dateTo' => ['nullable', 'date'],
        ];
    }
}
