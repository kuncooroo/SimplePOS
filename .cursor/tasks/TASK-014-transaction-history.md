# TASK-014 — Transaction History

**Task ID:** TASK-014  
**Title:** Transaction history  
**Filename:** `TASK-014-transaction-history.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let authorized users list, search, filter, and view completed transactions and their snapshot details. No edit, void, refund, or delete.

---

## Background

MVP-06, FR-TRX-02–07, SFR-TRX-04–05, SRCH-05–10, BR-13–14, AC-08, AC-09, UI_UX tables/filters.

---

## Dependencies

- TASK-012, TASK-013
- TASK-004 (`viewAllTransactions` / own)

---

## Files likely affected

- `app/Livewire/Sales/TransactionIndex.php`
- `app/Livewire/Sales/TransactionShow.php`
- `app/Policies/TransactionPolicy.php`
- `resources/views/livewire/sales/`
- `routes/web.php`
- `tests/Feature/Sales/`

---

## Database changes

None. Add indexes only if missing from TASK-012 (invoice unique, completed_at, cashier_id, composite cashier+completed_at).

---

## Backend requirements

1. Paginated list of `COMPLETED` transactions only.
2. Search by invoice number (FR-TRX-03, SRCH-05).
3. Date-range filter (FR-TRX-04).
4. Cashier filter for management roles (FR-TRX-05).
5. Reset pagination when filters change.
6. Detail: header + items snapshots + cashier name (even if cashier later inactive — BR-14).
7. Eager-load `items`, `cashier` (N+1).
8. No update/delete endpoints.

---

## Frontend requirements

- Transactions table (`UI_UX.md` §11): invoice, datetime, cashier, total, actions view/receipt.
- Filters: invoice, dates, cashier (managers).
- Empty and no-results states.
- Detail page standard (`UI_UX.md` §25).
- Cashier nav: Transactions yes; filters limited.

---

## Validation rules

| Filter | Rules |
|---|---|
| invoice | nullable string, max 64 |
| date_from, date_to | nullable dates; if both set, from <= to |
| cashier_id | nullable exists users; ignore if unauthorized to filter |

---

## Authorization rules

- Owner/Admin: all transactions.
- Cashier: own/allowed only (matrix). Default **own** = `cashier_id === auth id`.
- `view` on detail/receipt must match list scope (SRCH-10).
- No role can hard-delete (matrix).

---

## Business rules

- BR-13, BR-14, FR-TRX-06–07.
- Failed checkouts never appear (they were never inserted).

---

## Edge cases

- Invalid date range rejected.
- Clearing filters restores permitted set (SRCH-09).
- Invoice search is precise enough not to leak other cashiers’ invoices to a cashier (query scoped first).

---

## Security considerations

- IDOR on show/receipt.
- Do not expose cost_price.
- Authorize Livewire `render` query, not only UI.

---

## Testing requirements

- AC-09 invoice search.
- AC-08: change product price after sale; detail still shows snapshot unit_price.
- Cashier cannot open another cashier’s transaction.
- No delete/edit routes.
- Pagination with > page size records.

---

## Acceptance criteria

- [ ] FR-TRX-02–07.
- [ ] AC-08, AC-09.

---

## Definition of Done

- [ ] History + detail complete.
- [ ] Tests pass.
- [ ] No refund/void UI.
