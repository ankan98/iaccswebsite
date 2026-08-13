<?php
ob_start();
@ini_set('upload_max_filesize', '25M');
@ini_set('post_max_size', '30M');
@ini_set('memory_limit', '256M');

$conn = require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/mailer.php';

$debug_payload = '';
if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.',
    ]);
    exit;
}

header('Content-Type: application/json');

/* ===============================
   Helper Functions
================================ */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function get_state_code($state_name)
{
    $map = [
        'andaman and nicobar islands' => 'AN',
        'andhra pradesh' => 'AP',
        'arunachal pradesh' => 'AR',
        'assam' => 'AS',
        'bihar' => 'BR',
        'chandigarh' => 'CH',
        'chhattisgarh' => 'CG',
        'dadra and nagar haveli and daman and diu' => 'DN',
        'delhi' => 'DL',
        'goa' => 'GA',
        'gujarat' => 'GJ',
        'haryana' => 'HR',
        'himachal pradesh' => 'HP',
        'jammu and kashmir' => 'JK',
        'jharkhand' => 'JH',
        'karnataka' => 'KA',
        'kerala' => 'KL',
        'ladakh' => 'LA',
        'lakshadweep' => 'LD',
        'madhya pradesh' => 'MP',
        'maharashtra' => 'MH',
        'manipur' => 'MN',
        'meghalaya' => 'ML',
        'mizoram' => 'MZ',
        'nagaland' => 'NL',
        'odisha' => 'OR',
        'orissa' => 'OR', // fallback
        'punjab' => 'PB',
        'rajasthan' => 'RJ',
        'sikkim' => 'SK',
        'tamil nadu' => 'TN',
        'telangana' => 'TS',
        'tripura' => 'TR',
        'uttar pradesh' => 'UP',
        'uttarakhand' => 'UK',
        'west bengal' => 'WB'
    ];

    $key = strtolower(trim($state_name));
    $index = array_search(strtoupper($key), $map);
    return $index === false ? ($map[$key] ?? 'NA') : strtoupper($key);
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
function saveUploadToUploads($file, string $subdir, string $prefix): ?string
{
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    $targetDir = $baseDir . DIRECTORY_SEPARATOR . $subdir;
    ensure_dir($targetDir);

    $mime = detect_mime_type((string) $file['tmp_name']) ?: ((string) ($file['type'] ?? ''));
    $filename = unique_upload_name($prefix, (string) ($file['name'] ?? ''), $mime);
    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    // Always store forward-slash paths in DB for web links.
    return 'uploads/' . $subdir . '/' . $filename;
}

function validateFile($file, $allowedTypes, $required = false, $maxSize = 15728640) {
    if (!isset($file) || !is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? ['error' => 'This file is required'] : null;
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['error' => 'File size exceeds maximum upload limit. Please upload a smaller file.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed (Code ' . $file['error'] . ')'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => 'File size exceeds 15MB limit'];
    }

    $mime = detect_mime_type((string) ($file['tmp_name'] ?? '')) ?: ((string) ($file['type'] ?? ''));
    $ext = safe_extension((string) ($file['name'] ?? ''), $mime);

    $validExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'heic', 'heif'];
    if (!empty($ext) && !in_array($ext, $validExts)) {
        return ['error' => 'Invalid file extension .' . $ext . '. Allowed: JPG, PNG, WEBP, PDF'];
    }
    unset($file['error']);
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
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function stmt_fetch_assoc(mysqli_stmt $stmt): ?array
{
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if (!$res) return null;
        $row = $res->fetch_assoc();
        return is_array($row) ? $row : null;
    }

    $meta = $stmt->result_metadata();
    if (!$meta) return null;

    $row = [];
    $bind = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
    }
    foreach ($row as &$value) {
        $bind[] = &$value;
    }
    unset($value);

    if (!call_user_func_array([$stmt, 'bind_result'], $bind)) {
        return null;
    }
    if (!$stmt->fetch()) {
        return null;
    }

    // Copy to detach references
    $out = [];
    foreach ($row as $k => $v) {
        $out[$k] = $v;
    }
    return $out;
}

