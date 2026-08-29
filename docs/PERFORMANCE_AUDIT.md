# Performance Audit

## SimplePOS — Sistem Kasir & Penjualan Sederhana

| Field | Value |
|---|---|
| Document | `docs/PERFORMANCE_AUDIT.md` |
| Date | 2026-08-28 |
| Role | Senior Laravel Performance Engineer |
| Product | SimplePOS Version 1.0 MVP |
| Runtime code in this repo | **None** (`artisan` / `app/` absent) |
| Basis | PRD PERF-01–06, SRS §19 / §30 / §36–37, DATABASE.md §11–12 / §33, CURSOR.md §30–32 / §53, SYSTEM_DESIGN cache/queue ADRs, `.cursor/tasks/` |

---

# 1. Executive summary

There is **no running SimplePOS application** to profile. This audit is a **design and implementation-risk review**: where the documented POS, dashboard, and reports will get slow on a **single-store VPS** if Laravel code ignores the schema and query rules already written.

**Production profile (realistic, not hypothetical hyperscale):**

| Dimension | Expected MVP |
|---|---|
| Users | A few Owner/Admin + 1–5 cashiers |
| Products | Hundreds to a few thousand |
| Sales history | Grows without purge (DATABASE.md §34) |
| Concurrency | Two cashiers on the same SKU is the hard case |
| Host | One VPS, one MySQL, file sessions |

Under that profile, Redis, queues, search engines, and summary tables are **premature**. Missing **pagination**, **eager loading**, **SQL aggregates**, and the **indexes already specified** are the failures that will actually show up after a busy year.

**Do not add:** Redis, Meilisearch/Elasticsearch, Horizon, dashboard cache as source of truth, queued checkout, read replicas.

**Do implement from day one (baseline, not “optimization”):**

1. Paginate every unbounded list (PERF-06).
2. `with()` on list/detail that would otherwise N+1.
3. `SUM`/`COUNT`/`GROUP BY` in MySQL for dashboard and reports (`CURSOR.md` §32).
4. Indexes from DATABASE.md §11–12.
5. Bounded POS search (limit + debounce).
6. Checkout `lockForUpdate` on product rows (correctness first; also avoids retry storms).

---

# 2. Method

Reviewed documented query paths, not production `EXPLAIN` output.

When TASK-001+ exist, re-run this audit with:

- `EXPLAIN` on POS search, checkout, dashboard, 30-day report, product-sales report.
- Laravel Debugbar or `DB::listen` **in local only**.
- A fixture of ~5,000 products and ~50,000 transactions before claiming PERF-05.

Targets (PRD §31) apply only after dataset, VPS size, and timezone are written down (SRS §36).

---

# 3. Findings (realistic production risk)

Severity here means **impact on a busy single store**, not internet-scale.

## PA-001 — Unpaginated history will collapse first

**Severity:** High (after months of sales, not day one)  
**Where:** Transaction list, stock movements, audit log, product admin, report detail rows  
**What goes wrong:** `Transaction::with('items')->get()` or Livewire binding an entire year’s sales. PHP memory spikes; TTFB exceeds PERF-06. Retention is **indefinite** (DATABASE.md §34), so this is certain, not speculative.  
**Fix (baseline):** `paginate()` (or cursor pagination later) on products, transactions, movements, audit, users, report line tables. Reset page when filters change (TASK-014).  
**Do not:** Cache the full list.

## PA-002 — Dashboard / reports summed in PHP

**Severity:** High if implemented that way  
**Where:** TASK-018 / 019 / 020  
**What goes wrong:** Load all `COMPLETED` rows for “today” or 30 days into a Collection and `->sum('total')`. At tens of thousands of rows this misses PERF-04/05 and fights the CPU on a small VPS.  
**Fix (baseline):**

```text
SELECT SUM(total), COUNT(*) FROM transactions
WHERE status = 'COMPLETED' AND completed_at BETWEEN ? AND ?
```

Product sales:

