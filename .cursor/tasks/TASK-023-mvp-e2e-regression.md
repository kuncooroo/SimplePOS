# TASK-023 — MVP End-to-End Regression

**Task ID:** TASK-023  
**Title:** MVP end-to-end regression  
**Filename:** `TASK-023-mvp-e2e-regression.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`. Do not add product features.

---

## Objective

Prove the Version 1.0 exit condition: login → product → cart → payment → checkout → transaction → stock → receipt → report, plus PRD AC-01–14, with automated tests (and browser coverage if the project’s test stack supports it).

---

## Background

PRD §34 AC-01–14, §35 Definition of Done, §38 Version 1.0 exit condition, SRS §33.3–33.6, CURSOR.md §36.

`docs/ROADMAP.md` was not in the repo; this task is the Version 1.0 gate.

---

## Dependencies

- TASK-001 through TASK-022 (all MVP features)

---

## Files likely affected

- `tests/Feature/Sales/CheckoutJourneyTest.php` (or Pest equivalent)
- `tests/Feature/Regression/` 
- Optional: `tests/Browser/` if Laravel Dusk or equivalent is already justified — **do not add Dusk solely for ceremony**; Feature/Livewire tests are sufficient if they cover the journey. If you add a browser test, one critical sale path only.
- `database/seeders/DemoSeeder.php` if missing: Owner, Admin, Cashier, category, products in/out of stock — for manual QA only, no production secrets.

---

## Database changes

None.

---

## Backend requirements

None except tests and optional demo seeder.

Seeder (if added):

- protected Owner, one Administrator, one Cashier (known local passwords in `.env.example` only)
- one category, two active products (stock 10 and stock 1), one inactive product
- store settings row if not seeded

---

## Frontend requirements

None.

---

## Validation rules

N/A.

---

## Authorization rules

Tests must include cashier denied: users, stock adjust, reports, dashboard, audit, settings.

---

## Business rules

Re-assert BR-01–22 as covered by existing tests; fill gaps found.

---

## Edge cases

Add tests if missing:

- concurrent checkout of last unit (optional Feature with two sequential lock tests if true parallel is hard)
- historical snapshot after rename/price change
- dashboard vs sales report today

---

## Security considerations

- Tests must not commit real secrets.
- Confirm debug is not required to be on for tests.

---

## Testing requirements

Single feature (or grouped) journey test:

1. Login cashier.
2. Search product.
3. Add to cart, set qty, discount, cash.
4. Confirm checkout.
5. Assert one COMPLETED transaction, items, stock, SALE movement, invoice.
6. Open receipt.
7. Login owner.
8. Transaction search by invoice.
9. Sales report for that day includes the total.
10. Product sales report includes qty.

Also run/repair AC-01–14 coverage across the suite so the full set is green.

---

## Acceptance criteria

- [ ] Version 1.0 exit condition is automated.
- [ ] AC-01 through AC-14 pass in CI/local suite.
- [ ] No new MVP features slipped in.

---

## Definition of Done

- [ ] `php artisan test` (or project equivalent) passes for the MVP suite.
- [ ] Gaps found in earlier tasks are fixed in the **owning module**, not with hacks in this task.
- [ ] Out-of-scope items remain absent (refunds, API, gateways, Redis).
