<?php
// backend/membership-card-download.php
ob_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$conn = require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/membership-card.php';

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

$ref_input = clean($_GET['ref'] ?? ($_GET['reference'] ?? ($_GET['membership_id'] ?? ($_GET['id'] ?? ''))));
$type = strtolower(clean($_GET['type'] ?? 'card'));

if (empty($ref_input)) {
    http_response_code(400);
    echo 'Missing reference number or membership id.';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM membership_requests WHERE (reference_number = ? OR membership_id = ?) AND status = 'Approved' AND LOWER(payment_status) = 'paid' LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo 'Database query error: ' . htmlspecialchars($conn->error);
    exit;
}

$stmt->bind_param("ss", $ref_input, $ref_input);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo 'Membership record not found, or payment/approval is still pending.';
    exit;
}

// Clean output buffer before generating PDF binary output
while (ob_get_level() > 0) {
    ob_end_clean();
}

try {
    if ($type === 'card') {
        generate_verification_slip($row, 'E_Verification_Slip.pdf', 'D');
    } else {
        generate_membership_card($row, 'Membership_Card.pdf', 'D');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'PDF Generation Error: ' . htmlspecialchars($e->getMessage());
}
exit;
