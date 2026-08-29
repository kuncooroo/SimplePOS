# Installer Design

## SimplePOS — Commercial source-code product

| Field | Value |
|---|---|
| Document | `docs/INSTALLER.md` |
| Date | 2026-08-28 |
| Audience | Product engineers packaging SimplePOS for VPS customers |
| Product | Single-store Laravel POS (not SaaS, not multi-tenant) |

This installer is for **buyers who receive the Laravel source** and deploy it on their own VPS. It is not a SaaS onboarding flow and does not create tenants.

The first privileged account is the **protected Owner** (`UserRole::Owner`), not an `ADMINISTRATOR`. That matches PRD §11 and DATABASE.md. The prompt’s “initial administrator” maps to this Owner.

---

# 1. Goals

1. A non-developer can finish a first install from a browser after uploading the project and pointing the vhost at `public/`.
2. A sysadmin can run the same steps over SSH (`php artisan simplepos:install`).
3. After success, the installer **cannot be reused to takeover the store** (rewrite `.env`, drop tables, or create a second Owner).
4. No extra packages if Laravel already covers the step (`key:generate`, `migrate`, filesystem, hash).

Out of scope:

- Composer/npm inside the web wizard (document as pre-requisite).
- Multi-tenant / license servers / phone-home.
- `migrate:fresh` / `db:wipe` from the web installer.
- Re-install “repair mode” that bypasses the lock without SSH.

---

# 2. Preconditions (before `/install`)

The deployer must already have:

```text
PHP compatible with composer.json (target Laravel 13 / PHP 8.3+)
Composer install --no-dev   (production)
npm ci && npm run build     (or ship built public/build)
MySQL database created, empty, utf8mb4
Web root = /path/to/simplepos/public
storage/ and bootstrap/cache/ exist
```

The wizard does **not** run `composer` or `npm`. It only **checks** that the runtime is usable.

---

# 3. Channels

## 3.1 Web wizard

Base path: `/install`  
Layout: guest, operational, no marketing chrome (`UI_UX.md` login spirit).

Only reachable when **not installed** (see §7). CSRF on every POST. Throttle all installer POSTs.

## 3.2 Artisan

```text
php artisan simplepos:install
php artisan simplepos:install --no-interaction
php artisan simplepos:install --demo
```

Same business steps and the same lock rules as the web wizard. Interactive prompts for DB and Owner when flags are omitted.

`simplepos:install` **must refuse** if the lock is present or an Owner already exists.

There is **no** `simplepos:uninstall` web route. Removing the lock is an SSH/filesystem operation and still blocked if an Owner row exists (see §7.3).

---

# 4. Wizard steps

One GET page per step (or a single Livewire wizard with server-side step index). Persist step progress in **session only**, never in a public cookie that authorizes later steps without re-validation.

| Step | Name | User action | Server action |
|---|---|---|---|
| 1 | Welcome | Continue | — |
| 2 | System requirements | Continue if all pass | PHP version, OS memory hint, `public/` as docroot warning |
| 3 | PHP extensions | Continue if all required loaded | See §5 |
| 4 | Folder permissions | Continue if writable | `storage`, `storage/app`, `storage/framework`, `storage/logs`, `bootstrap/cache` |
| 5 | Database | Host, port, database, username, password | PDO connect; **do not** log password |
| 6 | Environment | App URL, timezone, `APP_ENV`, recommend `APP_DEBUG=false` | Write `.env` from `.env.example` + form; `chmod` 0600 where OS allows |
| 7 | Application key | Automatic (no user secret shown in full) | `artisan key:generate --force` only if key empty |
| 8 | Migrations | Run | `artisan migrate --force` (**never** fresh/wipe) |
| 9 | Owner account | Name, email, password, confirm | Create **one** `OWNER`, `active=true`; hash password |
| 10 | Store settings | Store name, currency IDR default, optional address | Insert/update the single `store_settings` row |
| 11 | Demo data | Checkbox, default **off** | Optional seeder; labeled “sample products, not production” |
| 12 | Finish | Go to login | Write installer lock; invalidate installer session; redirect `route('login')` |

Failed steps must not mark the app installed. Partial `.env` writes should be retry-safe (re-test connection).

If migrations already ran (retry after timeout), `migrate` is idempotent; Owner create must still be unique-email and **single Owner** (see §6.3).

---

# 5. Requirement checks

## 5.1 System

| Check | Fail if |
|---|---|
| PHP version | Below `composer.json` `require.php` |
| `public/index.php` is the front controller | Deployer pointed vhost at project root |
| `APP_RUNNING_IN_CONSOLE` not required | — |
| Disk free (optional warn) | < 100MB in `storage/` |

