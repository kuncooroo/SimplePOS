# TASK-020 — Product Sales Reports

**Task ID:** TASK-020  
**Title:** Product sales reports  
**Filename:** `TASK-020-product-sales-reports.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let Owner and Administrator see quantities (and line amounts from snapshots) sold per product for a selected period, using transaction item snapshots rather than current catalog prices.

---

## Background

MVP-08, REP-04, SFR-REP-03, REP-05–08, AC-12 companion.

---

## Dependencies

- TASK-019 (same auth, period UX, completed-only rule)

---

## Files likely affected

- `app/Livewire/Reporting/ProductSalesReport.php`
- `app/Queries/Reporting/ProductSalesQuery.php`
- `resources/views/livewire/reporting/`
- `routes/web.php`
- `tests/Feature/Reporting/`

---

## Database changes

None. Optional index on `transaction_items (product_id)` already in DATABASE.md.

---

## Backend requirements

1. Same date-range rules as sales report.
2. Group by product identity: prefer `product_id` when not null, display `product_name_snapshot` (and SKU snapshot) so renamed products still show sale-time name **or** show both current name and snapshot — **required:** quantities and amounts from `quantity` and `line_total` / `unit_price`, never `products.selling_price`.
3. Paginate grouped results if large.
4. Empty state.
5. Join only completed parent transactions.

---

## Frontend requirements

- Product sales table: product (snapshot name/SKU), qty sold, sales amount (sum of line_total).
- Period displayed.
- Nav under Reports.

---

## Validation rules

Same date range as TASK-019.

---

## Authorization rules

- `viewReports`. Cashier denied.

---

## Business rules

- SFR-REP-03, BR-13, REP-08.

---

## Edge cases

- Product deleted/deactivated: `product_id` may still point at row; if null, group by sku_snapshot + name_snapshot.
- Same product renamed: grouping by `product_id` combines rows; name column can show latest snapshot in range or current name with footnote. **Recommended:** group by `product_id`, display MAX/any snapshot name plus current name if different.

---

## Security considerations

- Same as sales reports.

---

## Testing requirements

- Two sales of product A in range: qty sums.
- Change current selling price: report amount unchanged.
- Cashier denied.

---

## Acceptance criteria

- [ ] REP-04 and snapshot-based amounts.
- [ ] Reconciles with items of completed transactions in the same period.

---

## Definition of Done

- [ ] Product sales report complete.
- [ ] Tests pass.
- [ ] No export.
