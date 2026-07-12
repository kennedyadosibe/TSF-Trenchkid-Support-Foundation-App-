# Progress: Page-Based Dashboard CMS

Date: 2026-06-30

## Completed

- Reworked the dashboard sidebar so public site pages are listed directly.
- Split page copy editing into separate page sections instead of one large Site Settings panel.
- Added partial page saving so each page form only updates its own fields.
- Added a Page Items Manager for Team members, Advisors, Impact stats, Programs, Testimonials, Regions, and FAQs.
- Added public content item endpoint and dynamic rendering script for Team, Impact, and Contact pages.
- Added content image upload storage under `images/content/`.
- Added an admin-editable two-photo Team group section before the public Team leadership briefing, with generated temporary collage fallbacks.
- Fixed admin dashboard image previews so uploaded `images/...` paths resolve correctly from inside the `admin/` folder.
- Created a professional TSF profile PDF for partner and donor submissions.
- Refreshed impact language across the site and PDF so public numbers read as expected targets, not already completed results.
- Synced the database import with current dashboard-editable settings and removed the hosted-blocking `donation_summary` view dependency.

## Next Useful Work

- Add edit-in-place modals for existing page items instead of remove-and-readd.
- Add drag-and-drop ordering for page items.
- Add image previews before upload.
