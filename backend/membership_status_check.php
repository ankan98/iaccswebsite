<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $conn = require_once __DIR__ . '/conn.php';
    if (!$conn || !($conn instanceof mysqli)) {
        throw new Exception("Database connection failed.");
    }

    function clean($value) {
        return trim(htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'));
    }

    // Accept 'id', 'membership_id', or 'ref'
    $raw_id = clean($_GET['id'] ?? ($_GET['membership_id'] ?? ($_GET['ref'] ?? '')));
    $dob = clean($_GET['dob'] ?? '');

    if ($raw_id === '' || $dob === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter your membership ID/ Reference ID and date of birth.',
        ]);
        exit;
    }

    // Check if $raw_id contains any alphabetic characters (A-Z or a-z)
    $has_alphabet = preg_match('/[a-zA-Z]/', $raw_id);

    if ($has_alphabet) {
        // If it contains letters (e.g. ACCSIN2026WBA001), store & search in membership_id
        $search_column = 'membership_id';
        $search_value = $raw_id;
    } else {
        // Otherwise (e.g. numeric reference number like 0308202601112919), store & search in reference_number
        $search_column = 'reference_number';
        $search_value = $raw_id;
    }

    // Prepare date format variations for flexible matching
    $dob_ymd = $dob;
    $dob_dmy = $dob;
    $timestamp = strtotime($dob);
    if ($timestamp !== false) {
        $dob_ymd = date('Y-m-d', $timestamp);
        $dob_dmy = date('d-m-Y', $timestamp);
    }

    // Query database selecting row where search_column matches search_value AND dob matches
    $query = "SELECT id, name, reference_number, membership_id, status, payment_status, email, mobile, dob, created_at, updated_at 
              FROM membership_requests 
              WHERE $search_column = ? 
                AND (dob = ? OR dob = ? OR dob = ? OR DATE(dob) = ? OR DATE_FORMAT(dob, '%Y-%m-%d') = ?) 
              ORDER BY id DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $search_value, $dob, $dob_ymd, $dob_dmy, $dob_ymd, $dob_ymd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        echo json_encode([
            'success' => false,
            'message' => 'No membership record found matching the provided Membership ID/ Reference ID and Date of Birth.',
        ]);
        exit;
    }

    // Build base_url dynamically using current HTTP_HOST domain
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    
    if (!empty($host)) {
        $base_url = $protocol . $host . '/';
    } else if (defined('BASE_URL') && !empty(BASE_URL)) {
        $base_url = rtrim(BASE_URL, '/') . '/';
    } else {
        $base_url = '/';
    }

    $download_url = '';
    $normalized_status = strtolower(trim($row['status'] ?? ''));
    $normalized_payment_status = strtolower(trim($row['payment_status'] ?? ''));
    if ($normalized_status === 'approved' && $normalized_payment_status === 'paid') {
        $download_url = $base_url . 'membership-card-download.php?ref=' . urlencode($row['reference_number']);
    }

    echo json_encode([
        'success' => true,
        'data' => $row,
        'download_url' => $download_url,
    ]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
    exit;
}
