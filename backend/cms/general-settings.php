<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
require_once dirname(__DIR__, 1) . '/conn.php';

// Session validation: check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Fetch current settings from database (row ID = 1)
$settings_result = $conn->query("SELECT * FROM cms_settings ORDER BY id ASC LIMIT 1");
$db_settings = $settings_result ? $settings_result->fetch_assoc() : null;

// Check and add missing columns in 'cms_settings' table
$check_email_col = $conn->query("SHOW COLUMNS FROM cms_settings LIKE 'official_email'");
if ($check_email_col && $check_email_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_settings ADD COLUMN official_email VARCHAR(255) NULL AFTER site_logo");
}

// Default structure
$default_settings = [
    'site_logo' => '',
    'site_title' => 'Association for Critical Care Sciences',
    'site_title_hindi' => '',
    'official_email' => 'admin@iaccs.org.in',
    'social_links' => [
        'facebook' => ['image' => '', 'link' => '', 'order' => 1],
        'instagram' => ['image' => '', 'link' => '', 'order' => 2],
        'linkedin' => ['image' => '', 'link' => '', 'order' => 3],
        'x' => ['image' => '', 'link' => '', 'order' => 4],
    ],
    'last_updated_on' => '',
    'address' => '',
    'footer_text' => ''
];

$settings = $default_settings;
if ($db_settings) {
    // Decode social links JSON
    $decoded_socials = json_decode($db_settings['social_links'] ?? '', true);
    if (!is_array($decoded_socials)) {
        $decoded_socials = [];
    }
    
    $settings = [
        'site_logo' => $db_settings['site_logo'] ?? '',
        'site_title' => $db_settings['site_title'] ?? '',
        'site_title_hindi' => $db_settings['site_title_hindi'] ?? '',
        'official_email' => $db_settings['official_email'] ?? 'admin@iaccs.org.in',
        'social_links' => array_replace_recursive($default_settings['social_links'], $decoded_socials),
        'last_updated_on' => $db_settings['last_updated_on'] ?? '',
        'address' => $db_settings['address'] ?? '',
        'footer_text' => $db_settings['footer_text'] ?? ''
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store uploads inside 'backend/uploads/icons/'
    $upload_dir = dirname(__DIR__) . '/uploads/icons/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Helper function for uploading images with size check
    $handle_upload = function($file_key, $existing_path, $prefix) use ($upload_dir) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES[$file_key]['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            if (in_array($file_ext, $allowed_exts)) {
                // Delete old file if exists
                if ($existing_path) {
                    $old_file = dirname(__DIR__) . '/' . $existing_path;
                    if (file_exists($old_file) && is_file($old_file)) {
                        @unlink($old_file);
                    }
                }
                
                $new_filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                $dest_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $dest_path)) {
                    return 'uploads/icons/' . $new_filename;
                }
            }
        }
        return $existing_path;
    };

    // Size Validation check (1MB limit)
    $has_errors = false;
    $files_to_validate = [
        'site_logo' => 'Logo',
        'social_facebook_image' => 'Facebook icon',
        'social_instagram_image' => 'Instagram icon',
        'social_linkedin_image' => 'LinkedIn icon',
        'social_x_image' => 'X.com icon'
    ];

    foreach ($files_to_validate as $file_key => $friendly_name) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            // 1MB in bytes = 1,048,576
            if ($_FILES[$file_key]['size'] > 1024 * 1024) {
                $_SESSION['message'] = "Upload failed: $friendly_name size exceeds the 1MB limit.";
                $_SESSION['message_type'] = 'error';
                $has_errors = true;
                break;
            }
        }
    }

    if (!$has_errors) {
        $required_fields = [
            'site_title' => 'Site Title',
            'site_title_hindi' => 'Site Title [Hindi]',
            'official_email' => 'Official Email Address',
            'last_updated_on' => 'Last Updated On',
            'address' => 'Address',
            'footer_text' => 'Footer Text',
            'social_facebook_link' => 'Facebook Link URL',
            'social_instagram_link' => 'Instagram Link URL',
            'social_linkedin_link' => 'LinkedIn Link URL',
            'social_x_link' => 'X.com Link URL'
        ];

        foreach ($required_fields as $field_key => $field_label) {
            $val = trim($_POST[$field_key] ?? '');
            if ($val === '') {
                $_SESSION['message'] = "$field_label is mandatory and cannot be left blank.";
                $_SESSION['message_type'] = 'error';
                $has_errors = true;
                break;
            }
        }

        // Email format validation
        if (!$has_errors) {
            $email_val = trim($_POST['official_email'] ?? '');
            if (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['message'] = "Please enter a valid Official Email Address (e.g. admin@iaccs.org.in).";
                $_SESSION['message_type'] = 'error';
                $has_errors = true;
            }
        }
    }

    if (!$has_errors) {
        // Update settings representation
        $settings['site_title'] = trim($_POST['site_title'] ?? '');
        $settings['site_title_hindi'] = trim($_POST['site_title_hindi'] ?? '');
        $settings['official_email'] = trim($_POST['official_email'] ?? '');
        $settings['last_updated_on'] = trim($_POST['last_updated_on'] ?? '');
        $settings['address'] = trim($_POST['address'] ?? '');
        $settings['footer_text'] = trim($_POST['footer_text'] ?? '');

        // Handle site logo upload
        $logo_res = $handle_upload('site_logo', $settings['site_logo'], 'logo');
        if ($logo_res !== false) {
            $settings['site_logo'] = $logo_res;
        }

        // Handle social links upload
        $socials = ['facebook', 'instagram', 'linkedin', 'x'];
        foreach ($socials as $social) {
            $settings['social_links'][$social]['link'] = trim($_POST["social_{$social}_link"] ?? '');
            $settings['social_links'][$social]['order'] = (int)($_POST["social_{$social}_order"] ?? 0);
            
            $social_res = $handle_upload("social_{$social}_image", $settings['social_links'][$social]['image'], $social);
            if ($social_res !== false) {
                $settings['social_links'][$social]['image'] = $social_res;
            }
        }

        // Serialize social links
        $social_links_json = json_encode($settings['social_links']);

        // Update database (settings row ID = 1)
        if ($db_settings) {
            $stmt = $conn->prepare("UPDATE cms_settings SET site_title = ?, site_title_hindi = ?, site_logo = ?, official_email = ?, last_updated_on = ?, address = ?, footer_text = ?, social_links = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $settings['site_title'], $settings['site_title_hindi'], $settings['site_logo'], $settings['official_email'], $settings['last_updated_on'], $settings['address'], $settings['footer_text'], $social_links_json, $db_settings['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO cms_settings (site_title, site_title_hindi, site_logo, official_email, last_updated_on, address, footer_text, social_links) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $settings['site_title'], $settings['site_title_hindi'], $settings['site_logo'], $settings['official_email'], $settings['last_updated_on'], $settings['address'], $settings['footer_text'], $social_links_json);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = 'Settings saved successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to save settings: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
        if (isset($stmt)) {
            $stmt->close();
        }
    }
    header("Location: general-settings.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$page_title = 'Site Settings';
include 'include/header.php';
?>

<div class="max-w-5xl mx-auto space-y-4 md:space-y-6 pb-28 px-3 sm:px-6 md:px-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Site Settings</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Configure global details, site titles, social links, and footer configurations.</p>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if ($message): ?>
        <div id="status-alert" class="p-3.5 sm:p-4 rounded-xl flex items-center gap-3 border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400' ?>">
            <span class="material-symbols-outlined shrink-0"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
            <span class="font-medium text-xs sm:text-sm flex-1"><?= htmlspecialchars($message) ?></span>
            <button type="button" onclick="document.getElementById('status-alert').remove()" class="ml-auto flex items-center justify-center p-1 rounded-lg text-slate-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-slate-650 dark:hover:text-slate-350 transition-colors shrink-0" title="Dismiss Alert">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Form Panel -->
    <form id="general_settings_form" method="POST" action="" enctype="multipart/form-data" novalidate onsubmit="return validateGeneralSettingsForm(event)" class="space-y-4 md:space-y-6">
        <!-- SINGLE CARD CONTAINER -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-4 sm:px-6 py-3.5 sm:py-4">
                <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Edit Site Settings</h2>
            </div>
            
            <div class="p-4 sm:p-6 space-y-6 sm:space-y-8">
                <!-- Section 1: General Information -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-base sm:text-lg">info</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">General Information</h3>
                    </div>
                    
                    <!-- Site Logo & Titles -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        <!-- Site Logo -->
                        <div class="space-y-2 lg:col-span-1">
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Site Logo</label>
                            <div class="flex items-center gap-3 sm:gap-4">
                                <?php 
                                $logo_rel_path = ltrim($settings['site_logo'] ?? '', '/');
                                $logo_disk_path = dirname(__DIR__) . '/' . $logo_rel_path;
                                $logo_url = '../' . $logo_rel_path;
                                if (!empty($logo_rel_path) && file_exists($logo_disk_path)): 
                                ?>
                                    <div class="relative w-14 h-14 sm:w-16 sm:h-16 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 overflow-hidden flex items-center justify-center shrink-0 hover:border-primary group transition-all cursor-pointer" title="Click to view full logo in lightbox">
                                        <img id="site_logo_img" src="<?= htmlspecialchars($logo_url) ?>" onclick="openLogoLightbox(this.src)" class="object-contain max-w-full max-h-full p-1 group-hover:scale-110 transition-transform" />
                                        <span id="site_logo_placeholder" class="hidden"></span>
                                    </div>
                                <?php else: ?>
                                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center shrink-0 shadow-xs">
                                        <img id="site_logo_img" src="" onclick="openLogoLightbox(this.src)" class="object-contain max-w-full max-h-full p-1 hidden" />
                                        <span id="site_logo_placeholder" class="material-symbols-outlined text-slate-400 text-2xl">image</span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <input type="file" name="site_logo" id="site_logo" accept="image/*" class="hidden" onchange="updateFilePreview(this, 'site_logo_preview', 'site_logo_img', 'site_logo_placeholder')"/>
                                    <label for="site_logo" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-xs cursor-pointer transition-colors">
                                        <span class="material-symbols-outlined text-sm">upload</span>
                                        <span>Upload Image</span>
                                    </label>
                                    <p id="site_logo_preview" class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-medium leading-tight">Upload a square image of minimum dimension 100x100 (Max: 1MB)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Site Title -->
                        <div class="space-y-2 lg:col-span-2">
                            <label for="site_title" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Site Title *</label>
                            <input type="text" name="site_title" id="site_title"
                                   value="<?= htmlspecialchars($settings['site_title']) ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Site Title Hindi -->
                        <div class="space-y-2">
                            <label for="site_title_hindi" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Site Title [Hindi] *</label>
                            <input type="text" name="site_title_hindi" id="site_title_hindi"
                                   value="<?= htmlspecialchars($settings['site_title_hindi']) ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- Last Updated On -->
                        <div class="space-y-2">
                            <label for="last_updated_on" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Last Updated On *</label>
                            <input type="text" name="last_updated_on" id="last_updated_on" placeholder="e.g. Aug 06, 2026"
                                   value="<?= htmlspecialchars($settings['last_updated_on']) ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>
                    </div>

                    <div class="space-y-4 pt-2">
                        <!-- Official Email Address -->
                        <div class="space-y-2">
                            <label for="official_email" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Official Email Address *</label>
                            <input type="text" name="official_email" id="official_email" placeholder="admin@iaccs.org.in"
                                   value="<?= htmlspecialchars($settings['official_email'] ?? 'admin@iaccs.org.in') ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                            <p id="official_email_error" class="hidden text-xs text-red-500 font-medium mt-1"></p>
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <label for="address" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Address *</label>
                            <textarea name="address" id="address" rows="3"
                                      class="w-full p-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= htmlspecialchars($settings['address']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Social Media Links -->
                <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-base sm:text-lg">share</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Social Media Links</h3>
                    </div>
                    
                    <div id="social-links-container" class="space-y-4">
                        <?php
                        $socials_info = [
                            'facebook' => [
                                'name' => 'Facebook',
                                'color' => 'text-blue-600',
                                'bg' => 'bg-blue-50/50 dark:bg-blue-950/10',
                                'border' => 'border-blue-200 dark:border-blue-900/30'
                            ],
                            'instagram' => [
                                'name' => 'Instagram',
                                'color' => 'text-pink-600',
                                'bg' => 'bg-pink-50/50 dark:bg-pink-950/10',
                                'border' => 'border-pink-200 dark:border-pink-900/30'
                            ],
                            'linkedin' => [
                                'name' => 'LinkedIn',
                                'color' => 'text-sky-600',
                                'bg' => 'bg-sky-50/50 dark:bg-sky-950/10',
                                'border' => 'border-sky-200 dark:border-sky-900/30'
                            ],
                            'x' => [
                                'name' => 'X.com',
                                'color' => 'text-slate-800 dark:text-slate-200',
                                'bg' => 'bg-slate-50/50 dark:bg-slate-900/50',
                                'border' => 'border-slate-200 dark:border-slate-800'
                            ]
                        ];

                        foreach ($socials_info as $k => &$meta) {
                            $meta['key'] = $k;
                            $meta['order'] = isset($settings['social_links'][$k]['order']) ? (int)$settings['social_links'][$k]['order'] : 0;
                        }
                        unset($meta);

                        usort($socials_info, function($a, $b) {
                            return $a['order'] <=> $b['order'];
                        });

                        foreach ($socials_info as $meta):
                            $key = $meta['key'];
                            $link_data = $settings['social_links'][$key];
                        ?>
                            <div class="p-4 sm:p-5 rounded-xl border <?= $meta['border'] ?> <?= $meta['bg'] ?> space-y-3 sm:space-y-4 draggable-card cursor-grab active:cursor-grabbing transition-all hover:border-slate-400 dark:hover:border-slate-500" draggable="true" data-key="<?= $key ?>">
                                <input type="hidden" name="social_<?= $key ?>_order" value="<?= htmlspecialchars($link_data['order']) ?>" class="order-input"/>
                                
                                <div class="flex items-center justify-between border-b border-slate-200/50 dark:border-slate-700/50 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-slate-400 text-lg select-none">drag_indicator</span>
                                        <span class="text-xs sm:text-sm font-bold <?= $meta['color'] ?>"><?= $meta['name'] ?></span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4 items-center">
                                    <!-- Link URL -->
                                    <div class="space-y-1 md:col-span-2">
                                        <label for="social_<?= $key ?>_link" class="block text-xs font-semibold text-slate-500 dark:text-slate-400">Link URL *</label>
                                        <input type="url" name="social_<?= $key ?>_link" id="social_<?= $key ?>_link" placeholder="https://..."
                                               value="<?= htmlspecialchars($link_data['link']) ?>"
                                               class="w-full h-10 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                                    </div>

                                    <!-- Custom Brand Icon / Image Upload -->
                                    <div class="space-y-1 md:col-span-1">
                                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">Custom Brand Icon/Image</label>
                                        <div class="flex items-center gap-3">
                                            <?php 
                                            $social_rel_path = ltrim($link_data['image'] ?? '', '/');
                                            $social_disk_path = dirname(__DIR__) . '/' . $social_rel_path;
                                            $social_url = '../' . $social_rel_path;
                                            if (!empty($social_rel_path) && file_exists($social_disk_path)): 
                                            ?>
                                                <div class="relative w-10 h-10 rounded-lg border border-slate-200 dark:border-slate-750 bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center shrink-0 shadow-xs">
                                                    <img id="social_<?= $key ?>_img" src="<?= htmlspecialchars($social_url) ?>" class="object-contain max-w-full max-h-full p-0.5" />
                                                    <span id="social_<?= $key ?>_placeholder" class="hidden"></span>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded-lg border border-slate-200 dark:border-slate-750 bg-white dark:bg-slate-800 flex items-center justify-center shrink-0 shadow-xs">
                                                    <img id="social_<?= $key ?>_img" src="" class="object-contain max-w-full max-h-full p-0.5 hidden" />
                                                    <span id="social_<?= $key ?>_placeholder" class="material-symbols-outlined text-slate-400 text-lg">link</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1 min-w-0">
                                                <input type="file" name="social_<?= $key ?>_image" id="social_<?= $key ?>_image" accept="image/*" class="hidden" onchange="updateFilePreview(this, 'social_<?= $key ?>_preview', 'social_<?= $key ?>_img', 'social_<?= $key ?>_placeholder')"/>
                                                <label for="social_<?= $key ?>_image" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-[11px] font-semibold text-slate-700 dark:text-slate-200 shadow-xs cursor-pointer transition-colors">
                                                    <span class="material-symbols-outlined text-xs">upload</span>
                                                    <span>Upload Icon</span>
                                                </label>
                                                <p id="social_<?= $key ?>_preview" class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">Max size: 1MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section 3: Footer Configuration -->
                <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-base sm:text-lg">campaign</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Footer Configuration</h3>
                    </div>
                    <div class="space-y-2">
                        <label for="footer_text" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Footer Text *</label>
                        <textarea name="footer_text" id="footer_text" rows="6"
                                  class="w-full p-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= htmlspecialchars($settings['footer_text']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Form Controls (Responsive Sticky Footer Bar) -->
    <div class="fixed bottom-0 left-0 md:left-64 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-4 sm:px-6 py-3 border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.03),0_-2px_4px_-1px_rgba(0,0,0,0.02)]">
        <div class="w-full max-w-5xl mx-auto flex items-center justify-end gap-2.5 sm:gap-3">
            <a href="dashboard.php" class="px-4 sm:px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-semibold rounded-xl transition-all flex items-center justify-center flex-1 sm:flex-none">
                Cancel
            </a>
            <button type="submit" form="general_settings_form" class="px-6 sm:px-8 py-2.5 bg-primary text-white text-xs sm:text-sm font-bold rounded-xl shadow-md shadow-primary/20 hover:bg-primary/95 active:scale-[0.99] transition-all flex items-center justify-center flex-1 sm:flex-none">
                Save
            </button>
        </div>
    </div>
</div>

<script>
    function updateFilePreview(input, previewId, imgId = null, placeholderId = null) {
        const preview = document.getElementById(previewId);
        if (!preview) return;
        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            const sizeKB = Math.round(file.size / 1024);
            if (file.size > 1024 * 1024) {
                alert('File size exceeds 1MB limit! (Selected: ' + sizeKB + ' KB). Please select an image smaller than 1MB.');
                input.value = '';
                preview.textContent = 'Max size: 1MB';
                preview.classList.remove('text-slate-700', 'dark:text-slate-200', 'font-bold');
                preview.classList.add('text-slate-400', 'dark:text-slate-500');
                return;
            }
            preview.textContent = file.name + ' (' + sizeKB + ' KB)';
            preview.classList.remove('text-slate-400', 'dark:text-slate-500');
            preview.classList.add('text-slate-700', 'dark:text-slate-200', 'font-bold');

            // Render live image preview
            if (imgId) {
                const imgEl = document.getElementById(imgId);
                if (imgEl) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgEl.src = e.target.result;
                        imgEl.classList.remove('hidden');
                        if (placeholderId) {
                            const ph = document.getElementById(placeholderId);
                            if (ph) ph.classList.add('hidden');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#footer_text',
        plugins: 'anchor autolink charmap codesample emoticons link lists searchreplace visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link emoticons charmap | align lineheight | numlist bullist indent outdent | code removeformat',
        height: 280,
        menubar: false,
        branding: false,
        promotion: false,
        setup: function (editor) {
            editor.on('change', function () {
                tinymce.triggerSave();
            });
        }
    });

    function validateGeneralSettingsForm(e) {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }

        const requiredFields = [
            { id: 'site_title', name: 'Site Title' },
            { id: 'site_title_hindi', name: 'Site Title [Hindi]' },
            { id: 'official_email', name: 'Official Email Address' },
            { id: 'last_updated_on', name: 'Last Updated On' },
            { id: 'address', name: 'Address' },
            { id: 'footer_text', name: 'Footer Text' },
            { id: 'social_facebook_link', name: 'Facebook Link URL' },
            { id: 'social_instagram_link', name: 'Instagram Link URL' },
            { id: 'social_linkedin_link', name: 'LinkedIn Link URL' },
            { id: 'social_x_link', name: 'X.com Link URL' }
        ];

        // Reset previous error styles
        requiredFields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el) {
                el.classList.remove('border-red-500', 'ring-2', 'ring-red-500/30');
            }
        });
        const emailErr = document.getElementById('official_email_error');
        if (emailErr) emailErr.classList.add('hidden');

        for (let i = 0; i < requiredFields.length; i++) {
            const field = requiredFields[i];
            const el = document.getElementById(field.id);
            let val = '';
            if (field.id === 'footer_text' && typeof tinymce !== 'undefined' && tinymce.get('footer_text')) {
                val = tinymce.get('footer_text').getContent({ format: 'text' }).trim();
            } else if (el) {
                val = el.value.trim();
            }

            if (!val) {
                if (e) e.preventDefault();
                if (field.id === 'official_email' && emailErr) {
                    emailErr.textContent = 'Official Email Address is mandatory and cannot be left blank.';
                    emailErr.classList.remove('hidden');
                } else {
                    alert(field.name + ' is mandatory and cannot be submitted blank!');
                }
                if (el) {
                    el.focus();
                    el.classList.add('border-red-500', 'ring-2', 'ring-red-500/30');
                }
                return false;
            }

            if (field.id === 'official_email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(val)) {
                    if (e) e.preventDefault();
                    if (emailErr) {
                        emailErr.textContent = 'Please enter a valid Official Email Address (e.g. admin@iaccs.org.in).';
                        emailErr.classList.remove('hidden');
                    }
                    if (el) {
                        el.focus();
                        el.classList.add('border-red-500', 'ring-2', 'ring-red-500/30');
                    }
                    return false;
                }
            }
        }
        return true;
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('social-links-container');
        let dragSourcesElement = null;

        container.addEventListener('dragstart', function (e) {
            const card = e.target.closest('.draggable-card');
            if (!card) return;
            dragSourcesElement = card;
            card.classList.add('opacity-40', 'scale-[0.98]', 'border-dashed');
            e.dataTransfer.effectAllowed = 'move';
        });

        container.addEventListener('dragover', function (e) {
            e.preventDefault();
            const card = e.target.closest('.draggable-card');
            if (!card || card === dragSourcesElement) return;

            const rect = card.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            const isAfter = e.clientY > midpoint;

            if (isAfter) {
                if (card.nextSibling !== dragSourcesElement) {
                    container.insertBefore(dragSourcesElement, card.nextSibling);
                }
            } else {
                if (card !== dragSourcesElement) {
                    container.insertBefore(dragSourcesElement, card);
                }
            }
        });

        container.addEventListener('dragend', function (e) {
            const card = e.target.closest('.draggable-card');
            if (card) {
                card.classList.remove('opacity-40', 'scale-[0.98]', 'border-dashed');
            }
            updateOrders();
        });

        // Add auto-sorting when typing order manually
        container.addEventListener('change', function (e) {
            if (e.target.classList.contains('order-input')) {
                sortCardsByInputValue();
            }
        });

        function updateOrders() {
            const cards = container.querySelectorAll('.draggable-card');
            cards.forEach((card, index) => {
                const orderInput = card.querySelector('.order-input');
                if (orderInput) {
                    orderInput.value = index + 1;
                }
            });
        }

        function sortCardsByInputValue() {
            const cards = Array.from(container.querySelectorAll('.draggable-card'));
            cards.sort((a, b) => {
                const valA = parseInt(a.querySelector('.order-input').value) || 0;
                const valB = parseInt(b.querySelector('.order-input').value) || 0;
                return valA - valB;
            });
            cards.forEach(card => container.appendChild(card));
            updateOrders();
        }
    });
</script>

<?php include 'include/footer.php'; ?>
