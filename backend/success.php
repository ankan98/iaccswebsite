<?php
session_start();
include_once "conn.php";
include_once "config.php";
include_once 'membership-card.php';

function clean($value) {
    return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
}

$success = false;
$error = "Payment Failed";

if(isset($_SESSION['reference_number'], $_POST['key'], $_POST['txnid'], $_POST['amount'], $_POST['productinfo'], $_POST['firstname'], $_POST['email'], $_POST['hash']) && $_SESSION['reference_number'] == $_POST['udf1']) {
    $merchant_key = PAYU_KEY;
    $merchant_salt = PAYU_SALT;
    
    $txnid_to_verify = $_POST['txnid'];

    $url = PAYU_VERIFY_URL;
    // 2. GENERATE HASH
    // Formula: sha512(key|command|var1|salt)
    $command = 'verify_payment';
    $hash_string = $merchant_key . '|' . $command . '|' . $txnid_to_verify . '|' . $merchant_salt;
    $hash = strtolower(hash('sha512', $hash_string));
    
    // 3. PREPARE POST DATA
    $post_data = [
    'key'     => $merchant_key,
    'command' => $command,
    'var1'    => $txnid_to_verify,
    'hash'    => $hash
    ];
    
    // 4. EXECUTE CURL REQUEST
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
       error_log('CURL Error: ' . curl_error($ch));
       exit('Error in verifying payment. Please try again later.');
    }
    
    // 5. PROCESS RESPONSE
    $result = json_decode($response, true);
    
    if (isset($result['status']) && $result['status'] == 1) {
        $transaction_details = $result['transaction_details'][$txnid_to_verify];
        
        if ($transaction_details['status'] == 'success') {
            $success = true;
            $error = "Payment Successful";
        }
    } else {
        $success = false;
        $error = "Payment Verification Failed";
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


    function get_new_membership_id($conn) {
        $month = date('m'); 
        $year  = date('y'); 
        $base_prefix = "ACCS{$month}{$year}M"; 
        $prefix_length = 9;

        $sql = "SELECT membership_id 
                FROM membership_requests 
                WHERE membership_id LIKE ? 
                ORDER BY membership_id DESC 
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $search_term = $base_prefix . '%';
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $next_sequence = 1;

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_id = $row['membership_id'];
            $last_number = (int) substr($last_id, $prefix_length);
            $next_sequence = $last_number + 1;
        }

        $stmt->close();

        $formatted_sequence = str_pad($next_sequence, 3, '0', STR_PAD_LEFT);

        return $base_prefix . $formatted_sequence;
    }

    function update_membership_request(mysqli $conn, $reference_number, $payment_id, $order_id, $payment_status, $payment_date, $payment_response, $permanent_file_paths, $membership_id = null): bool
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
            employment_proof = ?,
            membership_id = ?,
            status = 'Approved'
        WHERE reference_number = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "ssssssssssss",
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
            $membership_id,
            $reference_number
        );

        return $stmt->execute();
    }

    $membership_id = get_new_membership_id($conn);
    update_membership_request($conn, $reference_number, $_POST['txnid'], $_POST['bank_ref_num'], 'Paid', date('Y-m-d H:i:s'), json_encode($_POST), $permanent_file_paths, $membership_id);
    
    $form_data['membership_id'] = $membership_id;
    $form_data['payment_status'] = 'Paid';
    $form_data['status'] = 'Approved';

    function send_admin_notification($form_data, $permanent_file_paths, $reference_number, $payment_id) {
        $to       = 'admin@iaccs.org.in';
        $to       = 'sk8327656239@gmail.com';
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

    send_admin_notification($form_data, $permanent_file_paths, $reference_number, $_POST['txnid']);
    send_verification_complete_email($form_data);
    
    unset($_SESSION['reference_number']);

    header("Location: thankyou.php?ref=" . $reference_number . "&payment_id=" . $_POST['txnid']);
    exit();
} else {
    header("Location: fail.php?ref=" . ($_SESSION['reference_number'] ?? ''));
    exit();
}