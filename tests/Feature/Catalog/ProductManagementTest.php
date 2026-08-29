<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ProductForm;
use App\Livewire\Catalog\ProductIndex;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_table_matches_catalog_schema(): void
    {
        $this->assertTrue(Schema::hasColumns('products', [
            'id',
            'category_id',
            'sku',
            'barcode',
            'name',
            'selling_price',
            'cost_price',
            'stock_quantity',
            'active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_authorized_create_makes_the_product_listable(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create(['name' => 'Minuman']);

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Air Mineral')
            ->set('sku', 'SKU-WATER')
            ->set('barcode', '')
            ->set('category_id', $category->id)
            ->set('selling_price', '5000.00')
            ->set('cost_price', '')
            ->set('stock_quantity', '12.5')
            ->set('active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Air Mineral',
            'sku' => 'SKU-WATER',
            'barcode' => null,
            'category_id' => $category->id,
            'cost_price' => null,
            'active' => 1,
        ]);

        $this->actingAs($owner)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Air Mineral', false)
            ->assertSee('SKU-WATER', false)
            ->assertSee('Minuman', false);
    }

    public function test_duplicate_sku_is_rejected_at_validation_and_database(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-DUPE',
        ]);

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Copy')
            ->set('sku', 'SKU-DUPE')
            ->set('category_id', $category->id)
            ->set('selling_price', '1000')
            ->set('stock_quantity', '1')
            ->call('save')
            ->assertHasErrors(['sku']);

        $this->expectException(QueryException::class);
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-DUPE',
        ]);
    }

    public function test_duplicate_barcode_is_rejected_when_both_are_present(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'barcode' => '8991234567890',
        ]);

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Other')
            ->set('sku', 'SKU-OTHER')
            ->set('barcode', '8991234567890')
            ->set('category_id', $category->id)
            ->set('selling_price', '1000')
            ->set('stock_quantity', '1')
            ->call('save')
            ->assertHasErrors(['barcode']);
    }

    public function test_multiple_products_may_omit_barcode(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'barcode' => null,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'barcode' => null,
        ]);

        $this->assertSame(2, Product::query()->whereNull('barcode')->count());
    }

    public function test_inactive_category_cannot_be_assigned(): void
    {
        $owner = User::factory()->owner()->create();
        $inactive = Category::factory()->inactive()->create();

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Blocked')
            ->set('sku', 'SKU-BLOCK')
            ->set('category_id', $inactive->id)
            ->set('selling_price', '1000')
            ->set('stock_quantity', '1')
            ->call('save')
            ->assertHasErrors(['category_id']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_negative_selling_price_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        Livewire::actingAs($owner)
            ->test(ProductForm::class)
            ->set('name', 'Bad price')
            ->set('sku', 'SKU-NEG')
            ->set('category_id', $category->id)
            ->set('selling_price', '-1')
            ->set('stock_quantity', '1')
            ->call('save')
            ->assertHasErrors(['selling_price']);
    }

    public function test_deactivated_product_remains_stored(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['name' => 'Teh Botol']);

        Livewire::actingAs($owner)
            ->test(ProductIndex::class)
            ->call('confirmDeactivate', $product->id)
            ->call('deactivate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Teh Botol',
            'active' => 0,
        ]);
    }

    public function test_search_matches_name_sku_and_barcode(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Kopi Sachet',
            'sku' => 'SKU-KOPI',
            'barcode' => '111',
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Gula',
            'sku' => 'SKU-GULA',
            'barcode' => '222',
        ]);

        Livewire::actingAs($owner)
            ->test(ProductIndex::class)
            ->set('search', 'KOPI')
            ->assertSee('Kopi Sachet', false)
            ->assertDontSee('Gula', false);

        Livewire::actingAs($owner)
            ->test(ProductIndex::class)
            ->set('search', '222')
            ->assertSee('Gula', false)
            ->assertDontSee('Kopi Sachet', false);

        Livewire::actingAs($owner)
            ->test(ProductIndex::class)
            ->set('search', 'zzzz-none')
            ->assertSee('No matching products', false);
    }

    public function test_cashier_cannot_manage_products(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create();

        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.create'))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.edit', $product))->assertForbidden();

        Livewire::actingAs($cashier)
            ->test(ProductIndex::class)
            ->assertForbidden();

        $this->assertFalse($cashier->can('create', Product::class));
        $this->assertFalse($cashier->can('delete', $product));
    }

    public function test_nobody_can_delete_a_product(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create();

        $this->assertFalse($owner->can('delete', $product));
        $this->assertFalse($owner->can('forceDelete', $product));
    }
}
