<?php
// ============================================
// FETCH DONATIONS — /BACKEND/Fetch_donations.php
// ============================================
define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
requireAdminAuth();

$pdo = getDB();

$method  = sanitize($_GET['method'] ?? '');
$search  = sanitize($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where   = ['1=1'];
$params  = [];

if ($method) {
    $where[] = 'd.payment_method = ?';
    $params[] = $method;
}
if ($search) {
    $where[] = '(d.first_name LIKE ? OR d.last_name LIKE ? OR d.email LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM donors d WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Data
$stmt = $pdo->prepare(
    "SELECT id, first_name, last_name, email, phone, gender, amount, payment_method, mobile_network, payment_verified, created_at
     FROM donors d WHERE $whereSQL
     ORDER BY created_at DESC LIMIT ? OFFSET ?"
);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$donors = $stmt->fetchAll();

// Summary
$summaryStmt = $pdo->query(
    "SELECT COUNT(*) AS total_donors,
            COALESCE(SUM(amount), 0) AS total_raised,
            MAX(created_at) AS last_donation_at
     FROM donors
     WHERE payment_verified = 1"
);
$summary = $summaryStmt->fetch() ?: ['total_donors' => 0, 'total_raised' => 0, 'last_donation_at' => null];

echo json_encode([
    'success'    => true,
    'donors'     => $donors,
    'total'      => $total,
    'page'       => $page,
    'per_page'   => $perPage,
    'summary'    => $summary,
]);
