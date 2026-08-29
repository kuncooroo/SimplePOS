# Commercial Release Playbook

## SimplePOS — Sistem Kasir & Penjualan Sederhana

| Field | Value |
|---|---|
| Document | `docs/COMMERCIAL_RELEASE.md` |
| Date | 2026-08-29 |
| Role | Software Product Manager (commercial source-code products) |
| Product edition | Version **1.0.0** Core MVP |
| Distribution model | **Licensed Laravel source** for **one store / one production install** (not SaaS) |
| Related | `PRD.md`, `docs/INSTALLER.md`, `docs/DEMO_MODE.md`, `docs/USER_GUIDE.md`, `docs/UPDATE_STRATEGY.md`, `docs/RELEASE_AUDIT.md` |

This playbook is how SimplePOS is **positioned, packaged, sold, and supported**. It is not a substitute for engineering completion.

**Ship gate:** Do not publish a paid ZIP, marketplace listing, or public demo until `docs/RELEASE_AUDIT.md` is **READY FOR RELEASE**. As of 2026-08-29 the audit verdict is **NOT READY** (no application in the repository). Use this document to build the commercial wrapper **in parallel** with TASK-001–028.

Fill every `[BRACKET]` before the first public listing.

---

# 1. Product name

| Use | Name |
|---|---|
| **Full name** | SimplePOS — Sistem Kasir & Penjualan Sederhana |
| **Short name** | SimplePOS |
| **Edition line** | SimplePOS 1.0 Core |
| **SKU / ZIP stem** | `SimplePOS-1.0.0-source` |
| **Do not use** | “SimplePOS Cloud”, “SimplePOS SaaS”, “ERP”, “Kasir AI” |

The Indonesian subtitle is the market promise: a **simple cash register and sales system**, not a platform.

Trademark and domain: `[BRAND_LEGAL_NAME]`, `[PRODUCT_DOMAIN]` (e.g. marketing site). Demo host: `[DEMO_URL]`.

---

# 2. Product positioning

**Category:** Commercial **source-code** Point of Sale for **one retail store**, deployed on the buyer’s VPS.

**One-line:**  
Own a focused Laravel cash POS: sell in the browser, keep stock honest, keep history when prices change — without a SaaS rent or an ERP project.

**Positioning statement:**

> For small Indonesian (and similar) retailers and the developers who deploy for them, SimplePOS is a **single-store, cash-first POS you install yourself**. Unlike monthly SaaS tills, you **keep the source and the database**. Unlike generic “Laravel POS” scripts, checkout, stock, and the invoice are **one database transaction**, roles are **three and enforced**, and the installer **locks** so the wizard cannot take over the shop.

**Not positioned as:**

- Multi-outlet / franchise SaaS
- Accounting, payroll, or purchasing
- Online store or marketplace sync
- Card/e-wallet acquirer
- White-label unlimited resale of the unmodified product (see §15)

**Price architecture (do not invent a number in git):** `[LIST_PRICE]` per **production store**, `[UPDATE_TERM]` of patches included, optional `[INSTALL_SERVICE]`.

---

# 3. Target buyers

Two wallets; one product.

### 3.1 Primary: source-code buyer (pays)

| Segment | Who | Why they buy |
|---|---|---|
| **Freelance / agency** | Laravel implementer for a warung, toko kelontong, ATK, mini market | Deliver a predictable POS without building checkout integrity from scratch |
| **In-house IT / owner-operator with a sysadmin** | One shop, one VPS | Stop Excel/WhatsApp sales; keep data on their server |
| **Reseller (licensed)** | Local IT that installs many shops | `[RESELLER_TERMS]` — usually **one license per live store**, not one license for unlimited shops |

### 3.2 Users (do not pay; must love the till)

Owner, Administrator, Cashier — PRD §8. Marketing must show the **Cashier** screen first; owners buy what cashiers will actually use.

### 3.3 Not a fit (say so on the sales page)

- Chains that need **one database for many branches**
- Restaurants (tables, KDS)
- Shops that **must** take cards/QRIS **inside** the app in v1.0
- Buyers who want **hosted SaaS** with no server
- Buyers who expect **refunds/voids** in v1.0

