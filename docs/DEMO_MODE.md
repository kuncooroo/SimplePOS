# Demo Mode Design

## SimplePOS — Public product sandbox

| Field | Value |
|---|---|
| Document | `docs/DEMO_MODE.md` |
| Date | 2026-08-28 |
| Product | SimplePOS — Sistem Kasir & Penjualan Sederhana |
| Audience | Product, security, and engineers shipping a **public** buyer demo |
| Related | `docs/INSTALLER.md` (optional **private** seed on a customer VPS — not this mode) |

This document specifies a **hosted public demo**: a dedicated SimplePOS instance that potential buyers can log into and explore. It is not SaaS multi-tenancy, not a per-visitor database, and not the installer checkbox “sample products for my store.”

A customer deployment must ship with **`DEMO_MODE=false`**. Enabling this mode on a live store is a product defect.

---

# 1. Goals

1. A buyer can walk the Version 1.0 journey: login → POS → cart → cash checkout → receipt → history → (as Admin/Owner) catalog, stock, dashboard, reports, audit — without talking to sales first.
2. A vandal cannot take over the instance, persist malware, spam email, lock out other visitors, or use the demo as a free production POS.
3. After a scheduled reset, every visitor sees the same catalog, users, and settings.
4. Demo restrictions are **server-side** (Policies/Actions). Hidden buttons are not a control.

Out of scope:

- Per-browser isolated sandboxes (too heavy for a single-store monolith).
- Phone-home license checks.
- Public registration.
- Re-opening `/install` on the demo host (`docs/INSTALLER.md` lock stays in force).

---

# 2. Deployment model

Run **one dedicated demo application** with its own:

- Database
- `APP_KEY`
- `storage/`
- Hostname (e.g. `demo.simplepos.example`)

Never point the demo at a customer database. Never share `APP_KEY` with production shops.

```text
DEMO_MODE=true
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=array          # or log; never SMTP/API
```

`config/demo.php` reads `DEMO_MODE` and the published account list. Helpers:

```text
demo_enabled(): bool
```

If `DEMO_MODE` is true, the app **must** show a persistent banner: this is a shared sandbox; data resets on a schedule; not a live store.

Search engines: `noindex,nofollow`. Login page may list demo credentials (they are public by design).

---

# 3. Demo accounts

Exactly **three** login identities, matching PRD §11. No fourth “guest” role. No public sign-up.

| Account ID | Display name | Email (login) | Role | Landing after login |
|---|---|---|---|---|
| `demo-owner` | Owner Demo | `owner@demo.simplepos.local` | `OWNER` | Dashboard |
| `demo-admin` | Admin Demo | `admin@demo.simplepos.local` | `ADMINISTRATOR` | Dashboard |
| `demo-cashier` | Kasir Demo | `cashier@demo.simplepos.local` | `CASHIER` | POS |

Rules:

- `active=true` always (reset restores this).
- These rows are **protected demo principals** (see §11). They are not the same as a customer’s protected Owner after a paid install.
- Do not seed extra Owner accounts.
- Historical `cashier_id` on seeded sales must point at `demo-cashier` (and optionally a second **non-login** staff user only if reports need “another cashier” — that extra user must be `active=false` so it cannot be used to authenticate).

Login page copy (Indonesian UI, operational):

```text
Mode demo — data bersama, direset otomatis.
Owner: owner@demo.simplepos.local
Administrator: admin@demo.simplepos.local
Kasir: cashier@demo.simplepos.local
```

---

# 4. Demo roles

Roles are the **real** `UserRole` values. Do not invent `DEMO_ADMIN`. Authorization still uses TASK-004 Gates; demo mode **intersects** them (AND):

```text
allowed = Gate::allows(ability) && DemoPolicy::allows(action)
```

