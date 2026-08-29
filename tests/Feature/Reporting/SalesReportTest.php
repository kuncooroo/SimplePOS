<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Reporting\Dashboard;
use App\Livewire\Reporting\SalesReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\Reporting\DashboardMetricsQuery;
use App\Queries\Reporting\DateRangeSalesQuery;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_completed_transactions_in_period_match_report_sum_and_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 10:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '20.000',
            'selling_price' => '10000.00',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '1000.00',
            cashReceived: '20000',
        );

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '2.000'],
            discount: '0',
            cashReceived: '30000',
        );

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $summary = app(DateRangeSalesQuery::class)->summary($today, $today);

        $this->assertSame('29000.00', $summary->salesTotal);
        $this->assertSame(2, $summary->transactionCount);

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format('29000.00'), false)
            ->assertSee('2', false);
    }

    public function test_failed_checkout_is_not_included_in_sales_report(): void
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

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format('0'), false)
            ->assertSee('No sales in this period', false);
    }

    public function test_product_price_change_does_not_change_report_totals(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
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

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $summary = app(DateRangeSalesQuery::class)->summary($today, $today);

        $this->assertSame('9000.00', $summary->salesTotal);

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format('9000.00'), false)
            ->assertDontSee(Money::format('50000.00'), false);
    }

    public function test_cashier_is_denied_sales_report_access(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('reports.sales'))
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(SalesReport::class)
            ->assertForbidden();
    }

    public function test_dashboard_today_matches_sales_report_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 15:00:00', config('app.timezone')));

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

        $dashboardMetrics = app(DashboardMetricsQuery::class)->execute();
        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $reportSummary = app(DateRangeSalesQuery::class)->summary($today, $today);

        $this->assertSame($dashboardMetrics->salesToday, $reportSummary->salesTotal);
        $this->assertSame($dashboardMetrics->transactionsToday, $reportSummary->transactionCount);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSee(Money::format($dashboardMetrics->salesToday), false);

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format($reportSummary->salesTotal), false);
    }

    public function test_single_day_range_includes_late_night_sale(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 23:59:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'selling_price' => '12000.00',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '12000',
        );

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $summary = app(DateRangeSalesQuery::class)->summary($today, $today);

        $this->assertSame('12000.00', $summary->salesTotal);
        $this->assertSame(1, $summary->transactionCount);

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->assertSee(Money::format('12000.00'), false);
    }

    public function test_invalid_date_range_shows_validation_error(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(SalesReport::class)
            ->set('dateFrom', '2026-08-10')
            ->set('dateTo', '2026-08-01')
            ->assertSee('The end date must be on or after the start date.', false)
            ->assertSee('Invalid date range', false);
    }

    public function test_owner_sidebar_includes_sales_report_link(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('reports.sales'), false)
            ->assertSee('Sales report', false);
    }

    public function test_cashier_sidebar_does_not_include_sales_report_link(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk()
            ->assertDontSee(route('reports.sales'), false)
            ->assertDontSee('Sales report', false);
    }
}