Do not fail solely because PHP is 8.3 when the locked framework allows `^8.3` (record actual versions).

## 5.2 PHP extensions (required)

Typical Laravel + MySQL + uploads:

```text
bcmath
ctype
curl
dom
fileinfo
filter
hash
mbstring
openssl
pcre
pdo
pdo_mysql
session
tokenizer
xml
json
```

Optional warn: `gd` / `exif` (not required if logo is stored as-is).

## 5.3 Permissions

Writable by the PHP user:

```text
bootstrap/cache
storage
storage/app
storage/app/public
storage/framework
storage/framework/cache
storage/framework/sessions
storage/framework/views
storage/logs
```

`.env` must be writable **during** install and should not be world-writable after. The web user writing `.env` is an accepted VPS trade-off; document that the file lives **outside** `public/`.

---

# 6. Configuration and data

## 6.1 Environment keys the installer may set

From SRS §35, plus installer flags:

```text
APP_NAME=SimplePOS
APP_ENV=production          # default for wizard; local leftover uses local
APP_DEBUG=false             # default for wizard
APP_URL=https://...
APP_KEY=                    # generated
APP_TIMEZONE=               # e.g. Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
APP_INSTALLED=true          # set only on successful finish
```

Never write Redis as default. Never enable a public API.

Do not put store name / receipt footer in `.env` (SRS §35.4). Those go to `store_settings`.

## 6.2 Application key

- Generate once if missing.
- Do not display the full key in the HTML response.
- After install, rotating the key is an ops task, not an installer step.

## 6.3 Owner creation

- Role **must** be `OWNER`.
- If any `OWNER` already exists, abort this step and treat the system as installed (lock if missing).
- Password: hashed with the framework hasher; min length as Laravel default; confirmed.
- This user is the **protected Owner** (Administrator cannot control them later).
- Do not create a default password in source (`admin`/`admin` is forbidden).

## 6.4 Store settings

Create the single settings row if missing: store name from the form, `currency_code=IDR`, `currency_symbol=Rp`, `low_stock_threshold` default from DATABASE.md.

Logo upload can wait for TASK-008 UI; installer may skip logo.

## 6.5 Demo data

Optional, default off.

If enabled, seed **non-Owner** users (Cashier/Admin with documented demo passwords), categories, products. Never overwrite the Owner created in step 9. Never seed on a database that already has completed `transactions` (skip + warn).

Demo passwords belong only in on-screen copy for that session, not in git.

## 6.6 Storage link

Run `php artisan storage:link` if missing, so a later logo can be public.

---

# 7. Installer lock and post-install security

The installer is **unauthenticated by design** before a user exists. After install it must be **dead**, not “hidden.”

## 7.1 Lock artifacts (all of them)

On successful finish:

1. File **`storage/app/installed`** (not under `public/`). Contents: ISO-8601 timestamp and a **hash** of `APP_KEY` (not the key). Mode 0640 if possible.
2. `.env` value **`APP_INSTALLED=true`**.
3. Session used for the wizard is **invalidated**.

## 7.2 Request gating

Register middleware globally on the `web` group (or in `bootstrap/app.php`):

| Condition | `/install*` | Rest of app |
|---|---|---|
| Not installed (no lock **and** no Owner) | Allowed (throttled) | Redirect to `/install` except assets |
| Installed (lock **or** Owner exists) | **HTTP 404** (empty/generic, not installer HTML) | Normal (login, etc.) |

Do **not** return 403 with “Already installed” copy that confirms a product and invites probing. 404 is enough.

Do **not** leave named routes `install.*` generating URLs in the app layout after lock (sidebar must not link `/install`).

## 7.3 Relock / bypass attempts

| Attack | Defense |
|---|---|
| Hit `/install` after success | 404 via middleware |
| Delete `storage/app/installed` only | Owner row still present → still 404 / artisan refuses |
| Delete Owner in DB but leave lock | Lock still 404s; recovery is SSH + documented support, not the wizard |
| Delete lock and Owner to “start over” | Allowed only if **both** gone **and** operator accepts empty DB; still no `migrate:fresh` from web. Document SSH procedure |
| POST `/install/migrate` with extra `_method` | Same middleware; no remaining controllers that wipe schema |
| Guess `/install/../.env` | Docroot is `public/` only |
| CSRF | VerifyCsrfToken on installer POSTs |
| Brute-force Owner create | Throttle + single Owner rule |
| Write `.env` after lock | Installer env writer **not registered** when installed (middleware never reaches controller) |

## 7.4 What not to do

