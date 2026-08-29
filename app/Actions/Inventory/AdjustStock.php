<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Actions\Audit\RecordActivity;
use App\Enums\ActivityAction;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AdjustStock
{
    public function __construct(
        private RecordActivity $recordActivity,
    ) {}

    public function execute(User $actor, Product $product, string $quantityChange, string $reason): StockMovement
    {
        Gate::forUser($actor)->authorize('adjustStock');

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required.',
            ]);
        }

        $quantityChange = $this->formatQuantity($quantityChange);

        if (bccomp($quantityChange, '0', 3) === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => 'Quantity change cannot be zero.',
            ]);
        }

        return DB::transaction(function () use ($actor, $product, $quantityChange, $trimmedReason): StockMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $quantityBefore = $this->formatQuantity((string) $lockedProduct->stock_quantity);
            $quantityAfter = $this->formatQuantity(bcadd($quantityBefore, $quantityChange, 3));

            if (bccomp($quantityAfter, '0', 3) < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => 'Stock cannot fall below zero.',
                ]);
            }

            $lockedProduct->stock_quantity = $quantityAfter;
            $lockedProduct->save();

            $movement = StockMovement::query()->create([
                'product_id' => $lockedProduct->id,
                'user_id' => $actor->id,
                'transaction_id' => null,
                'movement_type' => StockMovementType::ManualAdjustment,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'reason' => $trimmedReason,
                'occurred_at' => now(),
            ]);

            $this->recordActivity->execute(
                actor: $actor,
                action: ActivityAction::StockManualAdjusted,
                subject: $lockedProduct,
                oldValues: [
                    'stock_quantity' => $quantityBefore,
                ],
                newValues: [
                    'stock_quantity' => $quantityAfter,
                ],
                context: [
                    'product_id' => $lockedProduct->id,
                    'quantity_change' => $quantityChange,
                    'reason' => $trimmedReason,
                ],
            );

            return $movement;
        });
    }

    private function formatQuantity(string $quantity): string
    {
        return bcadd($quantity, '0', 3);
    }
}
