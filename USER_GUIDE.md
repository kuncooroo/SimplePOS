# USER GUIDE

## SimplePOS — Sistem Kasir & Penjualan Sederhana

**Document Version:** 1.0  
**Document Type:** Commercial User & Administrator Guide  
**Audience:** Store Owners, Administrators, Cashiers, Implementers, and Developers  
**Product Scope:** SimplePOS MVP / Commercial Source Code

> This guide explains how to install, configure, operate, maintain, and troubleshoot SimplePOS. It is written for both non-technical users and developers. Technical configuration sections are clearly separated from daily operational instructions.

---

# 1. Introduction

SimplePOS is a lightweight web-based Point of Sale application designed for small and medium retail businesses.

It helps store operators:

- record sales;
- manage products;
- manage categories;
- monitor stock;
- process cash payments;
- view transaction history;
- generate sales reports;
- manage users and roles;
- configure store information;
- review activity logs.

SimplePOS is designed as a self-hosted Laravel application.

Typical users:

- Store Owner
- Administrator
- Cashier
- Technical Implementer / Developer

The core transaction flow is:

```text
Login
→ Select Product
→ Add to Cart
→ Enter Cash Payment
→ Complete Sale
→ Stock Updated
→ Receipt Available
→ Transaction Appears in Reports
```

---

# 2. Key Features

SimplePOS includes the following MVP features.

## 2.1 Authentication

- secure login;
- logout;
- active/inactive user control;
- role-based access.

## 2.2 User Management

- Owner;
- Administrator;
- Cashier;
- user activation/deactivation;
- role management.

## 2.3 Product Management

- product name;
- SKU;
- optional barcode;
- category;
- selling price;
- optional cost price;
- current stock;
- active/inactive state.

## 2.4 Category Management

- create categories;
- edit categories;
- activate/deactivate categories.

## 2.5 Point of Sale

- search products;
- add products to cart;
- quantity changes;
- cart subtotal;
- simple transaction-level discount;
- cash received;
- change calculation;
- checkout.

## 2.6 Inventory

- current stock;
- sale stock deduction;
- manual stock adjustments;
- stock movement history;
- low-stock visibility.

## 2.7 Transactions

- unique invoice number;
- historical item snapshots;
- transaction history;
- transaction detail;
- receipt.

## 2.8 Dashboard

- sales today;
- transaction count;
- low-stock information;
- best-selling product indicator.

## 2.9 Reports

- daily sales;
- date-range sales;
- transaction count;
- product quantities sold.

## 2.10 Settings

- store name;
- address;
- contact information;
- currency display;
- receipt footer;
- store logo.

## 2.11 Audit Trail

Tracks selected sensitive actions such as:

- stock adjustments;
- role changes;
- user activation/deactivation;
- sensitive store-setting changes.

---

# 3. System Requirements

SimplePOS is intended to run on a standard Laravel-compatible environment.

Target project stack:

```text
Laravel 13.x
PHP 8.4.x
MySQL 8.4 LTS
Blade
Livewire
Alpine.js
Tailwind CSS
```

Actual installed versions should always be verified from the project files and server environment.

## 3.1 Recommended Server Requirements

Typical production server:

- Linux VPS;
- supported PHP version;
- MySQL;
- Composer;
- Node.js/npm for asset build when required;
- web server such as Nginx or Apache;
- HTTPS certificate;
- persistent storage.

## 3.2 PHP Extensions

Typical Laravel-compatible extensions may include:

```text
BCMath
Ctype
cURL
DOM
Fileinfo
Filter
Hash
Mbstring
OpenSSL
PCRE
PDO
PDO MySQL
Session
Tokenizer
XML
```

Exact requirements must be verified against the actual Laravel version and `composer.json`.

## 3.3 Browser Requirements

Recommended modern browsers:

- Google Chrome;
- Microsoft Edge;
- Mozilla Firefox;
- Safari.

For cashier use, desktop or tablet is recommended.

---

# 4. Installation

This section is intended for technical users or developers.

## 4.1 Upload the Project

Upload or clone the SimplePOS source code to the server.

Example project location:

```text
/var/www/simplepos
```

[Screenshot Placeholder — Project files on server]

## 4.2 Install PHP Dependencies

From the project root:

```text
composer install
```

For production, use the appropriate production Composer options according to the project deployment guide.

