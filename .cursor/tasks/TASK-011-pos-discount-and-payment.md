# TASK-011 — POS Discount and Payment

**Task ID:** TASK-011  
**Title:** POS discount and cash payment  
**Filename:** `TASK-011-pos-discount-and-payment.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Add transaction-level discount, cash received, amount due, and change **display** on POS, using a server-side calculator. Do not persist a completed sale (TASK-012).

---

## Background

FR-DISC-01–03, FR-PAY-01–04, BR-05–08, SFR-POS-02, UI_UX.md §32, BUSINESS_FLOW §9.

MVP payment method is cash only (BR-22).

---

## Dependencies

- TASK-010

---

## Files likely affected

- `app/Services/Sales/CheckoutCalculator.php`
- `app/Livewire/Pos/PaymentPanel.php`
- `app/Livewire/Pos/CartPanel.php` / `PosPage.php`
- `tests/Unit/Sales/CheckoutCalculatorTest.php`
- `tests/Feature/Sales/` Livewire tests
- `resources/views/livewire/pos/`

---

## Database changes

None.

---

## Backend requirements

1. `CheckoutCalculator` (pure): given line totals, discount, cash received → subtotal, discount, total (>= 0), change. No Eloquent writes.
2. Discount is transaction-level, not per line (FR-DISC-01). `line_discount` stays 0 later.
3. Discount cannot exceed subtotal (BR-06) or push total below 0 (FR-DISC-02, BR-05).
4. Change = cash_received - total when cash_received >= total (BR-07, BR-08).
5. If cash_received < total, calculator/UI marks payment insufficient; do not compute negative change as success.
6. Livewire may show preview totals; mark them as preview. TASK-012 recalculates again.

---

## Frontend requirements

- Payment panel: amount due, discount input, cash received, change, confirm checkout **disabled or non-persisting** until TASK-012. You may add a Confirm button that only validates preview rules and shows errors (insufficient pay) without writing DB — or leave Confirm wired in TASK-012. **Preferred:** Confirm visible but TASK-012 implements the Action; this task can no-op persist and show “checkout coming” **or** call a validation-only method. Do not create transactions.
- Checkout confirmation dialog copy may be added here as UI (`UI_UX.md` §19) without committing.

---

## Validation rules

| Field | Rules |
|---|---|
| discount | numeric, min 0, max = current subtotal |
| cash_received | numeric, min 0 |

---

## Authorization rules

- `accessPos` on payment field updates.

---

## Business rules

- BR-05, BR-06, BR-07, BR-08, BR-22.
- Discount stored later on transaction (FR-DISC-03) — not in this task.

---

## Edge cases

- Discount = subtotal → total 0; cash received 0 is allowed (exact).
- Extra cash → change.
- Non-numeric input rejected.
- Cart change resets/revalidates discount if discount now exceeds new subtotal.

---

## Security considerations

- Discount and cash are untrusted until TASK-012.
- Do not accept a client-supplied `total` or `change`.

---

## Testing requirements

Unit tests for calculator:

- discount capped;
- total never negative;
- insufficient cash detected;
- change exact.

Livewire: entering cash less than due shows error, no DB writes.

---

## Acceptance criteria

- [ ] FR-DISC-01–02, FR-PAY-01–04 at UI/calculator layer.
- [ ] No completed transaction created.

---

## Definition of Done

- [ ] Calculator covered by unit tests.
- [ ] Payment panel usable on POS.
- [ ] Checkout persistence explicitly left to TASK-012.
