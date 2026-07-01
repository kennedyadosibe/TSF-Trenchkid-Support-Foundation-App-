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
- `SMTP_*` values must be configured for admin password recovery emails to leave the server.
- Do not put secret keys in `.html`, `.js`, or public GitHub repositories.
- The site can still run locally with default database settings for XAMPP.
- If hosting does not support environment variables, copy `BACKEND/config.local.example.php` to `BACKEND/config.local.php` on the server and put the hosting credentials there. `config.local.php` is ignored by git.
- On local XAMPP without SMTP credentials, password reset links are saved in `logs/password-reset-links.log` for testing.
- Uploaded files in `images/content/`, `images/gallery/`, `images/news/`, and `images/page/` must be copied to hosting if they should appear live.
- InfinityFree does not allow creating arbitrary databases or views on free MySQL accounts, so donation totals are calculated directly from `donors` instead of relying on a database view.

## InfinityFree Test Hosting

1. Create a free hosting account/domain in InfinityFree.
2. Create a MySQL database from the InfinityFree control panel and note the host, database name, username, and password.
3. Open phpMyAdmin for that database and import `database/tsf.sql`.
4. Upload the project files into the domain `htdocs` folder.
5. On the server, copy `BACKEND/config.local.example.php` to `BACKEND/config.local.php`.
6. Edit `BACKEND/config.local.php` with the InfinityFree domain URL and MySQL credentials.
7. Visit the domain, then test `/admin/login.php`, public pages, gallery, article publishing, and password recovery.

## Before Going Live

1. Import `database/tsf.sql` into the hosted MySQL database.
2. Set the environment variables above.
3. Confirm PHP is enabled and PHP files are executed, not downloaded.
4. Enable HTTPS on the domain.
5. Test admin login, password recovery email, donation checkout, and Paystack verification on the live domain.