- Do not keep a secret “reinstall token” in `.env.example`.
- Do not expose phpinfo, `.env`, or SQL errors to the wizard in production (`APP_DEBUG=false`).
- Do not log DB passwords or the generated `APP_KEY`.
- Do not ship with `APP_DEBUG=true` after the wizard defaults.

## 7.5 Updates vs install

Application **updates** (new migrations on an existing store) use `php artisan migrate` over SSH or a documented deploy script. They **must not** open `/install`.

---

# 8. Security requirements (normative)

1. After lock, installer HTTP surface is indistinguishable from a missing page (404).
2. Web installer never runs `migrate:fresh`, `migrate:refresh`, `db:wipe`, or `db:seed` of `DatabaseSeeder` that recreates users blindly.
3. `.env` is never served and never echoed back after save.
4. Passwords hashed; never stored in installer session in plaintext after the request.
5. Installer routes are not in `routes/api.php` (no API).
6. Rate-limit installer POSTs (e.g. 10/minute/IP).
7. Production wizard defaults `APP_DEBUG=false`.
8. Tests prove: after lock, `GET /install` and `POST /install/anything` are 404 even with a valid CSRF token from a new session.

---

# 9. Recommended code placement

Do not add `nwidart` modules or a paid installer package.

```text
app/Http/Middleware/RedirectIfNotInstalled.php
app/Http/Middleware/PreventAccessToInstaller.php
app/Http/Controllers/Install/...          # thin; one Action per write
app/Actions/Install/WriteEnvironmentFile.php
app/Actions/Install/TestDatabaseConnection.php
app/Actions/Install/RunMigrations.php
app/Actions/Install/CreateOwner.php
app/Actions/Install/WriteInstallLock.php
app/Support/Install/RequirementChecker.php
app/Console/Commands/SimplePosInstallCommand.php
resources/views/install/...
routes/install.php                        # required from bootstrap only when needed
tests/Feature/Install/
```

`routes/install.php` is loaded always, but **controllers short-circuit** through middleware 404 when installed. Do not dynamically `require` the file based on a world-writable flag in `public/`.

Livewire is optional; classic controllers + Blade are easier to reason about for a one-time wizard (less public-property surface). Prefer controllers.

---

# 10. Failure and recovery

| Failure | Recovery |
|---|---|
| DB credentials wrong | Stay on step 5; no lock |
| Migrate fails | Show generic error + log internally; retry migrate |
| Owner create unique clash | If the row is the Owner from a previous attempt, skip to lock if migrations OK |
| `.env` not writable | Fail step 6 with permission message |
| Lock write fails after Owner created | Artisan `simplepos:install --lock-only` (SSH) or middleware still treats Owner as installed |

Support documentation (not in-app): restore from backup; do not tell customers to delete `users` via phpMyAdmin as a first step.

---

# 11. Testing (must exist)

P0:

- Full happy path in Feature test (sqlite or mysql): checks → env in temp dir → migrate → Owner → lock → login works.
- After lock, `GET /install` is 404.
- After lock, `POST` installer URLs 404.
- Second Owner via installer rejected.
- `migrate:fresh` is not invoked by installer code (assert command list / test spy).
- Demo seeder does not run unless requested.
- DB password not present in `Log::` fake.

P1:

- Extension missing → cannot continue.
- Unwritable `storage` → cannot continue.
- Invalid DB → cannot continue; `.env` not marked installed.

---

# 12. Relationship to other docs

| Topic | Source |
|---|---|
| Owner vs Administrator | PRD §11, TASK-003/004 |
| Settings in DB not `.env` | SRS §35 |
| File sessions, sync queue | `docs/SECURITY_AUDIT.md`, `docs/PERFORMANCE_AUDIT.md` |
| Logo later | TASK-008 |
| Demo seeder | TASK-023 |
| No public API | ADR-10 |

Installer tasks (implement after MVP TASK-023):

| Task | File | Scope |
|---|---|---|
| TASK-024 | `.cursor/tasks/TASK-024-installer-shell-and-gating.md` | Wizard shell and gating |
| TASK-025 | `.cursor/tasks/TASK-025-installer-requirement-checks.md` | System, extensions, permissions |
| TASK-026 | `.cursor/tasks/TASK-026-installer-database-and-environment.md` | Database, `.env`, `APP_KEY` |
| TASK-027 | `.cursor/tasks/TASK-027-installer-migrate-owner-settings-demo.md` | Migrate, Owner, settings, demo |
| TASK-028 | `.cursor/tasks/TASK-028-installer-lock-and-hardening.md` | Lock, 404, Artisan, security tests |
