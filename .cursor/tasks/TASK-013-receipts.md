# TASK-013 — Receipts

**Task ID:** TASK-013  
**Title:** Printable receipts  
**Filename:** `TASK-013-receipts.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Show a receipt for a completed transaction using store identity, sale snapshots, payment, change, and receipt footer, with a printable view.

---

## Background

MVP-09 receipt, FR-RCP-01–04, SFR settings/receipt, LOC-04, BUSINESS_FLOW §11, UI_UX receipt layout, AC-05 receipt available.

---

## Dependencies

- TASK-012
- TASK-008 (store name, logo, footer, currency)

---

## Files likely affected

- `app/Http/Controllers/Sales/ReceiptController.php`
- `app/Policies/TransactionPolicy.php` (view)
- `resources/views/layouts/receipt.blade.php`
- `resources/views/receipts/show.blade.php`
- `routes/web.php`
- `tests/Feature/Sales/`

Livewire is optional; print is a full page (`window.print`).

---

## Database changes

None.

---

## Backend requirements

1. Named route `transactions.receipt` (or `receipts.show`) with route-model binding.
2. Load transaction + items + cashier; do **not** join current product price for display amounts.
3. Store settings for header/logo/footer.
4. Authorize view (same as transaction detail: cashier own/allowed, managers all). If TASK-014 is not done, allow: actor is cashier of the record **or** `viewAllTransactions`.
5. 404 for missing; 403 for unauthorized.

---

## Frontend requirements

- Receipt content: store identity, invoice, datetime, cashier display name, items (name snapshot, qty, prices), subtotal, discount, total, cash, change, footer (FR-RCP-02–03).
- Print stylesheet / print button (FR-RCP-04).
- Money format from settings (LOC-04).
- After checkout, POS should navigate here.

---

## Validation rules

None (read-only). Invalid id → 404.

---

## Authorization rules

- Cashier: own/allowed transactions only (SEC-07).
- Owner/Admin: any completed transaction.
- Guest: no.

---

## Business rules

- Snapshots only (BR-13).
- Footer from current settings is allowed on **print** (FR-SET-06: subsequent documents). Financial lines stay historical.

---

## Edge cases

- Missing logo: skip image, still print.
- Empty footer: omit section.
- Long product names wrap for thermal-ish width; keep simple readable layout.

---

## Security considerations

- IDOR tests for cashier A reading cashier B receipt.
- Escape all snapshot text.
- Logo URL must not allow path traversal.

---

## Testing requirements

- Successful sale receipt contains invoice, snapshot price, store name.
- Product master price change does not change receipt line (can wait until AC-08 with TASK-014; include here if easy).
- Unauthorized cashier 403.

---

## Acceptance criteria

- [ ] FR-RCP-01–04.
- [ ] Receipt available after checkout (AC-05).

---

## Definition of Done

- [ ] Printable receipt works.
- [ ] Authorization tested.
- [ ] No PDF export package required.
