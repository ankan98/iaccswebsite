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
$conn = require_once dirname(__DIR__, 1) . '/conn.php';

// Check if the 'icon' column exists in 'cms_menu_items'
$check_column = $conn->query("SHOW COLUMNS FROM cms_menu_items LIKE 'icon'");
if ($check_column && $check_column->num_rows === 0) {
    $conn->query("ALTER TABLE cms_menu_items ADD COLUMN icon VARCHAR(100) NULL AFTER url");
}

// Handle Menu Selection (default to Main Menu, ID = 1)
$menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 1;

// Handle CRUD Operations & Order Updating BEFORE any HTML headers sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $upload_dir = dirname(__DIR__) . '/uploads/menu_icons/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $handle_icon_upload = function($file_key, $existing_path) use ($upload_dir, $menu_id) {
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES[$file_key]['name'];
            $file_size = $_FILES[$file_key]['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            // Validate file size limit <= 500 KB (512,000 bytes)
            if ($file_size > 512000) {
                $_SESSION['message'] = 'Navigation icon file size must be less than 500 KB. (Uploaded file size: ' . round($file_size / 1024) . ' KB)';
                $_SESSION['message_type'] = 'error';
                header("Location: manage-menus.php?menu_id=" . $menu_id);
                exit();
            }

            if (!in_array($file_ext, $allowed_exts)) {
                $_SESSION['message'] = 'Invalid image format for navigation icon. Allowed formats: JPG, JPEG, PNG, GIF, SVG, WEBP.';
                $_SESSION['message_type'] = 'error';
                header("Location: manage-menus.php?menu_id=" . $menu_id);
                exit();
            }

            if ($existing_path && strpos($existing_path, 'uploads/menu_icons/') === 0) {
                $old_file = dirname(__DIR__) . '/' . $existing_path;
                if (file_exists($old_file) && is_file($old_file)) {
                    @unlink($old_file);
                }
            }
            
            $new_filename = 'icon_' . time() . '_' . uniqid() . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $dest_path)) {
                return 'uploads/menu_icons/' . $new_filename;
            }
        }
        return $existing_path;
    };

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

        $icon = $handle_icon_upload('icon', '');

        if ($title === '' || $url === '') {
            $_SESSION['message'] = 'Title and URL are required.';
            $_SESSION['message_type'] = 'error';
            header("Location: manage-menus.php?menu_id=" . $menu_id);
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO cms_menu_items (menu_id, title, url, icon, parent_id, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssii", $menu_id, $title, $url, $icon, $parent_id, $sort_order);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Menu item added successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to add menu item: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
            header("Location: manage-menus.php?menu_id=" . $menu_id);
            exit();
        }
    } elseif ($action === 'edit') {
        $item_id = intval($_POST['item_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

        if ($parent_id === $item_id) {
            $parent_id = null;
        }

        $existing_icon = '';
        $stmt_fetch = $conn->prepare("SELECT icon FROM cms_menu_items WHERE id = ?");
        $stmt_fetch->bind_param("i", $item_id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($existing_icon);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        $icon = $handle_icon_upload('icon', $existing_icon);

        if ($title === '' || $url === '') {
            $_SESSION['message'] = 'Title and URL are required.';
            $_SESSION['message_type'] = 'error';
            header("Location: manage-menus.php?menu_id=" . $menu_id);
            exit();
        } else {
            $stmt = $conn->prepare("UPDATE cms_menu_items SET title = ?, url = ?, icon = ?, parent_id = ?, sort_order = ? WHERE id = ? AND menu_id = ?");
            $stmt->bind_param("sssiiii", $title, $url, $icon, $parent_id, $sort_order, $item_id, $menu_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Menu item updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to update menu item: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
            header("Location: manage-menus.php?menu_id=" . $menu_id);
            exit();
        }
    } elseif ($action === 'delete') {
        $item_id = intval($_POST['item_id'] ?? 0);
        
        $existing_icon = '';
        $stmt_fetch = $conn->prepare("SELECT icon FROM cms_menu_items WHERE id = ?");
        $stmt_fetch->bind_param("i", $item_id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($existing_icon);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        if ($existing_icon && strpos($existing_icon, 'uploads/menu_icons/') === 0) {
            $old_file = dirname(__DIR__) . '/' . $existing_icon;
            if (file_exists($old_file) && is_file($old_file)) {
                @unlink($old_file);
            }
        }

        $conn->query("UPDATE cms_menu_items SET parent_id = NULL WHERE parent_id = $item_id");

        $stmt = $conn->prepare("DELETE FROM cms_menu_items WHERE id = ? AND menu_id = ?");
        $stmt->bind_param("ii", $item_id, $menu_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Menu item deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to delete menu item: ' . $conn->error;
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
        header("Location: manage-menus.php?menu_id=" . $menu_id);
        exit();
    } elseif ($action === 'update_order') {
        $order_json = $_POST['order_data'] ?? '[]';
        $order_data = json_decode($order_json, true);
        if (is_array($order_data)) {
            $stmt = $conn->prepare("UPDATE cms_menu_items SET parent_id = ?, sort_order = ? WHERE id = ? AND menu_id = ?");
            foreach ($order_data as $idx => $item) {
                $item_id = intval($item['id']);
                $parent_id = !empty($item['parent_id']) ? intval($item['parent_id']) : null;
                $sort_order = intval($item['sort_order'] ?? ($idx + 1));

                if ($parent_id === $item_id) {
                    $parent_id = null;
                }

                $stmt->bind_param("iiii", $parent_id, $sort_order, $item_id, $menu_id);
                $stmt->execute();
            }
            $stmt->close();

            $msg = 'Menu drag & drop order & sub-menu hierarchy saved successfully!';

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => $msg]);
                exit;
            } else {
                $_SESSION['message'] = $msg;
                $_SESSION['message_type'] = 'success';
                header("Location: manage-menus.php?menu_id=" . $menu_id);
                exit();
            }
        }
    }
}

// Retrieve flash message from session, then remove immediately
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Fetch all available menus for selector
$menus_result = $conn->query("SELECT * FROM cms_menus ORDER BY id ASC");
$menus = [];
if ($menus_result) {
    while ($m = $menus_result->fetch_assoc()) {
        $menus[] = $m;
    }
}

// Ensure selected menu is valid
$selected_menu = null;
foreach ($menus as $m) {
    if ($m['id'] == $menu_id) {
        $selected_menu = $m;
        break;
    }
}
if (!$selected_menu && !empty($menus)) {
    $menu_id = intval($menus[0]['id']);
    $selected_menu = $menus[0];
}

$page_title = 'Manage Menus';
include 'include/header.php';

// Fetch all menu items for the current selected menu
$items_result = $conn->query("SELECT * FROM cms_menu_items WHERE menu_id = $menu_id ORDER BY sort_order ASC, id ASC");
$menu_items = [];
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $menu_items[] = $row;
    }
}

