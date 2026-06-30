# Donation Contact Notifications

## Summary

The donation flow no longer requires donors to provide an email address. This keeps the Paystack checkout realistic while reducing friction for donors who only want to use mobile money.

## What Changed

- `donate.html` now labels email as optional.
- `js/time.js` validates email only when a donor enters one.
- `BACKEND/payment_helpers.php` accepts blank donor email values and stores them as `NULL`.
- `BACKEND/initialize_payment.php` sends Paystack a generated TSF checkout email when the donor leaves email blank.
- Thank-you email is only attempted when a real donor email was provided.
- A phone appreciation message is created for verified donations with a phone number.

## SMS Setup

Phone appreciation messages are stored in `sms_notifications`.

If `SMS_API_URL` and `SMS_API_KEY` are filled in `BACKEND/config.php`, the backend will attempt to send the message through that provider. If they are empty, the message remains queued in the database for follow-up.

## Database

Run `database/donation_contact_sms_migration.sql` on existing databases. It makes `donors.email` optional and creates `sms_notifications`.

Fresh installs get the same schema from `database/tsf.sql`.

## Testing

- Submit a donation with blank email and a valid Ghana mobile money number.
- Paystack should initialize successfully.
- After payment verification, `donors.email` should be `NULL` when no email was provided.
- `sms_notifications` should contain the phone appreciation message.