```text
SELECT product_id, SUM(quantity), SUM(line_total)
FROM transaction_items
INNER JOIN transactions ON ...
WHERE transactions.status = 'COMPLETED' AND completed_at BETWEEN ? AND ?
GROUP BY product_id
```

Use `Queries/Reporting/*` (`PROJECT_STRUCTURE.md`). Same rules for dashboard and reports so they reconcile (DASH-05).  
**Do not:** Precomputed rollup tables until EXPLAIN + 50k-row fixture fail PERF-05.

## PA-003 — N+1 on transaction and movement screens

**Severity:** High when lists exist  
**Where:** Transaction index/detail, receipt, movement history, POS cart refresh  
**What goes wrong:** Per-row `cashier`, `items`, `product`, `user` lazy loads → 1 + N queries. Livewire re-renders amplify this every keystroke if the list is on the POS page.  
**Fix (baseline):** `with(['cashier', 'items'])` on history; `with(['product', 'user', 'transaction'])` on movements; POS search `select` only needed columns. Do **not** eager-load every relationship on Product (`CURSOR.md` §30).  
**Watch:** `with('items.product')` on a paginated transaction list is usually wasted (snapshots are on the item).

## PA-004 — POS search without bound + leading-wildcard LIKE

**Severity:** Medium–High at a few thousand SKUs  
**Where:** TASK-009  
**What goes wrong:** `Product::where('name', 'like', '%'.$q.'%')->get()` with no `limit`, no `active` filter in SQL, full catalog hydrated into Livewire state. PERF-01 (2s) fails; every keyup hits MySQL. `%term%` cannot use `products.name` index.  
**Fix (baseline):** Filter `active = true` in SQL; `limit` (~20); debounce Livewire; **exact** match on `sku` and `barcode` first (unique indexes). Name search: prefix `like 'term%'` if UX allows, else accept a table scan **only** because the catalog is small—still cap rows.  
**Do not:** Add Scout/Meilisearch for MVP.

## PA-005 — Livewire serializing fat models every request

**Severity:** Medium (POS is the hottest path)  
**Where:** Cart/POS public properties  
**What goes wrong:** Storing full `Product` Eloquent models (or unbounded collections) in Livewire state. Each qty change dehydrates/hydrates large payloads; PERF-02 (1s) suffers on slow links. Also a **correctness** smell if prices live on the component.  
**Fix:** Cart holds `{product_id, quantity}` only; reload price/stock in the Action/calculator. Keep search results as a short DTO/array, not `Product::all()`.  
**Do not:** Cache cart totals as authority.

## PA-006 — Missing or unused indexes on filter/report paths

**Severity:** Medium (grows with history)  
**Where:** Migrations vs DATABASE.md §11–12  
**Must exist at launch:**

| Index | Why |
|---|---|
| `products.sku` UNIQUE | POS + admin lookup |
| `products.barcode` UNIQUE nullable | POS barcode |
| `products.name`, `active`, `category_id` | Admin/POS filters |
| `transactions.invoice_number` UNIQUE | AC-09 search |
| `transactions.completed_at` | Dashboard, reports, date filter |
| `transactions.cashier_id` | Manager filter |
| `(cashier_id, completed_at)` | Cashier + date (common) |
| `transaction_items.transaction_id` | Receipt/detail |
| `transaction_items.product_id` | Product-sales report |
| `(product_id, occurred_at)` on `stock_movements` | Movement history |
| `activity_logs.occurred_at` + `(subject_type, subject_id, occurred_at)` | Audit UI |

`(status, completed_at)` is **optional** while MVP only stores `COMPLETED` (DATABASE.md §12).  
`products.stock_quantity` index only if low-stock `EXPLAIN` shows a scan on a large catalog.

**Do not:** Index every column “just in case.”

## PA-007 — Low-stock and dashboard as four full table walks

