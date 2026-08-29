# SimplePOS Cursor Tasks — MVP

**Product:** SimplePOS — Sistem Kasir & Penjualan Sederhana  
**Scope:** Version 1.0 Core MVP (`PRD.md` §13, §38)

`docs/ROADMAP.md` was not present in the repository. These tasks implement **Version 1.0** from `PRD.md` §38 only. Versions 1.5–4.0 are out of scope.

**Authoritative docs:** `docs/PRD.md`, `docs/SRS.md`, `docs/SYSTEM_DESIGN.md`, `docs/DATABASE.md`, `docs/BUSINESS_FLOW.md`, `docs/PROJECT_STRUCTURE.md`, `docs/INSTALLER.md`, `CURSOR.md`  
(Until docs are copied into `docs/`, use the matching files at the repository root.)

---

## How to use

1. Complete tasks in the **implementation order** below, not only by filename.
2. Implement **one task per session** unless the user explicitly expands scope.
3. Do not add out-of-scope features (refunds, payment gateways, APIs, Redis, queues for checkout).
4. Follow `docs/PROJECT_STRUCTURE.md` for file placement.
5. Follow `CURSOR.md` for engineering rules.

Each task is independently implementable **after its dependencies are done**.

---

## Implementation order

| Order | Task | MVP coverage |
|---|---|---|
| 1 | [TASK-001-project-foundation.md](TASK-001-project-foundation.md) | App bootstrap |
| 2 | [TASK-002-authentication.md](TASK-002-authentication.md) | MVP-01 |
| 3 | [TASK-004-roles-permissions.md](TASK-004-roles-permissions.md) | MVP-02 (authorization) |
| 4 | [TASK-003-users.md](TASK-003-users.md) | MVP-02 (user admin) |
| 5 | [TASK-005-audit-recording.md](TASK-005-audit-recording.md) | AUD-01–05 write path |
| 6 | [TASK-006-categories.md](TASK-006-categories.md) | MVP-03 |
| 7 | [TASK-007-products.md](TASK-007-products.md) | MVP-04 |
| 8 | [TASK-008-store-settings.md](TASK-008-store-settings.md) | MVP-09 settings |
| 9 | [TASK-009-pos-product-search.md](TASK-009-pos-product-search.md) | MVP-05 search |
| 10 | [TASK-010-pos-cart.md](TASK-010-pos-cart.md) | MVP-05 cart |
| 11 | [TASK-011-pos-discount-and-payment.md](TASK-011-pos-discount-and-payment.md) | MVP-05 discount/cash UI |
| 12 | [TASK-012-checkout.md](TASK-012-checkout.md) | MVP-05/06/07 sale persistence |
| 13 | [TASK-013-receipts.md](TASK-013-receipts.md) | MVP-09 receipt |
| 14 | [TASK-014-transaction-history.md](TASK-014-transaction-history.md) | MVP-06 history |
| 15 | [TASK-015-inventory-stock-overview.md](TASK-015-inventory-stock-overview.md) | MVP-07 current/low stock |
| 16 | [TASK-016-stock-adjustment.md](TASK-016-stock-adjustment.md) | MVP-07 adjustment |
| 17 | [TASK-017-stock-movement-history.md](TASK-017-stock-movement-history.md) | MVP-07 movements |
| 18 | [TASK-018-dashboard.md](TASK-018-dashboard.md) | MOD-02 |
| 19 | [TASK-019-sales-reports.md](TASK-019-sales-reports.md) | MVP-08 sales |
| 20 | [TASK-020-product-sales-reports.md](TASK-020-product-sales-reports.md) | MVP-08 product sales |
| 21 | [TASK-021-audit-log-ui.md](TASK-021-audit-log-ui.md) | MOD-11 read UI |
| 22 | [TASK-022-user-profile.md](TASK-022-user-profile.md) | Profile (UI_UX) |
| 23 | [TASK-023-mvp-e2e-regression.md](TASK-023-mvp-e2e-regression.md) | PRD AC-01–14 / exit condition |

TASK-003 is numbered to match the requested naming (`users` then `roles-permissions`) but **roles must be implemented before user administration**.

---

## Commercial installer (after MVP)

Packaging for source-code buyers. Design: `docs/INSTALLER.md`. Implement **after TASK-023** unless a later task lists an earlier technical dependency.

The first privileged account is the **protected Owner**, not `ADMINISTRATOR`. After a successful install, `/install*` must be HTTP 404 (not an “already installed” UI).

| Order | Task | Coverage |
|---|---|---|
| 24 | [TASK-024-installer-shell-and-gating.md](TASK-024-installer-shell-and-gating.md) | Wizard shell, CSRF, throttle, not-installed redirect |
| 25 | [TASK-025-installer-requirement-checks.md](TASK-025-installer-requirement-checks.md) | System, PHP extensions, folder permissions |
| 26 | [TASK-026-installer-database-and-environment.md](TASK-026-installer-database-and-environment.md) | DB connection, `.env`, `APP_KEY` |
| 27 | [TASK-027-installer-migrate-owner-settings-demo.md](TASK-027-installer-migrate-owner-settings-demo.md) | Migrate, Owner, store settings, optional demo |
| 28 | [TASK-028-installer-lock-and-hardening.md](TASK-028-installer-lock-and-hardening.md) | Completion, lock, Artisan CLI, post-install 404 |

---

## Explicitly not tasked (out of MVP)

Refunds, voids, payment gateways, email/SMS/WhatsApp, public API, multi-tenancy, Redis, checkout queues, product images, CSV import/export, multi-warehouse, native mobile.
