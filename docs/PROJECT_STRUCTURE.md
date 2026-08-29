# Project Structure

## SimplePOS — Sistem Kasir & Penjualan Sederhana

**Document Version:** 1.0  
**Status:** Recommended Laravel structure  
**Authoritative sources:** `docs/PRD.md`, `docs/SRS.md`, `docs/SYSTEM_DESIGN.md`, `docs/DATABASE.md`, `docs/BUSINESS_FLOW.md`, `CURSOR.md`

> This document defines how SimplePOS code should be organized. It is a Laravel-native modular monolith. Do not introduce extra layers, packages, or empty folders to make the tree look more “architectural.”

---

# 1. Purpose

SimplePOS is one deployable Laravel application for a single store. Checkout, stock, and reporting share one MySQL database and must complete in one request when a sale succeeds.

The structure must:

1. keep business boundaries visible;
2. keep controllers and Livewire components thin;
3. put multi-step workflows in Actions / Services;
4. stay familiar to any Laravel developer;
5. grow by adding files in the right folder, not by adding new abstraction types.

---

# 2. Architectural Choice

**Chosen:** Standard Laravel `app/` with **domain subfolders** for Actions, Services, Livewire, Controllers, Form Requests, and Queries.

**Rejected for MVP:**

| Pattern | Why not |
|---|---|
| Microservices | One checkout transaction must span sale + stock. |
| `nwidart/laravel-modules` or package-per-domain | Extra package and empty folders for eight tables. |
| Hexagonal / DDD `Domain` + `Application` + `Infrastructure` | Laravel already provides the application boundary. |
| Repository layer | ADR-07 and SRS §15: Eloquent is the persistence API. |
| Public API / SPA | Blade + Livewire is the delivery model. |
| CQRS infrastructure, event sourcing, queues for checkout | Correctness is synchronous. |

This matches `SYSTEM_DESIGN.md` §4, ADR-01, ADR-07, and `CURSOR.md` §4.

Logical modules are **namespaces and folders**, not separate Composer packages.

---

# 3. Request Lifecycle

```text
Browser
  → routes/web.php
  → middleware (session, CSRF, auth)
  → Policy / Gate
  → Controller or Livewire component
  → Form Request or Livewire validation
  → Action / Service  (business workflow + DB transaction)
  → Eloquent model
  → MySQL
  → Blade / Livewire response
```

Rules:

1. Presentation never owns final price, stock, totals, or permissions.
2. Authorization runs server-side on every protected write and sensitive read.
3. Checkout recalculates totals from authoritative product rows, not from Livewire/Alpine state.
4. Critical sale + stock work stays inside one database transaction in the Action, not in a listener or job.

---

# 4. Logical Modules

Map product capabilities to folders. Do not create a PHP package per module.

```text
Identity        Authentication, users, roles
Catalog         Categories, products
Sales           POS cart, checkout, transactions, receipts
Inventory       Current stock, manual adjustment, movements, low stock
Reporting       Dashboard metrics, sales reports, product sales
Configuration   Store settings, logo, receipt footer
Audit           Activity logs for sensitive changes
Shared          Layout, money display, enums used across modules
```

Coupling (from `SYSTEM_DESIGN.md`):

1. Sales may read Catalog.
2. Sales may change stock **only** by calling Inventory Actions inside the same DB transaction.
3. Reporting reads Sales and Inventory. It does not write them.
4. Configuration affects display and receipts. It does not rewrite historical sales.
5. Audit records sensitive changes. It does not decide whether checkout succeeded.
6. Identity supplies the authenticated user and role to every protected module.

---

# 5. Recommended Tree

Create a folder when the **first real class** belongs there. Do not pre-create Jobs, Events, Listeners, Notifications, Repositories, or DTO directories.

