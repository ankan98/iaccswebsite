<?php
ob_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
include_once "conn.php";
include_once "membership-card.php";
// conn.php may enable display_errors in development; force-disable for PDF output.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

$reference = clean($_GET['ref'] ?? '');
$membership_id = clean($_GET['membership_id'] ?? '');
$type = strtolower(clean($_GET['type'] ?? 'card'));

if ($reference === '' && $membership_id === '') {
    http_response_code(400);
    echo 'Missing reference number or membership id.';
    exit;
}

if ($reference !== '') {
    $stmt = $conn->prepare("SELECT * FROM membership_requests WHERE reference_number = ? AND status = 'Approved' AND LOWER(payment_status) = 'paid' LIMIT 1");
    $stmt->bind_param("s", $reference);
} else {
    $stmt = $conn->prepare("SELECT * FROM membership_requests WHERE membership_id = ? AND status = 'Approved' AND LOWER(payment_status) = 'paid' LIMIT 1");
    $stmt->bind_param("s", $membership_id);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo 'Membership not approved/paid or not found.';
    exit;
}

// Prevent TCPDF header errors if any notices/warnings were buffered.
if (ob_get_length()) { ob_clean(); }

if ($type === 'card') {
   generate_verification_slip($row, 'E_Verification_Slip.pdf', 'I');
} else {
generate_membership_card($row, 'Membership_Card.pdf', 'D');
   
}
