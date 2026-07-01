# Progress: Editable Images and Page Items

Date: 2026-06-30

## Completed

- Added update actions for existing Page Items.
- Added update actions for existing Gallery photos.
- Made Page Items rows editable for names, roles/subtitles, descriptions, meta values, image URLs, upload replacement images, and display order.
- Made Gallery rows editable for title, category, caption, image URL, replacement image upload, and display order.
- Added editable hero background image fields for Home, About, Team, Impact, Gallery, News, Donate, and Contact pages.
- Connected public page hero sections to admin-managed image settings.
- Kept remove actions available beside save actions.
- Verified dashboard renders the editable rows and upload fields after login.
- Replaced the Page Items table editor with self-contained editor cards so Team, Advisor, and other item edits save reliably.
- Changed Messages from clipped table text to full readable message cards.
- Added cover image uploads for article publishing.
- Fixed admin content storage so text is saved cleanly and escaped only during display.

## Notes

- Team members can now have their names, positions, bios, initials, and pictures changed from the admin dashboard.
- Programs/testimonials/FAQs/regions/impact stats can also be changed from Page Items Manager.
- Gallery pictures can be replaced directly from Gallery Photos.
- Page header pictures can be changed directly from each page editor.
- Admins can now read the full body of received messages without truncated text.
- Published news articles can now include uploaded cover images from `images/news/`.
- Public gallery images can now be clicked to view a larger preview and download the image.
- Published articles now generate a public article URL so readers can open a single article directly.
- Dashboard form saves now redirect back to a clean dashboard URL, preventing refresh from re-submitting an old CSRF token.
- Admins can now delete/unpublish published articles from the Recent Articles dashboard panel.
