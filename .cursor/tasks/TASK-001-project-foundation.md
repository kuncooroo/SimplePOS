# TASK-001 — Project Foundation

**Task ID:** TASK-001  
**Title:** Project foundation  
**Filename:** `TASK-001-project-foundation.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Initialize the SimplePOS Laravel application with the approved stack, authenticated app shell, shared Blade UI primitives, and a `users` table that can support later authentication and roles.

---

## Background

SimplePOS is a single-store Laravel modular monolith (PRD §13, SYSTEM_DESIGN ADR-01/ADR-03). There is no application code yet. This task creates the runnable skeleton so later tasks add one feature at a time.

Target stack (`CURSOR.md` §2, SRS environment table): Laravel 13.x, PHP 8.4.x, MySQL 8.4 LTS, Blade, Livewire, Alpine.js, Tailwind CSS. Verify actual versions from generated project files after init. If a version cannot be verified, record `TBD — Requires Environment Verification` rather than inventing it.

Do not add Redis, queues, a public API, SPA frontend, or extra packages Laravel already covers.

---

## Dependencies

None.

---

## Files likely affected

- `composer.json`, `package.json`, `composer.lock`, lockfile
- `.env.example`
- `bootstrap/app.php`
- `config/app.php`, `config/database.php`, `config/filesystems.php`
- `database/migrations/*_create_users_table.php` (and password reset only if scaffolding includes it; password reset is **not** an MVP product requirement)
- `app/Models/User.php`
- `app/Enums/UserRole.php` (allowed here so the users table has a typed role from day one)
- `app/Providers/AppServiceProvider.php`
- `resources/css/app.css`, `resources/js/app.js`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/components/` (`page-header`, `button`, `input`, `table`, `badge`, `alert`, `toast`, `empty-state`, `money`, `modal` stubs)
- `routes/web.php`
- `tests/TestCase.php` / Pest or PHPUnit bootstrap
- `docs/PROJECT_STRUCTURE.md` only if generated paths differ and must be recorded

---

## Database changes

Create/extend `users` per `DATABASE.md` §23:

| Column | Rules |
|---|---|
| `id` | BIGINT UNSIGNED PK |
| `name` | VARCHAR(150), required |
| `email` | VARCHAR(191), unique, login identifier |
| `password` | VARCHAR(255), hashed |
| `role` | VARCHAR(32), indexed, values `OWNER` / `ADMINISTRATOR` / `CASHIER` |
| `active` | BOOLEAN, default true, indexed |
| `created_at`, `updated_at` | timestamps |

No `roles` table. No `tenant_id`. Do not add other business tables in this task.

Seeder (minimal): one protected Owner account for local development. Use `.env` placeholders for the password; never commit a production secret.

---

## Backend requirements

1. Create the Laravel app using official installer/starter compatible with the verified versions.
2. Install Livewire and Tailwind + Alpine in the Laravel-supported way.
3. Session driver: file (default). Do not introduce Redis.
4. `User` model: fillable/guarded per convention, hide `password` / `remember_token`, cast `active` to bool, cast `role` to `UserRole`.
5. `UserRole` enum: `OWNER`, `ADMINISTRATOR`, `CASHIER`.
6. Named routes; auth routes may be stubs that redirect until TASK-002.
7. Testing framework enabled (Pest or PHPUnit — follow what the installer generates).
8. Code style: strict types where consistent with generated Laravel code.

---

## Frontend requirements

1. Guest layout: centered, no marketing chrome (`UI_UX.md` login direction).
2. App layout: sidebar + top bar + main content (`UI_UX.md` §2–3). Sidebar items may be placeholders; hide unimplemented modules or link to `#` only if labeled as unavailable — prefer listing only routes that exist.
3. Tailwind tokens: professional, operational, no glassmorphism or decorative heroes (`CURSOR.md` §51).
4. Shared components for later CRUD/POS reuse. `<x-money>` may render a placeholder format until TASK-008 store currency exists (default IDR / Rp is acceptable per LOC-05).
5. Toast/alert region in the app layout for session flash.
6. Accessible labels, focus states, semantic headings.

---

## Validation rules

Not a product feature task. Ensure `.env.example` documents required `APP_*` and `DB_*` keys without secrets.

---

## Authorization rules

No product authorization yet. All business pages remain behind a future `auth` middleware (TASK-002). Do not leave debug tooling enabled in the documented production configuration.

---

## Business rules

- Single-store MVP (BR-21).
- Three roles only (PRD §11).
- Do not implement checkout, products, or reports.

---

## Edge cases

- Fresh `migrate` on empty MySQL succeeds.
- Missing `.env` is documented; app must not require committed secrets.
- Frontend build (`npm`/`pnpm`) produces CSS used by layouts.

---

## Security considerations

- Never commit `.env`.
- `APP_DEBUG` documented as false for production.
- User `password` hidden from serialization.
- HTTPS is a deployment concern, not implemented here; do not disable CSRF.

---

## Testing requirements

- Application boots (`php artisan --version` / feature test hitting a public health or welcome/login placeholder).
- `User` factory can create a user with role and `active`.
- Migration is reversible / migrate-fresh works locally.

---

## Acceptance criteria

- [ ] Laravel app runs locally against MySQL.
- [ ] Livewire, Blade, Alpine, and Tailwind are wired.
- [ ] Guest and app layouts render.
- [ ] `users` table matches DATABASE.md (name, email, password, role, active).
- [ ] `UserRole` enum exists.
- [ ] Owner seeder creates one active Owner.
- [ ] No extra infrastructure (Redis, API, SPA, queue workers) was added.

---

## Definition of Done

- [ ] Requirement implemented as a runnable skeleton only.
- [ ] Versions recorded from actual `composer.json` / `package.json` / runtime where possible.
- [ ] Layouts match UI_UX direction (operational, not decorative).
- [ ] No unrelated features.
- [ ] Relevant smoke tests pass.
- [ ] Diff contains no credentials.
