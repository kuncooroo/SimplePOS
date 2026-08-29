<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('invoice_number', 64)->unique();
            $table->string('status', 32)->default('COMPLETED')->index();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('cash_received', 15, 2);
            $table->decimal('change_amount', 15, 2);
            $table->dateTime('completed_at')->index();
            $table->timestamps();

            $table->index(['cashier_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