## 4.3 Configure Environment

Copy the example environment file:

```text
.env.example
```

to:

```text
.env
```

Configure:

- application URL;
- database;
- session;
- logging;
- file storage;
- mail if later required.

Never publish or share `.env`.

## 4.4 Generate Application Key

Use the standard Laravel application-key command for the installed version.

## 4.5 Create Database

Create a new MySQL database.

Example logical name:

```text
simplepos
```

Configure the database credentials in `.env`.

## 4.6 Run Database Migrations

Run the project's Laravel migrations.

Do not manually modify production tables unless specifically documented.

## 4.7 Seed Initial Data

If the project provides seeders:

- run required system seeders;
- create the first Owner account;
- optionally load demo/sample data only for non-production environments.

## 4.8 Configure Storage

If public file storage requires a symbolic link, run the appropriate Laravel storage setup command.

This is needed for files such as the store logo when stored on the public filesystem disk.

## 4.9 Build Frontend Assets

Install JavaScript dependencies:

```text
npm install
```

Build production assets with the project-defined command.

Typically:

```text
npm run build
```

Verify the exact script in `package.json`.

## 4.10 Configure Web Server

The web server document root must point to Laravel's public directory.

Do not expose:

- `.env`;
- storage internals;
- application source directories;
- configuration secrets.

## 4.11 Production Optimization

Before production release:

- disable debug mode;
- configure production app environment;
- configure HTTPS;
- configure cache commands appropriate to the installed Laravel version;
- confirm filesystem permissions;
- confirm database backup.

[Screenshot Placeholder — Successful SimplePOS login page]

---

# 5. Initial Configuration

After installation, log in as the initial Owner.

Recommended setup order:

```text
1. Store Information
2. User Accounts
3. Categories
4. Products
5. Initial Stock
6. Test POS Transaction
7. Verify Receipt
8. Verify Reports
```

---

# 6. Admin Setup

## 6.1 Configure Store Identity

Go to:

```text
Settings
```

Configure:

- store name;
- address;
- phone/contact;
- currency;
- receipt footer;
- logo.

[Screenshot Placeholder — Store Settings page]

Save changes.

Verify that the updated store information appears in supported receipt/display areas.

## 6.2 Create Operational Users

Go to:

```text
Users
```

Create accounts for:

- Administrators;
- Cashiers.

Do not share one account between multiple employees when individual accountability is required.

---

# 7. User Management

## 7.1 Create a User

1. Open **Users**.
2. Click **Add User**.
3. Enter the user name.
4. Enter the login identity.
5. Set credentials.
6. Select the allowed role.
7. Set the active state.
8. Click **Save**.

[Screenshot Placeholder — Add User form]

## 7.2 Edit a User

1. Open **Users**.
2. Select the user.
3. Click **Edit**.
4. Modify permitted information.
5. Save.

## 7.3 Deactivate a User

1. Open **Users**.
2. Select the account.
3. Choose **Deactivate**.
4. Confirm the action.

An inactive user cannot log in.

Historical transactions associated with that user remain preserved.

## 7.4 Reactivate a User

1. Open the inactive user.
2. Choose **Activate**.
3. Confirm.

---

# 8. Roles and Permissions

SimplePOS uses three primary roles.

## 8.1 Owner

Typical access:

- Dashboard;
- POS;
- Transactions;
- Products;
- Categories;
- Inventory;
- Reports;
- Users;
- Settings;
- Activity Logs.

The Owner is the highest operational role.

## 8.2 Administrator

Typical access:

- Dashboard;
- POS;
- Transactions;
- Products;
- Categories;
- Inventory;
- Reports;
- permitted user management;
- permitted settings.

Default restriction:

> An Administrator must not create, promote, demote, or deactivate a protected Owner account.

## 8.3 Cashier

Typical access:

- POS;
- permitted transaction history;
- receipt access within allowed scope.

A Cashier must not have unrestricted access to:

- user management;
- manual stock adjustment;
- store settings;
- unrestricted reports;
- activity logs.

[Screenshot Placeholder — Role-based sidebar comparison]

---

# 9. Module Guides

# 9.1 Dashboard

The Dashboard provides a quick business overview.

Typical information:

- Sales Today;
- Transactions Today;
- Low Stock;
- Best Seller.

