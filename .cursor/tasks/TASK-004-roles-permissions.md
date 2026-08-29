# TASK-004 — Roles and Permissions

**Task ID:** TASK-004  
**Title:** Roles and permissions  
**Filename:** `TASK-004-roles-permissions.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Encode the three MVP roles and the permissions matrix as Laravel Policies/Gates, including protected-Owner rules and role-aware navigation helpers, so every later module authorizes the same way.

---

## Background

PRD §11, §20, FR-AUTH-05, BR-16, SRS §8–9, SYSTEM_DESIGN §9, CURSOR.md §12.

Fixed roles only. Do **not** add a `permissions` table, role-builder UI, or Spatie permission package unless an explicit later requirement says so.

**Implement this before TASK-003.**

---

## Dependencies

- TASK-001 (`User`, `UserRole`)
- TASK-002 (authenticated user)

---

## Files likely affected

- `app/Enums/UserRole.php` (complete helpers: `isOwner()`, `isAdministrator()`, `isCashier()` if useful)
- `app/Policies/UserPolicy.php`
- `app/Providers/AppServiceProvider.php` (Gate::define for cross-cutting abilities)
- `app/View/Components/` or Blade composer for sidebar flags
- `resources/views/layouts/app.blade.php` / `resources/views/components/sidebar.blade.php`
- `bootstrap/app.php` if registering policies
- `tests/Feature/Identity/` or `tests/Feature/Auth/` authorization tests

Feature policies for Product, Transaction, etc. are created **in those later tasks**, but the Gates listed below must exist now so later tasks reuse names.

---

## Database changes

None. Roles live on `users.role`.

---

## Backend requirements

Define Gates (names may be camelCase as below; keep them stable):

| Gate / ability | Owner | Administrator | Cashier |
|---|---|---|---|
| `accessPos` | yes | yes | yes |
| `viewDashboard` | yes | yes | **no** (DASH-06) |
| `viewOwnTransactions` | yes | yes | yes (own/allowed) |
| `viewAllTransactions` | yes | yes | no |
| `manageProducts` | yes | yes | no |
| `manageCategories` | yes | yes | no |
| `adjustStock` | yes | yes | no |
| `viewStockMovements` | yes | yes | no |
| `viewReports` | yes | yes | no |
| `manageUsers` | yes | limited | no |
| `assignOwnerRole` | yes | no | no |
| `manageStoreSettings` | yes | limited | no |
| `viewAuditLog` | yes | limited/no — **default MVP: Owner only** unless a later approved rule expands Administrator | no |

`UserPolicy` must implement:

- Administrator cannot `update` / `delete` / deactivate / change role of a user whose role is `OWNER`.
- Administrator cannot `create` with role `OWNER`.
- Cashier has no user management abilities.
- Nobody has `forceDelete` on completed transactions (encode as a Gate `deleteCompletedTransaction` that returns false for all MVP roles).

Do not hardcode role strings in Blade; use `@can` / policies.

---

## Frontend requirements

- Sidebar groups from `UI_UX.md` §3, filtered by `@can`.
- Cashier sidebar: POS and Transactions (and Profile/logout). No Users, Settings, Audit, Reports, Inventory, Categories.
- Owner: full list (links to unimplemented modules may be omitted until those routes exist).
- Unauthorized deep link later returns 403; for now add a simple 403 Blade page.

---

## Validation rules

None beyond enum-backed `role` values already on `User`.

---

## Authorization rules

- UI hiding is supplementary; Gates/Policies are authoritative (`CURSOR.md` §12).
- Record-level: cashiers may only access own/allowed transactions once TASK-014 exists; define `viewAllTransactions` vs own now so TASK-014 can call `$user->can('viewAllTransactions')`.
- Default commercial rule: Administrator never controls the protected Owner (PRD §20).

---

## Business rules

- BR-16.
- Three roles only.
- Completed transactions are not hard-deletable (matrix row).

---

## Edge cases

- User with unknown/invalid `role` value: treat as unauthorized for all management gates; do not throw raw errors to the UI.
- Changing role of the acting Administrator to Owner is only allowed if policy permits (Owner only).
- Blade `@can` and Livewire `authorize()` must agree.

---

## Security considerations

- No client-side-only role checks.
- Do not put role in a hidden field as authority.
- 403 must not leak extra user data.
- Tests must include cashier denied (AC-11) even if user UI is TASK-003: assert `Gate::forUser($cashier)->denies('manageUsers')` and `denies('adjustStock')`.

---

## Testing requirements

- Owner allowed for management gates listed yes.
- Administrator denied `assignOwnerRole` and denied updating an Owner user.
- Cashier denied `manageUsers`, `adjustStock`, `viewReports`, `manageStoreSettings`, `viewAuditLog`, `viewDashboard`.
- All roles denied `deleteCompletedTransaction`.
- Feature test: cashier hitting a future-proof named route you register as a tiny authorized-only ping **or** unit/feature tests on Gates if no user routes exist yet.

---

## Acceptance criteria

- [ ] Permissions matrix encoded in Policies/Gates.
- [ ] Protected Owner rules tested.
- [ ] AC-11 gate assertions pass.
- [ ] Sidebar visibility follows role.
- [ ] No dynamic RBAC package.

---

## Definition of Done

- [ ] Later tasks can authorize with these Gates/Policies without duplicating role `if` trees.
- [ ] Tests pass.
- [ ] 403 page exists.
- [ ] Documentation of gate names is in this task file (source of truth for implementers).
