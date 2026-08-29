# TASK-012 — Checkout

**Task ID:** TASK-012  
**Title:** Checkout  
**Filename:** `TASK-012-checkout.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`. This is release-critical.

---

## Objective

Complete a cash sale atomically: one completed transaction, item snapshots, unique invoice, stock reduction, and `SALE` stock movements — or roll back everything. Recalculate totals on the server. Never trust Livewire prices.

---

## Background

MVP-05/06/07 core, FR-PAY-05–08, FR-TRX-01, FR-INV-01/04–05, SFR-POS-03–05, SFR-TRX-01–03, SFR-INV-01/03–04, BR-01–14, BUSINESS_FLOW §10 / §13, DATABASE.md §21.1 / §26–28 / §31–32 / §36, CURSOR.md §48–49, AC-05/06/07/14.

Do **not** use jobs, events, or listeners for stock or transaction rows.

---

## Dependencies

- TASK-007, TASK-010, TASK-011
- TASK-004 (`accessPos`)

---

## Files likely affected

- `database/migrations/*_create_transactions_table.php`
- `database/migrations/*_create_transaction_items_table.php`
- `database/migrations/*_create_stock_movements_table.php`
- `app/Models/Transaction.php`
- `app/Models/TransactionItem.php`
- `app/Models/StockMovement.php`
- `app/Enums/TransactionStatus.php` (`COMPLETED` only)
- `app/Enums/StockMovementType.php` (`SALE`, `MANUAL_ADJUSTMENT`)
- `app/Actions/Sales/CompleteSale.php`
- `app/Actions/Inventory/ApplySaleStockDeduction.php`
- `app/Support/Sales/InvoiceNumberGenerator.php`
- `app/Support/Sales/CheckoutPayload.php` (optional)
- `app/Policies/TransactionPolicy.php` (no update/delete)
- `app/Livewire/Pos/PaymentPanel.php`
- `tests/Feature/Sales/`
- `tests/Unit/Sales/`

---

## Database changes

**transactions** (`DATABASE.md` §26): `cashier_id` FK users, unique `invoice_number`, `status` default COMPLETED, `subtotal`, `discount`, `total`, `cash_received`, `change_amount` DECIMAL(15,2), `completed_at`, timestamps. Indexes per DATABASE.md.

**transaction_items** (`DATABASE.md` §27): snapshots `product_name_snapshot`, `sku_snapshot`, `barcode_snapshot`, `unit_price`, `quantity`, `line_subtotal`, `line_discount` default 0, `line_total`, `product_id` nullable FK. FK transaction cascade with header only if DATABASE.md says so — prefer restrict on delete of transaction (MVP never deletes). Product FK nullOnDelete or restrict; **snapshots remain if product later deactivated**.

**stock_movements** (`DATABASE.md` §28): `product_id`, `user_id`, `transaction_id` nullable, `movement_type`, `quantity_before`, `quantity_change` (negative for sale), `quantity_after`, `reason` null for sale, `occurred_at`.

Invoice uniqueness at DB level.

---

## Backend requirements

`CompleteSale` (single DB transaction):

1. Authorize actor can sell.
2. Reject empty cart.
3. Reload products with lock appropriate to MySQL (e.g. `lockForUpdate`) to prevent oversell (DATABASE.md §36).
4. Validate active, qty > 0, stock sufficient.
5. Recalculate with `CheckoutCalculator` using DB prices.
6. Validate discount and cash_received >= total.
7. Generate unique invoice (`InvoiceNumberGenerator`).
8. Insert transaction + items (snapshots, not current-only names).
9. `ApplySaleStockDeduction` for each stock-tracked line.
10. Commit. On any failure, rollback (AC-14).
11. Prevent double submit (Livewire locked button + idempotency token or disable until response).

Sale movements: `movement_type=SALE`, `transaction_id` set, `quantity_change < 0`, `reason` null.

Do not write `activity_logs` for successful sale (DATABASE.md §20).

After success, clear cart and redirect to receipt if TASK-013 exists; otherwise show invoice number and a simple success view.

---

## Frontend requirements

- Explicit confirm checkout (FR-PAY-05) — dialog.
- Loading state on confirm (`UI_UX.md` §17).
- Insufficient stock/payment: business errors, not success toast (NOT-03, NOT-06).
- Do not show success if commit failed.

---

## Validation rules

Revalidate in Action (not only Livewire):

- cart not empty
- each product active
- quantity > 0 and <= locked stock
- selling price from DB >= 0
- discount 0..subtotal
- cash_received >= total
- total >= 0

---

## Authorization rules

- `accessPos`.
- `cashier_id` = authenticated user, never from request.
- TransactionPolicy: no update/delete for completed sales.

---

## Business rules

BR-01 through BR-14, BR-22 as applicable. Stock reduced only on success (BR-11). Failed checkout is not revenue (BR-12).

---

## Edge cases

- Concurrent two cashiers last-unit: one succeeds, one insufficient stock, no negative stock.
- Invoice collision: retry/fail without partial sale.
- Product renamed after add-to-cart: snapshot uses **reload** name/price at checkout, not the Livewire label, unless you snapshot at add — **authoritative checkout reload** (BUSINESS_FLOW §10).
- Discount/cash tampered in request: ignore client totals.

---

## Security considerations

- CURSOR.md §48: never trust browser/Livewire/Alpine/hidden prices.
- IDOR not applicable to creating own sale.
- No raw SQL without bindings.
- Do not log payment as secrets; cash amounts are business data.

---

## Testing requirements

Mandatory (`CURSOR.md` §36):

- empty cart rejected;
- valid cash sale: one transaction, items, stock down, SALE movements, invoice unique (AC-05);
- insufficient payment: no transaction (AC-06);
- insufficient stock: no stock change (AC-07);
- rollback / AC-14;
- duplicate submit does not create two completed sales;
- inactive product rejected;
- unit: invoice generator uniqueness under sequential calls.

---

## Acceptance criteria

- [ ] AC-05, AC-06, AC-07, AC-14.
- [ ] FR-PAY-05–08, FR-TRX-01, FR-INV-01/04.

---

## Definition of Done

- [ ] Atomic checkout implemented in Actions, not Livewire.
- [ ] Tests above pass.
- [ ] No queues/events on the critical path.
- [ ] Receipt may be a stub pending TASK-013.