Honest “not for you” copy reduces refunds and 1-star “where is multi-store?” reviews.

---

# 4. Unique selling points

Lead with **proof**, not adjective stacks.

1. **You own the running system** — source + MySQL on your VPS; no tenant lock-in.
2. **Checkout you can trust** — completed sale, line snapshots, stock drop, and stock movement in **one** commit; failed pay does not leave a half-sale (PRD AC-05/06/07/14).
3. **History survives catalog edits** — old receipts keep sale-time name and price (AC-08).
4. **Three roles, protected Owner** — Administrator cannot seize the Owner account (PRD §20).
5. **Cashier-speed POS** — search, cart, discount, cash, change; no ERP menus on the till.
6. **Runs on a small VPS** — PHP + MySQL; **no Redis, no queue worker** for selling.
7. **Installer that ends** — browser wizard, then `/install` is gone (404), not a permanent backdoor.
8. **IDR-first retail** — timezone and money as decimals; store identity in settings, not in code.
9. **Documented upgrades** — SemVer, migrate forward, never `migrate:fresh` on a live shop (`docs/UPDATE_STRATEGY.md`).
10. **Narrow on purpose** — v1.0 refuses accounting, e-commerce, and gateways so the core stays shippable.

---

# 5. Feature list

### Version 1.0 (sell this, only this)

| Area | Features |
|---|---|
| Access | Login/logout, active users only, Owner / Administrator / Cashier |
| POS | Search name/SKU/barcode, cart, line qty, sale discount, cash tender, change, print receipt |
| Catalog | Categories, products (SKU, optional barcode, prices, stock, active) |
| Inventory | Current/low stock, manual adjust with reason, movement history |
| Records | Transaction list (cashier: own), invoice search, snapshots |
| Management | Users (no hard delete), store settings, logo, receipt footer |
| Insight | Dashboard (today), sales report, product sales; Owner activity log |
| Ops | Web + artisan install, installer lock, file sessions, sync queue |

### Explicitly not in the 1.0 listing (do not put on the marketplace feature grid)

Refunds/voids, payment gateways, email/WhatsApp receipts, password-reset mail, public API, multi-store, product photo catalog, CSV/PDF export, in-app backup button, Redis, kitchen/restaurant, native mobile app.

Roadmap teaser (optional, labeled **future**): PRD v1.5–2.0 candidates — import, export, basic returns — **not** promised in the 1.0 invoice.

---

# 6. Minimum server requirements

Publish this on the sales page and in the ZIP `README`.

| Item | Minimum for 1.0 |
|---|---|
| OS | Linux VPS (Ubuntu LTS typical) |
| Web | Nginx or Apache; **document root = `public/`** |
| PHP | Version required by the shipped `composer.json` (Laravel 13; **PHP 8.3+** as locked — verify at tag) |
| Extensions | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `json` |
| Database | MySQL 8.x, empty schema, `utf8mb4` |
| Node | Only to **build** assets (`npm ci && npm run build`) unless you ship `public/build` |
| RAM / CPU | `[VPS_SIZE — e.g. 1–2 vCPU, 2 GB RAM]` for one quiet shop |
| SSL | Strongly recommended |
| Not required | Redis, Elasticsearch, queue daemon, MongoDB |

Buyer checklist: Composer, SSH, ability to create a database. Non-developers need `[INSTALL_SERVICE]` or a host that offers Laravel-style deploys.

---

# 7. Installation experience

**Promise:** After files are on the server and `public/` is the site root, a non-developer can finish setup in the browser.

1. Upload / `git` the package (without overwriting a future `.env`).
2. `composer install --no-dev` and frontend build **or** use a ZIP that already contains `vendor/` + `public/build` (see §17).
3. Open `https://shop.example/install`.
4. Pass checks → database → URL/timezone → Owner account → store name → optional sample data **off** for live shops.
5. Land on **login**. `/install` must not work again.

SSH alternative: `php artisan simplepos:install`.

