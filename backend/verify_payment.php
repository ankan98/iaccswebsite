<?php
session_start();
require('vendor/autoload.php');
include_once "conn.php";
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

include_once "config.php";

$success = true;
$error = "Payment Failed";

if (empty($_POST['razorpay_payment_id']) === false) {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

    try {
        $attributes = array(
            'razorpay_order_id' => $_POST['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        );

        $api->utility->verifyPaymentSignature($attributes);
    } catch(SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();
    }
}

if ($success === true) {
    $permanentDir = 'uploads/';
    if (!is_dir($permanentDir)) {
        mkdir($permanentDir, 0777, true);
    }

    $reference_number = $_SESSION['reference_number'];

    // Fetch the record from the database
    $stmt = $conn->prepare("SELECT * FROM membership_requests WHERE reference_number = ?");
    $stmt->bind_param("s", $reference_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $form_data = $result->fetch_assoc();
    $stmt->close();

    $file_paths = [
        'photo' => $form_data['photo'],
        'id_proof' => $form_data['id_proof'],
        'education_doc' => $form_data['education_doc'],
        'student_id' => $form_data['student_id'],
        'employment_proof' => $form_data['employment_proof'],
    ];

    function moveFileToPermanent($tempPath, $permanentDir)
    {
        if ($tempPath && file_exists($tempPath)) {
            $filename = basename($tempPath);
            $permanentPath = $permanentDir . $filename;
            if (rename($tempPath, $permanentPath)) {
                return $permanentPath;
            }
        }
        return null;
    }

    $permanent_file_paths = [];
    foreach ($file_paths as $key => $tempPath) {
        $permanent_file_paths[$key] = moveFileToPermanent($tempPath, $permanentDir);
    }

    function update_membership_request(mysqli $conn, $reference_number, $payment_id, $order_id, $payment_status, $payment_date, $payment_response, $permanent_file_paths): bool
    {
        $sql = "
        UPDATE membership_requests 
        SET 
            order_id = ?,
            payment_status = ?,
            payment_date = ?,
            transaction_id = ?,
            payment_response = ?,
            photo = ?,
            id_proof = ?,
            education_doc = ?,
            student_id = ?,
            employment_proof = ?
        WHERE reference_number = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "sssssssssss",
            $order_id,
            $payment_status,
            $payment_date,
            $payment_id,
            $payment_response,
            $permanent_file_paths['photo'],
            $permanent_file_paths['id_proof'],
            $permanent_file_paths['education_doc'],
            $permanent_file_paths['student_id'],
            $permanent_file_paths['employment_proof'],
            $reference_number
        );

        return $stmt->execute();
    }

    update_membership_request($conn, $reference_number, $_POST['razorpay_payment_id'], $_SESSION['razorpay_order_id'], 'Paid', date('Y-m-d H:i:s'), json_encode($_POST), $permanent_file_paths);
    
    function send_thank_you_mail($toEmail, $name, $membership_plan, $amount, $reference_number, $payment_id) {

        $subject = 'Acknowledgement of Membership Application - ACCS';
    
        $boundary = md5(time());
    
        $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/related; boundary=\"$boundary\"\r\n";
    
        $htmlBody = "Dear Applicant $name,

Thank you for submitting your membership application to the Association for Critical Care Sciences (ACCS).

Your application reference number is $reference_number. We are pleased to inform you that your application has been successfully received and securely recorded in our system. Your payment of INR $amount has been successfully processed. Payment ID: $payment_id.

Upon confirmation of payment, your application will be reviewed. Membership approval and further communication will be shared with you within 3-5 working days.

For any assistance or queries, please contact us at admin@iaccs.org.in. We'll get back to you promptly, usually within 1-2 working days.

Thank you for choosing to join the ACCS community. We are excited to have you on board!

Regards,
Association for Critical Care Sciences (ACCS)";
    
        $htmlBody = nl2br($htmlBody);
    
        $message  = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= "--$boundary--";
    
        return mail($toEmail, $subject, $message, $headers);
    }

    function send_admin_notification($form_data, $permanent_file_paths, $reference_number, $payment_id) {
        $to       = 'admin@iaccs.org.in';
        $subject  = 'New Membership Application (Documents Attached)';
        $boundary = md5(time());
    
        $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
        $headers .= "Reply-To: {$form_data['email']}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    
        $htmlBody = "
<h3>New Membership Application</h3>
<table border='1' cellpadding='6' cellspacing='0'>
<tr><th>Membership Plan</th><td><b>{$form_data['membership_plan']}</b></td></tr>
<tr><th>Amount</th><td><b>Rs. {$form_data['amount']}</b></td></tr>
<tr><th>Reference Number</th><td><b>$reference_number</b></td></tr>
<tr><th>Payment ID</th><td><b>$payment_id</b></td></tr>
<tr><th>Name</th><td>{$form_data['name']}</td></tr>
<tr><th>Father/Husband Name</th><td>{$form_data['father_name']}</td></tr>
<tr><th>Date of Birth</th><td>{$form_data['dob']}</td></tr>
<tr><th>Age</th><td>{$form_data['age']}</td></tr>
<tr><th>Gender</th><td>{$form_data['gender']}</td></tr>
<tr><th>Address</th><td>{$form_data['address']}</td></tr>
<tr><th>City</th><td>{$form_data['city']}</td></tr>
<tr><th>District</th><td>{$form_data['district']}</td></tr>
<tr><th>PIN</th><td>{$form_data['pin']}</td></tr>
<tr><th>State</th><td>{$form_data['state']}</td></tr>
<tr><th>Mobile</th><td>{$form_data['mobile']}</td></tr>
<tr><th>Email</th><td>{$form_data['email']}</td></tr>
<tr><th>Nationality</th><td>{$form_data['nationality']}</td></tr>
<tr><th>Educational Qualification</th><td>{$form_data['education']}</td></tr>
<tr><th>Status</th><td>{$form_data['education_status']}</td></tr>
<tr><th>Academic Session</th><td>{$form_data['academic_session']}</td></tr>
<tr><th>College/Institution</th><td>{$form_data['college_name']}</td></tr>
<tr><th>University</th><td>{$form_data['university_name']}</td></tr>
<tr><th>Currently Employed</th><td>{$form_data['employed']}</td></tr>
<tr><th>Employment Type</th><td>{$form_data['employment_type']}</td></tr>
<tr><th>Hospital/Institute</th><td>{$form_data['hospital_name']}</td></tr>
<tr><th>Designation</th><td>{$form_data['designation']}</td></tr>
<tr><th>Employee ID</th><td>{$form_data['employee_id']}</td></tr>
</table>
";
    
        $message  = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $message .= $htmlBody . "\r\n";
    
        function attachFile(&$message, $filePath, $boundary) {
            if (!$filePath || !file_exists($filePath)) return;
        
            $content = chunk_split(base64_encode(file_get_contents($filePath)));
            $filename = basename($filePath);
            $type = mime_content_type($filePath);

            $message .= "--$boundary\r\n";
            $message .= "Content-Type: {$type}; name=\"{$filename}\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= $content . "\r\n";
        }
    
        foreach($permanent_file_paths as $filePath) {
            attachFile($message, $filePath, $boundary);
        }
    
        $message .= "--$boundary--";
    
        return mail($to, $subject, $message, $headers);
    }

    send_thank_you_mail($form_data['email'], $form_data['name'], $form_data['membership_plan'], $form_data['amount'], $reference_number, $_POST['razorpay_payment_id']);
    send_admin_notification($form_data, $permanent_file_paths, $reference_number, $_POST['razorpay_payment_id']);
    
    unset($_SESSION['reference_number']);
    unset($_SESSION['razorpay_order_id']);

    header("Location: thankyou.php?ref=" . $reference_number . "&payment_id=" . $_POST['razorpay_payment_id']);
    exit();
} else {
    echo $error;
}