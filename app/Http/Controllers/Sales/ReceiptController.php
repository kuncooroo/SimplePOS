<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\StoreSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function __invoke(Transaction $transaction): View
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['items', 'cashier']);
        $settings = StoreSettings::current();

        return view('receipts.show', [
            'transaction' => $transaction,
            'settings' => $settings,
            'logoUrl' => $this->resolveLogoUrl($settings->logo_path),
            'title' => 'Receipt '.$transaction->invoice_number.' — '.config('app.name'),
        ]);
    }

    private function resolveLogoUrl(?string $logoPath): ?string
    {
        if ($logoPath === null || $logoPath === '') {
            return null;
        }

        if (str_contains($logoPath, '..')) {
            return null;
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        return Storage::disk('public')->url($logoPath);
    }
}
