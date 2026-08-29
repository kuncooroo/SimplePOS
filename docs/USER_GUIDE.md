# SimplePOS User Guide

## Sistem Kasir & Penjualan Sederhana

| Field | Value |
|---|---|
| Document | `docs/USER_GUIDE.md` |
| Product | SimplePOS — Sistem Kasir & Penjualan Sederhana |
| Edition | Version 1.0 (Core MVP) |
| Audience | Store owners, administrators, cashiers, and technical operators who deploy the source on a VPS |
| Related | `docs/INSTALLER.md`, `docs/DEMO_MODE.md`, `PRD.md` |

This guide describes **how to install, configure, and operate** SimplePOS as a commercial, single-store Point of Sale. It is written so a non-developer can run the store day to day, and so a developer or sysadmin can deploy and maintain the server.

In-app labels may appear in Indonesian or English depending on the build you received. Module names in this guide match the product: Dashboard, POS, Transactions, Products, Categories, Inventory, Reports, Users, Settings, Activity Log.

> **Screenshot:** Product login page with store name and Sign in button.  
> **Suggested file:** `docs/images/user-guide/00-login.png`

---

# 1. Introduction

SimplePOS is a **web-based cash register** for one retail store. Staff open it in a browser, sign in, and record sales. Completed sales are stored with item snapshots, reduce stock, and appear in history and reports.

It is distributed as **Laravel source code** that you install on your own VPS (or equivalent). It is **not** a multi-store SaaS, not an online shop, and not an accounting or ERP suite.

### Who uses it

| Role | Typical person | Main job in SimplePOS |
|---|---|---|
| **Owner** | Business owner | Full control: users, settings, reports, audit, POS |
| **Administrator** | Store manager / ops | Catalog, stock, reports, limited users and settings |
| **Cashier** | Kasir | Sell: POS, own transaction history, receipts |

The first account created at install is the **Owner**. That account is **protected**: an Administrator cannot create, promote, demote, or deactivate it.

### What Version 1.0 includes

- Sign in / sign out (email + password)
- Categories and products (SKU, barcode, price, stock)
- Cash POS: search, cart, discount, pay, receipt
- Transaction history (cashiers see their own sales)
- Inventory overview, manual stock adjustment, movement history
- Dashboard and sales reports (Owner / Administrator)
- Store settings and receipt footer / logo
- Activity log for Owner
- User management with three fixed roles

### What Version 1.0 does not include

Refunds and voids, card/e-wallet gateways, email/SMS/WhatsApp receipts, password-reset email, public API, multi-store, product photo catalog, CSV/PDF export, in-app backup button, Redis, or background jobs for checkout.

Those absences are intentional. Checkout, stock, and invoices are **saved immediately** in the database—not put on a queue.

---

# 2. Key Features

| Feature | Why it matters |
|---|---|
| **Fast cash checkout** | One POS screen from search to change due |
| **Server-calculated totals** | Prices and stock come from the database, not the cashier’s browser |
| **Stock follows sales** | A completed sale reduces quantity in the same save as the invoice |
| **Receipt with snapshots** | Printed names and prices stay as they were at sale time |
| **Roles** | Cashiers cannot open users, settings, inventory, or reports |
| **Store identity** | Name, address, logo, IDR display, receipt footer without editing code |
| **Dashboard** | Today’s sales, transaction count, low stock, best seller |
| **Reports** | Daily and date-range sales; product quantities sold |
| **Audit (Owner)** | Who changed users, stock, or settings |
| **Web installer** | Browser wizard after the files and database exist |

> **Screenshot:** POS screen with product search, cart, and pay panel.  
> **Suggested file:** `docs/images/user-guide/02-pos-overview.png`

---

# 3. System Requirements

Have these ready **before** you open `/install`. The wizard does not install PHP, Composer, Node, or MySQL for you.

### Server (typical VPS)

