<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Livewire\Pos\CartPanel;
use App\Livewire\Pos\PaymentPanel;
use App\Livewire\Pos\PosPage;
use App\Livewire\Pos\ProductSearch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_same_product_twice_merges_into_one_line(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SKU-MERGE',
            'stock_quantity' => '10.000',
            'selling_price' => '5000.00',
            'active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->assertSet('items', [
                $product->id => '2.000',
            ])
            ->assertSee('SKU-MERGE', false);

        $this->assertCount(1, $component->viewData('lines'));
    }

    public function test_adding_same_product_via_search_event_merges_lines(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Merged Via Search',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->dispatch('add-to-cart', productId: $product->id)
            ->dispatch('add-to-cart', productId: $product->id)
            ->assertSet('items', [
                $product->id => '2.000',
            ]);
    }

    public function test_quantity_above_stock_is_rejected_and_cart_unchanged(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Limited Stock',
            'stock_quantity' => '2.000',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->assertHasErrors(['cart'])
            ->assertSet('items', [
                $product->id => '2.000',
            ]);
    }

    public function test_increment_above_stock_is_rejected(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '1.000',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->call('incrementQuantity', $product->id)
            ->assertHasErrors(['cart'])
            ->assertSet('items', [
                $product->id => '1.000',
            ]);
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->inactive()->create([
            'category_id' => $category->id,
            'name' => 'Inactive Item',
            'active' => false,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->assertHasErrors(['cart'])
            ->assertSet('items', []);
    }

    public function test_removing_a_line_updates_subtotal(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $first = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'First Product',
            'sku' => 'SKU-ONE',
            'selling_price' => '10000.00',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);
        $second = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Second Product',
            'sku' => 'SKU-TWO',
            'selling_price' => '5000.00',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $first->id)
            ->call('addToCart', $second->id);

        $this->assertSame('15000.00', $component->instance()->subtotal());

        $component->call('removeLine', $first->id);

        $this->assertSame('5000.00', $component->instance()->subtotal());
        $component->assertDontSee('First Product', false)
            ->assertSee('Second Product', false);
    }

    public function test_decrementing_to_zero_removes_the_line(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->call('decrementQuantity', $product->id)
            ->assertSet('items', [])
            ->assertSee('Cart is empty', false);
    }

    public function test_subtotal_uses_current_selling_price_from_database(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'selling_price' => '12000.00',
            'stock_quantity' => '3.000',
            'active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id);

        $this->assertSame('24000.00', $component->instance()->subtotal());

        $product->update(['selling_price' => '15000.00']);

        $this->assertSame('30000.00', $component->instance()->subtotal());
    }

    public function test_inactive_product_in_cart_is_dropped_on_quantity_change(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Soon Inactive',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $component = Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id);

        $product->update(['active' => false]);

        $component->call('incrementQuantity', $product->id)
            ->assertHasErrors(['cart'])
            ->assertSet('items', []);
    }

    public function test_product_search_dispatches_add_to_cart_event(): void
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
            ->assertDispatched('add-to-cart', productId: $product->id);
    }

    public function test_pos_page_includes_cart_and_payment_panels(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PosPage::class)
            ->assertSeeLivewire(CartPanel::class)
            ->assertSeeLivewire(PaymentPanel::class)
            ->assertSee('Cart is empty', false);
    }
}
