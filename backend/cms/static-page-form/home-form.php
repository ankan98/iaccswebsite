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
$check_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE 'home_json'");
if ($check_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_pages ADD COLUMN home_json LONGTEXT NULL AFTER custom_css");
}

$check_type_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE 'type'");
if ($check_type_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_pages ADD COLUMN type ENUM('static', 'dynamic') NOT NULL DEFAULT 'static'");
}

// Helper to handle dynamic file uploads
function handleSingleUpload($file_array, $existing_path = '', $prefix = 'home') {
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
    // 1. Hero BG Image Upload
    $hero_bg_existing = $_POST['existing_hero_bg_image'] ?? '';
    $hero_bg_image = handleSingleUpload($_FILES['hero_bg_image'] ?? null, $hero_bg_existing, 'hero_bg');

    // 2. Vision & Mission Images Upload
    $vision_img_existing = $_POST['existing_vision_image'] ?? '';
    $vision_image = handleSingleUpload($_FILES['vision_image'] ?? null, $vision_img_existing, 'vision');

    $mission_img_existing = $_POST['existing_mission_image'] ?? '';
    $mission_image = handleSingleUpload($_FILES['mission_image'] ?? null, $mission_img_existing, 'mission');

    // 3. Process Repeatable Governing Members List
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

    // 4. Process Repeatable Slider Images
    $slider_images = [];
    $slider_ids = $_POST['slider_image_ids'] ?? [];
    if (is_array($slider_ids)) {
        foreach ($slider_ids as $s_id) {
            $s_img_existing = $_POST['existing_slider_images'][$s_id] ?? '';
            $s_img = handleSingleUpload($_FILES['slider_image_file_' . $s_id] ?? null, $s_img_existing, 'slider');
            
            if (!empty($s_img)) {
                $slider_images[] = $s_img;
            }
        }
    }

    // 5. Process Repeatable Cards List
    $cards = [];
    $card_ids = $_POST['card_ids'] ?? [];
    if (is_array($card_ids)) {
        foreach ($card_ids as $c_id) {
            $c_title = trim($_POST['card_titles'][$c_id] ?? '');
            $c_desc = trim($_POST['card_descriptions'][$c_id] ?? '');
            
            $c_img_existing = $_POST['existing_card_images'][$c_id] ?? '';
            $c_img = handleSingleUpload($_FILES['card_image_file_' . $c_id] ?? null, $c_img_existing, 'card');

            if (!empty($c_title)) {
                $cards[] = [
                    'title' => $c_title,
                    'description' => $c_desc,
                    'image' => $c_img
                ];
            }
        }
    }

    // Construct the home JSON block
    $payload = [
        // Hero Section
        'hero_bg_image' => $hero_bg_image,
        'hero_btn_bg_color' => trim($_POST['hero_btn_bg_color'] ?? '#38b6ff'),
        'hero_btn_text_color' => trim($_POST['hero_btn_text_color'] ?? '#000000'),

        // Vision & Mission
        'vision_title' => trim($_POST['vision_title'] ?? 'VISION'),
        'vision_image' => $vision_image,
        'vision_text' => trim($_POST['vision_text'] ?? ''),
        
        'mission_title' => trim($_POST['mission_title'] ?? 'MISSION'),
        'mission_image' => $mission_image,
        'mission_text' => trim($_POST['mission_text'] ?? ''),

        // Governing Members settings
        'members_title' => trim($_POST['members_title'] ?? 'Our Governing Members'),
        'members_btn_text' => trim($_POST['members_btn_text'] ?? 'View Full list'),
        'members_btn_link' => trim($_POST['members_btn_link'] ?? '/about-us'),
        'members_btn_bg_color' => trim($_POST['members_btn_bg_color'] ?? '#38b6ff'),
        'members_btn_text_color' => trim($_POST['members_btn_text_color'] ?? '#000000'),
        'members_notice' => trim($_POST['members_notice'] ?? ''),
        'members_autoplay' => isset($_POST['members_autoplay']) ? true : false,
        'members_autoplay_speed' => intval($_POST['members_autoplay_speed'] ?? 4000),
        'members_apply_about_us' => isset($_POST['members_apply_about_us']) ? true : false,
        'members' => $members,

        // Announcements feed note
        'docs_bottom_note' => trim($_POST['docs_bottom_note'] ?? ''),

        // Carousel slider settings
        'carousel_title' => trim($_POST['carousel_title'] ?? ''),
        'carousel_autoplay' => isset($_POST['carousel_autoplay']) ? true : false,
        'carousel_autoplay_speed' => intval($_POST['carousel_autoplay_speed'] ?? 3000),
        'carousel_images' => $slider_images,

        // Features/Advocacy cards list
        'cards' => $cards
    ];

    $home_json_str = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Core fields for compatibility
    $hero_heading = trim($_POST['hero_heading'] ?? '');
    $hero_subheading = trim($_POST['hero_subheading'] ?? '');
    $hero_btn_text = trim($_POST['hero_btn_text'] ?? '');
    $hero_btn_link = trim($_POST['hero_btn_link'] ?? '');
    $hero_content = trim($_POST['hero_content'] ?? '');
    $type = trim($_POST['type'] ?? 'static');

    $stmt = $conn->prepare("UPDATE cms_pages SET home_json = ?, heading = ?, subheading = ?, btn_text = ?, btn_link = ?, content = ?, type = ? WHERE (slug = 'home' OR slug = '/' OR slug = '' OR title = 'Home')");
    $stmt->bind_param("sssssss", $home_json_str, $hero_heading, $hero_subheading, $hero_btn_text, $hero_btn_link, $hero_content, $type);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Home Page updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update Home Page: ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();

    header("Location: home-form.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$page_title = 'Home Page Custom Editor';
include '../include/header.php';

// Fetch home page data
$stmt = $conn->prepare("SELECT * FROM cms_pages WHERE (slug = 'home' OR slug = '/' OR slug = '' OR title = 'Home') LIMIT 1");
$stmt->execute();
$home_page = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Resolve existing details
$hero_heading = $home_page['heading'] ?? 'Welcome to ACCS The Association for Critical Care Sciences';
$hero_subheading = $home_page['subheading'] ?? 'RECOGNITION . STANDARDS . EXCELLENCE .';
$hero_btn_text = $home_page['btn_text'] ?? 'JOIN US TODAY';
$hero_btn_link = $home_page['btn_link'] ?? '/membership';
$hero_content = $home_page['content'] ?? '<p>ACCS is dedicated to advancing clinical excellence, promoting education, and strengthening the future workforce in Critical Care Science. Together, we work for recognition, standardization, and growth of our profession.</p>';

// Defaults for JSON fields
$defaults = [
    'hero_bg_image' => '',
    'hero_btn_bg_color' => '#38b6ff',
    'hero_btn_text_color' => '#000000',
    'vision_title' => 'VISION',
    'vision_image' => '',
    'vision_text' => 'The Association for Critical Care Sciences (ACCS) is a community-led initiative formed to represent, support, and advance the field of Critical Care Technology/Science in India. We work towards unifying students, graduates, educators, and professionals to strengthen recognition, create academic opportunities, and uphold high standards in clinical practice.',
    'mission_title' => 'MISSION',
    'mission_image' => '',
    'mission_text' => 'To empower Critical Care Technology professionals through education, advocacy, collaboration, and skill development, ensuring excellence in patient care across Intensive Care settings. A future where Critical Care Technology/Science is nationally recognized and valued as an essential healthcare specialty supported by strong academic pathways, ethical practice, and professional dignity.',
    'members_title' => 'Our Governing Members',
    'members_btn_text' => 'View Full list',
    'members_btn_link' => '/about-us',
    'members_btn_bg_color' => '#38b6ff',
    'members_btn_text_color' => '#000000',
    'members_notice' => '*Due to some limitations, we are currently unable to publish the members list. However we assure you that it will be made available at a later date.',
    'members_autoplay' => true,
    'members_autoplay_speed' => 4000,
    'members_apply_about_us' => false,
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
    ],
    'docs_bottom_note' => '*Note:- If you can’t find any announcement, notices or reports visit the official sections.',
    'carousel_title' => 'Critical Care Technology Professionals working in hospital settings',
    'carousel_autoplay' => true,
    'carousel_autoplay_speed' => 3000,
    'carousel_images' => [
        '/assets/images/img297.jpg',
        '/assets/images/img300.jpg',
        '/assets/images/img303.jpg'
    ],
    'cards' => [
        [
            'title' => 'Advocacy for Recognition',
            'description' => 'Working toward the official recognition of Critical Care Technology/Science under national healthcare frameworks. We collaborate with policymakers, institutions, and stakeholders to secure professional identity and rights',
            'image' => '/assets/images/img324.jpg'
        ],
        [
            'title' => 'Training & Skill Development',
            'description' => 'Helping students and professionals enhance their knowledge and hands-on ICU skills through structured programs and learning opportunities.',
            'image' => '/assets/images/img327.jpg'
        ],
        [
            'title' => 'Academic Support & Study Resources',
            'description' => 'Providing structured learning materials, mentorship, and access to essential educational resources for students and practicing professionals in critical care domains.',
            'image' => '/assets/images/img330.jpg'
        ]
    ]
];

$home_json = [];
if (!empty($home_page['home_json'])) {
    $home_json = json_decode($home_page['home_json'], true);
}
if (!is_array($home_json)) {
    $home_json = [];
}

// Merge with defaults
$config = array_replace_recursive($defaults, $home_json);

// Make sure members, carousel_images and cards lists are correctly fetched (array_replace_recursive might merge lists incorrectly if counts differ, so we override array fields explicitly if present in json)
if (isset($home_json['members'])) {
    $config['members'] = $home_json['members'];
}
if (isset($home_json['carousel_images'])) {
    $config['carousel_images'] = $home_json['carousel_images'];
}
if (isset($home_json['cards'])) {
    $config['cards'] = $home_json['cards'];
}
?>

<div class="w-full space-y-6 pb-28">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Home Page Visual Editor</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Easily configure and manage all visual sections of the landing page.</p>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div id="status-alert" class="p-4 rounded-xl flex items-center gap-3 border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400' ?>">
            <span class="material-symbols-outlined"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
            <span class="font-medium text-sm"><?= htmlspecialchars($message) ?></span>
            <button type="button" onclick="document.getElementById('status-alert').remove()" class="ml-auto flex items-center justify-center p-1 rounded-lg text-slate-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-slate-650 dark:hover:text-slate-350 transition-colors" title="Dismiss Alert">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <form id="home_visual_form" method="POST" action="" enctype="multipart/form-data" onsubmit="tinymce.triggerSave()">
        <input type="hidden" name="type" value="<?= htmlspecialchars($home_page['type'] ?? 'static') ?>" />

        <!-- SINGLE CARD CONTAINER -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-6 py-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit Home Page Content</h2>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- Section 1: Hero Welcome Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">campaign</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Hero Welcome Section</h3>
                    </div>
                    <!-- BG Image Upload -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Hero Background Image</label>
                            <div class="flex items-center gap-3">
                                <input type="file" name="hero_bg_image" id="hero_bg_image" accept="image/*" class="hidden" onchange="updateFileName(this, 'hero_bg_file_name')" />
                                <label for="hero_bg_image" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                    <span class="material-symbols-outlined text-sm">upload</span>
                                    <span>Choose Image</span>
                                </label>
                                <span id="hero_bg_file_name" class="text-xs text-slate-400 truncate">No file chosen</span>
                            </div>
                            <input type="hidden" name="existing_hero_bg_image" value="<?= htmlspecialchars($config['hero_bg_image']) ?>" />
                        </div>
                        <?php if ($config['hero_bg_image']): ?>
                            <div class="relative w-full max-w-[200px] aspect-video border rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800">
                                <img src="../../<?= htmlspecialchars(ltrim($config['hero_bg_image'], '/')) ?>" class="w-full h-full object-cover" />
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Welcome Text Heading</label>
                            <input type="text" name="hero_heading" required value="<?= htmlspecialchars($hero_heading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">RECOGNITION / Subtitle Accent Text</label>
                            <input type="text" name="hero_subheading" required value="<?= htmlspecialchars($hero_subheading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Text</label>
                            <input type="text" name="hero_btn_text" value="<?= htmlspecialchars($hero_btn_text) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Link</label>
                            <input type="text" name="hero_btn_link" value="<?= htmlspecialchars($hero_btn_link) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button BG Color</label>
                            <input type="color" name="hero_btn_bg_color" value="<?= htmlspecialchars($config['hero_btn_bg_color']) ?>" class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Text Color</label>
                            <input type="color" name="hero_btn_text_color" value="<?= htmlspecialchars($config['hero_btn_text_color']) ?>" class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer" />
                        </div>
                    </div>

                    <div class="space-y-2 pt-4">
                        <label for="hero_content" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Detailed Description (Rich HTML editor)</label>
                        <textarea id="hero_content" name="hero_content" rows="10" class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary transition-all"><?= htmlspecialchars($hero_content) ?></textarea>
                    </div>
                </div>

                <!-- Section 2: Vision & Mission -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">visibility</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Vision & Mission</h3>
                    </div>
                    <!-- VISION -->
                    <div class="space-y-4 border-b border-slate-100 dark:border-slate-800/50 pb-6">
                        <h4 class="text-xs font-bold text-slate-850 dark:text-slate-200 border-l-4 border-primary pl-2.5">Vision Configuration</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Vision Title</label>
                                    <input type="text" name="vision_title" value="<?= htmlspecialchars($config['vision_title']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Vision Image</label>
                                    <div class="flex items-center gap-3">
                                        <input type="file" name="vision_image" id="vision_image" accept="image/*" class="hidden" onchange="updateFileName(this, 'vision_file_name')" />
                                        <label for="vision_image" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                            <span class="material-symbols-outlined text-sm">upload</span>
                                            <span>Choose Image</span>
                                        </label>
                                        <span id="vision_file_name" class="text-xs text-slate-400 truncate">No file chosen</span>
                                    </div>
                                    <input type="hidden" name="existing_vision_image" value="<?= htmlspecialchars($config['vision_image']) ?>" />
                                </div>
                            </div>
                            <?php if ($config['vision_image']): ?>
                                <div class="relative w-full max-w-[200px] aspect-video border rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800">
                                    <img src="../../<?= htmlspecialchars(ltrim($config['vision_image'], '/')) ?>" class="w-full h-full object-cover" />
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Vision Text Description</label>
                            <textarea name="vision_text" rows="4" class="w-full p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm"><?= htmlspecialchars($config['vision_text']) ?></textarea>
                        </div>
                    </div>

                    <!-- MISSION -->
                    <div class="space-y-4 pb-2">
                        <h4 class="text-xs font-bold text-slate-850 dark:text-slate-200 border-l-4 border-primary pl-2.5">Mission Configuration</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Mission Title</label>
                                    <input type="text" name="mission_title" value="<?= htmlspecialchars($config['mission_title']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Mission Image</label>
                                    <div class="flex items-center gap-3">
                                        <input type="file" name="mission_image" id="mission_image" accept="image/*" class="hidden" onchange="updateFileName(this, 'mission_file_name')" />
                                        <label for="mission_image" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                            <span class="material-symbols-outlined text-sm">upload</span>
                                            <span>Choose Image</span>
                                        </label>
                                        <span id="mission_file_name" class="text-xs text-slate-400 truncate">No file chosen</span>
                                    </div>
                                    <input type="hidden" name="existing_mission_image" value="<?= htmlspecialchars($config['mission_image']) ?>" />
                                </div>
                            </div>
                            <?php if ($config['mission_image']): ?>
                                <div class="relative w-full max-w-[200px] aspect-video border rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800">
                                    <img src="../../<?= htmlspecialchars(ltrim($config['mission_image'], '/')) ?>" class="w-full h-full object-cover" />
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Mission Text Description</label>
                            <textarea name="mission_text" rows="4" class="w-full p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm"><?= htmlspecialchars($config['mission_text']) ?></textarea>
                        </div>
                    </div>
                </div>


                <!-- Section 4: Governing Members -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">groups</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Governing Members</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Section Title</label>
                            <input type="text" name="members_title" value="<?= htmlspecialchars($config['members_title']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Limitations Alert Text (Red Border Notice)</label>
                            <input type="text" name="members_notice" value="<?= htmlspecialchars($config['members_notice']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Text</label>
                            <input type="text" name="members_btn_text" value="<?= htmlspecialchars($config['members_btn_text']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Link</label>
                            <input type="text" name="members_btn_link" value="<?= htmlspecialchars($config['members_btn_link']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Color</label>
                            <input type="color" name="members_btn_bg_color" value="<?= htmlspecialchars($config['members_btn_bg_color']) ?>" class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Button Text Color</label>
                            <input type="color" name="members_btn_text_color" value="<?= htmlspecialchars($config['members_btn_text_color']) ?>" class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 border-t border-slate-100 dark:border-slate-800/50 pt-4">
                        <div class="flex items-center pt-8">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="members_autoplay" value="1" <?= $config['members_autoplay'] ? 'checked' : '' ?> class="sr-only peer">
                                <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-650 peer-checked:bg-primary"></div>
                                <span class="ms-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Autoplay Members Slider</span>
                            </label>
                        </div>
                        <div class="flex items-center pt-8">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="members_apply_about_us" value="1" <?= !empty($config['members_apply_about_us']) ? 'checked' : '' ?> class="sr-only peer">
                                <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-650 peer-checked:bg-primary"></div>
                                <span class="ms-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Apply for About Us Page</span>
                            </label>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Autoplay Speed (ms)</label>
                            <input type="number" name="members_autoplay_speed" value="<?= htmlspecialchars($config['members_autoplay_speed']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                    </div>

                    <!-- Repeatable Container -->
                    <div id="members-list" class="space-y-4 mt-6">
                        <?php 
                        $m_idx = 1000;
                        foreach ($config['members'] as $m): 
                            $m_idx++;
                        ?>
                            <div class="member-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30">
                                <input type="hidden" name="member_ids[]" value="<?= $m_idx ?>" />
                                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Member">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Full Name</label>
                                        <input type="text" name="member_names[<?= $m_idx ?>]" required value="<?= htmlspecialchars($m['name']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Designation / Role</label>
                                        <input type="text" name="member_roles[<?= $m_idx ?>]" value="<?= htmlspecialchars($m['role']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Academic Qualification</label>
                                        <input type="text" name="member_quals[<?= $m_idx ?>]" value="<?= htmlspecialchars($m['qualification']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Member Photo</label>
                                        <div class="flex items-center gap-3">
                                            <input type="file" name="member_photo_<?= $m_idx ?>" id="member_photo_<?= $m_idx ?>" accept="image/*" class="hidden" onchange="updateFileName(this, 'member_file_name_<?= $m_idx ?>')" />
                                            <label for="member_photo_<?= $m_idx ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                                <span class="material-symbols-outlined text-sm">upload</span>
                                                <span>Choose Image</span>
                                            </label>
                                            <span id="member_file_name_<?= $m_idx ?>" class="text-xs text-slate-400 truncate">No file chosen</span>
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
                    <div class="mt-6 flex justify-start">
                        <button type="button" onclick="addMemberRow()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg shadow-sm hover:bg-primary/95 transition-all">
                            <span class="material-symbols-outlined text-xs">add</span>
                            <span>Add Member</span>
                        </button>
                    </div>
                </div>

                <!-- Section 5: Documents Bottom Note -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">campaign</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Documents Bottom Note</h3>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Document Feeds Note (Green Section Footer)</label>
                        <input type="text" name="docs_bottom_note" value="<?= htmlspecialchars($config['docs_bottom_note']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                    </div>
                </div>

                <!-- Section 6: Hospital Settings Slider Images -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">gallery_thumbnail</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Hospital Settings Slider Images</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Carousel Section Title</label>
                            <input type="text" name="carousel_title" value="<?= htmlspecialchars($config['carousel_title']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                        <div class="flex items-center pt-8">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="carousel_autoplay" value="1" <?= $config['carousel_autoplay'] ? 'checked' : '' ?> class="sr-only peer">
                                <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-650 peer-checked:bg-primary"></div>
                                <span class="ms-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Autoplay Slider</span>
                            </label>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Autoplay Speed (ms)</label>
                            <input type="number" name="carousel_autoplay_speed" value="<?= htmlspecialchars($config['carousel_autoplay_speed']) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                        </div>
                    </div>

                    <!-- Repeatable Images Container -->
                    <div id="sliders-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                        <?php 
                        $s_idx = 1000;
                        foreach ($config['carousel_images'] as $img):
                            $s_idx++;
                        ?>
                            <div class="slider-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30 flex flex-col justify-between">
                                <input type="hidden" name="slider_image_ids[]" value="<?= $s_idx ?>" />
                                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Image">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Choose Slider Image</label>
                                    <div class="flex items-center gap-3">
                                        <input type="file" name="slider_image_file_<?= $s_idx ?>" id="slider_image_file_<?= $s_idx ?>" accept="image/*" class="hidden" onchange="updateFileName(this, 'slider_file_name_<?= $s_idx ?>')" />
                                        <label for="slider_image_file_<?= $s_idx ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                            <span class="material-symbols-outlined text-sm">upload</span>
                                            <span>Choose Image</span>
                                        </label>
                                        <span id="slider_file_name_<?= $s_idx ?>" class="text-xs text-slate-400 truncate">No file chosen</span>
                                    </div>
                                    <input type="hidden" name="existing_slider_images[<?= $s_idx ?>]" value="<?= htmlspecialchars($img) ?>" />
                                </div>
                                <?php if ($img): ?>
                                    <div class="w-full aspect-video rounded-lg overflow-hidden border bg-white dark:bg-slate-900 mt-2">
                                        <img src="../../<?= htmlspecialchars(ltrim($img, '/')) ?>" class="w-full h-full object-cover" />
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex justify-start">
                        <button type="button" onclick="addSliderRow()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg shadow-sm hover:bg-primary/95 transition-all">
                            <span class="material-symbols-outlined text-xs">add</span>
                            <span>Add Image</span>
                        </button>
                    </div>
                </div>

                <!-- Section 7: Feature Cards / Objectives -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">feature_search</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Feature Cards / Objectives</h3>
                    </div>
                    <!-- Repeatable Cards Container -->
                    <div id="cards-list" class="space-y-4">
                        <?php 
                        $c_idx = 1000;
                        foreach ($config['cards'] as $c):
                            $c_idx++;
                        ?>
                            <div class="card-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30">
                                <input type="hidden" name="card_ids[]" value="<?= $c_idx ?>" />
                                <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Card">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-4">
                                        <div class="space-y-1">
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Title</label>
                                            <input type="text" name="card_titles[<?= $c_idx ?>]" required value="<?= htmlspecialchars($c['title']) ?>" class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Image</label>
                                            <div class="flex items-center gap-3">
                                                <input type="file" name="card_image_file_<?= $c_idx ?>" id="card_image_file_<?= $c_idx ?>" accept="image/*" class="hidden" onchange="updateFileName(this, 'card_file_name_<?= $c_idx ?>')" />
                                                <label for="card_image_file_<?= $c_idx ?>" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                                    <span class="material-symbols-outlined text-sm">upload</span>
                                                    <span>Choose Image</span>
                                                </label>
                                                <span id="card_file_name_<?= $c_idx ?>" class="text-xs text-slate-400 truncate">No file chosen</span>
                                            </div>
                                            <input type="hidden" name="existing_card_images[<?= $c_idx ?>]" value="<?= htmlspecialchars($c['image']) ?>" />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Description</label>
                                        <textarea name="card_descriptions[<?= $c_idx ?>]" rows="4" class="w-full p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm"><?= htmlspecialchars($c['description']) ?></textarea>
                                    </div>
                                </div>
                                <?php if ($c['image']): ?>
                                    <img src="../../<?= htmlspecialchars(ltrim($c['image'], '/')) ?>" class="w-24 h-16 rounded object-cover border" />
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex justify-start">
                        <button type="button" onclick="addCardRow()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg shadow-sm hover:bg-primary/95 transition-all">
                            <span class="material-symbols-outlined text-xs">add</span>
                            <span>Add Card</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- FORM CONTROLS (Sticky/Fixed Footer Bar) -->
    <div class="fixed bottom-0 left-64 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-center shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.03),0_-2px_4px_-1px_rgba(0,0,0,0.02)]">
        <div class="w-full flex justify-end gap-3">
            <a href="../static-pages.php" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-lg transition-all flex items-center justify-center">
                Cancel
            </a>
            <button type="submit" form="home_visual_form" class="px-8 py-3 bg-primary text-white text-sm font-bold rounded-lg shadow-md shadow-primary/20 hover:bg-primary/95 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Save
            </button>
        </div>
    </div>
</div>

<!-- TinyMCE Rich Text Editor script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#hero_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontsize | bold italic underline | link table | numlist bullist | code removeformat',
        height: 320,
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

    // Dynmamically add items using Timestamp key index generators to prevent collision
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

    function addSliderRow() {
        const id = Date.now();
        const container = document.getElementById('sliders-list');
        const row = document.createElement('div');
        row.className = 'slider-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30 flex flex-col justify-between';
        row.innerHTML = `
            <input type="hidden" name="slider_image_ids[]" value="${id}" />
            <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Image">
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
            <div class="space-y-1.5 text-left">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Choose Slider Image</label>
                <div class="flex items-center gap-3">
                    <input type="file" name="slider_image_file_${id}" id="slider_image_file_${id}" required accept="image/*" class="hidden" onchange="updateFileName(this, 'slider_file_name_${id}')" />
                    <label for="slider_image_file_${id}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                        <span class="material-symbols-outlined text-sm">upload</span>
                        <span>Choose Image</span>
                    </label>
                    <span id="slider_file_name_${id}" class="text-xs text-slate-400 truncate">No file chosen</span>
                </div>
                <input type="hidden" name="existing_slider_images[${id}]" value="" />
            </div>
        `;
        container.appendChild(row);
    }

    function addCardRow() {
        const id = Date.now();
        const container = document.getElementById('cards-list');
        const row = document.createElement('div');
        row.className = 'card-row border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-4 relative bg-slate-50 dark:bg-slate-900/30';
        row.innerHTML = `
            <input type="hidden" name="card_ids[]" value="${id}" />
            <button type="button" onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 transition-colors" title="Delete Card">
                <span class="material-symbols-outlined text-[20px]">delete</span>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Title</label>
                        <input type="text" name="card_titles[${id}]" required class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Image</label>
                        <div class="flex items-center gap-3">
                            <input type="file" name="card_image_file_${id}" id="card_image_file_${id}" accept="image/*" class="hidden" onchange="updateFileName(this, 'card_file_name_${id}')" />
                            <label for="card_image_file_${id}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                <span class="material-symbols-outlined text-sm">upload</span>
                                <span>Choose Image</span>
                            </label>
                            <span id="card_file_name_${id}" class="text-xs text-slate-400 truncate">No file chosen</span>
                        </div>
                        <input type="hidden" name="existing_card_images[${id}]" value="" />
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Card Description</label>
                    <textarea name="card_descriptions[${id}]" rows="4" class="w-full p-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm"></textarea>
                </div>
            </div>
        `;
        container.appendChild(row);
    }
</script>

<?php include '../include/footer.php'; ?>
