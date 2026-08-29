# Security Audit

## SimplePOS — Sistem Kasir & Penjualan Sederhana

| Field | Value |
|---|---|
| Document | `docs/SECURITY_AUDIT.md` |
| Date | 2026-08-28 |
| Auditor role | Senior Application Security Engineer (Laravel) |
| Product version in scope | Version 1.0 MVP (PRD §13 / §38) |
| Code reviewed | SimplePOS repository + leftover Laravel 13 scaffold at `../_simplepos_laravel_tmp` |
| Authoritative controls | `CURSOR.md` §28–29, SRS §31, PRD §29, SYSTEM_DESIGN ADR-05/ADR-10, DATABASE.md, BUSINESS_FLOW.md |

---

# 1. Executive summary

SimplePOS **does not yet contain an application** (`artisan`, `app/`, `composer.json` are absent). Security controls required by the PRD cannot execute.

A stock Laravel 13.29 skeleton exists **outside** this repository from an interrupted TASK-001. Composer `audit` on that skeleton reported **no known advisory CVEs**. The skeleton is still **not** a SimplePOS deployment: it uses SQLite, debug-on defaults, no roles, no Livewire, and no POS/checkout.

**Verdict:** Residual risk is **Critical** until TASK-001–012 exist and pass security tests. Design documents are generally aligned with Laravel-native controls. The main danger is shipping the leftover skeleton, or implementing checkout/auth without the documented server-side invariants.

**Fixed in this pass (safe, no business-rule change):**

1. Added a repository `.gitignore` so `.env`, keys, vendor, and logs cannot be committed when the app is created.
2. Hardened the leftover skeleton `.env.example` and `.gitignore` so a future copy-in does not ignore `.cursor/` or default to database queue/session/cache.

---

# 2. Scope and method

Reviewed:

- Product and engineering docs (PRD, SRS, SYSTEM_DESIGN, DATABASE, BUSINESS_FLOW, CURSOR.md, PROJECT_STRUCTURE, Cursor tasks).
- Leftover Laravel 13 files: `User` model, users migration, `.env` / `.env.example`, `config/session.php`, `routes/web.php`, `.gitignore`, `phpunit.xml`.
- `composer audit` in the leftover project.

Not reviewed (does not exist): Livewire components, Policies, checkout Actions, file-upload handlers, production VPS config, TLS termination, backup jobs.

No penetration payloads were executed against a live store.

---

# 3. Findings

## SA-001 — No enforceable security control plane

**Severity:** Critical  
**Affected component:** SimplePOS repository (missing Laravel application)  
**Attack scenario:** An operator copies the leftover skeleton, or a partially built POS, onto a VPS and exposes it. There is no role model, no checkout authorization, and no documented HTTPS enforcement in running code.  
**Impact:** Full compromise of whatever data is later put on that host; sales and stock cannot be trusted.  
**Recommended fix:** Complete TASK-001 inside this repo, then TASK-002 and TASK-004 before any network exposure. Do not deploy `_simplepos_laravel_tmp` as SimplePOS.  
**Status:** Open (implementation). `.gitignore` added to reduce secret leakage when the app appears.

---

## SA-002 — Repository had no `.gitignore`

**Severity:** Critical (pre-fix) / **Mitigated**  
**Affected component:** Git / secrets handling  
**Attack scenario:** TASK-001 copies Laravel into SimplePOS. `.env` (APP_KEY, DB password) and `storage/*.key` are committed. Attackers with repo access decrypt sessions, forge cookies, and read the database.  
**Impact:** Credential and application-key disclosure; session forgery.  
**Recommended fix:** Ignore `.env`, keys, `vendor/`, logs; **do not** ignore `.cursor/` (task files must remain versioned).  
**Status:** **Fixed** — `.gitignore` added at the SimplePOS root.

---

## SA-003 — Identity schema cannot enforce roles or inactive users

**Severity:** Critical (if the leftover schema is used)  
**Affected component:** `users` migration / `User` model in leftover scaffold  
**Attack scenario:** Application is launched on the stock `users` table. Every authenticated user is equivalent. Inactive staff cannot be barred at the data layer (`active` column missing).  
**Impact:** Privilege confusion; deactivated cashiers keep access if only UI is later gated. Violates SEC-03, SEC-04, FR-AUTH-03, DATABASE.md §23.  
**Recommended fix:** Add `role` (enum `OWNER` / `ADMINISTRATOR` / `CASHIER`, indexed) and `active` (boolean, default true, indexed) before any login feature. Cast `role` to `UserRole`. Never put `role` on unguarded mass assignment from a Cashier-accessible form.

