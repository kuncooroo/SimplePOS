# Product Requirements Document (PRD)

## SimplePOS --- Sistem Kasir & Penjualan Sederhana

------------------------------------------------------------------------

## 1. Document Information

  -----------------------------------------------------------------------
  Field                               Value
  ----------------------------------- -----------------------------------
  Document                            Product Requirements Document (PRD)

  Product Name                        SimplePOS --- Sistem Kasir &
                                      Penjualan Sederhana

  Product Type                        Web-based Point of Sale (POS)

  Business Domain                     Point of Sale / Retail Management /
                                      Inventory Management

  Target Market                       Micro, small, and medium retail
                                      businesses

  Primary Users                       Owner, Administrator, Cashier

  Planned Platform                    Web Application

  Planned Technology Context          Laravel + MySQL

  Document Version                    1.0

  Status                              Initial Product Definition

  Primary Purpose                     Define product behavior, scope,
                                      requirements, and acceptance
                                      criteria before implementation
  -----------------------------------------------------------------------

This PRD defines **what SimplePOS must do and how the product must
behave**. It intentionally avoids implementation-level code and
low-level technical architecture decisions.

------------------------------------------------------------------------

## 2. Product Overview

SimplePOS is a web-based Point of Sale application designed for small
and medium retail businesses that need a simple way to manage sales
transactions, products, payments, basic inventory, transaction history,
and sales reporting.

The product is intended to be simple enough for small businesses to
adopt without extensive training while remaining structured and
configurable enough to be sold repeatedly as commercial source code,
deployed for individual businesses, or evolved into a SaaS product in
the future.

The initial product focuses on general retail use cases rather than
industry-specific workflows.

------------------------------------------------------------------------

## 3. Background

Many small retail businesses still use notebooks, calculators,
spreadsheets, messaging applications, or disconnected software to manage
sales and inventory.

This creates operational problems because sales records, stock
information, payment records, and reports are not maintained in one
consistent system.

A small business owner needs to know what was sold, who processed the
transaction, how much revenue was generated, and what stock remains. A
cashier needs a fast and uncomplicated checkout process. An
administrator needs a controlled way to maintain products, users, and
store settings.

SimplePOS is intended to centralize these essential activities without
becoming a full ERP system.

------------------------------------------------------------------------

## 4. Problem Statement

Small retail businesses often lack an affordable and simple centralized
system for recording sales, managing products, tracking stock, and
reviewing sales performance.

Manual or disconnected workflows create the following problems:

1.  Checkout takes longer than necessary.
2.  Transaction calculations are prone to human error.
3.  Sales records may be incomplete or difficult to trace.
4.  Stock quantities may not reflect actual sales activity.
5.  Owners cannot quickly determine daily or monthly sales.
6.  Transaction history is difficult to search.
7.  Accountability between owners, administrators, and cashiers is
    limited.
8.  Preparing sales reports requires manual reconciliation.

SimplePOS must reduce these problems through a centralized and
controlled POS workflow.

------------------------------------------------------------------------

## 5. Product Vision

To provide small retail businesses with a lightweight, reliable, and
easy-to-use POS system that centralizes sales, products, stock, and
reporting while remaining sufficiently configurable and maintainable to
be commercially distributed to multiple customers.

The product should be:

-   Simple for cashiers.
-   Informative for owners.
-   Manageable for administrators.
-   Configurable for different retail businesses.
-   Suitable for repeated commercial distribution.
-   Extendable without requiring MVP users to operate unnecessary
    features.

------------------------------------------------------------------------

## 6. Product Objectives

### OBJ-01 --- Simplify Checkout

A trained cashier must be able to create and complete a standard sale
without navigating away from the POS workflow.

### OBJ-02 --- Centralize Sales Records

Every successfully completed sale must create a persistent transaction
record with its items, quantities, prices, totals, payment information,
cashier, and timestamp.

### OBJ-03 --- Maintain Basic Inventory Accuracy

Completed sales of stock-tracked products must reduce recorded stock
according to the quantities sold.

### OBJ-04 --- Improve Business Visibility

Owners must be able to review key sales indicators and reports without
manually calculating transaction totals.

### OBJ-05 --- Improve Traceability

Authorized users must be able to identify the user and time associated
with important business records and controlled changes.

### OBJ-06 --- Support Repeated Commercial Use

Store identity and basic business settings must be configurable without
modifying the product's core behavior.

### OBJ-07 --- Keep the Product Focused

The MVP must solve general retail POS requirements without expanding
into accounting, payroll, CRM, e-commerce, or ERP functionality.

------------------------------------------------------------------------

## 7. Success Metrics

The following metrics define initial product success.

  -----------------------------------------------------------------------
  ID                      Metric                  Target
  ----------------------- ----------------------- -----------------------
  SM-01                   Standard checkout       A trained cashier can
                          completion              complete a normal cash
                                                  transaction from
                                                  product selection
                                                  through receipt view in
                                                  one continuous POS
                                                  workflow

  SM-02                   Transaction persistence 100% of successful
                                                  checkouts create a
                                                  retrievable transaction
                                                  record

  SM-03                   Stock synchronization   100% of completed sales
                                                  for stock-tracked
                                                  products create the
                                                  corresponding stock
                                                  reduction

  SM-04                   Calculation accuracy    Subtotal, discount,
                                                  total, payment, and
                                                  change calculations
                                                  match defined business
                                                  rules in all acceptance
                                                  tests

  SM-05                   Traceability            100% of completed
                                                  transactions identify
                                                  the responsible cashier
                                                  and transaction time

  SM-06                   Searchability           Authorized users can
                                                  retrieve a known
                                                  transaction using its
                                                  invoice number

  SM-07                   Reporting consistency   Report totals reconcile
                                                  with eligible completed
                                                  transactions for the
                                                  same reporting period

  SM-08                   Access control          Users cannot
                                                  successfully perform
                                                  actions outside their
                                                  assigned role
                                                  permissions in
                                                  acceptance tests

  SM-09                   Usability               Core cashier functions
                                                  are accessible without
                                                  requiring access to
                                                  administrative
                                                  configuration screens

  SM-10                   Commercial              Store name, logo,
                          configurability         contact information,
                                                  currency display, and
                                                  receipt footer can be
                                                  changed through
                                                  authorized settings
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 8. Target Users

