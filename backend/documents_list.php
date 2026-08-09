<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function json_error(int $status, string $message): void {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ]);
    exit;
}

try {
    $type = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : '';
    $typeMap = [
        'announcements' => 'announcement',
        'notices' => 'notice',
        'reports' => 'report',
    ];
    if (!isset($typeMap[$type])) {
        json_error(400, 'Invalid type');
    }
    $dbType = $typeMap[$type];

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    if ($limit < 0) $limit = 0;
    if ($offset < 0) $offset = 0;

    /** @var mysqli $conn */
    $conn = include_once __DIR__ . '/conn.php';

    $status = 'active';

    $total = 0;
    $stmtTotal = $conn->prepare("SELECT COUNT(*) AS total FROM notices WHERE type = ? AND status = ?");
    if (!$stmtTotal) {
        throw new Exception('Failed to prepare total query');
    }
    $stmtTotal->bind_param('ss', $dbType, $status);
    if (!$stmtTotal->execute()) {
        throw new Exception('Failed to execute total query');
    }
    $resTotal = $stmtTotal->get_result();
    $rowTotal = $resTotal ? ($resTotal->fetch_assoc() ?: []) : [];
    $total = (int)($rowTotal['total'] ?? 0);
    $stmtTotal->close();

    if ($offset >= $total) {
        echo json_encode([
            'success' => true,
            'type' => $type,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'items' => [],
        ]);
        exit;
    }

    $sql = "SELECT id, title, description, file_path, created_at, updated_at
            FROM notices
            WHERE type = ? AND status = ?
            ORDER BY updated_at DESC, created_at DESC, id DESC";

    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare items query');
        }
        $stmt->bind_param('ssii', $dbType, $status, $limit, $offset);
    } else if ($offset > 0) {
        $sql .= " LIMIT 18446744073709551615 OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare items query');
        }
        $stmt->bind_param('ssi', $dbType, $status, $offset);
    } else {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare items query');
        }
        $stmt->bind_param('ss', $dbType, $status);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to execute items query');
    }
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $items = array_map(function ($row) {
        $filePath = trim((string)($row['file_path'] ?? ''));
        $url = null;
        if ($filePath !== '') {
            if (preg_match('/^https?:\\/\\//i', $filePath)) {
                $url = $filePath;
            } else {
                $url = '/' . ltrim(str_replace('\\', '/', $filePath), '/');
            }
        }

        $createdAtRaw = (string)($row['created_at'] ?? '');
        $updatedAtRaw = (string)($row['updated_at'] ?? '');
        $createdAt = $createdAtRaw !== '' ? date(DATE_ATOM, strtotime($createdAtRaw)) : null;
        $updatedAt = $updatedAtRaw !== '' ? date(DATE_ATOM, strtotime($updatedAtRaw)) : $createdAt;

        return [
            'title' => (string)($row['title'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'fileName' => $filePath !== '' ? basename($filePath) : null,
            'url' => $url,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt,
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'type' => $type,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    json_error(500, 'Failed to fetch documents');
}
