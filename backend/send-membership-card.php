<?php
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once('vendor/autoload.php');
// If not using Composer, adjust path (e.g., require_once('tcpdf/tcpdf.php');)

// 2. DATA SETUP (Replace with your DB variables)
function generate_membership_card($data, $file_name = 'Membership_Card.pdf', $return_type = 'E')
{
    // 3. INITIALIZE PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Disable default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->AddPage();

    // --- HEADER SECTION ---

    // Logo Logic (Checks if logo.png exists to prevent errors)
    $logo_path = 'iaccslogo.png';
    if (file_exists($logo_path)) {
        // x=10, y=10, w=15
        $pdf->Image($logo_path, 10, 10, 15, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }

    // Header Title
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetXY(30, 12); // Offset to right of logo
    $pdf->Cell(0, 0, 'The Association for Critical Care Sciences', 0, 1, 'L');

    // Header IP (Gray)
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY(30, 19);
    $pdf->Cell(0, 0, 'IP: ' . $_SERVER['REMOTE_ADDR'], 0, 0, 'L');

    // Header Date (Right Aligned)
    $pdf->SetXY(150, 19);
    $pdf->Cell(0, 0, 'Date: ' . date('M d, Y'), 0, 1, 'R');

    // Horizontal Black Line
    $pdf->Ln(8);
    $pdf->SetDrawColor(0, 0, 0); // Black
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());


    // --- BODY CONTENT ---

    // "Verification Complete" Title
    $pdf->Ln(10);
    $pdf->SetTextColor(0, 0, 0); // Reset to Black
    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 0, 'Verification Complete', 0, 1, 'C');
    $pdf->Ln(5);

    // --- MAIN CARD CONTAINER ---
    $card_x = 10;
    $card_y = $pdf->GetY();
    $card_w = 190;
    $card_h = 160;

    // Draw Card Border (Rounded)
    $pdf->SetDrawColor(220, 220, 220); // Light Grey Border
    $pdf->SetLineWidth(0.3);
    // FIXED: Added '1111' as 6th param to define corners, 'D' is 7th param
    $pdf->RoundedRect($card_x, $card_y, $card_w, $card_h, 5, '1111', 'D');

    // "Verified" Label inside card
    $pdf->SetXY($card_x + 5, $card_y + 5);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(150, 150, 150); // Light Grey text
    $pdf->Cell(0, 0, 'Verified', 0, 1, 'L');


    // --- DATA TABLE ---
    $pdf->SetXY($card_x + 5, $card_y + 15);

    // CSS Styling for HTML Table
    $html_table = '
    <style>
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        td { 
            padding: 8px 5px; 
            border-bottom: 1px solid #f0f0f0; 
            font-family: helvetica; 
            font-size: 10pt;
        }
        /* LEFT ALIGN LABELS */
        .label { 
            color: #555555; 
            font-weight: bold; 
            width: 40%; 
            text-align: left; 
        }
        /* RIGHT ALIGN VALUES */
        .value { 
            color: #000000; 
            text-align: right; 
            width: 60%; 
            font-weight: bold; 
        }
        .paid { color: #28a745; font-weight: bold; } 
        .no-border { border-bottom: none; }
    </style>
    <table cellpadding="5">
        <tr>
            <td class="label">Full Name</td>
            <td class="value">' . $data['name'] . '</td>
        </tr>
        <tr>
            <td class="label">Membership ID</td>
            <td class="value">' . $data['membership_id'] . '</td>
        </tr>
        <tr>
            <td class="label">Qualification</td>
            <td class="value">' . $data['education'] . '</td>
        </tr>
        <tr>
            <td class="label">Phone</td>
            <td class="value">' . $data['mobile'] . '</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td class="value">' . $data['email'] . '</td>
        </tr>
        <tr>
            <td class="label">Membership Type</td>
            <td class="value">' . ucwords($data['membership_plan']) . ' membership</td>
        </tr>
        <tr>
            <td class="label">Payment Status</td>
            <td class="value paid" style="color: #28a745; font-weight: bold;"><span style="font-family: dejavusans;">☑ </span>' . 'PAID' . '</td>
        </tr>
        <tr>
            <td class="label no-border">Membership Expiry Date</td>
            <td class="value no-border paid">' . (date('M d, Y', strtotime('+1 year'))) . '</td>
        </tr>
    </table>';

    // Print Table
    $pdf->writeHTMLCell($card_w - 10, 0, $card_x + 5, $card_y + 15, $html_table, 0, 1, 0, true, 'L', true);


    // --- BARCODE SECTION (Blue Dashed Box) ---

    // Define Box Area
    $box_x = $card_x + 5;
    $box_y = $pdf->GetY() + 5; // Start below table
    $box_w = $card_w - 10;
    $box_h = 60;

    // Set Blue Dashed Line Style
    $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => '5,2', 'color' => array(66, 133, 244)));

    // Draw Box (FIXED: Added '1111' before 'D')
    $pdf->RoundedRect($box_x, $box_y, $box_w, $box_h, 5, '1111', 'D');

    // "Membership Barcode" Label with Pin
    $pdf->SetXY($box_x, $box_y + 3);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(80, 80, 80);
    // Using HTML for the red pin icon
    $pdf->writeHTMLCell($box_w, 0, $box_x, $box_y + 3, 'Membership Barcode', 0, 1, 0, true, 'C', true);

    // Generate Barcode
    $pdf->SetTextColor(0, 0, 0); // Reset text color to black

    // Barcode Style Options
    $style = array(
        'position' => '',
        'align' => 'C',       // CENTER ALIGN
        'stretch' => false,
        'fitwidth' => false,  // Do not stretch bars
        'cellfitalign' => '',
        'border' => false,
        'hpadding' => 'auto',
        'vpadding' => 'auto',
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => false,
        'text' => true,       // Show ID text
        'font' => 'helvetica',
        'fontsize' => 14,     // Text Size
        'stretchtext' => 0
    );

    // Draw Barcode 
    // We use the full $box_w and align='C' to center it automatically
    $pdf->write1DBarcode($data['membership_id'], 'C128', $box_x, $box_y + 12, $box_w, 35, 0.65, $style, 'N');


    // 4. OUTPUT
    return $pdf->Output($file_name, $return_type);
}

