<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name', 200);
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('currency_code', 10);
            $table->string('currency_symbol', 10);
            $table->text('receipt_footer')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->decimal('low_stock_threshold', 15, 3);
            $table->timestamps();
        });

        $now = now();

        DB::table('store_settings')->insert([
            'id' => 1,
            'store_name' => 'SimplePOS Store',
            'address' => null,
            'phone' => null,
            'email' => null,
            'currency_code' => 'IDR',
            'currency_symbol' => 'Rp',
            'receipt_footer' => null,
            'logo_path' => null,
            'low_stock_threshold' => '5.000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
