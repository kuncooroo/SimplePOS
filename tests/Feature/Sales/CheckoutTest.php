<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Actions\Sales\CompleteSale;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Livewire\Pos\CartPanel;
use App\Livewire\Pos\PaymentPanel;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_is_rejected_by_complete_sale(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->expectException(ValidationException::class);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [],
            discount: '0',
            cashReceived: '0',
        );
    }

    public function test_valid_cash_sale_persists_transaction_items_stock_and_movements(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $first = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Coffee',
            'sku' => 'SKU-COF',
            'barcode' => '123456',
            'selling_price' => '15000.00',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);
        $second = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Snack',
            'sku' => 'SKU-SNACK',
            'selling_price' => '5000.00',
            'stock_quantity' => '4.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [
                $first->id => '2.000',
                $second->id => '1.000',
            ],
            discount: '2000.00',
            cashReceived: '40000.00',
        );

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('transaction_items', 2);
        $this->assertDatabaseCount('stock_movements', 2);

        $transaction->refresh();
        $first->refresh();
        $second->refresh();

        $this->assertSame(TransactionStatus::Completed, $transaction->status);
        $this->assertSame($cashier->id, $transaction->cashier_id);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{6}$/', $transaction->invoice_number);
        $this->assertSame('35000.00', (string) $transaction->subtotal);
        $this->assertSame('2000.00', (string) $transaction->discount);
        $this->assertSame('33000.00', (string) $transaction->total);
        $this->assertSame('40000.00', (string) $transaction->cash_received);
        $this->assertSame('7000.00', (string) $transaction->change_amount);
        $this->assertSame('8.000', (string) $first->stock_quantity);
        $this->assertSame('3.000', (string) $second->stock_quantity);

        $coffeeItem = TransactionItem::query()->where('transaction_id', $transaction->id)
            ->where('sku_snapshot', 'SKU-COF')
            ->first();

        $this->assertNotNull($coffeeItem);
        $this->assertSame('Coffee', $coffeeItem->product_name_snapshot);
        $this->assertSame('123456', $coffeeItem->barcode_snapshot);
        $this->assertSame('30000.00', (string) $coffeeItem->line_total);

        $movements = StockMovement::query()->where('transaction_id', $transaction->id)->get();
        $this->assertCount(2, $movements);
        $this->assertTrue($movements->every(
            fn (StockMovement $movement): bool => $movement->movement_type === StockMovementType::Sale
                && bccomp((string) $movement->quantity_change, '0', 3) < 0
                && $movement->reason === null,
        ));
    }

    public function test_insufficient_payment_does_not_create_transaction_rows(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'selling_price' => '10000.00',
            'active' => true,
        ]);

        try {
            app(CompleteSale::class)->execute(
                actor: $cashier,
                cartItems: [$product->id => '1.000'],
                discount: '0',
                cashReceived: '5000',
            );
            $this->fail('Expected insufficient payment to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }

        $product->refresh();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('5.000', (string) $product->stock_quantity);
    }

    public function test_insufficient_stock_does_not_change_inventory_or_create_sale_rows(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '1.000',
            'selling_price' => '10000.00',
            'active' => true,
        ]);

        try {
            app(CompleteSale::class)->execute(
                actor: $cashier,
                cartItems: [$product->id => '2.000'],
                discount: '0',
                cashReceived: '30000',
            );
            $this->fail('Expected insufficient stock to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('stock', $exception->errors());
        }

        $product->refresh();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('1.000', (string) $product->stock_quantity);
    }

    public function test_inactive_product_is_rejected_at_checkout(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->inactive()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => false,
        ]);

        try {
            app(CompleteSale::class)->execute(
                actor: $cashier,
                cartItems: [$product->id => '1.000'],
                discount: '0',
                cashReceived: '20000',
            );
            $this->fail('Expected inactive product checkout to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_second_checkout_after_successful_sale_fails_with_empty_cart(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '2.000',
            'selling_price' => '10000.00',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [$product->id => '1.000'])
            ->set('cashReceived', '10000')
            ->call('requestCheckout')
            ->call('confirmCheckout')
            ->assertRedirect(route('transactions.receipt', Transaction::query()->first()));

        $this->assertDatabaseCount('transactions', 1);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '0.00', isEmpty: true, items: [])
            ->set('cashReceived', '10000')
            ->call('requestCheckout')
            ->assertHasErrors(['checkout']);

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_payment_panel_checkout_clears_cart_and_redirects_to_receipt(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Bottled Water',
            'stock_quantity' => '3.000',
            'selling_price' => '5000.00',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->assertSet('items', [$product->id => '1.000']);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '5000.00', isEmpty: false, items: [$product->id => '1.000'])
            ->set('cashReceived', '10000')
            ->call('requestCheckout')
            ->call('confirmCheckout')
            ->assertRedirect(route('transactions.receipt', Transaction::query()->first()));

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('clearCart')
            ->assertSet('items', []);

        $transaction = Transaction::query()->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee($transaction->invoice_number, false)
            ->assertSee('Bottled Water', false)
            ->assertSee('Print receipt', false);
    }

    public function test_livewire_insufficient_cash_shows_error_without_database_writes(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'selling_price' => '10000.00',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [$product->id => '1.000'])
            ->set('cashReceived', '5000')
            ->call('requestCheckout')
            ->assertHasErrors(['payment']);

        $this->assertDatabaseCount('transactions', 0);
    }
}
