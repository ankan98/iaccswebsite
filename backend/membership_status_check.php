<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include_once "conn.php";

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

$reference = clean($_GET['ref'] ?? '');
$membership_id = clean($_GET['membership_id'] ?? '');
$email = clean($_GET['email'] ?? '');
$mobile = clean($_GET['mobile'] ?? '');

if ($reference === '' && $membership_id === '' && $email === '' && $mobile === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide Reference Number, Membership ID, Email, or Mobile.',
    ]);
    exit;
}

$conditions = [];
$params = [];
$types = '';

if ($reference !== '') {
    $conditions[] = 'reference_number = ?';
    $params[] = $reference;
    $types .= 's';
}
if ($membership_id !== '') {
    $conditions[] = 'membership_id = ?';
    $params[] = $membership_id;
    $types .= 's';
}
if ($email !== '') {
    $conditions[] = 'email = ?';
    $params[] = $email;
    $types .= 's';
}
if ($mobile !== '') {
    $conditions[] = 'mobile = ?';
    $params[] = $mobile;
    $types .= 's';
}

$where = implode(' OR ', $conditions);
$stmt = $conn->prepare("SELECT id, name, reference_number, membership_id, status, payment_status, email, mobile, created_at, updated_at FROM membership_requests WHERE $where ORDER BY id DESC LIMIT 1");
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.',
    ]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode([
        'success' => false,
        'message' => 'No membership record found with the given details.',
    ]);
    exit;
}

$base_url = (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1'))
    ? 'http://localhost/iaccs/'
    : 'https://iaccs.org.in/';

$download_url = '';
$normalized_status = strtolower(trim($row['status'] ?? ''));
$normalized_payment_status = strtolower(trim($row['payment_status'] ?? ''));
if ($normalized_status === 'approved' && $normalized_payment_status === 'paid') {
    $download_url = $base_url . 'membership-card-download.php?ref=' . urlencode($row['reference_number']);
}

echo json_encode([
    'success' => true,
    'data' => $row,
    'download_url' => $download_url,
]);
