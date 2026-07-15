# TSF - Trenchkid Support Foundation App

Website and admin dashboard for Trenchkid Support Foundation. The app includes public pages, donations through Paystack Checkout, article publishing, gallery management, contact messages, and an admin-editable dashboard for site content.

## About Trenchkid Support Foundation

Trenchkid Support Foundation (TSF) is a youth-led foundation from Bolga in Ghana's Upper East Region, created to help underprivileged children access education, digital skills, mentorship, health support, and practical community care. TSF exists to turn compassion into structured action: connecting donors, volunteers, schools, and partners with children who need real opportunities to learn, grow, and build independent futures.

This website is TSF's digital home. It presents the foundation professionally, receives secure donations through Paystack, publishes updates, manages gallery evidence, and gives the TSF team an admin dashboard for keeping the site active without editing code.

## Ownership And Use

This project, brand, content, design, and codebase belong to Trenchkid Support Foundation and its founding team. It is shared on GitHub for TSF development, hosting, backup, and collaboration purposes only.

Do not copy, rebrand, resell, redistribute, or reuse this project as another foundation, charity, business, template, or website without written permission from TSF leadership. The TSF name, story, visual identity, site copy, dashboard flow, and project structure are intended for TSF's own official use.

## Main Features

- Public pages: Home, About, Team, Impact, Gallery, News, Donate, and Contact.
- Admin dashboard for editing page text, contact details, hero images, team content, gallery photos, articles, and site settings.
- Admin MFA login using a password plus a short-lived email/SMS verification code.
- Team group photo section with two editable banner images.
- Paystack Checkout donation flow for card and Ghana mobile money payments.
- Donation verification, donor records, thank-you email support, and SMS notification logging.
- Article publishing with generated public article URLs.
- Gallery image viewer with downloadable uploaded photos.

## Requirements

- PHP 8+
- MySQL or MariaDB
- Apache, such as XAMPP
- PHP extensions: PDO MySQL and cURL

## Local Setup With XAMPP

1. Place the project folder inside your XAMPP `htdocs` directory, or point Apache to this folder.
2. Start Apache and MySQL from XAMPP.
3. Create a MySQL database, for example `tsf`.
4. Import the schema from:

```text
database/tsf.sql
```

5. If needed, apply migration files in `database/` for newer features.
6. Open the site in your browser:

```text
http://localhost/TSF_APP/
```

## Configuration

Main configuration is in:

```text
BACKEND/config.php
```

For local testing or hosting without environment-variable support, copy:

```text
BACKEND/config.local.example.php
```

to:

```text
BACKEND/config.local.php
```

Then put your private database, Paystack, SMTP, and SMS settings there. `BACKEND/config.local.php` is ignored by Git and should not be pushed to GitHub.

For local XAMPP, the default database values are:

```php
'DB_HOST' => 'localhost',
'DB_NAME' => 'tsf',
'DB_USER' => 'root',
'DB_PASS' => '',
```

## Paystack Testing

Paystack is initialized from the backend. Put the Paystack secret key only in backend configuration, `BACKEND/config.local.php`, or server environment variables.

For local testing, use a test secret key that starts with `sk_test_`:

```php
'PAYSTACK_SECRET_KEY' => 'sk_test_your_key_here',
```

Do not commit real secret keys to GitHub.

The current donation page redirects users to Paystack Checkout, so no public key is required in the frontend JavaScript for the current flow.

## Admin Dashboard

Admin login is available at:

```text
admin/login.php
```

After importing `database/tsf.sql`, set your own admin password before using the dashboard. The database seed creates the admin user, but the README does not publish the starting password for security reasons.

Use the command-line reset tool below to generate a fresh password and, if needed, update the recovery email before the first login.

Admin login uses MFA when enabled. After the password is accepted, the dashboard sends a 6-digit verification code to the configured recovery email, and also to the admin phone number when SMS settings are available. The code expires quickly and is stored only as a hash in the database.

### Command-Line Password Reset

If the admin password is lost, reset it safely from the command line. The project never stores plain passwords; this command writes only a hashed password to the `admin.password` column.

Generate a strong temporary password:

```text
php tools/reset_admin_password.php --username=tsf_admin --generate
```

Or read a password from standard input:

```text
echo "NewStrongPassword123!" | php tools/reset_admin_password.php --username=tsf_admin --password-stdin
```

You can also update the recovery email during the reset:

```text
php tools/reset_admin_password.php --username=tsf_admin --email=your@email.com --generate
```

The generated password is shown once in the terminal. Store it safely, sign in, and change it when needed. If MFA contact details are wrong, use `--email=your@email.com` during the reset so the next login code is sent to the correct address.

### MFA Delivery Setup

Email OTP delivery needs working SMTP settings in `BACKEND/config.local.php` or hosting environment variables:

```php
'SMTP_HOST' => 'smtp.gmail.com',
'SMTP_PORT' => 587,
'SMTP_USER' => 'your@email.com',
'SMTP_PASS' => 'your_app_password',
'FROM_EMAIL' => 'your@email.com',
```

SMS OTP delivery is optional and needs an SMS provider endpoint:

```php
'SMS_API_URL' => 'https://your-sms-provider-endpoint',
'SMS_API_KEY' => 'your_sms_api_key',
'SMS_SENDER_ID' => 'TSF',
```

For existing hosted databases, import:

```text
database/admin_mfa_migration.sql
```

On local XAMPP, if SMTP is not configured, the MFA code is written to:

```text
logs/admin-mfa-codes.log
```

After login, the dashboard lets the admin edit each page from the blue sidebar. The Team page editor includes:

- Large Team Group Photo 1
- Large Team Group Photo 2
- Group Photo Title
- Group Photo Brief
- Team page headings and CTA text

Repeatable content such as team members, advisors, programs, impact stats, testimonials, regions, and FAQs is managed from the Page Items Manager.

## Upload Folders

Uploaded files are stored under:

```text
images/content/
images/gallery/
images/news/
images/page/
```

The repository includes `.gitkeep` files so those folders exist after cloning.

## Branch Workflow

- `dev` is for active development and testing.
- `main` is for official approved code.
- Complete, testable changes should be committed to `dev`.
- Merge `dev` into `main` only when the work is approved for release.

## Security Notes

- Never commit Paystack live keys, SMTP passwords, database passwords, or SMS API keys.
- Use environment variables on hosting where possible.
- Keep `DEBUG_MODE` disabled in production.
- Keep admin MFA enabled on hosted sites and make sure the recovery email can receive OTP messages.

## Project Documentation

Feature notes live in:

```text
docs/features/
```

Progress notes live in:

```text
progress/
```
