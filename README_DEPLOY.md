## Deployment: Supabase Postgres + Vercel PHP Backend

This document describes how to deploy the backend API for this project using **Supabase PostgreSQL** and the **vercel-community/php** runtime on Vercel.

The existing PHP templates (`index.php`, `about.php`, etc.) can call the new `/api` endpoints via `fetch`/AJAX or form posts.

---

### 1. Supabase Setup

1. Create a new project in Supabase.
2. In Supabase:
   - Open **SQL Editor**.
   - Paste the contents of `supabase/migrations/001_init.sql`.
   - Run the script to create tables, enums, extensions, and seed data.
3. In **Project Settings → Database**, copy the **transaction pooler** connection string (preferred).

You can use either:

- A single `DATABASE_URL` connection string (recommended), or
- Separate `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT` values.

This backend is optimized for the pooler:

- Always uses `sslmode=require`.
- Uses emulated prepares (`ATTR_EMULATE_PREPARES = true`) to avoid server-side prepared statement dependencies.

---

### 2. Vercel Setup

1. Install the Vercel CLI and log in:

   ```bash
   npm i -g vercel
   vercel login
   ```

2. From the project root (where `vercel.json` is), run:

   ```bash
   vercel
   ```

   and follow the prompts to link or create a Vercel project.

3. Ensure `vercel.json` exists with:

   ```json
   {
     "functions": {
       "api/**/*.php": {
         "runtime": "vercel-php@0.6.0"
       }
     },
     "routes": [
       { "src": "/api/(.*)", "dest": "/api/$1" }
     ]
   }
   ```

4. In the Vercel dashboard, configure **Environment Variables**:

   Required:

   - `DATABASE_URL` – Supabase Postgres connection string (or use `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT` instead).
   - `JWT_SECRET` – a strong random secret string used for signing JWT auth cookies.
   - `APP_ENV` – e.g. `production` (controls cookie `secure` flag; use `local` only for HTTP testing).
   - `CLOUDINARY_CLOUD_NAME`
   - `CLOUDINARY_API_KEY`
   - `CLOUDINARY_API_SECRET`

   Optional:

   - `JWT_TTL_SECONDS` – JWT lifetime in seconds (default: 3600).
   - `GALLERY_MAX_BYTES` – max image upload size in bytes (default: 5 MiB).

5. Deploy:

   ```bash
   vercel --prod
   ```

After deployment, your API will be available under:

- `https://your-project.vercel.app/api/...`

---

### 3. API Overview

**Auth**

- `POST /api/auth/login.php` – login with `username`, `password`, optional `remember_me`.
- `POST /api/auth/logout.php` – clear auth and remember-me cookies.
- `GET /api/auth/me.php` – return current authenticated user.

**Public**

- `GET /api/public/site_settings.php`
- `GET /api/public/page.php?slug=home|about|service`
- `GET /api/public/gallery.php`
- `POST /api/public/contact_submit.php`

**Client (requires client role)**

- `POST /api/client/request_create.php`
- `GET /api/client/requests_list.php?status=...&limit=...`

**Admin (requires admin role)**

- `POST /api/admin/site_settings_update.php`
- `POST /api/admin/page_update.php`
- `GET /api/admin/requests_list.php?status=...&limit=...`
- `POST /api/admin/request_update_status.php`
- `POST /api/gallery/upload.php` – upload image to Cloudinary and store metadata.

All endpoints return JSON in the form:

```json
{
  "success": true,
  "data": {},
  "error": null
}
```

---

### 4. Local Development

1. Copy your Supabase connection string to a local `.env` or system environment:

   ```bash
   export DATABASE_URL="postgres://user:pass@host:5432/dbname?sslmode=require"
   export JWT_SECRET="a-very-strong-secret"
   export APP_ENV="local"
   export CLOUDINARY_CLOUD_NAME="your_cloud"
   export CLOUDINARY_API_KEY="..."
   export CLOUDINARY_API_SECRET="..."
   ```

2. Run the PHP built-in server from the project root:

   ```bash
   php -S localhost:8000
   ```

3. Call the API endpoints via:

   - `http://localhost:8000/api/auth/login.php`
   - `http://localhost:8000/api/public/site_settings.php`
   - etc.

Note: for local HTTP (no TLS), set `APP_ENV=local` so cookies are sent without the `secure` flag.

---

### 5. Migration: Existing JSON → Database

For existing installations that already have data in:

- `data/users.json`
- `data/site_content.json`

you can run the migration helper to upsert that data into Supabase.

1. Ensure `DATABASE_URL` (or individual DB_* vars) is set in your shell.
2. From the project root:

   ```bash
   php scripts/migrate_json_to_db.php
   ```

3. The script will:

   - Upsert users from `data/users.json` into the `users` table (matched by `username`).
   - Upsert site settings from `data/site_content.json` into `site_settings`.
   - Upsert page definitions and content for `home`, `about`, and `service`.

It is safe to run this script multiple times; it only updates or inserts rows based on existing usernames and slugs.

