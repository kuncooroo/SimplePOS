<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Actions\Sales\CompleteSale;
use App\Enums\StockMovementType;
use App\Livewire\Inventory\MovementIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class StockMovementHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_checkout_produces_visible_sale_movement_row(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Checkout Coffee',
            'sku' => 'SKU-COF',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '2.000'],
            discount: '0',
            cashReceived: '50000',
        );

        $movement = StockMovement::query()->where('transaction_id', $transaction->id)->sole();

        $this->actingAs($owner)
            ->get(route('inventory.movements.index'))
            ->assertOk()
            ->assertSee('Checkout Coffee', false)
            ->assertSee('SKU-COF', false)
            ->assertSee('Sale', false)
            ->assertSee('-2.000', false)
            ->assertSee($transaction->invoice_number, false)
            ->assertSee($cashier->name, false);

        $this->assertSame(StockMovementType::Sale, $movement->movement_type);
        $this->assertNull($movement->reason);
    }

    public function test_manual_adjustment_produces_row_with_reason(): void
    {
        $admin = User::factory()->administrator()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Shelf Sugar',
            'sku' => 'SKU-SUG',
            'stock_quantity' => '6.000',
        ]);

        app(AdjustStock::class)->execute(
            actor: $admin,
            product: $product,
            quantityChange: '-1.500',
            reason: 'Spillage during restock',
        );

        Livewire::actingAs($admin)
            ->test(MovementIndex::class)
            ->assertSee('Shelf Sugar', false)
            ->assertSee('Manual adjustment', false)
            ->assertSee('Spillage during restock', false)
            ->assertSee('-1.500', false)
            ->assertSee($admin->name, false);
    }

    public function test_cashier_is_denied_stock_movement_history(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('inventory.movements.index'))
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(MovementIndex::class)
            ->assertForbidden();
    }

    public function test_movement_type_filter_limits_results(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Filter Product',
            'stock_quantity' => '20.000',
            'active' => true,
        ]);

        app(AdjustStock::class)->execute(
            actor: $owner,
            product: $product,
            quantityChange: '1.000',
            reason: 'Count correction',
        );

        app(CompleteSale::class)->execute(
            actor: User::factory()->cashier()->create(),
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '50000',
        );

        $manualOnly = Livewire::actingAs($owner)
            ->test(MovementIndex::class)
            ->set('movementType', StockMovementType::ManualAdjustment->value);

        $manualOnly
            ->assertSee('Count correction', false)
            ->assertSee('Manual adjustment', false);
        $this->assertCount(1, $manualOnly->viewData('movements'));

        $saleOnly = Livewire::actingAs($owner)
            ->test(MovementIndex::class)
            ->set('movementType', StockMovementType::Sale->value);

        $saleOnly
            ->assertSee('Sale', false)
            ->assertDontSee('Count correction', false);
        $this->assertCount(1, $saleOnly->viewData('movements'));
    }

    public function test_sale_movement_links_to_transaction_detail(): void
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
            ->get(route('inventory.movements.index'))
            ->assertOk()
            ->assertSee(route('transactions.show', $transaction), false);
    }

    public function test_history_page_is_read_only(): void
    {
        $reflection = new \ReflectionClass(MovementIndex::class);

        $this->assertFalse($reflection->hasMethod('save'));
        $this->assertFalse($reflection->hasMethod('update'));
        $this->assertFalse($reflection->hasMethod('delete'));

        $movementRoutes = collect(Route::getRoutes())->filter(function ($route): bool {
            $uri = $route->uri();

            return str_contains($uri, 'inventory/movements')
                && in_array('PUT', $route->methods(), true);
        });

        $this->assertCount(0, $movementRoutes);
        $this->assertCount(0, collect(Route::getRoutes())->filter(function ($route): bool {
            $uri = $route->uri();

            return str_contains($uri, 'inventory/movements')
                && in_array('PATCH', $route->methods(), true);
        }));
    }

    public function test_owner_sidebar_includes_stock_movements_link(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('inventory.movements.index'), false)
            ->assertSee('Stock movements', false);
    }

    public function test_cashier_sidebar_does_not_include_stock_movements_link(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk()
            ->assertDontSee(route('inventory.movements.index'), false)
            ->assertDontSee('Stock movements', false);
    }
}
