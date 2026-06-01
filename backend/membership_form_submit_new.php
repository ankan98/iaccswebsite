<?php
session_start();

include_once "conn.php";

header('Content-Type: application/json');

// Create a directory for temporary file uploads
$tempDir = 'temp_uploads/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

/* ===============================
   Helper Functions
================================ */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
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


function validateFile($file, $allowedTypes, $required = false, $maxSize = 2097152) {
    if(!$file){
        return null;
    }

    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? ['error' => 'This file is required'] : null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload error'];
    }

    if ($file['size'] > $maxSize) {
        return ['error' => 'File size exceeds 2MB'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type'];
    }

    return $file;
}

function insert_membership_request(mysqli $conn, $form_data, $file_paths, $reference_number): bool
{
    $data = [
        'name' => clean($form_data['name']),
        'father_name' => clean($form_data['father_name']),
        'dob' => clean($form_data['dob']) ?: null,
        'age' => clean($form_data['age']),
        'gender' => clean($form_data['gender']),
        'address' => clean($form_data['address']),
        'city' => clean($form_data['city']),
        'district' => clean($form_data['district']),
        'pin' => clean($form_data['pin']),
        'state' => clean($form_data['state']),
        'mobile' => clean($form_data['mobile']),
        'email' => clean($form_data['email']),
        'nationality' => clean($form_data['nationality']),
        'education' => clean($form_data['education']),
        'education_status' => clean($form_data['education_status']),
        'academic_session' => clean($form_data['academic_session']),
        'college_name' => clean($form_data['college_name']),
        'university_name' => clean($form_data['university_name']),
        'employed' => clean($form_data['employed']),
        'employment_type' => clean($form_data['employment_type']),
        'hospital_name' => clean($form_data['hospital_name']),
        'designation' => clean($form_data['designation']),
        'employee_id' => clean($form_data['employee_id']),
        'membership_plan' => clean($form_data['membership_plan']),
        'amount' => (float) ($form_data['amount'] ?? 0),
        'reference_number' => $reference_number,
        'photo' => $file_paths['photo'],
        'id_proof' => $file_paths['id_proof'],
        'education_doc' => $file_paths['education_doc'],
        'student_id' => $file_paths['student_id'],
        'employment_proof' => $file_paths['employment_proof'],
        'payment_status' => 'pending',
        'status' => 'Pending',
    ];

    $sql = "
    INSERT INTO membership_requests (
        name, father_name, dob, age, gender,
        address, city, district, pin, state,
        mobile, email, nationality,
        education, education_status, academic_session, college_name, university_name,
        employed, employment_type, hospital_name, designation, employee_id,
        membership_plan, amount, reference_number,
        photo, id_proof, education_doc, student_id, employment_proof,
        payment_status, status
    ) VALUES (
        ?,?,?,?,?, ?,?,?,?,?, ?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?, ?,?,?,?,?, ?,?,?
    )";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "ssssssssssssssssssssssssdssssssss",
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
        $data['photo'],
        $data['id_proof'],
        $data['education_doc'],
        $data['student_id'],
        $data['employment_proof'],
        $data['payment_status'],
        $data['status']
    );

    return $stmt->execute();
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

/* ===============================
   File Uploads
================================ */
$photo = validateFile(
    $_FILES['photo'] ?? null,
    ['image/jpeg', 'image/png'],
    false
);

$id_proof = validateFile(
    $_FILES['id_proof'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);

$education_doc = validateFile(
    $_FILES['education_doc'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);

$student_id = validateFile(
    $_FILES['student_id'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);

$employment_proof = validateFile(
    $_FILES['employment_proof'] ?? null,
    ['image/jpeg', 'image/png', 'application/pdf'],
    false
);

/* ===============================
   Validation
================================ */
$errors = [];

// Basic validation
if (empty($name)) $errors['name'] = 'Name is required';
if (empty($email)) $errors['email'] = 'Email is required';
if (!isValidEmail($email)) $errors['email'] = 'Invalid email format';
if (empty($mobile)) $errors['mobile'] = 'Mobile number is required';
if (empty($membership_plan)) $errors['membership_plan'] = 'Membership plan is required';
if (empty($amount) || !is_numeric($amount)) $errors['amount'] = 'Invalid amount';


if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors'  => $errors
    ]);
    exit;
}

$file_paths = [];

function moveUploadedFile($file, $tempDir)
{
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $filename = uniqid() . '-' . $file['name'];
        $path = $tempDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return $path;
        }
    }
    return null;
}

$file_paths['photo'] = moveUploadedFile($photo, $tempDir);
$file_paths['id_proof'] = moveUploadedFile($id_proof, $tempDir);
$file_paths['education_doc'] = moveUploadedFile($education_doc, $tempDir);
$file_paths['student_id'] = moveUploadedFile($student_id, $tempDir);
$file_paths['employment_proof'] = moveUploadedFile($employment_proof, $tempDir);

insert_membership_request($conn, $_POST, $file_paths, $reference_number);

echo json_encode([
    'success' => true,
    'message' => 'Application submitted successfully. Redirecting to payment.',
    'redirect_url' => BASE_URL . '/checkout.php?ref=' . $reference_number,
    'thankyou_url' => BASE_URL . '/thankyou.php?ref=' . $reference_number,
]);