### Primary Users

1.  **Store Owner**
    -   Monitors sales.
    -   Reviews reports.
    -   Controls users and business settings.
    -   Needs visibility into business activity.
2.  **Cashier**
    -   Processes customer purchases.
    -   Accepts payments.
    -   Provides receipts.
    -   Needs a fast and low-friction interface.
3.  **Administrator**
    -   Maintains products and categories.
    -   Adjusts inventory when authorized.
    -   Maintains users and store configuration.
    -   Supports day-to-day system administration.

### Secondary Commercial User

4.  **Source Code Buyer / Implementer**
    -   Deploys or customizes SimplePOS for a business.
    -   Requires a product whose business behavior is predictable and
        configurable.
    -   Is not an operational role inside the default store workflow.

------------------------------------------------------------------------

## 9. User Personas

### Persona 1 --- Retail Owner

**Profile:** Operates one small retail business and may employ one or
more cashiers.

**Goals:** - Know today's sales. - Review historical sales. - Monitor
stock. - Identify low-stock products. - Control who can access
administrative functions.

**Frustrations:** - Manual reconciliation. - Missing transaction
records. - Unclear stock quantities. - Difficulty monitoring cashier
activity.

### Persona 2 --- Cashier

**Profile:** Processes customer purchases throughout the working day.

**Goals:** - Find products quickly. - Add products to a cart. - See the
correct total. - Enter payment. - See the correct change. - Finish
checkout quickly. - Reprint or view an allowed receipt when necessary.

**Frustrations:** - Too many steps. - Complex menus. - Slow product
search. - Unclear payment status. - Accidental loss of cart data.

### Persona 3 --- Store Administrator

**Profile:** Maintains operational master data.

**Goals:** - Add and update products. - Organize categories. - Maintain
stock records. - Manage authorized users. - Configure store information.

**Frustrations:** - Duplicate products. - Inconsistent product codes. -
Unclear stock adjustments. - Uncontrolled access to sensitive functions.

------------------------------------------------------------------------

## 10. User Pain Points

  -----------------------------------------------------------------------
  ID                      Pain Point              Product Response
  ----------------------- ----------------------- -----------------------
  PP-01                   Manual checkout         Automatic cart and
                          calculation             payment calculation

  PP-02                   Slow product lookup     Search by
                                                  product-identifying
                                                  information

  PP-03                   Missing sales records   Persistent transaction
                                                  history

  PP-04                   Uncertain stock levels  Stock deduction and
                                                  stock movement history

  PP-05                   Manual sales recap      Dashboard and reports

  PP-06                   Difficult transaction   Invoice/date/cashier
                          lookup                  filtering

  PP-07                   Uncontrolled            Role-based permissions
                          administrative access   

  PP-08                   Difficult store         Store settings
                          customization           

  PP-09                   Untraceable changes     Audit trail for defined
                                                  sensitive activities

  PP-10                   Accidental data loss    Clear validation and
                          during validation       recoverable workflows
                          errors                  where possible
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## 11. User Roles

The MVP must provide three operational roles.

### ROLE-01 --- Owner

The Owner is the highest standard business role.

The Owner must be able to: - Access dashboard business indicators. -
Manage products and categories. - Access the POS. - View transactions. -
View reports. - view and perform permitted inventory activities. -
Manage users. - Manage store settings. - View audit information made
available by the product.

### ROLE-02 --- Administrator

The Administrator manages operational data.

The Administrator must be able to: - Access the dashboard. - Manage
products and categories. - Access the POS. - View transaction history. -
View reports. - Perform permitted inventory adjustments. - Manage users
where explicitly permitted by the permissions matrix. - Manage
operational store settings where explicitly permitted.

### ROLE-03 --- Cashier

The Cashier focuses on selling.

The Cashier must be able to: - Access the POS. - Search and select
available products. - Create and complete sales. - View transactions
permitted by the product. - View or print receipts for transactions the
role is authorized to access.

The Cashier must not be able to: - Manage users. - Change store-wide
configuration. - Perform unrestricted inventory adjustments. - Delete
completed transactions. - Access administrative audit data.

------------------------------------------------------------------------

## 12. Product Scope

The initial product scope includes:

1.  User authentication.
2.  Role-based access.
3.  Dashboard.
4.  Product management.
5.  Product categories.
6.  POS/cart workflow.
7.  Payment recording.
8.  Transaction history.
9.  Basic inventory.
10. Stock movement records.
11. Basic sales reporting.
12. Printable transaction receipt.
13. User management.
14. Store settings.
15. Search and filtering for relevant business records.
16. Basic audit trail for sensitive activities.
17. Basic localization settings required for reusable commercial
    deployment.

The product scope is intentionally limited to essential general-retail
operations.

------------------------------------------------------------------------

## 13. MVP Scope

The MVP must include the following end-to-end capabilities.

### MVP-01 Authentication

-   Users can sign in with valid credentials.
-   Invalid credentials do not create an authenticated session.
-   Authenticated users can sign out.

### MVP-02 User and Role Control

-   Authorized users can create and update operational users.
-   Each operational user has an assigned role.
-   Role permissions are enforced.

### MVP-03 Category Management

-   Authorized users can create, view, edit, activate/deactivate, and
    search categories.

### MVP-04 Product Management

-   Authorized users can create, view, edit, activate/deactivate, and
    search products.
-   A product can contain a name, SKU, optional barcode, category,
    selling price, optional cost price, stock quantity/status
    information, and active status.
-   SKU must uniquely identify a product.
-   A non-empty barcode, when used, must uniquely identify a product.

### MVP-05 POS

