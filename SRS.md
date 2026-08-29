# Software Requirements Specification (SRS)

## SimplePOS --- Sistem Kasir & Penjualan Sederhana

**Document Version:** 1.0\
**Status:** Initial Technical Specification\
**Authoritative Product Source:** `docs/PRD.md`\
**Intended Deployment:** Single-store web application on VPS

> This SRS translates the approved product behavior in `docs/PRD.md`
> into technical system requirements. Where this document conflicts with
> the PRD on product scope or business behavior, the PRD takes
> precedence.

------------------------------------------------------------------------

## Technology Environment Verification

The available project workspace was inspected before defining the stack.
At the time this SRS was produced, the available project contains
`docs/PRD.md` but does **not** contain an inspectable Laravel
application environment such as `composer.json`, `composer.lock`,
`package.json`, lock files, `.env.example`, framework bootstrap files,
or database server metadata.

Therefore, actual installed versions cannot be verified.

  -------------------------------------------------------------------------------
  Component         Requested Target  Verified Existing SRS Status
                                      Version           
  ----------------- ----------------- ----------------- -------------------------
  Laravel           Laravel 13.x      TBD --- Requires  Target architecture;
                                      Environment       verify after project
                                      Verification      initialization

  PHP               PHP 8.4.x         TBD --- Requires  Target runtime; verify
                                      Environment       before
                                      Verification      installation/deployment

  Database          MySQL 8.4 LTS     TBD --- Requires  Target database; verify
                                      Environment       on development/VPS
                                      Verification      environment

  Server-rendered   Blade             TBD --- Requires  Required frontend
  UI                                  Environment       approach
                                      Verification      

  Reactive UI       Livewire          TBD --- Requires  Required where reactive
                                      Environment       server-driven UI adds
                                      Verification      value

  Lightweight       Alpine.js         TBD --- Requires  Required only for
  browser                             Environment       suitable client-side
  interaction                         Verification      interactions

  CSS               Tailwind CSS      TBD --- Requires  Required styling approach
                                      Environment       
                                      Verification      

  Web Server        Not specified     TBD --- Requires  Select during VPS
                                      Environment       provisioning
                                      Verification      

  Node.js / npm     Not specified     TBD --- Requires  Determine from generated
                                      Environment       frontend toolchain
                                      Verification      

  Composer          Not specified     TBD --- Requires  Determine during
                                      Environment       environment setup
                                      Verification      
  -------------------------------------------------------------------------------

### Stack Decision Rule

1.  Do not hard-code framework/package versions into implementation
    plans until the initialized project is inspected.
2.  After initialization, `composer.json`, `composer.lock`,
    `package.json`, package lock files, runtime output, and database
    server version become the authoritative technical-version sources.
3.  The requested Laravel + MySQL + Blade + Livewire + Alpine.js +
    Tailwind CSS direction remains the target unless compatibility
    verification requires an approved change.
4.  Use stable, officially supported, mutually compatible releases when
    creating the new environment.
5.  Avoid adding third-party packages when Laravel-native functionality
    adequately satisfies the requirement.

------------------------------------------------------------------------

# 1. System Overview

SimplePOS is a server-rendered web-based Point of Sale system for a
single retail business instance.

The system supports:

-   authentication;
-   Owner, Administrator, and Cashier roles;
-   dashboard indicators;
-   category and product management;
-   POS/cart operations;
-   cash payment;
-   transaction-level discount;
-   checkout;
-   transaction history;
-   basic inventory;
-   stock adjustment and stock movement history;
-   sales reporting;
-   printable receipts;
-   user management;
-   store configuration;
-   basic activity/audit logging.

The MVP is a **modular monolith**. It must not introduce microservices,
distributed event systems, multi-tenancy, or other infrastructure that
is not required by the PRD.

------------------------------------------------------------------------

# 2. System Context

## 2.1 Actors

### Owner

Highest normal business role. Controls business settings, users,
reports, inventory, products, and transactions according to the PRD.

### Administrator

Maintains operational data and performs permitted administrative
activities without unrestricted control over the protected Owner
account.

### Cashier

Processes sales and accesses only cashier-appropriate transaction
information.

## 2.2 External Context

MVP context:

``` text
Owner / Administrator / Cashier
              |
              v
        Web Browser
              |
           HTTPS
              |
              v
      SimplePOS Application
        |             |
        v             v
      MySQL       File Storage
                      |
                      v
                  Store Logo
```

No external payment gateway, marketplace, accounting platform, SMS
service, WhatsApp service, SaaS tenant platform, or external POS
hardware integration is required for MVP.

## 2.3 Trust Boundaries

1.  Browser input is untrusted until validated and authorized.
2.  Authenticated identity does not imply authorization for every
    resource.
