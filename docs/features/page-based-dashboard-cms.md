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

These records are stored in `content_items` and loaded on public pages through `BACKEND/content_items.php`.

## Uploads

- Content item images upload into `images/content/`.
- Gallery images upload into `images/gallery/`.
- Accepted image types: JPG, PNG, WebP, GIF.
- Maximum upload size: 4MB.

## Public Rendering

The script `js/page-content.js` replaces static fallback content on Team, Impact, and Contact pages when database content is available.
