<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
ini_set('display_errors', 0);
error_reporting(0);
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'agcinfos_iaccs';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

// Set charset (important)
$conn->set_charset('utf8mb4');

header('Content-Type: application/json');

// Debug payload for local dev (prints POST data in JSON response)
$debug_payload = null;
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host === 'localhost' || $host === '127.0.0.1') {
    $debug_payload = [
        'post' => $_POST,
        'files' => array_map(function ($file) {
            return [
                'name' => $file['name'] ?? null,
                'type' => $file['type'] ?? null,
                'size' => $file['size'] ?? null,
            ];
        }, $_FILES),
    ];
}

/* ===============================
   Helper Functions
================================ */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function membership_price($membership_plan){
    return match($membership_plan){
        'basic' => 50,
        'premium' => 100,
        'enterprice' => 200,
        default => 0
    };
}

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

function detect_mime_type(string $tmpPath): string
{
    if ($tmpPath === '' || !file_exists($tmpPath)) return '';
    if (!function_exists('finfo_open')) return '';
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) return '';
    $mime = finfo_file($finfo, $tmpPath) ?: '';
    finfo_close($finfo);
    return is_string($mime) ? $mime : '';
}

function ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function safe_extension(string $originalName, string $mimeType = ''): string
{
    $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    if ($ext) return $ext;

    return match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        default => '',
    };
}

function unique_upload_name(string $prefix, string $originalName, string $mimeType = ''): string
{
    $ext = safe_extension($originalName, $mimeType);
    $rand = bin2hex(random_bytes(12));
    $ts = date('YmdHis');
    return $prefix . $ts . '_' . $rand . ($ext ? ('.' . $ext) : '');
}

/**
 * Saves a validated upload into `uplods/<subdir>/` and returns a DB-safe relative path:
 * e.g. `uplods/photos/<filename>`
 */
function saveUploadToUplods($file, string $subdir, string $prefix): ?string
{
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'uplods';
    $targetDir = $baseDir . DIRECTORY_SEPARATOR . $subdir;
    ensure_dir($targetDir);

    $mime = detect_mime_type((string) $file['tmp_name']) ?: ((string) ($file['type'] ?? ''));
    $filename = unique_upload_name($prefix, (string) ($file['name'] ?? ''), $mime);
    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    // Always store forward-slash paths in DB for web links.
    return 'uplods/' . $subdir . '/' . $filename;
}


function validateFile($file, $allowedTypes, $required = false, $maxSize = 2097152) {
    if (!isset($file) || !is_array($file) || (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
        return $required ? ['error' => 'This file is required'] : null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload error'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => 'File size exceeds 2MB'];
    }

    $detected = '';
    if (!empty($file['tmp_name'])) {
        $detected = detect_mime_type((string)$file['tmp_name']);
    }
    $mimeType = $detected ?: ($file['type'] ?? '');

    if (!in_array($mimeType, $allowedTypes, true)) {
        return ['error' => 'Invalid file type'];
    }

    // Normalize to detected MIME when available.
    $file['type'] = $mimeType;
    return $file;
}

function attachFile(&$message, $file, $boundary) {
    if (!$file || !isset($file['tmp_name'])) return;

    $content = chunk_split(base64_encode(file_get_contents($file['tmp_name'])));

    $message .= "--$boundary\r\n";
    $message .= "Content-Type: {$file['type']}; name=\"{$file['name']}\"\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$file['name']}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= $content . "\r\n";
}

function attachFileFromPath(&$message, string $absolutePath, string $boundary, ?string $filename = null): void
{
    if ($absolutePath === '' || !is_file($absolutePath)) return;

    $name = $filename ?: basename($absolutePath);
    $mimeType = detect_mime_type($absolutePath) ?: 'application/octet-stream';
    $content = chunk_split(base64_encode(file_get_contents($absolutePath)));

    $message .= "--$boundary\r\n";
    $message .= "Content-Type: {$mimeType}; name=\"{$name}\"\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$name}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= $content . "\r\n";
}

function saveUpload($file, $targetDir) {
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $filename = uniqid('pay_', true) . ($safeExt ? "." . $safeExt : "");
    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }

    return null;
}

