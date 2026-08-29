# CURSOR.md

## SimplePOS — Sistem Kasir & Penjualan Sederhana

**Purpose:** Permanent engineering instructions for AI coding assistants working in this repository.

**Primary Product Documentation:**
- `docs/PRD.md`
- `docs/SRS.md`
- `docs/SYSTEM_DESIGN.md`
- `docs/BUSINESS_FLOW.md`
- `docs/DATABASE.md`
- `docs/UI_UX.md`

> These documents define the approved product behavior, architecture, database design, workflows, and UI/UX direction. AI assistants must treat them as authoritative unless the user explicitly changes the requirements.

---

# 1. Core AI Operating Rules

Before changing any file, the AI must:

1. Inspect the existing project structure.
2. Read the relevant existing code before editing.
3. Read the relevant project documentation.
4. Identify the existing architectural pattern.
5. Identify framework/runtime/package versions from actual project files whenever possible.
6. Reuse existing conventions, helpers, services, policies, components, and patterns before introducing new ones.
7. Check whether the requested behavior already exists.
8. Determine the smallest safe change that satisfies the requirement.
9. Avoid changing unrelated files.
10. Preserve backward compatibility whenever possible.

The AI must not assume that a blank-slate implementation is appropriate when existing code is present.

The AI must not silently replace the current project architecture with a preferred personal pattern.

---

# 2. Environment Verification Rules

Target stack direction:

```text
Laravel 13.x
PHP 8.4.x
MySQL 8.4 LTS
Blade
Livewire
Alpine.js
Tailwind CSS
```

Actual versions must be determined from the project environment.

Before making version-specific decisions, inspect:

```text
composer.json
composer.lock
package.json
package-lock.json / pnpm-lock.yaml / yarn.lock
bootstrap/app.php
config/*
.env.example
```

When available, verify using runtime commands such as:

```text
php -v
composer show laravel/framework
mysql --version
node --version
npm --version
```

If the environment cannot be inspected, do not invent version-specific behavior.

Use:

```text
TBD — Requires Environment Verification
```

where necessary.

---

# 3. Source of Truth

Order of authority:

```text
1. Explicit current user instruction
2. docs/PRD.md
3. docs/SRS.md
4. docs/SYSTEM_DESIGN.md
5. docs/BUSINESS_FLOW.md
6. docs/DATABASE.md
7. docs/UI_UX.md
8. Existing implementation conventions
```

If two documents conflict:

- do not silently choose;
- identify the conflict;
- preserve the higher-priority requirement;
- update documentation if the architecture/business rule is intentionally changed.

Never silently change:

- business rules;
- permissions;
- transaction status behavior;
- inventory behavior;
- reporting logic;
- database retention behavior.

---

# 4. Architecture Rules

SimplePOS uses a **Laravel modular monolith**.

Do not introduce microservices unless there is an explicit, documented technical requirement.

Do not introduce:

- Kafka;
- RabbitMQ;
- Kubernetes;
- service mesh;
- CQRS infrastructure;
- event sourcing;
- distributed databases;
- external search engines;
- Redis;
- separate frontend SPA;
- GraphQL;
- public API infrastructure

unless the current approved requirement genuinely needs them.

Preferred architecture:

```text
Blade / Livewire / Alpine.js
        |
Controllers / Livewire Components
        |
Authorization + Validation
        |
Application Services / Actions
        |
Eloquent
        |
MySQL
```

Keep business boundaries clear:

- Identity
- Catalog
- Sales
- Inventory
- Reporting
- Configuration
- Audit

---

# 5. Laravel Conventions

Always follow Laravel conventions unless the existing project clearly uses another established internal convention.

Prefer:

- Eloquent models;
- route model binding;
- Form Requests;
- Policies/Gates;
- service container;
- framework validation;
- framework filesystem;
- framework logging;
- framework queues/scheduler only when justified;
- named routes where appropriate;
- resourceful controllers where appropriate.

Do not create custom framework-like abstractions when Laravel already solves the requirement.

---

# 6. PHP Standards

Use modern PHP consistent with the verified runtime.

General rules:

1. Use strict, readable type declarations where compatible with the project.
2. Use return types where practical.
3. Use constructor property promotion when it improves clarity and matches the existing codebase.
4. Prefer immutable/local data flow for calculations.
5. Avoid global state.
6. Avoid magic behavior unless Laravel convention requires it.
7. Keep methods small and purposeful.
8. Avoid deeply nested conditional logic.
9. Use early returns where they improve readability.
10. Do not suppress errors.

