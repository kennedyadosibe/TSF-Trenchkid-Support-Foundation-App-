# Hosting Readiness

## Environment Variables

For live hosting, set these in the hosting control panel or server environment instead of writing secrets into code:

- `SITE_URL`
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `PAYSTACK_SECRET_KEY`
- `PAYSTACK_PUBLIC_KEY`
- `PAYSTACK_CALLBACK_URL`
- `ADMIN_EMAIL`
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`
- `FROM_EMAIL`
- `SMS_API_URL`
- `SMS_API_KEY`
- `SMS_SENDER_ID`

## Notes

- `PAYSTACK_SECRET_KEY` must be the live secret key only on the server.
- Do not put secret keys in `.html`, `.js`, or public GitHub repositories.
- The site can still run locally with default database settings for XAMPP.
- Uploaded files in `images/content/`, `images/gallery/`, `images/news/`, and `images/page/` must be copied to hosting if they should appear live.

## Before Going Live

1. Import `database/tsf.sql` into the hosted MySQL database.
2. Set the environment variables above.
3. Confirm PHP is enabled and PHP files are executed, not downloaded.
4. Enable HTTPS on the domain.
5. Test admin login, password recovery email, donation checkout, and Paystack verification on the live domain.
