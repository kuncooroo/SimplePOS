# TASK-003 — Users

**Task ID:** TASK-003  
**Title:** User management  
**Filename:** `TASK-003-users.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let authorized staff create, view, update, activate, and deactivate operational users with a role, without deleting historical identity and without allowing Administrators to control the protected Owner.

---

## Background

MVP-02, FR-USER-01–04, BUSINESS_FLOW §3, PRD permissions matrix §20, AC-11 (cashier denied).

User administration is not self-registration. There is no public registration.

Implement **after TASK-004** so Policies/Gates already exist. If TASK-004 is missing, stop and implement TASK-004 first.

---

## Dependencies

- TASK-001, TASK-002
- **TASK-004 (required)** — UserPolicy, role gates, protected Owner rules

---

## Files likely affected

- `app/Livewire/Identity/UserIndex.php`
- `app/Livewire/Identity/UserForm.php`
- `app/Actions/Identity/CreateUser.php`
- `app/Actions/Identity/UpdateUser.php`
- `app/Actions/Identity/ChangeUserStatus.php`
- `app/Http/Requests/Identity/` (only if not using Livewire validation)
- `app/Policies/UserPolicy.php` (extend, do not replace TASK-004 rules)
- `resources/views/livewire/identity/`
- `routes/web.php`
- `database/factories/UserFactory.php`
- `tests/Feature/Identity/`

Do not add a `users` repository.

---

## Database changes

None beyond TASK-001 `users`. Do not hard-delete users. No soft deletes required (`DATABASE.md` §14).

---

## Backend requirements

1. Paginated user list: name, email, role, active status; search by name/email.
2. Create user: name, email, password, role, active (default true).
3. Edit permitted fields: name, email, role (subject to policy), active, optional password change.
4. Deactivate/reactivate via `ChangeUserStatus` — do not destroy the row (FR-USER-04).
5. Hash passwords with the framework hasher.
6. Unique email at database and validation layers.
7. Use Actions for create/update/status when the flow writes the user **and** (once TASK-005 exists) will record audit. Until TASK-005, Actions still keep writes out of Livewire.
8. Eager-load nothing unnecessary; paginate (`CURSOR.md` §31).

---

## Frontend requirements

- Users list + create + edit pages per `UI_UX.md` CRUD standards (§24).
- Sidebar item “Users” visible only when the policy allows view/manage.
- Role shown as a clear label (Owner / Administrator / Cashier), not a raw dump of internals.
- Confirm before deactivation.
- Empty and “no search results” states (SRCH-08).
- Success/error toasts (NOT-01, NOT-02, NOT-06).
- Cashier must not see the Users nav item **and** must be denied if they hit the URL.

---

## Validation rules

| Field | Create | Update |
|---|---|---|
| name | required, max 150 | required, max 150 |
| email | required, email, unique | required, email, unique except self |
| password | required, min length per Laravel default (document it) | optional; if present, confirmed + min length |
| password_confirmation | required_with password | required_with password |
| role | required, `UserRole` | required, `UserRole` |
| active | boolean | boolean |

---

## Authorization rules

From PRD §20 and TASK-004:

| Actor | Can |
|---|---|
| Owner | Full user management including assigning Owner if product allows (default: Owner may assign Owner; keep this explicit and tested) |
| Administrator | Limited: manage Cashiers and Administrators as defined by UserPolicy; **cannot** create, promote, demote, or deactivate the protected Owner; **cannot** assign `OWNER` |
| Cashier | No |

IDOR: a cashier must not open `/users/{id}/edit` by guessing IDs (SEC-07).

Users cannot use this screen to edit their own role in a way that bypasses policy; self-service is TASK-022.

---

## Business rules

- BR-16: role-permitted actions only.
- Deactivation does not cascade-delete transactions or logs (FR-USER-04, BR-14).
- Inactive users cannot log in (already TASK-002); this task must set `active` correctly.
- Do not implement “delete user.”

---

## Edge cases

- Duplicate email.
- Administrator attempts to change Owner role/status → 403, no write.
- Last/protected Owner cannot be deactivated through Administrator; Owner deactivating themselves: **reject** (avoid lockout) unless a second Owner exists — default MVP: do not allow the acting user to deactivate their own account.
- Empty search vs empty directory.
- Password omitted on update leaves hash unchanged.

---

## Security considerations

- Mass assignment: never accept `id` or raw hashes from the client.
- Never echo existing password.
- Authorize on every Livewire mutating method, not only on `mount`.
- CSRF via Livewire/Laravel.
- Audit of role/status changes is TASK-005; leave a single write path in Actions so audit can hook without a second UI.

---

## Testing requirements

- Owner can create Administrator and Cashier.
- Administrator cannot assign Owner and cannot deactivate Owner.
- Cashier is forbidden on index/create/update (AC-11).
- Unique email rejected.
- Deactivated user still exists and can be referenced later (assert row remains).
- Inactive user cannot authenticate (regression on TASK-002).

---

## Acceptance criteria

- [ ] FR-USER-01–04 satisfied.
- [ ] Permissions matrix for User management / Assign Owner / Deactivate users holds.
- [ ] AC-11: cashier denied user management.
- [ ] No hard delete.

---

## Definition of Done

- [ ] User admin UI works for Owner and limited Administrator.
- [ ] Policies enforced server-side.
- [ ] Validation and empty/error states present.
- [ ] Tests pass; POS flows not required yet.
- [ ] No extra RBAC builder or permissions tables.