**Severity:** Medium  
**Where:** TASK-015 / 018  
**What goes wrong:** Four independent `Product::all()` / `Transaction::all()` for cards. Low stock without `active` + threshold in SQL. Best-seller computed in PHP.  
**Fix:** One aggregate query per card (or one grouped query). Low stock: `WHERE active = 1 AND stock_quantity <= :threshold` with a **limit** on the dashboard widget (count + top N), full list paginated on inventory.  
**Do not:** Cache dashboard as source of truth (SRS §19). If later measured slow, cache **count/sum for “today”** with a short TTL and invalidate on checkout—not Redis required; `Cache::store('file')` is enough *after* measurement.

## PA-008 — Checkout doing extra work besides the required transaction

**Severity:** Medium if mis-implemented  
**Where:** TASK-012  
**What goes wrong:** Per-line queries without locking; N product `save()` without `lockForUpdate`; generating invoices with a `max()` table scan under contention; logging/audit/events inside the hot path; loading all categories for a receipt.  
**Fix:** One transaction; lock only sold product rows; unique invoice constraint (retry on collision); no queue; no extra `activity_logs` for sales (DATABASE.md §20). PERF-03 (3s) is ample if this stays a handful of indexed writes.  
**Do not:** Queue checkout to “make it faster.”

## PA-009 — Unbounded report date range

**Severity:** Medium  
**Where:** TASK-019 / 020  
**What goes wrong:** Owner selects 10 years; `SUM` still scans all `completed_at` history. With a proper index this is often fine on MySQL; without pagination of **detail** rows, the HTML table is the real OOM.  
**Fix:** SQL aggregates for summary (always). Paginate detail. Optional P2: cap range (e.g. 366 days) if EXPLAIN + fixtures show multi-second scans.  
**Do not:** Warehouse or materialized views for MVP (SRS §30.9).

## PA-010 — Repeated queries in Livewire render

**Severity:** Medium  
**Where:** Nested POS components  
**What goes wrong:** `ProductSearch`, `CartPanel`, and `PaymentPanel` each query store settings, user, and the same products on one request.  
**Fix:** Resolve settings once per request (`StoreSetting::query()->first()` is one row—cheap). Share cart payload from the parent. `remember()` on settings is optional later (invalidate on TASK-008 update).  
**Do not:** Request-level Redis.

## PA-011 — File handling

**Severity:** Low for MVP  
**Where:** Store logo only  
**What goes wrong:** Storing originals at 20MB, or `Intervention\Image` on every receipt paint; reading logo from remote disk per line item.  
**Fix:** Validate size at upload (TASK-008). Store one file; receipts use the path once per page. No image processing package unless measured.  
**Do not:** Object storage until multi-node (SYSTEM_DESIGN local disk is accepted).

## PA-012 — Debug / logging in production

**Severity:** High **if** `APP_DEBUG=true` or query log on  
**Where:** Environment (see `docs/SECURITY_AUDIT.md` SA-004)  
**What goes wrong:** Debugbar and `APP_DEBUG` dump queries and double response time. Verbose `Log::debug` of full cart JSON per checkout fills the disk.  
**Fix:** Production debug off. Log failures with ids, not payloads.

## PA-013 — API response sizes

**Severity:** N/A  
**Where:** No public API (ADR-10)  
**Fix:** Do not add JSON APIs that dump `Product::with(...)` collections. If an API appears later, paginate and sparse fieldsets.

## PA-014 — Memory: factories and reports in artisan

**Severity:** Low until someone runs a one-off  
**What goes wrong:** `Product::all()` in a seeder or “export” command; Excel libraries loading all sales (import/export is out of MVP).  
**Fix:** Chunk seeders; never `all()` for reports.

---

# 4. Review checklist vs requested topics

