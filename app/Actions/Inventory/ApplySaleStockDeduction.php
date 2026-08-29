<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApplySaleStockDeduction
{
    public function execute(
        Product $product,
        User $actor,
        Transaction $transaction,
        string $quantitySold,
    ): StockMovement {
        $quantityBefore = $this->formatQuantity((string) $product->stock_quantity);
        $quantityChange = $this->formatQuantity(bcmul($quantitySold, '-1', 3));
        $quantityAfter = $this->formatQuantity(bcadd($quantityBefore, $quantityChange, 3));

        if (bccomp($quantityAfter, '0', 3) < 0) {
            throw ValidationException::withMessages([
                'stock' => 'Insufficient stock for '.$product->name.'.',
            ]);
        }

        $product->stock_quantity = $quantityAfter;
        $product->save();

        return StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $actor->id,
            'transaction_id' => $transaction->id,
            'movement_type' => StockMovementType::Sale,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'reason' => null,
            'occurred_at' => $transaction->completed_at,
        ]);
    }

    private function formatQuantity(string $quantity): string
    {
        return bcadd($quantity, '0', 3);
    }
}