---

## SA-004 — Default debug and SQLite in leftover environment example

**Severity:** High  
**Affected component:** leftover `.env` / `.env.example`  
**Attack scenario:** Production uses `APP_DEBUG=true`. Failed requests dump env, SQL, and stack traces (SEC-11). SQLite file on a public disk or in backups is a single-file database steal.  
**Impact:** Secret and schema disclosure; bypass of MySQL integrity/locking assumptions used by checkout.  
**Recommended fix:** Production: `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=mysql`. Document in `.env.example`.  
**Status:** **Partially fixed** — leftover `.env.example` comments and drivers updated. Local leftover `.env` still has `APP_DEBUG=true` (acceptable for local only; do not copy that file).

---

## SA-005 — Checkout financial integrity not implemented

**Severity:** Critical (product risk; no code yet)  
**Affected component:** Sales / Inventory (TASK-012 not built)  
**Attack scenario:** When POS is added, if totals, prices, or stock are taken from Livewire/Alpine/hidden fields, a cashier or MITM client submits `unit_price=0` or `quantity` above stock. Concurrent checkouts of the last unit create negative stock or a completed sale without items.  
**Impact:** Direct financial loss, inventory desync, false revenue. Violates BR-07–12, CURSOR.md §48, DATABASE.md §21.1 / §36.  
**Recommended fix:** `CompleteSale` must reload products with row locks, recalculate with `CheckoutCalculator`, persist transaction + items + stock + `SALE` movements in **one** DB transaction, ignore client totals. Tests: AC-05, AC-06, AC-07, AC-14.

---

## SA-006 — IDOR on transactions/receipts (design, unimplemented)

**Severity:** High (when TASK-013/014 ship without policies)  
**Affected component:** Transaction history, receipts  
**Attack scenario:** Cashier A replaces `transaction_id` / invoice in the URL and reads Cashier B’s sale (SEC-07, SRCH-10).  
**Impact:** Disclosure of other cashiers’ sales, customer ticket contents, cash amounts.  
**Recommended fix:** `TransactionPolicy`: Cashier `view` only when `cashier_id === auth()->id()` (own/allowed). Owner/Admin `viewAllTransactions`. Apply on Livewire `render` and receipt controller, not only on nav visibility.

---

## SA-007 — Privilege escalation via mass assignment of `role` / `active`

**Severity:** High (when user admin ships)  
**Affected component:** `User` model, Livewire user/profile forms  
**Attack scenario:** `role` is added to `#[Fillable]` and a profile or user form binds `$this->user->update($request->all())`. A Cashier sets `role=OWNER`. Leftover model currently fillable `name`, `email`, `password` only — safe until role is added carelessly.  
**Impact:** Full store takeover.  
**Recommended fix:** Keep `role` and `active` off request mass assignment. Change them only in `ChangeUserRole` / `ChangeUserStatus` after `UserPolicy`. Profile must not accept `role`. Administrator cannot mutate Owner (PRD §20).

---

## SA-008 — Login throttling and session regeneration not implemented

**Severity:** High (when TASK-002 ships without them)  
**Affected component:** Authentication  
**Attack scenario:** Unthrottled POST `/login` brute-forces Owner password. Session fixation if login does not regenerate session ID (SYSTEM_DESIGN §8).  
**Impact:** Account takeover of Owner; unrestricted POS and settings.  
**Recommended fix:** Laravel `RateLimiter` / `throttle` on login (SRS §31.11). `session()->regenerate()` on success; invalidate on logout. Generic error string (no user enumeration). Reject `active=false` with the same message.

---

## SA-009 — Store logo path traversal / XSS via SVG

**Severity:** High (when TASK-008 ships)  
**Affected component:** Store settings file upload  
**Attack scenario:** Upload `../../.env` as filename, or SVG with script, stored under `public` and opened by Owner/Cashier on receipts.  
**Impact:** Secret file overwrite/read or stored XSS (session theft). Violates SEC-10, CURSOR.md §29.  
**Recommended fix:** Allow-list MIME (`image/png`, `image/jpeg`, `image/webp`). Reject SVG. Store hashed name via Laravel filesystem; never use client basename. Failed replace keeps old `logo_path`.

