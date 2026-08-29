# TASK-022 — User Profile

**Task ID:** TASK-022  
**Title:** User profile  
**Filename:** `TASK-022-user-profile.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let an authenticated user view their profile (name, login, role) and update their own name and password. They must not change their own role or other users’ accounts here.

---

## Background

UI_UX.md §27. Role management belongs in TASK-003 only.

---

## Dependencies

- TASK-002, TASK-003 (User model)

---

## Files likely affected

- `app/Livewire/Identity/ProfileForm.php`
- `resources/views/livewire/identity/`
- `routes/web.php`
- `tests/Feature/Identity/`

---

## Database changes

None.

---

## Backend requirements

1. Show name, email, role (read-only role).
2. Update own name; optional password change with current password confirmation.
3. Email change: **omit** unless you also add unique re-validation; **recommended MVP: email read-only** on profile to avoid account-takeover complexity.
4. Authorize: user may only update `auth()->id()`.

---

## Frontend requirements

- Profile in user menu (`UI_UX.md` §4, §27).
- All roles including Cashier.
- Success/error toasts.

---

## Validation rules

| Field | Rules |
|---|---|
| name | required, max 150 |
| current_password | required if new password present, must match |
| password | optional, min length, confirmed |

---

## Authorization rules

- Any authenticated active user.
- Cannot set `role` or `active`.
- Cannot open another user’s profile by id.

---

## Business rules

- No self-escalation.
- Password never displayed.

---

## Edge cases

- Wrong current password.
- Cashier can use profile without seeing Users module.

---

## Security considerations

- Hash new password.
- Session regenerate after password change (Laravel best practice).
- Mass assignment protection.

---

## Testing requirements

- User updates name.
- Password change with correct current password; old password fails login.
- Cannot pass `role` to become Owner.
- Cashier allowed.

---

## Acceptance criteria

- [ ] Profile view/update without role change.
- [ ] All authenticated roles can open their own profile.

---

## Definition of Done

- [ ] Profile complete.
- [ ] Tests pass.
- [ ] No extra account-recovery features.