```text
simplepos/
|
├── app/
│   ├── Actions/                      # one use-case write workflows
│   │   ├── Audit/
│   │   ├── Catalog/
│   │   ├── Configuration/
│   │   ├── Identity/
│   │   ├── Inventory/
│   │   └── Sales/
│   │
│   ├── Enums/
│   ├── Exceptions/                   # only if a domain exception is clearer than a generic one
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   └── Sales/                # receipt print, non-Livewire exits
│   │   ├── Middleware/
│   │   └── Requests/                 # Form Requests, grouped by module
│   │       ├── Catalog/
│   │       ├── Configuration/
│   │       ├── Identity/
│   │       └── Inventory/
│   │
│   ├── Livewire/                     # full-page + nested interactive UI
│   │   ├── Audit/
│   │   ├── Catalog/
│   │   ├── Configuration/
│   │   ├── Identity/
│   │   ├── Inventory/
│   │   ├── Pos/
│   │   ├── Reporting/
│   │   └── Sales/
│   │
│   ├── Models/                       # Eloquent only; keep flat
│   ├── Policies/
│   ├── Providers/
│   ├── Queries/                      # complex reads only (reports)
│   │   └── Reporting/
│   ├── Services/                     # multi-step coordinators when an Action is not enough
│   │   ├── Inventory/
│   │   ├── Reporting/
│   │   └── Sales/
│   └── Support/                      # tiny shared helpers, not a dumping ground
│       └── Sales/
│
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── components/               # Blade UI kit
│       ├── layouts/
│       ├── livewire/                 # Livewire view counterparts
│       ├── auth/
│       ├── pos/
│       ├── receipts/
│       └── vendor/                   # pagination, etc.
│
├── routes/
│   └── web.php                       # only web routes for MVP
│
├── storage/
├── tests/
│   ├── Feature/
│   │   ├── Audit/
│   │   ├── Auth/
│   │   ├── Catalog/
│   │   ├── Configuration/
│   │   ├── Identity/
│   │   ├── Inventory/
│   │   ├── Reporting/
│   │   └── Sales/
│   └── Unit/
│       ├── Sales/
│       ├── Inventory/
│       └── Support/
│
├── docs/
└── CURSOR.md
```

Laravel 13 default folders (`Console`, `Jobs`, `Events`, `Listeners`, `Notifications`, `Mail`) stay unused until a requirement needs them.

---

# 6. Where Each Type Belongs

## 6.1 Models

**Location:** `app/Models/`

Keep all Eloquent models in one flat Laravel-standard directory. There are eight MVP tables. Nesting models by module fights framework discovery and does not pay off at this size.

| Model | Table | Module |
|---|---|---|
| `User` | `users` | Identity |
| `Category` | `categories` | Catalog |
| `Product` | `products` | Catalog |
| `Transaction` | `transactions` | Sales |
| `TransactionItem` | `transaction_items` | Sales |
| `StockMovement` | `stock_movements` | Inventory |
| `StoreSetting` | `store_settings` | Configuration |
| `ActivityLog` | `activity_logs` | Audit |

Models may contain:

- relationships;
- casts (including enums and decimals);
- query scopes;
- small accessors;
- `$fillable` / `$guarded` as the project convention requires.

Models must **not** contain:

- checkout orchestration;
- stock + movement + audit in `saving` / `created` observers;
- report aggregation;
- permission checks that belong in Policies.

No persistent `Cart` model. Cart state lives in the Livewire POS component until `CompleteSale` runs.

## 6.2 Controllers

**Location:** `app/Http/Controllers/{Module}/`

Controllers stay thin:

```text
authorize → validate → call Action/Service → redirect or view
```

Use controllers when the request is a **page load, form post, or print/download** with little reactive state.

MVP controller uses:

| Controller | Why a controller |
|---|---|
| `Auth\LoginController` / Laravel auth scaffolding | Session login and logout |
| `Sales\ReceiptController` | Printable receipt (`window.print`), not a Livewire round-trip |
| Optional export/download later | File response, not UI state |

Do **not** put checkout, stock mutation, or report SQL in controllers.

Do **not** duplicate a Livewire page with a fat controller for the same screen. If the screen is Livewire, the Livewire component is the HTTP entry. The controller is unnecessary.

## 6.3 Services

**Location:** `app/Services/{Module}/`

A Service is justified when several related operations share one domain and would be awkward as unrelated Actions.

Expected MVP services:

| Class | Role |
|---|---|
| `Services\Sales\CheckoutCalculator` | Pure totals: subtotal, discount, amount due, change. No DB writes. |
| `Services\Reporting\DashboardMetricsService` or skip in favor of Queries | Only if dashboard needs more than one query object. |

Do **not** create:

- `UserService` that only wraps `User::create()`;
- `ProductService` that only proxies Eloquent CRUD;
- a generic `BaseService`.

If a write workflow is one use case, use an **Action** instead of a Service.

## 6.4 Actions

**Location:** `app/Actions/{Module}/`

Default home for **write workflows** that touch multiple models, need a database transaction, or deserve isolated tests.

Preferred shape: one class, one public method (`__invoke` or `execute`).