function is_duplicate_membership(mysqli $conn, $email, $mobile): bool
{
    $stmt = $conn->prepare("SELECT id FROM membership_requests WHERE email = ? AND mobile = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $email, $mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result && $result->num_rows > 0;
}

function create_new_reference_number(mysqli $conn, string $table = 'membership_requests'): string
{
    // Fetch next AUTO_INCREMENT value
    $sql = "
        SELECT AUTO_INCREMENT
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $nextId = $result['AUTO_INCREMENT'] ?? 1;

    // Left pad to minimum 3 digits (001, 023, 105, 1023...)
    $paddedId = str_pad($nextId, 3, '0', STR_PAD_LEFT);

    // Format: ddmmyyyyHHii + paddedId
    return date('dmYhi') . $paddedId;
}

function create_membership_id(mysqli $conn, string $state_code, string $table = 'membership_requests'): string
{
    $year = date('Y');
    $state = strtoupper(trim($state_code));
    if ($state === '') {
        $state = 'NA';
    }

    $prefix = "ACCSIN{$year}{$state}";
    $like = $prefix . '%';

    $stmt = $conn->prepare("SELECT membership_id FROM $table WHERE membership_id LIKE ? ORDER BY membership_id DESC LIMIT 1");
    if (!$stmt) {
        return $prefix . "A001";
    }
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();

    $nextLetter = 'A';
    $nextNumber = 1;

    if ($res && ($row = $res->fetch_assoc())) {
        $lastRef = $row['membership_id'] ?? '';
        $suffix = substr($lastRef, -4); // e.g. A001
        $letter = strtoupper(substr($suffix, 0, 1));
        $num = (int) substr($suffix, 1, 3);

        if ($letter >= 'A' && $letter <= 'Z') {
            if ($num >= 999) {
                if ($letter < 'Z') {
                    $nextLetter = chr(ord($letter) + 1);
                    $nextNumber = 1;
                } else {
                    // Wrap if exceeded Z999
                    $nextLetter = 'A';
                    $nextNumber = 1;
                }
            } else {
                $nextLetter = $letter;
                $nextNumber = $num + 1;
            }
        }
    }

    $padded = str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    return $prefix . $nextLetter . $padded;
}

/* ===============================
   Payment Step (Step 2)
================================ */
$action = $_POST['action'] ?? '';
if ($action === 'payment') {
    $record_id = (int)($_POST['record_id'] ?? 0);
    $reference_number = '';
    $transaction_id = clean($_POST['payment_transaction_id'] ?? '');
    $payment_proof_validated = validateFile(
        $_FILES['payment_proof'] ?? null,
        ['image/jpeg', 'image/png', 'application/pdf'],
        false,
        5242880
    );

    $errors = [];
    $payment_proof = null;
    if (is_array($payment_proof_validated) && isset($payment_proof_validated['error'])) {
        $errors['payment_proof'] = $payment_proof_validated['error'];
    } else {
        $payment_proof = $payment_proof_validated;
    }

    if ($record_id <= 0) {
        $errors['record_id'] = 'Record id is required';
    }
    if (empty($transaction_id) && !$payment_proof) {
        $errors['payment'] = 'Transaction ID or payment proof is required';
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'errors' => $errors,
            'debug' => $debug_payload,
        ]);
        exit;
    }

    $paymentProofPath = null;
    if ($payment_proof) {
        $paymentProofPath = saveUploadToUplods($payment_proof, 'paymets', 'pay_');
        if (!$paymentProofPath) {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'payment_proof' => 'Failed to save payment proof. Please try again.',
                ],
                'debug' => $debug_payload,
            ]);
            exit;
        }
    }

    // Fetch applicant info for email (optional) and check time window
    $stmtRef = $conn->prepare("SELECT reference_number, name, email, created_at FROM membership_requests WHERE id = ? LIMIT 1");
    $applicant_name = '';
    $applicant_email = '';
    $created_at = '';
    if ($stmtRef) {
        $stmtRef->bind_param("i", $record_id);
        $stmtRef->execute();
        $resRef = $stmtRef->get_result();
        if ($resRef && $rowRef = $resRef->fetch_assoc()) {
            $reference_number = $rowRef['reference_number'] ?? '';
            $applicant_name = $rowRef['name'] ?? '';
            $applicant_email = $rowRef['email'] ?? '';
            $created_at = $rowRef['created_at'] ?? '';
        }
    }

    if (!empty($created_at)) {
        $created_ts = strtotime($created_at);
        if ($created_ts && (time() - $created_ts) > 300) {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'payment' => 'Payment window expired. Please submit the application again.'
                ],
                'debug' => $debug_payload,
            ]);
            exit;
        }
    }

    // Update payment details in DB (if record exists)
    $payment_status = 'Not Verified';
    $stmt = $conn->prepare("UPDATE membership_requests SET transaction_id = ?, paid_transaction_id_number = ?, paid_transaction_proof = ?, payment_status = ?, payment_date = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssssi", $transaction_id, $transaction_id, $paymentProofPath, $payment_status, $record_id);
        $stmt->execute();
    }

    $admin_to = 'admin@iaccs.org.in';

    // Send "New Membership Application" mail after Step 2 (payment submit)
    $appRow = null;
    $stmtApp = $conn->prepare("SELECT * FROM membership_requests WHERE id = ? LIMIT 1");
    if ($stmtApp) {
        $stmtApp->bind_param("i", $record_id);
        $stmtApp->execute();
        $resApp = $stmtApp->get_result();
        if ($resApp) {
            $appRow = $resApp->fetch_assoc();
        }
    }

    $applicationMailed = false;
    if (is_array($appRow)) {
        $subjectApp  = 'New Membership Application (Documents Attached)';
        $boundaryApp = md5(time() . '_app');
        $headersApp  = "From: IACCS <noreply@iaccs.org.in>\r\n";
        $headersApp .= "Reply-To: " . ($appRow['email'] ?? $applicant_email) . "\r\n";
        $headersApp .= "MIME-Version: 1.0\r\n";
        $headersApp .= "Content-Type: multipart/mixed; boundary=\"$boundaryApp\"\r\n";

        $htmlBodyApp = "
<h3>New Membership Application</h3>
<table border='1' cellpadding='6' cellspacing='0'>
<tr><th>Reference Number</th><td><b>" . ($appRow['reference_number'] ?? $reference_number) . "</b></td></tr>
<tr><th>Membership ID</th><td><b>" . ($appRow['membership_id'] ?? '') . "</b></td></tr>
<tr><th>Membership Plan</th><td><b>" . ($appRow['membership_plan'] ?? '') . "</b></td></tr>
<tr><th>Amount</th><td><b>Rs. " . ($appRow['amount'] ?? '') . "</b></td></tr>
<tr><th>Record ID</th><td><b>$record_id</b></td></tr>
<tr><th>Transaction ID</th><td>" . htmlspecialchars($transaction_id) . "</td></tr>
<tr><th>Name</th><td>" . ($appRow['name'] ?? '') . "</td></tr>
<tr><th>Father/Husband Name</th><td>" . ($appRow['father_name'] ?? '') . "</td></tr>
<tr><th>Date of Birth</th><td>" . ($appRow['dob'] ?? '') . "</td></tr>
<tr><th>Age</th><td>" . ($appRow['age'] ?? '') . "</td></tr>
<tr><th>Gender</th><td>" . ($appRow['gender'] ?? '') . "</td></tr>
<tr><th>Address</th><td>" . ($appRow['address'] ?? '') . "</td></tr>
<tr><th>City</th><td>" . ($appRow['city'] ?? '') . "</td></tr>
<tr><th>District</th><td>" . ($appRow['district'] ?? '') . "</td></tr>
<tr><th>PIN</th><td>" . ($appRow['pin'] ?? '') . "</td></tr>
<tr><th>State</th><td>" . ($appRow['state'] ?? '') . "</td></tr>
<tr><th>Mobile</th><td>" . ($appRow['mobile'] ?? '') . "</td></tr>
<tr><th>Email</th><td>" . ($appRow['email'] ?? '') . "</td></tr>
<tr><th>Nationality</th><td>" . ($appRow['nationality'] ?? '') . "</td></tr>
<tr><th>Educational Qualification</th><td>" . ($appRow['education'] ?? '') . "</td></tr>
<tr><th>Status</th><td>" . ($appRow['education_status'] ?? '') . "</td></tr>
<tr><th>Academic Session</th><td>" . ($appRow['academic_session'] ?? '') . "</td></tr>
<tr><th>College/Institution</th><td>" . ($appRow['college_name'] ?? '') . "</td></tr>
<tr><th>University</th><td>" . ($appRow['university_name'] ?? '') . "</td></tr>
<tr><th>Currently Employed</th><td>" . ($appRow['employed'] ?? '') . "</td></tr>
<tr><th>Employment Type</th><td>" . ($appRow['employment_type'] ?? '') . "</td></tr>
<tr><th>Hospital/Institute</th><td>" . ($appRow['hospital_name'] ?? '') . "</td></tr>
<tr><th>Designation</th><td>" . ($appRow['designation'] ?? '') . "</td></tr>
<tr><th>Employee ID</th><td>" . ($appRow['employee_id'] ?? '') . "</td></tr>
</table>
";

        $messageApp  = "--$boundaryApp\r\n";
        $messageApp .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $messageApp .= $htmlBodyApp . "\r\n";

        $pathsToAttach = [
            (string)($appRow['photo'] ?? ''),
            (string)($appRow['id_proof'] ?? ''),
            (string)($appRow['education_doc'] ?? ''),
            (string)($appRow['student_id'] ?? ''),
            (string)($appRow['employment_proof'] ?? ''),
            (string)($paymentProofPath ?? ''),
        ];

        foreach ($pathsToAttach as $rel) {
            $rel = trim(str_replace('\\', '/', $rel));
            if ($rel === '' || str_starts_with($rel, 'http://') || str_starts_with($rel, 'https://')) continue;
            if (str_contains($rel, '..')) continue;
            $abs = __DIR__ . '/' . ltrim($rel, '/');
            attachFileFromPath($messageApp, $abs, $boundaryApp);
        }

        $messageApp .= "--$boundaryApp--";
        $applicationMailed = mail($admin_to, $subjectApp, $messageApp, $headersApp);
    }

    // Email admin with payment details
    $to = $admin_to;
    $subject = 'Payment Confirmation - ACCS Membership';
    $boundary = md5(time());
    $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $htmlBody = "
    <h3>Payment Confirmation</h3>
    <table border='1' cellpadding='6' cellspacing='0'>
      <tr><th>Reference Number</th><td><b>$reference_number</b></td></tr>
      <tr><th>Record ID</th><td><b>$record_id</b></td></tr>
      <tr><th>Transaction ID</th><td>$transaction_id</td></tr>
    </table>
    ";

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $htmlBody . "\r\n";

    if (!empty($paymentProofPath)) {
        $absProof = __DIR__ . '/' . ltrim(str_replace('\\', '/', $paymentProofPath), '/');
        attachFileFromPath($message, $absProof, $boundary);
    }
    $message .= "--$boundary--";

    $adminMailed = mail($to, $subject, $message, $headers);

    if (!empty($applicant_email)) {
        send_payment_confirmation_mail(
            $applicant_email,
            $applicant_name ?: 'Applicant',
            $reference_number,
            $transaction_id
        );
    }

    $base_url = (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1'))
        ? 'http://localhost/iaccs/'
        : 'https://iaccs.org.in/';

    echo json_encode([
        'success' => true,
        'message' => 'Payment details submitted successfully.',
        'redirect_url' => $base_url . 'thankyou.php?ref=' . urlencode($reference_number),
        'debug' => $debug_payload,
    ]);
    exit;
}

