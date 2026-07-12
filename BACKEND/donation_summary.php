<?php
// ============================================
// PUBLIC DONATION SUMMARY
// /BACKEND/donation_summary.php
// ============================================

define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    $pdo = getDB();
    $summary = $pdo->query(
        'SELECT COUNT(*) AS total_donors,
                COALESCE(SUM(amount), 0) AS total_raised,
                MAX(created_at) AS last_donation_at
         FROM donors
         WHERE payment_verified = 1'
    )->fetch()
        ?: ['total_donors' => 0, 'total_raised' => 0, 'last_donation_at' => null];

    $last = $pdo->query(
        'SELECT first_name, last_name, amount, created_at FROM donors
         WHERE payment_verified = 1
         ORDER BY created_at DESC
         LIMIT 1'
    )->fetch();

    jsonResponse(true, 'Donation summary loaded.', [
        'summary' => [
            'total_donors' => (int)($summary['total_donors'] ?? 0),
            'total_raised' => (float)($summary['total_raised'] ?? 0),
            'last_donation_at' => $summary['last_donation_at'] ?? null,
        ],
        'last_donation' => $last ?: null,
    ]);
} catch (PDOException $e) {
    error_log('Donation summary error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to load donation summary.');
}
