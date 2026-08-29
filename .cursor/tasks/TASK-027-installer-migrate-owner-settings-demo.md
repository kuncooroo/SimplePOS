# TASK-027 — Installer Migrate, Owner, Settings, Demo

**Task ID:** TASK-027  
**Title:** Installer migrate, Owner, settings, and optional demo  
**Filename:** `TASK-027-installer-migrate-owner-settings-demo.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/INSTALLER.md`.

---

## Objective

Run database migrations, create the single protected Owner, write basic `store_settings`, optionally seed demo data, and `storage:link` if missing. Do not write the installer lock yet (TASK-028).

---

## Background

`docs/INSTALLER.md` §4 steps 8–11, §6.3–6.6. The prompt’s “initial administrator” is the **protected Owner** (`UserRole::Owner`), not `ADMINISTRATOR` (PRD §11).

Web installer **must not** call `migrate:fresh`, `migrate:refresh`, or `db:wipe`.

---

## Dependencies

- TASK-026 (valid `.env`, `APP_KEY`, DB connection)
- TASK-001 / TASK-004 (`users.role`, `UserRole::Owner`)
- TASK-008 (`store_settings` table and model)
- For demo products: TASK-006 and TASK-007 (skip demo catalog if those tables are absent — fail closed: do not half-seed)
- Optional: TASK-023 `DemoSeeder` — reuse it; do not duplicate a second demo world

---

## Files likely affected

- `app/Actions/Install/RunMigrations.php`
- `app/Actions/Install/CreateOwner.php`
- `app/Actions/Install/WriteInitialStoreSettings.php`
- `app/Actions/Install/SeedDemoData.php`
- `app/Http/Controllers/Install/MigrateController.php`
- `app/Http/Controllers/Install/OwnerController.php`
- `app/Http/Controllers/Install/SettingsController.php`
- `app/Http/Controllers/Install/DemoDataController.php`
- `app/Http/Requests/Install/CreateOwnerRequest.php`
- `app/Http/Requests/Install/InitialSettingsRequest.php`
- `resources/views/install/migrate.blade.php`
- `resources/views/install/owner.blade.php`
- `resources/views/install/settings.blade.php`
- `resources/views/install/demo.blade.php`
- `tests/Feature/Install/MigrateOwnerSettingsDemoTest.php`

---

## Database changes

None new. Use existing migrations only (`artisan migrate --force`).

---

## Backend requirements

1. **Migrations:** `migrate --force` only. Idempotent retry. On failure: generic UI error; log internally without DB password.
2. **Owner:** name, email, password, confirmation. Role **must** be `OWNER`, `active=true`, hashed password. If any Owner already exists: abort create; do not create a second Owner. Unique email.
3. **No default password** in source (`admin`/`admin` forbidden).
4. **Settings:** insert/update the single `store_settings` row: store name, currency IDR / Rp, optional address; `low_stock_threshold` default from DATABASE.md. Skip logo upload (TASK-008 UI).
5. **Demo:** checkbox, **default off**. If on: run the existing demo seeder path; never overwrite the Owner from step 9; skip + warn if completed `transactions` already exist.
6. **storage:link** if the public storage link is missing.
7. Do not set `APP_INSTALLED` or write `storage/app/installed` here.
8. Wizard session: these steps only if previous installer steps completed.

---

## Frontend requirements

- Migrate: one primary “Run migrations” (or auto-run with a progress/result message — still a POST).
- Owner form: password confirmation; no password in page source after error beyond the input the user retypes.
- Settings: store name required; currency shown as IDR default, not a world CMS.
- Demo: labeled “sample products, not production”; default unchecked.
- Operational install layout from TASK-024.

---

## Validation rules

| Field | Rules |
|---|---|
| Owner name | required, max 150 |
| Owner email | required, email, unique:users |
| Owner password | required, confirmed, Laravel default password rules |
| store_name | required, max 200 |
| address | nullable string |
| demo | boolean, default false |

---

## Authorization rules

Unauthenticated installer. Creating Owner does not log the user in until TASK-028 finish (optional: do not `Auth::login` here to avoid a session that outlives a failed lock).

---

## Business rules

- Exactly one Owner from the installer.
- Administrator is a later in-app user, not the bootstrap account.
- Demo must not run unless requested.

---

## Edge cases

- Migrations already applied: continue.
- Owner unique clash from a previous partial attempt: if that user is Owner, skip create and continue (do not reset their password from the form unless you explicitly document a one-time reset — **prefer skip, do not reset**).
- Demo requested but catalog migrations missing: refuse demo, do not abort Owner/settings.

---

## Security considerations

- Passwords hashed; not stored in session after the request.
- No `migrate:fresh` anywhere in installer Actions or controllers (assert in tests).
- Do not seed demo passwords into git.

---

## Testing requirements

- Happy path: migrate → one Owner → settings row → demo off → no lock file yet.
- Second Owner via installer rejected.
- Demo seeder does not run unless requested.
- Installer code paths do not call `migrate:fresh` (search/spy).
- Owner password is hashed (not equal to plaintext in DB).

---

## Acceptance criteria

- [ ] `migrate --force` only; never fresh/wipe from the web installer.
- [ ] First user is protected Owner.
- [ ] Single `store_settings` row with IDR defaults.
- [ ] Demo optional, default off, does not overwrite Owner.
- [ ] Lock / `APP_INSTALLED` still unset.

---

## Definition of Done

- [ ] Matches `docs/INSTALLER.md` §6.3–6.6.
- [ ] Tests above pass.
- [ ] Diff contains no credentials or demo production passwords.
