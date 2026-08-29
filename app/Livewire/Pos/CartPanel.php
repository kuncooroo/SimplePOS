<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class CartPanel extends Component
{
    /**
     * @var array<int, string>
     */
    public array $items = [];

    #[On('cart-clear')]
    public function clearCart(): void
    {
        $this->authorizePosAccess();
        $this->items = [];
        $this->resetErrorBag('cart');
        $this->broadcastCartState();
    }

    public function mount(): void
    {
        $this->authorizePosAccess();
    }

    #[On('add-to-cart')]
    public function addToCart(int $productId): void
    {
        $this->authorizePosAccess();

        $product = Product::query()->whereKey($productId)->firstOrFail();

        if (! $product->active) {
            $this->addError('cart', 'This product is not available for sale.');

            return;
        }

        $currentQuantity = (float) ($this->items[$productId] ?? 0);
        $newQuantity = $currentQuantity + 1;

        if ($this->exceedsStock($product, $newQuantity)) {
            $this->addError('cart', 'Insufficient stock for '.$product->name.'.');

            return;
        }

        $this->items[$productId] = $this->formatQuantity($newQuantity);
        $this->resetErrorBag('cart');
        $this->broadcastCartState();
    }

    public function incrementQuantity(int $productId): void
    {
        $this->authorizePosAccess();

        if (! array_key_exists($productId, $this->items)) {
            return;
        }

        $product = $this->requireActiveProduct($productId);

        if ($product === null) {
            return;
        }

        $newQuantity = (float) $this->items[$productId] + 1;

        if ($this->exceedsStock($product, $newQuantity)) {
            $this->addError('cart', 'Insufficient stock for '.$product->name.'.');

            return;
        }

        $this->items[$productId] = $this->formatQuantity($newQuantity);
        $this->resetErrorBag('cart');
        $this->broadcastCartState();
    }

    public function decrementQuantity(int $productId): void
    {
        $this->authorizePosAccess();

        if (! array_key_exists($productId, $this->items)) {
            return;
        }

        $product = $this->requireActiveProduct($productId);

        if ($product === null) {
            return;
        }

        $newQuantity = (float) $this->items[$productId] - 1;

        if ($newQuantity <= 0) {
            $this->removeLine($productId);

            return;
        }

        $this->items[$productId] = $this->formatQuantity($newQuantity);
        $this->resetErrorBag('cart');
        $this->broadcastCartState();
    }

    public function removeLine(int $productId): void
    {
        $this->authorizePosAccess();

        unset($this->items[$productId]);
        $this->resetErrorBag('cart');
        $this->broadcastCartState();
    }

    /**
     * @return Collection<int, array{product: Product, quantity: string, line_total: string}>
     */
    public function resolvedLines(): Collection
    {
        if ($this->items === []) {
            return collect();
        }

        $products = Product::query()
            ->select(['id', 'name', 'sku', 'selling_price', 'stock_quantity', 'active'])
            ->whereIn('id', array_keys($this->items))
            ->get()
            ->keyBy('id');

        return collect($this->items)
            ->map(function (string $quantity, int $productId) use ($products): ?array {
                $product = $products->get($productId);

                if ($product === null || ! $product->active) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => bcmul((string) $product->selling_price, $quantity, 2),
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): string
    {
        return $this->resolvedLines()
            ->reduce(
                fn (string $total, array $line): string => bcadd($total, $line['line_total'], 2),
                '0.00',
            );
    }

    public function render(): View
    {
        $lines = $this->resolvedLines();

        return view('livewire.pos.cart-panel', [
            'lines' => $lines,
            'subtotal' => $this->subtotal(),
            'isEmpty' => $lines->isEmpty(),
        ]);
    }

    private function authorizePosAccess(): void
    {
        Gate::authorize('accessPos');
    }

    private function requireActiveProduct(int $productId): ?Product
    {
        $product = Product::query()->whereKey($productId)->first();

        if ($product === null || ! $product->active) {
            unset($this->items[$productId]);
            $this->addError('cart', 'A product in your cart is no longer available and was removed.');

            return null;
        }

        return $product;
    }

    private function exceedsStock(Product $product, float $quantity): bool
    {
        return $quantity > (float) $product->stock_quantity;
    }

    private function formatQuantity(float $quantity): string
    {
        return number_format($quantity, 3, '.', '');
    }

    private function broadcastCartState(): void
    {
        $lines = $this->resolvedLines();

        $this->dispatch(
            'cart-state-updated',
            subtotal: $this->subtotal(),
            isEmpty: $lines->isEmpty(),
            items: $this->items,
        );
    }
}