| Role in demo | What the buyer should feel | Extra demo clamps |
|---|---|---|
| Cashier | Real POS: search, cart, discount, cash pay, receipt, own transactions, profile (read) | Cannot change password/name/email; cannot open Users/Settings/Inventory/Reports |
| Administrator | Catalog, stock, all transactions, dashboard, reports, settings **screens** | Cannot mutate users, settings, uploads, or seed identity; catalog/stock writes allowed but reset |
| Owner | Same as Admin plus audit log and the Owner-only matrix rows | Cannot assign Owner, cannot deactivate demo users, cannot change own password, cannot unlock installer |

Cashier remains denied the same product Gates as production (AC-11). Demo mode does not grant Cashiers extra powers.

---

# 5. Demo credentials strategy

Public demo passwords are **not secrets**. Treat them as published documentation.

| Rule | Requirement |
|---|---|
| Storage | Hashed in `users.password` like any account (`Hash::make`) |
| Source of plaintext | `config/demo.php` + login banner + marketing page. **Not** committed as a production Owner password. Not reused as the installer default |
| Value | One shared passphrase for all three accounts is acceptable (simpler support). Prefer distinct per role so a buyer notices they changed account: e.g. `Demo-Owner-2026`, `Demo-Admin-2026`, `Demo-Kasir-2026` |
| Rotation | Only when the **published** marketing copy rotates. After each DB reset, reseeds **the same** published hashes so bookmarks keep working |
| Change in-app | **Forbidden** for all demo principals (profile and user-admin) |
| Guessing | Login throttle still applies (TASK-002). Public passwords do not mean unlimited session creation |
| Customer installs | Installer must **not** create these emails. `DEMO_MODE=false` seed uses different Owner credentials chosen by the buyer (`docs/INSTALLER.md` §6.3) |
| Support/staff | No hidden backdoor user. No `simplepos:demo-login` HTTP route |

Never log the passphrase. Never put it in `APP_KEY` or `.env.example` for customer builds. A demo-only `.env.demo.example` may list `DEMO_MODE=true` and point at this document for the published passwords.

---

# 6. Restricted operations (policy)

Three layers. Implement all three.

### Layer A — Hard deny (always, while `DEMO_MODE=true`)

Mutations that threaten takeover, persistence, or the shared shell of the demo. Return **403** with a stable message, not a silent no-op.

```text
Demo mode: this action is disabled.
```

UI: control visible but disabled **or** visible with the same message on submit. Server deny is mandatory even if the button is hidden.

### Layer B — Allow, then reset

Operational exploration (POS checkout, optional catalog/stock edits). Bounded (rate limits, caps). Destroyed on the reset schedule.

### Layer C — Read-only product behavior

Unchanged: reports, dashboards, receipts, audit **view**, stock overview, transaction history.

---

# 7. Database reset strategy

## 7.1 Command

```text
php artisan simplepos:demo-reset
```

Must **refuse** unless `DEMO_MODE=true`. Must **refuse** if `APP_INSTALLED` is true **and** `DEMO_MODE` is false (belt: never wipe a customer shop). Prefer an additional env `DEMO_RESET_TOKEN` required as `--token=` so a mis-copied cron cannot hit the wrong host.

No HTTP route, no Livewire button, no “Reset demo now” in Settings.

## 7.2 What reset does

Prefer **deterministic reseed**, not `migrate:fresh`, so schema drift from pending migrations is not silently applied on a public host (ops applies migrations separately).

Order:

1. Cache lock `demo-reset` (skip if another reset is running).
2. Optional short maintenance flag (cache) so checkout fails closed mid-reset.
3. Delete files under `storage/app/public` that are not the seeded logo.
4. Truncate in FK-safe order (or delete children first):

   ```text
   activity_logs
   stock_movements
   transaction_items
   transactions
   products
   categories
   users          # then re-insert demo principals + optional inactive staff
   store_settings # then re-insert the single demo row
   ```

   Do not truncate `migrations` / `sessions` table without also flushing session files.

