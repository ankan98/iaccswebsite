<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$conn = require_once __DIR__ . '/conn.php';

$result = $conn->query("SELECT * FROM cms_settings ORDER BY id ASC LIMIT 1");
$db = $result ? $result->fetch_assoc() : null;

if (!$db) {
    echo json_encode([
        'site_logo'        => '',
        'site_title'       => 'Association for Critical Care Sciences',
        'site_title_hindi' => 'दि एसोसिएशन फ़ॉर क्रिटिकल केयर साइंसेज़',
        'address'          => '',
        'footer_text'      => '',
        'last_updated_on'  => '',
        'social_links'     => []
    ]);
    exit;
}

$social_links = json_decode($db['social_links'] ?? '{}', true);
if (!is_array($social_links)) {
    $social_links = [];
}

// Sort social links by order
uasort($social_links, function($a, $b) {
    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
});

echo json_encode([
    'site_logo'        => $db['site_logo']        ?? '',
    'site_title'       => $db['site_title']        ?? 'Association for Critical Care Sciences',
    'site_title_hindi' => $db['site_title_hindi']  ?? 'दि एसोसिएशन फ़ॉर क्रिटिकल केयर साइंसेज़',
    'address'          => $db['address']           ?? '',
    'footer_text'      => $db['footer_text']       ?? '',
    'last_updated_on'  => $db['last_updated_on']   ?? '',
    'social_links'     => $social_links
]);
