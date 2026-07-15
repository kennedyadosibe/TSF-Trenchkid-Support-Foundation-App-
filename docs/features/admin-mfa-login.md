# Admin MFA Login

## What Changed

The admin login now supports multi-factor authentication. A correct username and password no longer opens the dashboard immediately when MFA is enabled. Instead, the system creates a pending admin session and sends a 6-digit OTP to the admin recovery email. If an admin phone number and SMS provider are configured, the same OTP is also sent by SMS.

## Files

- `admin/login.php` starts the pending MFA login after password verification.
- `admin/verify_mfa.php` verifies or resends the OTP before creating the real admin session.
- `BACKEND/mfa_helpers.php` generates, hashes, sends, and verifies MFA codes.
- `BACKEND/email_helpers.php` now includes a reusable SMS request helper.
- `admin/dashboard.php` lets the admin update recovery email, MFA phone number, and MFA status.
- `database/tsf.sql` includes MFA fields for fresh installs.
- `database/admin_mfa_migration.sql` upgrades existing hosted databases.

## Security Behavior

- OTPs are never stored in plain text.
- The database stores a SHA-256 hash of the OTP.
- Codes expire after 10 minutes.
- Failed verification attempts are limited.
- A password reset clears stale MFA tokens.
- Full `admin_logged_in` session access is only granted after OTP verification.

## Setup

For existing databases, import:

```text
database/admin_mfa_migration.sql
```

For email OTP delivery, configure SMTP in `BACKEND/config.local.php` or server environment variables. For SMS OTP delivery, configure `SMS_API_URL`, `SMS_API_KEY`, and `SMS_SENDER_ID`.

On local XAMPP, if SMTP is not configured, the OTP is written to `logs/admin-mfa-codes.log` so development login can still be tested. Production hosting should use real SMTP and/or SMS delivery.

## Testing

1. Import the MFA migration on an existing database.
2. Configure SMTP so the recovery email can receive messages.
3. Log in at `admin/login.php`.
4. Confirm the password step redirects to `admin/verify_mfa.php`.
5. Enter the OTP from the admin email.
6. Confirm the dashboard opens only after the OTP is accepted.