5. Run `DemoSeeder` (same dataset every time).
6. Flush session store (file sessions: delete `storage/framework/sessions/*`). All visitors must re-login.
7. Write `storage/app/demo_last_reset` timestamp for the banner.
8. Release lock.

Do not use `migrate:fresh` from the web installer (`docs/INSTALLER.md`). The Artisan demo reset may `migrate` (forward only) if ops wants schema catch-up; **do not** `fresh` unless the runbook explicitly says the host is disposable.

## 7.3 Isolation

Reset touches **only** the demo connection. `config/database.php` default connection on that host is the demo DB. No second “customer” connection on the same app.

---

# 8. Demo data

Seed must look like a small Indonesian retailer, IDR / Rp, operational not playful.

| Domain | Content |
|---|---|
| Store settings | Name e.g. `Toko Demo SimplePOS`; address/phone dummy; `currency_code=IDR`; `currency_symbol=Rp`; receipt footer; **no** buyer-uploaded logo (optional packaged PNG in `database/demo/logo.png` copied once) |
| Categories | 4–6 active (e.g. Minuman, Makanan, Sembako, ATK); one inactive category to show filters |
| Products | ~12–20 SKUs; mix of in-stock, low-stock (≤ threshold), out-of-stock, one inactive (not sellable). Unique SKUs/`DEMO-*` prefix. Realistic IDR prices. Cost prices present for Owner/Admin views |
| Opening stock | Enough for several hours of visitor checkouts (e.g. 50+ on popular SKUs) so the POS does not die before the next reset |
| Users | The three demo principals only (plus optional inactive historical cashier) |
| Transactions | 8–15 **completed** cash sales across today and prior days so dashboard/reports/history are not empty. Snapshots populated. `SALE` movements consistent with current stock |
| Manual adjustments | 1–2 `MANUAL_ADJUSTMENT` rows with reasons so inventory history is demonstrable |
| Activity log | Seeded rows for settings/user/stock so Audit is not empty for Owner |

`DemoSeeder` is shared with TASK-023 / installer `--demo` **only if** it is parameterized:

```text
DemoSeeder::run(protectedOwner: ?User, publicPasswords: bool)
```

Customer `--demo` must **not** create `owner@demo.simplepos.local` or published passphrases. Public demo host uses the published identities. Do not fork two incompatible catalogs if one seeder with a mode flag will do.

---

# 9. Upload restrictions

MVP upload surface is **store logo** (SRS FILE-01–03, TASK-008).

While `DEMO_MODE=true`:

- Reject all `store_settings` logo replace/delete/upload in `UpdateStoreSettings` **before** the filesystem write.
- Do not accept `multipart` files on any other route (none exist in MVP; keep the deny in a global middleware on `POST` with files when demo is on, except nothing — fail closed).
- Do not write visitor files under `storage/app/public`.
- Seeded logo (if any) is restored on reset; visitors cannot replace it.

Rationale: public upload is malware, storage fill, and XSS via SVG. A demo does not need logo QA; buyers evaluate layout with the packaged file.

---

# 10. Configuration restrictions

| Setting | Demo |
|---|---|
| `store_name`, address, phone, email, receipt footer | **Hard deny** write (Layer A). Read allowed so receipts/settings screens still teach the UI |
| `currency_code` / `currency_symbol` | **Hard deny** write (a visitor switching to USD confuses every other buyer) |
| `low_stock_threshold` | **Hard deny** write |
| `logo_path` | **Hard deny** (see §9) |
| `.env` / `APP_*` / `DEMO_MODE` | No UI. Artisan only on SSH |
| `/install*` | Remains 404 (`docs/INSTALLER.md` §7) |
| Session/cache/queue drivers | Unchanged; do not expose |

Settings **pages** stay in the Owner/Admin nav so buyers see MVP-09. Submit is 403.

---

# 11. Email restrictions

MVP does not send mail (SRS §21). Demo still hardens the future surface:

