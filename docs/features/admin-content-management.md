# Admin Content Management

## Summary

The admin dashboard now controls editable public site content through shared site settings. Public pages read settings from `BACKEND/site_settings.php`, while admin users edit those values in `admin/dashboard.php`.

## Files

- `BACKEND/content_definitions.php` defines editable fields, labels, defaults, and field types.
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
2. Open `Site Settings`.
3. Edit a field and save.
4. Refresh the related public page.
5. Confirm the text updates without editing code.

## Notes

Gallery photos and news articles are managed separately from the page copy. Password recovery email delivery still depends on XAMPP/PHP mail or SMTP configuration.

