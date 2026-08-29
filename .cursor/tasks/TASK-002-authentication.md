# TASK-002 — Authentication

**Task ID:** TASK-002  
**Title:** Authentication  
**Filename:** `TASK-002-authentication.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Allow an active user to sign in with email and password, establish a session, reach a role-appropriate landing page, and sign out. Reject invalid credentials and inactive accounts without leaking whether the email exists.

---

## Background

MVP-01, FR-AUTH-01–05, SFR-AUTH-01–04, BUSINESS_FLOW §4–5, PRD AC-01/AC-02, SEC-01/SEC-04/SEC-05/SEC-11.

Authentication is session-based. Password reset, email verification, and social login are **not** required.

---

## Dependencies

- TASK-001 (User model, layouts, Owner seeder)

---

## Files likely affected

- `app/Http/Controllers/Auth/LoginController.php` (or equivalent Laravel auth scaffolding)
- `app/Http/Requests/Auth/LoginRequest.php`
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/guest.blade.php`
- `routes/web.php`
- `bootstrap/app.php` (auth middleware aliases, login throttling)
- `app/Http/Middleware/` if a dedicated `EnsureUserIsActive` check is cleaner than inline login logic
- `tests/Feature/Auth/`

Do not add Fortify/Breeze/Jetstream packages unless the official Laravel 13 starter already includes one and it stays within MVP (no extra features enabled).

---

## Database changes

None. Use existing `users`.

---

## Backend requirements

1. `GET` login (guest), `POST` login, `POST` logout (auth). Named routes.
2. Login: look up email, verify password, require `active === true`, regenerate session, redirect.
3. Inactive or wrong password: same generic failure, no session.
4. Logout: invalidate session and regenerate CSRF token.
5. Protected routes use `auth` middleware.
6. Throttle login attempts (SRS §31.11).
7. After login:
   - Cashier → POS if the POS route exists, otherwise a safe authenticated placeholder.
   - Owner / Administrator → dashboard if it exists, otherwise authenticated placeholder.
   Until POS/dashboard exist, a simple authenticated home is acceptable; do not invent metrics.
8. Authenticated users visiting login redirect away from the login form.

---

## Frontend requirements

- Centered login panel: product name, email, password, Sign in (`UI_UX.md` §28).
- No social buttons, testimonials, or marketing heroes.
- Generic error: “Unable to sign in with those credentials.”
- Show validation errors on empty fields without revealing account existence.
- Logout control in the app top-bar user menu (can be a stub menu).
- Keyboard-accessible form, labeled fields.

---

## Validation rules

| Field | Rules |
|---|---|
| email | required, email format |
| password | required, string |

Do not apply “exists:users,email” in a way that changes the generic error message.

---

## Authorization rules

- Guests cannot access authenticated routes (SEC-01).
- Authentication success does not grant every module; module policies arrive in TASK-004+. Until then, authenticated home is enough.
- Do not trust hidden form fields for identity.

---

## Business rules

- Only **active** users authenticate (BR implicit / FR-AUTH-03).
- Deactivated users with valid passwords still fail (same generic message).
- Passwords never stored or displayed in plaintext (SEC-05).
- Session regeneration on login (SYSTEM_DESIGN §8).

---

## Edge cases

- Trim/normalize email consistently (case of email: follow Laravel default; do not invent a second identifier).
- User deactivated **after** login: next request should not keep privileged access — prefer checking `active` on authenticated requests (middleware) so a deactivated cashier is logged out or forbidden.
- Concurrent logout + back button does not restore a valid session.
- Remember-me: omit unless scaffolding enables it by default; if present, it must still honor `active`.

---

## Security considerations

- Generic login errors (no “email not found” vs “wrong password”).
- CSRF on login/logout.
- Rate-limit login.
- Do not log passwords.
- `password` hidden on the model.
- Do not expose stack traces to the browser in the documented production config.

---

## Testing requirements

Feature tests (`CURSOR.md` §36):

- active user with correct password authenticates;
- wrong password denied, no session;
- unknown email denied, generic message;
- inactive user denied even with correct password;
- logout invalidates session;
- guest is redirected from an authenticated route;
- login is throttled after repeated failures (assert without brittle timing if needed).

---

## Acceptance criteria

- [ ] AC-01: active valid user reaches authenticated area permitted as a logged-in user.
- [ ] AC-02: invalid credentials create no session.
- [ ] Inactive user cannot authenticate.
- [ ] Logout works.
- [ ] Login error copy does not disclose account existence.
- [ ] Login is throttled.

---

## Definition of Done

- [ ] MVP-01 behavior observable.
- [ ] Tests above pass.
- [ ] Middleware protects non-guest routes added in this task.
- [ ] No password-reset/email/social features added.
- [ ] No credentials in git.
- [ ] Labels consistent with UI_UX login spec.