[Screenshot Placeholder — Dashboard]

Use the Dashboard to identify:

- sales activity;
- transaction volume;
- products requiring stock attention.

---

# 9.2 Categories

## Create Category

1. Open **Categories**.
2. Click **Add Category**.
3. Enter category name.
4. Save.

## Edit Category

1. Select a category.
2. Click **Edit**.
3. Update information.
4. Save.

## Deactivate Category

Use deactivation when a category should no longer be used.

Historical data should remain available.

---

# 9.3 Products

## Create Product

1. Open **Products**.
2. Click **Add Product**.
3. Enter:
   - product name;
   - SKU;
   - optional barcode;
   - category;
   - selling price;
   - optional cost price;
   - stock information;
   - active status.
4. Save.

[Screenshot Placeholder — Product form]

Important rules:

- SKU must be unique.
- Barcode must be unique when provided.
- Inactive products cannot be sold.
- Current product-price changes do not alter historical completed transactions.

## Search Products

You may search using:

- product name;
- SKU;
- barcode.

## Deactivate Product

Use **Deactivate** instead of deleting an old product.

This preserves history.

---

# 9.4 Point of Sale

The POS page is the primary cashier workspace.

[Screenshot Placeholder — POS screen]

## Start a Sale

1. Open **POS**.
2. Search for a product.
3. Select the product.
4. The product appears in the cart.
5. Repeat for additional items.

## Change Quantity

Use the cart quantity controls.

The quantity must be valid and cannot violate stock rules.

## Remove Item

Select the remove action for the cart item.

If all items are removed, the cart becomes empty.

## Apply Discount

If the transaction-level discount feature is enabled:

1. enter the permitted discount;
2. verify the updated total.

A discount must not result in an invalid negative total.

---

# 9.5 Payment

SimplePOS MVP uses cash payment.

1. Review the final amount due.
2. Enter **Cash Received**.
3. Verify the calculated **Change**.
4. Click **Complete Sale**.

[Screenshot Placeholder — Payment panel]

If cash received is below the amount due, checkout is rejected.

---

# 9.6 Checkout

When the sale is confirmed, SimplePOS revalidates:

- user authorization;
- cart content;
- product active status;
- quantities;
- stock;
- product values;
- transaction totals;
- payment.

A successful checkout creates:

```text
Completed Transaction
+ Transaction Items
+ Stock Reduction
+ Stock Movement
+ Receipt
```

If a required operation fails, the sale must not appear as completed.

---

# 9.7 Transactions

Open:

```text
Transactions
```

You may search/filter according to role permissions.

Typical filters:

- invoice number;
- date;
- cashier.

[Screenshot Placeholder — Transaction list]

## Open Transaction Detail

Transaction detail shows:

- invoice;
- date/time;
- cashier;
- products;
- quantities;
- subtotal;
- discount;
- total;
- cash received;
- change.

Completed transactions are historical records and are read-only in the MVP.

---

# 9.8 Receipt

After checkout, the receipt becomes available.

Typical receipt information:

- store name;
- invoice;
- date/time;
- cashier;
- items;
- price;
- quantity;
- subtotal;
- discount;
- total;
- cash;
- change;
- receipt footer.

[Screenshot Placeholder — Receipt]

Historical receipt values use transaction snapshots, not current product prices.

---

# 9.9 Inventory

Open:

```text
Inventory
```

Typical information:

- product;
- SKU;
- current stock;
- stock status.

[Screenshot Placeholder — Inventory list]

## Manual Stock Adjustment

Authorized users may adjust stock.

1. Select a product.
2. Choose **Adjust Stock**.
3. Enter the adjustment.
4. Enter a reason.
5. Review the resulting stock.
6. Confirm.

Every manual stock adjustment requires:

- responsible user;
- adjustment value;
- reason;
- stock movement record;
- audit record.

Cashiers are not permitted to perform unrestricted manual stock adjustments.

---

# 9.10 Stock Movement History

Use movement history to review stock changes.

MVP movement types:

```text
SALE
MANUAL_ADJUSTMENT
```

Sale movements are linked to completed transactions where applicable.

---

# 9.11 Reports

Open:

```text
Reports
```

Select a reporting period.

Typical reports include:

- total sales;
- completed transaction count;
- product quantities sold.

[Screenshot Placeholder — Sales report]

