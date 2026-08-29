# TASK-006 — Categories

**Task ID:** TASK-006  
**Title:** Category management  
**Filename:** `TASK-006-categories.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let Owner and Administrator create, search, edit, activate, and deactivate product categories. Cashiers have no category management.

---

## Background

MVP-03, FR-CAT-01–05, SFR-CAT-01–02, BUSINESS_FLOW §6, DATABASE.md §24.

Deactivating a category must not erase products or history. Product assignment rules that block inactive categories on **new** product create/edit are enforced here for the category model and completed in TASK-007.

---

## Dependencies

- TASK-001, TASK-002, TASK-004

---

## Files likely affected

- `database/migrations/*_create_categories_table.php`
- `app/Models/Category.php`
- `app/Policies/CategoryPolicy.php`
- `app/Livewire/Catalog/CategoryIndex.php`
- `app/Livewire/Catalog/CategoryForm.php`
- `resources/views/livewire/catalog/`
- `database/factories/CategoryFactory.php`
- `routes/web.php`
- `tests/Feature/Catalog/`

Use Eloquent directly. A Category Action is optional; only add one if create/update is more than a validated save.

---

## Database changes

`categories`:

| Column | Rules |
|---|---|
| `id` | PK |
| `name` | VARCHAR(150), required |
| `active` | BOOLEAN, default true, indexed |
| timestamps | yes |

FK later from products: `restrict`/`restrict` — do **not** cascade delete categories into products (`DATABASE.md` §13). This task only creates `categories`.

No unique name constraint unless DATABASE.md requires it (it does not). Avoid duplicate-name UX if cheap: optional unique `name` is **not** required; do not invent it.

---

## Backend requirements

1. Paginated list, search by name (FR-CAT-05), filter active/inactive if simple.
2. Create/edit name + active.
3. Activate/deactivate without deleting.
4. `CategoryPolicy`: manage for Owner and Administrator; deny Cashier.
5. Paginate; prevent N+1 (no product count required unless cheap `withCount` for display — optional).

---

## Frontend requirements

- Categories CRUD per UI_UX list/form standards.
- Status badge for active/inactive.
- Confirm deactivate if the category is in use **once products exist**; until TASK-007, deactivate is always allowed.
- Empty state.
- Nav item only if `manageCategories`.

---

## Validation rules

| Field | Rules |
|---|---|
| name | required, string, max 150 |
| active | boolean |

---

## Authorization rules

- Owner, Administrator: Full.
- Cashier: No (matrix). Direct URL → 403.

---

## Business rules

- Inactive category must not be used for **new** sellable product assignments where that would violate active-data rules (FR-CAT-04) — enforce in TASK-007 product form; this task must keep `active` accurate.
- Deactivation does not delete categories or child data.

---

## Edge cases

- Search with no matches → empty state (SRCH-08).
- Deactivate then reactivate.
- Cashier denied.

---

## Security considerations

- Authorize every Livewire mutation.
- No mass-assignment of `id`.
- XSS: escape category names in Blade.

---

## Testing requirements

- Authorized create/edit/search/deactivate.
- Cashier cannot access.
- Inactive flag persists.
- Name required.

---

## Acceptance criteria

- [ ] MVP-03 and FR-CAT-01–03, FR-CAT-05.
- [ ] Cashier denied.
- [ ] No destructive delete of categories that would be needed for history (no delete in MVP UI).

---

## Definition of Done

- [ ] Category module usable.
- [ ] Policy + tests pass.
- [ ] Follows PROJECT_STRUCTURE Catalog Livewire paths.
