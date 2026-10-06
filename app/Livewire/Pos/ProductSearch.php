<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ProductSearch extends Component
{
    public const RESULT_LIMIT = 20;

    public string $search = '';

    public ?int $selectedProductId = null;

    public function mount(): void
    {
        $this->authorizePosAccess();
    }

    public function updatedSearch(): void
    {
        $this->authorizePosAccess();
        $this->validateOnly('search');
        $this->selectedProductId = null;
    }

    public function selectProduct(int $productId): void
    {
        $this->authorizePosAccess();

        $product = Product::query()
            ->active()
            ->whereKey($productId)
            ->firstOrFail();

        $this->dispatchProductSelected($product);
    }

    /**
     * Cashier-scanner path: Enter after an exact barcode match adds the product and clears the field.
     */
    public function confirmScan(?string $scanned = null): void
    {
        $this->authorizePosAccess();

        $term = trim((string) ($scanned ?? $this->search));
        $this->search = $term;
        $this->selectedProductId = null;
        $this->resetErrorBag('search');

        if ($term === '') {
            $this->refocusSearch();

            return;
        }

        $this->validateOnly('search');

        $product = Product::query()
            ->active()
            ->where('barcode', $term)
            ->first();

        if ($product === null) {
            $this->refocusSearch();

            return;
        }

        $this->dispatchProductSelected($product);
        $this->search = '';
        $this->selectedProductId = null;
        $this->refocusSearch();
    }

    /**
     * @return Collection<int, Product>
     */
    public function results(): Collection
    {
        $term = trim($this->search);

        if ($term === '') {
            return collect();
        }

        $baseQuery = Product::query()
            ->active()
            ->select([
                'id',
                'name',
                'sku',
                'barcode',
                'selling_price',
                'stock_quantity',
            ]);

        $exactBarcode = (clone $baseQuery)
            ->where('barcode', $term)
            ->limit(self::RESULT_LIMIT)
            ->get();

        if ($exactBarcode->isNotEmpty()) {
            return $exactBarcode;
        }

        $exactSku = (clone $baseQuery)
            ->where('sku', $term)
            ->limit(self::RESULT_LIMIT)
            ->get();

        if ($exactSku->isNotEmpty()) {
            return $exactSku;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $baseQuery
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function render(): View
    {
        $term = trim($this->search);

        return view('livewire.pos.product-search', [
            'products' => $this->results(),
            'hasQuery' => $term !== '',
        ]);
    }

    private function dispatchProductSelected(Product $product): void
    {
        $this->selectedProductId = $product->id;
        $this->dispatch('product-selected', productId: $product->id);
        $this->dispatch('add-to-cart', productId: $product->id);
    }

    private function refocusSearch(): void
    {
        $this->js('document.getElementById("pos-search")?.focus()');
    }

    private function authorizePosAccess(): void
    {
        Gate::authorize('accessPos');
    }
}
