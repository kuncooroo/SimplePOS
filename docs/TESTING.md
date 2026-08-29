# Testing Strategy

## SimplePOS — Sistem Kasir & Penjualan Sederhana

| Field | Value |
|---|---|
| Document | `docs/TESTING.md` |
| Date | 2026-08-28 |
| Role | Senior Laravel QA Engineer |
| Product | SimplePOS Version 1.0 MVP |
| Authoritative sources | `PRD.md` §34–35, `SRS.md` §33, `CURSOR.md` §36–37, `BUSINESS_FLOW.md`, `DATABASE.md`, `docs/PROJECT_STRUCTURE.md`, `.cursor/tasks/` |

This document defines **what must be tested**, **in what order**, and **where tests live**. It does not invent extra product features. “Works in the browser” is not sufficient for checkout (`CURSOR.md` §36).

---

# 1. Principles

1. Name tests after **business behavior**, not framework internals.
2. Use Arrange–Act–Assert.
3. Prefer Feature tests that hit HTTP/Livewire + the database for workflows.
4. Use Unit tests only for pure calculation and small generators (totals, change, invoice format).
5. Authorization is tested by **denied** cases, not only happy paths (AC-11).
6. Financial tests assert **database state**, not only UI text.
7. Failed checkout must assert **absence** of completed rows, stock change, and movements (AC-14).
8. Follow the test runner created at project init (PHPUnit 12 on Laravel 13, or Pest if that is what the installer generated). Do not add a second runner.

Good names (`CURSOR.md` §37):

```text
cashier_cannot_checkout_when_stock_is_insufficient
completed_transaction_preserves_sale_time_product_price
administrator_cannot_deactivate_protected_owner
```

---

# 2. Test types

## 2.1 Unit tests

Isolated PHP, no HTTP, database optional (prefer none).

| Subject | Class under test | Assert |
|---|---|---|
| Subtotal, discount cap, total ≥ 0 | `CheckoutCalculator` | BR-05, BR-06, FR-DISC-02 |
| Change = cash − total | `CheckoutCalculator` | BR-07, BR-08 |
| Insufficient cash detected | `CheckoutCalculator` | AC-06 logic |
| Invoice uniqueness algorithm | `InvoiceNumberGenerator` | BR-01 (format + uniqueness helper) |
| Gate helpers on `UserRole` | enum / policy unit if thin | Owner vs Cashier abilities |

Do not unit-test Eloquent as a substitute for checkout Feature tests.

## 2.2 Feature tests

HTTP or Livewire + `RefreshDatabase`. Default for almost all MVP behavior.

Covers: login, CRUD permissions, POS Livewire, checkout Action via the UI or a thin Livewire call, reports, settings, audit.

## 2.3 Integration tests

In this project, **Feature tests with a real schema (SQLite in-memory or MySQL) are the integration suite**. Do not add a third folder named `Integration` unless a cross-system boundary appears (none in MVP).

Checkout Feature tests **are** integration tests: they must touch `transactions`, `transaction_items`, `products.stock_quantity`, and `stock_movements` in one assertion block.

## 2.4 Authorization tests

Every protected module: Owner allowed, Administrator limits, Cashier denied, IDOR.

Minimum:

- Cashier cannot manage users or adjust stock (AC-11).
- Administrator cannot create/promote/demote/deactivate protected Owner.
- Cashier cannot open another cashier’s transaction/receipt.
- Cashier cannot `viewDashboard`, `viewReports`, `viewAuditLog`, `manageStoreSettings`.
- All roles denied hard-delete of completed transactions.

Use `$this->actingAs($user)` plus `assertForbidden()` / `assertStatus(403)` on HTTP, and `Livewire::actingAs($user)->test(...)->assertForbidden()` / `assertUnauthorized()`. Also assert `Gate::forUser($cashier)->denies('adjustStock')` in TASK-004 before routes exist.

## 2.5 Validation tests

Invalid payloads must not write business state.

