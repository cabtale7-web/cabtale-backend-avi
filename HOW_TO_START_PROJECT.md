# How to Start This Project (Cabtale Backend)

This repository is a **Laravel 10 modular backend**.

- Main app: `cabtale_main-main`
- Admin login route: `/admin/auth/login`
- API base: `/api`
- Note: a separate React frontend is **not present** in this repo. For VM split frontend/backend deployment, see `DEPLOY_FRONTEND_BACKEND_GCP.md`.

## 1) What You Need Before Start

Required:

- PHP `8.2` (with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, `zip`, `redis` if using Redis)
- Composer `2.x`
- MySQL `8+` (or compatible)
- Node.js + npm (only needed if you rebuild assets)

Optional but used by current `.env`:

- Redis (for cache/session/queue if you keep Redis drivers)
- Google Cloud Storage credentials (if `FILESYSTEM_DRIVER=gcs`)
- Reverb/Pusher config (if broadcast is enabled)

## 2) Important Current Config Notes

From your current `.env`:

- `APP_URL` is `www.cabtale.com` (missing `http://` or `https://`).
- `BROADCAST_DRIVER` appears twice; keep only one final value.
- `.env` contains real-looking secrets (DB/mail/pusher). Rotate them if shared.

## 3) First-Time Setup (Recommended Local Mode)

Run from project folder:

```bash
cd cabtale_main-main
```

Install dependencies:

```bash
composer install
npm install
```

### 3.1 Configure `.env` for local start (minimal dependency mode)

Edit `.env` and set at least:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cabtale_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
FILESYSTEM_DRIVER=local
BROADCAST_DRIVER=log
```

This avoids Redis/Reverb/GCS during first boot.

## 4) Database Setup

Create DB and then choose one method:

### Option A: Migrations + seed

```bash
php artisan key:generate --force
php artisan migrate --seed
```

### Option B: Import provided SQL snapshot

```bash
# create empty db first, then import
mysql -u your_db_user -p cabtale_db < installation/backup/database.sql
php artisan key:generate --force
```

## 5) Final Prep

```bash
php artisan storage:link
php artisan optimize:clear
```

If you changed frontend assets, build them:

```bash
npm run dev
# or
npm run production
```

## 6) Start Project

Start backend:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

- App/Landing: `http://127.0.0.1:8000`
- Admin login: `http://127.0.0.1:8000/admin/auth/login`

## 7) Optional Background Services

Only if enabled in `.env`:

```bash
php artisan queue:work
php artisan schedule:work
php artisan reverb:start
```

## 8) Quick Health Check

This project includes a built-in readiness command:

```bash
php artisan test:connections
# includes storage read/write test
php artisan test:connections --with-storage
```

## 9) Common Startup Errors

- `php not recognized`: install PHP 8.2 and add to PATH.
- `composer not recognized`: install Composer and restart terminal.
- DB connection error: verify `DB_*` values and MySQL service.
- Redis connection error: either run Redis or switch to `file/sync` drivers.
- CSS/JS broken URL: set `APP_URL` with scheme (`http://...` or `https://...`).

## 10) Production / VM Deployment

Use the existing runbook:

- `DEPLOY_FRONTEND_BACKEND_GCP.md`

It already contains separate domain setup for frontend + backend on one VM.
