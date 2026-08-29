<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Reporting\ProductSalesReport;
use App\Models\Category;
use App\Models\Product;
use App\Models\TransactionItem;
use App\Models\User;
use App\Queries\Reporting\ProductSalesQuery;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_two_sales_of_the_same_product_sum_quantities_in_the_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 10:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Grouped Tea',
            'sku' => 'SKU-TEA',
            'stock_quantity' => '20.000',
            'selling_price' => '10000.00',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '2.000'],
            discount: '0',
            cashReceived: '30000',
        );

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $totals = app(ProductSalesQuery::class)->periodTotals($today, $today);

        $this->assertSame('3.000', $totals['quantitySold']);
        $this->assertSame('30000.00', $totals['salesAmount']);

        Livewire::actingAs($owner)
            ->test(ProductSalesReport::class)
            ->assertSee('Grouped Tea', false)
            ->assertSee('SKU-TEA', false)
            ->assertSee('3.000', false)
            ->assertSee(Money::format('30000.00'), false);
    }

    public function test_current_selling_price_change_does_not_change_report_amounts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 12:00:00', config('app.timezone')));

        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Snapshot Coffee',
            'sku' => 'SKU-COF',
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

        $product->update([
            'selling_price' => '50000.00',
            'name' => 'Renamed Coffee',
        ]);

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $totals = app(ProductSalesQuery::class)->periodTotals($today, $today);

        $this->assertSame('9000.00', $totals['salesAmount']);

        Livewire::actingAs($owner)
            ->test(ProductSalesReport::class)
            ->assertSee('Snapshot Coffee', false)
            ->assertSee('Current name: Renamed Coffee', false)
            ->assertSee(Money::format('9000.00'), false)
            ->assertDontSee(Money::format('50000.00'), false);
    }

    public function test_grouped_amounts_reconcile_with_completed_transaction_items_in_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29 13:00:00', config('app.timezone')));

        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $first = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'active' => true,
        ]);
        $second = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [
                $first->id => '1.000',
                $second->id => '2.000',
            ],
            discount: '0',
            cashReceived: '60000',
        );

        $today = Carbon::now()->timezone((string) config('app.timezone'))->toDateString();
        $query = app(ProductSalesQuery::class);
        $totals = $query->periodTotals($today, $today);
        $rows = $query->paginate($today, $today, 15);

        $expectedAmount = (string) TransactionItem::query()->sum('line_total');
        $expectedQuantity = bcadd((string) TransactionItem::query()->sum('quantity'), '0', 3);
        $groupedAmount = '0.00';
        foreach ($rows as $row) {
            $groupedAmount = bcadd($groupedAmount, (string) $row->sales_amount, 2);
        }

        $this->assertSame(bcadd($expectedAmount, '0', 2), $totals['salesAmount']);
        $this->assertSame($expectedQuantity, $totals['quantitySold']);
        $this->assertSame(bcadd($expectedAmount, '0', 2), $groupedAmount);
        $this->assertCount(2, $rows);
    }

    public function test_cashier_is_denied_product_sales_report_access(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('reports.product-sales'))
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(ProductSalesReport::class)
            ->assertForbidden();
    }

    public function test_owner_sidebar_includes_product_sales_report_link(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('reports.product-sales'), false)
            ->assertSee('Product sales', false);
    }

    public function test_cashier_sidebar_does_not_include_product_sales_report_link(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk()
            ->assertDontSee(route('reports.product-sales'), false)
            ->assertDontSee('Product sales', false);
    }
}