Support script: if `/install` 404s after a real install, that is **success**, not a bug (`docs/INSTALLER.md`).

Do not promise “one-click cPanel install” unless you actually test and ship an installer for that panel.

---

# 8. Demo strategy

| Channel | Purpose | Rules |
|---|---|---|
| **Hosted public demo** | Pre-sale try POS | Dedicated host, `DEMO_MODE=true`, reset every 2 hours, Layer A denies (`docs/DEMO_MODE.md`) |
| **Promo video** | Buyers who will not log in | 90–150 s cashier journey |
| **Screenshots** | Marketplace + landing | Staging dummy data only |
| **Installer sample data** | Post-sale practice | `--demo` / wizard checkbox; **`DEMO_MODE=false`** |

Never put a customer’s shop in demo mode. Never reuse demo emails as installer defaults.

Go-live of `[DEMO_URL]`: only after RELEASE_AUDIT **READY** **and** demo denies + reset cron proven.

---

# 9. Screenshot requirements

Capture on staging with **dummy** Indonesian retail data (IDR). No real customer names. UI: operational, not a marketing landing inside the app.

| # | Shot | Why it sells |
|---|---|---|
| 1 | Login + store name | First impression |
| 2 | **POS full screen** (hero) | Cashier speed |
| 3 | POS payment (cash, change) | Trust in totals |
| 4 | Printed-style receipt | “We give struk” |
| 5 | Dashboard today | Owner vanity + utility |
| 6 | Products list | Catalog is real |
| 7 | Stock adjustment + reason | Inventory seriousness |
| 8 | Sales report date range | Not a toy |
| 9 | Users + roles | Agency talking point |
| 10 | Settings / logo | White-label-ish identity |
| 11 | Cashier sidebar vs Owner sidebar | Permission story |
| 12 | Installer welcome (optional) | Source-product proof |

Also: one **tablet-width** POS crop (primary register device).

Export: PNG, ≥1400px wide, no debug bar, no `APP_DEBUG` traces, no `.env`. Store under `docs/images/marketing/` and `docs/images/user-guide/`.

---

# 10. Promo video requirements

**Length:** 90–150 seconds (marketplace cap often 2–3 min; keep it short).

**Script beat sheet:**

1. 0–10s — Problem: notebook / WhatsApp / “berapa omzet hari ini?”
2. 10–25s — SimplePOS on a tablet: search → cart
3. 25–50s — Cash, change, receipt (no cut away from POS)
4. 50–70s — Owner: dashboard + one report
5. 70–90s — Admin: product + stock adjust (reason visible)
6. 90–110s — “Source on your VPS; three roles; cash POS”
7. 110–end — CTA: `[DEMO_URL]` + “Bukan SaaS cabang banyak / bukan refund di v1.0”

**Audio:** Indonesian voice-over primary; English captions optional.  
**Do not:** stock footage of shopping malls, AI-generated fake UI, feature list voice-over of 20 modules you do not ship.  
**File:** `[PRODUCT]-1.0-demo.mp4`, 1080p, no background music drowning speech.

---

# 11. Documentation requirements

Ship **inside the ZIP** (customer-facing):

| File | Audience |
|---|---|
| `README.md` | What it is, requirements, install pointers |
| `LICENSE.txt` | `[LICENSE_NAME]` — human-readable + legal |
| `CHANGELOG.md` | 1.0.0 notes |
| `docs/USER_GUIDE.md` | Owner/cashier/operator |
| `docs/INSTALLER.md` | Wizard + lock (or a short `INSTALL.md` that points to it) |
| `.env.example` | Keys, no secrets |

Do **not** ship to customers (keep in vendor repo only):

- `.cursor/tasks/` (optional: omit from ZIP to reduce noise)
- `docs/RELEASE_AUDIT.md` (internal)
- Internal security audit with leftover-path details if it names private hosts
- `CURSOR.md` engineering rules (optional include for agencies — **recommended include**; it reduces “please add microservices” tickets)

Fill USER_GUIDE `[SUPPORT_EMAIL]` before the ZIP is cut.

Screenshots in the user guide: either add files or delete the appendix so the PDF is not full of empty captions.