| Domain | Reject |
|---|---|
| Auth | empty email/password |
| Users | duplicate email; weak/missing password on create |
| Categories | empty name |
| Products | duplicate SKU; duplicate non-null barcode; negative price; inactive category on assign |
| Cart / checkout | empty cart; qty ≤ 0; qty > stock; inactive product |
| Payment | cash < amount due; discount > subtotal |
| Stock adjust | empty reason; zero delta; resulting stock < 0 |
| Settings | invalid logo MIME/size; unknown currency |

Assert validation errors **and** unchanged row counts.

## 2.6 Database tests

- Unique `users.email`, `products.sku`, `products.barcode` (nullable unique), `transactions.invoice_number`.
- Foreign keys: product belongs to category; items belong to transaction; movements belong to product/user.
- Money columns remain `DECIMAL` (no float drift in assertions — compare strings or `bcmul` equals).
- `migrate:fresh` + seed Owner succeeds (TASK-001).
- Failed `CompleteSale` leaves zero `COMPLETED` transactions (rollback).

Use `RefreshDatabase`. PHPUnit in Laravel 13 defaults to SQLite `:memory:` — acceptable for CI. Add a MySQL CI job before commercial release if locking behavior must match production (`lockForUpdate`).

## 2.7 Business rule tests

Map BR-01–BR-22 to automated cases. Highest risk:

| Rule | Test |
|---|---|
| BR-01 unique invoice | two sequential sales → two invoices |
| BR-02 at least one item qty > 0 | empty cart rejected |
| BR-09 inactive not sold | POS add + checkout reject |
| BR-10 no oversell | qty > stock rejected; stock unchanged |
| BR-11 stock only on success | failed pay → stock unchanged |
| BR-12 failed checkout not revenue | reports exclude it (no row) |
| BR-13 snapshots | price change after sale |
| BR-14 cashier identity after deactivate | history still shows name |
| BR-15 reason + actor on manual adjust | AC-10 |
| BR-16 role permissions | AC-11 |
| BR-17 / BR-18 SKU / barcode unique | AC-04 |
| BR-19 reports completed only | AC-12 |
| BR-22 cash only | no other payment method accepted |

## 2.8 Workflow tests

End-to-end paths from `BUSINESS_FLOW.md`, executed as Feature/Livewire tests (browser optional — see P3).

Critical workflows (must automate):

1. **Authentication** — login success / fail / inactive / logout (`BUSINESS_FLOW` §4–5). AC-01, AC-02.
2. **User administration** — create user, change role/status, protected Owner (`§3`).
3. **Category + product maintenance** — create, deactivate, unique SKU (`§6–7`). AC-03, AC-04.
4. **POS cart** — add, merge line, qty, remove, subtotal (`§8`).
5. **Cash payment preview** — discount, cash, change (`§9`).
6. **Checkout completion** — atomic persist (`§10`, `§13`). AC-05, AC-06, AC-07, AC-14.
7. **Receipt** — snapshot + store identity (`§11`). AC-05, AC-13.
8. **Transaction search** — invoice + date range + cashier scope (`§12`). AC-09.
9. **Manual stock adjustment** — reason, movement, audit (`§14`). AC-10.
10. **Dashboard + reports** — completed-only, reconcile (`§16` and PRD reporting). AC-12.
11. **Settings** — logo/name; historical totals unchanged (AC-13).
12. **Audit recording** — user role/status, settings, stock (AUD-01–05).

Version 1.0 exit condition (`PRD` §38):

```text
login → product → cart → payment → checkout
→ transaction → stock → receipt → report
```

That path is TASK-023 and is **P0**.

## 2.9 Financial calculation tests

Unit + Feature:

- Line: `unit_price × quantity` (server price, not Livewire).
- Subtotal = sum of lines.
- Discount 0..subtotal; total = subtotal − discount ≥ 0.
- Change = cash_received − total when cash ≥ total.
- Stored `transaction.total` equals calculator output after reload.
- Report `SUM(total)` equals sum of completed rows in the same timezone window.
- Product sales `SUM(line_total)` uses snapshots, never `products.selling_price`.