3.  Uploaded files are untrusted until validated.
4.  Database records containing completed transactions and audit history
    are protected business records.
5.  VPS and production secrets are privileged operational resources.

------------------------------------------------------------------------

# 3. Technical Objectives

**TO-01 --- Correctness**\
A successful checkout must persist the sale and required inventory
effects consistently.

**TO-02 --- Simplicity**\
Use a Laravel-native modular monolith and avoid infrastructure that does
not directly support an approved requirement.

**TO-03 --- Traceability**\
Sensitive operations defined by the PRD must identify responsible users
and timestamps.

**TO-04 --- Security**\
Authentication, authorization, validation, secure session behavior, and
protected file handling must be enforced server-side.

**TO-05 --- Maintainability**\
Business rules must have clear ownership and must not be duplicated
unpredictably across controllers/components/views.

**TO-06 --- Commercial Reusability**\
Store-specific values must be configuration/data rather than hard-coded
customer-specific behavior.

**TO-07 --- Testability**\
Critical transaction, inventory, reporting, permission, and
historical-integrity behavior must be automatically testable.

**TO-08 --- Deployability**\
The application must be deployable to a conventional VPS without
requiring distributed infrastructure.

------------------------------------------------------------------------

# 4. Functional Requirements

The PRD functional requirements remain authoritative. This section
defines system-level realization requirements.

## 4.1 Authentication

**SFR-AUTH-01** The application shall authenticate active operational
users before granting protected access.\
**SFR-AUTH-02** Failed authentication shall not create an authenticated
session.\
**SFR-AUTH-03** Inactive accounts shall be rejected.\
**SFR-AUTH-04** Logout shall invalidate the current authenticated
session according to the session policy.

## 4.2 Categories

**SFR-CAT-01** Authorized users shall create, read, update, search,
activate, and deactivate categories.\
**SFR-CAT-02** Category validation shall prevent invalid state
transitions defined by the PRD.

## 4.3 Products

**SFR-PROD-01** Authorized users shall manage product master data.\
**SFR-PROD-02** SKU uniqueness shall be enforced persistently.\
**SFR-PROD-03** Populated barcode uniqueness shall be enforced
persistently.\
**SFR-PROD-04** Inactive products shall not be accepted into new POS
sales.\
**SFR-PROD-05** Historical transaction item snapshots shall remain
unchanged when product master data changes.

## 4.4 POS

**SFR-POS-01** The POS shall maintain a working cart for the active
cashier workflow.\
**SFR-POS-02** Cart operations shall support add, quantity change,
removal, subtotal calculation, discount calculation, amount due, cash
received, and change.\
**SFR-POS-03** Checkout shall reject empty carts.\
**SFR-POS-04** Checkout shall revalidate sale-critical data on the
server immediately before completion.\
**SFR-POS-05** Client-side state shall never be the sole authority for
price, total, stock, permission, or transaction completion.

## 4.5 Transactions

**SFR-TRX-01** One successful checkout shall produce exactly one
completed transaction.\
**SFR-TRX-02** Each completed transaction shall receive a unique invoice
identifier.\
**SFR-TRX-03** Transaction items shall preserve sale-time product
identity, price, quantity, and calculated values required by the PRD.\
**SFR-TRX-04** Completed transaction financial records shall not expose
normal edit or hard-delete operations.\
**SFR-TRX-05** Authorized users shall retrieve transaction history and
detail according to permissions.

## 4.6 Inventory

**SFR-INV-01** Applicable stock shall decrease only after a successful
checkout.\
**SFR-INV-02** Manual adjustments shall require an authorized user and a
non-empty reason.\
**SFR-INV-03** Every stock-affecting sale or manual adjustment shall
produce a traceable stock movement.\
**SFR-INV-04** Where negative stock is prohibited, final checkout
validation shall reject insufficient stock.

## 4.7 Reports

**SFR-REP-01** Reports shall derive totals from eligible completed
transactions.\
**SFR-REP-02** Daily and date-range sales reports shall reconcile with
transaction records under identical filters.\
**SFR-REP-03** Product-sales reports shall aggregate quantities from
historical transaction items rather than current product prices.

## 4.8 Settings and Receipt

**SFR-SET-01** Authorized users shall manage the store identity fields
approved by the PRD.\
**SFR-RCP-01** Completed transactions shall provide a printable receipt
representation.\
**SFR-RCP-02** Receipt financial values shall originate from the
completed transaction snapshot.

------------------------------------------------------------------------

# 5. Non-Functional Requirements

