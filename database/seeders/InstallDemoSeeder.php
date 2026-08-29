<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo users and catalog only. Does not create or overwrite the Owner.
 */
class InstallDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = (string) env('DEMO_ADMIN_PASSWORD', env('OWNER_PASSWORD', 'password'));
        $cashierPassword = (string) env('DEMO_CASHIER_PASSWORD', env('OWNER_PASSWORD', 'password'));

        User::query()->updateOrCreate(
            ['email' => (string) env('DEMO_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => (string) env('DEMO_ADMIN_NAME', 'Store Administrator'),
                'password' => $adminPassword !== '' ? $adminPassword : 'password',
                'role' => UserRole::Administrator,
                'active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => (string) env('DEMO_CASHIER_EMAIL', 'cashier@example.com')],
            [
                'name' => (string) env('DEMO_CASHIER_NAME', 'Store Cashier'),
                'password' => $cashierPassword !== '' ? $cashierPassword : 'password',
                'role' => UserRole::Cashier,
                'active' => true,
            ],
        );

        $category = Category::query()->firstOrCreate(
            ['name' => 'General'],
            ['active' => true],
        );

        Product::query()->updateOrCreate(
            ['sku' => 'DEMO-STOCK-10'],
            [
                'category_id' => $category->id,
                'name' => 'Demo Product (Stock 10)',
                'barcode' => 'DEMO000010',
                'selling_price' => '15000.00',
                'cost_price' => null,
                'stock_quantity' => '10.000',
                'active' => true,
            ],
        );

        Product::query()->updateOrCreate(
            ['sku' => 'DEMO-STOCK-1'],
            [
                'category_id' => $category->id,
                'name' => 'Demo Product (Stock 1)',
                'barcode' => 'DEMO000001',
                'selling_price' => '8000.00',
                'cost_price' => null,
                'stock_quantity' => '1.000',
                'active' => true,
            ],
        );

        Product::query()->updateOrCreate(
            ['sku' => 'DEMO-INACTIVE'],
            [
                'category_id' => $category->id,
                'name' => 'Demo Inactive Product',
                'barcode' => 'DEMO000999',
                'selling_price' => '5000.00',
                'cost_price' => null,
                'stock_quantity' => '5.000',
                'active' => false,
            ],
        );
    }
}
