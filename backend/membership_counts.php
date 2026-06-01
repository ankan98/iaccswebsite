<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

try {
    /** @var mysqli $conn */
    $conn = include_once __DIR__ . '/conn.php';

    $sql = "
        SELECT
            SUM(membership_plan = 'student') AS student_count,
            SUM(membership_plan = 'premium') AS premium_count
        FROM membership_requests
    ";

    $res = $conn->query($sql);
    if (!$res) {
        throw new Exception($conn->error);
    }
    $row = $res->fetch_assoc() ?: [];

    echo json_encode([
        'success' => true,
        'counts' => [
            'student' => (int)($row['student_count'] ?? 0),
            'premium' => (int)($row['premium_count'] ?? 0),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch counts',
    ]);
}