- `MAIL_MAILER=array` (or `log` on disk that is not public).
- No SMTP credentials on the demo host.
- If password reset or notifications are added later: **hard deny** in demo (would spam, enumerate, or phish using the demo domain).
- Store settings `email` field is display data only; changing it is already denied (§10).
- Do not CC or notify `owner@demo.simplepos.local` of visitor sales.

---

# 12. Password restrictions

| Action | Demo |
|---|---|
| Login with published password | Allowed |
| Profile: change own password | **Hard deny** |
| Profile: change own name | **Hard deny** (keeps the three roles recognizable) |
| Profile: change email | **Hard deny** (MVP profile email is already read-only; keep it) |
| Users: set another user’s password | **Hard deny** |
| Users: create user with a password | **Hard deny** |
| “Forgot password” | Not in MVP; if added, **hard deny** |
| Artisan `simplepos:demo-reset` reseeding hashes | Allowed (ops) |
| Using demo passwords on a customer install | Forbidden by installer (different Owner) |

Throttle login. Do not weaken password validation globally; demo accounts simply cannot rotate.

---

# 13. Admin protection

“Admin” here means **both** the protected Owner **and** the Administrator demo principal, plus the Cashier identity that other visitors share.

Protected demo principals are identified by **email allowlist** in `config/demo.php` (not a guessable `role` check alone). Optional `users.is_demo_principal` boolean is acceptable if documented; do not rely on name “Owner Demo” as the control.

While `DEMO_MODE=true`, **nobody** (including someone who is logged in as Owner) may:

- Create, update, deactivate, or reactivate any user
- Assign `OWNER` or change any user’s `role` or `email`
- Deactivate themselves
- Open installer
- Run demo reset from the app
- Change store settings or logo
- Change any user’s password

This is stricter than production: in production, Owner **may** manage users. In public demo, user management is a **read-only gallery** (list still useful) or the mutating buttons 403.

If User index is too tempting, keep the index **read-only** (no create/edit routes registered, or routes return 403). Prefer showing the list so buyers see MVP-02.

Production rule “Administrator cannot control Owner” still holds. Demo adds “nobody can control demo principals.”

---

# 14. Reset schedule

| Item | Default |
|---|---|
| Interval | **Every 2 hours** |
| Timezone | `Asia/Jakarta` (product locale) |
| Scheduler | Laravel `Schedule` on the demo host **or** system cron calling `php artisan simplepos:demo-reset --token=...` |
| Banner | “Data demo direset setiap 2 jam. Reset terakhir: {datetime}. Reset berikutnya: {datetime}.” |
| During reset | Checkout and other writes return a short “Demo is refreshing” 503/429; typically seconds |
| Manual extra reset | SSH only, same command |
| Not scheduled | Customer VPS (`DEMO_MODE=false`) |

Why 2 hours: long enough to finish a sales walkthrough and screenshots; short enough that a vandalized catalog or emptied stock does not linger. If checkout volume is huge, shorten to 1 hour rather than raising stock to infinity.

Ops alert if `demo_last_reset` is older than 3 hours (cron died).

---

# 15. Application actions — disable inventory

Authoritative list for SimplePOS Version 1.0 (tasks TASK-002–022). Status:

- **DENY** — Layer A, 403 in demo
- **ALLOW** — Layer B or C
- **N/A** — not in MVP; if added later, default **DENY** until explicitly reviewed

## 15.1 Authentication and session (TASK-002)

| Action | Status | Notes |
|---|---|---|
| Login | ALLOW | Throttled; published credentials |
| Logout | ALLOW | |
| Session regenerate on login | ALLOW | Keep production behavior |
| Reject inactive users | ALLOW | Demo principals stay active |
| Password reset | N/A → DENY | |
| Email verification | N/A → DENY | |
| Remember-me forever | ALLOW with short session lifetime recommended (e.g. 2h) so reset + expiry align |

## 15.2 Identity — users (TASK-003)

