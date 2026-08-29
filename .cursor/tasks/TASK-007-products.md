# TASK-007 — Products

**Task ID:** TASK-007  
**Title:** Product management  
**Filename:** `TASK-007-products.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let authorized users create, search, edit, activate, and deactivate products with unique SKU, optional unique barcode, category, prices, and stock quantity. Inactive products must not be sellable later in POS.

---

## Background

MVP-04, FR-PROD-01–09, SFR-PROD-01–04, BR-04/17/18/20, BUSINESS_FLOW §7, DATABASE.md §25, AC-03/AC-04.

Product images are out of scope. Historical snapshot integrity is proven after checkout exists (TASK-012 + tests here for master-data edits not touching non-existent transactions).

---

## Dependencies

- TASK-006 (categories)

---

## Files likely affected

- `database/migrations/*_create_products_table.php`
- `app/Models/Product.php`
- `app/Models/Category.php` (relationship)
- `app/Policies/ProductPolicy.php`
- `app/Livewire/Catalog/ProductIndex.php`
- `app/Livewire/Catalog/ProductForm.php`
- `app/Livewire/Catalog/ProductShow.php` (optional detail)
- `resources/views/livewire/catalog/`
- `database/factories/ProductFactory.php`
- `routes/web.php`
- `tests/Feature/Catalog/`

Do not put checkout or stock-movement writes in this task. Initial `stock_quantity` is master data (opening stock). Manual adjustment with reason is TASK-016 — creating a product with initial stock does **not** require a movement unless you choose to; DATABASE.md treats current stock on the product. Prefer: initial stock set on create **without** a movement, and TASK-016/012 create movements for later changes. Document that choice in code comments if needed.

---

## Database changes

`products` (`DATABASE.md` §25):

- `category_id` FK → categories, indexed, **no cascade delete**
- `sku` VARCHAR(100) UNIQUE
- `barcode` VARCHAR(100) NULL UNIQUE (multiple NULLs allowed)
- `name` VARCHAR(200), indexed
- `selling_price` DECIMAL(15,2) not negative
- `cost_price` DECIMAL(15,2) nullable
- `stock_quantity` DECIMAL(15,3) default 0
- `active` BOOLEAN default true, indexed
- timestamps

DB unique on `sku` and unique on `barcode`. Check/app validation: `selling_price >= 0`, `stock_quantity >= 0` for create/edit in this UI (do not allow negative opening stock).

---

## Backend requirements

1. List: paginate, search name/SKU/barcode (FR-PROD-09), filter category and active.
2. Create/edit all mandatory fields; barcode optional.
3. New/changed products cannot use an **inactive** category (FR-CAT-04).
4. Activate/deactivate product (FR-PROD-06).
5. `ProductPolicy`: Owner/Admin manage; Cashier no management (POS view is TASK-009).
6. Eager-load `category` on list (`CURSOR.md` §30).
7. Money stored as decimal, never float.

---

## Frontend requirements

- Products table example columns: name, SKU, category, price, stock, status (`UI_UX.md` §11).
- Form field widths and labels per UI_UX.
- Money display via `<x-money>`.
- Empty and no-results states.
- Confirm deactivate.
- Cashier: no Products admin nav.

---

## Validation rules

| Field | Rules |
|---|---|
| name | required, max 200 |
| sku | required, max 100, unique |
| barcode | nullable, max 100, unique if not null/empty (treat `''` as null) |
| category_id | required, exists categories, category must be active on create/update |
| selling_price | required, numeric, min 0, 2 decimal places |
| cost_price | nullable, numeric, min 0 |
| stock_quantity | required, numeric, min 0, up to 3 decimal places |
| active | boolean |

---

## Authorization rules

- Owner/Admin: Full including deactivate (matrix).
- Cashier: No product management; 403 on admin routes.

---

## Business rules

- BR-04, BR-17, BR-18, BR-20.
- Inactive products remain in DB for history (FR-PROD-08 will apply once sales exist).
- Do not change any transaction tables (they do not exist yet).

---

## Edge cases

- Duplicate SKU (AC-04).
- Duplicate barcode when both non-null.
- Empty barcode allowed.
- Inactive category selected → reject.
- Cost price omitted.
- Large list pagination.

---

## Security considerations

- Authorize mutations.
- No raw SQL concatenation.
- Mass assignment: do not allow arbitrary columns.
- Cost price is operational data; still authorize like other product fields.

---

## Testing requirements

- AC-03 authorized create becomes listable.
- AC-04 duplicate SKU rejected at validation **and** DB unique.
- Duplicate barcode rejected.
- Inactive product remains stored (`active` false).
- Cashier denied.
- Inactive category cannot be assigned.

---

## Acceptance criteria

- [ ] MVP-04 and FR-PROD-01–07, FR-PROD-09.
- [ ] Unique SKU/barcode persisted.
- [ ] Selling price cannot be negative.

---

## Definition of Done

- [ ] Product admin complete for MVP master data.
- [ ] Tests pass.
- [ ] No product image upload.
- [ ] Placement matches PROJECT_STRUCTURE Catalog module.
