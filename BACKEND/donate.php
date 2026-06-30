<?php
// ============================================
// TSF DONATION HANDLER
// /BACKEND/donate.php
// ============================================

define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

// ---- CSRF CHECK ----
$csrfToken = $input['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken, 'donate')) {
    jsonResponse(false, 'Security validation failed. Please refresh the page and try again.');
}

// ---- SANITIZE & VALIDATE ----
$firstName = trim(strip_tags((string)($input['first_name'] ?? '')));
$lastName  = trim(strip_tags((string)($input['last_name']  ?? '')));
$rawEmail  = trim($input['email'] ?? '');
$email     = $rawEmail === '' ? '' : filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
$phone     = trim(strip_tags((string)($input['phone'] ?? '')));
$gender    = trim(strip_tags((string)($input['gender'] ?? '')));
$amount    = filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$method    = trim(strip_tags((string)($input['payment_method'] ?? '')));
$network   = trim(strip_tags((string)($input['mobile_network'] ?? '')));
$txRef     = trim(strip_tags((string)($input['transaction_ref'] ?? ''))); // From client-side payment SDK

$errors = [];

if (empty($firstName)) $errors[] = 'First name is required.';
if (empty($lastName))  $errors[] = 'Last name is required.';
if ($rawEmail !== '' && !$email) $errors[] = 'Enter a valid email address or leave it blank.';
if ($amount === false || $amount <= 0) $errors[] = 'A valid donation amount is required.';
if (!in_array($method, ['mobile_money', 'card'])) $errors[] = 'Invalid payment method.';
if ($method === 'mobile_money' && !in_array($network, ['mtn', 'telecel', 'airteltigo'])) $errors[] = 'Please select MTN, Telecel, or AirtelTigo.';
if (!in_array($gender, ['Male', 'Female', 'Prefer not to say', ''])) $errors[] = 'Invalid gender value.';

// Ghana phone validation
if (!empty($phone)) {
    $phoneClean = preg_replace('/\s+/', '', $phone);
    if (!preg_match('/^(0[0-9]{9}|\+233[0-9]{9})$/', $phoneClean)) {
        $errors[] = 'Enter a valid Ghana phone number (e.g. 0244000000 or +233244000000).';
    }
    $phone = $phoneClean;
}

if ($errors) {
    jsonResponse(false, implode(' ', $errors), ['errors' => $errors]);
}

// ---- PAYMENT VERIFICATION ----
$paymentVerified = false;
$verifiedRef = null;

if (!empty($txRef)) {
    switch ($method) {
        case 'mobile_money':
            $paymentVerified = verifyMobileMoneyPayment($txRef, $amount, $method);
            break;
        case 'card':
            $paymentVerified = verifyPaystackPayment($txRef, $amount);
            break;
    }
    $verifiedRef = $txRef;
} else {
    jsonResponse(false, 'Payment reference is required. Please use the secure checkout flow.');
}

if (!$paymentVerified) {
    jsonResponse(false, 'Payment could not be verified. Donation was not recorded.');
}

// ---- SAVE TO DATABASE ----
try {
    $pdo = getDB();

    // Duplicate transaction reference check
    if (!empty($verifiedRef)) {
        $stmt = $pdo->prepare('SELECT id FROM donors WHERE transaction_ref = ?');
        $stmt->execute([$verifiedRef]);
        if ($stmt->fetch()) {
            jsonResponse(false, 'This transaction has already been recorded. Please contact support if you believe this is an error.');
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO donors (first_name, last_name, email, phone, gender, amount, payment_method, mobile_network, transaction_ref, payment_verified)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $firstName,
        $lastName,
        $email ?: null,
        $phone ?: null,
        $gender ?: null,
        $amount,
        $method,
        $network ?: null,
        $verifiedRef ?: null,
        $paymentVerified ? 1 : 0
    ]);

    $donorId = $pdo->lastInsertId();

    sendThankYouEmail($email, $firstName, $amount, $method);
    queueThankYouSms((int)$donorId, $phone ?: null, $firstName, $amount);

    jsonResponse(true, 'Donation recorded successfully. Thank you for your generous support!', [
        'donor_id'         => (int) $donorId,
        'payment_verified' => $paymentVerified,
        'amount'           => number_format($amount, 2),
        'currency'         => 'GHS',
    ]);

} catch (PDOException $e) {
    error_log('Donation DB error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to process donation at this time. Please try again.');
}


// ---- PAYMENT VERIFICATION FUNCTIONS ----

function verifyMobileMoneyPayment(string $txRef, float $expectedAmount, string $method): bool {
    // In production: call MTN MoMo / Telecel Pesa API to verify transaction
    // Example (Paystack MoMo callback verification):
    $url = 'https://api.paystack.co/transaction/verify/' . urlencode($txRef);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return false;

    $data = json_decode($response, true);
    if (!$data || !$data['status']) return false;

    $tx = $data['data'] ?? [];
    $verified = ($tx['status'] === 'success' &&
                 abs(($tx['amount'] / 100) - $expectedAmount) < 0.01);
    return $verified;
}

function verifyPaystackPayment(string $txRef, float $expectedAmount): bool {
    return verifyMobileMoneyPayment($txRef, $expectedAmount, 'card');
}

function sendThankYouEmail(?string $email, string $name, float $amount, string $method): void {
    if (!$email) {
        return;
    }

    $subject = 'Thank You for Your Generous Support - TSF';
    $methodLabels = [
        'mobile_money' => 'Mobile Money',
        'card' => 'Visa/Mastercard',
    ];
    $methodLabel = $methodLabels[$method] ?? $method;
    $date = date('d M Y, h:i A');

    $body = "Dear $name,\n\n"
          . "THANK YOU FOR YOUR GENEROUS SUPPORT. YOUR DONATION IS MAKING A REAL DIFFERENCE IN A CHILD'S LIFE.\n\n"
          . "Donation Details:\n"
          . "  Amount: GHS " . number_format($amount, 2) . "\n"
          . "  Method: $methodLabel\n"
          . "  Date:   $date\n\n"
          . "Your contribution directly funds education, healthcare, and digital skills training for underprivileged children across Ghana.\n\n"
          . "With gratitude,\n"
          . "The TSF Team\n"
          . "www.tsfghana.org | info@tsfghana.org";

    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n"
             . "Reply-To: " . ADMIN_EMAIL . "\r\n"
             . "X-Mailer: PHP/" . phpversion();

    @mail($email, $subject, $body, $headers);
}

function queueThankYouSms(int $donorId, ?string $phone, string $name, float $amount): void {
    if (!$phone) {
        return;
    }

    $message = "Dear $name, thank you for supporting TSF with GHS " . number_format($amount, 2) . ". Your donation is making a real difference in a child's life.";
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO sms_notifications (donor_id, phone, message, status, provider_response)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$donorId, $phone, $message, 'queued', 'SMS provider is not configured. Message recorded for follow-up.']);
    } catch (PDOException $e) {
        error_log('SMS notice log error: ' . $e->getMessage());
    }
}
