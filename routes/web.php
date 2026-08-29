<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Sales\CheckoutSuccessController;
use App\Http\Controllers\Sales\ReceiptController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Livewire\Audit\ActivityLogIndex;
use App\Livewire\Catalog\CategoryForm;
use App\Livewire\Catalog\CategoryIndex;
use App\Livewire\Catalog\ProductForm;
use App\Livewire\Catalog\ProductIndex;
use App\Livewire\Configuration\StoreSettingsForm;
use App\Livewire\Identity\ProfileForm;
use App\Livewire\Identity\UserForm;
use App\Livewire\Identity\UserIndex;
use App\Livewire\Inventory\MovementIndex;
use App\Livewire\Inventory\StockIndex;
use App\Livewire\Pos\PosPage;
use App\Livewire\Reporting\Dashboard;
use App\Livewire\Reporting\ProductSalesReport;
use App\Livewire\Reporting\SalesReport;
use App\Livewire\Sales\TransactionIndex;
use App\Livewire\Sales\TransactionShow;
use App\Support\Auth\AuthenticatedHome;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = Auth::user();

    if ($user) {
        return redirect()->to(AuthenticatedHome::url($user));
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('can:viewDashboard')
        ->name('dashboard');

    Route::get('/pos', PosPage::class)
        ->middleware('can:accessPos')
        ->name('pos');

    Route::get('/pos/success/{transaction}', CheckoutSuccessController::class)
        ->middleware('can:accessPos')
        ->name('pos.checkout.success');

    Route::get('/transactions/{transaction}/receipt', ReceiptController::class)
        ->name('transactions.receipt');

    Route::get('/transactions', TransactionIndex::class)
        ->name('transactions.index');

    Route::get('/transactions/{transaction}', TransactionShow::class)
        ->name('transactions.show');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile', ProfileForm::class)->name('profile.edit');

    Route::middleware('can:manageUsers')->group(function () {
        Route::get('/users', UserIndex::class)->name('users.index');
        Route::get('/users/create', UserForm::class)->name('users.create');
        Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');
    });

    Route::middleware('can:manageCategories')->group(function () {
        Route::get('/categories', CategoryIndex::class)->name('categories.index');
        Route::get('/categories/create', CategoryForm::class)->name('categories.create');
        Route::get('/categories/{category}/edit', CategoryForm::class)->name('categories.edit');
    });

    Route::middleware('can:manageProducts')->group(function () {
        Route::get('/products', ProductIndex::class)->name('products.index');
        Route::get('/products/create', ProductForm::class)->name('products.create');
        Route::get('/products/{product}/edit', ProductForm::class)->name('products.edit');
    });

    Route::middleware('can:manageStoreSettings')->group(function () {
        Route::get('/settings', StoreSettingsForm::class)->name('settings.edit');
    });

    Route::middleware('can:viewInventory')->group(function () {
        Route::get('/inventory', StockIndex::class)->name('inventory.index');
    });

    Route::middleware('can:viewStockMovements')->group(function () {
        Route::get('/inventory/movements', MovementIndex::class)->name('inventory.movements.index');
    });

    Route::middleware('can:viewReports')->group(function () {
        Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
        Route::get('/reports/product-sales', ProductSalesReport::class)->name('reports.product-sales');
    });

    Route::middleware('can:viewAuditLog')->group(function () {
        Route::get('/audit-log', ActivityLogIndex::class)->name('audit-log.index');
    });
});