| Topic | Realistic issue? | Guidance |
|---|---|---|
| N+1 queries | **Yes** on lists | Eager load list relations; not everything |
| Missing indexes | **Yes** if migrations skip DATABASE.md | Ship §11; add composites when filters exist |
| Large queries | **Yes** if `get()` on history | Paginate + date bounds |
| Pagination | **Yes** (PERF-06, no purge) | Mandatory on listed modules |
| Eager loading | **Yes** | Intentional `with()` |
| Database aggregation | **Yes** | Dashboard/reports in SQL |
| Expensive dashboard | **Yes** if PHP sums | Four cheap aggregates, not four full loads |
| Caching | **Not yet** | Settings/file cache only after measure; never checkout/stock |
| Queue candidates | **Not checkout** | Future: email, large export only |
| File handling | **Low** | One small logo |
| Memory usage | **Yes** with unbounded Eloquent | Paginate; cart IDs only |
| API response sizes | **N/A** | No API |
| Repeated queries | **Yes** in Livewire | One settings read; parent owns cart |
| Slow reports | **Yes** at 30 days × many items if scanned in PHP | SQL `SUM`/`GROUP BY` + index on `completed_at` |

---

# 5. Recommended optimizations (ordered)

Do these **in implementation**, not as a later “perf project.”

| Order | Action | Why |
|---|---|---|
| 1 | Paginate all operational lists | PERF-06; unbounded growth |
| 2 | Migrate indexes from DATABASE.md | Cheap, required for lookup |
| 3 | POS search: `active`, limit, sku/barcode exact | PERF-01 |
| 4 | Cart/checkout: ids + server reload; `lockForUpdate` | PERF-02/03 + correctness |
| 5 | Dashboard/reports: SQL aggregates in Query objects | PERF-04/05, DASH-05 |
| 6 | `with()` on history/receipt/movements | N+1 |
| 7 | Debounce POS search | Extra round trips |
| 8 | Select sparse columns on POS results | Payload |
| 9 | **Measure** with a large fixture | Avoid Redis |
| 10 | Optional: file-cache store settings (TTL + bust on save) | Only if settings queried everywhere |

Stop after step 8 unless a measured regression exists.

---

# 6. Caching and queues (explicit non-recommendations)

**Cache only if a profiler shows a hot read that is allowed to be slightly stale:**

- Store settings (one row)—weak candidate; query is already O(1).
- Dashboard “sales today”—only with explicit freshness (e.g. 30s) and invalidation on checkout. Easy to get **wrong** vs AC-12. Prefer skipping for MVP.

**Never cache:** stock, cart totals, authorization, completed transactions as write-behind.

**Never queue:** checkout, stock deduction, invoice, receipt readiness (`CURSOR.md` §13).

**Later queue (not performance-critical MVP):** email, CSV export, if those features are approved.

---

# 7. Checkout locking vs “performance”

`SELECT ... FOR UPDATE` on sold products can wait tens of milliseconds under two cashiers. That is **correct**. Optimizing it away (no lock, or queue) creates oversell, which is a **P0 product defect**, not a perf win.

Invoice `UNIQUE` plus short retry is cheaper than a global lock table.

---

# 8. Missing runtime evidence

| Check | Status |
|---|---|
| `EXPLAIN` on production-like data | Not possible (no app) |
| N+1 in Livewire | Not possible |
| Debugbar traces | Not possible |
| 30-day report wall time | Not possible |

Re-audit after TASK-012, TASK-018, and TASK-019 with a seeded dataset. Until then, treat PA-001–PA-007 as **implementation constraints**, not optional polish.

---

# 9. Production acceptance (when code exists)

Document before claiming PERF-*:

- Product count, transaction count, item rows
- Concurrent cashiers
- VPS CPU/RAM / MySQL buffer pool
- `app.timezone` for “today”
- How time was measured (server vs browser)

Then:

```text
[ ] No list endpoint returns unpaginated Eloquent collections
[ ] Dashboard/report totals are SQL aggregates
[ ] POS search limited and filtered in SQL
[ ] Transaction list eager-loads cashier (not items of all rows)
[ ] EXPLAIN on 30-day SUM uses completed_at (or equivalent)
[ ] APP_DEBUG=false
[ ] No Redis/queue on checkout
```
