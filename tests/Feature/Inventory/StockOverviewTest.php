<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\StockIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_inventory_stock_overview(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Rice Pack',
            'sku' => 'SKU-RICE',
            'stock_quantity' => '12.000',
            'active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Inventory', false)
            ->assertSee('Rice Pack', false)
            ->assertSee('SKU-RICE', false)
            ->assertDontSee('cost_price', false);
    }

    public function test_low_stock_filter_includes_products_at_or_below_threshold(): void
    {
        $admin = User::factory()->administrator()->create();
        $category = Category::factory()->create();

        StoreSettings::current()->update(['low_stock_threshold' => '5.000']);
        StoreSettings::clearCache();

        $low = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Low Item',
            'sku' => 'SKU-LOW',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Healthy Item',
            'sku' => 'SKU-OK',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(StockIndex::class)
            ->set('lowStockOnly', true)
            ->assertSee('Low Item', false)
            ->assertSee('Low stock', false)
            ->assertDontSee('Healthy Item', false);

        $this->assertSame($low->id, Livewire::actingAs($admin)
            ->test(StockIndex::class)
            ->set('lowStockOnly', true)
            ->viewData('products')
            ->first()
            ->id);
    }

    public function test_threshold_of_zero_marks_only_zero_stock_as_low(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        StoreSettings::current()->update(['low_stock_threshold' => '0.000']);
        StoreSettings::clearCache();

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Empty Shelf',
            'sku' => 'SKU-EMPTY',
            'stock_quantity' => '0.000',
            'active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Single Unit',
            'sku' => 'SKU-ONE',
            'stock_quantity' => '1.000',
            'active' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(StockIndex::class)
            ->set('lowStockOnly', true)
            ->assertSee('Empty Shelf', false)
            ->assertDontSee('Single Unit', false);
    }

    public function test_search_filters_by_name_or_sku(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Green Tea',
            'sku' => 'SKU-TEA',
            'stock_quantity' => '8.000',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mineral Water',
            'sku' => 'SKU-WATER',
            'stock_quantity' => '8.000',
        ]);

        Livewire::actingAs($owner)
            ->test(StockIndex::class)
            ->set('search', 'TEA')
            ->assertSee('Green Tea', false)
            ->assertDontSee('Mineral Water', false);
    }

    public function test_cashier_is_denied_inventory_access(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('inventory.index'))
            ->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(StockIndex::class)
            ->assertForbidden();
    }

    public function test_inventory_page_is_read_only(): void
    {
        $reflection = new \ReflectionClass(StockIndex::class);

        $this->assertFalse($reflection->hasMethod('save'));
        $this->assertFalse($reflection->hasMethod('updateStock'));
        $this->assertFalse($reflection->hasMethod('adjustStock'));
    }

    public function test_owner_sidebar_includes_inventory_link(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('inventory.index'), false)
            ->assertSee('Inventory', false);
    }

    public function test_cashier_sidebar_does_not_include_inventory_link(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk()
            ->assertDontSee(route('inventory.index'), false)
            ->assertDontSee('Inventory', false);
    }
}