**SNFR-01** The system shall be a maintainable modular monolith.\
**SNFR-02** Critical business state shall be persisted in MySQL.\
**SNFR-03** Authorization shall be enforced server-side.\
**SNFR-04** Validation errors shall be actionable and safe to display.\
**SNFR-05** Supported POS views shall avoid page-level horizontal
scrolling at agreed target viewport sizes.\
**SNFR-06** Application behavior shall preserve historical transaction
integrity.\
**SNFR-07** Store-specific branding shall be configurable.\
**SNFR-08** Critical financial and inventory flows shall have automated
tests.\
**SNFR-09** Production traffic shall use HTTPS.\
**SNFR-10** Application errors shall not expose secrets or internal
stack information to normal production users.

------------------------------------------------------------------------

# 6. Application Architecture

## 6.1 Architecture Style

Use a **Laravel modular monolith** with server-rendered pages.

Preferred flow:

``` text
Browser
  |
  v
Routes / Middleware
  |
  v
Controller or Livewire Component
  |
  +--> Authorization
  +--> Validation
  |
  v
Application / Domain Service
  |
  v
Eloquent Models
  |
  v
MySQL
```

Views:

``` text
Blade
 + Livewire where server-driven reactivity is valuable
 + Alpine.js for small local browser interactions
 + Tailwind CSS for presentation
```

## 6.2 Architectural Boundaries

Logical modules:

-   Auth
-   Dashboard
-   Users
-   Categories
-   Products
-   POS
-   Transactions
-   Inventory
-   Reports
-   Settings
-   Audit

These are logical boundaries inside one Laravel application, not
separate services.

## 6.3 Controller/Component Responsibility

Controllers and Livewire components shall coordinate requests/UI state
but shall not become the primary home of complex checkout, inventory, or
reporting rules.

## 6.4 Model Responsibility

Eloquent models may contain relationships, casts, scopes, and compact
domain behavior. Large multi-record workflows shall not be hidden in
model event chains.

------------------------------------------------------------------------

# 7. Authentication Requirements

1.  Use Laravel-native authentication capabilities compatible with the
    verified project version.
2.  Authentication shall be session-based for the web MVP.
3.  Login shall reject inactive users.
4.  Login failure messages shall not disclose whether a specific account
    exists.
5.  Passwords shall be stored only using the framework-supported secure
    password hashing mechanism.
6.  Protected routes shall require authenticated users.
7.  Authentication implementation shall support logout and session
    invalidation.
8.  Password reset/email verification are not mandatory unless
    separately approved because the PRD does not require them.
9.  Social login is out of MVP scope.
10. API token authentication is not required for MVP.

------------------------------------------------------------------------

# 8. Authorization Requirements

1.  Authorization shall be enforced on the server for every protected
    operation.
2.  UI visibility is supplementary and shall not replace authorization
    checks.
3.  Prefer Laravel-native authorization mechanisms such as
    policies/gates compatible with the verified version.
4.  Resource authorization shall account for role and, where applicable,
    record scope.
5.  Cashiers shall not access unrestricted user management, stock
    adjustment, reports, store configuration, or audit functions.
6.  Administrators shall not create, promote, demote, or deactivate the
    protected Owner account under default MVP rules.
7.  Completed transaction destructive operations shall not be authorized
    for standard roles.
8.  Authorization failures shall return a controlled forbidden response.

------------------------------------------------------------------------

# 9. User Role Architecture

MVP roles:

``` text
Owner
Administrator
Cashier
```

Do not introduce a dynamic enterprise RBAC permission-builder unless an
approved requirement requires it.

Recommended technical representation:

-   a stable role identifier associated with each operational user;
-   policies/gates mapping role capability to protected actions;
-   centralized role constants/enumeration where supported by the
    verified environment.

Role rules shall reflect the PRD permissions matrix.

The system must preserve historical references to users even when an
account is deactivated.

------------------------------------------------------------------------

# 10. Session Management

**SES-01** Use server-managed Laravel web sessions.\
**SES-02** Session cookies shall use secure production settings
appropriate for HTTPS.\
**SES-03** Authentication shall regenerate session identity after
successful login.\
**SES-04** Logout shall invalidate the authenticated session and
regenerate anti-forgery state as appropriate.\
**SES-05** Expired sessions shall require re-authentication.\
**SES-06** Session lifetime shall be configurable through
environment/application configuration rather than hard-coded.\
**SES-07** POS cart persistence across session expiry is not guaranteed
unless separately specified.\
**SES-08** Sensitive state shall not be trusted solely because it exists
in browser/session data; checkout must revalidate it.

------------------------------------------------------------------------

# 11. Database Requirements

## 11.1 Database Engine

Target: MySQL 8.4 LTS.\
Verified version: **TBD --- Requires Environment Verification**.

## 11.2 Core Data Areas

The database must support at minimum:

-   users;
-   roles or stable role representation;
-   categories;
-   products;
-   transactions;
-   transaction items;
-   stock movements;
-   store settings;
-   activity/audit records.

