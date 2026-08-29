# TASK-005 — Audit Recording

**Task ID:** TASK-005  
**Title:** Audit recording  
**Filename:** `TASK-005-audit-recording.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Persist business audit rows for sensitive changes through a single `RecordActivity` action, and hook it into existing user role/status writes. Do not build the audit log UI (TASK-021).

---

## Background

PRD §28 AUD-01–07, SRS §24, DATABASE.md §20 / §30, CURSOR.md §34.

Completed sales are **not** duplicated as generic activity events; they are represented by transactions and stock movements.

This task is the write model only.

---

## Dependencies

- TASK-001
- TASK-003 (user Actions to hook)
- TASK-004

---

## Files likely affected

- `database/migrations/*_create_activity_logs_table.php`
- `app/Models/ActivityLog.php`
- `app/Enums/ActivityAction.php`
- `app/Actions/Audit/RecordActivity.php`
- `app/Actions/Identity/CreateUser.php`
- `app/Actions/Identity/ChangeUserRole.php` (extract from UpdateUser if role change is currently inline)
- `app/Actions/Identity/ChangeUserStatus.php`
- `app/Policies/ActivityLog.php` or `ActivityLogPolicy.php` — deny update/delete for all roles; view is TASK-021
- `tests/Feature/Audit/` or `tests/Feature/Identity/` asserting log rows
- `database/factories/ActivityLogFactory.php` if useful for tests

---

## Database changes

Create `activity_logs` (`DATABASE.md` §30):

- `id`, `user_id` nullable FK users, `action` VARCHAR(100) indexed
- `subject_type` nullable, `subject_id` nullable
- `old_values` JSON nullable, `new_values` JSON nullable, `context` JSON nullable
- `occurred_at` datetime indexed, `created_at`
- **No `updated_at`** (rows are not edited)
- Index `(subject_type, subject_id, occurred_at)`

FK on `user_id`: do not cascade-delete audit when a user is deactivated (user rows are not deleted in MVP).

---

## Backend requirements

1. `ActivityAction` enum: `USER_CREATED`, `USER_ROLE_CHANGED`, `USER_STATUS_CHANGED`. Add `STOCK_MANUAL_ADJUSTED` and `STORE_SETTINGS_UPDATED` now so later tasks reuse the enum without migrating string chaos — those actions are written in TASK-016 and TASK-008.
2. `RecordActivity::execute(...)` inserts one row. No events/listeners.
3. Call it **inside the same DB transaction** as the user change.
4. Do not log passwords or password hashes in `old_values` / `new_values`.
5. `ActivityLogPolicy`: `update`/`delete` false for everyone.

---

## Frontend requirements

None. Do not add Activity Log navigation yet (TASK-021).

---

## Validation rules

`RecordActivity` requires a non-empty `action`. Subject fields optional but should be set for user events (`subject_type` = `User`, `subject_id` = target user).

---

## Authorization rules

- Recording is performed by trusted Actions after the actor already passed UserPolicy.
- Cashiers must not be able to write arbitrary audit rows via HTTP. No public controller for insert.

---

## Business rules

- AUD-04: role and active status changes are auditable.
- AUD-03 is satisfied by `transactions.cashier_id` (not this table).
- AUD-06/07: no cashier edit; no standard delete.

---

## Edge cases

- Failed user update must not write a log (same transaction rollback).
- Null actor: should not happen for user admin; require `user_id` of the acting staff.
- JSON columns must store only non-sensitive diffs (role, active, name, email — not password).

---

## Security considerations

- Never log credentials or session tokens (`CURSOR.md` §33).
- Mass assignment on ActivityLog should be tightly controlled (create only via Action).
- Do not expose audit rows in this task.

---

## Testing requirements

- Creating a user writes `USER_CREATED`.
- Role change writes `USER_ROLE_CHANGED` with old/new role.
- Status change writes `USER_STATUS_CHANGED`.
- Password change does not store the password in audit JSON.
- Rollback: force a failure after user write if practical, or assert Action uses `DB::transaction`.
- Policy denies update/delete.

---

## Acceptance criteria

- [ ] `activity_logs` matches DATABASE.md.
- [ ] User create/role/status produce audit rows.
- [ ] No audit UI.
- [ ] No sale events duplicated here.

---

## Definition of Done

- [ ] Single `RecordActivity` write path exists.
- [ ] Identity Actions hooked.
- [ ] Tests pass.
- [ ] Enum includes future stock/settings actions without writing them yet.