| Item | Requirement |
|---|---|
| OS | Linux VPS commonly used for Laravel (Ubuntu LTS is a usual choice) |
| Web server | Nginx or Apache; **document root = `public/`** only |
| PHP | Version required by `composer.json` (Laravel 13; **PHP 8.3 or newer** as locked in your package). Confirm with `php -v` |
| PHP extensions | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `json` |
| Database | MySQL 8.x, empty database, `utf8mb4` |
| HTTPS | Strongly recommended (see §22) |
| Disk | Enough for the app, logs, and logo uploads (warn if `storage/` has under ~100 MB free) |

Optional: `gd` / `exif` if you process images; not required if the logo is stored as uploaded.

### Workstation (cashier / admin)

| Item | Requirement |
|---|---|
| Browser | Current Chrome, Edge, Firefox, or Safari |
| Screen | Desktop or tablet for POS; phone is acceptable for admin lists, not as the primary register |
| Network | Stable connection to your server |

### What you run once on the server (technical)

```text
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

Or ship a release that already contains `vendor/` and `public/build` per your vendor’s package notes.

**Redis is not required.** Sessions, cache, and the queue default to **file** / **sync**.

> **Screenshot:** Hosting panel or SSH session showing PHP and MySQL versions (optional for internal runbooks).  
> **Suggested file:** `docs/images/user-guide/03-server-versions.png`

---

# 4. Installation

Two paths do the same business steps: **browser wizard** (`/install`) or **SSH** (`php artisan simplepos:install`).

Detailed engineer spec: `docs/INSTALLER.md`.

### 4.1 Upload and web root

1. Copy the SimplePOS project to the server (for example `/var/www/simplepos`).
2. Point the site’s document root at **`…/simplepos/public`**, not the project folder.
3. Create an empty MySQL database and a user with rights on that database only.
4. Ensure these folders are writable by the PHP user:

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

5. Run Composer and the frontend build (see §3) unless your package already includes built assets.

### 4.2 Browser wizard

1. Open `https://YOUR-DOMAIN/install` in a browser.
2. Follow the steps. Do not skip a failed check.

| Step | What you do |
|---|---|
| Welcome | Continue |
| System requirements | Continue only if all required checks pass |
| PHP extensions | Continue only if required extensions are loaded |
| Folder permissions | Continue only if listed folders are writable |
| Database | Enter host, port (usually `3306`), database name, username, password |
| Environment | App URL (`https://…`), timezone (e.g. `Asia/Jakarta`). Keep **debug off** in production |
| Application key | Created automatically; you will not see the full key |
| Migrations | Run database tables |
| Owner account | Your name, email, password (this is the **Owner**, not a junior admin) |
| Store settings | Store name; currency defaults to **IDR / Rp** |
| Demo data | Leave **off** for a real store. On only if you want sample products for practice |
| Finish | You are sent to the login page |

If a step fails, fix the cause and retry. The app is **not** marked installed until Finish succeeds.

> **Screenshot:** Installer welcome, then database form (password field empty in the capture).  
> **Suggested files:** `docs/images/user-guide/04-install-welcome.png`, `04-install-database.png`

### 4.3 Command-line install

```text
php artisan simplepos:install
php artisan simplepos:install --no-interaction
php artisan simplepos:install --demo
```

`--demo` loads sample catalog data. Do **not** use it on a live shop unless you intend to wipe that practice data.

The command **refuses** if the product is already installed or an Owner already exists.

### 4.4 After a successful install

- `/install` must **not** open again (the product returns a not-found page).
- Sign in with the Owner email and password you set.
- Link public storage if the installer did not: `php artisan storage:link` (needed for a logo on receipts).

Never run `migrate:fresh` or `db:wipe` on a store that has real sales.

---

# 5. Initial Configuration

Sign in as **Owner**, then complete the store identity before the first real sale.

### 5.1 Open Settings