// Fetch all available page slugs across cms_pages, notices, and default site routes for auto-complete
$available_pages = [];

// 1. Static CMS Pages (cms_pages table)
$res_cms = $conn->query("SELECT title, slug FROM cms_pages WHERE status = 'published'");
if ($res_cms) {
    while ($row = $res_cms->fetch_assoc()) {
        if (!empty($row['slug'])) {
            $slug_path = '/' . ltrim($row['slug'], '/');
            if ($row['slug'] === 'home') $slug_path = '/';
            $available_pages[] = [
                'title' => $row['title'],
                'url'   => $slug_path
            ];
        }
    }
}

// 2. Dynamic Pages (notices table)
$res_notices = $conn->query("SELECT title, slug FROM notices WHERE (type = 'page' OR type = '' OR type IS NULL) AND status = 'active'");
if ($res_notices) {
    while ($row = $res_notices->fetch_assoc()) {
        if (!empty($row['slug'])) {
            $slug_path = '/' . ltrim($row['slug'], '/');
            $available_pages[] = [
                'title' => $row['title'],
                'url'   => $slug_path
            ];
        }
    }
}

// 3. System default routes
$default_routes = [
    ['title' => 'Home', 'url' => '/'],
    ['title' => 'About Us', 'url' => '/about-us'],
    ['title' => 'Contact Us', 'url' => '/contact-us'],
    ['title' => 'Membership', 'url' => '/membership'],
    ['title' => 'Membership Application Status', 'url' => '/membership-status'],
    ['title' => 'Notices & Announcements', 'url' => '/notices-announcements'],
    ['title' => 'Pricing', 'url' => '/pricing'],
    ['title' => 'Terms and Conditions', 'url' => '/terms-conditions'],
    ['title' => 'Privacy Policy', 'url' => '/privacy-policy'],
    ['title' => 'Refund Policy', 'url' => '/refund-policy']
];