Follow the project's formatter/linter configuration if present.

If none exists, follow standard Laravel/PHP style conventions.

---

# 7. Naming Conventions

Use clear domain-driven names.

## Classes

Use PascalCase.

Examples:

```text
CheckoutService
AdjustStockAction
ProductPolicy
StoreSetting
TransactionItem
```

## Methods / Variables

Use camelCase.

Examples:

```text
calculateTotal()
cashReceived
invoiceNumber
```

## Database

Use snake_case.

Examples:

```text
transaction_items
cash_received
completed_at
stock_quantity
```

## Boolean Names

Prefer positive semantics:

```text
active
isActive()
canManageUsers()
```

Avoid ambiguous names such as:

```text
flag
status2
data1
tmp
```

---

# 8. Models

Eloquent models should represent domain data and relationships.

Models may contain:

- relationships;
- casts;
- scopes;
- accessors/mutators when justified;
- small domain helpers;
- concise computed behavior.

Do not place large multi-step workflows directly inside models.

Do not hide critical checkout/inventory workflows inside model observers or boot hooks.

Models must explicitly control mass assignment according to project convention.

Sensitive fields must never be serialised/exposed unnecessarily.

Avoid adding traits with unclear side effects.

---

# 9. Controllers

Controllers must remain thin.

Controllers should:

1. receive the request;
2. authorize;
3. invoke validation;
4. call application service/action;
5. return response/view/redirect.

Controllers must not contain:

- full checkout logic;
- stock mutation rules;
- report aggregation logic;
- large calculations;
- multi-model orchestration;
- reusable business logic.

Preferred flow:

```text
Controller:
validate
authorize
call CheckoutService
return result
```

---

# 10. Services / Actions

Use services/actions for business workflows that:

- touch multiple models;
- contain business rules;
- require a database transaction;
- are reusable;
- are complex enough to deserve isolated tests.

Expected examples:

```text
CheckoutService / CompleteSaleAction
AdjustStockAction
UpdateStoreSettingsAction
```

Do not create service classes for trivial single-model CRUD solely for pattern consistency.

Avoid generic services that merely proxy Eloquent methods.

---

# 11. Form Requests

Use Form Requests for non-trivial validation in controller-based flows.

Use Form Requests when:

- multiple fields are validated;
- validation rules are reused;
- authorization can be expressed at request level;
- validation has custom messages;
- rules are complex enough to clutter the controller.

Do not duplicate identical validation rules across multiple controllers.

Livewire components may use Livewire validation where appropriate, but critical business rules must still be revalidated in the server-side business workflow.

---

# 12. Policies and Gates

Use Policies/Gates for authorization.

Never rely on:

- hidden buttons;
- disabled UI;
- sidebar visibility;
- client-side role checks

as the only authorization mechanism.

Authorization must be enforced server-side.

Key role rules:

```text
OWNER
ADMINISTRATOR
CASHIER
```

Cashier must not receive unrestricted access to:

- user administration;
- stock adjustment;
- store settings;
- unrestricted reports;
- audit logs.

Administrator must not control the protected Owner account under default MVP rules.

---

# 13. Jobs

Jobs are only for genuinely asynchronous work.

Do not queue critical sale operations.

The following must remain synchronous for MVP:

- checkout;
- transaction persistence;
- stock deduction;
- stock movement creation;
- receipt readiness.

Potential future queue candidates:

- email;
- large export;
- large import;
- external notification.

Do not create jobs just to make architecture look more complex.

---

# 14. Events

Use events only when they reduce coupling and the behavior is genuinely event-like.

Do not use events to hide critical business logic.

Critical sale correctness must not depend on an unreliable event listener chain.

If an event is introduced:

- document why;
- define synchronous/asynchronous behavior;
- ensure failures cannot corrupt core business state.

---

# 15. Listeners

Listeners should handle secondary concerns.

Examples that may become valid later:

- non-critical email;
- external analytics;
- non-critical notifications.

Do not move essential stock deduction or transaction creation into listeners.

---

# 16. Notifications

MVP notifications are in-app feedback only.

Do not add:

- email notification;
- SMS;
- WhatsApp;
- WebSocket push