Only eligible completed transactions are included.

Failed checkout attempts must not affect reports.

---

# 9.12 Activity Log

Authorized management users can review sensitive activity.

Examples:

- user role changed;
- user activated/deactivated;
- manual stock adjusted;
- sensitive settings updated.

Activity logs are read-only through normal business workflows.

---

# 10. Business Workflows

# 10.1 Standard Sale

```text
Cashier Login
→ Open POS
→ Search Product
→ Add Product
→ Adjust Quantity
→ Review Total
→ Enter Cash
→ Complete Sale
→ Receipt
```

# 10.2 Product Setup

```text
Create Category
→ Create Product
→ Set Price
→ Set Stock
→ Activate Product
→ Product Available in POS
```

# 10.3 Manual Stock Correction

```text
Open Inventory
→ Select Product
→ Adjust Stock
→ Enter Reason
→ Confirm
→ Stock Updated
→ Movement Logged
```

# 10.4 User Administration

```text
Owner/Admin Login
→ Users
→ Create/Edit User
→ Assign Permitted Role
→ Save
→ Audit Event
```

---

# 11. Notifications

SimplePOS MVP uses in-application feedback.

Examples:

- success message;
- validation error;
- insufficient stock;
- insufficient payment;
- low-stock warning;
- authorization error.

No external notifications are required for MVP.

Not included by default:

- SMS;
- WhatsApp;
- push notification;
- external email notification.

---

# 12. Reports

## 12.1 Daily Sales

Select the current day or desired date.

Review:

- revenue;
- transaction count;
- products sold.

## 12.2 Date Range

1. Open Reports.
2. Select start date.
3. Select end date.
4. Apply filter.

The report should display the selected period.

## 12.3 Report Reconciliation

Dashboard and reports should use equivalent completed-sale rules.

If report values appear incorrect:

1. verify date range;
2. verify completed transaction list;
3. confirm timezone;
4. check whether the product price changed after the historical sale;
5. remember that historical values use transaction snapshots.

---

# 13. File Management

The primary MVP file upload is the store logo.

## Upload Logo

1. Open **Settings**.
2. Select store logo.
3. Choose a valid image.
4. Save.

Rules:

- file type is validated;
- file size is validated;
- invalid uploads are rejected;
- failed replacement must not remove the previous valid logo.

The database stores the file reference/path, not the raw image binary.

---

# 14. Email Configuration

Email is **not required for the core SimplePOS MVP**.

The application must not depend on email for:

- login;
- checkout;
- inventory;
- reporting;
- receipt.

If a future release adds email features, configure Laravel mail settings in the environment.

Typical configuration areas may include:

```text
MAIL_MAILER
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_ENCRYPTION
MAIL_FROM_ADDRESS
MAIL_FROM_NAME
```

Do not commit mail credentials.

After enabling mail functionality:

1. configure the provider;
2. update `.env`;
3. clear/rebuild relevant configuration caches;
4. send a test message;
5. verify logs on failure.

[Screenshot Placeholder — Future mail configuration if provided]

---

# 15. Cron Configuration

No scheduled task is mandatory for the SimplePOS MVP.

Future scheduled tasks may include:

- automated backups;
- temporary file cleanup;
- scheduled reports;
- notification checks.

If the deployed version uses Laravel Scheduler, configure the server cron according to the installed Laravel version.

General approach:

1. confirm scheduled tasks exist;
2. configure one scheduler entry;
3. ensure the correct PHP binary/project path is used;
4. verify file permissions;
5. check logs;
6. test the scheduled command manually.

Do not configure scheduler infrastructure unless the project actually uses it.

---

# 16. Queue Configuration

Queue workers are **not required for critical MVP transaction processing**.

The following must not depend on a queue:

- checkout;
- transaction persistence;
- stock deduction;
- stock movement;
- receipt availability.

A future release may use queues for:

- email;
- large imports;
- large exports;
- external notifications.

If queue functionality is introduced:

1. configure the queue connection;
2. configure worker process supervision;
3. define retry/failure handling;
4. monitor failed jobs;
5. ensure business-critical transaction correctness remains synchronous.

Never assume a queue worker is running unless deployment documentation explicitly requires it.

---

# 17. Backup

Production backups are strongly recommended.

Back up at least:

```text
MySQL database
Persistent uploaded files
Environment/configuration references required for recovery
```

