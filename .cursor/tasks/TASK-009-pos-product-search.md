# TASK-009 — POS Product Search

**Task ID:** TASK-009  
**Title:** POS product search  
**Filename:** `TASK-009-pos-product-search.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Provide the POS screen product lookup: authorized sellers search active sellable products by name, SKU, or barcode and see results usable for the next cart task. Do not persist a sale.

---

## Background

MVP-05 (search/select), FR-POS-01–02 (start/select), SRCH-01–03, PERF-01, UI_UX.md §13 / §31, CURSOR.md §21.

Cart mutations are TASK-010. This task can render an empty cart panel as a layout placeholder.

---

## Dependencies

- TASK-007 (products)
- TASK-004 (`accessPos`)
- TASK-008 optional for money formatting; if settings exist, use them

---

## Files likely affected

- `app/Livewire/Pos/PosPage.php`
- `app/Livewire/Pos/ProductSearch.php`
- `resources/views/livewire/pos/`
- `routes/web.php`
- `tests/Feature/Sales/` Livewire tests

---

## Database changes

None.

---

## Backend requirements

1. Authenticated POS route for users with `accessPos` (Owner, Admin, Cashier).
2. Search active products only (`active` true). Inactive never appear (BR-09 / FR-PROD-07).
3. Match name (partial), SKU, barcode (SRCH-01–03).
4. Debounce search (`CURSOR.md` §21).
5. Limit result size (e.g. 20); do not load the full catalog into Livewire state.
6. Eager-load nothing extra; select columns needed for display (name, sku, barcode, selling_price, stock_quantity).
7. Selecting a product may dispatch a Livewire event for TASK-010; until then, selection can highlight the row only.

---

## Frontend requirements

- POS desktop layout: search + results left, cart placeholder right (`UI_UX.md` §31).
- Keyboard: focus search on load if practical (UI_UX §22).
- Empty search vs no matches (SRCH-08).
- Show price and stock in results; do not allow checkout.
- Cashier-friendly density; no admin chrome in the main POS grid.

---

## Validation rules

Search string: optional string, max length (e.g. 100). Ignore empty as “no query” (show empty-state or a small “type to search” hint — do not dump entire catalog).

---

## Authorization rules

- `accessPos` required.
- Users without POS (none in MVP except unauthenticated) 403.
- Search must not return products the seller cannot sell; MVP: all POS roles can sell any active product (no per-product ACL).

---

## Business rules

- BR-09 inactive not in new sales (exclude from search).
- Do not use inactive categories as a hard hide if the product itself is still active — product `active` is the sellable flag. (If a category is inactive but product remains active, still hide from POS if you interpret FR-CAT-04 strictly on **assignment**; already-assigned products: **recommended:** POS uses product `active` only unless docs say otherwise. PRD FR-PROD-07 is the POS rule.)

---

## Edge cases

- Barcode exact match should rank first or return immediately.
- SQL wildcard characters in search: escape `LIKE`.
- Low stock still searchable if active and quantity > 0; zero stock may still show but TASK-010/012 reject oversell.

---

## Security considerations

- Authorize `accessPos` on `mount` and search method.
- Parameterized Eloquent, no raw user LIKE concatenation without binding.
- Do not expose `cost_price` on POS results.

---

## Testing requirements

- Livewire: search by name, SKU, barcode finds active product.
- Inactive product omitted.
- Guest redirected.
- Cashier allowed.
- Result cap respected.

---

## Acceptance criteria

- [ ] SRCH-01–03 on POS.
- [ ] PERF-01 considered (indexed name/sku/barcode already on products).
- [ ] No checkout.

---

## Definition of Done

- [ ] POS page exists with working search.
- [ ] Tests pass.
- [ ] Cart not required to persist items yet.
