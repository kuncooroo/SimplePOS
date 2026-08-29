<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Livewire\Inventory\StockIndex;
use App\Livewire\Pos\CartPanel;
use App\Livewire\Pos\PaymentPanel;
use App\Livewire\Pos\ProductSearch;
use App\Livewire\Reporting\ProductSalesReport;
use App\Livewire\Reporting\SalesReport;
use App\Livewire\Sales\TransactionIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Queries\Reporting\DashboardMetricsQuery;
use App\Queries\Reporting\DateRangeSalesQuery;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_cashier_can_complete_the_full_pos_to_reporting_journey(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 15:30:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create([
            'email' => 'cashier-journey@example.com',
            'password' => 'password',
        ]);
        $category = Category::factory()->create(['name' => 'Beverages']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Journey Coffee',
            'sku' => 'SKU-JOURNEY',
            'barcode' => '888777666',
            'selling_price' => '12000.00',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect(route('pos'));

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'Journey Coffee')
            ->assertSee('Journey Coffee', false)
            ->call('selectProduct', $product->id);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->call('incrementQuantity', $product->id)
            ->assertSet('items', [$product->id => '2.000']);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '24000.00', isEmpty: false, items: [$product->id => '2.000'])
            ->set('discount', '2000')
            ->set('cashReceived', '25000')
            ->call('requestCheckout')
            ->call('confirmCheckout')
            ->assertRedirect(route('transactions.receipt', Transaction::query()->first()));

        $transaction = Transaction::query()->firstOrFail();
        $product->refresh();

        $this->assertSame(TransactionStatus::Completed, $transaction->status);
        $this->assertSame('22000.00', (string) $transaction->total);
        $this->assertSame($cashier->id, $transaction->cashier_id);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{6}$/', $transaction->invoice_number);
        $this->assertSame('8.000', (string) $product->stock_quantity);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseCount('stock_movements', 1);

        $item = TransactionItem::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame('Journey Coffee', $item->product_name_snapshot);
        $this->assertSame('2.000', (string) $item->quantity);

        $movement = StockMovement::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(StockMovementType::Sale, $movement->movement_type);
        $this->assertSame('-2.000', (string) $movement->quantity_change);

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction))
            ->assertOk()
            ->assertSee($transaction->invoice_number, false)
            ->assertSee('Journey Coffee', false)
            ->assertSee('Print receipt', false);

        Livewire::actingAs($owner)
            ->test(TransactionIndex::class)
            ->set('invoice', $transaction->invoice_number)
            ->assertSee($transaction->invoice_number, false)
            ->assertSee($cashier->name, false);

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $salesSummary = app(DateRangeSalesQuery::class)->summary($today, $today);
        $dashboardMetrics = app(DashboardMetricsQuery::class)->execute();

        $this->assertSame('22000.00', $salesSummary->salesTotal);
        $this->assertSame($dashboardMetrics->salesToday, $salesSummary->salesTotal);
        $this->assertSame(1, $salesSummary->transactionCount);

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format('22000.00'), false)
            ->assertSee('1', false);

        Livewire::actingAs($owner)
            ->test(ProductSalesReport::class)
            ->assertSee('Journey Coffee', false)
            ->assertSee('2.000', false)
            ->assertSee(Money::format('24000.00'), false);
    }

    public function test_historical_snapshots_remain_after_product_rename_and_price_change(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 16:00:00', config('app.timezone')));

        $cashier = User::factory()->cashier()->create();
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Original Label',
            'selling_price' => '9000.00',
            'stock_quantity' => '4.000',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '9000.00', isEmpty: false, items: [$product->id => '1.000'])
            ->set('cashReceived', '9000')
            ->call('requestCheckout')
            ->call('confirmCheckout');

        $transaction = Transaction::query()->firstOrFail();

        $product->update([
            'name' => 'Renamed Label',
            'selling_price' => '50000.00',
        ]);

        $this->actingAs($cashier)
            ->get(route('transactions.receipt', $transaction))
            ->assertSee('Original Label', false)
            ->assertDontSee('Renamed Label', false);

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();

        $this->assertSame('9000.00', app(DateRangeSalesQuery::class)->summary($today, $today)->salesTotal);

        Livewire::actingAs($owner)
            ->test(ProductSalesReport::class)
            ->assertSee('Original Label', false)
            ->assertSee(Money::format('9000.00'), false)
            ->assertDontSee(Money::format('50000.00'), false);
    }

    public function test_last_unit_cannot_be_sold_twice_in_sequential_checkouts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 17:00:00', config('app.timezone')));

        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Last Unit Item',
            'stock_quantity' => '1.000',
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

        $this->assertSame('0.000', (string) $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('transactions', 1);

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [$product->id => '1.000'])
            ->set('cashReceived', '10000')
            ->call('requestCheckout')
            ->call('confirmCheckout')
            ->assertHasErrors(['stock']);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame('0.000', (string) $product->fresh()->stock_quantity);
    }

    public function test_failed_checkout_leaves_inventory_and_completed_rows_unchanged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 18:00:00', config('app.timezone')));

        $cashier = User::factory()->cashier()->create();
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '3.000',
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
        $this->assertSame('3.000', (string) $product->fresh()->stock_quantity);

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format('0'), false);

        Livewire::actingAs($owner)
            ->test(StockIndex::class)
            ->assertSee('3.000', false);
    }
}