-   Cashiers can search/select sellable products.
-   Cashiers can add products to a cart.
-   Cashiers can change quantities subject to stock rules.
-   Cashiers can remove cart items.
-   The system calculates subtotal, permitted discount, final total,
    payment, and change.
-   Checkout cannot succeed until all required conditions are satisfied.

### MVP-06 Transactions

-   Successful checkout creates an invoice number.
-   Transaction records contain immutable sale snapshots required for
    historical accuracy.
-   Authorized users can view transaction details.
-   Completed transactions cannot be silently deleted or edited.

### MVP-07 Inventory

-   Sale completion reduces stock for stock-tracked items.
-   Authorized stock adjustments require a reason.
-   Stock movement history records relevant inventory changes.

### MVP-08 Reports

-   Authorized users can view daily and date-range sales information.
-   Reports use completed eligible transactions as their source of
    truth.
-   Reports provide at least sales totals, transaction count, and
    product-sales information.

### MVP-09 Settings and Receipt

-   Authorized users can configure basic store identity.
-   Completed transactions have a printable receipt view.

------------------------------------------------------------------------

## 14. Out of Scope

The following are explicitly excluded from the MVP:

-   Full accounting/general ledger.
-   Payroll.
-   Human resource management.
-   Customer loyalty points.
-   Advanced CRM.
-   E-commerce storefront.
-   Marketplace synchronization.
-   Advanced supplier procurement.
-   Purchase orders.
-   Multi-warehouse management.
-   Multi-branch management.
-   Restaurant table management.
-   Kitchen display systems.
-   Reservation systems.
-   Pharmacy-specific regulation workflows.
-   Advanced taxation engines.
-   Automated tax filing.
-   Native mobile applications.
-   AI forecasting.
-   AI recommendations.
-   WhatsApp automation.
-   Advanced subscription billing.
-   Multi-tenant SaaS administration.
-   Third-party payment gateway processing.
-   Complex refunds and returns.
-   Offline-first synchronization.
-   Full double-entry accounting.
-   Hardware-specific device management.

Future versions may introduce selected items only after the core POS is
stable.

------------------------------------------------------------------------

## 15. Functional Requirements

### 15.1 Authentication

**FR-AUTH-01**\
The system must allow an active user to authenticate using the supported
login credentials.

**FR-AUTH-02**\
The system must reject invalid credentials without revealing whether a
specific account exists.

**FR-AUTH-03**\
The system must prevent inactive users from accessing authenticated
functions.

**FR-AUTH-04**\
The system must allow an authenticated user to sign out.

**FR-AUTH-05**\
After authentication, the user must only be able to access functions
allowed by the assigned role.

### 15.2 Product Categories

**FR-CAT-01**\
Authorized users must be able to create a category with a required name.

**FR-CAT-02**\
Authorized users must be able to edit an existing category.

**FR-CAT-03**\
Authorized users must be able to activate or deactivate a category.

**FR-CAT-04**\
The system must prevent an inactive category from being used for new
sellable product assignments where doing so would violate active-data
rules.

**FR-CAT-05**\
Authorized users must be able to search categories by name.

### 15.3 Products

**FR-PROD-01**\
Authorized users must be able to create a product with all mandatory
product information.

**FR-PROD-02**\
Each product must have a unique SKU.

**FR-PROD-03**\
If a barcode is supplied, the system must reject a barcode already
assigned to another product.

**FR-PROD-04**\
A product's selling price must not be negative.

**FR-PROD-05**\
Authorized users must be able to edit product information.

**FR-PROD-06**\
Authorized users must be able to activate or deactivate a product.

**FR-PROD-07**\
Inactive products must not be available for new POS sales.

**FR-PROD-08**\
The system must preserve historical transaction item information when
the current product name or price changes.

**FR-PROD-09**\
Authorized users must be able to search products by at least name, SKU,
or barcode.

### 15.4 POS Cart

**FR-POS-01**\
An authorized cashier must be able to start a new cart.

**FR-POS-02**\
The cashier must be able to add an active sellable product to the cart.

**FR-POS-03**\
Adding the same product more than once must result in a predictable
quantity update rather than an ambiguous duplicate state.

**FR-POS-04**\
The cashier must be able to increase or decrease item quantity subject
to product and stock rules.

**FR-POS-05**\
The cashier must be able to remove an item before checkout.

**FR-POS-06**\
The system must display the current subtotal whenever cart contents
change.

**FR-POS-07**\
The system must recalculate the payable total whenever a relevant cart
value changes.

**FR-POS-08**\
The system must prevent checkout of an empty cart.

**FR-POS-09**\
For products that cannot be sold beyond available stock, the system must
reject a checkout quantity exceeding available stock.

### 15.5 Discount

**FR-DISC-01**\
The MVP must support a simple transaction-level discount.

**FR-DISC-02**\
A discount must not reduce the transaction total below zero.

**FR-DISC-03**\
The final discount amount applied must be stored in the completed
transaction.

### 15.6 Payment and Checkout

**FR-PAY-01**\
Before completing checkout, the system must display the amount due.

**FR-PAY-02**\
For cash payment, the cashier must be able to enter the amount received.

**FR-PAY-03**\
The system must calculate change as the amount received minus the final
amount due.

**FR-PAY-04**\
The system must reject a cash checkout when the amount received is lower
than the amount due.

**FR-PAY-05**\
The cashier must explicitly confirm checkout before a transaction is
completed.

**FR-PAY-06**\
A successful checkout must create exactly one completed transaction for
that checkout action.

**FR-PAY-07**\
A successful checkout must produce a unique invoice number.

**FR-PAY-08**\
A failed checkout must not leave a completed transaction with partially
applied stock effects.

### 15.7 Transactions

**FR-TRX-01**\
Every completed transaction must record its invoice number, transaction
time, cashier, items, quantities, unit sale prices, subtotal, discount,
final total, payment information required by the product, and change
where applicable.

**FR-TRX-02**\
Authorized users must be able to view transaction details.

**FR-TRX-03**\
Authorized users must be able to search for a transaction by invoice
number.

