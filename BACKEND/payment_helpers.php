<?php
// ============================================
// TSF PAYMENT HELPERS
// /BACKEND/payment_helpers.php
// ============================================

if (!defined('TSF_LOADED')) {
    http_response_code(403);
    die('Direct access not permitted.');
}

function normalizePhone(string $phone): string {
    return preg_replace('/\s+/', '', trim($phone));
}

function validateDonationInput(array $input, bool $requirePhone = false): array {
    $data = [
        'first_name' => sanitize($input['first_name'] ?? ''),
        'last_name' => sanitize($input['last_name'] ?? ''),
        'email' => filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL),
        'phone' => normalizePhone($input['phone'] ?? ''),
        'gender' => sanitize($input['gender'] ?? ''),
        'amount' => filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT),
        'payment_method' => sanitize($input['payment_method'] ?? ''),
        'mobile_network' => sanitize($input['mobile_network'] ?? ''),
    ];

    $errors = [];
    if ($data['first_name'] === '') $errors[] = 'First name is required.';
    if ($data['last_name'] === '') $errors[] = 'Last name is required.';
    if (!$data['email']) $errors[] = 'A valid email address is required.';
    if ($data['amount'] === false || $data['amount'] <= 0) $errors[] = 'A valid donation amount is required.';
    if (!in_array($data['payment_method'], ['mobile_money', 'card'], true)) $errors[] = 'Invalid payment method.';
    if (!in_array($data['gender'], ['Male', 'Female', 'Prefer not to say', ''], true)) $errors[] = 'Invalid gender value.';

    if ($data['payment_method'] === 'mobile_money' && !in_array($data['mobile_network'], ['mtn', 'telecel', 'airteltigo'], true)) {
        $errors[] = 'Please select MTN, Telecel, or AirtelTigo.';
    }
    if ($data['payment_method'] === 'card') {
        $data['mobile_network'] = '';
    }

    if ($requirePhone && $data['payment_method'] === 'mobile_money' && $data['phone'] === '') {
        $errors[] = 'Mobile money number is required for this payment method.';
    }
    if ($data['phone'] !== '' && !preg_match('/^(0[0-9]{9}|\+233[0-9]{9})$/', $data['phone'])) {
        $errors[] = 'Enter a valid Ghana phone number (e.g. 0244000000 or +233244000000).';
    }

    return [$data, $errors];
}

function paystackRequest(string $method, string $path, array $payload = []): array {
    if (PAYSTACK_SECRET_KEY === '' || strpos(PAYSTACK_SECRET_KEY, 'sk_test_xxxxx') === 0) {
        return ['ok' => false, 'message' => 'Paystack secret key is not configured.'];
    }

    $ch = curl_init('https://api.paystack.co' . $path);
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($method !== 'GET') {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'message' => 'Payment gateway request failed: ' . $error];
    }

    $body = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300 || !is_array($body) || empty($body['status'])) {
        return [
            'ok' => false,
            'message' => $body['message'] ?? 'Payment gateway returned an error.',
            'body' => $body,
        ];
    }

    return ['ok' => true, 'body' => $body];
}

function getDonationCallbackUrl(): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '') {
        $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443'))
            ? 'https'
            : 'http';
        return $scheme . '://' . $host . '/donate.html';
    }
    return PAYSTACK_CALLBACK_URL;
}

function donationFromPaystackMetadata(array $tx): array {
    $metadata = is_array($tx['metadata'] ?? null) ? $tx['metadata'] : [];
    $amount = ((float)($tx['amount'] ?? 0)) / 100;
    return [
        'first_name' => $metadata['first_name'] ?? '',
        'last_name' => $metadata['last_name'] ?? '',
        'email' => $tx['customer']['email'] ?? ($tx['email'] ?? ''),
        'phone' => $metadata['phone'] ?? '',
        'gender' => $metadata['gender'] ?? '',
        'amount' => $amount,
        'payment_method' => $metadata['payment_method'] ?? 'card',
        'mobile_network' => $metadata['mobile_network'] ?? '',
    ];
}

function recordVerifiedDonation(array $donation, string $reference): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, first_name, amount FROM donors WHERE transaction_ref = ?');
    $stmt->execute([$reference]);
    $existing = $stmt->fetch();
    if ($existing) {
        return [
            'donor_id' => (int)$existing['id'],
            'first_name' => $existing['first_name'],
            'amount' => number_format((float)$existing['amount'], 2),
            'already_recorded' => true,
        ];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO donors (first_name, last_name, email, phone, gender, amount, payment_method, mobile_network, transaction_ref, payment_verified)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([
        $donation['first_name'],
        $donation['last_name'],
        $donation['email'],
        $donation['phone'] ?: null,
        $donation['gender'] ?: null,
        $donation['amount'],
        $donation['payment_method'],
        $donation['mobile_network'] ?: null,
        $reference,
    ]);

    $donorId = (int)$pdo->lastInsertId();
    sendThankYouEmail($donation['email'], $donation['first_name'], (float)$donation['amount'], $donation['payment_method']);

    return [
        'donor_id' => $donorId,
        'first_name' => $donation['first_name'],
        'amount' => number_format((float)$donation['amount'], 2),
        'already_recorded' => false,
    ];
}

function methodLabel(string $method): string {
    return [
        'mobile_money' => 'Mobile Money',
        'card' => 'Visa/Mastercard',
    ][$method] ?? $method;
}

function networkLabel(?string $network): string {
    return [
        'mtn' => 'MTN',
        'telecel' => 'Telecel',
        'airteltigo' => 'AirtelTigo',
    ][$network ?? ''] ?? '';
}

function sendThankYouEmail(string $email, string $name, float $amount, string $method): void {
    $subject = 'Thank You for Your Generous Support - TSF';
    $date = date('d M Y, h:i A');
    $body = "Dear $name,\n\n"
          . "THANK YOU FOR YOUR GENEROUS SUPPORT. YOUR DONATION IS MAKING A REAL DIFFERENCE IN A CHILD'S LIFE.\n\n"
          . "Donation Details:\n"
          . "  Amount: GHS " . number_format($amount, 2) . "\n"
          . "  Method: " . methodLabel($method) . "\n"
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