function create_new_reference_number(mysqli $conn, string $table = 'membership_requests'): string
{
    // Fetch next AUTO_INCREMENT value
    $sql = "
        SELECT AUTO_INCREMENT AS auto_increment
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $fallback = str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
        return date('dmYhi') . $fallback;
    }
    $stmt->bind_param('s', $table);
    if (!$stmt->execute()) {
        $fallback = str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
        return date('dmYhi') . $fallback;
    }
    $result = stmt_fetch_assoc($stmt) ?: [];

    $nextId = (int)($result['auto_increment'] ?? 0);
    if ($nextId <= 0) $nextId = 1;

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
    if(strlen($state) > 2){
        $state = get_state_code($state);
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


/* ===============================
   Payment Step (Step 2)
================================ */
$action = $_POST['action'] ?? '';
if ($action === 'payment') {
    $record_id = (int)($_POST['record_id'] ?? 0);
    $reference_number = '';
    $transaction_id = clean($_POST['payment_transaction_id'] ?? '');
    $payment_proof = validateFile(
        $_FILES['payment_proof'] ?? null,
        ['image/jpeg', 'image/png', 'application/pdf'],
        false,
        5242880
    );

    $errors = [];
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
        $paymentProofPath = saveUploadToUploads($_FILES['payment_proof'] ?? null, 'payment_proofs', 'pay_');
        if (!$paymentProofPath) {
            $savedName = saveUpload($payment_proof, __DIR__ . '/assets/payment_recive');
            if ($savedName) {
                $paymentProofPath = 'assets/payment_recive/' . $savedName;
            }
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
        $rowRef = stmt_fetch_assoc($stmtRef);
        if (is_array($rowRef)) {
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
    
    // $admin_to = 'abhinandansarkar00@gmail.com';
    $admin_to = 'admin@iaccs.org.in';

    // Send Payment Confirmation to Applicant
    if (!empty($applicant_email)) {
        try {
            send_payment_confirmation_mail(
                $applicant_email,
                $applicant_name ?: 'Applicant',
                $reference_number,
                $transaction_id,
                $paymentProofPath
            );
        } catch (Throwable $e) {
            // Silently swallow mail exceptions
        }
    }
    
    // Send Payment Received Notification to Admin
    $admin_subject = "Payment Received - Ref: $reference_number";
    $admin_msg = "Payment has been submitted for Application Ref: $reference_number.\nTransaction ID: $transaction_id\nApplicant Name: $applicant_name";
    try {
        @mail($admin_to, $admin_subject, $admin_msg, "From: noreply@iaccs.org.in\r\n");
    } catch (Throwable $e) {
        // Silently swallow mail exceptions
    }

    $base_url = (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1'))
        ? 'http://localhost/iaccs/'
        : 'https://iaccs.org.in/';

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Payment details submitted successfully.',
        'redirect_url' => $base_url . 'thankyou.php?ref=' . urlencode($reference_number),
        'debug' => $debug_payload,
    ]);
    exit;
}

function insert_membership_request(mysqli $conn, string $reference_number, string $membership_id, array $file_paths = []): bool
{
    $data = [
        'name' => clean($_POST['name'] ?? ''),
        'father_name' => clean($_POST['father_name'] ?? ''),
        'dob' => clean($_POST['dob'] ?? ''),
        'age' => clean($_POST['age'] ?? ''),
        'gender' => clean($_POST['gender'] ?? ''),

        'address' => clean($_POST['address'] ?? ''),
        'city' => clean($_POST['city'] ?? ''),
        'district' => clean($_POST['district'] ?? ''),
        'pin' => clean($_POST['pin'] ?? ''),
        'state' => clean($_POST['state'] ?? ''),

        'mobile' => clean($_POST['mobile'] ?? ''),
        'email' => clean($_POST['email'] ?? ''),
        'nationality' => clean($_POST['nationality'] ?? ''),

        'education' => clean($_POST['education'] ?? ''),
        'education_status' => clean($_POST['education_status'] ?? ''),
        'academic_session' => clean($_POST['academic_session'] ?? ''),
        'college_name' => clean($_POST['college_name'] ?? ''),
        'university_name' => clean($_POST['university_name'] ?? ''),

        'employed' => clean($_POST['employed'] ?? 'No'),
        'employment_type' => clean($_POST['employment_type'] ?? ''),
        'hospital_name' => clean($_POST['hospital_name'] ?? ''),
        'designation' => clean($_POST['designation'] ?? ''),
        'employee_id' => clean($_POST['employee_id'] ?? ''),

        'membership_plan' => clean($_POST['membership_plan'] ?? ''),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'reference_number' => $reference_number,
        'membership_id' => $membership_id,

        'photo' => $file_paths['photo'] ?? '',
        'id_proof' => $file_paths['id_proof'] ?? '',
        'education_doc' => $file_paths['education_doc'] ?? '',
        'student_id' => $file_paths['student_id'] ?? '',
        'employment_proof' => $file_paths['employment_proof'] ?? '',
        'paid_transaction_id_number' => '',
        'paid_transaction_proof' => '',
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
    $subject = 'Acknowledgement of Membership Application - ACCS';
    $htmlBody = "Dear Applicant $name,<br/><br/>
Thank you for submitting your membership application to the Association for Critical Care Sciences (ACCS).<br/><br/>
Your application reference number is <b>$reference_number</b>. We are pleased to inform you that your application has been successfully received and securely recorded in our system.<br/><br/>
Membership approval and further communication will be shared with you within 3-5 working days.<br/><br/>
For any assistance or queries, please contact us at admin@iaccs.org.in. We'll get back to you promptly, usually within 1-2 working days.<br/><br/>
Thank you for choosing to join the ACCS community.<br/><br/>
Regards,<br/>
Association for Critical Care Sciences (ACCS)";

    if (function_exists('smtp_mailer')) {
        try {
            $sent = smtp_mailer($toEmail, $subject, $htmlBody);
            if ($sent) return true;
        } catch (Throwable $e) {
            // fallback to mail
        }
    }

    $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "Reply-To: noreply@iaccs.org.in\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return @mail($toEmail, $subject, $htmlBody, $headers);
}

function send_payment_confirmation_mail($toEmail, $name, $reference_number, $transaction_id, $paymentProofPath = null) {
    $subject = 'Payment Received - ACCS Membership';
    $boundary = md5(time() . '_payconfirm');

    $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

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

    $message  = "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $htmlBody . "\r\n";

    if (!empty($paymentProofPath)) {
        $absPath = __DIR__ . '/' . ltrim($paymentProofPath, '/');
        attachFileFromPath($message, $absPath, $boundary);
    }

    $message .= "--$boundary--";

    return @mail($toEmail, $subject, $message, $headers);
}


/* ===============================
   Collect Form Data (Step 1)
================================ */
$name              = clean($_POST['name'] ?? '');
$father_name       = clean($_POST['father_name'] ?? '');
$dob               = clean($_POST['dob'] ?? '');
$age               = clean($_POST['age'] ?? '');
$gender            = clean($_POST['gender'] ?? '');

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
   File Uploads & Validation
================================ */
$photo = validateFile(
    $_FILES['photo'] ?? null,
    ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
    true // REQUIRED
);

$id_proof = validateFile(
    $_FILES['id_proof'] ?? null,
    ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'],
    true // REQUIRED
);

$education_doc = validateFile(
    $_FILES['education_doc'] ?? null,
    ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'],
    false
);

$student_id = validateFile(
    $_FILES['student_id'] ?? null,
    ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'],
    false
);

$employment_proof = validateFile(
    $_FILES['employment_proof'] ?? null,
    ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'],
    false
);

$fileErrors = [];
if (is_array($photo) && isset($photo['error'])) {
    $fileErrors['photo'] = ($photo['error'] === 'This file is required') ? 'Passport size photo is required' : $photo['error'];
}
if (is_array($id_proof) && isset($id_proof['error'])) {
    $fileErrors['id_proof'] = ($id_proof['error'] === 'This file is required') ? 'ID Card / Registration Certificate is required' : $id_proof['error'];
}
if (is_array($education_doc) && isset($education_doc['error'])) {
    $fileErrors['education_doc'] = $education_doc['error'];
}
if (is_array($student_id) && isset($student_id['error'])) {
    $fileErrors['student_id'] = $student_id['error'];
}
if (is_array($employment_proof) && isset($employment_proof['error'])) {
    $fileErrors['employment_proof'] = $employment_proof['error'];
}

if (!empty($fileErrors)) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Please upload all required document files.',
        'errors' => $fileErrors,
        'debug' => $debug_payload
    ]);
    exit;
}