**FR-TRX-04**\
Authorized users must be able to filter transactions by date range.

**FR-TRX-05**\
Where role permissions permit, transactions must be filterable by
cashier.

**FR-TRX-06**\
Completed transaction financial values must not change merely because
current product prices change.

**FR-TRX-07**\
The MVP must not provide ordinary hard deletion of completed
transactions.

### 15.8 Inventory

**FR-INV-01**\
Completing a sale must reduce the recorded stock of each applicable
stock-tracked product by the quantity sold.

**FR-INV-02**\
Authorized users must be able to perform a manual stock adjustment.

**FR-INV-03**\
A manual stock adjustment must require an adjustment reason.

**FR-INV-04**\
Each stock-affecting sale or manual adjustment must create a stock
movement record.

**FR-INV-05**\
A stock movement record must identify the product, movement type,
quantity change, resulting or traceable stock context, responsible user
where applicable, reference where applicable, and timestamp.

**FR-INV-06**\
The product must identify products considered low stock according to the
configured or defined threshold behavior.

### 15.9 Receipt

**FR-RCP-01**\
A completed transaction must have a receipt view.

**FR-RCP-02**\
The receipt must show at least store identity, invoice number,
transaction date/time, cashier identification suitable for display, sold
items, quantities, prices, totals, payment amount, and change where
applicable.

**FR-RCP-03**\
The receipt must use the store's configured receipt footer when one
exists.

**FR-RCP-04**\
Authorized users must be able to open a printable representation of the
receipt.

### 15.10 Users

**FR-USER-01**\
Authorized users must be able to create an operational user with a role.

**FR-USER-02**\
Authorized users must be able to update permitted user information.

**FR-USER-03**\
Authorized users must be able to activate or deactivate accounts subject
to role restrictions.

**FR-USER-04**\
Deactivating a user must not remove historical references to that user
from completed transactions or audit records.

### 15.11 Store Settings

**FR-SET-01**\
Authorized users must be able to configure store name.

**FR-SET-02**\
Authorized users must be able to configure store address and contact
information.

**FR-SET-03**\
Authorized users must be able to configure a store logo.

**FR-SET-04**\
Authorized users must be able to configure currency display from
supported options.

**FR-SET-05**\
Authorized users must be able to configure receipt footer text.

**FR-SET-06**\
Changes to store settings must apply to subsequent product displays or
documents where those settings are relevant, while historical
transaction financial data remains unchanged.

------------------------------------------------------------------------

## 16. Non-Functional Requirements

### NFR-01 Usability

Core cashier tasks must use clear labels and must not require access to
administrative screens.

### NFR-02 Consistency

Equivalent actions, statuses, labels, currency formats, and validation
behavior must be presented consistently across the application.

### NFR-03 Reliability

A confirmed transaction must not be represented as successful unless the
required transaction and stock effects have completed consistently.

### NFR-04 Maintainable Product Behavior

Business rules must be documented and must not depend on
customer-specific hidden assumptions.

### NFR-05 Browser Support

The product must support the current major desktop versions of commonly
used modern browsers at the time of a release.

### NFR-06 Responsive Administration

Administrative pages must remain usable on common desktop and tablet
viewport sizes.

### NFR-07 Cashier Priority

The primary POS workflow must be optimized for desktop/tablet retail
operation and must remain usable without horizontal page-level scrolling
at supported POS viewport sizes.

### NFR-08 Accessibility

Interactive controls must have understandable text or accessible labels,
keyboard focus must remain visible, and validation must not rely solely
on color.

### NFR-09 Data Integrity

The product must prevent business records from entering states that
violate defined transaction, stock, uniqueness, or permission rules.

### NFR-10 Commercial Reusability

Store-specific identity must be configurable rather than permanently
embedded in the default product behavior.

------------------------------------------------------------------------

## 17. User Stories

### Owner

**US-OWN-01**\
As an Owner, I want to see today's sales so that I can quickly
understand current business performance.

**US-OWN-02**\
As an Owner, I want to view sales for a selected date range so that I
can review business performance over time.

**US-OWN-03**\
As an Owner, I want to see low-stock products so that I can identify
products requiring attention.

**US-OWN-04**\
As an Owner, I want to manage users so that only authorized staff can
access business functions.

**US-OWN-05**\
As an Owner, I want to configure store information so that SimplePOS
reflects my business identity.

### Administrator

**US-ADM-01**\
As an Administrator, I want to add products so that they can be sold
through the POS.

**US-ADM-02**\
As an Administrator, I want to organize products into categories so that
product management is easier.

**US-ADM-03**\
As an Administrator, I want to adjust stock with a reason so that
corrections remain traceable.

**US-ADM-04**\
As an Administrator, I want to search transactions so that I can respond
to operational questions.

### Cashier

**US-CAS-01**\
As a Cashier, I want to find a product quickly so that I can serve
customers efficiently.

**US-CAS-02**\
As a Cashier, I want to change quantities in the cart so that the cart
matches the customer's purchase.

**US-CAS-03**\
As a Cashier, I want the system to calculate totals automatically so
that I do not need a separate calculator.

**US-CAS-04**\
As a Cashier, I want to enter the cash received and see the change so
that I can complete payment accurately.

**US-CAS-05**\
As a Cashier, I want a receipt after checkout so that the customer has
evidence of the transaction.

------------------------------------------------------------------------

## 18. User Journeys

### Journey A --- Standard Cash Sale

1.  Cashier signs in.
2.  Cashier opens the POS.
3.  Cashier searches for or identifies the first product.
4.  Cashier adds the product to the cart.
5.  Cashier repeats product selection as required.
6.  Cashier changes quantities if required.
7.  System displays subtotal.
8.  Cashier applies an allowed transaction discount if required.
9.  System displays final amount due.
10. Cashier enters cash received.
11. System displays change.
12. Cashier confirms checkout.
13. System validates the transaction.
14. System completes the transaction.
15. System reduces applicable stock.
16. System assigns an invoice number.
17. System presents the completed transaction/receipt.
18. Transaction becomes available in history and reports.

