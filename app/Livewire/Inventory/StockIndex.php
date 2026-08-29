<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Support\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class StockIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: false)]
    public bool $lowStockOnly = false;

    public function mount(): void
    {
        $this->authorize('viewInventory');
    }

    public function updatingSearch(): void
    {
        $this->validateOnly('search');
        $this->resetPage();
    }

    public function updatedLowStockOnly(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->lowStockOnly = false;
        $this->resetPage();
        $this->resetErrorBag();
    }

    public function openAdjustStock(int $productId): void
    {
        $this->authorize('adjustStock');
        $this->dispatch('open-adjust-stock', productId: $productId);
    }

    #[On('stock-adjusted')]
    public function refreshAfterAdjustment(): void
    {
        $this->authorize('viewInventory');
    }

    public function render(): View
    {
        $settings = StoreSettings::current();
        $threshold = (string) $settings->low_stock_threshold;
        $searchTerm = trim($this->search);
        $hasFilters = $searchTerm !== '' || $this->lowStockOnly;

        $products = $this->baseQuery($threshold)
            ->paginate(self::PER_PAGE);

        return view('livewire.inventory.stock-index', [
            'products' => $products,
            'threshold' => $threshold,
            'hasFilters' => $hasFilters,
            'hasSearch' => $searchTerm !== '',
        ])->extends('layouts.app', [
            'heading' => 'Inventory',
            'title' => 'Inventory — '.config('app.name'),
        ])->section('content');
    }

    /**
     * @return Builder<Product>
     */
    private function baseQuery(string $threshold): Builder
    {
        $query = Product::query()
            ->with('category')
            ->orderBy('name')
            ->orderBy('id');

        $searchTerm = trim($this->search);
        if ($searchTerm !== '') {
            $like = '%'.addcslashes($searchTerm, '%_\\').'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            });
        }

        if ($this->lowStockOnly) {
            $query->where('stock_quantity', '<=', $threshold);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'lowStockOnly' => ['boolean'],
        ];
    }
}
