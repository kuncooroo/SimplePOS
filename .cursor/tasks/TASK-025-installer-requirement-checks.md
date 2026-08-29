# TASK-025 — Installer Requirement Checks

**Task ID:** TASK-025  
**Title:** Installer requirement checks  
**Filename:** `TASK-025-installer-requirement-checks.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/INSTALLER.md`.

---

## Objective

Implement wizard steps that verify system requirements, required PHP extensions, and writable folders. The user cannot continue to database setup until every required check passes.

---

## Background

`docs/INSTALLER.md` §4 steps 2–4, §5. These checks run in PHP; they do not install extensions or chmod the server.

PHP version must follow **`composer.json` `require.php`**, not a hardcoded 8.4 if the lockfile allows `^8.3`. Record the actual runtime. Do not fail solely because PHP is 8.3 when the framework allows it.

---

## Dependencies

- TASK-024 (wizard shell, `install.*` routes, session step helper)

---

## Files likely affected

- `app/Support/Install/RequirementChecker.php`
- `app/Support/Install/ExtensionChecker.php`
- `app/Support/Install/PermissionChecker.php`
- `app/Http/Controllers/Install/RequirementsController.php`
- `app/Http/Controllers/Install/ExtensionsController.php`
- `app/Http/Controllers/Install/PermissionsController.php`
- `resources/views/install/requirements.blade.php`
- `resources/views/install/extensions.blade.php`
- `resources/views/install/permissions.blade.php`
- `tests/Feature/Install/RequirementChecksTest.php`

Controllers may be one class with three actions if thinner; checkers stay separate and unit-testable.

---

## Database changes

None.

---

## Backend requirements

1. **System:** PHP version vs `composer.json`; warn if the web root is not `public/` (best-effort: `public_path()` vs `base_path()`); optional disk-free warning under 100MB in `storage/` (warn, do not hard-fail unless you can measure reliably on Windows/Linux).
2. **Extensions (required):** `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `json` (`docs/INSTALLER.md` §5.2). Optional warn: `gd` / `exif`.
3. **Permissions:** PHP user can write `bootstrap/cache`, `storage`, `storage/app`, `storage/app/public`, `storage/framework`, `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/views`, `storage/logs`.
4. Continue is allowed **only** when all required checks pass. Failed required checks keep the user on the step.
5. Do not mutate the filesystem except perhaps creating missing empty `storage/*` subdirs if Laravel expects them and they are missing — prefer reporting, not silently chmod.
6. Advance wizard session step only after pass.
7. Do not write `.env` or connect to MySQL in this task.

---

## Frontend requirements

- One screen per group (system / extensions / permissions) or a single “preflight” page with three sections — either is fine if Continue is blocked until required items pass.
- Pass / fail (and warn) per row; no raw exception dumps.
- Primary Continue disabled or server-rejected when required checks fail.
- Operational copy; Windows-friendly permission messages (not only `chmod 775` Linux paste).

---

## Validation rules

N/A beyond server-side “all required passed.”

---

## Authorization rules

Same as TASK-024: unauthenticated, throttled POSTs.

---

## Business rules

Buyer must satisfy the runtime before any secret (DB password) is collected.

---

## Edge cases

- `pdo_mysql` missing: fail extensions; do not attempt a DB ping here.
- Read-only `storage/logs`: fail permissions even if other dirs work.
- PHP 8.3 vs documented 8.4 target: pass if `composer.json` allows it; do not invent a 8.4-only fail.

---

## Security considerations

- Do not print `phpinfo()`.
- Do not list directory contents of the project root beyond the required paths.
- Do not echo environment variables.

---

## Testing requirements

- Checker unit tests: missing extension → fail; all present → pass (mock `extension_loaded` via a thin wrapper if needed).
- Unwritable path → cannot continue (feature test with a temp dir or mock).
- Happy path: all pass → next installer route is reachable in session.

---

## Acceptance criteria

- [ ] System, extension, and permission checks match `docs/INSTALLER.md` §5.
- [ ] Required failures block Continue.
- [ ] Optional `gd`/`exif` are warnings only.
- [ ] PHP constraint comes from `composer.json`, not a guessed version.
- [ ] No `.env` / migrate / lock changes.

---

## Definition of Done

- [ ] Checks are testable without a real VPS.
- [ ] UI shows pass/fail clearly.
- [ ] Tests above pass.
- [ ] Diff contains no credentials.