### Journey B --- Product Creation

1.  Authorized user signs in.
2.  User opens product management.
3.  User selects Add Product.
4.  User enters required product information.
5.  User submits the product.
6.  System validates required fields and uniqueness rules.
7.  If valid, the product is saved.
8.  If active and sellable, the product becomes available to the POS.

### Journey C --- Manual Stock Adjustment

1.  Authorized user opens the relevant inventory/product stock function.
2.  User selects a product.
3.  User enters the adjustment quantity/direction.
4.  User enters a required reason.
5.  System displays or identifies the resulting change.
6.  User confirms the adjustment.
7.  System updates stock.
8.  System creates a stock movement/audit record.
9.  Updated stock becomes visible to authorized users and POS
    availability rules.

### Journey D --- Owner Reviews Sales

1.  Owner signs in.
2.  Owner views dashboard indicators.
3.  Owner opens reports.
4.  Owner selects a reporting period.
5.  System displays report results for the selected period.
6.  Owner can inspect relevant transaction/product details according to
    available report functions.

------------------------------------------------------------------------

## 19. Business Rules

**BR-01**\
Each completed transaction must have one unique invoice number.

**BR-02**\
A completed transaction must contain at least one item with a quantity
greater than zero.

**BR-03**\
Product sale quantity must be a positive value.

**BR-04**\
Selling price must not be negative.

**BR-05**\
Transaction total must not be negative.

**BR-06**\
Transaction-level discount must not exceed the amount it is allowed to
reduce.

**BR-07**\
Cash received must be equal to or greater than the amount due before a
cash transaction can complete.

**BR-08**\
Cash change equals cash received minus final amount due.

**BR-09**\
Inactive products cannot be added to a new sale.

**BR-10**\
Where negative stock is not permitted, a transaction cannot complete if
the required sale quantity exceeds available stock.

**BR-11**\
Stock is reduced only when the sale is successfully completed.

**BR-12**\
A failed checkout must not be counted as sales revenue.

**BR-13**\
Completed transaction item prices are historical snapshots and must not
be recalculated when current product prices change.

**BR-14**\
Historical transactions must retain the identity of the cashier who
completed them even if that user's account is later deactivated.

**BR-15**\
Manual stock adjustments require a reason and responsible user.

**BR-16**\
Users may only perform actions permitted by their role.

**BR-17**\
A SKU must be unique.

**BR-18**\
A populated barcode must be unique.

**BR-19**\
Reports must exclude incomplete/failed checkout attempts.

**BR-20**\
Ordinary product deactivation must preserve historical transaction
records.

**BR-21**\
The MVP operates as a single-store business instance unless explicitly
extended in a future version.

**BR-22**\
The product's default MVP payment behavior supports cash payment;
additional payment methods are future enhancements unless explicitly
enabled in a later approved scope.

------------------------------------------------------------------------

## 20. Permissions Matrix

Legend:

-   **Full** --- create/view/update or perform the relevant operation.
-   **View** --- read-only access.
-   **Own/Allowed** --- limited to the transaction scope allowed for the
    cashier.
-   **No** --- access must be denied.

  Capability                            Owner   Administrator                            Cashier
  ----------------------------------- ------- --------------- ----------------------------------
  Dashboard                              Full            Full   Limited/No business-wide metrics
  POS                                    Full            Full                               Full
  Create sale                            Full            Full                               Full
  View transaction history               Full            Full                        Own/Allowed
  View transaction detail                Full            Full                        Own/Allowed
  Product management                     Full            Full              View through POS only
  Category management                    Full            Full                                 No
  Manual stock adjustment                Full            Full                                 No
  Stock movement history                 Full            Full                                 No
  Reports                                Full            Full                                 No
  User management                        Full         Limited                                 No
  Assign Owner role                      Full              No                                 No
  Store settings                         Full         Limited                                 No
  Audit trail                            Full      Limited/No                                 No
  Deactivate products                    Full            Full                                 No
  Deactivate users                       Full         Limited                                 No
  Hard-delete completed transaction        No              No                                 No

Administrator limitations must be consistently defined by the released
product configuration. The default commercial product must never allow
an Administrator to create, promote, demote, or deactivate the protected
Owner account unless an explicit future requirement changes this rule.

------------------------------------------------------------------------

## 21. Main Modules

### MOD-01 Authentication

Login, logout, session access, active-account enforcement.

### MOD-02 Dashboard

Business summary for authorized management roles.

### MOD-03 User & Role Management

Operational user accounts and role assignments.

### MOD-04 Category Management

Product classification.

### MOD-05 Product Management

Product master information and sellable status.

### MOD-06 POS

Cart, discount, payment, checkout, and transaction completion.

### MOD-07 Transaction Management

Transaction history, detail, search, and receipt access.

### MOD-08 Inventory

Current stock, adjustments, low-stock visibility, and stock movement
history.

### MOD-09 Reports

Sales and product-sales reporting.

### MOD-10 Store Settings

Business identity, currency display, logo, and receipt configuration.

### MOD-11 Audit Trail

Traceability for defined sensitive actions.

------------------------------------------------------------------------

## 22. Dashboard Requirements

**DASH-01**\
The management dashboard must display sales revenue for the current
business day.

**DASH-02**\
The dashboard must display the number of completed transactions for the
current business day.

**DASH-03**\
The dashboard must display low-stock information or a count/list
sufficient for the user to identify affected products.

**DASH-04**\
The dashboard must display a best-selling product indicator based on
completed sales for a clearly defined period.

**DASH-05**\
Dashboard sales totals must reconcile with report totals when the same
period and transaction eligibility rules are used.

**DASH-06**\
Cashiers must not receive unrestricted access to business-wide
management metrics by default.

**DASH-07**\
If there are no eligible transactions for a metric, the dashboard must
show an explicit zero/empty state rather than misleading data.

------------------------------------------------------------------------

## 23. Reporting Requirements

**REP-01**\
Authorized users must be able to view a daily sales report.