| Action | Status |
|---|---|
| `CreateUser` | DENY |
| `UpdateUser` (name, email, role) | DENY |
| `ChangeUserStatus` (activate/deactivate) | DENY |
| Assign `OWNER` / `assignOwnerRole` | DENY |
| User index / show (read) | ALLOW |
| Hard-delete user | N/A (product forbids) → remain DENY |

## 15.3 Identity — profile (TASK-022)

| Action | Status |
|---|---|
| View own profile | ALLOW |
| Update own name | DENY |
| Update own password | DENY |
| Update own email | DENY |
| Set `role` / `active` via profile | DENY (already illegal in production) |

## 15.4 Catalog — categories (TASK-006)

| Action | Status | Notes |
|---|---|---|
| List / search / filter | ALLOW | |
| Create category | ALLOW | Reset wipes extras; cap e.g. 15 categories total |
| Update category name | ALLOW | Seed names restored on reset |
| Activate / deactivate category | ALLOW | Do not allow deactivating **all** seed categories if it would empty POS; cap: cannot deactivate last active seed category |
| Delete category | N/A (deactivate only) | |

## 15.5 Catalog — products (TASK-007)

| Action | Status | Notes |
|---|---|---|
| List / search / show | ALLOW | |
| Create product | ALLOW | Cap e.g. 40 products; SKU prefix optional |
| Update product (name, prices, SKU, barcode, category, opening stock) | ALLOW | Restored on reset |
| Activate / deactivate product | ALLOW | Cannot deactivate every sellable SKU (keep ≥1 in-stock active product) |
| Product images | N/A | Out of MVP; DENY if added |

## 15.6 POS (TASK-009–012)

| Action | Status | Notes |
|---|---|---|
| Product search (name/SKU/barcode) | ALLOW | |
| Add / update qty / remove cart lines | ALLOW | |
| Clear cart | ALLOW | Session-only |
| Line or cart discount (cash rules) | ALLOW | Server-side calculator unchanged |
| Tender cash / `CompleteSale` | ALLOW | Rate-limit e.g. 20 checkouts / IP / hour to limit table growth |
| Trust client prices | DENY | Production invariant; unchanged |
| Oversell / negative stock | DENY | Production invariant |
| Concurrent last-unit | ALLOW | Real locking; may 422 — good demo of AC-07 |
| Queue / job checkout | N/A | Must stay sync |

## 15.7 Receipts and history (TASK-013–014)

| Action | Status |
|---|---|
| View receipt (authorized) | ALLOW |
| Print (browser) | ALLOW |
| Email/WhatsApp receipt | N/A → DENY |
| Transaction index / filters | ALLOW |
| Cashier IDOR other cashiers’ tickets | DENY | Same `TransactionPolicy` as production |
| Void / refund / delete completed sale | N/A → DENY (`deleteCompletedTransaction` already false) |
| Edit completed totals | N/A → DENY |

## 15.8 Inventory (TASK-015–017)

| Action | Status | Notes |
|---|---|---|
| Stock overview / low stock | ALLOW | |
| `AdjustStock` | ALLOW | Reason required; no negative stock; rate-limit; reset restores quantities |
| Stock movement history | ALLOW | |
| CSV export of stock | N/A → DENY | |

## 15.9 Dashboard and reports (TASK-018–020)

| Action | Status |
|---|---|
| Dashboard widgets | ALLOW | |
| Sales reports | ALLOW | |
| Product sales reports | ALLOW | |
| Export/print PDF/CSV | N/A → DENY if added (data exfil / load) |
| Cashier access to dashboard/reports | DENY | Production AC-11 |

## 15.10 Audit (TASK-005, TASK-021)

| Action | Status |
|---|---|
| `RecordActivity` on allowed writes | ALLOW | |
| View audit log (Owner) | ALLOW | |
| Administrator view audit | DENY unless product later allows | Default MVP Owner only |
| Delete / purge audit | N/A → DENY | |
| Tamper audit rows via UI | DENY | |

## 15.11 Store settings (TASK-008)

