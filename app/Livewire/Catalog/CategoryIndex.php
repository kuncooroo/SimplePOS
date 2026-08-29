<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public ?int $pendingDeactivateId = null;

    public ?string $pendingDeactivateName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDeactivate(int $categoryId): void
    {
        $target = Category::query()->findOrFail($categoryId);
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

        $target = Category::query()->findOrFail($this->pendingDeactivateId);
        $this->authorize('changeStatus', $target);

        $target->update(['active' => false]);

        $this->cancelDeactivate();
        session()->flash('success', 'Category deactivated.');
    }

    public function activate(int $categoryId): void
    {
        $target = Category::query()->findOrFail($categoryId);
        $this->authorize('changeStatus', $target);

        $target->update(['active' => true]);

        session()->flash('success', 'Category activated.');
    }

    public function render(): View
    {
        $query = Category::query()->orderBy('name')->orderBy('id');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where('name', 'like', $like);
        }

        if ($this->status === 'active') {
            $query->where('active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('active', false);
        }

        return view('livewire.catalog.category-index', [
            'categories' => $query->paginate(15),
            'hasSearch' => $term !== '',
            'hasStatusFilter' => $this->status !== 'all',
        ])->extends('layouts.app', [
            'heading' => 'Categories',
            'title' => 'Categories — '.config('app.name'),
        ])->section('content');
    }
}