Additional supporting tables may be introduced only when they simplify
integrity or maintainability.

## 11.3 Integrity Requirements

1.  Primary keys shall uniquely identify records.
2.  SKU shall have persistent uniqueness enforcement.
3.  Non-null/non-empty barcode values shall be unique.
4.  Invoice numbers shall have persistent uniqueness enforcement.
5.  Monetary values shall use exact decimal-compatible storage, never
    floating-point semantics for persisted financial amounts.
6.  Quantities shall use a type appropriate to the approved product
    quantity model.
7.  Required relationships shall use referential integrity where it does
    not conflict with historical retention.
8.  Historical records shall not rely on mutable product/user display
    fields for values that must remain historically accurate.
9.  Completed transactions shall contain sale-time snapshots required by
    the PRD.
10. Destructive cascading behavior shall not remove completed financial
    history unintentionally.

## 11.4 Migrations

All schema changes shall be represented through version-controlled
Laravel migrations.

Production schema changes shall not depend on manual database edits.

------------------------------------------------------------------------

# 12. Validation Strategy

Use layered validation.

## Request/UI Validation

Validate: - required fields; - formats; - ranges; - accepted file
types; - basic uniqueness feedback; - date-range validity.

## Business Validation

Revalidate business-sensitive conditions inside the application/service
workflow, including: - active product; - current stock; - authorized
role; - non-empty cart; - valid discount; - sufficient cash; - valid
stock adjustment reason.

## Database Validation

Use database constraints for invariants such as: - unique SKU; - unique
invoice number; - unique populated barcode where supported by the chosen
schema; - required foreign-key integrity.

Validation shall not rely exclusively on JavaScript or Livewire client
state.

------------------------------------------------------------------------

# 13. Business Logic Strategy

Business logic shall be centralized by business capability.

Examples:

-   Checkout calculation and completion.
-   Stock adjustment.
-   Invoice generation.
-   Dashboard/report aggregation.
-   User-role protection.
-   Store settings update.

Rules from the PRD shall not be duplicated independently in Blade
templates, controllers, Livewire components, and models.

High-risk rules shall have dedicated automated tests.

Historical calculations shall use stored transaction snapshots, not
current product master values.

------------------------------------------------------------------------

# 14. Service Layer Strategy

A lightweight service/action layer is recommended where a use case:

-   modifies multiple records;
-   requires a database transaction;
-   contains significant business rules;
-   is called from more than one delivery mechanism;
-   benefits from isolated testing.

Expected candidates:

-   `Checkout` application service/action;
-   stock adjustment service/action;
-   reporting query service where queries become non-trivial;
-   store settings update service if file replacement and audit behavior
    must be coordinated.

Do not create a service class for trivial CRUD merely to satisfy a
pattern.

------------------------------------------------------------------------

# 15. Repository Strategy if Required

Default strategy: **do not add a repository layer for ordinary Eloquent
CRUD**.

Eloquent is sufficient for the MVP unless one of these conditions
appears:

1.  A domain requires multiple interchangeable persistence
    implementations.
2.  Complex query behavior becomes difficult to isolate and test.
3.  A stable domain interface is required independently of Eloquent.
4.  Reporting/query code becomes sufficiently complex to justify
    dedicated query objects.

Avoid generic `BaseRepository` abstractions that merely mirror Eloquent
methods.

------------------------------------------------------------------------

# 16. Transaction Management

Database transaction management is mandatory for business workflows that
must complete atomically.

## 16.1 Checkout Transaction Boundary

The successful checkout boundary must coordinate:

1.  final server-side validation;
2.  transaction header creation;
3.  transaction item snapshots;
4.  stock reduction;
5.  stock movement records;
6.  final transaction completion state.

If a required step fails, the checkout shall not remain represented as a
completed sale.

## 16.2 Concurrency

Checkout must protect against concurrent sales causing invalid stock
outcomes.

The implementation shall use database-supported
transactional/concurrency controls appropriate to the verified MySQL
environment.

## 16.3 Manual Stock Adjustment

Stock quantity change and its movement/audit record shall be committed
consistently.

## 16.4 External Side Effects

Non-critical external side effects, if introduced later, shall not make
core financial persistence dependent on unreliable external services.

------------------------------------------------------------------------

# 17. Queue Architecture

MVP queue complexity should be minimal.

The PRD does not require asynchronous external processing.

Therefore:

-   checkout shall not depend on a queue;
-   stock deduction shall not depend on a queue;
-   invoice creation shall not depend on a queue;
-   basic report correctness shall not depend on a queue.

Laravel queues may be introduced later for non-critical asynchronous
work such as: - email; - large imports/exports; - external
notifications; - long-running report generation.

Queue driver/version/configuration: **TBD --- Requires Environment
Verification** if queues become required.

------------------------------------------------------------------------

