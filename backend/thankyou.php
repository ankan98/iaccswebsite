<?php
include_once "config.php";
include_once "conn.php";

$reference = $_GET['ref'] ?? '';
$reference = trim($reference);

$stmt = null;
$data = null;
if ($reference !== '') {
    $stmt = $conn->prepare("SELECT name, membership_id, reference_number, payment_status, status FROM membership_requests WHERE reference_number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $reference);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res ? $res->fetch_assoc() : null;
    }
}

$memberId = $data['membership_id'] ?? 'Pending allocation';
$refNo    = $data['reference_number'] ?? $reference ?: 'Not found';
$payStatus = ucfirst(strtolower($data['payment_status'] ?? 'Pending'));
$appStatus = ucfirst(strtolower($data['status'] ?? 'Pending'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - ACCS Membership</title>
    <style>
        :root {
            --primary: #0f172a;
            --accent: #0ea5e9;
            --muted: #475569;
        }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }
        header, footer {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
        }
        .wrap {
            max-width: 920px;
            margin: 24px auto;
            padding: 0 20px 60px;
        }
        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15,23,42,0.08);
            padding: 32px;
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 600;
            background: #ecfeff;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .pill {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 14px;
            background: #f8fafc;
        }
        .pill span:first-child {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .pill strong {
            font-size: 18px;
            color: #0f172a;
        }
        .notice {
            margin-top: 24px;
            text-align: left;
            line-height: 1.6;
            color: var(--muted);
        }
        .cta {
            margin-top: 28px;
            display: inline-flex;
            padding: 12px 22px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .cta.secondary {
            background: white;
            color: var(--primary);
            border: 1px solid #cbd5e1;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <header>
        <div style="max-width: 1100px; margin: 0 auto; display:flex; align-items:center; justify-content:space-between;">
            <div style="font-weight:700; font-size:18px; letter-spacing:0.01em;">Association for Critical Care Sciences (ACCS)</div>
            <div style="color:#64748b; font-size:14px;">Membership Desk</div>
        </div>
    </header>

    <div class="wrap">
        <div class="card">
            <div class="badge">Thank you! Your submission was received.</div>
            <h1 style="margin: 18px 0 10px; font-size: 28px;">We’re reviewing your membership</h1>
            <p style="margin: 0; color: var(--muted); max-width: 640px; margin-inline:auto;">
                Our team will verify your payment details. Once approved, your e-certificate will be emailed to you.
                You can also track your certificate status and download it when approved.
            </p>

            <div class="grid">
                <div class="pill">
                    <span>Membership ID</span>
                    <strong><?php echo htmlspecialchars($memberId); ?></strong>
                </div>
                <div class="pill">
                    <span>Reference Number</span>
                    <strong><?php echo htmlspecialchars($refNo); ?></strong>
                </div>
                <div class="pill">
                    <span>Payment Status</span>
                    <strong><?php echo htmlspecialchars($payStatus); ?></strong>
                </div>
                <div class="pill">
                    <span>Application Status</span>
                    <strong><?php echo htmlspecialchars($appStatus); ?></strong>
                </div>
            </div>

            <div class="notice">
                <ul style="margin: 12px 0 0 18px; padding:0; list-style:disc;">
                    <li>You will receive an email with your e-certificate after approval.</li>
                    <li>If payment is pending, please complete it using the QR shared with you.</li>
                    <li>Need help? Write to <a href="mailto:admin@iaccs.org.in">admin@iaccs.org.in</a>.</li>
                </ul>
            </div>

            <div style="margin-top: 28px;">
                <a class="cta" href="<?php echo BASE_URL; ?>/membership_status_check.php?ref=<?php echo urlencode($refNo); ?>">Check Status</a>
                <a class="cta secondary" href="<?php echo BASE_URL; ?>/">Back to Home</a>
            </div>
        </div>
    </div>

    <footer>
        <div style="max-width: 1100px; margin: 0 auto; color:#94a3b8; font-size:13px;">
            © <?php echo date('Y'); ?> ACCS. All rights reserved.
        </div>
    </footer>
</body>
</html>