| Action | Must do |
|---|---|
| `Actions\Sales\CompleteSale` | Reload products, recalculate, lock stock as required, create transaction + items, call Inventory deduction, commit atomically |
| `Actions\Inventory\ApplySaleStockDeduction` | Reduce `products.stock_quantity`, write `SALE` movements. Called **only** from `CompleteSale` inside the same DB transaction |
| `Actions\Inventory\AdjustStock` | Authorized manual correction, movement `MANUAL_ADJUSTMENT`, reason, then `RecordActivity` |
| `Actions\Configuration\UpdateStoreSettings` | Settings fields + logo replace with rollback of file on failure + audit |
| `Actions\Identity\CreateUser` | Create operational user |
| `Actions\Identity\ChangeUserRole` | Role change + audit; enforce protected Owner rules |
| `Actions\Identity\ChangeUserStatus` | Activate/deactivate + audit; protected Owner rules |
| `Actions\Audit\RecordActivity` | Insert `activity_logs` row. Called from other Actions, same transaction |
| `Actions\Catalog\CreateProduct` / `UpdateProduct` | Only if create/update is more than a validated Eloquent save (SKU/barcode rules, stock init). Otherwise Livewire/controller + model is enough |

Checkout must call Inventory through `ApplySaleStockDeduction` (or equivalent). Controllers and Livewire must not `Product::decrement()` directly.

Failed checkout must not leave a `COMPLETED` transaction, reduced stock, or orphan movements (`BUSINESS_FLOW.md` §10, `DATABASE.md` §21.1).

## 6.5 DTOs

**Default: do not add a DTO layer.**

Do not install `spatie/laravel-data` (or similar) for CRUD forms.

**One optional exception — checkout input.**

Livewire public properties are not authoritative. `CompleteSale` should accept an explicit, typed cart payload so the Action does not read the Livewire component.

If a plain array becomes error-prone, add a **readonly PHP class**, not a DTO framework:

```text
app/Support/Sales/CartItemData.php
app/Support/Sales/CheckoutPayload.php
```

These are input snapshots (product id, quantity). Prices and totals are recalculated inside `CompleteSale` / `CheckoutCalculator`.

Do not create DTOs for Product, User, or report rows.

## 6.6 Enums

**Location:** `app/Enums/`

Use backed PHP enums for stored string vocabularies. Do **not** enum boolean `active` flags.

| Enum | Values (MVP) |
|---|---|
| `UserRole` | `OWNER`, `ADMINISTRATOR`, `CASHIER` |
| `TransactionStatus` | `COMPLETED` only |
| `StockMovementType` | `SALE`, `MANUAL_ADJUSTMENT` |
| `ActivityAction` | `USER_CREATED`, `USER_ROLE_CHANGED`, `USER_STATUS_CHANGED`, `STOCK_MANUAL_ADJUSTED`, `STORE_SETTINGS_UPDATED` |

Cast them on the matching model columns.

Do not add `CANCELLED`, `VOID`, `REFUNDED`, or `PENDING` until the PRD defines those workflows.

Cart states (`EMPTY`, `OPEN`, `READY_FOR_PAYMENT`, …) are UI/workflow states. They are not database enums and need no persisted enum.

## 6.7 Policies

**Location:** `app/Policies/`

Laravel-standard, one policy per sensitive model (or a dedicated `ReportPolicy` if reports have no model).

| Policy | Protects |
|---|---|
| `UserPolicy` | User CRUD; Administrator cannot control protected Owner |
| `ProductPolicy` | Catalog maintenance vs cashier POS read |
| `CategoryPolicy` | Category maintenance |
| `TransactionPolicy` | History visibility; no edit/delete of completed sales |
| `StockMovementPolicy` or inventory ability on `ProductPolicy` | Manual adjustment vs movement read |
| `StoreSettingPolicy` | Settings and logo |
| `ActivityLogPolicy` | Audit read; no cashier edit |

Register via Laravel policy discovery or `AppServiceProvider`.

UI hiding of sidebar items is **not** authorization. Policies/Gates run on the server for every protected action (`CURSOR.md` §12, SRS §8).

Gates are acceptable for cross-cutting abilities (`viewReports`, `adjustStock`) if a full policy feels forced. Prefer Policies when the check is resource-shaped.

## 6.8 Form Requests

**Location:** `app/Http/Requests/{Module}/`

Use Form Requests for **controller** flows with non-trivial rules.

Examples if those screens stay controller-based:

- `Requests\Catalog\StoreProductRequest`
- `Requests\Catalog\UpdateProductRequest`
- `Requests\Identity\StoreUserRequest`
- `Requests\Inventory\AdjustStockRequest`
- `Requests\Configuration\UpdateStoreSettingRequest`

Livewire screens use `$this->validate()` / `rules()` on the component. Do not duplicate the same rule arrays in a Form Request **and** Livewire unless you extract a shared rules method.

Critical business rules (stock, payment sufficiency, active product) must still be enforced again inside the Action. Form Request / Livewire validation is the first gate, not the last.

## 6.9 Jobs

**MVP: none.**

Do not queue checkout, stock deduction, invoice creation, or receipt readiness (`CURSOR.md` §13, SRS §17).

Do not create `app/Jobs` until a real async need exists (email, large export, large import).

When a Job is added later, put it under `app/Jobs/{Module}/` and keep it off the financial commit path.

## 6.10 Events

**MVP: none required.**

Do not fire `SaleCompleted` and depend on listeners for stock or transaction rows. That hides the invariant:

```text
completed transaction  ⇔  items  ⇔  stock reduction  ⇔  SALE movements
```

If an event is added later, it may only trigger **secondary** work (analytics, email). Document whether it is synchronous. Failures must not corrupt sale state.

## 6.11 Listeners

**MVP: none.**

Audit for stock adjustment and user/role changes belongs **inside the Action’s database transaction** via `RecordActivity`, not in a listener that can fail independently.

## 6.12 Notifications

**MVP: Laravel `Notification` classes are not used.**

In-app feedback only (`PRD` / SRS §20):

- session flash after redirects;
- Livewire validation errors;
- toasts for success / business errors / low-stock warnings.

Implement toasts as Blade/Livewire UI (`resources/views/components/toast.blade.php` or equivalent), not `Illuminate\Notifications`.

Do not add mail, SMS, WhatsApp, or WebSocket notifications.

## 6.13 Repositories

**Do not add a repository layer.**

Eloquent on the model (and query scopes) is the persistence API (ADR-07, SRS §15).

If a report query becomes large, extract a **Query object**:

```text
app/Queries/Reporting/DailySalesQuery.php
app/Queries/Reporting/ProductSalesQuery.php
app/Queries/Reporting/LowStockQuery.php
```

A Query object returns aggregated data. It is not a generic `BaseRepository` and must not wrap `find`, `create`, or `update`.

Introduce a repository only if persistence must be swapped or a stable interface is proven necessary. That is not an MVP condition.

## 6.14 Traits

**Avoid by default.**

Do not add `HasStock`, `Auditable`, or observer-like traits that hide checkout or audit side effects.

Acceptable later, and only if duplication is real:

- a tiny date-range filter concern for report Livewire pages;
- nothing that writes stock or activity logs.

Prefer explicit Action calls over trait hooks.

## 6.15 Helpers

**Location:** `app/Support/` and Blade components — not a global `helpers.php` soup.

| Helper | Location |
|---|---|
| Invoice number generation | `Support\Sales\InvoiceNumberGenerator` |
| Money rounding/format used in PHP | `Support\Money` (if needed besides model decimal casts) |
| Money on screen | Blade component `<x-money>` |
| Store settings read | `StoreSetting` model or a single `Support\StoreContext` if many call sites appear |

Do not put business workflows in helpers. Do not use unbounded global functions.

## 6.16 Livewire Components

**Location:** `app/Livewire/{Module}/`  
**Views:** `resources/views/livewire/{module}/`

Livewire is the primary UI for authenticated operational screens (`SYSTEM_DESIGN.md` ADR-03, `CURSOR.md` §21).

### POS (highest care)

Keep the POS **page** as a composer of smaller components. Do not put checkout SQL in one giant class.

```text
Livewire/Pos/PosPage.php              # layout + wiring
Livewire/Pos/ProductSearch.php
Livewire/Pos/CartPanel.php
Livewire/Pos/PaymentPanel.php
```

`PosPage` (or `PaymentPanel`) calls `CompleteSale`. It does not implement stock math.

Rules:

- authorize on every mutating method;
- validate on the server;
- debounce search;
- prevent double submit on checkout;
- never treat public properties as source of truth for price or stock;
- eager-load what the grid needs.

### Other modules