---

# 12. Support policy

Publish this **verbatim** on the sales page (tune hours). Placeholders:

| Item | Policy |
|---|---|
| Channel | Email `[SUPPORT_EMAIL]` (tickets, not WhatsApp-as-SLA unless you staff it) |
| Hours | `[SUPPORT_HOURS]` `[TIMEZONE]` |
| Language | `[Bahasa Indonesia / English]` |
| Included | Installer wizard failures, documented bugs in 1.0, “how do I deactivate a cashier” |
| Not included | Free custom features, server hardening, nginx from scratch, accounting advice, data recovery without a backup |
| Severity | Till down (cannot sell) vs how-to vs enhancement |
| First response | `[e.g. 1 business day]` |
| Fix | Best effort on PATCH within `[UPDATE_TERM]`; no 24/7 unless `[SLA_PAID]` |
| Refund | `[MARKETPLACE_REFUND_WINDOW]` — after a working install, source is delivered; refunds follow platform rules |

**What a ticket must contain:** URL (not passwords), role, time, invoice number if a sale, exact message. See USER_GUIDE §22.

Agencies: you support the **product**; they support their **client** unless a separate AMS contract exists.

---

# 13. Update policy

Align with `docs/UPDATE_STRATEGY.md`. Customer-facing summary:

- Versioning: **SemVer** `MAJOR.MINOR.PATCH`
- Included: patches and documented minors during `[UPDATE_TERM]` via `[DOWNLOAD_PORTAL / email ZIP]`
- Upgrade: SSH `migrate --force`; **never** `/install` again; **never** `migrate:fresh`
- Backup before every upgrade
- Major versions: may be paid (`[MAJOR_UPGRADE_POLICY]`)
- No obligation to take a major that adds restaurant/multi-store
- Deprecated UI: announced in a minor, removed in a major

Changelog is part of the product, not a blog afterthought.

---

# 14. Licensing considerations

**This is not legal advice.** Have `[COUNSEL]` issue `LICENSE.txt`. Product decisions:

| Topic | Recommended commercial default |
|---|---|
| What is sold | License to use **this** SimplePOS source for **one production store** (one live `APP_URL` / one production DB) |
| Staging | One staging copy per license, not public as a second shop |
| Source | Buyer **receives** source; they may **customize** for that store |
| Resale | **Forbidden** to republish SimplePOS (modified or not) as a competing marketplace item |
| SaaS | **Forbidden** to host SimplePOS as a multi-tenant paid service without a **separate** OEM/SaaS license |
| Laravel/MIT deps | Remain under **their** licenses; you do not relicense the framework |
| Attribution | Keep Laravel and JS license files in `vendor/` / `public/` as required |
| Seat count | Unlimited cashiers **in that store**; license is per **store**, not per user |
| Encoded ionCube | **Not recommended** for this product — buyers expect readable Laravel. If a marketplace demands encoding, it fights the “source” story |
| License keys / phone-home | **Not in v1.0** (installer spec forbids). Trust contract + invoice |
| Demo credentials | Not a license; public by design |

Invoice line: `SimplePOS 1.0.0 — 1 production installation — [LICENSE_ID]`.

---

# 15. Product package structure (vendor repo vs customer)

**Vendor (you) keeps:** git, tasks, audits, unreleased docs, demo host secrets.

**Customer package** is a **clean tree** of the tagged application + customer docs.

```text
SimplePOS/                          # after unzip
├── README.md
├── LICENSE.txt
├── CHANGELOG.md
├── .env.example
├── app/
├── bootstrap/
├── config/                  # includes simplepos.php version
├── database/migrations/
├── database/seeders/        # no production passwords
├── public/                  # index.php; optionally public/build
├── resources/
├── routes/
├── docs/
│   ├── USER_GUIDE.md
│   ├── INSTALLER.md
│   └── images/              # optional
├── composer.json
├── composer.lock
├── package.json
└── (vendor/  — only in the “full” SKU)
```

