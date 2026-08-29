<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Reporting\Dashboard;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Reporting\DashboardMetricsQuery;
use App\Support\Money;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_completed_sale_today_appears_in_sales_total_and_transaction_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 10:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'selling_price' => '15000.00',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '2.000'],
            discount: '2000.00',
            cashReceived: '40000',
        );

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee('Sales today', false)
            ->assertSee(Money::format('28000.00'), false)
            ->assertSee('Transactions today', false)
            ->assertSee('1', false);
    }

    public function test_yesterday_sales_do_not_count_toward_today_metrics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-28 18:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        Carbon::setTestNow(Carbon::parse('2026-08-29 09:00:00', config('app.timezone')));

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee(Money::format('0'), false)
            ->assertSee('No sales today', false);

        $metrics = app(DashboardMetricsQuery::class)->execute();
        $this->assertSame('0.00', $metrics->salesToday);
        $this->assertSame(0, $metrics->transactionsToday);
    }

    public function test_failed_checkout_does_not_affect_dashboard_totals(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 11:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
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
                cashReceived: '1000',
            );
            $this->fail('Expected insufficient payment to fail checkout.');
        } catch (ValidationException) {
            // Expected.
        }

        $this->assertSame(0, Transaction::query()->count());

        $metrics = app(DashboardMetricsQuery::class)->execute();
        $this->assertSame('0.00', $metrics->salesToday);
        $this->assertSame(0, $metrics->transactionsToday);
    }

    public function test_cashier_is_denied_dashboard_access(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(Dashboard::class)
            ->assertForbidden();
    }

    public function test_empty_day_shows_zero_metrics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 08:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee(Money::format('0'), false)
            ->assertSee('Transactions today', false)
            ->assertSee('0', false)
            ->assertSee('No sales today', false)
            ->assertSee('0 sold', false);
    }

    public function test_product_price_change_does_not_change_today_sales_total(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Stable Price Item',
            'selling_price' => '9000.00',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '9000',
        );

        $product->update(['selling_price' => '50000.00']);

        $metrics = app(DashboardMetricsQuery::class)->execute();

        $this->assertSame('9000.00', $metrics->salesToday);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee(Money::format('9000.00'), false)
            ->assertDontSee(Money::format('50000.00'), false);
    }

    public function test_best_seller_uses_today_quantities_with_name_tie_break(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 13:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();

        $alpha = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Alpha Snack',
            'stock_quantity' => '20.000',
            'active' => true,
        ]);
        $beta = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Beta Snack',
            'stock_quantity' => '20.000',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$alpha->id => '2.000'],
            discount: '0',
            cashReceived: '50000',
        );

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$beta->id => '3.000'],
            discount: '0',
            cashReceived: '50000',
        );

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee('Beta Snack', false)
            ->assertSee('3.000 sold', false)
            ->assertDontSee('Alpha Snack', false);
    }

    public function test_low_stock_count_uses_store_threshold(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 14:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        StoreSettings::current()->update(['low_stock_threshold' => '5.000']);
        StoreSettings::clearCache();

        Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
        ]);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee('Low stock', false)
            ->assertSee('1', false)
            ->assertSee(route('inventory.index'), false);
    }
}