# 18. Scheduled Jobs

No mandatory product scheduled job is required for MVP.

Potential future scheduled work: - automated backups; - cleanup of
temporary files; - scheduled report delivery; - external low-stock
notifications; - future retention/archival policies.

If scheduled jobs are introduced, use Laravel's native scheduler
compatible with the verified project version and configure a single VPS
scheduler trigger.

------------------------------------------------------------------------

# 19. Cache Strategy

MVP correctness must not depend on cache.

Use cache only when measurement demonstrates value.

Possible cache candidates: - rarely changing store settings; -
non-critical dashboard aggregates where freshness rules are explicit; -
lookup data.

Do not cache: - mutable checkout totals as the source of truth; - final
stock authority; - authorization decisions in a way that can leave stale
privileges; - completed transaction persistence.

Cache driver and TTL values: **TBD --- Requires Environment Verification
and performance measurement**.

------------------------------------------------------------------------

# 20. Notification Architecture

The PRD explicitly excludes real-time push, SMS, email, and
messaging-app notifications from MVP.

MVP notifications are in-application feedback:

-   success messages;
-   validation messages;
-   errors;
-   insufficient-stock warnings;
-   low-stock visibility.

Use framework-native session/Livewire UI feedback patterns compatible
with the verified project version.

Do not add WebSockets, push infrastructure, SMS gateways, or third-party
notification services for MVP.

------------------------------------------------------------------------

# 21. Email Architecture

Email is **not required for the MVP product behavior**.

Do not make email infrastructure a prerequisite for: - login; -
checkout; - stock; - reporting; - receipts.

If future features require email, prefer Laravel's native
mail/notification facilities and queue non-critical delivery.

Mail provider, SMTP service, and credentials are **TBD --- Not Required
for MVP**.

------------------------------------------------------------------------

# 22. File Storage

MVP file upload requirement: store logo.

Requirements:

1.  Use Laravel filesystem abstraction compatible with the verified
    version.
2.  Default VPS deployment may use local persistent storage for the
    store logo.
3.  Public access shall expose only files intended for public display.
4.  Upload validation shall enforce approved image types and size
    limits.
5.  Invalid upload shall not replace the existing valid logo.
6.  Replacing a logo shall avoid leaving application state pointing to a
    missing file.
7.  Product images are optional/out of core MVP.
8.  Object storage is not required for MVP but future migration should
    remain possible through the filesystem abstraction.

------------------------------------------------------------------------

# 23. Logging

Application logging shall use Laravel-native logging facilities
compatible with the verified version.

Production logs shall include sufficient context to diagnose:

-   authentication failures at an appropriate security level;
-   authorization failures where useful;
-   application exceptions;
-   checkout failures;
-   database failures;
-   queue/scheduler failures if introduced;
-   file-storage failures.

Logs shall not contain: - plaintext passwords; - session secrets; -
application secrets; - unnecessary sensitive credential data.

Log retention/rotation shall be configured for the VPS environment.

------------------------------------------------------------------------

# 24. Activity Logging

Activity logging is distinct from diagnostic application logging.

MVP activity/audit requirements include:

-   manual stock adjustments;
-   user role changes;
-   user active-status changes;
-   sensitive store-setting changes;
-   cashier identity on completed transactions.

Prefer a small first-party audit model/table for the MVP unless
environment inspection later demonstrates an already-installed suitable
dependency.

Each audit entry shall contain enough context to determine: - actor; -
action; - target/resource; - relevant change/reference; - timestamp.

Cashiers shall not edit audit records.

Standard application functions shall not silently delete audit history.

------------------------------------------------------------------------

# 25. Error Handling

1.  Validation errors shall return actionable field/business messages.
2.  Authorization failures shall produce a controlled forbidden
    response.
3.  Missing records shall produce a controlled not-found state.
4.  Production unexpected errors shall not expose stack traces or
    secrets to users.
5.  Failed checkout shall not be displayed as successful.
6.  Transactional failures shall roll back required atomic business
    changes.
7.  Recoverable form input should be preserved where practical.
8.  Application exceptions shall be logged with diagnostic context.
9.  User-facing error text shall distinguish correctable business
    conditions from unexpected system failures.

------------------------------------------------------------------------

# 26. API Requirements

No public REST API is required for the MVP.

The primary interface is Laravel web routes with Blade/Livewire.

Rules:

1.  Do not build a duplicate API merely for architectural symmetry.
2.  Livewire/server interactions remain part of the web application
    boundary.
3.  If a future mobile app, integration, or SaaS requirement introduces
    an API, it shall receive a separate versioned API specification.
4.  API authentication, rate limits, resource formats, and versioning
    are therefore **not applicable to MVP**.

------------------------------------------------------------------------

# 27. Webhook Requirements if Applicable

