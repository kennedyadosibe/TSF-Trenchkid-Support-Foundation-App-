# Page-Based Dashboard CMS

## Summary

The admin dashboard now presents public pages directly in the sidebar so admins can edit content by page instead of searching through one large Site Settings area.

## Dashboard Pages

- Global
- Home
- About
- Team
- Impact
- Gallery
- News
- Donate
- Contact
- Social Links

The dashboard behaves like a single-page admin app: the blue sidebar controls which editor is visible, and only one page or manager panel is shown at a time.

## Repeatable Content

The Page Items Manager controls repeatable sections:

- Team members
- Advisors
- Impact stats
- Programs
- Testimonials
- Regions
- FAQs

The manager includes filters so admins can view one content type at a time.

Existing page items can be edited directly from dedicated editor cards. Admins can change names, roles, descriptions, ordering, initials/stat values, URLs, and upload a replacement image without deleting the item.

Each editor card uses a normal self-contained form, so Team, Advisor, and other page-item changes submit reliably with their matching Save Changes button.

These records are stored in `content_items` and loaded on public pages through `BACKEND/content_items.php`.

## Uploads

- Content item images upload into `images/content/`.
- Gallery images upload into `images/gallery/`.
- Article cover images upload into `images/news/`.
- Accepted image types: JPG, PNG, WebP, GIF.
- Maximum upload size: 4MB.

Gallery rows are editable too, including title, category, caption, order, URL fallback, and replacement image upload.

## Page Hero Images

Each public page editor includes a hero background image field. Admins can upload or replace the main page header image for:

- Home
- About
- Team
- Impact
- Gallery
- News
- Donate
- Contact

Uploaded page header images are stored in `images/page/` and applied on the public pages through `data-setting-bg`.

## Public Rendering

The script `js/page-content.js` replaces static fallback content on Team, Impact, and Contact pages when database content is available.

## Messages

The Messages panel displays each enquiry as a full readable card with sender details, subject/type, date, and the complete message body.

## Article Publishing

The Publish Article panel supports optional cover image upload. Uploaded covers are saved to `images/news/`, stored in the `news.cover_image` field, shown in the Recent Articles admin table, and rendered on the public News page through `BACKEND/fetch_news.php`.
