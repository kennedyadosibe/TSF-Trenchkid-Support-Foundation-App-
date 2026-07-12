# Admin Content Management

## Summary

The admin dashboard now controls editable public site content through shared site settings. Public pages read settings from `BACKEND/site_settings.php`, while admin users edit those values in `admin/dashboard.php`.

## Files

- `BACKEND/content_definitions.php` defines editable fields, labels, defaults, and field types.
- `BACKEND/email_helpers.php` sends application emails through SMTP when credentials are configured.
- `BACKEND/site_settings.php` returns saved settings merged with defaults.
- `admin/dashboard.php` renders the Site Settings form from the shared definitions.
- Public pages use `data-setting` attributes so page text can be replaced from admin-managed settings.

## Editable Areas

- Global site name, tagline, mission statement, social links.
- Home page hero, intro, pillars, and CTA text.
- About page hero, story, journey, and CTA text.
- Impact page hero, numbers, programs, testimonials, and reach text.
- Team page hero, leadership, advisors, and CTA text.
- News page hero and donation prompt.
- Donate page hero, form copy, security note, and thank-you text.
- Contact page hero, form intro, contact card, contact details, office hours, and volunteer note.
- Gallery page hero and section copy.

## Testing

1. Log in at `/admin/login.php`.
2. On a fresh database import, set a fresh admin password with `tools/reset_admin_password.php`, then log in and confirm the recovery email.
3. Open `Site Settings`.
4. Edit a field and save.
5. Refresh the related public page.
5. Confirm the text updates without editing code.

## Notes

Gallery photos and news articles are managed separately from the page copy. Gallery items can be added with direct image uploads, with URL entry kept only as a fallback.

Password recovery never displays the reset link on the website. When the recovery email matches an admin account, the system creates a one-time token, stores only its hash, emails the reset link to the registered address, and shows the same generic message either way. Reset requests are throttled for five minutes per admin account, except local XAMPP testing can create a fresh fallback link when SMTP is not configured.

On hosting, set the `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, and `FROM_EMAIL` environment variables so recovery links are sent by real email. On local XAMPP without SMTP credentials, failed recovery emails are written to `logs/password-reset-links.log` for testing.

## Gallery Uploads

- Upload folder: `images/gallery/`
- Accepted formats: JPG, PNG, WebP, GIF
- Maximum file size: 4MB
- The admin dashboard stores the uploaded relative path in the existing `gallery.image_url` field.