function insert_membership_request(mysqli $conn, $reference_number, $membership_id, array $file_paths = []): bool
{
    $data = [
        'name' => clean($_POST['name']),
        'father_name' => clean($_POST['father_name']),
        'dob' => clean($_POST['dob']),
        'age' => clean($_POST['age']),
        'gender' => clean($_POST['gender']),

        'address' => clean($_POST['address']),
        'city' => clean($_POST['city']),
        'district' => clean($_POST['district']),
        'pin' => clean($_POST['pin']),
        'state' => clean($_POST['state']),

        'mobile' => clean($_POST['mobile']),
        'email' => clean($_POST['email']),
        'nationality' => clean($_POST['nationality']),

        'education' => clean($_POST['education']),
        'education_status' => clean($_POST['education_status']),
        'academic_session' => clean($_POST['academic_session']),
        'college_name' => clean($_POST['college_name']),
        'university_name' => clean($_POST['university_name']),

        'employed' => clean($_POST['employed']),
        'employment_type' => clean($_POST['employment_type']),
        'hospital_name' => clean($_POST['hospital_name']),
        'designation' => clean($_POST['designation']),
        'employee_id' => clean($_POST['employee_id']),

        'membership_plan' => clean($_POST['membership_plan']),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'reference_number' => $reference_number,
        'membership_id' => $membership_id,

        'photo' => (string)($file_paths['photo'] ?? ''),
        'id_proof' => (string)($file_paths['id_proof'] ?? ''),
        'education_doc' => (string)($file_paths['education_doc'] ?? ''),
        'student_id' => (string)($file_paths['student_id'] ?? ''),
        'employment_proof' => (string)($file_paths['employment_proof'] ?? ''),
        'paid_transaction_id_number' => "",
        'paid_transaction_proof' => "",
    ];

    $sql = "
    INSERT INTO membership_requests (
        name, father_name, dob, age, gender,
        address, city, district, pin, state,
        mobile, email, nationality,
        education, education_status, academic_session, college_name, university_name,
        employed, employment_type, hospital_name, designation, employee_id,
        membership_plan, amount, reference_number, membership_id,
        photo, id_proof, education_doc, student_id, employment_proof,
        paid_transaction_id_number, paid_transaction_proof
    ) VALUES (
        ?,?,?,?,?, ?,?,?,?,?, ?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?, ?,?,?,?,?,?, ?,?,?
    )";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "ssssssssssssssssssssssssdsssssssss",
        $data['name'],
        $data['father_name'],
        $data['dob'],
        $data['age'],
        $data['gender'],
        $data['address'],
        $data['city'],
        $data['district'],
        $data['pin'],
        $data['state'],
        $data['mobile'],
        $data['email'],
        $data['nationality'],
        $data['education'],
        $data['education_status'],
        $data['academic_session'],
        $data['college_name'],
        $data['university_name'],
        $data['employed'],
        $data['employment_type'],
        $data['hospital_name'],
        $data['designation'],
        $data['employee_id'],
        $data['membership_plan'],
        $data['amount'],
        $data['reference_number'],
        $data['membership_id'],
        $data['photo'],
        $data['id_proof'],
        $data['education_doc'],
        $data['student_id'],
        $data['employment_proof'],
        $data['paid_transaction_id_number'],
        $data['paid_transaction_proof']
    );

    return $stmt->execute();
}


