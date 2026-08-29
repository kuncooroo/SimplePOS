# TASK-008 — Store Settings

**Task ID:** TASK-008  
**Title:** Store settings  
**Filename:** `TASK-008-store-settings.md`

Cursor: implement only this task. Follow `CURSOR.md` and `docs/PROJECT_STRUCTURE.md`.

---

## Objective

Let authorized users configure store identity, contact details, currency display, receipt footer, logo, and global low-stock threshold. Changes must not rewrite historical sale amounts (no sale tables required yet). Sensitive changes must be audited.

---

## Background

MVP-09 (settings), FR-SET-01–06, FILE-01–03, LOC-01/05, AUD-05, DATABASE.md §19 / §29, SRS §22, AC-13.

One settings row for the single store. Not a JSON blob of all keys.

---

## Dependencies

- TASK-004 (gates `manageStoreSettings`)
- TASK-005 (`RecordActivity`, `STORE_SETTINGS_UPDATED`)

---

## Files likely affected

- `database/migrations/*_create_store_settings_table.php`
- `app/Models/StoreSetting.php`
- `app/Policies/StoreSettingPolicy.php`
- `app/Actions/Configuration/UpdateStoreSettings.php`
- `app/Livewire/Configuration/StoreSettingsForm.php`
- `resources/views/livewire/configuration/`
- `app/Support/Money.php` or update `<x-money>` to read currency from settings
- `storage/app/public` + `php artisan storage:link` documented in `.env.example`
- `tests/Feature/Configuration/`

---

## Database changes

`store_settings` (`DATABASE.md` §29): `store_name`, `address`, `phone`, `email`, `currency_code`, `currency_symbol`, `receipt_footer`, `logo_path`, `low_stock_threshold` DECIMAL(15,3), timestamps.

Seed one row: store name placeholder, `currency_code=IDR`, `currency_symbol=Rp`, sensible `low_stock_threshold` (e.g. 5).

Application enforces a single row (id=1 or first()).

---

## Backend requirements

1. `UpdateStoreSettings` in a DB transaction: update fields, handle logo replace, `RecordActivity`.
2. Logo: Laravel filesystem, store path only, validate MIME and size (`CURSOR.md` §29). Failed replacement must keep the previous valid logo.
3. Do not trust original filename; prevent path traversal.
4. Administrator **Limited** settings: default MVP — Administrator may update operational identity/logo/footer/threshold; if a field must be Owner-only, document it. Protected rule: do not invent extra Owner-only fields beyond the matrix. Matrix says Administrator Limited — allow the same form except do not add “destroy store” or multi-store. Keep one form; both Owner and Admin can update unless you explicitly restrict logo/currency to Owner. **Recommended default:** Owner and Administrator can update all MVP settings fields; Cashier cannot.
5. Currency: supported options at least IDR (LOC-05). A small allow-list (IDR, USD) is enough; do not build a world currency CMS.

---

## Frontend requirements

- Settings pages: Store information, Receipt, Localization (`UI_UX.md` §26).
- Logo preview; file input with helper text for type/size.
- Success toast; validation errors on fields (NOT-05).
- Nav “Settings” for authorized roles only.

---

## Validation rules

| Field | Rules |
|---|---|
| store_name | required, max 200 |
| address | nullable, string |
| phone | nullable, max 50 |
| email | nullable, email, max 191 |
| currency_code | required, in allow-list |
| currency_symbol | required, max 10 |
| receipt_footer | nullable, string |
| logo | nullable, image MIME (e.g. png, jpeg, webp), max size (document e.g. 2MB) |
| low_stock_threshold | required, numeric, min 0 |

---

## Authorization rules

- Owner: Full.
- Administrator: Limited — for MVP treat as can update this single settings record; cannot access if you later split Owner-only keys.
- Cashier: No.
- Authorize on the Action, not only the Livewire `mount`.

---

## Business rules

- FR-SET-06: subsequent displays use new config; historical financials unchanged (no writes to transactions).
- BR-21 single store.
- Logo binary not stored in DB.

---

## Edge cases

- Replace logo when old file missing.
- Invalid file type/size rejected; old logo remains.
- Missing settings row: seeder must prevent this; fail clearly if empty.
- `low_stock_threshold` = 0 allowed (everything with stock 0 is low / none — define: products with `stock_quantity <= threshold` and typically active).

---

## Security considerations

- FILE-02/03, SEC-10.
- Public disk vs private: logo must be displayable on receipts; use `public` disk with `storage:link` or an authorized file response. Do not allow SVG if XSS risk; prefer raster images.
- Audit must not include file binaries.

---

## Testing requirements

- Owner can update name; subsequent read shows new name (AC-13).
- Cashier denied.
- Invalid logo rejected; previous `logo_path` unchanged.
- `STORE_SETTINGS_UPDATED` audit row written.
- Currency allow-list rejects unknown codes.

---

## Acceptance criteria

- [ ] FR-SET-01–05, FILE-01–03.
- [ ] AUD-05 satisfied for settings updates.
- [ ] Single settings row.

---

## Definition of Done

- [ ] Settings UI works.
- [ ] Money component can use configured symbol/code.
- [ ] Tests pass.
- [ ] No product image feature added.
