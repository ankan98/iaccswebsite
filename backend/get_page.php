<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$conn = require_once __DIR__ . '/conn.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Slug parameter is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT title, heading, subheading, btn_text, btn_link, content, custom_css, home_json, about_json, contact_json FROM cms_pages WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();
$stmt->close();

if (!$page) {
    // Check in notices table for dynamic pages
    $stmt = $conn->prepare("SELECT title, page_heading AS heading, page_content AS content, status, hero_json, custom_css, meta_description, meta_keyword FROM notices WHERE slug = ? AND (type = 'page' OR type = '' OR type IS NULL) LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $notice_page = $result->fetch_assoc();
    $stmt->close();

    if ($notice_page && $notice_page['status'] === 'active') {
        // Map the columns to standard page format
        $page = [
            'title' => $notice_page['title'],
            'heading' => $notice_page['heading'],
            'subheading' => '',
            'btn_text' => '',
            'btn_link' => '',
            'content' => $notice_page['content'],
            'custom_css' => $notice_page['custom_css'] ?? '',
            'is_dynamic' => true,
            'hero_json' => $notice_page['hero_json'],
            'meta_description' => $notice_page['meta_description'],
            'meta_keyword' => $notice_page['meta_keyword']
        ];
    }
}

if ($page && $slug === 'about-us') {
    $home_stmt = $conn->prepare("SELECT home_json FROM cms_pages WHERE slug = 'home' LIMIT 1");
    if ($home_stmt) {
        $home_stmt->execute();
        $home_res = $home_stmt->get_result()->fetch_assoc();
        $home_stmt->close();
        if (!empty($home_res['home_json'])) {
            $home_json = json_decode($home_res['home_json'], true);
            if (!empty($home_json['members_apply_about_us'])) {
                $about_json = [];
                if (!empty($page['about_json'])) {
                    $about_json = json_decode($page['about_json'], true);
                }
                if (!is_array($about_json)) {
                    $about_json = [];
                }
                
                $about_json['members_title'] = $home_json['members_title'] ?? ($about_json['members_title'] ?? 'Our Governing Members');
                $about_json['members_btn_text'] = $home_json['members_btn_text'] ?? ($about_json['members_btn_text'] ?? 'View Full list');
                $about_json['members_btn_link'] = $home_json['members_btn_link'] ?? ($about_json['members_btn_link'] ?? '/about-us');
                $about_json['members_btn_bg_color'] = $home_json['members_btn_bg_color'] ?? ($about_json['members_btn_bg_color'] ?? '#38b6ff');
                $about_json['members_btn_text_color'] = $home_json['members_btn_text_color'] ?? ($about_json['members_btn_text_color'] ?? '#000000');
                $about_json['members_notice'] = $home_json['members_notice'] ?? ($about_json['members_notice'] ?? '');
                $about_json['members_autoplay'] = isset($home_json['members_autoplay']) ? $home_json['members_autoplay'] : ($about_json['members_autoplay'] ?? true);
                $about_json['members_autoplay_speed'] = $home_json['members_autoplay_speed'] ?? ($about_json['members_autoplay_speed'] ?? 4000);
                $about_json['members'] = $home_json['members'] ?? [];
                
                $page['about_json'] = json_encode($about_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
    }
}

if ($page) {
    // Fetch official_email from cms_settings
    $settings_res = $conn->query("SELECT official_email FROM cms_settings ORDER BY id ASC LIMIT 1");
    if ($settings_res && $settings_row = $settings_res->fetch_assoc()) {
        if (!empty($settings_row['official_email'])) {
            $page['official_email'] = trim($settings_row['official_email']);
        }
    }
    echo json_encode($page);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Page not found.']);
}
