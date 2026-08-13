<?php
// backend/verify_otp.php
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/otp_helper.php';
ensure_email_otps_table($conn);

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? $_POST['email'] ?? '');
$otp   = trim($input['otp'] ?? $_POST['otp'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

if (empty($otp) || strlen($otp) < 4) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid 6-digit OTP code.'
    ]);
    exit;
}

$now = date('Y-m-d H:i:s');

// 1. Check if email is already verified
$checkVerified = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND is_verified = 1 LIMIT 1");
if ($checkVerified) {
    $checkVerified->bind_param("s", $email);
    $checkVerified->execute();
    $checkVerified->store_result();
    if ($checkVerified->num_rows > 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully!',
            'is_verified' => true
        ]);
        exit;
    }
}

// 2. Query email_otps table with current PHP timestamp
$stmt = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND otp = ? AND expires_at >= ? AND is_verified = 0 ORDER BY id DESC LIMIT 1");
if (!$stmt) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("sss", $email, $otp, $now);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($otp_id);
    $stmt->fetch();

    // Mark as verified
    $updateStmt = $conn->prepare("UPDATE email_otps SET is_verified = 1 WHERE email = ?");
    if ($updateStmt) {
        $updateStmt->bind_param("s", $email);
        $updateStmt->execute();
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully!',
        'is_verified' => true
    ]);
    exit;
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired OTP code. Please check and try again.'
    ]);
    exit;
}
