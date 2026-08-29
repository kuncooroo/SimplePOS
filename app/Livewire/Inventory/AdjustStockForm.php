<?php

declare(strict_types=1);

namespace App\Livewire\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class AdjustStockForm extends Component
{
    public bool $showModal = false;

    public ?int $productId = null;

    public string $productName = '';

    public string $productSku = '';

    public string $currentStock = '0';

    public string $quantityChange = '';

    public string $reason = '';

    public bool $isProcessing = false;

    #[On('open-adjust-stock')]
    public function open(int $productId): void
    {
        $this->authorize('adjustStock');

        $product = Product::query()->findOrFail($productId);

        $this->productId = $product->id;
        $this->productName = $product->name;
        $this->productSku = $product->sku;
        $this->currentStock = (string) $product->stock_quantity;
        $this->quantityChange = '';
        $this->reason = '';
        $this->showModal = true;
        $this->isProcessing = false;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->authorize('adjustStock');
        $this->resetForm();
    }

    public function confirm(AdjustStock $adjustStock): void
    {
        $this->authorize('adjustStock');

        if ($this->productId === null) {
            return;
        }

        $this->validate();

        $this->isProcessing = true;

        try {
            $product = Product::query()->findOrFail($this->productId);

            $adjustStock->execute(
                actor: auth()->user(),
                product: $product,
                quantityChange: $this->quantityChange,
                reason: $this->reason,
            );
        } catch (ValidationException $exception) {
            $this->isProcessing = false;
            throw $exception;
        }

        $this->resetForm();
        session()->flash('success', 'Stock adjusted.');
        $this->dispatch('stock-adjusted');
    }

    public function render(): View
    {
        return view('livewire.inventory.adjust-stock-form');
    }

    private function resetForm(): void
    {
        $this->showModal = false;
        $this->productId = null;
        $this->productName = '';
        $this->productSku = '';
        $this->currentStock = '0';
        $this->quantityChange = '';
        $this->reason = '';
        $this->isProcessing = false;
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'quantityChange' => [
                'required',
                'numeric',
                'decimal:0,3',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (bccomp((string) $value, '0', 3) === 0) {
                        $fail('Quantity change cannot be zero.');
                    }
                },
            ],
            'reason' => [
                'required',
                'string',
                'max:1000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        $fail('A reason is required.');
                    }
                },
            ],
        ];
    }
}
