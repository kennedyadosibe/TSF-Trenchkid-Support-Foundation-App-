# Database Hosting Sync

## Summary

Updated the TSF database import and hosted-site migration path so fresh GitHub/database deployments match the current dashboard and shared-hosting limits.

## Files

- `database/tsf.sql`
- `database/hosting_content_sync_migration.sql`
- `database/cms_gallery_password_migration.sql`
- `admin/dashboard.php`
- `BACKEND/donation_summary.php`
- `BACKEND/Fetch_donations.php`

## What Changed

- Removed the required `donation_summary` MySQL view from application queries because InfinityFree does not allow creating views on the shared-hosting account.
- Seeded the full current admin-editable site settings, including Team group photos, page hero copy, gallery copy, contact copy, and Impact page copy.
- Added a migration that syncs existing hosted databases with the newer target-based impact wording and team group photo settings.
- Changed seeded impact items from completed-language labels to target-language labels.
- Removed hard-coded `USE tsf;` statements from migration files so they can run inside the database selected by shared-hosting phpMyAdmin.

## How To Test

- Import `database/tsf.sql` into the selected TSF database in phpMyAdmin.
- For an existing hosted database, run `database/hosting_content_sync_migration.sql`.
- Open the admin dashboard and confirm Overview loads without a `donation_summary` view.
- Open the Impact page and confirm the numbers read as targets/planned goals.
