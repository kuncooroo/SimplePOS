<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Services\Sales\CheckoutCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutCalculatorTest extends TestCase
{
    private CheckoutCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new CheckoutCalculator;
    }

    public function test_subtotal_is_summed_from_line_totals(): void
    {
        $result = $this->calculator->calculateFromLines(
            ['10000.00', '5000.00', '2500.50'],
            '0',
            '20000.00',
        );

        $this->assertSame('17500.50', $result->subtotal);
        $this->assertSame('17500.50', $result->total);
        $this->assertTrue($result->paymentSufficient);
        $this->assertSame('2499.50', $result->change);
    }

    public function test_discount_is_capped_at_subtotal(): void
    {
        $result = $this->calculator->calculate('10000.00', '15000.00', '0');

        $this->assertSame('10000.00', $result->discount);
        $this->assertSame('0.00', $result->total);
        $this->assertTrue($result->paymentSufficient);
        $this->assertSame('0.00', $result->change);
    }

    public function test_total_never_goes_negative(): void
    {
        $result = $this->calculator->calculate('5000.00', '5000.00', '0');

        $this->assertSame('0.00', $result->total);
    }

    public function test_discount_equal_to_subtotal_allows_zero_cash(): void
    {
        $result = $this->calculator->calculate('7500.00', '7500.00', '0');

        $this->assertSame('0.00', $result->total);
        $this->assertTrue($result->paymentSufficient);
        $this->assertSame('0.00', $result->change);
    }

    public function test_insufficient_cash_is_detected(): void
    {
        $result = $this->calculator->calculate('10000.00', '1000.00', '8000.00');

        $this->assertSame('9000.00', $result->total);
        $this->assertFalse($result->paymentSufficient);
        $this->assertSame('0.00', $result->change);
    }

    public function test_change_is_cash_minus_total_when_sufficient(): void
    {
        $result = $this->calculator->calculate('12500.00', '500.00', '15000.00');

        $this->assertSame('12000.00', $result->total);
        $this->assertTrue($result->paymentSufficient);
        $this->assertSame('3000.00', $result->change);
    }

    public function test_exact_cash_has_zero_change(): void
    {
        $result = $this->calculator->calculate('20000.00', '0', '20000.00');

        $this->assertTrue($result->paymentSufficient);
        $this->assertSame('0.00', $result->change);
    }

    #[DataProvider('blankAmountProvider')]
    public function test_blank_amounts_are_treated_as_zero(string $discount, string $cashReceived): void
    {
        $result = $this->calculator->calculate('10000.00', $discount, $cashReceived);

        $this->assertSame('0.00', $result->discount);
        $this->assertSame('10000.00', $result->total);
        $this->assertFalse($result->paymentSufficient);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function blankAmountProvider(): array
    {
        return [
            'empty discount and cash' => ['', ''],
            'whitespace discount' => ['   ', '0'],
        ];
    }
}
