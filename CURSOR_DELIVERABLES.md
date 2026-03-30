# Cursor Deliverables: Supabase (Postgres) + PHP Backend (Vercel) + Frontend Integration

## Goal
Upgrade this PHP app from JSON-file storage to a proper Supabase PostgreSQL database, deploy the PHP backend to Vercel (serverless PHP runtime), and connect all frontend pages to the backend so the app works fully after Git push → Vercel deploy.

This repo currently stores data in JSON files:
- data/users.json
- data/site_content.json
- data/remember_tokens.json
Uploads: uploads/gallery/* (filesystem)

We are migrating to:
- Supabase PostgreSQL for all data
- Cloudinary for image storage (recommended for Vercel/serverless)
- PHP API endpoints for frontend (AJAX/fetch or form POST)
- Vercel deployment using vercel-community/php runtime

## Deliverables (Must Complete)
1. ✅ Supabase DB schema (SQL) + migrations file(s)
2. ✅ PHP backend refactor to use PostgreSQL via PDO (no JSON persistence)
3. ✅ Authentication:
   - login/logout
   - password hashing (bcrypt)
   - role-based access: admin / client
   - remember-me token storage in DB
4. ✅ CMS:
   - site settings CRUD
   - pages: home/about/service CRUD (admin)
   - public pages read from DB
5. ✅ Client Portal:
   - create service request
   - list my requests
   - request status tracking
6. ✅ Admin Portal:
   - view & update all service requests
   - view contact form submissions
   - manage gallery images (metadata)
7. ✅ Gallery:
   - Upload image to Cloudinary
   - Store metadata (public_id, secure_url, title, caption, sort_order) in DB
   - Public gallery shows DB-driven images
8. ✅ Frontend integration:
   - Replace all JSON reads with API calls
   - Ensure pages render from DB content
9. ✅ Deployment docs:
   - Supabase connection strings & pooler usage
   - Vercel env vars
   - vercel.json configuration
   - local dev instructions

---

## Architecture (Target)

### Hosting
- DB: Supabase Postgres
- Backend: PHP on Vercel using `vercel-community/php` runtime (serverless functions)
- Images: Cloudinary (permanent, CDN)

Notes:
- Supabase provides pooler connection strings; pooling commonly uses transaction mode. Use the “Connect to Postgres” strings from dashboard. Avoid prepared statements if using transaction pooler. (Use simple PDO queries.) 
- Vercel serverless filesystem is not reliable for permanent uploads → Cloudinary required for gallery.

References:
- Supabase connection guide & pooler terminology: https://supabase.com/docs/guides/database/connecting-to-postgres
- Supavisor/pooler transaction mode example: https://supabase.com/docs/guides/troubleshooting/supavisor-and-connection-terminology-explained-9pr_ZO
- Vercel community PHP runtime: https://github.com/vercel-community/php and https://php.vercel.app/

---

## Database Schema (Supabase SQL)
Create a single SQL migration file: `supabase/migrations/001_init.sql`

### Extensions
- Enable `pgcrypto` and `citext`

### Tables
#### 1) Users
- id (uuid, pk)
- username (citext, unique)
- full_name (text)
- email (citext, unique, nullable)
- password_hash (text, bcrypt)
- role (enum: admin/client)
- is_active (bool)
- created_at, updated_at

#### 2) Remember Tokens
- id uuid pk
- user_id fk users
- selector text unique
- token_hash text (store hashed validator)
- expires_at timestamptz
- created_at timestamptz
- revoked_at timestamptz null

#### 3) Site Settings (KV)
- key text pk
- value text
- updated_at
- updated_by fk users null

#### 4) Pages + Page Content
pages:
- id uuid pk
- slug unique (home/about/service)
- title
- is_published
- updated_by, created_at, updated_at

page_content:
- page_id pk fk pages
- heading
- subheading
- content
- highlight
- updated_at, updated_by

#### 5) Contact Messages
- id uuid pk
- name, email, phone, subject, message
- status enum (new/in_progress/closed)
- created_at, handled_by, handled_at

#### 6) Service Requests
- id uuid pk
- client_id fk users
- request_title
- category
- description
- budget_range
- expected_start date
- status enum (submitted/in_review/quoted/approved/in_progress/completed/rejected)
- created_at, updated_at

service_request_updates:
- id uuid pk
- request_id fk
- author_id fk users null
- note
- created_at

#### 7) Gallery Images (Cloudinary-backed)
- id uuid pk
- public_id unique
- secure_url
- format, bytes, width, height
- title, caption
- sort_order int
- is_visible bool
- uploaded_by fk users null
- uploaded_at timestamptz
- deleted_at timestamptz null

### Required Seed Data
- Insert default site settings (site title + primary/secondary colors) based on existing `data/site_content.json`
- Insert pages for: home/about/service with content from existing JSON
- Create an initial admin user from existing `data/users.json` (or create a new one and document credentials)

---

## Backend Refactor Plan (PHP)
Create a clean data access layer and replace JSON reads/writes.

### Folder Layout (Target)
- /api
  - health.php
  - auth/login.php
  - auth/logout.php
  - auth/me.php
  - auth/remember_rotate.php (optional)
  - public/site_settings.php (GET)
  - public/page.php?slug=home (GET)
  - public/gallery.php (GET)
  - public/contact_submit.php (POST)
  - admin/site_settings_update.php (POST)
  - admin/page_update.php (POST)
  - admin/requests_list.php (GET)
  - admin/request_update_status.php (POST)
  - client/request_create.php (POST)
  - client/requests_list.php (GET)
  - gallery/upload.php (POST)  <-- uploads to Cloudinary
- /lib
  - db.php (PDO factory)
  - auth.php (session + role guards)
  - csrf.php (optional)
  - validators.php
  - cloudinary.php
- /views (if server-rendered)
- /public (if static assets)

### DB Connection (PDO)
- Use PDO pgsql
- All credentials via ENV:
  - DATABASE_URL (preferred) or DB_HOST, DB_NAME, DB_USER, DB_PASS
  - Ensure sslmode=require for Supabase

Implement in `/lib/db.php`:
- `get_pdo(): PDO`
- robust error handling
- default fetch mode assoc

### Authentication
- Login:
  - lookup user by username
  - verify password using `password_verify`
  - set session (or secure cookie)
- Session handling:
  - serverless functions: rely on cookies; use PHP sessions carefully. If sessions are problematic on Vercel, implement stateless JWT cookie auth.
  - Prefer: signed JWT in HttpOnly cookie (simple for serverless)
- Remember-me:
  - store selector + hashed validator in DB
  - set cookie with `selector:validator`
  - rotate on usage
  - revoke on logout

Cursor: choose ONE auth method and implement fully:
Option A (recommended for Vercel): JWT cookie auth
Option B: PHP session cookie (only if stable)

### Role-based Guards
- `require_login()`
- `require_admin()`
- `require_client()`

---

## Cloudinary Integration (Required)
Implement `/lib/cloudinary.php` using Cloudinary PHP SDK or direct REST API.

### Env vars
- CLOUDINARY_CLOUD_NAME
- CLOUDINARY_API_KEY
- CLOUDINARY_API_SECRET

### Upload endpoint
`POST /api/gallery/upload.php`
- Accept multipart file "image"
- Validate file type/size
- Upload to Cloudinary folder: `ait/gallery`
- Store returned metadata to `gallery_images`
- Return JSON: {id, secure_url, title, caption}

---

## Frontend Integration (Required)
Replace all “read JSON” usage with API fetch.

### Public site pages
- On load:
  - GET /api/public/site_settings.php
  - GET /api/public/page.php?slug=home  (or about/service)
- Render heading/subheading/content/highlight

### Gallery
- GET /api/public/gallery.php
- Render images using secure_url

### Contact form
- POST /api/public/contact_submit.php
- Show success state

### Admin dashboard
- page editor:
  - GET current page content
  - POST updates to /api/admin/page_update.php
- site settings:
  - POST updates /api/admin/site_settings_update.php
- requests:
  - GET /api/admin/requests_list.php
  - POST /api/admin/request_update_status.php

### Client dashboard
- create request:
  - POST /api/client/request_create.php
- list requests:
  - GET /api/client/requests_list.php

---

## Vercel Setup (Must Add)
### 1) `vercel.json`
Configure PHP runtime using vercel-community/php.

Example (adjust to repo structure):
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