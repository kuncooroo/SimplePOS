<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\StoreSettingPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use App\Support\Install\ArtisanMigrationRunner;
use App\Support\Install\DatabaseConnector;
use App\Support\Install\MigrationRunner;
use App\Support\Install\PdoDatabaseConnector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DatabaseConnector::class, PdoDatabaseConnector::class);
        $this->app->bind(MigrationRunner::class, ArtisanMigrationRunner::class);
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ActivityLog::class, ActivityLogPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(StoreSetting::class, StoreSettingPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);

        Gate::define('accessPos', fn (User $user): bool => $user->resolvedRole() !== null);
        Gate::define('viewDashboard', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('viewOwnTransactions', fn (User $user): bool => $user->resolvedRole() !== null);
        Gate::define('viewAllTransactions', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('manageProducts', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('manageCategories', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('adjustStock', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('viewInventory', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('viewStockMovements', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('viewReports', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('manageUsers', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('assignOwnerRole', fn (User $user): bool => $user->isOwner());
        Gate::define('manageStoreSettings', fn (User $user): bool => $this->isOwnerOrAdministrator($user));
        Gate::define('viewAuditLog', fn (User $user): bool => $user->isOwner());
        Gate::define('deleteCompletedTransaction', fn (User $user): bool => false);
    }

    private function isOwnerOrAdministrator(User $user): bool
    {
        return $user->isOwner() || $user->isAdministrator();
    }
}