**REP-02**\
Authorized users must be able to select a valid date range for sales
reporting.

**REP-03**\
The sales report must show at least total eligible sales and completed
transaction count.

**REP-04**\
The product-sales report must identify quantities sold per product for
the selected period.

**REP-05**\
Report calculations must use completed eligible transactions only.

**REP-06**\
A report must clearly display the period being reported.

**REP-07**\
If no eligible data exists, the report must show a clear empty state.

**REP-08**\
Changing current product information must not retroactively alter
historical transaction sales values in reports.

**REP-09**\
Report totals for a given period must reconcile with the transaction
records included under the same rules.

------------------------------------------------------------------------

## 24. Notification Requirements

The MVP does not require real-time push, SMS, email, or messaging-app
notifications.

It must support in-application feedback and operational alerts.

**NOT-01**\
The system must show clear success feedback after a successful
record-changing action.

**NOT-02**\
The system must show clear error feedback when an action fails.

**NOT-03**\
The system must warn the cashier when checkout cannot continue because
of insufficient stock.

**NOT-04**\
Management users must have a visible way to identify low-stock products.

**NOT-05**\
Validation messages must identify the field or business condition that
requires correction.

**NOT-06**\
The system must not display a success notification for an operation that
did not complete successfully.

External notification channels are out of MVP scope.

------------------------------------------------------------------------

## 25. Search and Filtering Requirements

**SRCH-01**\
POS product search must support product name.

**SRCH-02**\
POS product lookup must support SKU.

**SRCH-03**\
POS product lookup must support barcode when a barcode is configured.

**SRCH-04**\
Product management must support searching by product name, SKU, or
barcode.

**SRCH-05**\
Transaction history must support exact or sufficiently precise
invoice-number search.

**SRCH-06**\
Transaction history must support date-range filtering.

**SRCH-07**\
Management transaction history must support cashier filtering where
relevant.

**SRCH-08**\
Search with no matching records must return a clear empty result state.

**SRCH-09**\
Clearing active filters must restore the unfiltered result set allowed
by the user's permissions.

**SRCH-10**\
Search results must never expose records the user is not authorized to
view.

------------------------------------------------------------------------

## 26. Import/Export Requirements

Bulk import/export is not required for the core MVP transaction
workflow.

To maintain commercial clarity:

**IMP-01**\
The MVP may be released without bulk product import.

**IMP-02**\
The MVP may be released without spreadsheet export if reporting can be
reviewed inside the product.

**IMP-03**\
If CSV product import is included in a commercial MVP release, the
system must validate required fields, SKU uniqueness, barcode uniqueness
where applicable, numeric values, and invalid rows before accepting
records.

**IMP-04**\
If an import contains invalid records, the user must receive a clear
result identifying rejected records or validation issues.

**EXP-01**\
If report export is included, exported totals must match the
corresponding on-screen report for the same filters.

**EXP-02**\
Exports must respect the current user's access permissions.

Recommended post-MVP additions: - Product CSV import. - Product CSV
export. - Sales CSV/Excel export. - Report PDF export.

------------------------------------------------------------------------

## 27. File Upload Requirements

The MVP requires file upload only where needed for store branding.

**FILE-01**\
Authorized users must be able to upload a store logo.

**FILE-02**\
The system must accept only explicitly supported image file types.

**FILE-03**\
The system must reject files exceeding the configured product upload
limit.

**FILE-04**\
An invalid file must not replace the currently valid store logo.

**FILE-05**\
The user must receive a clear validation message when an upload is
rejected.

**FILE-06**\
Uploaded store logos must be displayable in the business identity areas
for which the product defines logo support.

Product image upload is optional for MVP and must not block core POS
completion.

------------------------------------------------------------------------

## 28. Audit Trail Requirements

**AUD-01**\
The system must record successful manual stock adjustments.

**AUD-02**\
A manual stock adjustment audit record must identify the responsible
user, affected product, adjustment value, reason, and timestamp.

**AUD-03**\
The system must preserve the responsible cashier on every completed
transaction.

**AUD-04**\
Changes to user role or active status must be auditable for management
review.

**AUD-05**\
Changes to sensitive store configuration must be auditable.

**AUD-06**\
Audit records must not be editable by Cashiers.

**AUD-07**\
Ordinary application users must not be able to silently delete audit
records through standard product functions.

**AUD-08**\
Audit information must only be visible to roles explicitly permitted to
access it.

------------------------------------------------------------------------

## 29. Security Requirements

**SEC-01**\
Unauthenticated users must not access protected business functions.

**SEC-02**\
The product must enforce authorization on protected actions, not merely
hide interface controls.

**SEC-03**\
A Cashier attempting an Owner-only function must be denied.

**SEC-04**\
Inactive accounts must not be able to authenticate successfully.

**SEC-05**\
Sensitive authentication credentials must never be displayed back to
users in readable form after being set.

**SEC-06**\
User input must be validated before it changes protected business data.

**SEC-07**\
Users must not be able to access another protected record merely by
changing an identifier when their role does not permit that record.

**SEC-08**\
The product must require authentication again according to the released
session/security policy after the user's authenticated session is no
longer valid.

**SEC-09**\
Completed transaction and audit history must not offer unrestricted
destructive actions.

**SEC-10**\
File uploads must be validated against the product's permitted upload
rules.

**SEC-11**\
Security-relevant error messages must not expose credentials, secrets,
or unnecessary internal system details.

------------------------------------------------------------------------

## 30. Localization Requirements

**LOC-01**\
Store currency display must be configurable from supported currency
options.

**LOC-02**\
All monetary values shown to the user must follow the active store
currency display rules consistently.

**LOC-03**\
The product must have a consistent date and time display convention.

**LOC-04**\
Receipt monetary formatting must match the configured store currency
display.

**LOC-05**\
The default commercial release should support Indonesian retail usage,
including Indonesian Rupiah display.

**LOC-06**\
Product text and business labels should be structured so that additional
language support can be introduced in a future release without changing
core business rules.

