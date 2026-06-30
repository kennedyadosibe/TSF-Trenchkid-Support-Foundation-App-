<?php
// ============================================
// VERIFY PAYSTACK DONATION
// /BACKEND/verify_payment.php
// ============================================

define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/payment_helpers.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$reference = sanitize($_GET['reference'] ?? $_POST['reference'] ?? '');
if ($reference === '') {
    jsonResponse(false, 'Missing payment reference.');
}

$result = paystackRequest('GET', '/transaction/verify/' . rawurlencode($reference));
if (!$result['ok']) {
    jsonResponse(false, $result['message']);
}

$tx = $result['body']['data'] ?? [];
if (($tx['status'] ?? '') !== 'success') {
    jsonResponse(false, 'Payment was not successful.');
}
if (($tx['currency'] ?? '') !== 'GHS') {
    jsonResponse(false, 'Invalid payment currency.');
}

[$donation, $errors] = validateDonationInput(donationFromPaystackMetadata($tx), false);

if ($errors) {
    jsonResponse(false, 'Verified payment metadata is incomplete. Please contact support.', ['errors' => $errors]);
}

try {
    $record = recordVerifiedDonation($donation, $reference);

    jsonResponse(true, 'Donation verified and recorded. Thank you for your generous support!', [
        'donor_id' => $record['donor_id'],
        'first_name' => $record['first_name'],
        'amount' => $record['amount'],
        'currency' => 'GHS',
        'already_recorded' => $record['already_recorded'],
    ]);
} catch (PDOException $e) {
    error_log('Payment verify DB error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to record verified donation at this time. Please contact support.');
}