unless a new requirement explicitly requests them.

Use concise feedback for:

- success;
- validation;
- business error;
- low-stock warning.

---

# 17. Database Migrations

Never modify an existing production migration after it has been deployed.

For schema changes:

```text
Create a new migration.
```

Migration rules:

1. one clear schema purpose per migration;
2. safe up/down behavior where practical;
3. preserve existing data;
4. avoid destructive changes without explicit approval;
5. add indexes intentionally;
6. add foreign keys intentionally;
7. use exact decimal types for money;
8. never place business data transformations in migrations without clear justification.

Do not edit old migrations merely to make a fresh local database look cleaner if production history exists.

---

# 18. Database Transactions

Use database transactions for multi-step critical operations.

Mandatory examples:

## Checkout

```text
create transaction
create transaction items
update stock
create stock movements
```

must complete atomically.

## Manual Stock Adjustment

```text
update stock
create stock movement
create required audit event
```

must remain consistent.

## User Role / Status Change

Recommended:

```text
update user
create audit log
```

Do not use a database transaction for every trivial read/write operation.

---

# 19. Concurrency

Critical stock and invoice behavior must be concurrency-safe.

Do not assume that UI-level stock checks are sufficient.

Final checkout must re-read/revalidate authoritative state.

Use database-level protections where required.

Unique constraints remain mandatory even when application code attempts to prevent duplicates.

---

# 20. Blade

Blade views should focus on presentation.

Rules:

1. escape user-generated output by default;
2. avoid raw HTML rendering;
3. keep business logic out of templates;
4. extract repeated UI into components;
5. use semantic HTML;
6. maintain accessible labels/headings;
7. do not issue complex database queries from views.

Do not call Eloquent directly from Blade for page-level data retrieval.

---

# 21. Livewire Components

Livewire is appropriate for:

- POS interactions;
- search;
- filters;
- pagination;
- reactive forms;
- payment panel;
- small management interactions.

Rules:

1. keep components focused;
2. do not allow giant page components to own all business logic;
3. delegate critical workflows to services/actions;
4. validate server-side;
5. authorize server-side;
6. prevent duplicate submissions;
7. avoid unnecessary network roundtrips;
8. debounce search responsibly;
9. reset pagination when filters change where necessary;
10. eager load required relationships.

Never trust Livewire public properties as authoritative financial/business state at checkout.

---

# 22. Alpine.js

Use Alpine.js only for lightweight client-side UI behavior.

Appropriate uses:

- dropdown;
- modal open/close;
- tabs;
- drawer;
- small local UI state.

Do not duplicate server business state in Alpine.

Do not implement checkout authority, stock authority, permissions, or financial calculations solely in Alpine.

---

# 23. Tailwind CSS

Use Tailwind consistently with the established design system.

Follow `docs/UI_UX.md`.

Avoid:

- arbitrary one-off styles everywhere;
- excessive gradients;
- glassmorphism;
- inconsistent spacing/radius;
- unnecessary custom CSS.

Prefer reusable Blade/Livewire UI components over repeated long class strings when repetition becomes substantial.

---

# 24. Routes

Keep routes clear and predictable.

Rules:

1. use route groups for auth/role/module organization where appropriate;
2. use named routes;
3. use route model binding where useful;
4. do not expose internal implementation details in URLs;
5. enforce authorization beyond route visibility.

Do not create duplicate routes that perform the same business action with inconsistent validation.

---

# 25. API

The MVP does not require a public API.

Do not build a REST API merely because modern applications often have one.

Only introduce API infrastructure when an explicit consumer exists.

If an API is added later, create separate API documentation and authentication/authorization rules.

---

# 26. Validation

Never trust client-side validation.

Validation layers:

```text
UI convenience validation
        +
Server request validation
        +
Business rule validation
        +
Database constraints
```

Examples:

- SKU uniqueness;
- barcode uniqueness;
- active product;
- stock availability;
- discount validity;
- payment sufficiency;
- role authority;
- file type/size.

Do not duplicate business rules inconsistently between layers.

---

# 27. Authorization

Authorization must be checked:

- before state-changing actions;
- before sensitive reads;
- before management pages;
- before record-level access.

Prevent IDOR-style access.

Do not assume that knowing a record ID grants access.

---

# 28. Security

Mandatory security rules:

