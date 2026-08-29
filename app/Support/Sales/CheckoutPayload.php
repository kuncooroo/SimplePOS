<?php

declare(strict_types=1);

namespace App\Support\Sales;

final readonly class CheckoutPayload
{
    /**
     * @param  array<int, string>  $items
     */
    public function __construct(
        public array $items,
        public string $discount,
        public string $cashReceived,
    ) {}
}
