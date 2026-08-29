<?php

declare(strict_types=1);

namespace Tests\Feature\Regression;

use App\Enums\UserRole;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_users_category_and_products_for_manual_qa(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => UserRole::Owner->value,
            'active' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::Administrator->value,
            'active' => 1,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'cashier@example.com',
            'role' => UserRole::Cashier->value,
            'active' => 1,
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'General',
            'active' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'DEMO-STOCK-10',
            'stock_quantity' => '10.000',
            'active' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'DEMO-STOCK-1',
            'stock_quantity' => '1.000',
            'active' => 1,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'DEMO-INACTIVE',
            'active' => 0,
        ]);

        $this->assertDatabaseHas('store_settings', [
            'store_name' => 'SimplePOS Store',
        ]);
    }
}
