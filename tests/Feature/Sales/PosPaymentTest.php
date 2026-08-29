<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Livewire\Pos\CartPanel;
use App\Livewire\Pos\PaymentPanel;
use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_panel_shows_preview_totals_from_cart_subtotal(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '15000.00', isEmpty: false, items: [1 => '1.000'])
            ->assertSet('subtotal', '15000.00')
            ->assertSee('Amount due', false)
            ->assertSee('recalculated on the server', false);
    }

    public function test_discount_reduces_amount_due(): void
    {
        $cashier = User::factory()->cashier()->create();

        $component = Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '20000.00', isEmpty: false, items: [1 => '1.000'])
            ->set('discount', '5000');

        $preview = $component->instance()->preview();

        $this->assertSame('5000.00', $preview->discount);
        $this->assertSame('15000.00', $preview->total);
    }

    public function test_discount_cannot_exceed_subtotal_in_validation(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [1 => '1.000'])
            ->set('discount', '15000')
            ->assertHasErrors(['discount']);
    }

    public function test_cart_subtotal_change_caps_existing_discount(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '20000.00', isEmpty: false, items: [1 => '1.000'])
            ->set('discount', '15000')
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [1 => '1.000'])
            ->assertSet('discount', '10000.00');
    }

    public function test_insufficient_cash_shows_error_and_does_not_open_confirmation(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [1 => '1.000'])
            ->set('cashReceived', '5000')
            ->call('requestCheckout')
            ->assertHasErrors(['payment'])
            ->assertSet('showConfirmDialog', false);
    }

    public function test_sufficient_cash_opens_confirmation_dialog(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [1 => '1.000'])
            ->set('cashReceived', '15000')
            ->call('requestCheckout')
            ->assertSet('showConfirmDialog', true);
    }

    public function test_empty_cart_disables_checkout_button(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->assertSee('Confirm checkout', false)
            ->assertSeeHtml('disabled');
    }

    public function test_non_numeric_discount_is_rejected(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PaymentPanel::class)
            ->dispatch('cart-state-updated', subtotal: '10000.00', isEmpty: false, items: [1 => '1.000'])
            ->set('discount', 'abc')
            ->assertHasErrors(['discount']);
    }

    public function test_cart_panel_broadcasts_cart_state_updates(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'selling_price' => '7500.00',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        Livewire::actingAs($cashier)
            ->test(CartPanel::class)
            ->call('addToCart', $product->id)
            ->assertDispatched('cart-state-updated', subtotal: '7500.00', isEmpty: false, items: [$product->id => '1.000']);
    }

    public function test_pos_page_includes_payment_panel(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)
            ->test(PosPage::class)
            ->assertSeeLivewire(PaymentPanel::class)
            ->assertSee('Payment', false);
    }
}