1. Use the sidebar **Settings**.
2. Fill store information used on screen and on receipts.

| Field | Guidance |
|---|---|
| Store name | Shown in the app and on receipts |
| Address / phone / email | Contact lines on the receipt (email here is **printed text**, not a mail server) |
| Currency | Version 1.0 defaults to **IDR** and **Rp** |
| Receipt footer | Thank-you line, NPWP, or return policy text |
| Low-stock threshold | Products at or below this quantity appear as low stock |
| Logo | Optional image; see §14 |

Changing settings does **not** rewrite old invoices. Historical amounts stay as sold.

> **Screenshot:** Settings — store information form with Save.  
> **Suggested file:** `docs/images/user-guide/05-settings-store.png`

### 5.2 Timezone

Sales “today” on the dashboard use the application timezone (set at install, typically `Asia/Jakarta`). If “today” looks wrong, ask your operator to confirm `APP_TIMEZONE`—do not invent a second timezone in Settings (Version 1.0 keeps timezone in the server environment).

### 5.3 Categories and products

1. **Categories:** create groups (for example Minuman, Sembako). Keep at least one **active** category before adding products.
2. **Products:** SKU (unique), name, selling price, stock, category. Barcode optional (unique if used).
3. Mark items you do not sell as **inactive**. Inactive products do not appear as sellable in POS.

> **Screenshot:** Product form with SKU, price, stock.  
> **Suggested file:** `docs/images/user-guide/05-product-form.png`

### 5.4 Staff accounts

Create an Administrator and at least one Cashier (see §6–§7). Do not share the Owner password at the register.

---

# 6. Admin Setup

“Admin setup” in SimplePOS means: **Owner first**, then **Administrator** users for daily operations.

### 6.1 Owner (protected)

- Created only during installation (or equivalent artisan install).
- Full access, including **Activity Log** and assigning the Owner role (if the product allows a second Owner—default: treat extra Owners as exceptional).
- Administrators **cannot** edit, deactivate, or take over this account.

If you lose Owner access, recovery is an **operational** procedure (database/password hash via a trusted sysadmin). There is no in-app “reset Owner by email” in Version 1.0.

### 6.2 Create an Administrator

1. Sign in as Owner.
2. Open **Users** → create user.
3. Role: **Administrator**.
4. Set a unique email and a strong password. Leave **Active** on.
5. Sign out and confirm that account can open Dashboard, Products, and Settings, but **cannot** change the Owner user.

> **Screenshot:** User create form with role dropdown set to Administrator.  
> **Suggested file:** `docs/images/user-guide/06-create-admin.png`

### 6.3 What the Administrator should do first

1. Confirm store Settings (name, receipt footer, logo) if the Owner delegated that work.
2. Finish categories and products.
3. Create Cashier accounts (Administrators cannot assign **Owner**).
4. Walk one test cash sale on POS, then open the receipt and Transactions.

---

# 7. User Management

**Who:** Owner (full). Administrator (Cashiers and Administrators only—not the protected Owner). Cashier: no access.

### 7.1 Create a user

1. **Users** → add user.
2. Enter name, email (login ID), password, confirmation.
3. Choose role: Owner (Owner only), Administrator, or Cashier.
4. Active: on for staff who may sign in.

Emails must be unique.

### 7.2 Edit a user

You may change name, email, role (within policy), active flag, and optionally a new password. Leaving password blank keeps the current password.

### 7.3 Deactivate instead of delete

Version 1.0 **does not delete** users. Deactivate so they cannot sign in. Their past sales and audit rows remain.

- Confirm before deactivation.
- Do not deactivate your own account (avoids lockout).
- Administrators cannot deactivate the Owner.

### 7.4 Inactive login

Inactive accounts are rejected at sign-in without telling the visitor whether the email exists.

> **Screenshot:** Users table with role badges and active status.  
> **Suggested file:** `docs/images/user-guide/07-users-list.png`

---

