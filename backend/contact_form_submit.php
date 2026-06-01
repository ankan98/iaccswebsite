<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function wordCount($message) {
    return str_word_count(trim($message));
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$msg_subject   = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if (empty($name)) {
    $errors['name'] = 'Name is required.';
}

if (empty($email)) {
    $errors['email'] = 'Email is required.';
} elseif (!isValidEmail($email)) {
    $errors['email'] = 'Invalid email format.';
}

if (empty(trim($msg_subject))) {
    $errors['subject'] = 'Subject is required.';
}

if (empty($message)) {
    $errors['message'] = 'Message is required.';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$to = 'admin@iaccs.org.in';
$site = 'iaccs.org.in';
$subject = 'Contact Form Submission';
$body = "You have received a new message from the $site contact form:\n\n"
      . "Name: $name\n"
      . "Email: $email\n\n"
      . "Subject: $msg_subject\n"
      . "Message:\n$message\n";
$headers = "From: noreply@iaccs.org.in\r\n"
         . "Reply-To: $email\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $body, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'There are some problem on the server. Please try again later.']);
}