No webhook is required by the PRD.

MVP status: **Not Applicable**.

Future payment gateways or integrations may introduce webhooks only
after: - event contract definition; - authentication/signature
requirements; - idempotency rules; - retry behavior; - audit
requirements; - failure-handling requirements

are separately specified.

------------------------------------------------------------------------

# 28. Search Architecture

Search shall use database-backed search appropriate to MVP scale.

Required searchable fields:

### POS Products

-   name;
-   SKU;
-   barcode.

### Product Management

-   name;
-   SKU;
-   barcode.

### Transactions

-   invoice number;
-   date range;
-   cashier where authorized.

Requirements:

1.  Search must respect authorization.
2.  Indexed exact-match identifiers such as SKU, barcode, and invoice
    number shall be optimized through database indexes.
3.  Common filter columns shall be indexed based on measured query
    patterns.
4.  No Elasticsearch/OpenSearch/Meilisearch dependency is required for
    MVP.
5.  Search results shall use pagination or bounded result behavior where
    appropriate.

------------------------------------------------------------------------

# 29. Import/Export

PRD status: optional/post-MVP.

MVP does not require bulk product import or spreadsheet export.

If added:

1.  Import shall validate every required business rule.
2.  Invalid rows shall be reported clearly.
3.  Import shall not bypass SKU/barcode uniqueness.
4.  Large imports may use queues only if synchronous processing becomes
    unsuitable.
5.  Export shall respect authorization and active filters.
6.  Exported report totals shall reconcile with on-screen totals.
7.  Third-party spreadsheet libraries shall be introduced only if the
    approved file format cannot be reasonably produced with existing
    project capabilities.

------------------------------------------------------------------------

# 30. Reporting Architecture

MVP reports shall be database-derived read operations over completed
transaction data.

Principles:

1.  Completed transactions are the financial source of truth.
2.  Historical transaction items are the source for historical product
    sales.
3.  Reports must not recalculate old sales using current product prices.
4.  Date-range filtering must use a consistent business timezone.
5.  Report queries shall be isolated from UI presentation.
6.  Dashboard metrics and reports using equivalent periods/rules must
    reconcile.
7.  Start with direct aggregate queries/query services.
8.  Do not introduce a data warehouse, OLAP engine, or analytics service
    for MVP.
9.  Precomputed summary tables are not required unless performance
    measurements justify them.

------------------------------------------------------------------------

# 31. Security Architecture

## 31.1 Transport

Production access shall use HTTPS.

## 31.2 Authentication

Use secure session-based authentication and framework-supported password
hashing.

## 31.3 Authorization

Use server-side policies/gates/middleware appropriate to the verified
Laravel version.

## 31.4 CSRF

State-changing web requests shall use Laravel's native CSRF protection
where applicable.

## 31.5 XSS

Prefer escaped Blade output. Raw HTML rendering shall be avoided unless
content is explicitly trusted/sanitized.

## 31.6 SQL Injection

Use Eloquent/query builder parameterization. Avoid unsafe
string-concatenated SQL.

## 31.7 Mass Assignment

Models shall explicitly protect which fields may be assigned through
request-driven operations according to the verified Laravel conventions.

## 31.8 File Uploads

Validate file type, size, and intended storage visibility.

## 31.9 Secrets

Secrets shall be environment-configured and shall not be committed to
source control.

## 31.10 Production Debugging

Debug output shall be disabled in production.

## 31.11 Rate Limiting

Authentication endpoints shall use suitable framework-native
throttling/rate limiting. Additional rate limits may be added based on
exposure and abuse risk.

## 31.12 Security Headers

The VPS/application shall provide appropriate baseline web security
headers compatible with application behavior.

------------------------------------------------------------------------

# 32. Backup Requirements

The PRD does not require an end-user backup/restore UI in MVP, but
production operations require recoverability.

Minimum VPS operational requirements:

1.  MySQL data shall be backed up on a defined schedule.
2.  Uploaded persistent files required by the business shall be included
    in backup coverage.
3.  Environment secrets shall be backed up securely outside the public
    application directory through an operational process.
4.  Backups shall be stored separately from the live application host or
    otherwise protected from a single-host failure.
5.  Backup retention shall be documented.
6.  Restore procedures shall be documented and periodically tested.
7.  Backup failure shall be operationally detectable.
8.  A future in-app backup/restore feature requires separate product
    approval.

Exact frequency, retention, and off-site provider: **TBD --- Requires
Deployment Policy Definition**.

------------------------------------------------------------------------

# 33. Testing Requirements

## 33.1 Unit Tests

Required for isolated high-risk business calculations/rules such as: -
totals; - discount boundaries; - cash/change calculations; -
role/capability rules where isolated; - invoice-generation rules where
isolated.

## 33.2 Feature Tests

