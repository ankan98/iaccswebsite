<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$conn = require_once __DIR__ . '/conn.php';

$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1; // Default Header Menu = 1

$stmt = $conn->prepare("SELECT id, menu_id, title, url, icon, parent_id, sort_order FROM cms_menu_items WHERE menu_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->bind_param("i", $menu_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Build nested parent-child tree structure
$menu_tree = [];
$items_by_id = [];

foreach ($items as $item) {
    $item['children'] = [];
    $items_by_id[$item['id']] = $item;
}

foreach ($items_by_id as $id => &$item) {
    if (!empty($item['parent_id']) && isset($items_by_id[$item['parent_id']])) {
        $items_by_id[$item['parent_id']]['children'][] = &$item;
    } else {
        $menu_tree[] = &$item;
    }
}

echo json_encode([
    'menu_id' => $menu_id,
    'items'   => $menu_tree
]);