| Component | Module |
|---|---|
| `Reporting/Dashboard` | Reporting |
| `Catalog/ProductIndex`, `ProductForm` | Catalog |
| `Catalog/CategoryIndex`, `CategoryForm` | Catalog |
| `Sales/TransactionIndex`, `TransactionShow` | Sales |
| `Inventory/StockIndex`, `AdjustStockForm`, `MovementIndex` | Inventory |
| `Reporting/SalesReport`, `ProductSalesReport` | Reporting |
| `Identity/UserIndex`, `UserForm` | Identity |
| `Configuration/StoreSettingsForm` | Configuration |
| `Audit/ActivityLogIndex` | Audit |
| `Identity/ProfileForm` | Identity |

List pages with search, filters, and pagination should be Livewire. Simple static Blade + controller is acceptable for login and printed receipt.

## 6.17 Views

**Location:** `resources/views/`

```text
resources/views/
├── layouts/
│   ├── app.blade.php                 # sidebar + top bar
│   ├── guest.blade.php               # login
│   └── receipt.blade.php             # print
├── components/                       # UI kit from docs/UI_UX.md
│   ├── sidebar.blade.php
│   ├── page-header.blade.php
│   ├── table.blade.php
│   ├── modal.blade.php
│   ├── toast.blade.php
│   ├── money.blade.php
│   ├── status-badge.blade.php
│   └── ...
├── livewire/
│   ├── pos/
│   ├── catalog/
│   ├── sales/
│   ├── inventory/
│   ├── reporting/
│   ├── identity/
│   ├── configuration/
│   └── audit/
├── auth/
│   └── login.blade.php
└── receipts/
    └── show.blade.php
```

Blade is presentation only:

- no checkout calculations;
- no `Product::all()` in templates;
- escape user content;
- reuse components instead of repeating long Tailwind strings (`CURSOR.md` §20, §23).

Alpine.js stays in Blade for dropdowns, modals, and drawers. It must not own cart totals or permissions.

## 6.18 Tests

**Location:** `tests/Feature/{Module}/` and `tests/Unit/{Module}/`

Use the test runner created at project init (Pest or PHPUnit). Name tests after **business behavior**.

Mandatory Feature coverage (`CURSOR.md` §36, SRS §33):

```text
tests/Feature/Auth/
  active user can log in
  inactive user is rejected
  logout invalidates session

tests/Feature/Identity/
  owner access
  administrator cannot control protected owner
  cashier cannot manage users

tests/Feature/Catalog/
  SKU uniqueness
  barcode uniqueness
  inactive product cannot be sold

tests/Feature/Sales/
  empty cart rejected
  valid cash sale
  insufficient payment rejected
  insufficient stock rejected
  exactly one completed transaction
  items + stock + movements created together
  rollback on failure
  duplicate submit protection
  product rename does not change snapshots
  product price change does not change historical reports

tests/Feature/Inventory/
  unauthorized adjustment rejected
  reason required
  movement + audit created

tests/Feature/Reporting/
  totals reconcile with completed transactions
```

Unit tests belong to pure calculation and small generators:

```text
tests/Unit/Sales/CheckoutCalculatorTest
tests/Unit/Sales/InvoiceNumberGeneratorTest
```

Livewire tests cover POS search, cart updates, and checkout invocation using Livewire’s test API.

Do not treat “works in the browser” as sufficient for checkout.

---

# 7. Placement Cheat Sheet

| Need | Put it in | Do not put it in |
|---|---|---|
| Table + relations | `Models/` | Livewire, controllers |
| One write use case (checkout, adjust stock) | `Actions/{Module}/` | Model observers, listeners |
| Pure money math | `Services/Sales/CheckoutCalculator` or `Support/` | Blade, Alpine |
| Heavy report SQL | `Queries/Reporting/` | Controllers, views |
| Role string | `Enums/UserRole` | Magic strings in Blade |
| “Can this user adjust stock?” | `Policies/` | Hidden buttons only |
| Controller form rules | `Http/Requests/` | Unvalidated `$request->all()` |
| POS interactivity | `Livewire/Pos/` | A repository + REST API |
| Flash / toast | Session + Blade/Livewire | `app/Notifications` |
| Email / SMS | Nowhere in MVP | Jobs pretending to be required |
| Generic `find()` wrapper | Nowhere | `Repositories/` |

---

# 8. Example Checkout Path

This is the shape every implementation should follow.

```text
Livewire\Pos\PaymentPanel
    authorize selling
    validate cart not empty, cash received present
    build CheckoutPayload (ids + quantities + cash + discount)
    call Actions\Sales\CompleteSale
        CheckoutCalculator (authoritative totals)
        DB::transaction
            lock/reload products
            validate active + stock
            create Transaction + TransactionItems (snapshots)
            Actions\Inventory\ApplySaleStockDeduction
            commit
    redirect / show receipt
```