1. Never commit credentials.
2. Never expose `.env`.
3. Never log passwords, tokens, session secrets, or API credentials.
4. Escape user-generated output.
5. Validate file uploads.
6. Restrict file types and sizes.
7. Use framework CSRF protection.
8. Use parameterized queries/Eloquent.
9. Avoid unsafe raw SQL.
10. Use secure password hashing.
11. Disable debug output in production.
12. Protect authentication endpoints with appropriate throttling.
13. Never trust hidden form fields for authorization.
14. Never expose internal stack traces to end users.
15. Never hard-code production secrets.

---

# 29. File Uploads

Current MVP upload requirement:

```text
Store Logo
```

Rules:

1. validate MIME/type;
2. validate size;
3. use Laravel filesystem abstraction;
4. store only file reference/path in database;
5. do not store raw image binary in DB;
6. failed replacement must preserve current valid logo;
7. do not trust original filename;
8. prevent path traversal;
9. ensure intended public/private visibility.

Product image upload must not be added unless approved.

---

# 30. Query Performance

Prevent N+1 queries.

Mandatory practices:

- eager load relationships when rendering lists/details;
- use `with()` / equivalent existing project convention;
- use aggregate queries for reporting;
- paginate large tables;
- select only required columns when query volume matters;
- index common lookup fields.

Do not blindly eager load every relationship.

Use eager loading intentionally.

---

# 31. Pagination

Use pagination for potentially large datasets:

- products;
- transactions;
- stock movements;
- audit logs;
- users;
- reports with detailed rows.

Do not load thousands of rows into memory for ordinary list screens.

---

# 32. Reporting

Reporting must use historical completed transaction data.

Rules:

1. do not recalculate old sales from current product price;
2. do not include failed checkout;
3. use transaction item snapshots;
4. date filters must be consistent;
5. dashboard and report totals must reconcile;
6. use database aggregation where practical;
7. avoid loading all rows into PHP merely to sum them.

---

# 33. Logging

Use Laravel-native logging.

Log:

- unexpected errors;
- failed critical operations;
- database/storage failures;
- queue/scheduler failures if introduced.

Do not log secrets.

Use structured/contextual information where useful:

```text
transaction_id
invoice_number
user_id
product_id
```

without exposing sensitive data.

---

# 34. Activity Logging

Business audit logging is separate from application diagnostic logs.

Required audit areas:

- manual stock adjustment;
- user role changes;
- user active status changes;
- sensitive store setting changes.

Do not silently delete audit records.

Do not allow Cashier to edit audit data.

---

# 35. Error Handling

Use controlled error handling.

Rules:

1. validation errors -> actionable field/business messages;
2. authorization errors -> controlled forbidden state;
3. missing records -> controlled not-found state;
4. unexpected errors -> safe generic user message + diagnostic log;
5. failed checkout -> never show success;
6. transactional failure -> rollback;
7. preserve user input where practical.

Do not catch exceptions merely to suppress them.

Do not use broad empty `catch` blocks.

---

# 36. Testing

Important business logic must have automated tests.

Mandatory test areas:

## Authentication

- active user login;
- inactive user rejection;
- logout.

## Authorization

- Owner access;
- Administrator restrictions;
- Cashier restrictions;
- protected Owner rules.

## Products

- SKU uniqueness;
- barcode uniqueness;
- inactive product cannot be sold.

## Checkout

- empty cart rejected;
- valid cash sale;
- insufficient payment rejected;
- insufficient stock rejected;
- exactly one completed transaction;
- transaction items created;
- stock reduced;
- stock movements created;
- rollback on failure;
- duplicate submission protection where applicable.

## Historical Integrity

- product rename does not change completed transaction snapshots;
- product price change does not change historical reports.

## Inventory

- manual adjustment requires authorization;
- reason required;
- movement created;
- audit created.

## Reports

- totals reconcile with completed transactions.

Write tests before or with high-risk business logic.

Do not accept "works manually" as sufficient for critical checkout behavior.

---

# 37. Test Style

Prefer clear Arrange-Act-Assert structure.

Tests should describe business behavior, not framework internals.

Good names:

```text
cashier_cannot_checkout_when_stock_is_insufficient
completed_transaction_preserves_sale_time_product_price
administrator_cannot_deactivate_protected_owner
```

Avoid brittle tests coupled to irrelevant implementation details.

---

# 38. Documentation

Update documentation when:

