# Progress: Admin Site Control

Date: 2026-06-30

## Completed

- Initialized git and created `main` and `dev` branches.
- Added `AGENTS.md` with project workflow rules.
- Added shared content definitions for admin-editable public page text.
- Updated admin Site Settings to render fields dynamically from the shared definitions.
- Connected major public page text across Home, About, Impact, Team, News, Donate, Contact, and Gallery to backend settings.
- Updated the settings API to return defaults for newly added fields even before they are saved in the database.
- Enhanced the admin dashboard layout with cleaner cards, sidebar navigation, table scroll containers, quick actions, and interactive settings accordions.
- Added direct gallery image uploads into `images/gallery/`, with URL entry retained as a fallback.
- Hardened password recovery so reset links are only sent by email, never displayed on the recovery page, and repeated reset emails are throttled.

## Current Branch

- Active branch: `dev`
- Purpose: testing and review before official approval.

## Next Useful Work

- Add richer managers for team members, testimonials, FAQs, and impact numbers if those should be editable item-by-item.
- Configure SMTP/PHP mail credentials on hosting so password recovery emails are delivered reliably.
