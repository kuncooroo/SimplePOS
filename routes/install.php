<?php

declare(strict_types=1);

use App\Http\Controllers\Install\CompleteController;
use App\Http\Controllers\Install\DatabaseController;
use App\Http\Controllers\Install\DemoDataController;
use App\Http\Controllers\Install\EnvironmentController;
use App\Http\Controllers\Install\ExtensionsController;
use App\Http\Controllers\Install\MigrateController;
use App\Http\Controllers\Install\OwnerController;
use App\Http\Controllers\Install\PermissionsController;
use App\Http\Controllers\Install\RequirementsController;
use App\Http\Controllers\Install\SettingsController;
use App\Http\Controllers\Install\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['install.unlocked'])
    ->prefix('install')
    ->name('install.')
    ->group(function (): void {
        Route::get('/', [WelcomeController::class, 'show'])->name('welcome');

        Route::post('/welcome', [WelcomeController::class, 'continue'])
            ->middleware('throttle:10,1')
            ->name('welcome.continue');

        Route::get('/requirements', [RequirementsController::class, 'show'])->name('requirements');
        Route::post('/requirements', [RequirementsController::class, 'continue'])
            ->middleware('throttle:10,1')
            ->name('requirements.continue');

        Route::get('/extensions', [ExtensionsController::class, 'show'])->name('extensions');
        Route::post('/extensions', [ExtensionsController::class, 'continue'])
            ->middleware('throttle:10,1')
            ->name('extensions.continue');

        Route::get('/permissions', [PermissionsController::class, 'show'])->name('permissions');
        Route::post('/permissions', [PermissionsController::class, 'continue'])
            ->middleware('throttle:10,1')
            ->name('permissions.continue');

        Route::get('/database', [DatabaseController::class, 'show'])->name('database');
        Route::post('/database', [DatabaseController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('database.store');

        Route::get('/environment', [EnvironmentController::class, 'show'])->name('environment');
        Route::post('/environment', [EnvironmentController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('environment.store');

        Route::get('/migrate', [MigrateController::class, 'show'])->name('migrate');
        Route::post('/migrate', [MigrateController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('migrate.store');

        Route::get('/owner', [OwnerController::class, 'show'])->name('owner');
        Route::post('/owner', [OwnerController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('owner.store');

        Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('settings.store');

        Route::get('/demo', [DemoDataController::class, 'show'])->name('demo');
        Route::post('/demo', [DemoDataController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('demo.store');

        Route::get('/complete', [CompleteController::class, 'show'])->name('complete');
        Route::post('/complete', [CompleteController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('complete.store');
    });
