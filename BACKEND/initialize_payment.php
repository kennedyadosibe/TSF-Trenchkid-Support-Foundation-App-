<?php
// ============================================
// INITIALIZE PAYSTACK DONATION
// /BACKEND/initialize_payment.php
// ============================================

define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/payment_helpers.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (!validateCsrfToken($input['csrf_token'] ?? '', 'donate')) {
    jsonResponse(false, 'Security validation failed. Please refresh the page and try again.');
}

[$donation, $errors] = validateDonationInput($input, true);
if ($errors) {
    jsonResponse(false, implode(' ', $errors), ['errors' => $errors]);
}

$reference = 'TSF_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
$channels = $donation['payment_method'] === 'mobile_money'
    ? ['mobile_money']
    : ['card'];
$checkoutEmail = $donation['email'] ?: 'donor+' . strtolower($reference) . '@tsfghana.org';

$payload = [
    'email' => $checkoutEmail,
    'amount' => (int) round($donation['amount'] * 100),
    'currency' => 'GHS',
    'reference' => $reference,
    'callback_url' => getDonationCallbackUrl(),
    'channels' => $channels,
    'metadata' => [
        'first_name' => $donation['first_name'],
        'last_name' => $donation['last_name'],
        'email' => $donation['email'],
        'phone' => $donation['phone'],
        'gender' => $donation['gender'],
        'payment_method' => $donation['payment_method'],
        'mobile_network' => $donation['mobile_network'],
        'source' => 'tsf_website',
        'custom_fields' => [
            [
                'display_name' => 'Donor Name',
                'variable_name' => 'donor_name',
                'value' => $donation['first_name'] . ' ' . $donation['last_name'],
            ],
            [
                'display_name' => 'Payment Method',
                'variable_name' => 'payment_method',
                'value' => trim(methodLabel($donation['payment_method']) . ' ' . networkLabel($donation['mobile_network'])),
            ],
        ],
    ],
];

$result = paystackRequest('POST', '/transaction/initialize', $payload);
if (!$result['ok']) {
    jsonResponse(false, $result['message']);
}

$data = $result['body']['data'] ?? [];
jsonResponse(true, 'Payment initialized.', [
    'authorization_url' => $data['authorization_url'] ?? '',
    'access_code' => $data['access_code'] ?? '',
    'reference' => $data['reference'] ?? $reference,
]);
