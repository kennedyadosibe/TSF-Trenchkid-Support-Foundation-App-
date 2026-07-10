# Progress: Donation Contact Notifications

Date: 2026-06-30

## Completed

- Made donor email optional in frontend and backend donation validation.
- Added generated Paystack checkout email fallback for donors who leave email blank.
- Changed thank-you email sending so it only runs when a donor provides an email.
- Added SMS appreciation logging for verified donations with a phone number.
- Added `sms_notifications` database table and made `donors.email` nullable.
- Applied `database/donation_contact_sms_migration.sql` to the local XAMPP MySQL database.
- Updated donation thank-you copy to mention phone appreciation and optional email.
- Changed the donation page so Paystack Checkout collects card/mobile-money details instead of the TSF form.
- Initialized Paystack with both card and mobile money channels and record the verified Paystack channel where available.

## Verification

- Paystack initialization succeeds with blank donor email and no phone number on the TSF form.
- A backend verified-donation test queued an SMS appreciation message and then cleaned up its test rows.
- PHP and JavaScript syntax checks passed for the changed files.
