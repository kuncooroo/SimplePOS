# TASK-026 — Installer Database and Environment

**Task ID:** TASK-026  
**Title:** Installer database and environment  
**Filename:** `TASK-026-installer-database-and-environment.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/INSTALLER.md`.

---

## Objective

Collect database credentials, verify the connection, write `.env` from `.env.example`, set production-safe defaults, and generate `APP_KEY` if missing. Do not run migrations or create users yet.

---

## Background

`docs/INSTALLER.md` §4 steps 5–7, §6.1–6.2. Store name and receipt footer stay out of `.env` (SRS §35.4) — those are TASK-027.

Wizard defaults: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, `FILESYSTEM_DISK=local`. No Redis. No public API keys.

---

## Dependencies

- TASK-025 (user reached this step only after preflight)
- TASK-001 (`.env.example` exists)

---

## Files likely affected

- `app/Actions/Install/TestDatabaseConnection.php`
- `app/Actions/Install/WriteEnvironmentFile.php`
- `app/Actions/Install/GenerateApplicationKey.php`
- `app/Http/Controllers/Install/DatabaseController.php`
- `app/Http/Controllers/Install/EnvironmentController.php`
- `app/Http/Requests/Install/DatabaseConnectionRequest.php`
- `app/Http/Requests/Install/EnvironmentRequest.php`
- `resources/views/install/database.blade.php`
- `resources/views/install/environment.blade.php`
- `tests/Feature/Install/DatabaseAndEnvironmentTest.php`

Use a **temp directory** in tests; never overwrite the developer’s real `.env` in CI. Inject the env path via the Action.

---

## Database changes

None (connection test only).

---

## Backend requirements

1. **Database form:** host, port (default 3306), database, username, password. `DB_CONNECTION=mysql` only.
2. `TestDatabaseConnection`: PDO/`SELECT 1`; on failure stay on the form with a **generic** error. Do not log the password. Do not dump SQLSTATE to the public page in a way that includes credentials.
3. **Environment form:** `APP_URL`, timezone (e.g. `Asia/Jakarta`), `APP_ENV` (default production), `APP_DEBUG` (default false, discourage true).
4. `WriteEnvironmentFile`: copy from `.env.example` if `.env` missing; set only allowed keys (`docs/INSTALLER.md` §6.1). Do **not** set `APP_INSTALLED=true` yet. `chmod` 0600 when the OS allows.
5. Retry-safe: user can correct DB credentials and rewrite `.env`.
6. `GenerateApplicationKey`: run key generation **only if** `APP_KEY` is empty; `--force` equivalent in-process. Do not render the full key in HTML.
7. After key write, reload config for the rest of the request/session as Laravel requires (`Artisan::call` + `config:clear` only if needed; do not `optimize` in the wizard).
8. Never write store identity into `.env`.
9. Never enable Redis or a queue worker.

---

## Frontend requirements

- Database step: password input `autocomplete="new-password"` or `off`; no password echo on validation error (re-prompt).
- Environment step: helper text that production should keep debug off.
- Success of DB test shown before leaving the step (same request or explicit “Test connection”).

---

## Validation rules

| Field | Rules |
|---|---|
| db host | required, string, max 255 |
| db port | required, integer, 1–65535 |
| database | required, string, max 64 |
| username | required, string |
| password | nullable string (empty allowed for local socket users) |
| APP_URL | required, URL |
| timezone | required, valid PHP timezone |
| APP_DEBUG | boolean; default false |

---

## Authorization rules

Unauthenticated installer; throttle POSTs. Refuse these controllers if wizard session has not passed TASK-025 (redirect back to requirements).

---

## Business rules

Partial `.env` without a successful DB test must not be treated as installed.

---

## Edge cases

- Wrong password: stay on step 5; `APP_INSTALLED` remains unset.
- `.env` not writable: fail with a permission message; do not claim success.
- Existing `APP_KEY`: do not rotate.
- MySQL 8.0 vs documented 8.4: connection success is enough; do not fail on server version.

---

## Security considerations

- Password never in logs, flash, or HTML value attribute after submit.
- `.env` is outside `public/`; never return file contents in a response.
- `APP_DEBUG=false` default for the wizard.
- No `migrate:fresh` here or later from the web installer.

---

## Testing requirements

- Successful PDO mock/sqlite substitute **or** skip-if-no-mysql: Action returns ok; env writer writes `DB_*` without `APP_INSTALLED`.
- Failed connection: env not marked installed; password absent from `Log::fake()`.
- Key generated only when empty; HTML response does not contain `base64:` key material.
- Test uses an isolated env file path.

---

## Acceptance criteria

- [ ] DB test + `.env` write + key generation match `docs/INSTALLER.md` §6.1–6.2.
- [ ] Production defaults: file session, sync queue, file cache, debug off.
- [ ] Store settings keys are not written to `.env`.
- [ ] Migrations and Owner creation are not in this task.

---

## Definition of Done

- [ ] Actions are injectable and testable with a temp env file.
- [ ] Tests above pass.
- [ ] Diff contains no real credentials or a committed `.env`.
