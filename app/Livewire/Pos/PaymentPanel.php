<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Actions\Sales\CompleteSale;
use App\Models\User;
use App\Services\Sales\CheckoutCalculation;
use App\Services\Sales\CheckoutCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentPanel extends Component
{
    public string $subtotal = '0.00';

    public bool $cartIsEmpty = true;

    /**
     * @var array<int, string>
     */
    #[Locked]
    public array $cartItems = [];

    public string $discount = '0';

    public string $cashReceived = '0';

    public bool $showConfirmDialog = false;

    public bool $isProcessingCheckout = false;

    public function mount(): void
    {
        $this->authorizePosAccess();
    }

    #[On('cart-state-updated')]
    public function syncCartState(string $subtotal, bool $isEmpty, array $items): void
    {
        $this->authorizePosAccess();

        $this->subtotal = app(CheckoutCalculator::class)->calculate($subtotal, '0', '0')->subtotal;
        $this->cartIsEmpty = $isEmpty;
        $this->cartItems = $items;

        if ($isEmpty) {
            $this->resetPaymentFields();

            return;
        }

        $this->capDiscountToSubtotal();
        $this->resetErrorBag('discount');
    }

    public function updatedDiscount(): void
    {
        $this->authorizePosAccess();
        $this->validateOnly('discount');
    }

    public function updatedCashReceived(): void
    {
        $this->authorizePosAccess();
        $this->validateOnly('cashReceived');
        $this->resetErrorBag('payment');
    }

    public function requestCheckout(): void
    {
        $this->authorizePosAccess();
        $this->validate();

        if ($this->cartIsEmpty || $this->cartItems === []) {
            $this->addError('checkout', 'Add at least one product before checkout.');

            return;
        }

        $preview = $this->preview();

        if (! $preview->paymentSufficient) {
            $this->addError('payment', 'Cash received is less than the amount due.');

            return;
        }

        $this->showConfirmDialog = true;
    }

    public function cancelCheckout(): void
    {
        $this->authorizePosAccess();
        $this->showConfirmDialog = false;
    }

    public function confirmCheckout(): void
    {
        if ($this->isProcessingCheckout) {
            return;
        }

        $this->authorizePosAccess();
        $this->validate();

        if ($this->cartIsEmpty || $this->cartItems === []) {
            $this->addError('checkout', 'Add at least one product before checkout.');
            $this->showConfirmDialog = false;

            return;
        }

        $preview = $this->preview();

        if (! $preview->paymentSufficient) {
            $this->addError('payment', 'Cash received is less than the amount due.');
            $this->showConfirmDialog = false;

            return;
        }

        $this->isProcessingCheckout = true;

        try {
            /** @var User $actor */
            $actor = Auth::user();

            $transaction = app(CompleteSale::class)->execute(
                actor: $actor,
                cartItems: $this->cartItems,
                discount: $this->discount,
                cashReceived: $this->cashReceived,
            );

            $this->dispatch('cart-clear');
            $this->resetPaymentFields();
            $this->showConfirmDialog = false;

            $this->redirectRoute('transactions.receipt', $transaction, navigate: true);
        } catch (ValidationException $exception) {
            $this->showConfirmDialog = false;

            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        } finally {
            $this->isProcessingCheckout = false;
        }
    }

    public function preview(): CheckoutCalculation
    {
        return app(CheckoutCalculator::class)->calculate(
            $this->subtotal,
            $this->discount,
            $this->cashReceived,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'discount' => ['nullable', 'numeric', 'min:0', 'max:'.$this->subtotal],
            'cashReceived' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'discount' => 'discount',
            'cashReceived' => 'cash received',
        ];
    }

    public function render(): View
    {
        $preview = $this->preview();

        return view('livewire.pos.payment-panel', [
            'preview' => $preview,
            'canCheckout' => ! $this->cartIsEmpty && $this->cartItems !== [],
        ]);
    }

    private function authorizePosAccess(): void
    {
        Gate::authorize('accessPos');
    }

    private function capDiscountToSubtotal(): void
    {
        if (! is_numeric($this->discount)) {
            return;
        }

        if (bccomp((string) $this->discount, $this->subtotal, 2) > 0) {
            $this->discount = $this->subtotal;
        }
    }

    private function resetPaymentFields(): void
    {
        $this->discount = '0';
        $this->cashReceived = '0';
        $this->showConfirmDialog = false;
        $this->cartItems = [];
        $this->resetErrorBag();
    }
}