// Save uploaded files into separate subfolders in uploads/
$file_paths = [
    'photo' => saveUploadToUploads($_FILES['photo'] ?? null, 'photos', 'photo_') ?: '',
    'id_proof' => saveUploadToUploads($_FILES['id_proof'] ?? null, 'id_proofs', 'id_proof_') ?: '',
    'education_doc' => saveUploadToUploads($_FILES['education_doc'] ?? null, 'education_docs', 'edu_') ?: '',
    'student_id' => saveUploadToUploads($_FILES['student_id'] ?? null, 'student_ids', 'student_') ?: '',
    'employment_proof' => saveUploadToUploads($_FILES['employment_proof'] ?? null, 'employment_proofs', 'emp_') ?: '',
];

// Email OTP Verification Check
require_once __DIR__ . '/otp_helper.php';
ensure_email_otps_table($conn);

$stmtOtpCheck = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND is_verified = 1 LIMIT 1");
$is_email_verified = false;
if ($stmtOtpCheck) {
    $stmtOtpCheck->bind_param("s", $email);
    $stmtOtpCheck->execute();
    $stmtOtpCheck->store_result();
    if ($stmtOtpCheck->num_rows > 0) {
        $is_email_verified = true;
    }
}

if (!$is_email_verified) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Email verification required. Please verify your email with OTP.',
        'errors' => [
            'email' => 'Email address is not verified via OTP.'
        ],
        'debug' => $debug_payload
    ]);
    exit;
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
$errors = [];