foreach ($default_routes as $dr) {
    $exists = false;
    foreach ($available_pages as $ap) {
        if ($ap['url'] === $dr['url']) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $available_pages[] = $dr;
    }
}
?>

<!-- Include SortableJS for Drag and Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Global HTML5 Datalist for Slug Autocomplete -->
<datalist id="available_slugs_list">
    <?php foreach ($available_pages as $page_opt): ?>
        <option value="<?= htmlspecialchars($page_opt['url']) ?>"><?= htmlspecialchars($page_opt['title']) ?> (<?= htmlspecialchars($page_opt['url']) ?>)</option>
    <?php endforeach; ?>
</datalist>

<div class="w-full space-y-4 md:space-y-6 pb-20 px-3 sm:px-6 md:px-8">
    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Manage Menus</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Configure site main menu and footer menu.</p>
        </div>
    </div>

    <!-- Alert Message -->
    <div id="alert_box" class="hidden p-3.5 sm:p-4 rounded-xl flex items-center justify-between border shadow-sm transition-all duration-300">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <span id="alert_icon" class="material-symbols-outlined shrink-0">info</span>
            <span id="alert_text" class="font-medium text-xs sm:text-sm truncate"></span>
        </div>
        <button type="button" onclick="this.parentElement.classList.add('hidden')" class="text-slate-400 hover:text-slate-600 shrink-0 p-1">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>

    <?php if ($message): ?>
        <div id="php_alert_box" class="p-3.5 sm:p-4 rounded-xl flex items-center justify-between border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400' ?>">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <span class="material-symbols-outlined shrink-0"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
                <span class="font-medium text-xs sm:text-sm flex-1"><?= htmlspecialchars($message) ?></span>
            </div>
            <button type="button" onclick="document.getElementById('php_alert_box').remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors p-1 rounded-md shrink-0" title="Close Alert">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Tabbed Menu Selector (Responsive Horizontal Scroll) -->
    <div class="flex border-b border-slate-200 dark:border-slate-800 gap-2 sm:gap-4 overflow-x-auto custom-scrollbar whitespace-nowrap pb-0.5">
        <?php foreach ($menus as $m): ?>
            <a href="?menu_id=<?= $m['id'] ?>" 
               class="px-4 sm:px-5 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold border-b-2 transition-all shrink-0 <?= $m['id'] == $menu_id ? 'border-primary text-primary font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white' ?>">
                <?= htmlspecialchars($m['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        <!-- Left 2 Cols: Drag and Drop Menu Tree -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="p-3.5 sm:p-6">
                    <?php if (empty($menu_items)): ?>
                        <div class="text-center py-10 sm:py-12 flex flex-col items-center justify-center">
                            <span class="material-symbols-outlined text-slate-300 dark:text-slate-700 text-4xl sm:text-5xl mb-3">list_alt</span>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No items inside this menu yet</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xs">Use the sidebar form to add links and organize your dynamic menu map.</p>
                        </div>
                    <?php else: ?>
                        <!-- Draggable Menu Items List Container -->
                        <div id="menu_sortable_list" class="space-y-3">
                            <?php
                            $items_by_id = [];
                            foreach ($menu_items as $item) {
                                $items_by_id[$item['id']] = $item;
                            }

                            foreach ($menu_items as $item):
                                $is_child = !empty($item['parent_id']);
                                $parent_title = ($is_child && isset($items_by_id[$item['parent_id']])) ? $items_by_id[$item['parent_id']]['title'] : '';
                            ?>
                                <div class="menu-item-row border rounded-xl overflow-hidden shadow-2xs transition-all duration-200 <?= $is_child ? 'ml-3 sm:ml-8 border-l-4 border-l-primary/70 bg-slate-50/70 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800' ?>"
                                     data-id="<?= $item['id'] ?>"
                                     data-parent-id="<?= htmlspecialchars($item['parent_id'] ?? '') ?>"
                                     data-sort-order="<?= $item['sort_order'] ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 sm:px-4 sm:py-3 gap-2.5 sm:gap-3">
                                        <!-- Drag Handle & Information -->
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                            <span class="drag-handle material-symbols-outlined text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-grab active:cursor-grabbing select-none shrink-0" title="Drag to reorder">
                                                drag_indicator
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <div class="item-title-container flex items-center gap-2 flex-wrap">
                                                    <?php if (!empty($item['icon'])): ?>
                                                        <img src="../<?= htmlspecialchars($item['icon']) ?>" class="w-5 h-5 object-contain rounded shrink-0 bg-slate-100 dark:bg-slate-800 p-0.5" />
                                                    <?php endif; ?>

                                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                                        <?= htmlspecialchars($item['title']) ?>
                                                    </h4>

                                                    <?php if ($is_child): ?>
                                                        <span class="sub-item-badge inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-800/40 shrink-0 shadow-2xs">
                                                            <span class="material-symbols-outlined text-[13px] leading-none">subdirectory_arrow_right</span>
                                                            <span>Sub-item</span>
                                                            <?php if ($parent_title): ?>
                                                                <span class="text-blue-400/80 font-normal">under</span>
                                                                <span class="font-bold text-blue-700 dark:text-blue-300"><?= htmlspecialchars($parent_title) ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="text-[11px] sm:text-xs text-slate-400 truncate mt-0.5 font-mono">
                                                    <?= htmlspecialchars($item['url']) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Actions & Hierarchy Controls -->
                                        <div class="flex items-center justify-end gap-1.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800/80">
                                            <!-- Edit button -->
                                            <button type="button" onclick='openEditModal(<?= json_encode($item) ?>)' class="p-1.5 text-slate-400 hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>

                                            <!-- Delete Form -->
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this menu link?');" class="inline m-0">
                                                <input type="hidden" name="action" value="delete"/>
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>"/>
                                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Col: Add Menu Item Form -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden sticky top-6">
                <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-4 sm:px-6 py-3.5 sm:py-4">
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-base sm:text-lg">add_link</span>
                        <span>Add Menu Item</span>
                    </h2>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data" novalidate class="p-4 sm:p-6 space-y-4">
                    <input type="hidden" name="action" value="add"/>

                    <!-- Navigation Title -->
                    <div class="space-y-1">
                        <label for="add_title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Navigation Title *</label>
                        <input type="text" name="title" id="add_title" placeholder="e.g. Terms and Conditions"
                               class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                    </div>

                    <!-- URL / Link Target with Autocomplete & Quick-Select -->
                    <div class="space-y-1">
                        <label for="add_url" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">URL / Link Target *</label>
                        <input type="text" name="url" id="add_url" list="available_slugs_list" placeholder="Type or choose page slug e.g. /terms-conditions" autocomplete="off"
                               class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        
                        <!-- Page Slugs Quick Select Box -->
                        <div class="pt-1.5">
                            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Available Page Slugs (Click to insert):</span>
                            <div class="flex flex-wrap gap-1.5 mt-1 max-h-32 overflow-y-auto custom-scrollbar p-2 bg-slate-50 dark:bg-slate-950/40 rounded-lg border border-slate-200 dark:border-slate-800">
                                <?php foreach ($available_pages as $p_item): ?>
                                    <button type="button" 
                                            onclick="selectSlug('<?= htmlspecialchars($p_item['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p_item['title'], ENT_QUOTES) ?>', 'add_url', 'add_title')"
                                            class="px-2 py-1 text-[11px] bg-white dark:bg-slate-800 hover:bg-primary/10 hover:text-primary dark:hover:text-primary text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded shadow-2xs font-mono transition-all text-left">
                                        <?= htmlspecialchars($p_item['url']) ?> <span class="text-[10px] text-slate-400 font-sans">(<?= htmlspecialchars($p_item['title']) ?>)</span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Icon Upload -->
                    <div class="space-y-1.5 pt-1">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Navigation Icon/Image (Optional)</label>
                        <div class="flex items-center gap-3">
                            <input type="file" name="icon" id="icon" accept="image/*" class="hidden" onchange="updateFileName(this, 'add_file_name')"/>
                            <label for="icon" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                <span class="material-symbols-outlined text-sm">upload</span>
                                <span>Choose Image</span>
                            </label>
                            <span id="add_file_name" class="text-xs text-slate-400 truncate">No file chosen</span>
                        </div>
                    </div>

                    <!-- Parent Select -->
                    <div class="space-y-1">
                        <label for="parent_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Parent Link (Sub-menu Parent)</label>
                        <select name="parent_id" id="parent_id"
                                class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary dark:bg-slate-900 transition-all">
                            <option value="">None (Top level parent menu)</option>
                            <?php foreach ($menu_items as $item): ?>
                                <?php if (empty($item['parent_id'])): ?>
                                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sort Order -->
                    <div class="space-y-1">
                        <label for="sort_order" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Display Order</label>
                        <input type="number" name="sort_order" id="sort_order" value="<?= count($menu_items) + 1 ?>"
                               class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2">
                        <button type="submit" class="w-full h-10 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/95 transition-all shadow-sm flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            <span>Add Link to Menu</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div id="editModal" class="hidden fixed inset-0 z-55 flex items-center justify-center bg-slate-900/60 backdrop-blur-[2px] p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-lg p-6 rounded-2xl shadow-xl space-y-4 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">edit_note</span>
                <span>Edit Menu Link</span>
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data" novalidate class="space-y-4">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="item_id" id="edit_item_id"/>
            
            <div class="space-y-1">
                <label for="edit_title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Navigation Title *</label>
                <input type="text" name="title" id="edit_title"
                       class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
            </div>

            <!-- URL / Link Target with Autocomplete -->
            <div class="space-y-1">
                <label for="edit_url" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">URL / Link Target *</label>
                <input type="text" name="url" id="edit_url" list="available_slugs_list" placeholder="Type or choose page slug" autocomplete="off"
                       class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                
                <!-- Quick Select Slugs -->
                <div class="pt-1.5">
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Available Page Slugs (Click to insert):</span>
                    <div class="flex flex-wrap gap-1.5 mt-1 max-h-28 overflow-y-auto custom-scrollbar p-2 bg-slate-50 dark:bg-slate-950/40 rounded-lg border border-slate-200 dark:border-slate-800">
                        <?php foreach ($available_pages as $p_item): ?>
                            <button type="button" 
                                    onclick="selectSlug('<?= htmlspecialchars($p_item['url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p_item['title'], ENT_QUOTES) ?>', 'edit_url', 'edit_title')"
                                    class="px-2 py-1 text-[11px] bg-white dark:bg-slate-800 hover:bg-primary/10 hover:text-primary dark:hover:text-primary text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded shadow-2xs font-mono transition-all text-left">
                                <?= htmlspecialchars($p_item['url']) ?> <span class="text-[10px] text-slate-400 font-sans">(<?= htmlspecialchars($p_item['title']) ?>)</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Icon Upload -->
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Navigation Icon/Image (Optional)</label>
                <div class="flex items-center gap-3">
                    <div id="edit_icon_preview_container" class="hidden w-8 h-8 rounded border border-slate-200 dark:border-slate-800 overflow-hidden flex items-center justify-center shrink-0 bg-slate-50 dark:bg-slate-850 p-0.5">
                        <img id="edit_icon_preview" src="" class="object-contain max-w-full max-h-full" />
                    </div>
                    <div class="flex-1 flex items-center gap-2">
                        <input type="file" name="icon" id="edit_icon_file" accept="image/*" class="hidden" onchange="updateFileName(this, 'edit_file_name')"/>
                        <label for="edit_icon_file" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                            <span class="material-symbols-outlined text-sm">upload</span>
                            <span>Choose Image</span>
                        </label>
                        <span id="edit_file_name" class="text-xs text-slate-400 truncate">No file chosen</span>
                    </div>
                </div>
            </div>

            <!-- Parent Link -->
            <div class="space-y-1">
                <label for="edit_parent_id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Parent Link</label>
                <select name="parent_id" id="edit_parent_id"
                        class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary dark:bg-slate-900 transition-all">
                    <option value="">None (Top level parent menu)</option>
                    <?php foreach ($menu_items as $item): ?>
                        <?php if (empty($item['parent_id'])): ?>
                            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sort Order -->
            <div class="space-y-1">
                <label for="edit_sort_order" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Display Order</label>
                <input type="number" name="sort_order" id="edit_sort_order"
                       class="w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-lg transition-all">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/95 transition-all shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Initialize SortableJS for WordPress-style drag and drop
    const sortableContainer = document.getElementById('menu_sortable_list');
    let sortableInstance = null;

    if (sortableContainer) {
        sortableInstance = new Sortable(sortableContainer, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-primary/10',
            chosenClass: 'scale-[1.01]',
            onEnd: function (evt) {
                updateMenuHierarchyFromDOM(evt);
                saveMenuOrder();
            }
        });
    }
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;")
                   .replace(/</g, "&lt;")
                   .replace(/>/g, "&gt;")
                   .replace(/"/g, "&quot;")
                   .replace(/'/g, "&#039;");
    }

    function updateRowUI(row, parentRow) {
        const titleContainer = row.querySelector('.item-title-container');
        let subBadge = row.querySelector('.sub-item-badge');

        if (parentRow) {
            const parentTitle = parentRow.querySelector('h4')?.textContent?.trim() || '';
            row.className = 'menu-item-row border rounded-xl overflow-hidden shadow-2xs transition-all duration-200 ml-3 sm:ml-8 border-l-4 border-l-primary/70 bg-slate-50/70 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800';
            row.setAttribute('data-parent-id', parentRow.getAttribute('data-id'));

            if (!subBadge && titleContainer) {
                subBadge = document.createElement('span');
                subBadge.className = 'sub-item-badge inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-800/40 shrink-0 shadow-2xs';
                titleContainer.appendChild(subBadge);
            }
            if (subBadge) {
                subBadge.innerHTML = `<span class="material-symbols-outlined text-[13px] leading-none">subdirectory_arrow_right</span><span>Sub-item</span><span class="text-blue-400/80 font-normal">under</span><span class="font-bold text-blue-700 dark:text-blue-300">${escapeHtml(parentTitle)}</span>`;
                subBadge.style.display = 'inline-flex';
            }
        } else {
            row.className = 'menu-item-row border rounded-xl overflow-hidden shadow-2xs transition-all duration-200 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800';
            row.setAttribute('data-parent-id', '');
            if (subBadge) {
                subBadge.style.display = 'none';
            }
        }
    }

    function updateMenuHierarchyFromDOM(evt) {
        const container = document.getElementById('menu_sortable_list');
        if (!container) return;
        const rows = Array.from(container.querySelectorAll('.menu-item-row'));
        const containerRect = container.getBoundingClientRect();

        let dropX = 0;
        if (evt && evt.originalEvent) {
            dropX = evt.originalEvent.clientX || (evt.originalEvent.touches && evt.originalEvent.touches[0] ? evt.originalEvent.touches[0].clientX : 0);
        }

        const relativeX = dropX ? (dropX - containerRect.left) : 0;
        const draggedRow = evt ? evt.item : null;

        rows.forEach((row, index) => {
            row.setAttribute('data-sort-order', index + 1);

            if (index === 0) {
                row.setAttribute('data-parent-id', '');
                updateRowUI(row, null);
                return;
            }

            if (draggedRow && draggedRow === row) {
                const prevRow = rows[index - 1];
                if (relativeX > 50 && prevRow) {
                    const parentRow = prevRow.getAttribute('data-parent-id') ? 
                        rows.find(r => r.getAttribute('data-id') === prevRow.getAttribute('data-parent-id')) || prevRow
                        : prevRow;
                    row.setAttribute('data-parent-id', parentRow.getAttribute('data-id'));
                } else if (relativeX > 0 && relativeX <= 50) {
                    row.setAttribute('data-parent-id', '');
                }
            }

            const currentParentId = row.getAttribute('data-parent-id');
            let parentRow = null;

            if (currentParentId) {
                for (let i = index - 1; i >= 0; i--) {
                    if (rows[i].getAttribute('data-id') === currentParentId) {
                        parentRow = rows[i];
                        break;
                    }
                }
            }

            updateRowUI(row, parentRow);
        });
    }

    function saveMenuOrder() {
        const rows = document.querySelectorAll('#menu_sortable_list .menu-item-row');
        const orderData = [];

        rows.forEach((row, index) => {
            const id = parseInt(row.getAttribute('data-id'));
            const parent_id = row.getAttribute('data-parent-id') ? parseInt(row.getAttribute('data-parent-id')) : null;
            const sort_order = index + 1;

            orderData.push({
                id: id,
                parent_id: parent_id,
                sort_order: sort_order
            });
        });

        const formData = new FormData();
        formData.append('action', 'update_order');
        formData.append('order_data', JSON.stringify(orderData));

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            showAlert(data.message || 'Menu structure saved successfully!', 'success');
        })
        .catch(err => {
            console.error('Error saving menu order:', err);
        });
    }

    function showAlert(text, type) {
        const box = document.getElementById('alert_box');
        const txt = document.getElementById('alert_text');
        const icon = document.getElementById('alert_icon');

        txt.textContent = text;
        if (type === 'success') {
            box.className = 'p-4 rounded-xl flex items-center justify-between border shadow-sm transition-all duration-300 bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400 mb-4';
            icon.textContent = 'check_circle';
        } else {
            box.className = 'p-4 rounded-xl flex items-center justify-between border shadow-sm transition-all duration-300 bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400 mb-4';
            icon.textContent = 'error';
        }
        box.classList.remove('hidden');
    }

    function openEditModal(item) {
        document.getElementById('edit_item_id').value = item.id;
        document.getElementById('edit_title').value = item.title;
        document.getElementById('edit_url').value = item.url;
        
        const previewImg = document.getElementById('edit_icon_preview');
        const previewContainer = document.getElementById('edit_icon_preview_container');
        if (item.icon) {
            previewImg.src = '../' + item.icon;
            previewContainer.classList.remove('hidden');
        } else {
            previewImg.src = '';
            previewContainer.classList.add('hidden');
        }

        // Reset file input value and label
        document.getElementById('edit_icon_file').value = '';
        const editSpan = document.getElementById('edit_file_name');
        editSpan.textContent = 'No file chosen';
        editSpan.classList.remove('text-slate-700', 'dark:text-slate-200', 'font-medium');
        editSpan.classList.add('text-slate-400');
        
        document.getElementById('edit_parent_id').value = item.parent_id || '';
        document.getElementById('edit_sort_order').value = item.sort_order;
        
        // Remove self from edit parent option to avoid cycle
        const select = document.getElementById('edit_parent_id');
        for (let i = 0; i < select.options.length; i++) {
            if (parseInt(select.options[i].value) === parseInt(item.id)) {
                select.options[i].disabled = true;
            } else {
                select.options[i].disabled = false;
            }
        }

        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('edit_icon_file').value = '';
        const editSpan = document.getElementById('edit_file_name');
        editSpan.textContent = 'No file chosen';
        editSpan.classList.remove('text-slate-700', 'dark:text-slate-200', 'font-medium');
        editSpan.classList.add('text-slate-400');
    }
</script>

<?php include 'include/footer.php'; ?>