Required for: - login/logout; - inactive account rejection; - role
authorization; - product/category CRUD permissions; - SKU/barcode
uniqueness; - checkout; - insufficient payment; - insufficient stock; -
stock adjustment; - transaction search; - reporting reconciliation; -
settings access; - audit behavior.

## 33.3 Transaction Integrity Tests

Must verify: - exactly one completed transaction per successful
checkout; - failed checkout does not leave completed partial state; -
stock and stock movement reconcile with successful sale; - historical
transaction values survive product edits.

## 33.4 UI/Livewire Tests

Critical reactive POS behavior shall be tested using test facilities
supported by the verified Livewire/Laravel versions.

## 33.5 Browser/E2E Tests

At least the critical sale journey should receive browser-level
acceptance coverage before commercial release when a suitable
project-supported tool is selected.

## 33.6 Regression

No release may knowingly break the core:

``` text
login -> product -> cart -> payment -> checkout
-> transaction -> stock -> receipt -> report
```

------------------------------------------------------------------------

# 34. Deployment Requirements

Target: VPS.

Minimum deployment characteristics:

``` text
Internet / Client Browser
          |
        HTTPS
          |
     Web Server
          |
     PHP Runtime
          |
   Laravel SimplePOS
      |         |
    MySQL    Storage
```

Requirements:

1.  Production shall use a supported PHP runtime compatible with the
    verified Laravel project.
2.  Required PHP extensions shall be documented after project
    initialization.
3.  MySQL shall use a verified compatible version.
4.  Web server configuration shall expose only the intended public
    application entry point.
5.  HTTPS certificate shall be active.
6.  Application secrets shall be provided through environment
    configuration.
7.  Persistent storage permissions shall be configured correctly.
8.  Production dependencies shall be installed deterministically from
    lock files.
9.  Frontend assets shall be built from the project's locked dependency
    set.
10. Database migrations shall run through a controlled deployment
    procedure.
11. Production debug mode shall be disabled.
12. Application caches may be generated as supported by the verified
    Laravel version.
13. Deployment shall include rollback/recovery planning for application
    and schema changes.
14. Queue workers/scheduler processes are required only if approved
    features actually use them.

------------------------------------------------------------------------

# 35. Environment Configuration

Configuration shall be environment-driven.

Expected configuration categories:

-   application name/environment;
-   application URL;
-   application key/secret;
-   debug mode;
-   timezone;
-   locale;
-   database connection;
-   session configuration;
-   cache configuration;
-   queue configuration if used;
-   filesystem/storage configuration;
-   logging configuration;
-   mail configuration if later used.

Rules:

1.  `.env` shall not be committed with production secrets.
2.  `.env.example` shall document required non-secret configuration keys
    once the project exists.
3.  Production and development configuration shall be separated.
4.  Business settings such as store name and receipt footer belong in
    application-managed settings, not deployment environment variables,
    unless they are truly deployment-level values.

------------------------------------------------------------------------

# 36. Performance Requirements

These targets are inherited from the PRD and apply under a documented
supported deployment profile.

**PERF-SRS-01** POS product search should normally become usable within
2 seconds.\
**PERF-SRS-02** Cart quantity/total interaction should visibly update
within 1 second under expected conditions.\
**PERF-SRS-03** Standard successful checkout should reach
success/receipt-ready state within 3 seconds.\
**PERF-SRS-04** Standard dashboard should become usable within 3
seconds.\
**PERF-SRS-05** Standard 30-day report should become usable within 5
seconds.\
**PERF-SRS-06** Large record lists shall use pagination/bounded
retrieval.

Before release certification, the team shall document: - representative
database size; - expected concurrent users; - VPS specification; -
network conditions; - browser; - measurement procedure.

Performance optimization shall be measurement-driven. Do not introduce
Redis, search engines, or precomputed analytics without demonstrated
need.

------------------------------------------------------------------------

# 37. Scalability Requirements

MVP scalability target is a **single-store deployment on one
conventional VPS**.

Design requirements:

1.  Application code shall remain stateless where practical except for
    configured session/storage mechanisms.
2.  Database indexes shall support common lookup/filter paths.
3.  Pagination shall prevent unbounded list retrieval.
4.  File access shall use Laravel's storage abstraction to preserve
    future migration options.
5.  Queue interfaces may support future asynchronous workloads without
    requiring queues today.
6.  Store-specific configuration shall not be hard-coded.
7.  Multi-tenancy is explicitly out of MVP scope.
8.  Horizontal scaling, read replicas, distributed cache, container
    orchestration, and service decomposition are not required.
9.  Future SaaS conversion shall be treated as a separate architectural
    phase, not a hidden MVP requirement.

------------------------------------------------------------------------

# 38. Maintainability Requirements