Full multi-language translation management is not required for MVP.

------------------------------------------------------------------------

## 31. Performance Requirements

Performance targets apply under the supported commercial deployment
profile and expected small-business data volume.

**PERF-01**\
After a user submits a valid POS product search, matching results should
normally become usable within 2 seconds under expected operating
conditions.

**PERF-02**\
Cart quantity and total changes must provide visible updated results
within 1 second after the application receives and processes the user's
local interaction under expected operating conditions.

**PERF-03**\
A standard successful checkout should return a success result or
receipt-ready state within 3 seconds under expected operating
conditions.

**PERF-04**\
A standard dashboard should become usable within 3 seconds under
expected operating conditions.

**PERF-05**\
A standard 30-day sales report should become usable within 5 seconds
under expected operating conditions.

**PERF-06**\
The product must provide pagination or equivalent bounded result
behavior for transaction/product lists when record counts exceed the
normal single-view capacity.

Performance acceptance testing must define the dataset size, user
concurrency, network conditions, and deployment profile used for
measurement before release certification.

------------------------------------------------------------------------

## 32. Data Retention Requirements

**RET-01**\
Completed transaction records must be retained until an authorized
retention or archival policy explicitly permits otherwise.

**RET-02**\
Product deactivation must not remove product information already
represented in historical transaction snapshots.

**RET-03**\
User deactivation must not remove historical transaction or audit
references.

**RET-04**\
Stock movement history must remain available for the product's defined
operational retention period.

**RET-05**\
The default MVP must not automatically purge completed transactions.

**RET-06**\
If a future configurable retention policy is introduced, it must not
silently remove financial or audit data without explicit authorized
configuration and documented behavior.

**RET-07**\
Backup lifecycle and regulatory retention automation are not part of the
MVP unless separately specified.

------------------------------------------------------------------------

## 33. Error Handling Requirements

**ERR-01**\
Validation failures must identify actionable corrections.

**ERR-02**\
A failed checkout must not be shown as completed.

**ERR-03**\
If a checkout cannot complete consistently, the user must receive an
error and the system must not create contradictory completed-sale and
stock states.

**ERR-04**\
Duplicate SKU submission must return a clear validation error.

**ERR-05**\
Duplicate non-empty barcode submission must return a clear validation
error.

**ERR-06**\
Insufficient-stock checkout must identify that the requested sale cannot
proceed under the stock rule.

**ERR-07**\
Unauthorized access must be denied without exposing protected content.

**ERR-08**\
A missing requested record must result in an appropriate not-found state
rather than unrelated data.

**ERR-09**\
Unexpected errors presented to normal users must use understandable
language and must not expose sensitive internal diagnostic information.

**ERR-10**\
When a recoverable form validation error occurs, previously valid user
input should be preserved where product behavior reasonably allows it.

------------------------------------------------------------------------

## 34. Acceptance Criteria

The MVP is acceptable only when all critical requirements and the
following end-to-end criteria pass.

### AC-01 Authentication

**Given** an active valid user\
**When** the user submits correct credentials\
**Then** the user gains access to functions permitted by the assigned
role.

### AC-02 Invalid Login

**Given** invalid credentials\
**When** authentication is attempted\
**Then** access is denied and no authenticated session is created.

### AC-03 Product Creation

**Given** an authorized user and valid unique product data\
**When** the product is submitted\
**Then** the product is saved and can become available to the POS when
active.

### AC-04 Duplicate SKU

**Given** an existing SKU\
**When** another product is submitted with the same SKU\
**Then** the submission is rejected.

### AC-05 Standard Sale

**Given** an authenticated Cashier and an active in-stock product\
**When** the Cashier adds the product, enters sufficient payment, and
confirms checkout\
**Then** exactly one completed transaction is created, stock is reduced
appropriately, an invoice number is assigned, and a receipt is
available.

### AC-06 Insufficient Payment

**Given** a cart total greater than the cash received\
**When** checkout is attempted\
**Then** checkout is rejected and no completed transaction is created.

### AC-07 Insufficient Stock

**Given** a stock-controlled product with insufficient available
quantity\
**When** the Cashier attempts to complete a quantity exceeding available
stock\
**Then** checkout is rejected and stock is not reduced by the failed
attempt.

### AC-08 Historical Price Integrity

**Given** a completed transaction\
**When** the current product selling price is later changed\
**Then** the completed transaction continues to show its original
recorded sale price.

### AC-09 Transaction Search

**Given** a known invoice number and an authorized user\
**When** the invoice number is searched\
**Then** the matching transaction can be retrieved.

### AC-10 Stock Adjustment

**Given** an authorized user\
**When** a valid manual stock adjustment with a reason is confirmed\
**Then** stock changes by the requested amount and a traceable stock
movement is recorded.

### AC-11 Permission Enforcement

**Given** a Cashier account\
**When** the Cashier attempts to access user management or unrestricted
stock adjustment\
**Then** access is denied.

### AC-12 Reporting Reconciliation

**Given** a set of completed transactions within a known period\
**When** an authorized user generates a report for exactly that period\
**Then** report totals match the eligible completed transaction records.

### AC-13 Store Settings

**Given** an Owner\
**When** valid store identity settings are changed\
**Then** subsequent supported displays/receipts use the new
configuration without altering historical transaction financial values.

### AC-14 Failed Checkout Consistency

**Given** an error that prevents transaction completion\
**When** checkout fails\
**Then** the system does not leave a transaction marked completed with
missing or contradictory required stock effects.

------------------------------------------------------------------------

## 35. Definition of Done

A feature is Done only when:

1.  Its approved product requirement is implemented in observable
    product behavior.
2.  All applicable acceptance criteria pass.
3.  Role permissions are enforced.
4.  Required validation is present.
5.  Relevant error and empty states are handled.
6.  Relevant audit behavior is present.
7.  The feature does not violate transaction or inventory business
    rules.