function send_verification_complete_email($user)
{

    $subject = "Welcome to ACCS! Your Membership is Approved - {$user['name']}";

    $boundary = md5(time());

    $headers  = "From: IACCS <noreply@iaccs.org.in>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/related; boundary=\"$boundary\"\r\n";

    /* ===============================
       Email HTML Body
    ================================ */
    $htmlBody = "Dear {$user['name']},

    We are delighted to inform you that your document verification and payment process have been <b>successfully completed</b>. It is our distinct pleasure to officially welcome you as a member of the Association for Critical Care Sciences (ACCS).

    Your membership has been approved, and your unique Membership ID is: {$user['membership_id']}.

    <b>Your Digital Membership Card:</b> Please find your official Digital Membership Card attached to this email as a PDF. We recommend downloading and saving this document for your records, as it serves as proof of your affiliation with the ACCS community.

    We look forward to your active participation and contribution to the field of critical care sciences.

    If you have any questions regarding your membership or require further assistance, please do not hesitate to contact us at admin@iaccs.org.in.

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
       Attach Membership Card PDF
    ================================ */
    if ($user['status'] === 'Approved') {
        $pdfContent = generate_membership_card($user, 'Membership_Card.pdf', 'E');

        $message .= "--$boundary\r\n";
        $message .= $pdfContent;
    }

    $message .= "--$boundary--";

    return mail($user['email'], $subject, $message, $headers);
}

// --- Database Configuration ---
define('ENVIRONMENT', 'production');
if (ENVIRONMENT === 'development') {
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs';
    $db_user = 'root';
    $db_pass = '';
} else {
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs';
    $db_user = 'agcinfos_iaccs';
    $db_pass = 'iaccs#1234X';
}
$table = 'membership_requests';

// --- Database Connection ---
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
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

            if ($memberData && send_verification_complete_email($memberData)) {
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
