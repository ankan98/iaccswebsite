<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$conn = require_once __DIR__ . '/conn.php';

$result = $conn->query("SELECT slug FROM notices WHERE (type = 'page' OR type = '' OR type IS NULL) AND status = 'active'");
$slugs = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['slug'])) {
            $slugs[] = $row['slug'];
        }
    }
}

echo json_encode($slugs);
