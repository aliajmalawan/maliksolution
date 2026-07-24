# EduTop — Dynamic CMS & Admin Dashboard

Custom plain-PHP MVC (no framework, no Composer), MySQL via PDO, Bootstrap 5 UI. All four planned phases are complete: Foundation & Security, Dynamic Page/Section CMS + Media Manager, Blog CMS + Leads, and Dashboard Analytics/Full Settings/Permission Matrix/Audit Viewers/File Manager/Backup & Restore. See `../.claude/plans` conversation history for the full design rationale.

## Requirements

- XAMPP (Apache + MySQL) with PHP 8.1+
- `mod_rewrite` and `mod_headers` enabled in Apache (both are enabled by default in XAMPP)
- PHP `gd` extension for image compression on upload (optional — uploads still work without it, just uncompressed) and `zip` extension for Website Files backups (optional — the Backups page will say so if it's missing)

## Setup

1. **Create the database.** In phpMyAdmin or the MySQL CLI:
   ```sql
   CREATE DATABASE edutop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Configure environment.** A `.env` file already exists with local defaults matching XAMPP's default MySQL account (`root` / no password). Review and edit it — at minimum, set real SMTP credentials so 2FA login emails can send, and change `SUPER_ADMIN_PASSWORD` before first login. Never commit `.env`. (SMTP, and everything else in `.env`, can also be overridden later from the admin **Settings** tabs without touching this file.)

3. **Run migrations** (creates all tables):
   ```
   C:\xampp\php\php.exe database\migrate.php
   ```

4. **Seed the database** — run in this order:
   ```
   C:\xampp\php\php.exe database\seeds\seed.php
   C:\xampp\php\php.exe database\seeds\seed_pages.php
   C:\xampp\php\php.exe database\seeds\seed_blog.php
   ```
   - `seed.php` creates the 7 roles, the full permission set, default role→permission grants, and one Super Admin user from the `SUPER_ADMIN_*` values in `.env`. Safe to re-run any time (e.g. after a permission is added to `config/permissions.php`) — it upserts rather than duplicating.
   - `seed_pages.php` creates all 16 pages from the spec (Home fully populated with a real section set, the rest with starter content), the primary/footer menus, and baseline settings. Safe to re-run — never overwrites content you've already edited.
   - `seed_blog.php` creates a couple of demo categories/tags and a few sample blog posts. Safe to re-run.

5. **Browse the site**:
   - Public homepage: `http://localhost/EduTop/`
   - Blog: `http://localhost/EduTop/blog`
   - Admin login: `http://localhost/EduTop/admin/login`

   Login requires email OTP: after a correct password, a 6-digit code is emailed via the configured SMTP account and must be entered on the next screen. If SMTP isn't configured yet, check `storage/logs/error.log` for the send failure, or temporarily read the code directly from the `user_otps` table while developing locally.

## Everything is editable from the Admin Dashboard

No PHP file needs editing after deployment. Pages/sections/SEO/media live under **Pages** and **Media Library**; navigation under **Menus**; blog content under **Blog Posts/Categories/Tags/Comments**; contact & demo-request leads under **Leads**; roles/permissions under **Roles & Permissions** (including a read-only **Permission Matrix** view for auditing); site identity, SMTP, analytics/reCAPTCHA, theme colors, and maintenance mode under **Settings**; general document storage under **File Manager**; database/file backups (and restore) under **Backups**; and full audit/login history under **Activity Log**/**Login History**.

## Scheduled backups (no cron on Windows/XAMPP)

`database/backup-cli.php` triggers a database backup from the command line — wire it to Windows Task Scheduler for unattended, scheduled backups:

```
schtasks /create /tn "EduTop DB Backup" /sc daily /st 02:00 ^
  /tr "C:\xampp\php\php.exe C:\xampp\htdocs\EduTop\database\backup-cli.php"
```

Backups are written to `storage/backups/` (already outside the webroot). The **Backups** admin page also supports one-click on-demand database and website-files backups, download, delete, and restore — restore is intentionally limited to the Super Admin role regardless of who else holds the `backups.manage` permission, since it overwrites live data.

## Security notes for production

- Point the Apache vhost `DocumentRoot` directly at `public/` instead of relying on the root `.htaccess` rewrite — this keeps `app/`, `config/`, `database/`, and `storage/` entirely outside the webroot rather than just access-denied within it.
- Set `APP_ENV=production`, `APP_DEBUG=0`, and `APP_FORCE_HTTPS=1` in `.env` once you have a valid TLS certificate.
- Rotate `APP_KEY` and all credentials before going live; the checked-in `.env` is for local development only.
- The File Manager (`storage/files/`) and Backups (`storage/backups/`) are both outside the webroot and never directly URL-accessible — downloads stream through authenticated, permission-gated admin routes.

## Project structure

See the architecture section of the implementation plan for the full directory layout.