function send_thank_you_mail($toEmail, $name, $membership_plan, $amount, $reference_number) {

    $upiId   = 'bapandoct.98-1@okaxis';
    $subject = 'Acknowledgement of Membership Application - ACCS';

    // QR image path (local server path)
    $qrPath = __DIR__ . '/google_pay.jpg'; // <-- save QR image with this name
    $qrCid  = 'https://iaccs.org.in/google_pay.jpg';

    $boundary = md5(time());

    $headers  = "From: IACCS <abhinandansarkar00@gmail.com>\r\n";
    // $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/related; boundary=\"$boundary\"\r\n";

    /* ===============================
       Email HTML Body
    ================================ */
    $upiHtml = '
        <h5 align="center" style="margin:10px auto">UPI ID:- 8918505434-1@okbizaxis</h5><img src="'. $qrCid .'" alt="Scan to Pay" width="250" style="display:block;margin:auto; margin-top: 20px;">';
    $htmlBody = "Dear Applicant $name,

Thank you for submitting your membership application to the Association for Critical Care Sciences (ACCS).

Your application reference number is $reference_number. We are pleased to inform you that your application has been successfully received and securely recorded in our system.

To complete the membership process, please proceed with the payment using the following secure link provided below in this email. Kindly ensure that the payment is made under the correct membership category (Student or Professional) as selected during submission. Please note that applications without successful payment will not be processed.

Upon confirmation of payment, your application will be reviewed. Membership approval and further communication will be shared with you within 3-5 working days.

For any assistance or queries, please contact us at admin@iaccs.org.in. We'll get back to you promptly, usually within 1-2 working days.

Thank you for choosing to join the ACCS community. We are excited to have you on board!

$upiHtml

Regards,
Association for Critical Care Sciences (ACCS)";

    $htmlBody = nl2br($htmlBody);

    /* ===============================
       Build Email
    ================================ */
    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $htmlBody . "\r\n";

    /* ===============================
       Attach QR Image Inline
    ================================ */
    // if (file_exists($qrPath)) {

    //     $qrContent = chunk_split(base64_encode(file_get_contents($qrPath)));

    //     $message .= "--$boundary\r\n";
    //     $message .= "Content-Type: image/jpeg; name=\"google_pay.jpg\"\r\n";
    //     $message .= "Content-Disposition: attachment; filename=\"google_pay.jpg\"\r\n";
    //     $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    //     $message .= $qrContent . "\r\n";
    // }

    $message .= "--$boundary--";

    return mail($toEmail, $subject, $message, $headers);
}

