# Update and Versioning Strategy

## SimplePOS — Sistem Kasir & Penjualan Sederhana

| Field | Value |
|---|---|
| Document | `docs/UPDATE_STRATEGY.md` |
| Date | 2026-08-28 |
| Role | Senior Release Engineer |
| Product | Commercial Laravel **source-code** POS (single store, VPS) |
| Versioning | [Semantic Versioning 2.0.0](https://semver.org/) |
| Related | `docs/INSTALLER.md`, `docs/USER_GUIDE.md` §18, `CURSOR.md` §17, `DATABASE.md` §1.2, PRD RET-01–05 |

This strategy is for **customers who already installed** SimplePOS. Updates must **never** reopen `/install`, never run `migrate:fresh` / `db:wipe`, and never rewrite completed-sale snapshots.

The non-negotiable release invariant:

```text
Upgrade MUST preserve users, roles, catalog, stock,
completed transactions, transaction item snapshots,
stock movements, store settings, activity logs,
installer lock, and APP_KEY.
```

If a change cannot be done without destroying that data, it is **not** an in-place upgrade. It is a **major** migration project with an explicit conversion tool and a tested rollback, or it is refused.

---

# 1. Version format

SimplePOS product versions follow SemVer:

```text
MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]
```

Examples:

```text
1.0.0
1.0.1
1.1.0
2.0.0
1.2.0-rc.1
1.0.0+20260828
```

| Part | Meaning for SimplePOS |
|---|---|
| **MAJOR** | Incompatible change: schema, runtime, or business rules that a 1.x store cannot run without a documented breaking upgrade |
| **MINOR** | Backward-compatible feature (new screen, new nullable column, new report) |
| **PATCH** | Backward-compatible defect or security fix; no required customer process change |
| **PRERELEASE** | `alpha`, `beta`, `rc` — not for live tills unless the customer opts in |
| **BUILD** | Optional metadata (date, git sha). Does not change compatibility |

### 1.1 What the version identifies

The **product version** is the commercial artifact (source tree + `composer.lock` + built assets + this changelog).

It is **not**:

- the Laravel framework version (record that in release notes as a dependency);
- the installer lock timestamp;
- MySQL’s version.

Ship a single source of truth, for example:

```text
config/simplepos.php  →  'version' => '1.2.0'
```

Show it on **Settings** (Owner/Admin) and in `php artisan about` / a `simplepos:version` command so support tickets cite the same number as `CHANGELOG.md`.

Pre-1.0 (`0.y.z`) is internal only. The first customer MVP ships as **`1.0.0`**.

### 1.2 Supported upgrade path

Within one MAJOR, customers may jump **any number of MINOR/PATCH releases** (e.g. `1.0.2` → `1.4.0`) provided they run **all** migrations in order (Laravel does this automatically).

Across MAJOR, only **N → N+1** is supported (e.g. `1.8.3` → `2.0.0`, not `1.0.0` → `3.0.0` in one step) unless a specific release note states a skip path with a conversion command.

Do not support downgrade of MAJOR (`2.x` → `1.x`) except by **backup restore**.

---

# 2. Patch releases (`x.y.Z`)

Increment **PATCH** when the public product behavior and schema contract stay compatible.

Typical PATCH:

- Checkout, receipt, or report **defects** (wrong total display, 500 on empty state)
- Authorization holes that do not change the published matrix
- Dependency security updates that do not require a new PHP major
- Translation/copy fixes
- Performance (indexes **added** in a new migration, queries rewritten)
- Installer/docs fixes that do not change installed shops

Patch **must not**:

- Drop or rename tables/columns used by 1.x
- Change money types or rounding rules
- Change `COMPLETED` sale semantics
- Rotate `APP_KEY` in the upgrade script
- Require new `.env` keys without a safe default
- Enable Redis/queues for checkout

Hotfixes use the next PATCH (e.g. `1.0.1`). Do not ship silent “same version, different zip.”

---

# 3. Minor releases (`x.Y.0`)

Increment **MINOR** for backward-compatible **features**.

Typical MINOR:

- New optional Settings field (nullable or defaulted)
- New report, filter, or export that does not rewrite history
- New Gate that **defaults deny** for existing roles (cashiers stay cashiers)
- New table for an additive module (e.g. future “notes”) with FK `restrict`/`nullOnDelete` as designed
- PHP/Laravel **minor** upgrades that still satisfy `composer.json` on the customer’s verified runtime
- Deprecating a UI label while the old URL still works

Minor **must**:

- Leave existing rows valid
- Keep POS cash checkout working without retraining (additive UI only)
- Provide migrations that succeed on a database that already has sales
- Keep `/install` locked and unused

Minor **must not** remove a Version 1.0 module (POS, receipts, roles) or make Redis mandatory.

After MINOR, PATCH resets to `0` (SemVer).

---

# 4. Major releases (`X.0.0`)

Increment **MAJOR** when customers **cannot** stay on the old version’s assumptions.

Typical MAJOR (examples — each must be justified in release notes):

| Change | Why it is breaking |
|---|---|
| PHP or Laravel **major** requiring a new runtime | Old VPS will not boot |
| New required `.env` without default | App fails after deploy |
| Checkout moved to a queue | Violates MVP invariant; tills break if worker down |
| Refund/void that **updates** historical totals in place | Breaks snapshot/audit promises unless designed as new rows |
| Changing `users.role` allowed values without a converter | Sessions/policies fail |
| Dropping snapshot columns on `transaction_items` | Financial history loss |
| Multi-tenant `tenant_id` on all tables | Not an in-place 1.x upgrade |
| Public API as the only way to sell | Different product |
| Removing cash POS | Different product |

A large **new** feature that is fully additive (new tables, old POS unchanged) is a **MINOR**, not a MAJOR.

MAJOR upgrades include a dedicated runbook, longer maintenance window, and mandatory backup restore drill on staging.

---

# 5. Database migration strategy

Schema change is the highest-risk part of a SimplePOS upgrade. Sales, stock, and snapshots are the customer’s business.

### 5.1 Rules (normative)

1. **Laravel migrations only.** No manual production SQL that is not also a migration in git.
2. **Never edit a migration that has shipped** to any customer (`CURSOR.md` §17). Fix forward with a new file.
3. **Never** `migrate:fresh`, `migrate:refresh`, `db:wipe`, or installer re-seed on an installed store (`docs/INSTALLER.md` §7.5).
4. **One purpose per migration** (index vs column vs table).
5. **Preserve data.** Default for new columns: `nullable()` or a documented `default` that matches current business rules.
6. **Do not rewrite** `transactions.*` money fields or `transaction_items` snapshot columns in a migration “to clean up.” History is immutable (DATABASE.md §1.2, PRD RET-01).
7. **Do not** deactivate users or wipe `activity_logs` as a side effect of migrate.
8. Foreign keys: additive; do not `cascadeDelete` completed sales.
9. Money remains exact decimals — no float migrations.
10. `down()` is required for PATCH/MINOR when technically possible. If `down()` would destroy customer data, **omit a destructive down** and document “rollback = restore backup” for that migration.

### 5.2 Expand / migrate / contract

For breaking column changes (rename, type, NOT NULL):

| Phase | When | What |
|---|---|---|
| **Expand** | MINOR or first MAJOR PR | Add new column/table; write **both** old and new in application if needed |
| **Migrate data** | Same release or a follow-up PATCH | Backfill in chunks; never lock the POS table for hours without a maintenance window |
| **Contract** | **Next MAJOR** | Drop old column only after a full MINOR of dual-write and a deprecation notice |

Do not expand-and-contract in the same PATCH on live sales tables.

### 5.3 Data backfills

Prefer `php artisan simplepos:upgrade-… --dry-run` for large backfills rather than a single unbounded `UPDATE` in `up()`.

Backfills **must not**:

- Recalculate historical `line_total` from current `products.selling_price`
- Reassign `cashier_id` except to fix a proven NULL with a documented rule
- Delete “orphan” transactions

### 5.4 Seeders

`DatabaseSeeder` / demo seed **must not** run on upgrade. Upgrades are migrate-only. Optional `--demo` is install-time only.

### 5.5 Installer lock and settings

Migrations must not delete `storage/app/installed`, clear `APP_INSTALLED`, or replace the single `store_settings` row with installer defaults.

### 5.6 Migration order and failures

`php artisan migrate --force` applies pending files in timestamp order. If `up()` fails:

- The transaction should roll back **that** migration (InnoDB).
- Do not continue the release.
- Restore from backup if the failure left uncertainty (see §9).

Record `migrations` table as the schema watermark. Product SemVer and migration set are listed together in the changelog (“requires migrations through `2026_09_01_120000_add_…`”).

---

# 6. Backward compatibility

### 6.1 Application compatibility (same MAJOR)

A store on `1.y.z` must run `1.(y+n).*` after migrate without:

- Manual SQL
- Recreating the Owner
- Re-uploading the catalog
- Changing cashier passwords

Compatible:

- New Blade/Livewire screens
- New nullable columns
- New indexes
- New config keys with defaults in `config/*.php`
- Dependency bumps within Composer constraints that still run on the customer’s PHP

### 6.2 `.env` compatibility

New environment variables need a **default in code** so old `.env` files still boot.

Removing or renaming a required env key is **MAJOR**. Document a mapping in release notes (`OLD_NAME` → `NEW_NAME`).

Never require customers to rotate `APP_KEY` to complete an upgrade.

### 6.3 File and asset compatibility

Keep `storage/app/public` logo paths valid. Do not change the disk layout without a mover command.

`public/build` is replaced as a whole each release; that is expected (cache-bust via Vite hashes).

### 6.4 Session compatibility

After deploy, file sessions may invalidate (new app code). Cashiers sign in again. That is acceptable. Do not encrypt new payload formats that cannot read old session files unless PATCH notes say “all users must re-login.”

### 6.5 Integration compatibility

Version 1.0 has **no public API**. If an API appears later, it is versioned separately (`/api/v1`) and does not break the Blade POS.

---

# 7. Breaking changes

A change is **breaking** if a competent operator following the previous user guide would lose data, fail to boot, or silently mis-report sales.

Always breaking (MAJOR unless the feature never shipped):

- Dropping tables/columns that hold customer data
- Changing unique keys so existing SKUs/invoices collide
- Altering checkout so totals differ for the same cart
- Making stock go negative where MVP forbade it
- Weakening Owner protection
- Serving `/install` again on an installed shop
- Forcing `DEMO_MODE` behavior on customer shops

Process:

1. Label **BREAKING** in `CHANGELOG.md` and release notes.
2. Provide upgrade steps, including any `simplepos:upgrade-*` command.
3. Provide rollback (§9) — usually **restore backup**, not `migrate:rollback`.
4. Do not hide breaking changes inside a PATCH.

---

# 8. Upgrade procedures

Updates are **SSH / deploy**, not the web installer (`docs/USER_GUIDE.md` §18, `docs/INSTALLER.md` §7.5).

### 8.1 Who may upgrade

A technical operator with:

- SSH
- Database credentials
- Ability to put the till in a short freeze (or upgrade after close)

Store Owners do not click “upgrade” in Version 1.0.

### 8.2 Pre-checks

1. Read release notes for **this** version and every skipped MINOR.
2. Confirm PHP / MySQL / extensions still match the new `composer.json`.
3. Confirm disk space for backup + `vendor/` + `public/build`.
4. Confirm `DEMO_MODE=false` on a live shop.
5. Confirm you can restore the backup (credentials, dump integrity).
6. Prefer a **staging** restore of last night’s dump and a dry-run migrate.

### 8.3 Backup (mandatory)

See §10. Do not start without a verified dump.

### 8.4 Standard in-place upgrade (PATCH / MINOR)

Schedule: after close or a announced 10–30 minute window.

```text
# 1. Freeze writes
php artisan down --retry=60 --secret="YOUR-TEMP-SECRET"

# 2. Record current version (config/simplepos.php or git tag)
php artisan simplepos:version   # or cat the version file

# 3. Backup (database + storage + .env) — §10

# 4. Replace code
#    git fetch && git checkout v1.2.0
#    OR unpack vendor zip preserving .env, storage/, and installed lock

# 5. Dependencies and assets
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
# skip npm if the release already contains public/build

# 6. Schema — forward only
php artisan migrate --force --no-interaction

# 7. Optional version-specific command from release notes
# php artisan simplepos:upgrade-1.2 --no-interaction

# 8. Storage link (idempotent)
php artisan storage:link

# 9. Caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 10. Open
php artisan up
```

Do **not**:

- Run `/install`
- Run `simplepos:install`
- Run `db:seed`
- Run `migrate:fresh`
- Commit or overwrite `.env`
- Delete `storage/app/installed`

### 8.5 MAJOR upgrade extras

- Written runbook from the vendor
- Staging upgrade with a copy of production data
- Longer freeze
- Post-upgrade checklist: login Owner, one cash sale, receipt, today’s dashboard, one product edit, confirm old invoice still prints snapshots

### 8.6 Zero-downtime

Not required for Version 1.0. Dual-running old PHP-FPM and new code against a half-migrated schema is **unsupported**. Use `artisan down` around migrate.

### 8.7 Failed migrate during upgrade

1. Leave `artisan down`.
2. Do not “fix by installing again.”
3. Restore backup (§9.2) unless the vendor hotfix is explicitly “retry migrate only.”
4. Open a support ticket with product version **from** and **to**, and the migration filename that failed.

---

# 9. Rollback procedures

Goal: tills work again with **the same financial data** as before the attempt (or the backup point).

### 9.1 When `migrate:rollback` is allowed

Only if **all** of the following are true:

- The release notes mark the new migrations **reversible**
- POS has been **down** the whole time (no new sales on the new schema)
- `down()` does not drop columns that already received production writes
- You rollback **exactly** the number of batches this release applied (`php artisan migrate:rollback --step=N`)

Then redeploy the **previous** git tag / zip and `composer install` for that version, then `artisan up`.

If any sale completed after migrate, **do not rollback schema**. Use backup restore or stay on the new version.

### 9.2 Guaranteed rollback: restore backup

This is the **default** rollback for SimplePOS.

1. Keep `artisan down`.
2. Restore MySQL from the pre-upgrade dump onto the **same** database (or swap DSN to a restored instance).
3. Restore `storage/app` (logo) from the matching tarball.
4. Restore application **code** to the previous product version (git tag / previous zip).
5. Restore `.env` only if the upgrade changed it; prefer the pre-upgrade `.env`.
6. `composer install` for the old version; `storage:link`; `optimize:clear`.
7. Do **not** run migrate forward until you intend to retry the upgrade.
8. `php artisan up`.
9. Confirm an old invoice and stock quantities match the backup sample.

Installer lock files should come back with `storage/app`. If code is old and DB is new (or the reverse), **stop** — mixed generations are undefined. Restore **code + DB + storage** from the **same** backup set.

### 9.3 What rollback must not do

- `migrate:fresh`
- Delete `users` to “reset Owner”
- Re-run demo seeder
- Open `/install`
- Point production at a demo database

---

# 10. Backup requirements

Required **immediately before every upgrade**, even PATCH.

| Asset | Required | Notes |
|---|---|---|
| MySQL logical dump | **Yes** | All SimplePOS tables; `--single-transaction` InnoDB; include `migrations` |
| `storage/app` | **Yes** | Logo and future uploads |
| `.env` | **Yes** | Offline, access-controlled; not inside `public/` |
| Product version id | **Yes** | Tag or `config/simplepos.php` |
| Code tree / vendor zip | Recommended | So rollback does not depend on GitHub being up |
| `storage/app/installed` | Included in `storage/app` | Do not omit |

Also follow `docs/USER_GUIDE.md` §17 for daily backups. Upgrade backup is **extra**, not a substitute for nightly dumps.

Verification before you proceed:

```text
# dump is non-empty and recent
# gzip -t backup.sql.gz
# restore to staging once per major release train
```

Retention: keep the pre-upgrade set until the shop has run **at least one full business day** on the new version (longer for MAJOR). Off-site copy recommended.

---

# 11. Changelog format

Maintain `CHANGELOG.md` at the repository root (Keep a Changelog structure).

```markdown
# Changelog

All notable changes to SimplePOS are documented in this file.

The format is based on Keep a Changelog,
and this project adheres to Semantic Versioning.

## [Unreleased]

### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security

## [1.0.1] - 2026-09-15

### Fixed
- POS: insufficient-stock message when the last unit sells on another till.

### Security
- Login throttle remains enforced on `/login`.

## [1.0.0] - 2026-09-01

### Added
- Version 1.0 Core MVP (see PRD §13).
```

Rules:

- Every **shipped** git tag `vMAJOR.MINOR.PATCH` has a matching section and date (`YYYY-MM-DD`).
- Group by `Added` / `Changed` / `Deprecated` / `Removed` / `Fixed` / `Security`.
- Breaking changes: start the bullet with **BREAKING:** and link the upgrade section.
- Name migrations if the operator must know (`Added index on transactions.completed_at`).
- Do not log internal refactors that have zero customer effect (optional under Changed).
- Do not put secrets in the changelog.

Compare links at the bottom (`[1.0.1]: https://…/compare/v1.0.0...v1.0.1`) when a public repo exists; otherwise cite the zip filename.

---

# 12. Release notes

Release notes are the **customer-facing** companion to the changelog. Ship `docs/releases/vMAJOR.MINOR.PATCH.md` (or the vendor portal equivalent).

Each file includes:

1. **Product version** and date  
2. **Who should install** (all 1.x / only if you hit bug X)  
3. **Compatibility** (PHP, MySQL, from-version)  
4. **BREAKING** (or “None”)  
5. **Operator steps** (link to §8; extra commands)  
6. **Backup reminder**  
7. **User-visible changes** (cashiers vs Owner) in plain language  
8. **Migrations** included (count or filenames)  
9. **Rollback** (restore vs migrate:rollback)  
10. **Known issues**

Language: English and/or Indonesian to match the commercial package. No stack traces. No “refactor Livewire.”

PATCH notes can be short. MAJOR notes must include a checklist (login, test sale, old receipt).

Do not use `/install` as an upgrade instruction.

---

# 13. Deprecation policy

Deprecation applies to **published** behavior: UI routes, Settings keys, `.env` names, Gates, receipt fields, report meanings.

| Step | When | Requirement |
|---|---|---|
| **Announce** | MINOR `x.Y.0` | Changelog `Deprecated`; release notes; still works |
| **Keep** | At least **one MINOR** after announce, or **90 days**, whichever is longer | Dual support; no data loss |
| **Remove** | Next **MAJOR** only | `Removed` in changelog; upgrade converter if data exists |

Never remove in a PATCH.

Examples:

- Renaming a sidebar label: not a deprecation (PATCH/MINOR copy).
- Replacing `/reports/sales` URL: keep a redirect until MAJOR.
- Replacing an `.env` key: read old key until MAJOR.
- Changing “sales today” to exclude discounts: **breaking** (report meaning) → MAJOR or a new report beside the old one.

Features that never shipped in a customer `1.0.0` can be removed without deprecation.

Installer wizard steps are not a public API; locking `/install` after first setup is permanent, not deprecated.

---

# 14. Data-loss prevention checklist (release gate)

A version must not be tagged `v*` for customers until:

- [ ] New migrations run on an empty DB **and** on a DB with fixture sales
- [ ] After migrate, existing invoices print the **same** snapshot names and amounts
- [ ] Stock quantities still reconcile with `stock_movements`
- [ ] Owner still logs in; installer still 404
- [ ] `migrate:fresh` is **not** in the upgrade doc
- [ ] Backup/restore drill documented for this train
- [ ] Changelog + release notes match the tag
- [ ] SemVer bump matches the change type in §§2–4

---

# 15. Roles

| Role | Responsibility |
|---|---|
| Vendor release engineer | Tag, changelog, notes, migration review |
| Customer operator | Backup, `down` / `migrate` / `up`, smoke test |
| Store Owner | Approve freeze window; verify a receipt after upgrade |

---

# 16. Related documents

| Topic | Where |
|---|---|
| First install (not upgrade) | `docs/INSTALLER.md` |
| Operator backup / update sketch | `docs/USER_GUIDE.md` §17–§18 |
| Migration coding rules | `CURSOR.md` §17 |
| Historical snapshots | `DATABASE.md` §1.2 |
| Retention | PRD §32 |
| Public demo resets | `docs/DEMO_MODE.md` (never on customer upgrades) |