## Recommended Backup Principles

- keep backups separate from the live application;
- protect backup access;
- use a defined retention policy;
- monitor backup failures;
- periodically test restoration.

## Manual Recovery Verification

A backup is not considered reliable until restoration has been tested.

Recommended recovery test:

```text
1. Restore database to a test environment
2. Restore uploaded files
3. Configure environment
4. Start application
5. Login
6. Open transactions
7. Verify product stock
8. Verify receipt
9. Verify reports
```

---

# 18. Updates

SimplePOS updates should be performed carefully.

Before updating:

1. read release notes;
2. back up database;
3. back up persistent files;
4. confirm current version;
5. review migration requirements;
6. test update in staging where possible.

## Update Process

Typical safe process:

```text
Backup
→ Maintenance Window
→ Deploy New Source
→ Install Dependencies
→ Build Assets
→ Run New Migrations
→ Refresh Supported Caches
→ Smoke Test
→ Return to Service
```

Never modify existing deployed production migrations to simulate an update.

Schema changes must use new migrations.

## Post-Update Checks

Verify:

- login;
- role permissions;
- products;
- POS;
- checkout;
- stock;
- receipt;
- transaction history;
- reports;
- settings.

---

# 19. Troubleshooting

# 19.1 Cannot Login

Check:

- username/email;
- password;
- user active status;
- browser cookies;
- session configuration.

If all users are affected:

- check application logs;
- check database connection;
- check session storage.

---

# 19.2 Product Does Not Appear in POS

Verify:

- product is active;
- category/configuration is valid;
- search term is correct;
- SKU/barcode is correct.

---

# 19.3 Checkout Fails

Possible causes:

- cart empty;
- insufficient stock;
- inactive product;
- invalid discount;
- insufficient cash received;
- database error;
- session expired.

Do not repeatedly force checkout without identifying the cause.

Check application logs for unexpected system failures.

---

# 19.4 Stock Looks Incorrect

Review:

1. completed transaction history;
2. stock movement history;
3. manual adjustments;
4. product current stock.

Do not manually modify MySQL records unless performing controlled technical recovery.

---

# 19.5 Report Does Not Match Expectation

Check:

- date range;
- timezone;
- transaction completion;
- filters;
- historical transaction snapshots.

Only completed eligible transactions should be counted.

---

# 19.6 Logo Does Not Display

Check:

- valid upload;
- filesystem permissions;
- storage link/configuration;
- file path;
- public file visibility.

---

# 19.7 Server Error

For production:

- user should see a safe generic error;
- developer should inspect application/server logs.

Check:

- PHP errors;
- database connection;
- storage permissions;
- missing environment configuration;
- failed migration;
- asset build.

Never enable verbose debug output permanently on a public production system.

---

# 20. FAQ

## Q1. Can Cashiers manage users?

No. Cashier access is intentionally limited.

## Q2. Can an inactive product still be sold?

No. Inactive products cannot be added to new sales.

## Q3. What happens if a product price changes?

New sales use the current approved price.

Completed historical transactions keep their original sale-time price.

## Q4. Can completed transactions be edited?

Not through normal MVP functions.

Completed transactions are protected historical records.

## Q5. Can completed transactions be deleted?

Not through standard MVP business workflows.

## Q6. Does SimplePOS support refunds?

Not in the approved MVP.

Refund/return behavior requires a separate business workflow.

## Q7. Does SimplePOS support credit card or QR payments?

The core MVP defines cash payment.

Additional payment methods are future scope.

## Q8. Does SimplePOS support multiple branches?

Not in the MVP.

## Q9. Does SimplePOS support multiple warehouses?

Not in the MVP.

## Q10. Does SimplePOS support SaaS multi-tenancy?

Not in the MVP.

## Q11. Is email required?

No.

## Q12. Is a queue worker required?

No, not for the MVP core workflow.

## Q13. Is cron required?

No scheduled task is mandatory for the core MVP.

## Q14. Should products be deleted when no longer sold?

Prefer deactivation so historical references remain safe.

## Q15. Why is a stock adjustment reason required?

To preserve traceability and reduce unexplained inventory changes.

---

# 21. Security Recommendations

Production operators should follow these rules.

## 21.1 Credentials

