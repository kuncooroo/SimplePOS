<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'SALE';
    case ManualAdjustment = 'MANUAL_ADJUSTMENT';
}
