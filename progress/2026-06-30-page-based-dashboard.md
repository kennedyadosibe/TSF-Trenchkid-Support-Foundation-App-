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

## Next Useful Work

- Add edit-in-place modals for existing page items instead of remove-and-readd.
- Add drag-and-drop ordering for page items.
- Add image previews before upload.