| Action | Status |
|---|---|
| View settings screens | ALLOW |
| `UpdateStoreSettings` any field | DENY |
| Logo upload / replace / delete | DENY |

## 15.12 Installer and ops (`docs/INSTALLER.md`, TASK-024–028)

| Action | Status |
|---|---|
| `/install*` | DENY | 404 when installed |
| Rewrite `.env` via installer | DENY | |
| `migrate:fresh` / `db:wipe` via HTTP | DENY | |
| `simplepos:install` | DENY | Already installed |
| `simplepos:demo-reset` via HTTP | DENY | Artisan + token only |
| Toggle `DEMO_MODE` from UI | DENY | No UI |

## 15.13 Cross-cutting / future

| Action | Status |
|---|---|
| Public API / tokens | N/A → DENY | ADR-10 |
| Arbitrary file manager | N/A → DENY | |
| Run Artisan from web | N/A → DENY | |
| Debug (`APP_DEBUG`, Telescope, Horizon) | DENY | Must be off |
| Multi-store / tenant switch | N/A | |
| Payment gateways | N/A | Cash only |
| Mass import/export | N/A → DENY | |

---

# 16. Recommended enforcement

Do not sprinkle `if (demo_enabled())` through Livewire only.

1. `DemoRestricted` middleware on `web` after `auth` for mutating routes that are always denied (users, settings, profile password).
2. `DemoGuard` inside Actions (same place as `authorize()`): `CreateUser`, `UpdateUser`, `ChangeUserStatus`, `UpdateStoreSettings`, profile password Action.
3. Optional `Gate::before` that cannot override production denies; only adds demo denies.
4. Caps (category/product counts, checkout rate) in those Actions.
5. Tests: `DEMO_MODE=true` feature tests for every **DENY** row in §15 that exists in MVP; plus Cashier still denied management; plus `simplepos:demo-reset` refuses when `DEMO_MODE=false`.

UI: reuse toast/alert (NOT-02). Do not leak stack traces.

---

# 17. Security requirements (normative)

1. `DEMO_MODE` default **false** in `.env.example` for the commercial source product.
2. Demo host: `APP_DEBUG=false`, no Telescope, no `/install` UI, no SMTP.
3. All **DENY** actions 403 even with CSRF + authenticated Owner session.
4. Demo reset is not reachable over HTTP.
5. Published passwords never unlock a customer install.
6. Uploads rejected before `store()`.
7. No second hidden Owner.
8. Session flush on reset.
9. Rate-limit login and checkout.
10. Banner always visible in demo (cannot be dismissed into a “clean” production impersonation for screenshots used as fraud — watermark receipts with `DEMO` on printed ticket).

Receipt watermark: append footer line `DEMO — bukan transaksi toko nyata` in addition to store footer when `DEMO_MODE=true` (does not rewrite historical snapshot columns; render-time only).

---

# 18. Relationship to installer demo data

| | Public demo (`docs/DEMO_MODE.md`) | Installer `--demo` (`docs/INSTALLER.md` §6.5) |
|---|---|---|
| Flag | `DEMO_MODE=true` | One-time seeder, `DEMO_MODE=false` |
| Accounts | Published Owner/Admin/Cashier | Buyer’s Owner + optional staff; **no** published passphrase |
| Restrictions | §15 DENY list | None (it is their store) |
| Reset cron | Yes | No |
| Purpose | Sell the product | Local QA / first-look after purchase |

Do not enable Layer A denies on a customer shop because they ticked “sample products” in the installer.

---

# 19. Implementation tasks (later)

Not created in this change. When tasked, split roughly:

- Config, banner, watermark, `demo_enabled()`
- Deny middleware/Action guards + tests
- `DemoSeeder` public identities + snapshot
- `simplepos:demo-reset` + schedule + refuse-if-not-demo

Depends on TASK-002–008, 012, 022 at minimum; full §15 coverage after TASK-023.