**Never in the ZIP:** `.env`, `storage/logs/*`, `auth.json`, customer dumps, `node_modules` (unless you have a reason), `.git/` (optional: omit; agencies like git — prefer **tag download without `.git`** plus lockfiles).

---

# 16. Release ZIP structure

Cut from a **release tag** `v1.0.0` after CI tests (P0) pass.

### SKU A — Source (developers) — default

```text
SimplePOS-1.0.0-source.zip
```

No `vendor/`, no `node_modules`, **include** `public/build` if you want zero-Node deploys; otherwise document `npm run build`. Smaller, Composer-reproducible.

### SKU B — Deploy (optional)

```text
SimplePOS-1.0.0-deploy.zip
```

Adds `vendor/` (`--no-dev`) + `public/build`. For buyers who will not run Composer. Larger; regenerate every patch.

### ZIP hygiene

```text
# exclude
.git, .env, storage/logs, storage/framework/sessions,
.phpunit.cache, tests/ (optional: INCLUDE tests — agencies trust tested source)
```

**Recommendation:** **include `tests/`** in SKU A. It is a commercial differentiator (“this POS has checkout tests”). Exclude only if the marketplace file-size cap forces it.

Name the inner folder `SimplePOS/` so unzip is predictable.

Checksum: publish `SHA256` next to the download.

---

# 17. Demo credentials

Public, hashed in the demo DB, shown on `[DEMO_URL]/login`. **Not** for customer installs.

| Role | Email | Password (published) |
|---|---|---|
| Owner | `owner@demo.simplepos.local` | `Demo-Owner-2026` |
| Administrator | `admin@demo.simplepos.local` | `Demo-Admin-2026` |
| Cashier | `cashier@demo.simplepos.local` | `Demo-Kasir-2026` |

Banner: data resets every **2 hours**; not a live shop; settings/users/passwords locked.

If credentials rotate, change this doc, the login banner, and the marketing page **the same day**.

---

# 18. Marketing page structure

Single long page or short page + docs. Order:

1. **Hero** — SimplePOS name, one-line, POS screenshot, CTA: Try demo / Buy source  
2. **Not SaaS / not ERP** — two sentences  
3. **Cashier journey** — 3 stills or embedded video  
4. **Owner trust** — dashboard, reports, snapshots, roles  
5. **For developers** — Laravel 13, MySQL, no Redis, installer lock, SemVer  
6. **Feature grid** — only §5 1.0 items  
7. **Requirements** — table from §6  
8. **What’s not included** — refunds, gateways, multi-store (builds trust)  
9. **Demo credentials** — copy-paste  
10. **Package** — what is in the ZIP  
11. **Support & updates** — §12–13  
12. **License** — one store, no SaaS resale  
13. **FAQ** — §19  
14. **Footer** — `[COMPANY]`, `[SUPPORT_EMAIL]`, legal  

SEO: Bahasa Indonesia primary if `[MARKET]` is ID; English if CodeCanyon global. Do not rank for “aplikasi restoran” or “kasir QRIS” in v1.0.

---

# 19. FAQ (sales)

**Is this a monthly subscription?**  
No. You buy a source license and run it on your server.

**Does it work without internet?**  
It is a web app. It needs a network to the VPS. It is not an offline-first Windows installer.

**Can I use it in many cabang?**  
Not in 1.0. One store per install/license.

**QRIS / kartu / e-wallet?**  
Not in 1.0. Cash only. You can customize later (your developer, your risk).

**Refund / batal transaksi?**  
Not in 1.0. Completed sales are kept.

**Do I need Redis?**  
No.

**Do I need email (SMTP)?**  
No for selling and logging in.

**Can my programmer change it?**  
Yes, for your licensed store. They may not republish it as their POS product.

**Is there a default admin/admin?**  
No. You create the Owner in the installer.

**Will updates wipe my sales?**  
Documented upgrades must not. You still backup first. We never tell you to `migrate:fresh` on production.

**Laravel version?**  
Shipped 1.0 tracks the tagged `composer.lock` (Laravel 13.x at design time — confirm on the tag).

**Demo passwords work on my shop?**  
No. Demo is a different host. Your Owner password is the one you set at install.