---

## SA-010 — Laravel skeleton `.gitignore` dropped `.cursor/`

**Severity:** High (supply-chain / process)  
**Affected component:** leftover `.gitignore`  
**Attack scenario:** Skeleton is copied into SimplePOS. Engineering tasks and any future secrets mistakenly placed under `.cursor/` vanish from git **or** (worse) a later “force add” of ignored files confuses operators. Primary issue: **loss of security-relevant task controls** and inconsistent repo hygiene.  
**Impact:** Process failure; easier to ship without the security tasks.  
**Recommended fix:** Do not ignore `.cursor/` in SimplePOS.  
**Status:** **Fixed** in leftover `.gitignore` and in the new SimplePOS `.gitignore`.

---

## SA-011 — Database queue/session/cache defaults vs MVP

**Severity:** Medium  
**Affected component:** leftover `.env.example` (`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`)  
**Attack scenario:** Checkout is later dispatched to a queue worker that fails silently; sale shows success in UI while stock is not committed (CURSOR.md §13). Session table theft if DB backups are looser than file permissions. Redis blocks in `.env.example` invite adding Redis without need.  
**Impact:** Financial inconsistency; extra attack surface.  
**Recommended fix:** `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file` for MVP. Never queue checkout.  
**Status:** **Fixed** in leftover `.env.example`.

---

## SA-012 — Session cookie `secure` unset

**Severity:** Medium  
**Affected component:** `config/session.php` (`SESSION_SECURE_COOKIE` empty)  
**Attack scenario:** Production HTTP (no HTTPS yet) sends session cookie in cleartext; network attacker steals Owner session. SRS §31.1 requires HTTPS in production.  
**Impact:** Session hijack.  
**Recommended fix:** Terminate TLS at the VPS; set `SESSION_SECURE_COOKIE=true` in production `.env`. Keep `http_only=true`, `same_site=lax` (already Laravel defaults).

---

## SA-013 — CSRF / XSS / SQLi (framework baseline, unimplemented UI)

**Severity:** Medium (residual if later code bypasses Blade/Eloquent)  
**Affected component:** Future Livewire/Blade/SQL  
**Attack scenario:** `{!! $product->name !!}` on POS; concatenated `whereRaw("name like '%$q%'")`; Livewire without CSRF.  
**Impact:** XSS, SQLi, CSRF state change.  
**Recommended fix:** Keep `@csrf` / Livewire defaults; `{{ }}` only; Eloquent bindings; escape `LIKE` wildcards in TASK-009.

---

## SA-014 — Audit JSON may capture passwords

**Severity:** Medium (when TASK-005/003 ship)  
**Affected component:** `activity_logs.old_values` / `new_values`  
**Attack scenario:** `UpdateUser` dumps `$user->getChanges()` including `password` hash or plaintext into audit, visible to Owner on TASK-021.  
**Impact:** Offline hash cracking; credential leak via backup of `activity_logs`.  
**Recommended fix:** Allow-list audit fields (role, active, name, email). Never log password or remember_token.

---

## SA-015 — Backup files in the web root

**Severity:** Medium (operations)  
**Affected component:** VPS backups (SRS §32; no in-app backup)  
**Attack scenario:** `public/backup.sql` or `storage/app/public/dump.sql` is world-readable.  
**Impact:** Full database disclosure including hashes and sales.  
**Recommended fix:** Store backups outside the document root; no public URL; encrypt at rest if possible.

---

## SA-016 — No security headers documented for VPS

**Severity:** Low  
**Affected component:** Web server (SRS §31.12)  
**Attack scenario:** Missing `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options` enables clickjacking of POS.  
**Impact:** UI redress; mixed with XSS.  
**Recommended fix:** Nginx/Caddy headers at deploy time; Laravel `security` middleware if using Laravel 11+ defaults.

---

## SA-017 — Dependency advisories (current skeleton)

**Severity:** Low / Informational  
**Affected component:** leftover `composer.lock`  
**Attack scenario:** Known CVE in Laravel or transitive packages.  
**Impact:** Depends on CVE.  
**Recommended fix:** Re-run `composer audit` after every `composer update`.  
**Status:** **Clear** as of 2026-08-28 (`No security vulnerability advisories found`).