# 8. Roles and Permissions

Three fixed roles. There is no custom permission builder.

| Capability | Owner | Administrator | Cashier |
|---|---|---|---|
| Dashboard (store-wide figures) | Yes | Yes | No |
| POS / complete cash sale | Yes | Yes | Yes |
| Transaction history | All | All | Own sales only |
| Receipt for allowed transactions | Yes | Yes | Own/allowed |
| Products / categories | Manage | Manage | POS search only |
| Manual stock adjustment | Yes | Yes | No |
| Stock movement history | Yes | Yes | No |
| Reports | Yes | Yes | No |
| Users | Full | Limited (not Owner) | No |
| Assign Owner role | Yes | No | No |
| Store settings | Yes | Yes (operational fields) | No |
| Activity log | Yes | No (default) | No |
| Delete a completed sale | No | No | No |

Hiding a menu is not security by itself. Opening a URL without permission must fail (typically **403**).

> **Screenshot:** Owner sidebar (full) vs Cashier sidebar (POS + Transactions).  
> **Suggested files:** `docs/images/user-guide/08-nav-owner.png`, `08-nav-cashier.png`

---

# 9. Module Guides

Use the sidebar. Items you are not allowed to see should not appear; if you bookmark them, expect access denied.

### 9.1 Sign in and profile

1. Open the site URL (not `/install` after setup).
2. Enter email and password → Sign in.
3. Owner/Administrator land on **Dashboard**; Cashier lands on **POS**.
4. **User menu** → Profile: view name, email, role. You may update your **name** and **password** (current password required). You cannot change your own **role** here.
5. Sign out from the user menu when leaving the register.

> **Screenshot:** User menu open — Profile and Sign out.  
> **Suggested file:** `docs/images/user-guide/09-user-menu.png`

### 9.2 Dashboard (Owner / Administrator)

Shows for the **current business day** (app timezone):

- Sales total
- Number of completed transactions
- Low-stock indicator
- Best seller

Zero sales shows zeros / empty states, not a stuck spinner.

Cashiers do not get this store-wide dashboard.

> **Screenshot:** Dashboard cards.  
> **Suggested file:** `docs/images/user-guide/09-dashboard.png`

### 9.3 POS

1. Open **POS**.
2. Search by name, SKU, or barcode.
3. Add lines; change quantity; remove lines. Stock rules apply (cannot sell more than available; inactive/out-of-stock items are not sellable).
4. Optional **discount** on the sale (rules enforced by the server).
5. Enter **cash received**. The app shows amount due and change. Payment method is **cash only**.
6. Confirm pay. Wait for success before starting the next customer (do not double-click).
7. Open or print the **receipt**.

Prices in the cart are verified on the server. You cannot complete an empty cart.

> **Screenshot:** Cart with discount and cash received.  
> **Suggested file:** `docs/images/user-guide/09-pos-payment.png`

### 9.4 Transactions

- **Owner / Administrator:** all completed sales; filter by date / cashier where offered.
- **Cashier:** own (allowed) sales only.

Open a sale for detail and receipt. You **cannot** edit or delete a completed transaction.

> **Screenshot:** Transaction list with invoice number and total.  
> **Suggested file:** `docs/images/user-guide/09-transactions.png`

### 9.5 Receipts

From the sale success screen or transaction detail: view and use the browser **Print** dialog. Receipts use store name, optional logo, item snapshots, totals, cash, change, and footer.

There is no email or WhatsApp send in Version 1.0.

> **Screenshot:** Receipt layout ready to print.  
> **Suggested file:** `docs/images/user-guide/09-receipt.png`

### 9.6 Categories

Create, search, edit, activate/deactivate. Deactivating does not erase products. New products cannot use an inactive category.

Cashiers have no category screens.

### 9.7 Products

Create and edit: SKU, optional barcode, name, category, selling price, optional cost, stock, active flag.