- architecture changes;
- business flow changes;
- database schema changes;
- roles/permissions change;
- new integration is introduced;
- new status is introduced;
- new module is added;
- deployment requirement changes.

Relevant documents:

```text
docs/PRD.md
docs/SRS.md
docs/SYSTEM_DESIGN.md
docs/BUSINESS_FLOW.md
docs/DATABASE.md
docs/UI_UX.md
CURSOR.md
```

Do not let code behavior silently drift away from these documents.

---

# 39. Git Discipline

Rules:

1. make focused commits;
2. do not mix unrelated refactors with feature work;
3. do not commit credentials;
4. do not commit generated environment secrets;
5. do not commit debug dumps;
6. do not reformat the entire codebase for a small change;
7. preserve readable history;
8. use descriptive commit messages;
9. do not modify unrelated files without reason.

Before completing a feature, review the diff for accidental unrelated changes.

---

# 40. Package Management

Never introduce a package without justification.

Before adding a package, answer:

```text
What requirement does this package solve?
Can Laravel already solve it?
Does the project already have an equivalent dependency?
What maintenance/security burden does it add?
Is it actively maintained and compatible with the verified environment?
```

Prefer Laravel-native functionality.

Do not add packages merely to reduce a few lines of straightforward code.

---

# 41. Refactoring Discipline

Never perform a large unrelated refactor while implementing a small feature.

Allowed:

- minimal local refactor required for correctness;
- extracting duplicated logic touched by the current feature;
- fixing clear technical debt that blocks the requested feature.

Not allowed without explicit scope:

- renaming entire modules;
- moving many files;
- replacing architectural patterns;
- rewriting all controllers/services;
- swapping major packages/framework approaches.

Small request -> small focused change.

---

# 42. Backward Compatibility

Maintain backward compatibility whenever possible.

Before changing:

- database schema;
- route names;
- public component interfaces;
- stored values;
- role identifiers;
- transaction statuses;
- configuration keys;

check existing usage.

Do not rename or reinterpret persisted business values casually.

---

# 43. Business Rule Protection

Do not silently change approved business rules.

Examples:

- Cash payment is MVP payment method.
- Completed transactions are not normally editable/deletable.
- Inactive products cannot be sold.
- Inactive users cannot authenticate.
- Manual stock adjustment requires a reason.
- Checkout updates stock atomically.
- Administrator cannot control protected Owner.
- Historical transaction values are snapshots.
- Reports use completed transactions.

If implementation reveals a business-rule ambiguity:

1. identify it;
2. preserve current documented behavior;
3. do not invent a new rule silently;
4. update documentation only after the user approves the change.

---

# 44. AI File-Change Protocol

For every coding request, the AI should internally follow this sequence:

```text
1. Understand requested outcome
2. Read relevant docs
3. Inspect project tree
4. Inspect affected files
5. Inspect neighboring similar implementation
6. Verify versions/dependencies
7. Identify business rules
8. Identify authorization requirements
9. Identify database impacts
10. Identify tests
11. Make smallest safe change
12. Run relevant tests/checks
13. Review diff
14. Update documentation if required
```

Do not start by generating files blindly.

---

# 45. Existing-Code Analysis Rules for Cursor

Before creating a new:

- Model
- Controller
- Service
- Action
- Form Request
- Policy
- Livewire component
- Blade component
- migration
- job
- event
- listener
- notification

Cursor must search for an existing equivalent or related implementation.

Questions to answer first:

```text
Is there already a class for this responsibility?
How are similar modules implemented?
What naming convention already exists?
What validation pattern already exists?
What authorization pattern already exists?
What UI component can be reused?
What tests already cover adjacent behavior?
```

Reuse before reinventing.

---

# 46. No Silent Architecture Drift

Cursor must not introduce a second architecture style beside the existing one without explicit approval.

Examples of prohibited drift:

```text
existing code uses Actions
new feature introduces arbitrary Repository + Manager + Handler layers
```

or:

```text
existing UI uses Livewire
new feature introduces a standalone React SPA
```

or:

```text
existing authorization uses Policies
new feature hardcodes role checks in Blade
```

Follow the established pattern.

---

# 47. No Silent Schema Drift

Before changing database structure:

1. read `docs/DATABASE.md`;
2. inspect existing migrations;
3. inspect models/relationships;
4. inspect production-compatible constraints;
5. determine whether a new migration is required.

