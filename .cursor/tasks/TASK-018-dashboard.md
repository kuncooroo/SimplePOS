# TASK-018 — Dashboard

**Task ID:** TASK-018  
**Title:** Dashboard  
**Filename:** `TASK-018-dashboard.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Show Owner and Administrator today’s sales total, today’s completed transaction count, low-stock visibility, and a best-seller indicator, with empty/zero states. Cashiers do not get business-wide metrics.

---

## Background

MOD-02, DASH-01–07, PERF-04, SRS reporting architecture, UI_UX.md §8.

Reports (TASK-019) must reconcile with the same completed-transaction rules when the period is “today”.

---

## Dependencies

- TASK-012 (transactions)
- TASK-008 / TASK-015 (low stock)
- TASK-004 (`viewDashboard`)

---

## Files likely affected

- `app/Livewire/Reporting/Dashboard.php`
- `app/Queries/Reporting/DashboardMetricsQuery.php` or small query classes
- `resources/views/livewire/reporting/dashboard.blade.php`
- `routes/web.php` (default post-login for Owner/Admin)
- `tests/Feature/Reporting/`

Use DB aggregation, not loading all rows into PHP (`CURSOR.md` §32).

---

## Database changes

None.

---

## Backend requirements

1. Sales today: sum `transactions.total` where `status=COMPLETED` and `completed_at` in current business day (store timezone: use `config('app.timezone')`; document it; default Asia/Jakarta is reasonable for IDR retail — verify, do not silently invent if app timezone already set).
2. Transaction count today: count completed same period.
3. Low stock: count and/or short list using TASK-015 rule.
4. Best seller: defined period (recommend **today**, or last 7 days — **pick today for DASH-04 and document**). Aggregate `transaction_items.quantity` for completed sales in that period; use snapshot product identity (`product_name_snapshot` or `product_id`).
5. No sales → explicit 0 / empty (DASH-07), not a spinner forever.
6. Exclude non-completed (none should exist).

---

## Frontend requirements

- Four priority cards (`UI_UX.md` §8): Sales today, Transactions today, Low stock, Best seller.
- No decorative charts required.
- Cashier login must **not** land here with these metrics (DASH-06). Keep cashier on POS.

---

## Validation rules

None (read-only).

---

## Authorization rules

- Owner/Admin: Full.
- Cashier: Limited/No — **deny `viewDashboard`** (TASK-004). Direct URL 403.
- Do not show other cashiers’ PII beyond what metrics need (best seller is product, not cashier).

---

## Business rules

- DASH-05: same eligibility as reports (completed only, snapshot sums).
- BR-19.

---

## Edge cases

- Timezone around midnight.
- Tied best sellers: pick deterministic winner (highest qty, then name) and document.
- Low stock none: zero state.

---

## Security considerations

- Authorize `viewDashboard`.
- Aggregates only; no leaking of unauthorized transaction lists on the dashboard.

---

## Testing requirements

- Known completed sale today appears in total and count.
- Failed checkout (no row) does not appear.
- Cashier 403.
- Empty day shows zeros.
- Product price change does not change today’s total (uses transaction.total).

---

## Acceptance criteria

- [ ] DASH-01–07.
- [ ] PERF-04 considered (aggregation + indexes on completed_at).

---

## Definition of Done

- [ ] Dashboard usable for management roles.
- [ ] Tests pass.
- [ ] No cashier business-wide metrics.
