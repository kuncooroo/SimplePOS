# TASK-024 — Installer Shell and Gating

**Task ID:** TASK-024  
**Title:** Installer shell and gating  
**Filename:** `TASK-024-installer-shell-and-gating.md`

Cursor: implement only this task. Follow `CURSOR.md`, `docs/PROJECT_STRUCTURE.md`, and `docs/INSTALLER.md`.

---

## Objective

Add the unauthenticated `/install` wizard shell, wizard session step tracking, and request gating so the rest of the app redirects to the installer when the product is not installed, and installer routes are not yet locked (lock lands in TASK-028).

---

## Background

Commercial source-code distribution (`docs/INSTALLER.md`). Buyers point the vhost at `public/` and complete a browser wizard. There is no SaaS tenant onboarding.

This task creates the **frame** only: routes, guest install layout, welcome step, middleware aliases, CSRF, and throttle. Requirement checks, `.env` writes, migrations, Owner creation, and the lock file are later tasks.

---

## Dependencies

- TASK-001 (Laravel app, guest layout primitives, `User` model)
- Read `docs/INSTALLER.md` in full before coding

Technical note: later installer steps need TASK-002 (login redirect on finish), TASK-004 (`OWNER` role), and TASK-008 (`store_settings`). Do not implement those here.

---

## Files likely affected

- `routes/install.php`
- `bootstrap/app.php` (load installer routes, middleware aliases)
- `app/Http/Middleware/EnsureApplicationIsInstalled.php` (or split per `docs/INSTALLER.md` §9)
- `app/Http/Middleware/RedirectIfNotInstalled.php`
- `app/Http/Controllers/Install/WelcomeController.php` (or a single `InstallController` with `welcome`)
- `app/Support/Install/InstallState.php` (session step index; installed? detection stub)
- `resources/views/layouts/install.blade.php`
- `resources/views/install/welcome.blade.php`
- `tests/Feature/Install/InstallerGatingTest.php`

Prefer classic controllers + Blade over Livewire for the wizard (`docs/INSTALLER.md` §9).

---

## Database changes

None.

---

## Backend requirements

1. Load installer routes on the `web` middleware group. Named routes `install.*`. Prefix `/install`.
2. **Not installed** (no lock file and no Owner row — Owner check may be a no-op until users exist; treat missing table as not installed, do not 500):
   - `GET /install` renders welcome.
   - Authenticated product routes (and `/` if it is the app home) redirect to `install.welcome`.
   - Static assets under `public/build` and Vite must still load.
3. **Do not** implement the post-install 404 lock in this task beyond a helper `InstallState::isInstalled(): bool` that returns false until TASK-028 writes the lock. Document the TODO in the helper.
4. CSRF on all installer POSTs (framework default). Do not disable it.
5. Throttle installer POSTs (e.g. `throttle:10,1` per IP). Welcome GET may be unthrottled or lightly throttled.
6. Wizard progress lives in **session only**. Do not authorize later steps with a public cookie.
7. Do not run Composer, npm, migrate, or `key:generate` in this task.

---

## Frontend requirements

1. Install layout: guest/operational, no marketing chrome, no app sidebar (`UI_UX.md` login spirit).
2. Welcome step: product name, that this is a one-time setup, Continue button to the next route (next step may 404 until TASK-025 — if so, only wire Continue to a placeholder `install.requirements` route that returns 501 or a “coming in next task” view **or** leave the button linking to a named route stub). Prefer a stub view “Requirements — not implemented” rather than a dead `#`.
3. No link to `/install` in the future app sidebar (nothing to add if sidebar has no install item).
4. Accessible heading, one primary action.

---

## Validation rules

N/A (welcome has no form fields).

---

## Authorization rules

Installer is unauthenticated by design until TASK-028 locks it. Do not require `auth` on `/install`.

---

## Business rules

- Single-store, single install (`docs/INSTALLER.md` §1).
- First privileged user is created later as **Owner**, not ADMINISTRATOR.

---

## Edge cases

- Missing `users` table (fresh clone before migrate): gating must not throw; treat as not installed.
- Hitting `/login` before install: redirect to `/install` (login may not exist yet — if TASK-002 is done, redirect; if not, only `/` and named app routes that exist).

---

## Security considerations

- Do not expose phpinfo, `.env`, or stack traces on the welcome page.
- Do not add a reinstall token or query-string bypass.
- Docroot remains `public/` only; do not serve the project root.

---

## Testing requirements

- `GET /install` returns 200 and the welcome view when not installed.
- CSRF token is present in the welcome form if Continue is POST; if Continue is GET to the next step, still assert the layout does not leak secrets.
- Installer POST (even a no-op stub) is throttled / has throttle middleware attached (assert middleware or hit the limit in a feature test).
- A product route that exists redirects to `/install` when not installed.

---

## Acceptance criteria

- [ ] `/install` welcome renders on a not-installed app.
- [ ] App home/login (if present) redirects to the installer when not installed.
- [ ] Installer routes are named `install.*`, prefixed `/install`, under `web` + CSRF + throttle on POST.
- [ ] `InstallState::isInstalled()` exists as the single place later tasks will flip.
- [ ] No migrations, `.env` writes, or lock file in this task.

---

## Definition of Done

- [ ] Matches `docs/INSTALLER.md` §3.1, §4 welcome, §9 placement.
- [ ] No unrelated product features.
- [ ] Tests above pass.
- [ ] Diff contains no credentials.