If any required step fails, nothing is committed as a completed sale.

---

# 9. Routes

**File:** `routes/web.php` only for MVP.

No `routes/api.php` consumer exists (ADR-10).

Group by auth and name every route:

```text
guest:     login
auth:      dashboard, pos, transactions, products, categories,
           inventory, reports, users, settings, activity-log, profile
```

Use middleware for `auth`. Use Policies/Gates for role. Do not rely on “cashier never sees the URL.”

Split into `routes/web/*.php` only if `web.php` becomes hard to scan. That is optional later, not an MVP requirement.

---

# 10. Database and Frontend Layout

Unchanged from Laravel defaults:

```text
database/migrations/     one purpose per migration
database/factories/      User, Product, Category, Transaction, …
database/seeders/        Owner, demo catalog if needed

resources/css/app.css    Tailwind
resources/js/app.js      Alpine + Livewire
```

Do not add Redis, queue workers, or a separate `frontend/` SPA app.

---

# 11. Growth Rules

Add structure when a **pain** appears, not in anticipation.

| Signal | Next step |
|---|---|
| `web.php` is long | `routes/web/*.php` includes |
| Report methods dominate a service | more files under `Queries/Reporting/` |
| Second delivery mechanism for checkout (e.g. future API) | keep `CompleteSale`; add a new thin controller |
| Email receipts approved | `app/Notifications` + Job; checkout still synchronous |
| Module exceeds ~15 Livewire classes | keep the same namespace; do not switch to `nwidart` without a decision record |

Never add a second architecture style beside this one (`CURSOR.md` §46).

---

# 12. Empty Folder Rule

Do **not** commit empty `Jobs/`, `Events/`, `Listeners/`, `Notifications/`, `Repositories/`, or `DTO/` directories.

The absence of those folders is the design: SimplePOS does not need them yet.

---

# 13. Naming

Follow `CURSOR.md` §7.

```text
CompleteSale
AdjustStock
ProductPolicy
StoreProductRequest
UserRole
DailySalesQuery
```

Module folder names use the logical domain (`Sales`, `Inventory`), not the table name (`Transactions` is a Livewire/page grouping under Sales).

---

# 14. Alignment With Existing Docs

| Topic | Source |
|---|---|
| Modular monolith, no microservices | `SYSTEM_DESIGN.md` §1, ADR-01 |
| Layering: Livewire → Action → Eloquent | `SYSTEM_DESIGN.md` §3, `CURSOR.md` §4 |
| No repository by default | `SYSTEM_DESIGN.md` ADR-07, SRS §15 |
| Actions/Services only for real workflows | SRS §14, `CURSOR.md` §10 |
| Synchronous checkout | ADR-05, `BUSINESS_FLOW.md` §10 |
| Eight models | `DATABASE.md` §2 |
| Livewire for POS and filters | `CURSOR.md` §21, `UI_UX.md` |
| Tests for checkout integrity | `CURSOR.md` §36, SRS §33 |
| Initial folder sketch | `SYSTEM_DESIGN.md` “Recommended Initial Project Structure” |

This document expands that sketch into placement rules for every application type. If implementation needs to diverge, update this file in the same change and do not silently invent a parallel tree.

---

# 15. Verified stack (TASK-001)

Recorded from this machine and generated lockfiles after initializing the Laravel app in this repository:

| Item | Verified value |
|---|---|
| PHP (CLI) | 8.3.26 (target docs mentioned 8.4.x; framework constraint is `^8.3`) |
| Composer | 2.8.12 |
| Laravel framework | v13.29.0 (`composer.lock`) |
| Laravel skeleton | laravel/laravel 13.x (`php: ^8.3`) |
| PHPUnit | 12.5.34 |
| Livewire | v4.4.2 |
| Tailwind CSS | ^4.0.0 (`package.json`) |
| Vite | ^8.0.0 (`package.json`) |
| Node | v22.20.0 |
| npm | 10.9.3 |
| MySQL (CLI) | 8.0.30 (target docs mentioned 8.4 LTS) |
| Session | `file` (`.env.example`) |
| Queue | `sync` |
| Cache | `file` |
| Alpine.js | Bundled with Livewire 4 (not a separate npm package) |

Do not treat PHP 8.4 or MySQL 8.4 as runtime facts on this workstation.