function send_payment_confirmation_mail($toEmail, $name, $reference_number, $transaction_id) {
    $subject = 'Payment Received - ACCS Membership';
    $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $htmlBody = "
    <p>Dear $name,</p>
    <p>We have received your payment for your membership application.</p>
    <table border='1' cellpadding='6' cellspacing='0'>
      <tr><th>Reference Number</th><td><b>$reference_number</b></td></tr>
      <tr><th>Transaction ID</th><td>$transaction_id</td></tr>
    </table>
    <p>We will review your application and get back to you shortly.</p>
    <p>Regards,<br/>Association for Critical Care Sciences (ACCS)</p>
    ";

    return mail($toEmail, $subject, $htmlBody, $headers);
}


/* ===============================
   Collect Form Data
================================ */
$name              = clean($_POST['name']);
$father_name       = clean($_POST['father_name']);
$dob               = clean($_POST['dob']);
$age               = clean($_POST['age']);
$gender            = clean($_POST['gender']);

$address           = clean($_POST['address']);
$city              = clean($_POST['city']);
$district          = clean($_POST['district']);
$pin               = clean($_POST['pin']);
$state             = clean($_POST['state']);

$mobile             = clean($_POST['mobile']);
$email              = clean($_POST['email']);
$nationality        = clean($_POST['nationality']);

