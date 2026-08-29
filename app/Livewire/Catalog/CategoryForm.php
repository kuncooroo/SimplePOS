<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CategoryForm extends Component
{
    public ?int $categoryId = null;

    public string $name = '';

    public bool $active = true;

    public function mount(?Category $category = null): void
    {
        if ($category !== null && $category->exists) {
            $this->authorize('update', $category);

            $this->categoryId = $category->id;
            $this->name = $category->name;
            $this->active = $category->active;
        } else {
            $this->authorize('create', Category::class);
            $this->active = true;
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'active' => ['boolean'],
        ]);

        if ($this->categoryId !== null) {
            $target = Category::query()->findOrFail($this->categoryId);
            $this->authorize('update', $target);
            $target->update($data);
            session()->flash('success', 'Category updated.');
        } else {
            $this->authorize('create', Category::class);
            Category::query()->create($data);
            session()->flash('success', 'Category created.');
        }

        $this->redirect(route('categories.index'));
    }

    public function isEditing(): bool
    {
        return $this->categoryId !== null;
    }

    public function render(): View
    {
        $heading = $this->isEditing() ? 'Edit category' : 'New category';

        return view('livewire.catalog.category-form')->extends('layouts.app', [
            'heading' => $heading,
            'title' => $heading.' — '.config('app.name'),
        ])->section('content');
    }
}
