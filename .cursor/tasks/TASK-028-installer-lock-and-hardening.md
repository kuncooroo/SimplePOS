# TASK-028 — Installer Lock and Hardening

**Task ID:** TASK-028  
**Title:** Installer lock, completion, Artisan, and post-install hardening  
**Filename:** `TASK-028-installer-lock-and-hardening.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/INSTALLER.md`. **Security-critical.**

---

## Objective

Finish the wizard: write the installer lock, set `APP_INSTALLED=true`, invalidate the installer session, redirect to login, make `/install*` return **HTTP 404** after install, add `php artisan simplepos:install`, and prove the installer is not exploitable afterward.

---

## Background

`docs/INSTALLER.md` §4 step 12, §7, §8, §11. After a commercial install, the unauthenticated wizard is an attack surface. It must be **dead** (404), not “already installed” HTML, and not a hidden route that still writes `.env`.

Lock is **dual**: `storage/app/installed` **and** `APP_INSTALLED=true`. Middleware also treats an existing **Owner** row as installed so deleting only the lock file is not enough.

There is **no** web uninstall. There is **no** `simplepos:uninstall` route.

---

## Dependencies

- TASK-027 (Owner + settings exist on happy path)
- TASK-002 (named `login` route for the finish redirect)
- TASK-024 (`InstallState`, installer routes)

---

## Files likely affected

- `app/Actions/Install/WriteInstallLock.php`
- `app/Support/Install/InstallState.php` (complete `isInstalled()`)
- `app/Http/Middleware/PreventAccessToInstaller.php` (404 when installed)
- `app/Http/Middleware/RedirectIfNotInstalled.php` (already from TASK-024; stop redirecting to installer when installed)
- `app/Http/Controllers/Install/CompleteController.php`
- `app/Console/Commands/SimplePosInstallCommand.php`
- `resources/views/install/complete.blade.php` (optional flash-then-redirect; prefer redirect-only)
- `bootstrap/app.php` (schedule middleware on **all** `install.*` plus web group)
- `tests/Feature/Install/InstallerLockTest.php`
- `tests/Feature/Install/SimplePosInstallCommandTest.php`

Unregister or no-op env-writer / migrate Actions when `isInstalled()` is true even if a tester bypasses middleware (defense in depth).

---

## Database changes

None.

---

## Backend requirements

1. **Finish POST:** write `storage/app/installed` (not under `public/`) with ISO-8601 timestamp and a **hash of `APP_KEY`** (not the key). Mode 0640 if possible. Set `APP_INSTALLED=true` in `.env`. Invalidate installer session. Redirect `route('login')`.
2. **`InstallState::isInstalled()`:** true if lock file exists **OR** `APP_INSTALLED=true` **OR** an Owner user exists. Missing `users` table → false (not installed).
3. **When installed:** any `GET`/`POST`/`PUT`/`PATCH`/`DELETE` under `/install` returns **404** with generic/empty body — **not** installer HTML, **not** 403 “Already installed”.
4. Named `install.*` URLs must not appear in the app layout.
5. Artisan `simplepos:install` runs the same business steps (or delegates to the same Actions). `--no-interaction`, `--demo`. **Refuse** if already installed (lock or Owner). Optional `simplepos:install --lock-only` for recovery when Owner exists but lock write failed (`docs/INSTALLER.md` §10).
6. No `simplepos:uninstall` web or HTTP API.
7. Rate limits remain on installer POSTs for the not-installed path.
8. Application updates use SSH `php artisan migrate`, never `/install`.

---

## Frontend requirements

- Completion: redirect to login; optional one-time success flash on login (“Installation complete. Sign in as Owner.”).
- No “reinstall” or “open installer” control in settings.

---

## Validation rules

Finish is allowed only if TASK-027 completed in this wizard session **or** Owner + settings already exist (retry after lock-write failure).

---

## Authorization rules

After lock, installer is unreachable. Login is normal `auth` guest.

---

## Business rules

- One successful commercial install per deployment.
- Recovery that deletes Owner **and** lock is an SSH ops procedure, not a product screen (`docs/INSTALLER.md` §7.3).

---

## Edge cases

- Delete `storage/app/installed` only: still 404 because Owner exists.
- Delete Owner only, leave lock: still 404.
- Lock write fails after Owner created: `simplepos:install --lock-only` over SSH; middleware still treats Owner as installed (installer already 404).
- CSRF token from a new session posted to `/install/*` after lock: still 404 (middleware before controller).

---

## Security considerations (normative)

1. After lock, installer HTTP surface is a missing page (404).
2. Env writer, migrate, Owner create, demo seed **must not run** when installed (middleware + Action guard).
3. Never `migrate:fresh` / `db:wipe` from installer.
4. Do not log passwords or `APP_KEY`.
5. Do not ship a reinstall token in `.env.example`.
6. Do not return `.env` contents.
7. `APP_DEBUG` stays false for production installs from the wizard.

---

## Testing requirements (P0)

- Full happy path (sqlite or mysql, temp env): checks may be stubbed if prior tasks’ checkers are slow; **must** include Owner → lock → login works.
- After lock, `GET /install` is 404.
- After lock, `POST /install/anything` is 404 (including with a valid CSRF token from a new session).
- Second Owner via installer rejected (if a request could reach the controller, Action still refuses).
- Deleting only the lock file in the test still 404s while Owner exists.
- `migrate:fresh` not invoked by installer code.
- Demo does not run unless requested (regression from TASK-027).
- DB password not present in `Log::fake()`.
- `php artisan simplepos:install` refuses when installed.

P1 if time:

- Extension/permission failures still block (TASK-025).
- Invalid DB does not set installed (TASK-026).

---

## Acceptance criteria

- [ ] Lock file under `storage/app/installed` + `APP_INSTALLED=true` + session invalidation.
- [ ] Installed ⇒ `/install*` is 404, not an installer UI.
- [ ] Owner presence is sufficient to treat the app as installed.
- [ ] Artisan installer refuses when locked/Owner exists.
- [ ] Login works for the Owner created in TASK-027.
- [ ] P0 tests in `docs/INSTALLER.md` §11 pass.

---

## Definition of Done

- [ ] Matches `docs/INSTALLER.md` §7–§8 and §11.
- [ ] Installer is not exploitable after installation.
- [ ] Tests above pass.
- [ ] Diff contains no credentials, no reinstall backdoor, no committed `.env`.