1.  Follow the conventions of the verified Laravel version.
2.  Prefer framework-native features over unnecessary dependencies.
3.  Keep controllers/Livewire components focused on request/UI
    orchestration.
4.  Centralize multi-step business workflows.
5.  Use clear domain-oriented naming.
6.  Keep business rules traceable to PRD/SRS requirement IDs where
    practical.
7.  All schema changes shall use migrations.
8.  Dependency versions shall be locked.
9.  Do not modify vendor dependencies directly.
10. Avoid premature abstractions such as generic repositories/services
    with no business purpose.
11. Maintain automated tests for critical workflows.
12. Keep store-specific customization outside core business logic.
13. Document environment setup and production deployment.
14. Remove dead code and obsolete feature flags before commercial
    releases.
15. New dependencies require a concrete requirement and maintenance
    justification.

------------------------------------------------------------------------

# 39. Upgrade Strategy

## 39.1 Framework and PHP

1.  Verify current project versions before every upgrade.
2.  Review official upgrade guidance for the installed version path.
3.  Upgrade in controlled increments rather than blindly changing major
    versions.
4.  Run the full automated test suite before and after upgrade.
5.  Verify authentication, authorization, Livewire behavior, asset
    build, database behavior, checkout, and reports after framework
    upgrades.

## 39.2 Database

1.  Verify MySQL compatibility before changing server versions.
2.  Back up production data before major database upgrades.
3.  Test migrations and critical financial queries against a staging
    copy.
4.  Avoid schema changes that silently reinterpret historical financial
    data.

## 39.3 Frontend Dependencies

1.  Treat `package.json` plus its lock file as authoritative after
    initialization.
2.  Upgrade Livewire/Alpine/Tailwind only after compatibility
    verification.
3.  Regression-test the POS interaction flow after frontend dependency
    changes.

## 39.4 Commercial Releases

Use semantic or clearly documented product release versions.

Recommended product progression follows the PRD: - 1.0 Core MVP; - 1.5
Commercial Productivity; - 2.0 Pro; - 3.0 Modular Editions; - 4.0 SaaS
Platform.

Technical upgrades shall not silently introduce future product-scope
features.

------------------------------------------------------------------------

# 40. Technical Constraints

1.  The PRD is authoritative for product behavior and MVP scope.
2.  Target backend framework family: Laravel.
3.  Target database family: MySQL.
4.  Target frontend approach: Blade + Livewire + Alpine.js + Tailwind
    CSS.
5.  Target production environment: VPS.
6.  Actual framework/runtime/package/database versions are **TBD ---
    Requires Environment Verification** until an initialized environment
    is available.
7.  MVP is single-store, not multi-tenant.
8.  MVP uses cash payment as defined by the PRD.
9.  No public API is required.
10. No webhook is required.
11. No email is required for core MVP behavior.
12. No external push/SMS/WhatsApp notification is required.
13. No queue-dependent critical transaction processing.
14. No microservices.
15. No Kubernetes requirement.
16. No Redis requirement unless later justified by measurement/approved
    workload.
17. No external search engine requirement.
18. No event-streaming platform requirement.
19. No data warehouse requirement.
20. No full accounting, ERP, payroll, CRM, e-commerce, multi-branch, or
    multi-warehouse implementation in MVP.
21. Completed financial history must remain historically stable.
22. Transaction and stock consistency is release-critical.
23. Authorization must be enforced server-side.
24. Commercial customizability must not turn the core into
    customer-specific code.

------------------------------------------------------------------------

# Technical Decision Summary

SimplePOS should begin as a conventional Laravel modular monolith:

``` text
Blade / Livewire / Alpine.js
            |
            v
     Laravel Web Layer
            |
            v
 Business Services / Actions
            |
            v
        Eloquent
            |
            v
          MySQL
```

The most important technical boundary is the checkout workflow:

``` text
Validate User + Cart + Product + Stock + Payment
                     |
                     v
             DB Transaction
                     |
        +------------+-------------+
        |            |             |
 Transaction    Transaction     Inventory
   Header          Items         Movement
        |            |             |
        +------------+-------------+
                     |
                     v
               COMMIT
                     |
                     v
            Receipt / Report
```

The system must remain intentionally simple until real requirements
justify additional infrastructure.

------------------------------------------------------------------------

# Environment Verification Checklist

Before implementation begins, inspect and record:

``` text
php -v
composer --version
composer show laravel/framework
mysql --version
node --version
npm --version
```

Also inspect:

``` text
composer.json
composer.lock
package.json
package-lock.json / other package lock
.env.example
bootstrap/app.php
config/*
```

Then update the Technology Environment Verification section of this SRS
with the actual installed versions.

Until that inspection can be performed, version-specific claims remain:

**TBD --- Requires Environment Verification**