- SKU unique; barcode unique when filled.
- Selling price cannot be negative.
- Deactivate instead of deleting if you stop selling an item.
- Changing today’s price does **not** change old receipts.

> **Screenshot:** Products table with search and active filter.  
> **Suggested file:** `docs/images/user-guide/09-products.png`

### 9.8 Inventory

**Overview:** current quantities; highlight low stock using the Settings threshold.

**Adjust stock (Owner / Administrator):**

1. Choose the product.
2. Enter a signed change (for example +10 or −2).
3. Enter a **reason** (required).
4. Confirm. Stock cannot go below zero.

The adjustment, movement row, and audit entry are saved together.

**Movement history:** sales deductions and manual adjustments with before/after quantity.

Cashiers cannot adjust stock.

> **Screenshot:** Stock adjustment dialog with reason field.  
> **Suggested file:** `docs/images/user-guide/09-stock-adjust.png`

### 9.9 Users and Settings

See §6–§7 and §5. Settings include store information, receipt, and localization (currency display).

### 9.10 Activity Log (Owner)

Read-only list of important changes (for example user status, stock adjustment, settings). Cashiers and (by default) Administrators do not have this module.

> **Screenshot:** Activity log table with actor and time.  
> **Suggested file:** `docs/images/user-guide/09-audit.png`

---

# 10. Business Workflows

### 10.1 Opening the day

1. Owner/Admin: glance at Dashboard (low stock, yesterday vs today if you open reports).
2. Confirm Cashiers are **Active**.
3. Cashier: sign in on the register browser, open POS.

### 10.2 Standard cash sale

1. Identify the item (search or barcode).
2. Set quantities.
3. Apply discount if policy allows.
4. Take cash; enter amount received; confirm change with the customer.
5. Complete sale; offer receipt (print if required).
6. Next customer.

If stock is insufficient, the sale **must not** complete. Correct the cart or adjust stock (authorized role) first.

### 10.3 After a price or name change

Sell as usual. Old invoices keep the **old** name and unit price on each line. Reports that use snapshots follow what was sold, not the new catalog price.

### 10.4 New product mid-day

Admin creates the product with opening stock → it becomes searchable on POS when **active**. No second “sync” step.

### 10.5 Wrong item on a completed sale

Version 1.0 has **no refund/void**. Operational practice: do not delete the invoice (the product forbids it). Handle compensation outside the system or wait for a future version that supports returns. Record a note in your shop process; the Activity Log will not invent a refund.

### 10.6 Staff leaves

Deactivate the user the same day. Historical sales stay under their name.

### 10.7 Closing the day

Owner/Admin: **Reports** for today; optionally compare Transactions list. Cashiers sign out.

---

# 11. Notifications

SimplePOS Version 1.0 uses **on-screen** messages only:

| Type | When you see it |
|---|---|
| Success toast | Saved user, completed sale, stock adjusted, settings saved |
| Validation errors | Missing reason, invalid email, password mismatch |
| Stock warnings | Not enough quantity; low-stock on dashboard/inventory |
| Access denied | Role cannot perform the action |

There are **no** push notifications, SMS, WhatsApp, or email alerts for sales or low stock.

If a message disappears, check the form fields; failed saves do not silently succeed.

> **Screenshot:** Success toast after checkout.  
> **Suggested file:** `docs/images/user-guide/11-toast-success.png`

---

# 12. Reports

**Who:** Owner and Administrator. **Not** Cashiers.

Figures include **completed** sales only. Totals should match the Transactions module for the same period.

### 12.1 Sales report

1. Open **Reports**.
2. Choose **daily** (one business day) or a **date range**.
3. Read total sales amount and transaction count for that period.

Empty periods show zero / empty, not fake sample numbers.

### 12.2 Product sales

Quantities (and line amounts from **snapshots**) per product for the selected period. Useful for what sold, not for revaluing old stock at today’s price.

### 12.3 Export