Never change persisted schema semantics silently.

---

# 48. Checkout-Specific Rules

Checkout is release-critical.

The AI must never implement checkout in a way that can create:

```text
completed transaction
without required transaction items
```

or:

```text
completed transaction
without required stock reduction
```

or:

```text
stock reduction
without completed transaction
```

Use one atomic database transaction for required persistence.

Server must recalculate authoritative totals.

Do not trust:

- browser total;
- Livewire property total;
- Alpine.js total;
- hidden input price;
- hidden input stock.

---

# 49. Inventory-Specific Rules

Current stock belongs to the product master.

Every business stock change must be traceable.

For sale:

```text
movement_type = SALE
```

For manual adjustment:

```text
movement_type = MANUAL_ADJUSTMENT
reason required
actor required
```

Do not update stock directly from arbitrary controllers without a traceable business workflow.

---

# 50. Transaction History Rules

Completed transaction history is protected.

Do not implement:

- ordinary edit;
- hard delete;
- cancellation;
- refund;
- void

unless a new approved product requirement explicitly defines those workflows.

Do not modify completed item snapshots when product master data changes.

---

# 51. UI/UX Rules

Follow `docs/UI_UX.md`.

Design goals:

```text
Professional
Operational
Fast
Predictable
Accessible
Commercial
```

Avoid:

- AI-generated dashboard clutter;
- excessive gradients;
- glassmorphism;
- excessive cards;
- oversized hero sections;
- decorative charts with no business purpose;
- excessive modal usage.

Cashier workflow has priority over decorative polish.

---

# 52. Accessibility

Do not remove:

- visible labels;
- focus states;
- semantic headings;
- accessible names;
- keyboard access.

Do not rely on color alone for status.

Dialogs must remain keyboard accessible.

---

# 53. Performance

Before optimizing, measure.

Mandatory baseline practices:

- eager loading where needed;
- avoid N+1;
- pagination;
- database indexes;
- database aggregation;
- avoid unnecessary repeated queries.

Do not add Redis/cache infrastructure without demonstrated need.

Do not cache authoritative checkout state.

---

# 54. Security Review Checklist

Before finishing security-sensitive work, verify:

```text
[ ] Authentication required where appropriate
[ ] Authorization enforced server-side
[ ] Validation is server-side
[ ] No mass-assignment exposure
[ ] No IDOR
[ ] No raw unsafe SQL
[ ] No secret exposed
[ ] User output escaped
[ ] Uploads validated
[ ] CSRF protection preserved
[ ] Sensitive errors hidden
[ ] Tests cover permission failure
```

---

# 55. Definition of Safe AI Completion

A coding task is not complete merely because code was written.

Before considering work complete, verify:

```text
[ ] Requirement matches PRD
[ ] Existing architecture followed
[ ] Versions/dependencies verified
[ ] Business rules preserved
[ ] Authorization implemented
[ ] Validation implemented
[ ] Critical DB transaction used if needed
[ ] N+1 checked
[ ] Tests added/updated
[ ] Relevant tests pass
[ ] No unrelated refactor
[ ] No credentials added
[ ] Documentation updated if architecture changed
[ ] Diff reviewed
```

---

# 56. Explicit Prohibitions

AI assistants must not:

1. invent unsupported product features;
2. create microservices for MVP;
3. create a public API without requirement;
4. add multi-tenancy preemptively;
5. add payment gateway behavior without approval;
6. implement refunds/voids without defined business rules;
7. add packages without justification;
8. place large business logic in controllers;
9. trust client-side validation;
10. bypass Policies/Gates;
11. modify deployed production migrations;
12. expose secrets;
13. commit credentials;
14. remove audit/history silently;
15. hard-delete completed transactions;
16. recalculate historical sales from current product prices;
17. perform unrelated large refactors;
18. change role semantics silently;
19. change transaction status semantics silently;
20. rewrite architecture because another pattern is personally preferred.

---

# 57. Final Engineering Principle

When there is a choice between a clever solution and a clear Laravel-native solution, choose the clear Laravel-native solution unless there is a measurable reason not to.

The guiding rule for SimplePOS is:

> **Preserve business correctness, transaction integrity, and maintainability before adding abstraction or complexity.**

And for AI-assisted development:

> **Inspect first, understand second, change third. Never code blindly.**
