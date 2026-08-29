<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Enums\ActivityAction;
use App\Enums\StockMovementType;
use App\Livewire\Inventory\AdjustStockForm;
use App\Livewire\Inventory\StockIndex;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_adjust_stock_and_persist_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Tea Box',
            'sku' => 'SKU-TEA',
            'stock_quantity' => '10.000',
            'active' => true,
        ]);

        $movement = app(AdjustStock::class)->execute(
            actor: $owner,
            product: $product,
            quantityChange: '-3.000',
            reason: 'Damaged units removed',
        );

        $product->refresh();

        $this->assertSame('7.000', (string) $product->stock_quantity);
        $this->assertSame(StockMovementType::ManualAdjustment, $movement->movement_type);
        $this->assertSame($product->id, $movement->product_id);
        $this->assertSame($owner->id, $movement->user_id);
        $this->assertNull($movement->transaction_id);
        $this->assertSame('10.000', (string) $movement->quantity_before);
        $this->assertSame('-3.000', (string) $movement->quantity_change);
        $this->assertSame('7.000', (string) $movement->quantity_after);
        $this->assertSame('Damaged units removed', $movement->reason);
    }

    public function test_adjustment_writes_stock_manual_adjusted_audit_row(): void
    {
        $admin = User::factory()->administrator()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '4.000',
        ]);

        app(AdjustStock::class)->execute(
            actor: $admin,
            product: $product,
            quantityChange: '2.000',
            reason: 'Restock from supplier',
        );

        $log = ActivityLog::query()->sole();

        $this->assertSame(ActivityAction::StockManualAdjusted, $log->action);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($product->id, $log->subject_id);
        $this->assertSame(['stock_quantity' => '4.000'], $log->old_values);
        $this->assertSame(['stock_quantity' => '6.000'], $log->new_values);
        $this->assertSame([
            'product_id' => $product->id,
            'quantity_change' => '2.000',
            'reason' => 'Restock from supplier',
        ], $log->context);
    }

    public function test_cashier_is_denied_manual_adjustment(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
        ]);

        $this->expectException(AuthorizationException::class);

        app(AdjustStock::class)->execute(
            actor: $cashier,
            product: $product,
            quantityChange: '1.000',
            reason: 'Should fail',
        );
    }

    public function test_cashier_cannot_open_adjust_stock_form(): void
    {
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
        ]);

        Livewire::actingAs($cashier)
            ->test(AdjustStockForm::class)
            ->dispatch('open-adjust-stock', productId: $product->id)
            ->assertForbidden();
    }

    public function test_reason_is_required(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
        ]);

        try {
            app(AdjustStock::class)->execute(
                actor: $owner,
                product: $product,
                quantityChange: '1.000',
                reason: '   ',
            );
            $this->fail('Expected validation exception for blank reason.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $this->assertSame('5.000', (string) $product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_zero_change_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '5.000',
        ]);

        try {
            app(AdjustStock::class)->execute(
                actor: $owner,
                product: $product,
                quantityChange: '0.000',
                reason: 'No actual change',
            );
            $this->fail('Expected validation exception for zero change.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity_change', $exception->errors());
        }

        $this->assertSame('5.000', (string) $product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_negative_result_is_rejected_without_persisting_changes(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '2.000',
        ]);

        try {
            app(AdjustStock::class)->execute(
                actor: $owner,
                product: $product,
                quantityChange: '-5.000',
                reason: 'Too large a reduction',
            );
            $this->fail('Expected validation exception for negative stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity_change', $exception->errors());
        }

        $this->assertSame('2.000', (string) $product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::query()->count());
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_livewire_form_adjusts_stock_from_inventory_overview(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Biscuit Pack',
            'sku' => 'SKU-BIS',
            'stock_quantity' => '12.000',
        ]);

        Livewire::actingAs($owner)
            ->test(StockIndex::class)
            ->call('openAdjustStock', $product->id);

        Livewire::actingAs($owner)
            ->test(AdjustStockForm::class)
            ->dispatch('open-adjust-stock', productId: $product->id)
            ->assertSet('productName', 'Biscuit Pack')
            ->assertSet('currentStock', '12.000')
            ->set('quantityChange', '-2.000')
            ->set('reason', 'Expired stock write-off')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $product->refresh();

        $this->assertSame('10.000', (string) $product->stock_quantity);
        $this->assertSame(1, StockMovement::query()->count());
        $this->assertSame(1, ActivityLog::query()->count());
    }

    public function test_livewire_form_validates_reason_before_adjustment(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => '8.000',
        ]);

        Livewire::actingAs($owner)
            ->test(AdjustStockForm::class)
            ->dispatch('open-adjust-stock', productId: $product->id)
            ->set('quantityChange', '1.000')
            ->set('reason', '   ')
            ->call('confirm')
            ->assertHasErrors(['reason']);

        $this->assertSame('8.000', (string) $product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::query()->count());
    }
}