Use integer cents or `decimal:2` string comparison. Do not assert floats.

## 2.10 File upload tests

Store logo only (MVP).

- Accept png/jpeg/webp within size limit.
- Reject svg, php, html, oversized files.
- Reject path payloads in original filename; stored name is not the client basename.
- Failed upload leaves previous `logo_path`.
- Cashier forbidden.
- `STORE_SETTINGS_UPDATED` audit does not store binary.

## 2.11 Notification tests

MVP has **no** mail/SMS/WhatsApp/Laravel `Notification` classes.

Test **in-app** feedback only:

- Livewire/session flash on successful checkout (not on failure).
- Validation messages identify the field (NOT-05).
- Insufficient stock warning (NOT-03).
- Success toast never shown when the Action rolled back (NOT-06).

Do not add Mail fake tests until email is an approved requirement.

## 2.12 API tests

MVP has **no public API** (ADR-10).

- **P3 / do not write** `tests/Feature/Api/` until a consumer exists.
- If `routes/api.php` appears without a requirement, that is a defect, not a test target.

## 2.13 Regression tests

Must stay green on every release (`SRS` §33.6):

- AC-01 through AC-14.
- The sale journey above.
- Cashier still denied user admin and stock adjust.
- Historical snapshots after catalog edits.

Implement as `tests/Feature/Regression/MvpJourneyTest.php` (TASK-023) plus the module Feature files. Do not skip P0 to ship a UI polish task.

---

# 3. Priority

## P0 — Critical (release-blocking)

No commercial or demo release if any P0 is missing or red.

| ID | Test | AC / rule | When |
|---|---|---|---|
| P0-01 | Active login; invalid login creates no session | AC-01, AC-02 | TASK-002 |
| P0-02 | Inactive user cannot authenticate | FR-AUTH-03 | TASK-002 |
| P0-03 | Cashier denied users + stock adjust | AC-11 | TASK-004 / 003 / 016 |
| P0-04 | Administrator cannot control protected Owner | PRD §20 | TASK-004 / 003 |
| P0-05 | Unique SKU (validation + DB) | AC-04, BR-17 | TASK-007 |
| P0-06 | Inactive product cannot be sold | BR-09 | TASK-009 / 012 |
| P0-07 | Valid cash sale: 1 transaction, items, stock↓, SALE movement, invoice | AC-05 | TASK-012 |
| P0-08 | Insufficient payment: no completed sale | AC-06 | TASK-012 |
| P0-09 | Insufficient stock: stock unchanged | AC-07 | TASK-012 |
| P0-10 | Checkout failure rollback (no completed + partial stock) | AC-14 | TASK-012 |
| P0-11 | Duplicate checkout submit does not create two sales | FR-PAY-06 | TASK-012 |
| P0-12 | Concurrent last-unit: no negative stock (lock test or sequential lock) | DATABASE §36 | TASK-012 |
| P0-13 | Snapshot price survives product price change | AC-08, BR-13 | TASK-014 |
| P0-14 | Reports match completed transactions for the same period | AC-12, BR-19 | TASK-019 |
| P0-15 | Sale journey Feature test (login→…→report) | PRD §38, TASK-023 | after TASK-019 |

## P1 — High

Required before MVP “Done”, but after P0 checkout/auth.

