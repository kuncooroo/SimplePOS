# TASK-010 — POS Cart

**Task ID:** TASK-010  
**Title:** POS cart  
**Filename:** `TASK-010-pos-cart.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let a POS user add active products to an in-memory cart, merge duplicate lines, change quantity within stock rules, remove lines, and see a live subtotal. Do not complete payment or write transactions.

---

## Background

FR-POS-01–08, SFR-POS-01–02 (cart parts), BR-03/09/10, BUSINESS_FLOW §2.4 / §8, UI_UX cart spec.

No `carts` table (`DATABASE.md` §2). Cart lives in Livewire state.

---

## Dependencies

- TASK-009

---

## Files likely affected

- `app/Livewire/Pos/PosPage.php`
- `app/Livewire/Pos/CartPanel.php`
- `app/Livewire/Pos/ProductSearch.php` (emit add-to-cart)
- `app/Support/Sales/CartItemData.php` (optional readonly)
- `resources/views/livewire/pos/`
- `tests/Feature/Sales/`

---

## Database changes

None.

---

## Backend requirements

1. Add product: if same `product_id` exists, increment quantity (FR-POS-03).
2. Quantity must be positive (BR-03). Decrement to 0 removes the line or reject 0 — **remove the line**.
3. Do not allow quantity above current `stock_quantity` for stock-tracked products (BR-10 / FR-POS-08–09). Reload stock from DB on add/qty change; do not trust a stale Livewire stock field as authority.
4. Remove line (FR-POS-05).
5. Display subtotal = sum(unit_price * qty) using **current** `selling_price` from DB at add/reprice time. If product price changed, refresh from DB on qty change.
6. Empty cart: checkout disabled (FR-POS-08) — button visible but TASK-011/012 enforce; this task should disable a “Pay” placeholder.
7. Inactive product cannot be added (re-check on add).

---

## Frontend requirements

- Cart lines: name, qty control, line amount, remove (`UI_UX.md` §31).
- Subtotal visible and updates within the Livewire request (PERF-02).
- Empty cart state.
- Prevent double-click add from creating two lines of the same product (merge rule).

---

## Validation rules

| Input | Rules |
|---|---|
| product_id | required, exists products |
| quantity | numeric, > 0, max = current stock |

---

## Authorization rules

- `accessPos` on every add/update/remove method.

---

## Business rules

- BR-03, BR-09, BR-10.
- Cart is not a completed sale (BR-11/12).

---

## Edge cases

- Product deactivated while in cart: remove or block checkout in TASK-012; this task should revalidate on qty change and drop/block inactive.
- Concurrent stock drop: qty change re-reads stock; reject if insufficient.
- Decimal quantities: schema allows DECIMAL(15,3); MVP retail may use integers — **accept integers in UI** unless a requirement says otherwise; store as decimal.

---

## Security considerations

- Never take `unit_price` from the client as authority (`CURSOR.md` §48). Store product_id + qty only; price from DB.
- Authorize each action.

---

## Testing requirements

- Add two of same SKU → one line qty 2.
- Qty above stock rejected; cart unchanged.
- Inactive product rejected.
- Remove line; subtotal updates.
- Empty cart cannot proceed (Pay disabled).

---

## Acceptance criteria

- [ ] FR-POS-01–08 (checkout still later).
- [ ] Subtotal recalculates (FR-POS-06–07 partial: payable total without discount yet).

---

## Definition of Done

- [ ] Cart behavior works on POS page.
- [ ] Tests pass.
- [ ] No transaction rows written.
