<?php

declare(strict_types=1);

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductForm extends Component
{
    public ?int $productId = null;

    public string $name = '';

    public string $sku = '';

    public ?string $barcode = null;

    public ?int $category_id = null;

    public string $selling_price = '';

    public ?string $cost_price = null;

    public string $stock_quantity = '0';

    public bool $active = true;

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            $this->authorize('update', $product);

            $this->productId = $product->id;
            $this->name = $product->name;
            $this->sku = $product->sku;
            $this->barcode = $product->barcode;
            $this->category_id = $product->category_id;
            $this->selling_price = (string) $product->selling_price;
            $this->cost_price = $product->cost_price !== null ? (string) $product->cost_price : null;
            $this->stock_quantity = (string) $product->stock_quantity;
            $this->active = $product->active;
        } else {
            $this->authorize('create', Product::class);
            $this->active = true;
            $this->stock_quantity = '0';
        }
    }

    public function save(): void
    {
        $this->barcode = $this->normalizeOptionalString($this->barcode);
        $this->cost_price = $this->normalizeOptionalString($this->cost_price);

        $data = $this->validate();
        $data['barcode'] = $this->barcode;
        $data['cost_price'] = $this->cost_price;

        if ($this->productId !== null) {
            $target = Product::query()->findOrFail($this->productId);
            $this->authorize('update', $target);
            $target->update($data);
            session()->flash('success', 'Product updated.');
        } else {
            $this->authorize('create', Product::class);
            Product::query()->create($data);
            session()->flash('success', 'Product created.');
        }

        $this->redirect(route('products.index'));
    }

    public function isEditing(): bool
    {
        return $this->productId !== null;
    }

    /**
     * @return Collection<int, Category>
     */
    public function assignableCategories(): Collection
    {
        $categories = Category::query()->active()->orderBy('name')->orderBy('id')->get();

        if ($this->category_id !== null) {
            $current = Category::query()->find($this->category_id);
            if ($current !== null && $categories->doesntContain('id', $current->id)) {
                $categories->prepend($current);
            }
        }

        return $categories;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category must be active.',
            'selling_price.min' => 'The selling price cannot be negative.',
            'stock_quantity.min' => 'Stock quantity cannot be negative.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($this->productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($this->productId),
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('active', true),
            ],
            'selling_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'stock_quantity' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'active' => ['boolean'],
        ];
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function render(): View
    {
        $heading = $this->isEditing() ? 'Edit product' : 'New product';

        return view('livewire.catalog.product-form')->extends('layouts.app', [
            'heading' => $heading,
            'title' => $heading.' — '.config('app.name'),
        ])->section('content');
    }
}
