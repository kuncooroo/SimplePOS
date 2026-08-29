<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Actions\Inventory\ApplySaleStockDeduction;
use App\Enums\TransactionStatus;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\Sales\CheckoutCalculation;
use App\Services\Sales\CheckoutCalculator;
use App\Support\Sales\CheckoutPayload;
use App\Support\Sales\InvoiceNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompleteSale
{
    public function __construct(
        private CheckoutCalculator $checkoutCalculator,
        private InvoiceNumberGenerator $invoiceNumberGenerator,
        private ApplySaleStockDeduction $applySaleStockDeduction,
    ) {}

    /**
     * @param  array<int, string>  $cartItems
     */
    public function execute(User $actor, array $cartItems, string $discount, string $cashReceived): Transaction
    {
        Gate::forUser($actor)->authorize('accessPos');

        if ($cartItems === []) {
            throw ValidationException::withMessages([
                'checkout' => 'Add at least one product before checkout.',
            ]);
        }

        $payload = new CheckoutPayload(
            items: $cartItems,
            discount: $discount,
            cashReceived: $cashReceived,
        );

        return DB::transaction(function () use ($actor, $payload): Transaction {
            $resolvedLines = $this->resolveLines($payload->items);
            $lineTotals = array_map(
                fn (array $line): string => $line['line_total'],
                $resolvedLines,
            );

            $calculation = $this->checkoutCalculator->calculateFromLines(
                $lineTotals,
                $payload->discount,
                $payload->cashReceived,
            );

            if (bccomp($calculation->subtotal, '0', 2) <= 0) {
                throw ValidationException::withMessages([
                    'checkout' => 'Add at least one product before checkout.',
                ]);
            }

            if (! $calculation->paymentSufficient) {
                throw ValidationException::withMessages([
                    'payment' => 'Cash received is less than the amount due.',
                ]);
            }

            $completedAt = now();
            $transaction = $this->createTransaction(
                actor: $actor,
                calculation: $calculation,
                completedAt: $completedAt,
            );

            foreach ($resolvedLines as $line) {
                TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $line['product']->id,
                    'product_name_snapshot' => $line['product']->name,
                    'sku_snapshot' => $line['product']->sku,
                    'barcode_snapshot' => $line['product']->barcode,
                    'unit_price' => $line['product']->selling_price,
                    'quantity' => $line['quantity'],
                    'line_subtotal' => $line['line_subtotal'],
                    'line_discount' => '0.00',
                    'line_total' => $line['line_total'],
                ]);

                $this->applySaleStockDeduction->execute(
                    product: $line['product'],
                    actor: $actor,
                    transaction: $transaction,
                    quantitySold: $line['quantity'],
                );
            }

            return $transaction->load(['items', 'cashier']);
        });
    }

    /**
     * @param  array<int, string>  $cartItems
     * @return list<array{product: Product, quantity: string, line_subtotal: string, line_total: string}>
     */
    private function resolveLines(array $cartItems): array
    {
        $productIds = array_map(intval(...), array_keys($cartItems));

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $resolvedLines = [];

        foreach ($cartItems as $productId => $rawQuantity) {
            $product = $products->get((int) $productId);

            if ($product === null) {
                throw ValidationException::withMessages([
                    'cart' => 'A product in your cart is no longer available.',
                ]);
            }

            if (! $product->active) {
                throw ValidationException::withMessages([
                    'cart' => $product->name.' is not available for sale.',
                ]);
            }

            $quantity = $this->normalizeQuantity($rawQuantity);

            if (bccomp($quantity, '0', 3) <= 0) {
                throw ValidationException::withMessages([
                    'cart' => 'Each cart line must have a quantity greater than zero.',
                ]);
            }

            if (bccomp($quantity, (string) $product->stock_quantity, 3) > 0) {
                throw ValidationException::withMessages([
                    'stock' => 'Insufficient stock for '.$product->name.'.',
                ]);
            }

            if (bccomp((string) $product->selling_price, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    'cart' => 'Invalid price for '.$product->name.'.',
                ]);
            }

            $lineSubtotal = bcmul((string) $product->selling_price, $quantity, 2);

            $resolvedLines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'line_subtotal' => $lineSubtotal,
                'line_total' => $lineSubtotal,
            ];
        }

        if ($resolvedLines === []) {
            throw ValidationException::withMessages([
                'checkout' => 'Add at least one product before checkout.',
            ]);
        }

        return $resolvedLines;
    }

    private function createTransaction(User $actor, CheckoutCalculation $calculation, Carbon $completedAt): Transaction
    {
        $attempts = 0;

        while ($attempts < 3) {
            try {
                return Transaction::query()->create([
                    'cashier_id' => $actor->id,
                    'invoice_number' => $this->invoiceNumberGenerator->generate(),
                    'status' => TransactionStatus::Completed,
                    'subtotal' => $calculation->subtotal,
                    'discount' => $calculation->discount,
                    'total' => $calculation->total,
                    'cash_received' => $calculation->cashReceived,
                    'change_amount' => $calculation->change,
                    'completed_at' => $completedAt,
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueInvoiceViolation($exception) && $attempts < 2) {
                    $attempts++;

                    continue;
                }

                throw $exception;
            }
        }

        throw ValidationException::withMessages([
            'checkout' => 'Could not assign a unique invoice number. Please try again.',
        ]);
    }

    private function normalizeQuantity(string $quantity): string
    {
        $trimmed = trim($quantity);

        if ($trimmed === '' || ! is_numeric($trimmed)) {
            throw ValidationException::withMessages([
                'cart' => 'Each cart line must have a valid quantity.',
            ]);
        }

        return bcadd($trimmed, '0', 3);
    }

    private function isUniqueInvoiceViolation(QueryException $exception): bool
    {
        $errorCode = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($errorCode, [1062, 19], true);
    }
}
