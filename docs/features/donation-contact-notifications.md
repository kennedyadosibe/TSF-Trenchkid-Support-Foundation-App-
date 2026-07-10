# Donation Contact Notifications

## Summary

The donation flow no longer requires donors to provide an email address or payment details on the TSF site. TSF collects only donor identity and amount, then sends the donor to Paystack Checkout where Paystack presents Mobile Money and card prompts securely.

## What Changed

- `donate.html` now labels email as optional.
- `donate.html` no longer asks for mobile money network, mobile money number, or card choice.
- `js/time.js` validates only donor identity, optional email, gender, and amount.
- `BACKEND/payment_helpers.php` accepts blank donor email values and stores them as `NULL`.
- `BACKEND/initialize_payment.php` sends Paystack a generated TSF checkout email when the donor leaves email blank.
- `BACKEND/initialize_payment.php` initializes Paystack with both `card` and `mobile_money` checkout channels.
- `BACKEND/payment_helpers.php` records the final payment method from Paystack's verified transaction channel where available.
- Thank-you email is only attempted when a real donor email was provided.
- A phone appreciation message is created only when a verified transaction includes a phone number.

## SMS Setup

Phone appreciation messages are stored in `sms_notifications`.

If `SMS_API_URL` and `SMS_API_KEY` are filled in `BACKEND/config.php`, the backend will attempt to send the message through that provider. If they are empty, the message remains queued in the database for follow-up.

## Database

Run `database/donation_contact_sms_migration.sql` on existing databases. It makes `donors.email` optional and creates `sms_notifications`.

Fresh installs get the same schema from `database/tsf.sql`.

## Testing

- Submit a donation with blank email and no phone number on the TSF form.
- Paystack should initialize successfully and present its own checkout prompts.
- After payment verification, `donors.email` should be `NULL` when no email was provided.
- `sms_notifications` should contain a phone appreciation message only if Paystack provides a phone number for the verified transaction.
