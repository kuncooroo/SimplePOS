<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: '')]
    public string $categoryId = '';

    public ?int $pendingDeactivateId = null;

    public ?string $pendingDeactivateName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function confirmDeactivate(int $productId): void
    {
        $target = Product::query()->findOrFail($productId);
        $this->authorize('changeStatus', $target);
        $this->pendingDeactivateId = $target->id;
        $this->pendingDeactivateName = $target->name;
    }

    public function cancelDeactivate(): void
    {
        $this->pendingDeactivateId = null;
        $this->pendingDeactivateName = null;
    }

    public function deactivate(): void
    {
        if ($this->pendingDeactivateId === null) {
            return;
        }

        $target = Product::query()->findOrFail($this->pendingDeactivateId);
        $this->authorize('changeStatus', $target);

        $target->update(['active' => false]);

        $this->cancelDeactivate();
        session()->flash('success', 'Product deactivated.');
    }

    public function activate(int $productId): void
    {
        $target = Product::query()->findOrFail($productId);
        $this->authorize('changeStatus', $target);

        $target->update(['active' => true]);

        session()->flash('success', 'Product activated.');
    }

    public function render(): View
    {
        $query = Product::query()
            ->with('category')
            ->orderBy('name')
            ->orderBy('id');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like);
            });
        }

        if ($this->status === 'active') {
            $query->where('active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('active', false);
        }

        if ($this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        return view('livewire.catalog.product-index', [
            'products' => $query->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
            'hasSearch' => $term !== '',
            'hasFilters' => $this->status !== 'all' || $this->categoryId !== '',
        ])->extends('layouts.app', [
            'heading' => 'Products',
            'title' => 'Products — '.config('app.name'),
        ])->section('content');
    }
}