CSV/PDF export is **not** in Version 1.0. Use on-screen figures or your operator’s database backup for archives.

> **Screenshot:** Sales report with date range controls.  
> **Suggested file:** `docs/images/user-guide/12-sales-report.png`

---

# 13. File Management

Version 1.0 stores **one business upload**: the **store logo**.

### 13.1 Upload a logo

1. Sign in as Owner or Administrator.
2. **Settings** → logo file field.
3. Choose an allowed image type and stay within the size limit shown on the form.
4. Save. Invalid files **must not** replace a good existing logo.

The file is stored on the server (local disk). Public URL goes through `storage:link` (`/storage/...`), not through a random folder inside `public/` that you uploaded by FTP.

### 13.2 What is not uploaded

Product images, CSV imports, and arbitrary documents are out of Version 1.0. Do not dump files into `public/` to “add photos.”

### 13.3 Operator notes

- Back up `storage/app` (and the public storage link target) with the database.
- Do not serve the project root; only `public/`.
- Demo hosts reject logo replacement (`docs/DEMO_MODE.md`).

> **Screenshot:** Settings logo preview and file picker.  
> **Suggested file:** `docs/images/user-guide/13-logo-upload.png`

---

# 14. Email Configuration

**Version 1.0 does not send email.** Login, checkout, receipts, and reports work without SMTP.

| Topic | Version 1.0 behavior |
|---|---|
| Password reset by email | Not provided — use a known password or Owner/Admin set a new password on **Users** |
| Receipt by email | Not provided — print or screenshot |
| Store “email” in Settings | Text printed on receipts only |
| `MAIL_*` in `.env` | Not required for the product to run. Leave mailer unused or `log`/`array` |

If a future version adds mail, use Laravel’s mail settings and **never** commit SMTP passwords to git. This guide will be updated then.

---

# 15. Cron Configuration

Version 1.0 **does not require** a cron job for sales, stock, or reports.

You may still add the standard Laravel scheduler **later** (backups, log cleanup). Typical Linux crontab entry (adjust paths and user):

```text
* * * * * cd /var/www/simplepos && php artisan schedule:run >> /dev/null 2>&1
```

Until you register real schedules in `routes/console.php` (or equivalent), this command does nothing harmful.

**Public demo hosts** (if you run one) use a separate reset command on a timer; that must **never** run against a customer shop. See `docs/DEMO_MODE.md`.

Customer stores: **do not** schedule `simplepos:demo-reset`.

---

# 16. Queue Configuration

Checkout, stock deduction, and invoice numbers run **in the same HTTP request**. They **must not** wait for a queue worker.

Recommended production `.env` (also set by the installer):

```text
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
CACHE_STORE=file
```

| Do | Don’t |
|---|---|
| Leave `sync` for Version 1.0 | Point checkout at Redis/database queues |
| Skip `php artisan queue:work` for core POS | Assume a stopped worker explains a missing sale |

If you later queue **non-critical** work (e.g. mail), keep sale completion on `sync` or an equivalent immediate path.

---

# 17. Backup

There is **no** “Backup” button in the app. Recoverability is your (or your host’s) operations duty.

### 17.1 What to copy

| Item | Why |
|---|---|
| MySQL database | Users, products, sales, stock, settings, audit |
| `storage/app` (including public uploads) | Logo and future files |
| `.env` | Secrets; store **offline**, never in the web root backup zip you leave on the same public disk unprotected |
| Application code | Your purchased source + any local patches |

Session files under `storage/framework/sessions` are optional (users simply sign in again).

### 17.2 Suggested rhythm

| Backup | Example policy (adjust to your risk) |
|---|---|
| Database | Daily automatic dump; keep ≥ 7 days |
| Files + `.env` | With each DB dump or whenever the logo changes |
| Off-site copy | Another machine or object storage, not only the same VPS |
| Restore test | Restore to a **staging** database at least once per quarter |

