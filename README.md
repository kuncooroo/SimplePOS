# SimplePOS

Sistem Kasir & Penjualan Sederhana — Laravel source for a single-store cash POS.

## Local setup

```text
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

Create a MySQL database named `simplepos` (utf8mb4) matching `.env`.

Production: set `APP_DEBUG=false` and `APP_ENV=production`. Do not commit `.env`.

Login is added in TASK-002. Until then, `/login` is a placeholder and `/dashboard` shows the app shell.

## Docs

See `CURSOR.md`, `docs/USER_GUIDE.md`, and `.cursor/tasks/`.
