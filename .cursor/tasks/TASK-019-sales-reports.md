# TASK-019 — Sales Reports

**Task ID:** TASK-019  
**Title:** Sales reports  
**Filename:** `TASK-019-sales-reports.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let Owner and Administrator view daily and date-range sales reports: completed sales total and transaction count for a clearly shown period, reconciling with transaction records.

---

## Background

MVP-08, REP-01–03, REP-05–09, SFR-REP-01–02, AC-12, CURSOR.md §32, UI_UX.md §34.

CSV/PDF export is out of MVP (PRD IMP/EXP).

---

## Dependencies

- TASK-012, TASK-014
- TASK-004 (`viewReports`)
- TASK-018 (reuse eligibility rules / queries if possible)

---

## Files likely affected

- `app/Livewire/Reporting/SalesReport.php`
- `app/Queries/Reporting/DailySalesQuery.php`
- `app/Queries/Reporting/DateRangeSalesQuery.php` (or one query with a range)
- `resources/views/livewire/reporting/`
- `routes/web.php`
- `tests/Feature/Reporting/`

---

## Database changes

None.

---

## Backend requirements

1. Default period: today. User may select a valid from–to date (REP-02).
2. Metrics: sum of `total`, count of completed transactions (REP-03).
3. Optional detail table: paginated transactions in range (invoice, time, cashier, total) — UI_UX report detail; keep aggregates from SQL SUM/COUNT.
4. Display period on the page (REP-06).
5. Empty range: empty state, totals 0 (REP-07).
6. Do not recompute from current product prices (REP-08).

---

## Frontend requirements

- Header + summary + detail (`UI_UX.md` §34).
- Date range filter; invalid range inline error.
- Money formatting from store settings.
- Loading state for slower ranges (PERF-05 target for 30 days).

---

## Validation rules

| Field | Rules |
|---|---|
| date_from | required date |
| date_to | required date, >= date_from |
| | reject extremely large ranges only if needed (e.g. > 366 days) — optional guard |

---

## Authorization rules

- Owner/Admin: Full.
- Cashier: No (403).
- Detail rows must not be a backdoor for cashiers.

---

## Business rules

- BR-19, REP-05, REP-09.
- Dashboard today vs report today must match (DASH-05).

---

## Edge cases

- from = to (single day).
- Inclusive timestamps: define start of from-date through end of to-date in app timezone; test a sale at 23:59.
- No data.

---

## Security considerations

- `viewReports`.
- Parameterized dates.

---

## Testing requirements

- AC-12: seeded completed transactions in a period match report sum and count.
- Incomplete/missing checkout not included.
- Price change on product does not change report.
- Cashier denied.
- Dashboard today equals report today for the same dataset.

---

## Acceptance criteria

- [ ] REP-01–03, REP-05–09.
- [ ] AC-12.

---

## Definition of Done

- [ ] Sales report usable.
- [ ] Aggregation in SQL.
- [ ] Tests pass.
- [ ] No export.