$education          = clean($_POST['education']); // Diploma / Bachelor / Masters
$education_status   = clean($_POST['education_status']); // Pursuing / Completed
$academic_session   = clean($_POST['academic_session']);
$college_name       = clean($_POST['college_name']);
$university_name    = clean($_POST['university_name']);

$employed           = clean($_POST['employed']); // Yes / No
$employment_type    = clean($_POST['employment_type']); // Govt / Private
$hospital_name      = clean($_POST['hospital_name']);
$designation        = clean($_POST['designation']);
$employee_id        = clean($_POST['employee_id']);
$membership_plan    = clean($_POST['membership_plan']);
$amount             = clean($_POST['amount']);
$reference_number   = create_new_reference_number($conn);
$membership_id      = create_membership_id($conn, $state);

/* ===============================
   File Uploads
================================ */
$errors = [];

$photo_validated = validateFile(
    $_FILES['photo'] ?? null,
    ['image/jpeg', 'image/png'],
    true
);
$photo = null;
if (is_array($photo_validated) && isset($photo_validated['error'])) {
    $errors['photo'] = $photo_validated['error'];
} else {
    $photo = $photo_validated;
}

$id_proof_validated = validateFile(
    $_FILES['id_proof'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    true
);
$id_proof = null;
if (is_array($id_proof_validated) && isset($id_proof_validated['error'])) {
    $errors['id_proof'] = $id_proof_validated['error'];
} else {
    $id_proof = $id_proof_validated;
}

$education_doc_validated = validateFile(
    $_FILES['education_doc'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);
$education_doc = null;
if (is_array($education_doc_validated) && isset($education_doc_validated['error'])) {
    $errors['education_doc'] = $education_doc_validated['error'];
} else {
    $education_doc = $education_doc_validated;
}

$student_id_validated = validateFile(
    $_FILES['student_id'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);
$student_id = null;
if (is_array($student_id_validated) && isset($student_id_validated['error'])) {
    $errors['student_id'] = $student_id_validated['error'];
} else {
    $student_id = $student_id_validated;
}

$employment_proof_validated = validateFile(
    $_FILES['employment_proof'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);
$employment_proof = null;
if (is_array($employment_proof_validated) && isset($employment_proof_validated['error'])) {
    $errors['employment_proof'] = $employment_proof_validated['error'];
} else {
    $employment_proof = $employment_proof_validated;
}

// Duplicate check (email + mobile) to prevent multiple submissions
// if (is_duplicate_membership($conn, $email, $mobile)) {
//     echo json_encode([
//         'success' => false,
//         'errors' => [
//             'duplicate' => 'A membership request with this email and mobile already exists.'
//         ],
//         'debug' => $debug_payload,
//     ]);
//     exit;
// }

/* ===============================
   Validation
================================ */
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors'  => $errors,
        'debug' => $debug_payload,
    ]);
    exit;
}

// if (empty($membership_plan)) $errors['membership_plan'] = 'Membership plan is required';
// if (empty($amount)) $errors['amount'] = 'Amount is required';
// if (empty($name)) $errors['name'] = 'Name is required';
// if (empty($father_name)) $errors['father_name'] = 'Father/Husband name is required';
// if (empty($dob)) $errors['dob'] = 'Date of birth is required';
// if (empty($gender)) $errors['gender'] = 'Gender is required';

// if (empty($mobile)) {
//     $errors['mobile'] = 'Mobile number is required';
// } elseif (!preg_match('/^\d{10}$/', $mobile)) {
//     $errors['mobile'] = 'Mobile must be 10 digits';
// }

// if (empty($email)) {
//     $errors['email'] = 'Email is required';
// } elseif (!isValidEmail($email)) {
//     $errors['email'] = 'Invalid email format';
// }

// if (empty($address)) $errors['address'] = 'Address is required';
// if (empty($education)) $errors['education'] = 'Educational qualification is required';

// if (!empty($errors)) {
//     echo json_encode([
//         'success' => false,
//         'errors'  => $errors
//     ]);
//     exit;
// }

/* ===============================
   Email Setup
================================ */
// $to       = 'admin@iaccs.org.in';
 $to       = 'abhinandansarkar00@gmail.com';
$subject  = 'New Membership Application (Documents Attached)';
$boundary = md5(time());

$headers  = "From: IACCS <abhinandansarkar00@gmail.com>\r\n";
// $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

/* ===============================
   HTML Body
================================ */
$htmlBody = "
<h3>New Membership Application</h3>
<table border='1' cellpadding='6' cellspacing='0'>
<tr><th>Membership Plan</th><td><b>$membership_plan</b></td></tr>
<tr><th>Amount</th><td><b>Rs. $amount</b></td></tr>
<tr><th>Name</th><td>$name</td></tr>
<tr><th>Father/Husband Name</th><td>$father_name</td></tr>
<tr><th>Date of Birth</th><td>$dob</td></tr>
<tr><th>Age</th><td>$age</td></tr>
<tr><th>Gender</th><td>$gender</td></tr>

<tr><th>Address</th><td>$address</td></tr>
<tr><th>City</th><td>$city</td></tr>
<tr><th>District</th><td>$district</td></tr>
<tr><th>PIN</th><td>$pin</td></tr>
<tr><th>State</th><td>$state</td></tr>

<tr><th>Mobile</th><td>$mobile</td></tr>
<tr><th>Email</th><td>$email</td></tr>
<tr><th>Nationality</th><td>$nationality</td></tr>

<tr><th>Educational Qualification</th><td>$education</td></tr>
<tr><th>Status</th><td>$education_status</td></tr>
<tr><th>Academic Session</th><td>$academic_session</td></tr>
<tr><th>College/Institution</th><td>$college_name</td></tr>
<tr><th>University</th><td>$university_name</td></tr>

<tr><th>Currently Employed</th><td>$employed</td></tr>
<tr><th>Employment Type</th><td>$employment_type</td></tr>
<tr><th>Hospital/Institute</th><td>$hospital_name</td></tr>
<tr><th>Designation</th><td>$designation</td></tr>
<tr><th>Employee ID</th><td>$employee_id</td></tr>
</table>
";

/* ===============================
   Build Email
================================ */
$message  = "--$boundary\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
$message .= $htmlBody . "\r\n";

/* ===============================
   Attach Files
================================ */
attachFile($message, $photo, $boundary);
attachFile($message, $id_proof, $boundary);
attachFile($message, $education_doc, $boundary);
attachFile($message, $student_id, $boundary);
attachFile($message, $employment_proof, $boundary);

$message .= "--$boundary--";

/* ===============================
   Send Email
================================ */
try {
    $file_paths = [];
    $file_paths['photo'] = saveUploadToUplods($photo, 'photos', 'photo_');
    $file_paths['id_proof'] = saveUploadToUplods($id_proof, 'ids', 'id_');

    if (!$file_paths['photo'] || !$file_paths['id_proof']) {
        echo json_encode([
            'success' => false,
            'errors' => [
                'upload' => 'Failed to save uploaded documents. Please try again.',
            ],
            'debug' => $debug_payload,
        ]);
        exit;
    }

    if ($education_doc) {
        $file_paths['education_doc'] = saveUploadToUplods($education_doc, 'ids', 'edu_');
        if (!$file_paths['education_doc']) {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'education_doc' => 'Failed to save education document. Please try again.',
                ],
                'debug' => $debug_payload,
            ]);
            exit;
        }
    }

    if ($student_id) {
        $file_paths['student_id'] = saveUploadToUplods($student_id, 'ids', 'stu_');
        if (!$file_paths['student_id']) {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'student_id' => 'Failed to save student ID. Please try again.',
                ],
                'debug' => $debug_payload,
            ]);
            exit;
        }
    }

    if ($employment_proof) {
        $file_paths['employment_proof'] = saveUploadToUplods($employment_proof, 'ids', 'emp_');
        if (!$file_paths['employment_proof']) {
            echo json_encode([
                'success' => false,
                'errors' => [
                    'employment_proof' => 'Failed to save employment proof. Please try again.',
                ],
                'debug' => $debug_payload,
            ]);
            exit;
        }
    }

    $inserted = insert_membership_request($conn, $reference_number, $membership_id, $file_paths);
    $adminMailed = false;
    $thankMailed = false;

    if ($inserted) {
        // Step 1: No admin email. Send thank-you/payment mail to applicant.
        $thankMailed = send_thank_you_mail(
            $email,
            $name,
            ucfirst($membership_plan),
            $amount,
            $reference_number
        );

        $msg = 'Membership application submitted successfully.';
        $msg .= $thankMailed
            ? ' Payment details sent to your email.'
            : ' Email delivery pending.';

        echo json_encode([
            'success' => true,
            'message' => $msg,
            'redirect_url' => '',
            'reference_number' => $reference_number,
            'record_id' => $conn->insert_id,
            'debug' => $debug_payload,
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Server error. Please try again later.',
            'debug' => $debug_payload,
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.',
        'error' => $e->getMessage(),
        'debug' => $debug_payload,
    ]);
}
