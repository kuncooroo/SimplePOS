<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Sales\InvoiceNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_invoice_numbers_with_expected_format(): void
    {
        $generator = new InvoiceNumberGenerator;

        $invoiceNumber = $generator->generate();

        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{6}$/', $invoiceNumber);
    }

    public function test_sequential_calls_generate_unique_invoice_numbers(): void
    {
        $generator = new InvoiceNumberGenerator;

        $first = $generator->generate();
        Transaction::query()->create([
            'cashier_id' => User::factory()->create()->id,
            'invoice_number' => $first,
            'status' => 'COMPLETED',
            'subtotal' => '1000.00',
            'discount' => '0.00',
            'total' => '1000.00',
            'cash_received' => '1000.00',
            'change_amount' => '0.00',
            'completed_at' => now(),
        ]);

        $second = $generator->generate();

        $this->assertNotSame($first, $second);
        $this->assertSame(
            (int) substr($first, -6) + 1,
            (int) substr($second, -6),
        );
    }
}