$errors = [];


/* ===============================
   Email Setup (Step 1)
================================ */
$to       = 'admin@iaccs.org.in';
// $to       = 'abhinandansarkar00@gmail.com';
$subject  = 'New Membership Application (Documents Attached)';
$boundary = md5(time());

$headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

/* ===============================
   HTML Body
================================ */
$htmlBody = "
<h3>New Membership Application</h3>
<table border='1' cellpadding='6' cellspacing='0'>
<tr><th>Reference Number</th><td><b>$reference_number</b></td></tr>
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
    $inserted = insert_membership_request($conn, $reference_number, $membership_id, $file_paths);
    $adminMailed = false;
    $thankMailed = false;

    if ($inserted) {
        
        // Step 1: SEND NEW APPLICATION MAIL TO ADMIN (With Attachments)
        $adminMailed = @mail($to, $subject, $message, $headers);
        
        // Step 1: Send thank-you/payment mail to APPLICANT.
        $thankMailed = send_thank_you_mail(
            $email,
            $name,
            ucfirst($membership_plan),
            $amount,
            $reference_number
        );

        $msg = 'Membership application submitted successfully.';
        $msg .= ($adminMailed && $thankMailed)
            ? ' Payment details sent to your email.'
            : ' Email delivery pending.';

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'redirect_url' => '',
            'reference_number' => $reference_number,
            'record_id' => $conn->insert_id,
            'debug' => $debug_payload,
        ]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Server error. Please try again later.',
            'debug' => $debug_payload,
        ]);
    }
} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.',
        'error' => $e->getMessage(),
        'debug' => $debug_payload,
    ]);
}