- never share Owner credentials;
- use individual user accounts;
- use strong passwords;
- deactivate former staff accounts;
- never commit credentials to source control.

## 21.2 Server Security

- use HTTPS;
- keep operating system packages updated;
- restrict database network exposure;
- use firewall rules;
- disable debug mode;
- protect SSH access.

## 21.3 Application Security

- keep Laravel dependencies updated through controlled releases;
- preserve CSRF protection;
- do not bypass authorization;
- validate uploads;
- do not expose `.env`;
- do not display stack traces publicly.

## 21.4 Backups

- perform regular backups;
- protect backup credentials;
- test restoration.

## 21.5 User Permissions

Use the least-privilege principle.

Cashiers should receive only the access necessary to process sales.

---

# 22. Support Information

Commercial distributors should replace this section with their official support information.

Suggested template:

```text
Product:
SimplePOS — Sistem Kasir & Penjualan Sederhana

Version:
[VERSION]

Support Email:
[SUPPORT_EMAIL]

Support Website:
[SUPPORT_URL]

Documentation:
[DOCUMENTATION_URL]

Purchase / License Reference:
[LICENSE_REFERENCE]

Support Hours:
[SUPPORT_HOURS]

Response Policy:
[SUPPORT_POLICY]
```

Before contacting support, prepare:

- application version;
- operating system;
- PHP version;
- Laravel version;
- MySQL version;
- relevant error message;
- steps to reproduce;
- screenshot where appropriate;
- sanitized application log excerpt.

Never send:

- `.env`;
- database passwords;
- SSH passwords;
- API secrets;
- private keys.

---

# 23. Screenshot Checklist for Commercial Documentation

Recommended screenshots to add before commercial release:

```text
[ ] Login page
[ ] Dashboard
[ ] User list
[ ] Add User
[ ] Product list
[ ] Add Product
[ ] Category list
[ ] POS screen
[ ] Payment panel
[ ] Successful checkout
[ ] Receipt
[ ] Transaction history
[ ] Transaction detail
[ ] Inventory list
[ ] Manual stock adjustment
[ ] Stock movement history
[ ] Sales report
[ ] Store settings
[ ] Activity log
[ ] Demo mode banner if applicable
```

Use screenshots from the final released UI, not mockups, whenever possible.

---

# 24. Quick Start for Store Owners

After technical installation is complete:

```text
1. Login as Owner
2. Configure Store Settings
3. Create Administrator/Cashier Users
4. Create Categories
5. Create Products
6. Set Initial Stock
7. Run a Test Sale
8. Check Stock
9. Open Receipt
10. Verify Dashboard and Reports
```

Once these checks succeed, SimplePOS is ready for normal store operation.

---

# 25. Quick Start for Cashiers

Daily cashier flow:

```text
1. Login
2. Open POS
3. Search/scan product
4. Add items
5. Confirm quantity
6. Review total
7. Enter cash received
8. Check change
9. Complete sale
10. Provide receipt
```

If checkout fails because of stock, payment, or product status, correct the displayed problem before trying again.

---

# 26. Quick Start for Administrators

Typical administrator workflow:

```text
Login
→ Review Dashboard
→ Maintain Categories/Products
→ Review Inventory
→ Perform Authorized Stock Adjustment
→ Review Transactions
→ Review Reports
→ Manage Permitted Users
```

Administrator permissions remain subject to Owner-protection rules.

---

# 27. Important Product Boundaries

The following are intentionally outside the approved MVP:

```text
Public User Registration
Refunds
Voids
Completed Transaction Cancellation
Multiple Payment Methods
Supplier Purchasing
Accounting / General Ledger
Payroll
CRM
E-Commerce
Marketplace Integration
Multi-Branch
Multi-Warehouse
Public API
WhatsApp Automation
SMS
SaaS Multi-Tenancy
```

If a customized version includes any of these features, its documentation must be updated accordingly.

---

# 28. Final Operational Principle

The most important operational rule in SimplePOS is:

> **A sale should be treated as completed only after the transaction and all required stock effects have been successfully saved.**

For store operators:

- do not manually alter database records;
- use the provided product/inventory/user workflows;
- review reports and stock movement history when discrepancies occur.

For developers:

- preserve historical transaction integrity;
- preserve server-side authorization;
- preserve atomic checkout behavior;
- update this guide when product behavior changes.
