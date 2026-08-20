<?php
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
require 'conn.php';
require 'mailer.php';
require_once('membership-card.php');
$table = 'membership_requests';

function send_verification_complete_email_from_admin($user)
{
    $subject = "Welcome to ACCS! Your Membership is Approved - {$user['name']}";

    // --- Prepare the HTML Body ---
    $htmlBody = "Dear {$user['name']},<br><br>
    We are delighted to inform you that your document verification and payment process have been <b>successfully completed</b>. It is our distinct pleasure to officially welcome you as a member of the Association for Critical Care Sciences (ACCS).<br><br>
    Your membership has been approved, and your unique Membership ID is: <b>{$user['membership_id']}</b>.<br><br>
    <b>Your Digital Membership Card:</b> Please find your official Digital Membership Card attached to this email as a PDF. We recommend downloading and saving this document for your records, as it serves as proof of your affiliation with the ACCS community.<br><br>
    We look forward to your active participation and contribution to the field of critical care sciences.<br><br>
    If you have any questions regarding your membership or require further assistance, please do not hesitate to contact us at admin@iaccs.org.in.<br><br>
    Regards,<br>
    <b>Association for Critical Care Sciences (ACCS)</b>";

    $attachments = [];

    // --- Handle PDF Generation ---
    if ($user['status'] === 'Approved') {
        // 1. Get the absolute path to the current directory
        $currentDir = __DIR__; 
        
        // 2. Define the subfolder and filename
        $fileName = 'Membership_Card_' . $user['membership_id'] . '.pdf';
        $tempPdfPath = $currentDir . '/temp/' . $fileName;

        // 3. Ensure the folder exists (failsafe)
        if (!is_dir($currentDir . '/temp/')) {
            mkdir($currentDir . '/temp/', 0755, true);
        }

        // 4. Generate and save the file
        // IMPORTANT: Use 'F' to save to the local file system
        generate_verification_slip($user, $tempPdfPath, 'F'); 
        
        if (file_exists($tempPdfPath)) {
            $attachments[] = $tempPdfPath;
        }
    }

    // --- Send via SMTP ---
    // Parameters: $to, $subject, $msg, $bcc (empty array), $attachments
    $result = smtp_mailer($user['email'], $subject, $htmlBody, [], $attachments);

    // --- Cleanup ---
    // Delete the temporary file after sending to keep the server clean
    if (!empty($attachments)) {
        foreach ($attachments as $file) {
            if (file_exists($file)) { unlink($file); }
        }
    }

    return $result;
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $memberId = $data['id'] ?? null;

    if ($memberId) {
        try {
            // 1. Update status to 'Approved'
            $stmt = $pdo->prepare("UPDATE $table SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$memberId]);

            // 2. Fetch member data
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
            $stmt->execute([$memberId]);
            $memberData = $stmt->fetch();

            if ($memberData && send_verification_complete_email_from_admin($memberData)) {
                $response = ['success' => true, 'message' => 'Membership approved and email sent successfully.'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to send email.'];
            }
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'No member ID provided.';
    }
}

echo json_encode($response);
