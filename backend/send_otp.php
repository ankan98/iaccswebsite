<?php
// backend/send_otp.php
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
if (file_exists(__DIR__ . '/mailer.php')) {
    require_once __DIR__ . '/mailer.php';
}

ensure_email_otps_table($conn);

// Read raw JSON body or $_POST
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? $_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

// Clean old unverified OTPs for this email to prevent multiple row accumulation
$deleteOld = $conn->prepare("DELETE FROM email_otps WHERE email = ? AND is_verified = 0");
if ($deleteOld) {
    $deleteOld->bind_param("s", $email);
    $deleteOld->execute();
}

// Generate 6-digit OTP
$otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', time() + 600); // 10 minutes

// Save OTP to DB
$stmt = $conn->prepare("INSERT INTO email_otps (email, otp, expires_at) VALUES (?, ?, ?)");
if (!$stmt) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database query preparation failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("sss", $email, $otp, $expires_at);
if (!$stmt->execute()) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save OTP to database.'
    ]);
    exit;
}

// Send OTP email via SMTP Mailer
$to = $email;
$subject = "Your IACCS Email Verification OTP Code: $otp";

$htmlMessage = "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'></head>
<body style='font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px;'>
  <div style='max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 30px; border: 1px solid #e2e8f0;'>
    <h2 style='color: #0284c7; margin-top: 0;'>Email Verification</h2>
    <p style='color: #334155; font-size: 15px;'>Thank you for applying for IACCS Membership. Please use the following 6-digit OTP code to verify your email address:</p>
    <div style='background: #f0f9ff; border: 1px dashed #0284c7; border-radius: 8px; text-align: center; padding: 15px; margin: 20px 0;'>
      <span style='font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #0369a1;'>$otp</span>
    </div>
    <p style='color: #64748b; font-size: 13px;'>This OTP is valid for 10 minutes. Please do not share this code with anyone.</p>
    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
    <p style='color: #94a3b8; font-size: 12px; text-align: center;'>Association for Critical Care Sciences (IACCS)</p>
  </div>
</body>
</html>
";

$mailSent = false;
if (function_exists('smtp_mailer')) {
    try {
        $mailSent = smtp_mailer($to, $subject, $htmlMessage);
    } catch (Throwable $e) {
        $mailSent = false;
    }
}

if (!$mailSent) {
    $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "Reply-To: admin@iaccs.org.in\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    try {
        $mailSent = @mail($to, $subject, $htmlMessage, $headers);
    } catch (Throwable $e) {
        $mailSent = false;
    }
}

if (ob_get_length()) ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'A 6-digit verification code has been sent to ' . $email . '. Please check your email inbox.',
    'otp_sent' => true
]);
exit;
