<?php
require_once('vendor/autoload.php');

function ensure_tcpdf_cache_dir()
{
    // Make TCPDF temporary/cache writes reliable on Windows/WAMP.
    $dir = __DIR__ . '/temp_uploads/tcpdf-cache/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!defined('K_PATH_CACHE')) {
        define('K_PATH_CACHE', $dir);
    }
}

// If not using Composer, adjust path (e.g., require_once('tcpdf/tcpdf.php');)

// 2. DATA SETUP (Replace with your DB variables)
function generate_membership_card($data, $file_name = 'Membership_Card.pdf', $return_type = 'E')
{
    ensure_tcpdf_cache_dir();
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
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->SetXY(30, 12); // Offset to right of logo
    $pdf->Cell(0, 0, 'The Association for Critical Care Sciences', 0, 1, 'L');

    // Header IP (Gray)
    $pdf->SetFont('dejavusans', '', 9);
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
    $pdf->SetFont('dejavusans', '', 14);
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
    $pdf->SetFont('dejavusans', 'B', 9);
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
            <td class="value paid" style="color: #28a745; font-weight: bold;"><span style="font-family: dejavusans;">ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã¢â‚¬Â¹Ãƒâ€¦Ã¢â‚¬Å“ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ </span>' . 'PAID' . '</td>
        </tr>
        <tr>
            <td class="label no-border">Membership Expiry Date</td>
            <td class="value no-border paid">' . (date('M d, Y', strtotime('+1 year'))) . '</td>
        </tr>
    </table>';
    $membership_id = (string)($data['membership_id'] ?? '');
    $reference_number = (string)($data['reference_number'] ?? '');
    $display_membership_id = $membership_id !== ''
        ? $membership_id
        : ($reference_number !== '' ? $reference_number : 'Pending');

    $plan_raw = strtolower(trim((string)($data['membership_plan'] ?? '')));
    if ($plan_raw === 'student' || $plan_raw === 'basic') {
        $membership_category = 'Student';
    } elseif ($plan_raw === 'professional' || $plan_raw === 'premium') {
        $membership_category = 'Professional';
    } else {
        $membership_category = $plan_raw !== '' ? ucwords($plan_raw) : '';
    }

    $status_raw = trim((string)($data['status'] ?? 'Pending'));
    $status_display = strtolower($status_raw) === 'approved' ? 'Active & Verified' : $status_raw;

    $payment_status_raw = trim((string)($data['payment_status'] ?? ''));
    $payment_status = $payment_status_raw !== ''
        ? $payment_status_raw
        : (!empty($data['paid_transaction_id_number']) ? 'Paid' : 'Pending');
    $payment_class = strtolower(trim($payment_status)) === 'paid' ? 'paid' : 'pending';

    $valid_until = !empty($data['created_at'])
        ? date('M d, Y', strtotime($data['created_at'] . ' +1 year'))
        : date('M d, Y', strtotime('+1 year'));

    $safe_name = htmlspecialchars((string)($data['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safe_membership_id = htmlspecialchars($display_membership_id, ENT_QUOTES, 'UTF-8');
    $safe_membership_category = htmlspecialchars($membership_category, ENT_QUOTES, 'UTF-8');
    $safe_membership_status = htmlspecialchars($status_display, ENT_QUOTES, 'UTF-8');
    $safe_qualification = htmlspecialchars((string)($data['education'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safe_email = htmlspecialchars((string)($data['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safe_mobile = htmlspecialchars((string)($data['mobile'] ?? ''), ENT_QUOTES, 'UTF-8');
    $safe_membership_type = htmlspecialchars(($membership_category !== '' ? $membership_category : 'Membership') . ' membership', ENT_QUOTES, 'UTF-8');
    $safe_payment_status = htmlspecialchars($payment_status, ENT_QUOTES, 'UTF-8');
    $safe_valid_until = htmlspecialchars($valid_until, ENT_QUOTES, 'UTF-8');

    $html_table = <<<HTML
<style>
  table { width: 100%; border-collapse: collapse; }
  td { padding: 8px 5px; border-bottom: 1px solid #f0f0f0; font-family: 'dejavusans'; font-size: 10pt; }
  .label { color: #555555; font-weight: bold; width: 44%; text-align: left; }
  .value { color: #000000; text-align: right; width: 56%; font-weight: bold; }
  .paid { color: #15803d; }
  .pending { color: #b45309; }
  .no-border td { border-bottom: none; }
</style>
<table cellpadding="5">
  <tr>
    <td class="label">Name</td>
    <td class="value">{$safe_name}</td>
  </tr>
  <tr>
    <td class="label">Membership ID</td>
    <td class="value">{$safe_membership_id}</td>
  </tr>
  <tr>
    <td class="label">Membership Category</td>
    <td class="value">{$safe_membership_category}</td>
  </tr>
  <tr>
    <td class="label">Membership Status</td>
    <td class="value">{$safe_membership_status}</td>
  </tr>
  <tr>
    <td class="label">Qualification</td>
    <td class="value">{$safe_qualification}</td>
  </tr>
  <tr>
    <td class="label">Email</td>
    <td class="value">{$safe_email}</td>
  </tr>
  <tr>
    <td class="label">Contact Number</td>
    <td class="value">{$safe_mobile}</td>
  </tr>
  <tr>
    <td class="label">Membership Type</td>
    <td class="value">{$safe_membership_type}</td>
  </tr>
  <tr>
    <td class="label">Payment Status</td>
    <td class="value {$payment_class}">{$safe_payment_status}</td>
  </tr>
  <tr class="no-border">
    <td class="label">Valid Until</td>
    <td class="value paid">{$safe_valid_until}</td>
  </tr>
</table>
HTML;


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
    $pdf->SetFont('dejavusans', '', 9);
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
        'font' => 'dejavusans',
        'fontsize' => 14,     // Text Size
        'stretchtext' => 0
    );

    // Draw Barcode 
    // We use the full $box_w and align='C' to center it automatically
    $pdf->write1DBarcode($display_membership_id, 'C128', $box_x, $box_y + 12, $box_w, 35, 0.65, $style, 'N');
    $style = array(
        'border' => 0,
        'padding' => 2,
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => false
    );

    // Generate QR Code using Member ID
    $pdf->write2DBarcode($memberId, 'QRCODE,L', 15, 140, 30, 30, $style, 'N');

    // 4. OUTPUT
    return $pdf->Output($file_name, $return_type);
}

function generate_verification_slip($data, $file_name = 'E_Verification_Slip.pdf', $return_type = 'E')
{
    ensure_tcpdf_cache_dir();
    $template_path = __DIR__ . '/templates/e-verification-slip.html';
    if (!file_exists($template_path)) {
        return false;
    }

    $membership_id = $data['membership_id'] ?? '';
    $reference_number = $data['reference_number'] ?? '';
    $display_membership_id = $membership_id !== '' ? $membership_id : ($reference_number !== '' ? $reference_number : 'Pending');
    $plan_raw = strtolower(trim((string)($data['membership_plan'] ?? '')));
    if ($plan_raw === 'student' || $plan_raw === 'basic') {
        $membership_category = 'Student';
    } elseif ($plan_raw === 'professional' || $plan_raw === 'premium') {
        $membership_category = 'Professional';
    } else {
        $membership_category = $plan_raw !== '' ? ucwords($plan_raw) : '';
    }
    $membership_type = $membership_category;
    $status = $data['status'] ?? 'Pending';
    $payment_status = $data['payment_status'] ?? 'Pending';
    $valid_until = !empty($data['created_at'])
        ? date('M d, Y', strtotime($data['created_at'] . ' +1 year'))
        : date('M d, Y', strtotime('+1 year'));
    $html = file_get_contents($template_path);
    $logo_path = __DIR__ . '/iaccslogo.png';
    $logo_src = '';
    if (file_exists($logo_path)) {
        $logo_src = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
    } else {
        // 1x1 transparent PNG
        $logo_src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIW2P8z/C/HwAGgwJ/lU7H9QAAAABJRU5ErkJggg==';
    }
    $replacements = array(
        '{{NAME}}' => htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{{LOGO_SRC}}' => $logo_src,
        '{{MEMBERSHIP_ID}}' => htmlspecialchars($display_membership_id, ENT_QUOTES, 'UTF-8'),
        '{{BARCODE_VALUE}}' => htmlspecialchars($display_membership_id, ENT_QUOTES, 'UTF-8'),
        '{{SIGNATORY_NAME}}' => 'Bapan Sarkar',
        '{{MEMBERSHIP_CATEGORY}}' => htmlspecialchars(ucwords($membership_category), ENT_QUOTES, 'UTF-8'),
        '{{MEMBERSHIP_STATUS}}' => htmlspecialchars($status === 'Approved' ? 'Active & Verified' : $status, ENT_QUOTES, 'UTF-8'),
        '{{QUALIFICATION}}' => htmlspecialchars($data['education'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{{EMAIL}}' => htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{{CONTACT_NUMBER}}' => htmlspecialchars($data['mobile'] ?? '', ENT_QUOTES, 'UTF-8'),
        '{{MEMBERSHIP_TYPE}}' => htmlspecialchars(ucwords($membership_type) . ' membership', ENT_QUOTES, 'UTF-8'),
        '{{PAYMENT_STATUS}}' => htmlspecialchars($payment_status, ENT_QUOTES, 'UTF-8'),
        '{{VALID_UNTIL}}' => htmlspecialchars($valid_until, ENT_QUOTES, 'UTF-8')
    );
    $html = str_replace(array_keys($replacements), array_values($replacements), $html);
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(6, 6, 6);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->AddPage();
    $pdf->setFontSubsetting(true);
    $pdf->SetFont('freeserif', '', 12, '', true);
    $pdf->writeHTML($html, true, false, true, false, '');

    // Draw QR + signature + footer on page 1 (avoid HTML page splitting)
    $footer_html = '<div style="font-size:8.0pt; line-height:1.4; color:#111827;">'
        . 'This E-Verification Slip is issued solely for internal membership identification purposes by The Association for Critical Care Sciences, an independent non-statutory professional body. It does not constitute, imply, or confer any statutory recognition, regulatory approval, governmental affiliation, accreditation, professional licensure, academic qualification, certification, or legal authority of any nature whatsoever.'
        . '<br/>'
        . 'The Association is not a government body, regulatory authority, licensing agency, statutory council, or accreditation board. This document shall not be used, presented, or relied upon as evidence of professional competence, clinical authorization, employment eligibility, academic equivalence, or legal entitlement.'
        . '<br/>'
        . 'Any misuse, misrepresentation, alteration, or unauthorized reliance upon this document is strictly prohibited. The Association assumes no liability for any third-party interpretation or use of this E-Verification Slip beyond its intended internal identification purpose.'
        . '</div>';

    $saved_page = $pdf->getPage();
    $pdf->setPage(1);

    // QR box + QR
    $qrBoxX = 14;
    $qrBoxY = 205;
    $qrBoxSize = 30;
    $pdf->Rect($qrBoxX, $qrBoxY, $qrBoxSize, $qrBoxSize);
    $qrStyle = array('border' => 0, 'padding' => 0, 'fgcolor' => array(0, 0, 0), 'bgcolor' => false);
    $pdf->write2DBarcode($display_membership_id, 'QRCODE,L', $qrBoxX + 1, $qrBoxY + 1, $qrBoxSize - 2, $qrBoxSize - 2, $qrStyle, 'N');

    // Signature block
    $sigPath = __DIR__ . '/assets/images/signature.png';
    if (file_exists($sigPath)) {
        $pdf->Image($sigPath, 120, 205, 65, 0, 'PNG');
    }
    $pdf->Line(115, 226, 195, 226);
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->SetXY(115, 228);
    $pdf->Cell(80, 5, 'Authorized Signatory (President)', 0, 1, 'C');
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->SetX(115);
    $pdf->Cell(80, 5, 'The Association for Critical Care Sciences', 0, 1, 'C');

    // Footer line + footer text
    $pdf->Line(6, 246, 200, 246);
    $pdf->SetY(-48  );
    $pdf->SetFont('dejavusans', '', 7);
    $pdf->writeHTML($footer_html, true, false, true, false, '');

    $pdf->setPage($saved_page);

    // Remove any extra pages created by HTML layout
    while ($pdf->getNumPages() > 1) {
        $pdf->deletePage(2);
    }

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