| ID | Test | When |
|---|---|---|
| P1-01 | Logout invalidates session | TASK-002 |
| P1-02 | Login throttled | TASK-002 |
| P1-03 | Unique barcode when present | TASK-007 |
| P1-04 | Product create happy path | AC-03, TASK-007 |
| P1-05 | Empty cart rejected | TASK-010 / 012 |
| P1-06 | Cart merges same product qty | TASK-010 |
| P1-07 | Calculator unit tests (discount, change) | TASK-011 |
| P1-08 | Invoice search | AC-09, TASK-014 |
| P1-09 | Cashier IDOR on transaction/receipt | TASK-013 / 014 |
| P1-10 | Manual adjust + movement + audit | AC-10, TASK-016 |
| P1-11 | Adjust without reason rejected | TASK-016 |
| P1-12 | Settings update; historical totals unchanged | AC-13, TASK-008 / 013 |
| P1-13 | Logo reject invalid type; old logo kept | TASK-008 |
| P1-14 | Dashboard today equals sales report today | TASK-018 / 019 |
| P1-15 | Product sales report uses snapshots | TASK-020 |
| P1-16 | Audit rows for user role/status | TASK-005 |
| P1-17 | User unique email; cashier 403 on user routes | TASK-003 |

## P2 — Medium

| ID | Test | When |
|---|---|---|
| P2-01 | Category CRUD + cashier 403 | TASK-006 |
| P2-02 | Inactive category cannot be assigned to new product | TASK-007 |
| P2-03 | POS search name/SKU/barcode; inactive omitted | TASK-009 |
| P2-04 | Stock overview low-stock filter | TASK-015 |
| P2-05 | Movement history lists SALE and MANUAL_ADJUSTMENT | TASK-017 |
| P2-06 | Audit log UI Owner-only; cashier 403 | TASK-021 |
| P2-07 | Profile: name/password; cannot set own role | TASK-022 |
| P2-08 | Deactivated cashier still listed on old transactions | TASK-003 / 014 |
| P2-09 | Pagination on products/transactions | TASK-007 / 014 |
| P2-10 | Guest redirected from protected routes | TASK-002 |
| P2-11 | Empty/zero dashboard and report states | TASK-018 / 019 |

## P3 — Optional

| ID | Test | Notes |
|---|---|---|
| P3-01 | Browser/Dusk (or equivalent) one sale journey | SRS §33.5; only if a project-supported tool is chosen. Do not add Dusk only for ceremony. |
| P3-02 | True parallel two-process oversell | Nice-to-have; P0-12 sequential lock test is enough for MVP |
| P3-03 | API Feature tests | **Out of MVP** |
| P3-04 | Mail/SMS notification fakes | **Out of MVP** |
| P3-05 | Visual regression / Percy | Not required |
| P3-06 | Load/perf suite | PERF-01–05 are manual/ops unless a bottleneck appears |

---

# 4. Recommended Laravel test structure

Match `docs/PROJECT_STRUCTURE.md`. Create a folder when the first test file exists.

```text
tests/
├── TestCase.php
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── LogoutTest.php
│   ├── Identity/
│   │   ├── UserManagementTest.php
│   │   ├── ProtectedOwnerTest.php
│   │   └── ProfileTest.php
│   ├── Catalog/
│   │   ├── CategoryTest.php
│   │   └── ProductTest.php
│   ├── Sales/
│   │   ├── PosSearchTest.php          # Livewire
│   │   ├── PosCartTest.php            # Livewire
│   │   ├── CheckoutTest.php           # P0
│   │   ├── ReceiptTest.php
│   │   └── TransactionHistoryTest.php
│   ├── Inventory/
│   │   ├── StockOverviewTest.php
│   │   ├── StockAdjustmentTest.php
│   │   └── StockMovementHistoryTest.php
│   ├── Configuration/
│   │   └── StoreSettingsTest.php
│   ├── Reporting/
│   │   ├── DashboardTest.php
│   │   ├── SalesReportTest.php
│   │   └── ProductSalesReportTest.php
│   ├── Audit/
│   │   ├── RecordActivityTest.php
│   │   └── ActivityLogUiTest.php
│   └── Regression/
│       └── MvpJourneyTest.php         # P0-15
└── Unit/
    ├── Sales/
    │   ├── CheckoutCalculatorTest.php
    │   └── InvoiceNumberGeneratorTest.php
    └── Support/
```

### Conventions

