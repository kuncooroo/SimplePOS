<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Pos\PaymentPanel;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_shows_store_name_invoice_and_snapshot_prices(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mineral Water',
            'sku' => 'SKU-WATER',
            'selling_price' => '7500.00',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '2.000'],
            discount: '500.00',
            cashReceived: '20000.00',
        );

        StoreSetting::query()->first()?->update([
            'store_name' => 'Toko Sejahtera',
            'receipt_footer' => 'Terima kasih',
        ]);
        StoreSettings::clearCache();

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee('Toko Sejahtera', false)
            ->assertSee($transaction->invoice_number, false)
            ->assertSee('Mineral Water', false)
            ->assertSee('SKU-WATER', false)
            ->assertSee('Print receipt', false)
            ->assertSee('Terima kasih', false)
            ->assertSee('15.000', false);
    }

    public function test_receipt_uses_snapshot_price_after_product_price_changes(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Price Locked Item',
            'selling_price' => '12000.00',
            'stock_quantity' => '3.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '12000',
        );

        $product->update(['selling_price' => '99999.00']);

        $response = $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction));

        $response->assertOk();
        $response->assertSee('12.000', false);
        $response->assertDontSee('99.999', false);
    }

    public function test_cashier_cannot_view_another_cashiers_receipt(): void
    {
        $cashierA = User::factory()->cashier()->create();
        $cashierB = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashierA,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $this->actingAs($cashierB)
            ->get(route('transactions.receipt', $transaction))
            ->assertForbidden();
    }

    public function test_owner_can_view_any_cashiers_receipt(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $this->actingAs($owner)
            ->get(route('transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee($transaction->invoice_number, false);
    }

    public function test_guest_is_redirected_from_receipt(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $this->get(route('transactions.receipt', $transaction))
            ->assertRedirect(route('login'));
    }

    public function test_checkout_redirects_to_receipt(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '3.000',
            'selling_price' => '5000.00',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '5000.00', isEmpty: false, items: [$product->id => '1.000'])
            ->set('cashReceived', '10000')
            ->call('requestCheckout')
            ->call('confirmCheckout')
            ->assertRedirect(route('transactions.receipt', Transaction::query()->first()));
    }

    public function test_legacy_success_route_redirects_to_receipt(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $this->actingAs($cashier)
            ->get(route('pos.checkout.success', $transaction))
            ->assertRedirect(route('transactions.receipt', $transaction));
    }
}