---

# 20. Pre-sale questions

Qualify **before** taking payment (form, chat, or sales call):

1. One physical store or many locations?  
2. Cash-only for v1.0 acceptable?  
3. Who will install (you / freelancer / us as `[INSTALL_SERVICE]`)?  
4. Do you have a VPS with PHP + MySQL and HTTPS?  
5. Need refunds or QRIS on day one? (If yes → **do not sell 1.0** as a fit.)  
6. Need restaurant tables / KDS? (If yes → no.)  
7. Developer familiar with Laravel, or do they need SKU B + install service?  
8. Expected items in catalog (hundreds vs tens of thousands)?  
9. Language of the till (ID/EN)?  
10. Deadline — is the app already **READY FOR RELEASE** internally?

If (5) or (6) is mandatory, walk away or sell a **future** edition, not a lie.

---

# 21. Post-sale support workflow

```text
Payment confirmed
  → Issue LICENSE_ID + invoice
  → Deliver ZIP (portal) + SHA256 + tagged version
  → Welcome email: USER_GUIDE, installer, “don’t use /install after success”
  → Optional: schedule install service
```

**Ticket states:** New → Waiting on customer → In progress → Resolved / Won’t fix (out of scope) / Escalated engineering.

**Triage:**

| Symptom | First check |
|---|---|
| Cannot open site | Docroot `public/`? |
| `/install` missing | Already installed? → login |
| Cannot log in | Inactive user? Owner password they chose? |
| Sale failed | Stock? Empty cart? Cash &lt; total? |
| CSS broken | `public/build` / `npm run build` |
| “Need refund module” | Out of 1.0 — enhancement backlog, not a defect |

**Handoff to engineering:** reproducible steps, version from Settings / `simplepos:version`, no `.env` in the ticket.

**Close:** customer confirms till works **or** documented out-of-scope.

Do not remote-debug by setting `APP_DEBUG=true` on a public URL.

---

# 22. Differentiation from generic competitors

| Competitor type | Typical weakness | SimplePOS contrast |
|---|---|---|
| **CodeCanyon “Laravel POS”** | Feature dump, Vue/API sprawl, checkout in Livewire without row locks, Spatie for three roles, installer always on | **Narrow 1.0**, atomic sale+stock, **three roles**, installer **404 after setup**, tests as part of the story |
| **SaaS kasir (Olsera, Moka, Majoo, …)** | Monthly fee, data on vendor cloud, limited customization | **Source + your MySQL**; you can hire a Laravel dev; you accept **ops burden** |
| **Odoo / ERP** | Long project, cashier drowned in modules | **Till-first**; no GL/payroll |
| **Excel / buku** | No concurrent stock, no audit | Real invoices, roles, movements |
| **Abandoned OSS POS** | Unclear money types, PHP 7 | Maintained SemVer, decimal money, Laravel 13 target |
| **Custom quote from an agency** | 3–6 months, checkout bugs | **Productized** behavior + docs + upgrade policy |

**Messaging rule:** Do not claim “more features than SaaS.” Claim **clearer cash POS, honest history, and ownership**. Feature-list wars against Majoo are how source products get 1-star reviews.

**Proof assets** (when they exist): P0 test names in `CHANGELOG` (“insufficient stock does not reduce qty”), screenshot of Owner vs Cashier nav, installer lock explanation.

---

# 23. Commercial launch checklist

Do not list until **READY FOR RELEASE**:

- [ ] `docs/RELEASE_AUDIT.md` READY  
- [ ] Tag `v1.0.0`, `CHANGELOG.md`, `LICENSE.txt`  
- [ ] SKU A ZIP + SHA256; optional SKU B  
- [ ] `[DEMO_URL]` with published credentials + 2h reset  
- [ ] Hero POS screenshot + video  
- [ ] Sales page with **not included** section  
- [ ] `[SUPPORT_EMAIL]` staffed  
- [ ] Pre-sale qualifier in use  
- [ ] Price and license one-pager signed by `[COUNSEL]`  

Until then, this file is the **commercial specification**, not an authorization to sell.
