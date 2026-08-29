<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Actions\Sales\CompleteSale;
use App\Livewire\Sales\TransactionIndex;
use App\Livewire\Sales\TransactionShow;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_search_transactions_by_invoice_number(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $matched = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        Livewire::actingAs($owner)
            ->test(TransactionIndex::class)
            ->set('invoice', $matched->invoice_number)
            ->assertSee($matched->invoice_number, false)
            ->assertSee($matched->cashier?->name ?? '', false);
    }

    public function test_detail_page_shows_snapshot_unit_price_after_product_price_changes(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Snapshot Product',
            'selling_price' => '9000.00',
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '9000',
        );

        $product->update(['selling_price' => '50000.00']);

        Livewire::actingAs($cashier)
            ->test(TransactionShow::class, ['transaction' => $transaction])
            ->assertSee('Snapshot Product', false)
            ->assertSee('9.000', false)
            ->assertDontSee('50.000', false);
    }

    public function test_cashier_cannot_open_another_cashiers_transaction_detail(): void
    {
        $cashierA = User::factory()->cashier()->create();
        $cashierB = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
            'active' => true,
        ]);

        $transaction = app(CompleteSale::class)->execute(
            actor: $cashierA,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $this->actingAs($cashierB)
            ->get(route('transactions.show', $transaction))
            ->assertForbidden();

        Livewire::actingAs($cashierB)
            ->test(TransactionShow::class, ['transaction' => $transaction])
            ->assertForbidden();
    }

    public function test_cashier_invoice_search_does_not_reveal_other_cashiers_sales(): void
    {
        $cashierA = User::factory()->cashier()->create();
        $cashierB = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $otherSale = app(CompleteSale::class)->execute(
            actor: $cashierA,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        Livewire::actingAs($cashierB)
            ->test(TransactionIndex::class)
            ->set('invoice', $otherSale->invoice_number)
            ->assertSee('No matching transactions', false)
            ->assertDontSee($otherSale->invoice_number, false);
    }

    public function test_cashier_only_sees_own_transactions_in_the_list(): void
    {
        $cashierA = User::factory()->cashier()->create(['name' => 'Cashier Alpha']);
        $cashierB = User::factory()->cashier()->create(['name' => 'Cashier Beta']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $ownSale = app(CompleteSale::class)->execute(
            actor: $cashierB,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        app(CompleteSale::class)->execute(
            actor: $cashierA,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        Livewire::actingAs($cashierB)
            ->test(TransactionIndex::class)
            ->assertSee($ownSale->invoice_number, false)
            ->assertDontSee('Cashier Alpha', false);
    }

    public function test_owner_can_filter_transactions_by_cashier(): void
    {
        $owner = User::factory()->owner()->create();
        $cashierA = User::factory()->cashier()->create(['name' => 'Filter Alpha']);
        $cashierB = User::factory()->cashier()->create(['name' => 'Filter Beta']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '20.000',
            'active' => true,
        ]);

        $alphaSale = app(CompleteSale::class)->execute(
            actor: $cashierA,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $betaSale = app(CompleteSale::class)->execute(
            actor: $cashierB,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $component = Livewire::actingAs($owner)
            ->test(TransactionIndex::class)
            ->set('cashierId', (string) $cashierA->id);

        $component->assertSee($alphaSale->invoice_number, false)
            ->assertSee('Filter Alpha', false)
            ->assertDontSee($betaSale->invoice_number, false);
        $this->assertSame(1, $component->viewData('transactions')->total());
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();

        Livewire::actingAs($owner)
            ->test(TransactionIndex::class)
            ->set('dateFrom', '2026-08-10')
            ->set('dateTo', '2026-08-01')
            ->assertHasErrors(['dateTo']);
    }

    public function test_clearing_filters_restores_the_full_permitted_list(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '20.000',
            'active' => true,
        ]);

        $first = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        $second = app(CompleteSale::class)->execute(
            actor: $cashier,
            cartItems: [$product->id => '1.000'],
            discount: '0',
            cashReceived: '20000',
        );

        Livewire::actingAs($owner)
            ->test(TransactionIndex::class)
            ->set('invoice', $first->invoice_number)
            ->assertSee($first->invoice_number, false)
            ->assertDontSee($second->invoice_number, false)
            ->call('clearFilters')
            ->assertSet('invoice', '')
            ->assertSee($first->invoice_number, false)
            ->assertSee($second->invoice_number, false);
    }

    public function test_transaction_list_is_paginated(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '100.000',
            'active' => true,
        ]);

        foreach (range(1, 16) as $index) {
            app(CompleteSale::class)->execute(
                actor: $cashier,
                cartItems: [$product->id => '1.000'],
                discount: '0',
                cashReceived: '20000',
            );
        }

        $component = Livewire::actingAs($owner)->test(TransactionIndex::class);

        $this->assertCount(15, $component->viewData('transactions')->items());
        $this->assertSame(16, $component->viewData('transactions')->total());
    }

    public function test_completed_transactions_cannot_be_updated_or_deleted(): void
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

        $this->assertFalse($owner->can('update', $transaction));
        $this->assertFalse($owner->can('delete', $transaction));
        $this->assertFalse($owner->can('deleteCompletedTransaction'));
    }

    public function test_inactive_cashier_name_is_still_shown_on_detail_page(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create(['name' => 'Former Cashier']);
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

        $cashier->update(['active' => false]);

        Livewire::actingAs($owner)
            ->test(TransactionShow::class, ['transaction' => $transaction])
            ->assertSee('Former Cashier', false);
    }
}