Exact frequency: define with your operator (**TBD** per deployment).

### 17.3 Restore (high level)

1. Put the store in maintenance or stop traffic.
2. Restore MySQL from the dump.
3. Restore `storage/app` and `.env`.
4. `php artisan storage:link` if needed.
5. Do **not** run the web installer again.
6. Smoke-test login, one POS sale on staging first when possible.

Never restore a backup onto the wrong shop’s database.

---

# 18. Updates

SimplePOS is source you deploy. Updates are **files + database migrations**, not `/install`.

### 18.1 Before you update

1. Backup (§17).
2. Read the release notes from your vendor for that version.
3. Test on a copy of the database when the change includes migrations.

### 18.2 Typical update steps (technical)

```text
# maintenance / brief downtime as you prefer
git pull
# or unpack the new source over the project, preserving .env
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
# then optimize for production if that is your standard
```

Do **not** open `/install` to “upgrade.” Do **not** `migrate:fresh`.

If `APP_KEY` changes, existing sessions (and encrypted values) break—only rotate the key on purpose.

### 18.3 After you update

Owner: sign in, complete a test sale, open a receipt, check Dashboard totals for today.

---

# 19. Troubleshooting

| Symptom | What to try |
|---|---|
| Site shows the project folder listing or Laravel error about `public` | Document root is wrong; point the vhost at `public/` |
| `/install` loops or “not found” after a real install | Installer is locked. Use login. Do not delete Owner to “fix” it |
| `/install` never starts | PHP extensions, permissions, or you are not on a fresh install |
| HTTP 500 after deploy | `storage/logs/laravel.log`; `APP_DEBUG` must stay **false** in production (fix via SSH, do not turn debug on publicly) |
| Database connection error | Host, port, name, user, password; MySQL running; user grants |
| CSS/JS missing | Run `npm run build` or deploy `public/build`; then hard-refresh |
| Logo 404 | `php artisan storage:link`; web server must allow `/storage` |
| Cannot sign in | Caps lock; inactive user; wrong email; Owner reset via Users if you still have another Owner/Admin |
| Cashier sees Users/Settings | Should not happen—sign out, report as a defect |
| Sale fails on last unit | Another till sold it; stock is correct—adjust cart |
| Dashboard “today” looks empty | Timezone; no completed sales yet; you are signed in as Cashier |
| Permission denied (403) | Expected for that role; use an Owner/Admin account |
| Two of the same invoice | Do not double-submit; if it still happens, stop and contact support with time and invoice numbers |

Cashiers: if POS will not complete, read the on-screen error (stock, empty cart, cash less than total). Do not refresh mid-pay if the success receipt already appeared—check **Transactions** first.

> **Screenshot:** Example validation on POS (insufficient stock).  
> **Suggested file:** `docs/images/user-guide/19-stock-error.png`

---

# 20. FAQ

**Is this one store or many?**  
One store per installation. Each customer VPS (or database) is a separate shop.

**Can I use it without a developer?**  
Day-to-day: yes. First install needs someone who can set PHP, MySQL, and `public/` as the web root—or a host that does that for you.

**What is the first login?**  
The Owner email and password you created in the installer. There is no default `admin` / `admin`.

**Can the Administrator fire the Owner?**  
No.

**Can we take cards?**  
Not in Version 1.0. Record cash only.

**Can we refund?**  
Not in Version 1.0. Completed sales cannot be deleted.

**Do we need Redis or a queue worker?**  
No.

**Do we need email?**  
No.

**Will changing a product price change old reports?**  
No. Lines store snapshots.

**Can two cashiers sell at once?**  
Yes. The last unit cannot be sold twice; one sale will be refused.

**What is demo data at install?**  
Optional sample products for practice. Turn it **off** for a live shop. A **public** marketing demo is a different, restricted host (`docs/DEMO_MODE.md`).

