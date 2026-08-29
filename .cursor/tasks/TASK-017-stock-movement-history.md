# TASK-017 — Stock Movement History

**Task ID:** TASK-017  
**Title:** Stock movement history  
**Filename:** `TASK-017-stock-movement-history.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let Owner and Administrator review stock movements (sales and manual adjustments) with product, type, quantities, actor, optional sale reference, reason, and time. Read-only.

---

## Background

MVP-07 history, FR-INV-04–05, UI_UX inventory, permissions matrix (Cashier No).

---

## Dependencies

- TASK-012 and/or TASK-016 (`stock_movements`)
- TASK-004 (`viewStockMovements`)

---

## Files likely affected

- `app/Livewire/Inventory/MovementIndex.php`
- `app/Policies/StockMovementPolicy.php`
- `resources/views/livewire/inventory/`
- `routes/web.php`
- `tests/Feature/Inventory/`

---

## Database changes

None. Ensure indexes from DATABASE.md exist (`product_id`, `occurred_at`, composite product+occurred_at).

---

## Backend requirements

1. Paginated movements, newest first.
2. Filters: product search/id, movement type, date range.
3. Eager-load `product`, `user`, `transaction` (invoice only).
4. No edit/delete.

---

## Frontend requirements

- Table: time, product, type, before/change/after, user, invoice if sale, reason if adjustment.
- Empty state.
- Link to transaction detail when `transaction_id` present (TASK-014).

---

## Validation rules

Filter dates: from <= to. Type: enum or empty.

---

## Authorization rules

- Owner/Admin: view.
- Cashier: 403.

---

## Business rules

- Movements are traceable history; not editable (AUD-style protection).

---

## Edge cases

- Sale movement without reason.
- Adjustment without transaction.
- Product later renamed: show current product name **and/or** id; movement does not snapshot name in schema — showing current name is OK for this operational log.

---

## Security considerations

- IDOR: cashiers must not access even with a known movement id.
- Authorize query globally for managers (all movements).

---

## Testing requirements

- Sale checkout produces a visible SALE row.
- Adjustment produces MANUAL_ADJUSTMENT with reason.
- Cashier denied.
- No update route.

---

## Acceptance criteria

- [ ] FR-INV-04–05 readable in UI.
- [ ] Cashier denied.

---

## Definition of Done

- [ ] History page complete.
- [ ] Tests pass.
- [ ] Read-only.
