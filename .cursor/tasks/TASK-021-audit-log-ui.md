# TASK-021 — Audit Log UI

**Task ID:** TASK-021  
**Title:** Audit log UI  
**Filename:** `TASK-021-audit-log-ui.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let permitted roles (default: Owner) view activity log entries. No edit or delete. Cashiers have no access.

---

## Background

MOD-11, AUD-06–08, PRD matrix Audit trail: Owner Full, Administrator Limited/No, Cashier No. TASK-004 default: Owner only.

---

## Dependencies

- TASK-005 (rows exist)
- TASK-008, TASK-016 (settings and stock events)
- TASK-003 (user events)

---

## Files likely affected

- `app/Livewire/Audit/ActivityLogIndex.php`
- `app/Policies/ActivityLogPolicy.php`
- `resources/views/livewire/audit/`
- `routes/web.php`
- `tests/Feature/Audit/`

---

## Database changes

None.

---

## Backend requirements

1. Paginated logs, newest first.
2. Filters: action, date range, actor.
3. Eager-load `user`.
4. Display old/new/context as readable JSON or key-value, never passwords.
5. `view` per TASK-004; `update`/`delete` always false.

---

## Frontend requirements

- Activity Log nav for Owner (and Admin only if you changed TASK-004 — default hide for Admin).
- Empty state.
- No row action except implicit view.

---

## Validation rules

Filter dates from <= to. Action in `ActivityAction` or empty.

---

## Authorization rules

- Owner: view.
- Administrator: default **No**.
- Cashier: No.
- IDOR: unauthorized 403 even with known id (index-only is enough if no show route).

---

## Business rules

- AUD-06, AUD-07, AUD-08.
- Sales not listed as generic events.

---

## Edge cases

- Actor user deactivated: still show name if stored on users row.
- Large JSON: truncate in UI with expand.

---

## Security considerations

- Do not render JSON as HTML without escaping.
- Do not expose other users’ emails beyond what audit stored.

---

## Testing requirements

- Owner sees a stock adjustment log and a role-change log.
- Cashier 403.
- Administrator 403 under default gate.
- No delete endpoint.

---

## Acceptance criteria

- [ ] AUD-08 view restricted.
- [ ] AUD-06/07 no cashier edit/delete.

---

## Definition of Done

- [ ] Read-only audit UI.
- [ ] Tests pass.