**How do I add another Owner?**  
Only an existing Owner can assign the Owner role (if your policy allows). Prefer one Owner plus Administrators.

**Why is `/install` gone?**  
By design, so nobody can take over the shop through the wizard.

---

# 21. Security Recommendations

1. **HTTPS** on the live URL. Do not operate the register on plain HTTP on a public network.
2. **`APP_DEBUG=false`** in production. Debug pages leak secrets.
3. Strong Owner and Administrator passwords; unique emails; no shared Owner login on the till—use **Cashier** accounts.
4. Deactivate staff the day they leave.
5. Keep `.env` outside the web root (Laravel default) and out of git. Restrict file permissions (e.g. `0600` where possible).
6. Document root = `public/` only. Never expose `.env` or `storage/logs`.
7. Apply OS and PHP updates; apply SimplePOS updates from your vendor with backups first.
8. Restrict SSH and the database port to trusted IPs when you can.
9. Backups off-box (§17); test restore.
10. Do not enable public **demo mode** on a store with real data.
11. Do not run `migrate:fresh` on production.
12. Do not install extra “adminer/phpmyadmin” on the same public host without extra protection.
13. Cashier PCs: sign out at end of shift; lock the OS when stepping away.
14. Treat Activity Log as a review tool, not a substitute for OS security.

---

# 22. Support Information

Replace the bracketed fields with your commercial support details before you ship this guide to customers.

| Item | Value |
|---|---|
| Product | SimplePOS — Sistem Kasir & Penjualan Sederhana |
| Version this guide covers | 1.0 Core MVP |
| Vendor / publisher | `[COMPANY_NAME]` |
| Support email | `[SUPPORT_EMAIL]` |
| Support hours | `[SUPPORT_HOURS_AND_TIMEZONE]` |
| Documentation | `[DOCS_URL]` |
| License / source delivery | `[LICENSE_TERM — e.g. per-store source license]` |
| Security issues | `[SECURITY_EMAIL]` — do not post exploits in public tickets |

### What to include in a ticket

- Store URL (not passwords)
- Role you are signed in as (Owner / Administrator / Cashier)
- Time of the issue (timezone)
- Invoice number if it is a sale problem
- Exact on-screen message
- For server errors: your operator may attach **redacted** log lines (no `.env`, no full card data—this product does not store cards)

### What support will not do

- Recover an Owner password without a verified operator process
- Enable debug on a public URL
- Bypass the installer lock on a live shop as a shortcut
- Treat a public demo as your production database

---

# Appendix A — Document map

| Need | Document |
|---|---|
| Product behavior | `PRD.md` |
| Install wizard lock and steps | `docs/INSTALLER.md` |
| Public sandbox (buyers) | `docs/DEMO_MODE.md` |
| This operating guide | `docs/USER_GUIDE.md` |

---

# Appendix B — Screenshot checklist

Capture these on a staging store with dummy data (no real customer names if you reuse screenshots in marketing):

| # | File | Scene |
|---|---|---|
| 00 | `00-login.png` | Login |
| 02 | `02-pos-overview.png` | POS |
| 04 | `04-install-welcome.png` | Installer |
| 05 | `05-settings-store.png` | Settings |
| 05 | `05-product-form.png` | Product form |
| 06 | `06-create-admin.png` | Create user |
| 07 | `07-users-list.png` | Users table |
| 08 | `08-nav-owner.png` | Owner sidebar |
| 08 | `08-nav-cashier.png` | Cashier sidebar |
| 09 | `09-dashboard.png` | Dashboard |
| 09 | `09-pos-payment.png` | Payment |
| 09 | `09-receipt.png` | Receipt |
| 09 | `09-stock-adjust.png` | Stock adjustment |
| 12 | `12-sales-report.png` | Sales report |
| 13 | `13-logo-upload.png` | Logo |

Place files under `docs/images/user-guide/` when you produce the illustrated PDF or HTML help.
