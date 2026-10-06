<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Livewire\Pos\PosPage;
use App\Livewire\Pos\ProductSearch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_pos(): void
    {
        $this->get(route('pos'))->assertRedirect(route('login'));
    }

    public function test_cashier_can_open_pos_with_product_search(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PosPage::class)
            ->assertOk()
            ->assertSeeLivewire(ProductSearch::class)
            ->assertSee('Search to sell', false);
    }

    public function test_search_by_name_finds_active_product(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Teh Botol Sosro',
            'sku' => 'SKU-TEH-01',
            'barcode' => '111222333',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'Teh Botol')
            ->assertSee('Teh Botol Sosro', false)
            ->assertSee('SKU-TEH-01', false)
            ->assertDontSee('cost_price', false);
    }

    public function test_search_by_sku_finds_active_product(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Indomie Goreng',
            'sku' => 'SKU-IND-99',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'IND-99')
            ->assertSee('Indomie Goreng', false);
    }

    public function test_search_by_barcode_finds_active_product(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Air Mineral',
            'sku' => 'SKU-WATER',
            'barcode' => '8991234567890',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', '8991234567890')
            ->assertSee('Air Mineral', false);
    }

    public function test_barcode_exact_match_returns_immediately(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();

        $matched = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Scanned Item',
            'sku' => 'SKU-SCAN',
            'barcode' => '555000111',
            'active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Other 555000111 Name',
            'sku' => 'SKU-OTHER',
            'barcode' => '5550001119',
            'active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', '555000111');

        $component->assertSee('Scanned Item', false);
        $this->assertCount(1, $component->viewData('products'));
        $this->assertSame($matched->id, $component->viewData('products')->first()->id);
    }

    public function test_inactive_product_is_omitted_from_search(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();

        Product::factory()->inactive()->create([
            'category_id' => $category->id,
            'name' => 'Hidden Snack',
            'sku' => 'SKU-HIDE',
            'active' => false,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'Hidden')
            ->assertSee('No matching products', false)
            ->assertDontSee('Hidden Snack', false);
    }

    public function test_result_cap_is_respected(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();

        foreach (range(1, 25) as $index) {
            Product::factory()->create([
                'category_id' => $category->id,
                'name' => 'Bulk Item '.$index,
                'sku' => 'SKU-BULK-'.$index,
                'active' => true,
            ]);
        }

        $component = Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'Bulk Item');

        $this->assertLessThanOrEqual(ProductSearch::RESULT_LIMIT, $component->viewData('products')->count());
    }

    public function test_selecting_a_product_highlights_it_and_dispatches_event(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Selectable Product',
            'sku' => 'SKU-SEL',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'Selectable')
            ->call('selectProduct', $product->id)
            ->assertSet('selectedProductId', $product->id)
            ->assertDispatched('product-selected', productId: $product->id);
    }

    public function test_enter_on_exact_barcode_auto_adds_product_and_clears_search(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Scanned Bottle',
            'sku' => 'SKU-SCAN-ENTER',
            'barcode' => '8999988877766',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->call('confirmScan', '8999988877766')
            ->assertSet('search', '')
            ->assertSet('selectedProductId', null)
            ->assertDispatched('add-to-cart', productId: $product->id)
            ->assertDispatched('product-selected', productId: $product->id);
    }

    public function test_enter_without_exact_barcode_does_not_auto_add(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Teh Botol Sosro',
            'sku' => 'SKU-TEH-ENTER',
            'barcode' => '111222333444',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->set('search', 'Teh Botol')
            ->call('confirmScan')
            ->assertSet('search', 'Teh Botol')
            ->assertNotDispatched('add-to-cart');
    }

    public function test_enter_on_exact_sku_does_not_auto_add_without_barcode_match(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'SKU Only Item',
            'sku' => 'EXACT-SKU-01',
            'barcode' => null,
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->call('confirmScan', 'EXACT-SKU-01')
            ->assertSet('search', 'EXACT-SKU-01')
            ->assertNotDispatched('add-to-cart');
    }

    public function test_enter_on_inactive_barcode_does_not_auto_add(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->inactive()->create([
            'category_id' => $category->id,
            'name' => 'Retired Barcode',
            'sku' => 'SKU-RETIRED',
            'barcode' => '8990001112223',
            'active' => false,
        ]);

        Livewire::actingAs($cashier)
            ->test(ProductSearch::class)
            ->call('confirmScan', '8990001112223')
            ->assertSet('search', '8990001112223')
            ->assertNotDispatched('add-to-cart');
    }

    public function test_empty_search_does_not_dump_the_catalog(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $component = Livewire::actingAs($cashier)->test(ProductSearch::class);

        $this->assertCount(0, $component->viewData('products'));
        $component->assertSee('Search to sell', false);
    }
}