---

## SA-018 — API and webhook surface

**Severity:** Informational (accepted)  
**Affected component:** N/A (ADR-10, SRS §26–27)  
**Attack scenario:** Accidental `routes/api.php` with unauthenticated JSON. Leftover `bootstrap/app.php` still treats `api/*` as JSON.  
**Impact:** None until an API is added.  
**Recommended fix:** Do not add a public API. If added later, separate auth, throttle, and documentation.

---

## SA-019 — Tenant isolation

**Severity:** Informational (accepted)  
**Affected component:** N/A (BR-21, ADR-09)  
**Attack scenario:** Multi-tenant IDOR via `tenant_id` — not in schema by design.  
**Impact:** N/A for single-store MVP.  
**Recommended fix:** Do not add `tenant_id` preemptively. A future SaaS version needs a dedicated isolation review.

---

# 4. Control coverage matrix

| Area | Evidence | Result |
|---|---|---|
| Authentication | Docs + TASK-002; no login code | **Not implemented** |
| Authorization | TASK-004 gates; no Policies in app | **Not implemented** |
| IDOR | Specified for transactions | **Gap until TASK-014** |
| Mass assignment | Leftover User fillable name/email/password | **OK until role added** |
| SQL injection | No custom SQL | **N/A (scaffold Eloquent only)** |
| XSS | Laravel welcome Blade; no user content | **N/A pending catalog** |
| CSRF | Laravel web middleware default | **OK if web stack kept** |
| File uploads | Logo not built | **Gap until TASK-008** |
| Path traversal | Logo path rules in CURSOR.md | **Must implement** |
| Rate limiting | Specified, not coded | **Gap until TASK-002** |
| Brute force | Same as rate limit | **Gap** |
| Password handling | `hashed` cast on leftover User; hasher default bcrypt/argon | **OK in skeleton** |
| Session security | httpOnly + SameSite=lax; Secure unset | **Harden in production** |
| Sensitive data exposure | Debug true locally | **Production must disable** |
| Environment configuration | No SimplePOS `.env`; leftover `.env` exists off-repo | **Do not copy `.env`** |
| Debug mode | `APP_DEBUG=true` local | **Forbidden in production** |
| API security | No API | **Accepted** |
| Webhook security | No webhooks | **Accepted** |
| Business logic | Checkout unspecified in code | **Critical gap** |
| Privilege escalation | Owner protection documented | **Must test TASK-003/004** |
| Tenant isolation | Single store | **Accepted** |
| Financial transaction integrity | Atomic checkout required, not coded | **Critical gap** |
| Logging of secrets | Rules in TASK-005 | **Must enforce in code** |
| Backup exposure | Ops requirement only | **Deploy checklist** |
| Dependency vulnerabilities | `composer audit` clean | **Watch** |

---

# 5. Production checklist (before first customer VPS)

```text
[ ] App lives in SimplePOS repo, not _simplepos_laravel_tmp
[ ] APP_ENV=production, APP_DEBUG=false
[ ] HTTPS only; SESSION_SECURE_COOKIE=true
[ ] MySQL 8 with migrations matching DATABASE.md
[ ] Login throttled; inactive users rejected
[ ] Policies on every write and sensitive read
[ ] Checkout atomic + locked stock; no client prices
[ ] Logo MIME/size/name hardening; no SVG
[ ] .env and APP_KEY never in git
[ ] Backups off the web root
[ ] composer audit clean
[ ] AC-01–14 and cashier-denied tests green
```

---

# 6. Fixes applied this audit

| Change | Why |
|---|---|
| `/.gitignore` in SimplePOS | Prevent committing `.env`, keys, vendor |
| Leftover `.env.example` drivers/comments | Align with file session, sync queue, file cache, MySQL, production debug warning |
| Leftover `.gitignore` no longer ignores `.cursor/` | Keep task/security instructions versioned if the skeleton is copied |

No application business logic was rewritten (none exists in-repo).

---

# 7. Residual risk

Until authentication, authorization, and atomic checkout exist **in this repository**, SimplePOS must not be exposed to untrusted networks. The leftover Laravel directory is a generic framework install, not a POS, and remains a deployment hazard if used as-is.