8.  Existing critical POS flows continue to pass regression testing.
9.  User-facing labels and behavior are consistent with the product.
10. The feature is usable at supported viewport sizes.
11. Security acceptance checks relevant to the feature pass.
12. Product documentation is updated when behavior or configuration
    changes.
13. No unresolved release-blocking defect remains.
14. The feature contains no unapproved expansion beyond the agreed
    scope.
15. Product Owner or designated acceptance authority approves the
    completed behavior.

The MVP itself is Done when all mandatory MVP modules satisfy this
definition and the full sale-to-stock-to-report journey passes
end-to-end acceptance testing.

------------------------------------------------------------------------

## 36. Product Risks

  --------------------------------------------------------------------------
  ID                Risk                Impact            Mitigation
  ----------------- ------------------- ----------------- ------------------
  R-01              Feature creep turns High              Maintain explicit
                    POS into ERP                          MVP and
                                                          out-of-scope lists

  R-02              Incorrect financial Critical          Explicit business
                    calculations                          rules and
                                                          calculation
                                                          acceptance tests

  R-03              Stock and           Critical          Treat checkout
                    transaction                           consistency as
                    inconsistency                         release-blocking

  R-04              Cashier workflow is High              Prioritize the
                    too complex                           core POS journey
                                                          and usability
                                                          testing

  R-05              Weak permission     Critical          Permission matrix
                    controls                              and authorization
                                                          acceptance tests

  R-06              Historical records  High              Preserve
                    change after                          transaction
                    master-data edits                     snapshots

  R-07              Commercial product  Medium            Focus on
                    is too generic                        simplicity,
                                                          configurability,
                                                          documentation, and
                                                          retail usability

  R-08              Product becomes     High              Keep default core
                    customer-specific                     business behavior
                                                          industry-neutral

  R-09              Reporting disagrees High              Define completed
                    with transactions                     transactions as
                                                          report source of
                                                          truth

  R-10              Support burden      Medium            Keep core scope
                    grows with                            stable and
                    customization                         separate future
                                                          modules

  R-11              Destructive actions High              Prefer
                    remove traceability                   deactivation and
                                                          retained
                                                          historical records

  R-12              SaaS complexity is  High              Keep multi-tenancy
                    introduced too                        and billing out of
                    early                                 MVP
  --------------------------------------------------------------------------

------------------------------------------------------------------------

## 37. Future Development

Future development should be modular and demand-driven.

### Candidate Business Features

-   Customer management.
-   Supplier management.
-   Purchase management.
-   Expense tracking.
-   Returns/refunds.
-   Cashier shifts.
-   Stock opname.
-   Barcode label printing.
-   Product image management.
-   Multiple payment methods.
-   More advanced discount rules.
-   Profit/margin reporting.
-   Advanced inventory reporting.
-   Data backup/restore experience.
-   Product spreadsheet import/export.
-   Sales spreadsheet/PDF export.
-   Low-stock external notifications.

### Candidate Commercial Modules

-   Retail Pro Module.
-   Restaurant Module.
-   Fashion/Variant Module.
-   Workshop/Service Module.
-   Multi-Branch Module.
-   Advanced Inventory Module.
-   Accounting Integration Module.

### Candidate SaaS Capabilities

Only after the self-hosted core is stable:

-   Multi-tenancy.
-   Tenant onboarding.
-   Subscription plans.
-   Billing.
-   Usage limits.
-   Tenant administration.
-   Central SaaS operator dashboard.
-   Tenant data isolation requirements.
-   SaaS backup and operational policies.

------------------------------------------------------------------------

## 38. Version Roadmap

### Version 1.0 --- SimplePOS Core MVP

**Goal:** Deliver a commercially presentable general-retail POS core.

Scope: - Authentication. - Owner/Admin/Cashier roles. - Dashboard. -
Categories. - Products. - POS/cart. - Cash payment. - Transaction-level
discount. - Checkout. - Transaction history. - Basic inventory. - Manual
stock adjustment. - Stock movement history. - Sales reports. - Printable
receipt. - Users. - Store settings. - Basic audit trail.

**Exit condition:** The end-to-end sale → payment → transaction → stock
→ receipt → report workflow passes acceptance criteria.

### Version 1.5 --- Commercial Productivity

**Goal:** Improve deployment value and day-to-day management.

Candidate scope: - Customers. - Suppliers. - Expenses. - Product
import/export. - Report export. - Barcode labels. - Backup/restore
experience. - Improved reporting. - Product images. - Basic
returns/refunds after separate business-rule definition.

### Version 2.0 --- SimplePOS Pro

**Goal:** Support more mature retail operations.

Candidate scope: - Purchasing. - Cashier shifts. - Stock opname. -
Multiple payment methods. - Profit analysis. - Advanced discounts. -
Advanced inventory controls. - Extended audit capabilities.

### Version 3.0 --- Modular Business Editions

**Goal:** Increase commercial reuse through optional vertical modules.

Candidate editions/modules: - Retail. - Restaurant. - Fashion. -
Workshop/service business. - Multi-branch. - Advanced inventory.

Each module must preserve a stable SimplePOS core and must not force
unrelated complexity on customers who do not require the module.

### Version 4.0 --- SaaS Platform

**Goal:** Offer SimplePOS as a hosted multi-customer service after the
core product has proven stable.

Candidate scope: - Multi-tenant business accounts. - Subscription
plans. - Billing lifecycle. - Tenant administration. - Usage controls. -
SaaS operator management. - Tenant-level configuration. - SaaS security,
isolation, backup, and operational requirements.

------------------------------------------------------------------------

# Product Principle

SimplePOS must prioritize:

1.  **Simple over feature-heavy.**
2.  **Reliable over visually complex.**
3.  **Traceable over destructive.**
4.  **Configurable over customer-specific.**
5.  **Reusable over one-off customization.**
6.  **Stable core over premature SaaS complexity.**
7.  **Explicit business rules over ambiguous behavior.**

The MVP is successful when a small retail business can reliably
configure products, process sales, receive payment, maintain basic
stock, retrieve transaction history, and review sales results without
requiring unrelated ERP functionality.
