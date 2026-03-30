# PHP Role-Based Company Website

This project is a plain PHP website ready for Linux hosting with:
- Public pages: Home, About, Service, Gallery, Contact Us
- Role-based login: Admin and Client
- Session login + optional cookie-based remember login
- Admin dashboard to edit website content and gallery
- Client dashboard with placeholder service request form

## Default Login
- Admin: `admin` / `admin123`
- Client: `client` / `client123`

## Project Structure
- `index.php`, `about.php`, `service.php`, `gallery.php`, `contact.php`, `login.php`
- `admin/` for admin dashboard and edit pages
- `client/` for client dashboard and service request form
- `includes/` shared config/helpers/layout files
- `data/` JSON files for users/content/tokens
- `uploads/gallery/` image uploads

## Linux Hosting Setup
1. Upload all files to your hosting folder (for example `public_html/company-site`).
2. Ensure PHP 8.0+ is enabled.
3. Make writable permissions for runtime folders:
   - `data/`
   - `uploads/gallery/`

Example permissions command:
```bash
chmod -R 775 data uploads/gallery
```

4. If the website is hosted in a subfolder, open `includes/config.php` and set:
```php
'base_path' => '/your-folder-name'
```

## Title and Color Theme
- Admin can update title and colors from:
  - `Admin Dashboard -> Site Title & Colors`
- Editable values:
  - site title
  - primary color (`#RRGGBB`)
  - secondary color (`#RRGGBB`)
  - accent color (`#RRGGBB`)

## Content Editing
- Admin can edit these pages:
  - Home
  - About
  - Service
  - Gallery (add/remove photos)
- Contact Us page is intentionally not included in admin content editing.

## Notes
- Data is currently JSON/file based (no database required).
- Replace placeholder form fields in `client/request_service.php` when final details are available.
"# hydera" 
