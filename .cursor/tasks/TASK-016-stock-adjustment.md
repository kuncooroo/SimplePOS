# TASK-016 — Stock Adjustment

**Task ID:** TASK-016  
**Title:** Manual stock adjustment  
**Filename:** `TASK-016-stock-adjustment.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let Owner and Administrator change a product’s stock with a required reason, writing stock, a `MANUAL_ADJUSTMENT` movement, and an audit row in one database transaction.

---

## Background

MVP-07, FR-INV-02–05, SFR-INV-02–03, BR-15, AUD-01–02, BUSINESS_FLOW §14, DATABASE.md §21.2 / §28, AC-10, AC-11 (cashier denied).

---

## Dependencies

- TASK-007 (products)
- TASK-012 (stock_movements table — if checkout not done, **this task may create `stock_movements`** using the same schema as TASK-012; do not duplicate incompatible schemas)
- TASK-004 (`adjustStock`)
- TASK-005 (`STOCK_MANUAL_ADJUSTED`)

---

## Files likely affected

- `app/Actions/Inventory/AdjustStock.php`
- `app/Livewire/Inventory/AdjustStockForm.php`
- `app/Http/Requests/Inventory/AdjustStockRequest.php` (if controller) or Livewire rules
- `app/Enums/StockMovementType.php`
- `app/Policies/` (ProductPolicy or StockMovementPolicy)
- `resources/views/livewire/inventory/`
- `tests/Feature/Inventory/`

---

## Database changes

If `stock_movements` does not exist yet, create it per DATABASE.md §28 (same as TASK-012). For `MANUAL_ADJUSTMENT`: `reason` required, `transaction_id` null, `quantity_change` signed.

Do not allow resulting stock below 0 unless product explicitly later allows negative stock — **MVP: reject adjustments that would make stock negative**.

---

## Backend requirements

`AdjustStock` inside `DB::transaction`:

1. Authorize `adjustStock`.
2. Lock product row.
3. Validate reason non-empty.
4. Apply delta (or set-to target — **use signed quantity change** from UI: +/− amount).
5. Write movement: before, change, after, user_id, type MANUAL_ADJUSTMENT, occurred_at.
6. `RecordActivity` with product id, delta, reason, actor (AUD-02).
7. Commit.

Do not call this from arbitrary controllers without the Action (`CURSOR.md` §49).

---

## Frontend requirements

- Adjustment dialog/page (`UI_UX.md` §33): product identity, current stock, change amount, reason, confirm.
- Success toast; validation on reason.
- Entry from stock overview.

---

## Validation rules

| Field | Rules |
|---|---|
| product_id | required, exists |
| quantity_change | required, numeric, not 0, 3 decimal max |
| reason | required, string, min 1, max reasonable (e.g. 1000) |

---

## Authorization rules

- Owner, Administrator: Full.
- Cashier: No (AC-11).
- Authorize on Action and Livewire method.

---

## Business rules

- BR-15, FR-INV-02–05.
- Sale path remains TASK-012 only.

---

## Edge cases

- Zero change rejected.
- Would-be negative stock rejected; no movement/audit.
- Concurrent adjustment: lock product.
- Reason whitespace-only rejected.

---

## Security considerations

- CSRF.
- Do not take `quantity_after` from the client as authority; compute from locked before + change.
- Audit without secrets.

---

## Testing requirements

- AC-10: stock changes and movement exists.
- Audit `STOCK_MANUAL_ADJUSTED`.
- Cashier denied (AC-11).
- Reason required.
- Negative result rejected; stock unchanged.

---

## Acceptance criteria

- [ ] FR-INV-02–05 for manual path.
- [ ] AUD-01, AUD-02.
- [ ] AC-10, AC-11.

---

## Definition of Done

- [ ] Action is the only write path for manual stock.
- [ ] Same DB transaction for stock + movement + audit.
- [ ] Tests pass.
