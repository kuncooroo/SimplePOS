# TASK-015 — Inventory Stock Overview

**Task ID:** TASK-015  
**Title:** Inventory stock overview  
**Filename:** `TASK-015-inventory-stock-overview.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Show authorized managers current product stock and which products are at or below the global low-stock threshold. Read-only. No adjustments.

---

## Background

MVP-07 visibility, FR-INV-06, DASH-03 related, NOT-04, DATABASE.md low-stock note, UI_UX.md §33.

Current stock is `products.stock_quantity`. Threshold is `store_settings.low_stock_threshold`.

---

## Dependencies

- TASK-007, TASK-008
- TASK-004 (`adjustStock` is not required to **view**; use `viewStockMovements` or `manageProducts`. Matrix: Cashier No for stock movement history and manual adjustment. Stock overview is an Inventory module — **Cashier No**. Owner/Admin Full.)

---

## Files likely affected

- `app/Livewire/Inventory/StockIndex.php`
- `app/Queries/Reporting/LowStockQuery.php` (optional; a scoped query on Product is enough)
- `app/Policies/ProductPolicy.php` or Gate `viewInventory`
- `resources/views/livewire/inventory/`
- `routes/web.php`
- `tests/Feature/Inventory/`

If TASK-004 has no `viewInventory`, add Gate: Owner/Admin true, Cashier false. Prefer adding `viewInventory` rather than overloading `adjustStock` (view should not imply adjust).

---

## Database changes

None.

---

## Backend requirements

1. Paginated product stock list: name, SKU, stock_quantity, low-stock flag.
2. Filter/search name/SKU; optional “low stock only”.
3. Low stock: `stock_quantity <= low_stock_threshold` (define inclusive; test it).
4. Eager-load category if shown.
5. Do not update stock here.

---

## Frontend requirements

- Inventory overview (`UI_UX.md` §33).
- Badge for low stock; do not rely on color alone (CURSOR.md §52).
- Empty state.
- Nav Inventory for Owner/Admin only.

---

## Validation rules

Filters: search max length; boolean low-stock toggle.

---

## Authorization rules

- Owner, Administrator: view.
- Cashier: 403 and no nav.
- IDOR not applicable beyond listing permitted catalog (all products for managers).

---

## Business rules

- FR-INV-06.
- Threshold from settings, not per-product (DATABASE.md).

---

## Edge cases

- Threshold 0: only zero (and negative if any — should not exist) stock.
- No products: empty state.
- Settings row missing: fail safe (treat as seeder bug).

---

## Security considerations

- Authorize `viewInventory`.
- Cashiers must not see cost-focused inventory if you display cost — **do not show cost_price on this page unless Owner/Admin; optional omit cost entirely** to keep overview operational.

---

## Testing requirements

- Product with stock <= threshold appears in low-stock filter.
- Cashier denied.
- Page is read-only (no PATCH).

---

## Acceptance criteria

- [ ] FR-INV-06.
- [ ] NOT-04 managers can identify low stock.

---

## Definition of Done

- [ ] Overview usable.
- [ ] Tests pass.
- [ ] No adjustment form.