- `RefreshDatabase` on Feature tests that write.
- Factories: `UserFactory` states `owner()`, `administrator()`, `cashier()`, `inactive()`.
- `ProductFactory` / `CategoryFactory` for catalog.
- Livewire: `Livewire::test(PosPage::class)` (or the component that calls `CompleteSale`).
- Checkout assertions always include: `transactions` count, `transaction_items` count, `stock_quantity`, `stock_movements.movement_type`.
- Timezone: freeze `Carbon::setTestNow()` for “today” dashboard/report tests; use `config('app.timezone')`.
- Do not `assertSee` Tailwind class strings.
- phpunit.xml (Laravel 13 default): SQLite memory, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`. Keep checkout tests on `sync`.

### What not to create

- `tests/Feature/Api/`
- `tests/Feature/Notifications/` for mail
- Browser tests before a tool is chosen
- Tests for refunds, voids, gateways, multi-tenant

---

# 5. Mapping: PRD acceptance criteria → tests

| AC | Priority | Suggested test class |
|---|---|---|
| AC-01 | P0 | `Feature/Auth/LoginTest` |
| AC-02 | P0 | `Feature/Auth/LoginTest` |
| AC-03 | P1 | `Feature/Catalog/ProductTest` |
| AC-04 | P0 | `Feature/Catalog/ProductTest` |
| AC-05 | P0 | `Feature/Sales/CheckoutTest` |
| AC-06 | P0 | `Feature/Sales/CheckoutTest` |
| AC-07 | P0 | `Feature/Sales/CheckoutTest` |
| AC-08 | P0 | `Feature/Sales/TransactionHistoryTest` |
| AC-09 | P1 | `Feature/Sales/TransactionHistoryTest` |
| AC-10 | P1 | `Feature/Inventory/StockAdjustmentTest` |
| AC-11 | P0 | `Feature/Identity/` + `Feature/Inventory/` |
| AC-12 | P0 | `Feature/Reporting/SalesReportTest` |
| AC-13 | P1 | `Feature/Configuration/StoreSettingsTest` |
| AC-14 | P0 | `Feature/Sales/CheckoutTest` |

---

# 6. Missing tests in the current project

**Inspection date:** 2026-08-28.

| Location | Tests found |
|---|---|
| `d:\Magang\Project\SimplePOS\tests\` | **None** (directory absent) |
| `composer.json` / `phpunit.xml` in SimplePOS | **None** (Laravel app not in this repo) |

Therefore **every P0, P1, and P2 case in this document is missing**.

A leftover Laravel 13 skeleton outside the repo (`_simplepos_laravel_tmp`) only contains the default `tests/Feature/ExampleTest.php` (GET `/` returns 200) and `tests/Unit/ExampleTest.php`. That is **not** SimplePOS coverage: no roles, no checkout, no AC-01–14.

### Gap list (complete until TASK-001+)

Missing P0: login, inactive user, cashier denial, protected Owner, SKU unique, inactive not sold, successful sale integrity, insufficient pay/stock, rollback, double submit, stock lock, snapshot price, report reconcile, journey test.

Missing P1: logout, throttle, barcode unique, cart merge, calculator unit tests, invoice search, receipt IDOR, stock adjust+audit, settings/logo, dashboard vs report, product sales snapshots, user email unique.

Missing P2: categories, POS search, inventory overview, movements UI, audit UI, profile, pagination, empty reports.

Missing by design (do not treat as a quality gap): API tests, mail tests, refund tests.

---

# 7. Definition of tested-done

A module is not Done until:

1. Its task’s Testing requirements section is automated.
2. Related P0 rows in §3 are green.
3. Cashier-denied cases exist for any new privileged route.
4. Checkout-related changes re-run `CheckoutTest` + `MvpJourneyTest`.

The MVP itself is not Done until **all P0 tests pass** and AC-01–14 are green (`PRD` §35, TASK-023).

---

# 8. How to run (after TASK-001)

```text
php artisan test
php artisan test --filter=CheckoutTest
```

Use the project’s configured command (`composer test` if present). Record the actual runner (PHPUnit vs Pest) in this file after environment verification — currently **TBD until the app exists in this repository**.
