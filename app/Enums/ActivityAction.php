<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityAction: string
{
    case UserCreated = 'USER_CREATED';
    case UserRoleChanged = 'USER_ROLE_CHANGED';
    case UserStatusChanged = 'USER_STATUS_CHANGED';
    case StockManualAdjusted = 'STOCK_MANUAL_ADJUSTED';
    case StoreSettingsUpdated = 'STORE_SETTINGS_UPDATED';
}
