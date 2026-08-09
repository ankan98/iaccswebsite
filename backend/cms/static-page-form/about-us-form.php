<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cms_root = (strpos($_SERVER['PHP_SELF'], '/static-page-form/') !== false) ? '../' : '';

// Session validation: check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: " . $cms_root . "../login.php");
    exit();
}

// Database Connection
require_once dirname(__DIR__, 2) . '/conn.php';

// Check and add missing columns in 'cms_pages' table
$check_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE 'about_json'");
if ($check_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_pages ADD COLUMN about_json LONGTEXT NULL AFTER home_json");
}

$check_type_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE 'type'");
if ($check_type_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_pages ADD COLUMN type ENUM('static', 'dynamic') NOT NULL DEFAULT 'static'");
}

// Helper to handle dynamic file uploads
function handleSingleUpload($file_array, $existing_path = '', $prefix = 'about') {
    if (isset($file_array) && $file_array['error'] === UPLOAD_ERR_OK) {
        $file_name = $file_array['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $upload_dir = dirname(__DIR__, 2) . '/uploads/home/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old file if it exists inside uploads/home/
            if ($existing_path && strpos($existing_path, 'uploads/home/') === 0) {
                $old_file = dirname(__DIR__, 2) . '/' . $existing_path;
                if (file_exists($old_file) && is_file($old_file)) {
                    @unlink($old_file);
                }
            }
            
            $new_filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_array['tmp_name'], $dest_path)) {
                return 'uploads/home/' . $new_filename;
            }
        }
    }
    return $existing_path;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_members_synced = false;
    $home_stmt = $conn->prepare("SELECT home_json FROM cms_pages WHERE slug = 'home' LIMIT 1");
    if ($home_stmt) {
        $home_stmt->execute();
        $home_res = $home_stmt->get_result()->fetch_assoc();
        $home_stmt->close();
        if (!empty($home_res['home_json'])) {
            $home_json = json_decode($home_res['home_json'], true);
            $is_members_synced = !empty($home_json['members_apply_about_us']);
        }
    }

    $db_stmt = $conn->prepare("SELECT about_json FROM cms_pages WHERE slug = 'about-us' LIMIT 1");
    $db_stmt->execute();
    $db_res = $db_stmt->get_result()->fetch_assoc();
    $db_stmt->close();
    
    $existing_about_json = [];
    if (!empty($db_res['about_json'])) {
        $existing_about_json = json_decode($db_res['about_json'], true);
    }

    if ($is_members_synced) {
        $members = $existing_about_json['members'] ?? [];
        $members_title = $existing_about_json['members_title'] ?? 'Our Governing Members';
        $members_btn_text = $existing_about_json['members_btn_text'] ?? 'View Full list';
        $members_btn_link = $existing_about_json['members_btn_link'] ?? '/about-us';
        $members_btn_bg_color = $existing_about_json['members_btn_bg_color'] ?? '#38b6ff';
        $members_btn_text_color = $existing_about_json['members_btn_text_color'] ?? '#000000';
        $members_notice = $existing_about_json['members_notice'] ?? '';
        $members_autoplay = isset($existing_about_json['members_autoplay']) ? $existing_about_json['members_autoplay'] : true;
        $members_autoplay_speed = $existing_about_json['members_autoplay_speed'] ?? 4000;
    } else {
        // Process Repeatable Governing Members List
        $members = [];
        $member_ids = $_POST['member_ids'] ?? [];
        if (is_array($member_ids)) {
            foreach ($member_ids as $m_id) {
                $m_name = trim($_POST['member_names'][$m_id] ?? '');
                $m_role = trim($_POST['member_roles'][$m_id] ?? '');
                $m_qual = trim($_POST['member_quals'][$m_id] ?? '');
                
                $m_img_existing = $_POST['existing_member_photos'][$m_id] ?? '';
                $m_img = handleSingleUpload($_FILES['member_photo_' . $m_id] ?? null, $m_img_existing, 'member');

                if (!empty($m_name)) {
                    $members[] = [
                        'name' => $m_name,
                        'role' => $m_role,
                        'qualification' => $m_qual,
                        'image' => $m_img
                    ];
                }
            }
        }
        $members_title = trim($_POST['members_title'] ?? 'Our Governing Members');
        $members_btn_text = trim($_POST['members_btn_text'] ?? 'View Full list');
        $members_btn_link = trim($_POST['members_btn_link'] ?? '/about-us');
        $members_btn_bg_color = trim($_POST['members_btn_bg_color'] ?? '#38b6ff');
        $members_btn_text_color = trim($_POST['members_btn_text_color'] ?? '#000000');
        $members_notice = trim($_POST['members_notice'] ?? '');
        $members_autoplay = isset($_POST['members_autoplay']) ? true : false;
        $members_autoplay_speed = intval($_POST['members_autoplay_speed'] ?? 4000);
    }

    // Collect settings for JSON payload
    $payload = [
        'members_title' => $members_title,
        'members_btn_text' => $members_btn_text,
        'members_btn_link' => $members_btn_link,
        'members_btn_bg_color' => $members_btn_bg_color,
        'members_btn_text_color' => $members_btn_text_color,
        'members_notice' => $members_notice,
        'members_autoplay' => $members_autoplay,
        'members_autoplay_speed' => $members_autoplay_speed,
        'members' => $members,
    ];

    $about_json_str = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Core fields
    $title = trim($_POST['title'] ?? 'About Us');
    $heading = trim($_POST['heading'] ?? '');
    $subheading = trim($_POST['subheading'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $custom_css = trim($_POST['custom_css'] ?? '');
    $type = trim($_POST['type'] ?? 'static');

    $stmt = $conn->prepare("UPDATE cms_pages SET title = ?, heading = ?, subheading = ?, content = ?, custom_css = ?, about_json = ?, type = ? WHERE slug = 'about-us'");
    $stmt->bind_param("sssssss", $title, $heading, $subheading, $content, $custom_css, $about_json_str, $type);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'About Us Page updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update About Us Page: ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();

    header("Location: about-us-form.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$page_title = 'About Us Custom Editor';
include '../include/header.php';

// Fetch about us page data
$stmt = $conn->prepare("SELECT * FROM cms_pages WHERE slug = 'about-us' LIMIT 1");
$stmt->execute();
$about_page = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Resolve existing details
$title = $about_page['title'] ?? 'About Us';
$heading = $about_page['heading'] ?? 'Delivering Free Healthcare to Underserved Communities Worldwide';
$subheading = $about_page['subheading'] ?? '';
$content = $about_page['content'] ?? '';
$custom_css = $about_page['custom_css'] ?? '';

// Defaults for JSON fields
$defaults = [
    'members_title' => 'Our Governing Members',
    'members_btn_text' => 'View Full list',
    'members_btn_link' => '/about-us',
    'members_btn_bg_color' => '#38b6ff',
    'members_btn_text_color' => '#000000',
    'members_notice' => '*Due to some limitations, we are currently unable to publish the members list. However we assure you that it will be made available at a later date.',
    'members_autoplay' => true,
    'members_autoplay_speed' => 4000,
    'members' => [
        [
            'name' => 'BAPAN SARKAR',
            'role' => 'President',
            'qualification' => 'M.sc CCST',
            'image' => '/assets/images/bapan-sarkar.jpg'
        ],
        [
            'name' => 'ATRI BANERJEE',
            'role' => 'General Secretary',
            'qualification' => 'B.sc CCT',
            'image' => '/assets/images/atri-banerjee.jpg'
        ]
    ]
];

$about_json = [];
if (!empty($about_page['about_json'])) {
    $about_json = json_decode($about_page['about_json'], true);
}
if (!is_array($about_json)) {
    $about_json = [];
}

// Fetch home configuration to check if members list is synced from home
$home_stmt = $conn->prepare("SELECT home_json FROM cms_pages WHERE slug = 'home' LIMIT 1");
$home_stmt->execute();
$home_res = $home_stmt->get_result()->fetch_assoc();
$home_stmt->close();

$home_json = [];
if (!empty($home_res['home_json'])) {
    $home_json = json_decode($home_res['home_json'], true);
}
$is_members_synced = !empty($home_json['members_apply_about_us']);

// Merge with defaults
$config = array_replace_recursive($defaults, $about_json);

// Make sure members list is correctly overridden explicitly if present
if (isset($about_json['members'])) {
    $config['members'] = $about_json['members'];
}

// If synced from home, override with home members settings
if ($is_members_synced) {
    $config['members_title'] = $home_json['members_title'] ?? $config['members_title'];
    $config['members_btn_text'] = $home_json['members_btn_text'] ?? $config['members_btn_text'];
    $config['members_btn_link'] = $home_json['members_btn_link'] ?? $config['members_btn_link'];
    $config['members_btn_bg_color'] = $home_json['members_btn_bg_color'] ?? $config['members_btn_bg_color'];
    $config['members_btn_text_color'] = $home_json['members_btn_text_color'] ?? $config['members_btn_text_color'];
    $config['members_notice'] = $home_json['members_notice'] ?? $config['members_notice'];
    $config['members_autoplay'] = isset($home_json['members_autoplay']) ? $home_json['members_autoplay'] : $config['members_autoplay'];
    $config['members_autoplay_speed'] = $home_json['members_autoplay_speed'] ?? $config['members_autoplay_speed'];
    $config['members'] = $home_json['members'] ?? [];
}
?>

<div class="max-w-6xl mx-auto space-y-6 pb-28">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">About Us Visual Editor</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Easily configure and manage all visual sections of the About Us page.</p>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div id="status-alert" class="p-4 rounded-xl flex items-center gap-3 border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400' ?>">
            <span class="material-symbols-outlined"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
            <span class="font-medium text-sm"><?= htmlspecialchars($message) ?></span>
            <button type="button" onclick="document.getElementById('status-alert').remove()" class="ml-auto flex items-center justify-center p-1 rounded-lg text-slate-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-slate-655 dark:hover:text-slate-350 transition-colors" title="Dismiss Alert">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <form id="about_visual_form" method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="type" value="<?= htmlspecialchars($about_page['type'] ?? 'static') ?>" />

        <!-- SINGLE CARD CONTAINER -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-6 py-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit About Us Page Content</h2>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- Section 1: Page Header & Main Details -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">info</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Header & Main Details</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Page Title</label>
                            <input type="text" name="title" required value="<?= htmlspecialchars($title) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Section Subtitle/Brief Tag</label>
                            <input type="text" name="subheading" value="<?= htmlspecialchars($subheading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Main Heading Accent</label>
                        <input type="text" name="heading"  value="<?= htmlspecialchars($heading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                    </div>
                </div>

                <!-- Section 2: Governing Members -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">groups</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Governing Members</h3>
                    </div>

                    <?php if ($is_members_synced): ?>
                        <div class="p-4 rounded-xl border border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-900/50 dark:bg-yellow-950/20 dark:text-yellow-400 text-sm flex items-center gap-3">
                            <span class="material-symbols-outlined">warning</span>
                            <span><strong>Note:</strong> Governing Members settings are currently synchronized from the Home page configuration. To edit members individually, disable <strong>"Apply for About Us Page"</strong> in the <a href="home-form.php" class="underline font-bold hover:text-yellow-900">Home Page Visual Editor</a>.</span>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Section Title</label>
                            <input type="text" name="members_title" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_title']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Limitations Alert Text (Red Border Notice)</label>
                            <input type="text" name="members_notice" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_notice']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Text</label>
                            <input type="text" name="members_btn_text" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_btn_text']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Link</label>
                            <input type="text" name="members_btn_link" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_btn_link']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Color</label>
                            <input type="color" name="members_btn_bg_color" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_btn_bg_color']) ?>" class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Text Color</label>
                            <input type="color" name="members_btn_text_color" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_btn_text_color']) ?>" class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 border-t border-slate-100 dark:border-slate-800/50 pt-4">
                        <div class="flex items-center pt-8">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="members_autoplay" <?= $is_members_synced ? 'disabled' : '' ?> value="1" <?= $config['members_autoplay'] ? 'checked' : '' ?> class="sr-only peer">
                                <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-650 peer-checked:bg-primary"></div>
                                <span class="ms-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Autoplay Members Slider</span>
                            </label>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Autoplay Speed (ms)</label>
                            <input type="number" name="members_autoplay_speed" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($config['members_autoplay_speed']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                    </div>

                    <!-- Repeatable Container -->
                    <div id="members-list" class="space-y-4 mt-6">
                        <?php 
                        $m_idx = 1000; // Counter for initial list items
                        foreach ($config['members'] as $m): 
                            $m_idx++;
                        ?>
                            <div class="member-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30">
                                <input type="hidden" name="member_ids[]" value="<?= $m_idx ?>" />
                                <?php if (!$is_members_synced): ?>
                                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Member">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                <?php endif; ?>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Full Name</label>
                                        <input type="text" name="member_names[<?= $m_idx ?>]" <?= $is_members_synced ? 'disabled' : '' ?> required value="<?= htmlspecialchars($m['name']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Designation / Role</label>
                                        <input type="text" name="member_roles[<?= $m_idx ?>]" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($m['role']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Academic Qualification</label>
                                        <input type="text" name="member_quals[<?= $m_idx ?>]" <?= $is_members_synced ? 'disabled' : '' ?> value="<?= htmlspecialchars($m['qualification']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Member Photo</label>
                                        <div class="flex items-center gap-3">
                                            <input type="file" name="member_photo_<?= $m_idx ?>" id="member_photo_<?= $m_idx ?>" accept="image/*" class="hidden" onchange="updateFileName(this, 'member_file_name_<?= $m_idx ?>')" />
                                            <?php if (!$is_members_synced): ?>
                                                <label for="member_photo_<?= $m_idx ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                                    <span class="material-symbols-outlined text-sm">upload</span>
                                                    <span>Choose Image</span>
                                                </label>
                                            <?php endif; ?>
                                            <span id="member_file_name_<?= $m_idx ?>" class="text-xs text-slate-400 truncate font-medium">No file chosen</span>
                                        </div>
                                        <input type="hidden" name="existing_member_photos[<?= $m_idx ?>]" value="<?= htmlspecialchars($m['image']) ?>" />
                                    </div>
                                    <?php if ($m['image']): ?>
                                        <img src="../../<?= htmlspecialchars(ltrim($m['image'], '/')) ?>" class="w-12 h-12 rounded-lg object-cover border" />
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!$is_members_synced): ?>
                        <div class="mt-6 flex justify-start">
                            <button type="button" onclick="addMemberRow()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg shadow-sm hover:bg-primary/95 transition-all">
                                <span class="material-symbols-outlined text-xs">add</span>
                                <span>Add Member</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section 3: Page Body Content -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">article</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Body Content (Rich Text)</h3>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Detailed Description (Rich HTML editor)</label>
                        <textarea id="about_content" name="content" rows="12" class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary transition-all"><?= htmlspecialchars($content) ?></textarea>
                    </div>
                </div>

                <!-- Section 4: Custom Stylesheet -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">css</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Custom Stylesheet</h3>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Custom CSS Rules (Applied dynamically to page)</label>
                        <textarea name="custom_css" id="custom_css" rows="6" placeholder="/* Custom page specific styling */"
                                  class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= htmlspecialchars($custom_css) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- FORM CONTROLS (Sticky/Fixed Footer Bar) -->
    <div class="fixed bottom-0 left-64 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-center shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.03),0_-2px_4px_-1px_rgba(0,0,0,0.02)]">
        <div class="w-full max-w-6xl flex justify-end gap-3">
            <a href="../static-pages.php" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-lg transition-all flex items-center justify-center">
                Cancel
            </a>
            <button type="submit" form="about_visual_form" class="px-8 py-3 bg-primary text-white text-sm font-bold rounded-lg shadow-md shadow-primary/20 hover:bg-primary/95 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Save
            </button>
        </div>
    </div>
</div>

<!-- TinyMCE Rich Text Editor script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#about_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontsize | bold italic underline | link table | numlist bullist | code removeformat',
        height: 400,
        branding: false,
        promotion: false
    });

    function updateFileName(input, targetId) {
        const span = document.getElementById(targetId);
        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
            span.classList.remove('text-slate-400');
            span.classList.add('text-slate-700', 'dark:text-slate-200', 'font-medium');
        } else {
            span.textContent = 'No file chosen';
            span.classList.remove('text-slate-700', 'dark:text-slate-200', 'font-medium');
            span.classList.add('text-slate-400');
        }
    }

    function addMemberRow() {
        const id = Date.now();
        const container = document.getElementById('members-list');
        const row = document.createElement('div');
        row.className = 'member-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30';
        row.innerHTML = `
            <input type="hidden" name="member_ids[]" value="${id}" />
            <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Member">
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Full Name</label>
                    <input type="text" name="member_names[${id}]" required class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Designation / Role</label>
                    <input type="text" name="member_roles[${id}]" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Academic Qualification</label>
                    <input type="text" name="member_quals[${id}]" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Member Photo</label>
                    <div class="flex items-center gap-3">
                        <input type="file" name="member_photo_${id}" id="member_photo_${id}" accept="image/*" class="hidden" onchange="updateFileName(this, 'member_file_name_${id}')" />
                        <label for="member_photo_${id}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                            <span class="material-symbols-outlined text-sm">upload</span>
                            <span>Choose Image</span>
                        </label>
                        <span id="member_file_name_${id}" class="text-xs text-slate-400 truncate">No file chosen</span>
                    </div>
                    <input type="hidden" name="existing_member_photos[${id}]" value="" />
                </div>
            </div>
        `;
        container.appendChild(row);
    }
</script>

<?php include '../include/footer.php'; ?>
