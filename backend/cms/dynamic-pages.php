<?php
$page_title = 'Custom Pages';
include 'include/header.php';

// Check and add missing columns in 'notices' table to support Dynamic Pages
$notices_cols = [
    'slug' => 'VARCHAR(255) NULL AFTER title',
    'meta_description' => 'TEXT NULL AFTER slug',
    'meta_keyword' => 'TEXT NULL AFTER meta_description',
    'page_heading' => 'VARCHAR(255) NULL AFTER meta_keyword',
    'page_content' => 'LONGTEXT NULL AFTER page_heading',
    'hero_json' => 'LONGTEXT NULL AFTER page_content',
    'custom_css' => 'LONGTEXT NULL AFTER hero_json'
];

foreach ($notices_cols as $col_name => $col_definition) {
    $check_col = $conn->query("SHOW COLUMNS FROM notices LIKE '$col_name'");
    if ($check_col->num_rows === 0) {
        $conn->query("ALTER TABLE notices ADD COLUMN $col_name $col_definition");
    }
}

// Ensure 'type' column can store 'page' and dynamic string values
$check_type = $conn->query("SHOW COLUMNS FROM notices LIKE 'type'");
if ($check_type && $type_row = $check_type->fetch_assoc()) {
    if (strpos(strtolower($type_row['Type']), 'enum') !== false) {
        $conn->query("ALTER TABLE notices MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'page'");
    }
}

// Helper to handle dynamic file uploads
function handleSingleUpload($file_array, $existing_path = '', $prefix = 'dynamic') {
    if (isset($file_array) && $file_array['error'] === UPLOAD_ERR_OK) {
        $file_name = $file_array['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $upload_dir = dirname(__DIR__, 1) . '/uploads/pages/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Delete old file if it exists inside uploads/pages/
            if ($existing_path && strpos($existing_path, 'uploads/pages/') === 0) {
                $old_file = dirname(__DIR__, 1) . '/' . $existing_path;
                if (file_exists($old_file) && is_file($old_file)) {
                    @unlink($old_file);
                }
            }
            
            $new_filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_array['tmp_name'], $dest_path)) {
                return 'uploads/pages/' . $new_filename;
            }
        }
    }
    return $existing_path;
}

$action = $_REQUEST['action'] ?? ($_GET['action'] ?? 'list');
$edit_id = isset($_REQUEST['edit_id']) ? intval($_REQUEST['edit_id']) : (isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0);

$message = '';
$message_type = '';

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $raw_slug = !empty($_POST['slug']) ? $_POST['slug'] : $title;
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $raw_slug), '-'));
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keyword = trim($_POST['meta_keyword'] ?? '');
        $page_heading = trim($_POST['page_heading'] ?? '');
        $page_content = trim($_POST['page_content'] ?? '');
        $custom_css = trim($_POST['custom_css'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $type = trim($_POST['type'] ?? 'page');
        if (empty($type)) {
            $type = 'page';
        }

        // Parse Hero Section details
        $hero_active = isset($_POST['hero_active']) && $_POST['hero_active'] === '1' ? 1 : 0;
        $hero_title = trim($_POST['hero_title'] ?? '');
        $hero_subtitle = trim($_POST['hero_subtitle'] ?? '');
        $hero_description = trim($_POST['hero_description'] ?? '');
        $hero_btn_text = trim($_POST['hero_btn_text'] ?? '');
        $hero_btn_link = trim($_POST['hero_btn_link'] ?? '');
        $hero_btn_color = trim($_POST['hero_btn_color'] ?? '#38b6ff');

        $hero_image = '';
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
            $hero_image = handleSingleUpload($_FILES['hero_image'], '', 'hero');
        }

        $hero_json = json_encode([
            'hero_active' => $hero_active,
            'hero_image' => $hero_image,
            'hero_title' => $hero_title,
            'hero_subtitle' => $hero_subtitle,
            'hero_description' => $hero_description,
            'hero_btn_text' => $hero_btn_text,
            'hero_btn_link' => $hero_btn_link,
            'hero_btn_color' => $hero_btn_color
        ]);

        if (empty($title)) {
            $message = 'Title is a required field.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO notices (title, slug, meta_description, meta_keyword, page_heading, page_content, hero_json, custom_css, status, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $title, $slug, $meta_description, $meta_keyword, $page_heading, $page_content, $hero_json, $custom_css, $status, $type);
            if ($stmt->execute()) {
                $message = 'Custom Page created successfully!';
                $message_type = 'success';
                $action = 'list';
            } else {
                $message = 'Failed to create page: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($post_action === 'edit') {
        $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : (isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0);
        $title = trim($_POST['title'] ?? '');
        $raw_slug = !empty($_POST['slug']) ? $_POST['slug'] : $title;
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $raw_slug), '-'));
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keyword = trim($_POST['meta_keyword'] ?? '');
        $page_heading = trim($_POST['page_heading'] ?? '');
        $page_content = trim($_POST['page_content'] ?? '');
        $custom_css = trim($_POST['custom_css'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $type = trim($_POST['type'] ?? 'page');
        if (empty($type)) {
            $type = 'page';
        }

        // Parse Hero Section details
        $hero_active = isset($_POST['hero_active']) && $_POST['hero_active'] === '1' ? 1 : 0;
        $hero_title = trim($_POST['hero_title'] ?? '');
        $hero_subtitle = trim($_POST['hero_subtitle'] ?? '');
        $hero_description = trim($_POST['hero_description'] ?? '');
        $hero_btn_text = trim($_POST['hero_btn_text'] ?? '');
        $hero_btn_link = trim($_POST['hero_btn_link'] ?? '');
        $hero_btn_color = trim($_POST['hero_btn_color'] ?? '#38b6ff');

        $existing_hero_image = trim($_POST['existing_hero_image'] ?? '');
        $hero_image = handleSingleUpload($_FILES['hero_image'] ?? null, $existing_hero_image, 'hero');

        $hero_json = json_encode([
            'hero_active' => $hero_active,
            'hero_image' => $hero_image,
            'hero_title' => $hero_title,
            'hero_subtitle' => $hero_subtitle,
            'hero_description' => $hero_description,
            'hero_btn_text' => $hero_btn_text,
            'hero_btn_link' => $hero_btn_link,
            'hero_btn_color' => $hero_btn_color
        ]);

        if (empty($title) || $edit_id <= 0) {
            $message = 'Title and valid Page ID are required.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE notices SET title = ?, slug = ?, meta_description = ?, meta_keyword = ?, page_heading = ?, page_content = ?, hero_json = ?, custom_css = ?, status = ?, type = ? WHERE id = ?");
            $stmt->bind_param("ssssssssssi", $title, $slug, $meta_description, $meta_keyword, $page_heading, $page_content, $hero_json, $custom_css, $status, $type, $edit_id);
            if ($stmt->execute()) {
                $message = 'Custom Page updated successfully!';
                $message_type = 'success';
                $action = 'list';
            } else {
                $message = 'Failed to update page: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($post_action === 'delete') {
        $delete_id = intval($_POST['delete_id'] ?? 0);
        if ($delete_id > 0) {
            $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $message = 'Custom Page deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete page: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Fetch Item Details if Editing
$edit_item = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit_item) {
        $message = 'Requested page was not found.';
        $message_type = 'error';
        $action = 'list';
    }
}



// Pagination & Search logic
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$where_clause = " WHERE (type = 'page' OR type = '' OR type IS NULL)";
if ($search !== '') {
    $escaped = $conn->real_escape_string($search);
    $where_clause .= " AND (title LIKE '%$escaped%' OR page_heading LIKE '%$escaped%' OR slug LIKE '%$escaped%')";
}
if ($filter_status !== '') {
    $escaped_status = $conn->real_escape_string($filter_status);
    $where_clause .= " AND status = '$escaped_status'";
}

// Total records count
$count_query = "SELECT COUNT(*) as total FROM notices" . $where_clause;
$count_result = $conn->query($count_query);
$total_rows = 0;
if ($count_result && $row = $count_result->fetch_assoc()) {
    $total_rows = intval($row['total']);
}

$total_pages = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
}
$offset = ($page - 1) * $per_page;

// Fetch paginated records (10 per page)
$query = "SELECT * FROM notices" . $where_clause . " ORDER BY id DESC LIMIT $offset, $per_page";
$items_result = $conn->query($query);
$items = [];
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Build query parameter prefix for pagination links
$query_params = $_GET;
unset($query_params['page']);
$base_query_string = http_build_query($query_params);
$link_prefix = '?' . ($base_query_string ? $base_query_string . '&' : '');
?>

<div class="w-full space-y-3 sm:space-y-4 pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Custom Pages</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage custom content pages, URLs, titles, and layouts served across the frontend.</p>
        </div>
        <div>
            <?php if ($action === 'list'): ?>
                <a href="?action=create" class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-primary text-white text-xs sm:text-sm font-bold rounded-lg shadow-sm shadow-primary/20 hover:bg-primary/95 transition-all">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Create Custom Page</span>
                </a>
            <?php else: ?>
                <a href="?action=list" class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-50 transition-all shadow-xs">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    <span>Back to List</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if ($message): ?>
        <div id="status-alert" class="p-3 sm:p-3.5 rounded-xl flex items-center gap-3 border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400' ?>">
            <span class="material-symbols-outlined shrink-0 text-lg"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
            <span class="font-medium text-xs sm:text-sm flex-1"><?= htmlspecialchars($message) ?></span>
            <button type="button" onclick="document.getElementById('status-alert').remove()" class="ml-auto flex items-center justify-center p-1 rounded-lg text-slate-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-slate-600 dark:hover:text-slate-350 transition-colors shrink-0" title="Dismiss Alert">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- LIST MODE -->
    <?php if ($action === 'list'): ?>
        <div class="flex w-full flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-slate-800">
            <!-- Search & Filters -->
            <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <form method="GET" action="" class="m-0 flex flex-col sm:flex-row gap-2.5 w-full lg:max-w-3xl">
                    <!-- Text Search -->
                    <div class="relative flex-1">
                        <span class="material-symbols-outlined absolute left-3 top-2 text-[18px] text-slate-400">search</span>
                        <input name="search" value="<?= htmlspecialchars($search) ?>" class="h-8.5 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 pl-9 pr-4 text-xs sm:text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Search page title, slug, or heading..." type="text"/>
                    </div>

                    <!-- Status Filter -->
                    <select name="status" onchange="this.form.submit()" class="h-8.5 min-w-[140px] sm:min-w-[160px] px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs sm:text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary outline-none transition-all cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>

                    <?php if ($search || $filter_status): ?>
                        <a href="?action=list" class="h-8.5 px-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-lg flex items-center justify-center transition-all shrink-0">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Responsive Table View with Touch Scroll -->
            <div class="w-full overflow-x-auto custom-scrollbar touch-pan-x" style="-webkit-overflow-scrolling: touch;">
                <table class="w-full min-w-[640px] border-collapse text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                            <th class="md:sticky md:left-0 bg-slate-50 dark:bg-slate-900 px-4 py-3 min-w-[180px] sm:min-w-[220px] font-semibold text-slate-900 dark:text-white shadow-[1px_0_0_0_rgba(0,0,0,0.05)] z-10">
                                Title / Heading
                            </th>
                            <th class="px-4 py-3 font-semibold min-w-[120px]">Page Slug</th>
                            <th class="px-4 py-3 font-semibold min-w-[100px]">Status</th>
                            <th class="px-4 py-3 font-semibold min-w-[130px]">Created Date</th>
                            <th class="md:sticky md:right-0 bg-slate-50 dark:bg-slate-900 px-4 py-3 min-w-[110px] text-right font-semibold text-slate-900 dark:text-white shadow-[-4px_0_4px_-2px_rgba(0,0,0,0.05)] z-10">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-400">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-700 mb-1.5">web_stories</span>
                                    <p class="text-xs sm:text-sm">No custom pages found matching criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="group hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="md:sticky md:left-0 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 px-4 py-3 shadow-[1px_0_0_0_rgba(0,0,0,0.05)]">
                                        <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($item['title']) ?></div>
                                        <?php if (!empty($item['page_heading'])): ?>
                                            <div class="text-xs text-slate-400 truncate max-w-[240px] mt-0.5"><?= htmlspecialchars($item['page_heading']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs font-semibold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded">/<?= htmlspecialchars($item['slug'] ?? '') ?></code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $item['status'] === 'active' ? 'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400' ?>">
                                            <?= ucfirst($item['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                    </td>
                                    <td class="md:sticky md:right-0 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 px-4 py-3 text-right shadow-[-4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="flex items-center justify-end gap-1">
                                            <?php
                                            $item_json = json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                                            ?>
                                            <button type="button" onclick='openDynamicPageInfoModal(<?= $item_json ?>)' class="p-1.5 text-slate-400 hover:text-amber-500 transition-colors" title="View Page Info & Metadata">
                                                <span class="material-symbols-outlined text-[18px]">info</span>
                                            </button>
                                            <a href="<?= $frontend_url ?>/<?= htmlspecialchars(ltrim($item['slug'], '/')) ?>" target="_blank" rel="noopener noreferrer" class="p-1.5 text-slate-400 hover:text-green-600 transition-colors" title="View Page (Opens in new tab)">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            </a>
                                            <button type="button" onclick="copyPageLink('<?= $frontend_url ?>/<?= htmlspecialchars(ltrim($item['slug'], '/')) ?>')" class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors" title="Copy Page Link">
                                                <span class="material-symbols-outlined text-[18px]">content_copy</span>
                                            </button>
                                            <a href="?action=edit&edit_id=<?= $item['id'] ?>" class="p-1.5 text-slate-400 hover:text-primary transition-colors" title="Edit Page">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </a>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this page?');" class="inline m-0">
                                                <input type="hidden" name="action" value="delete"/>
                                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>"/>
                                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-650 transition-colors" title="Delete Page">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Navigation Bar -->
            <?php if ($total_rows > 0): ?>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200 dark:border-slate-800 px-4 py-3 bg-slate-50/50 dark:bg-slate-900/50">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        Showing <span class="font-bold text-slate-900 dark:text-white"><?= $total_rows > 0 ? ($offset + 1) : 0 ?></span> to <span class="font-bold text-slate-900 dark:text-white"><?= min($offset + $per_page, $total_rows) ?></span> of <span class="font-bold text-slate-900 dark:text-white"><?= $total_rows ?></span> entries
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="flex items-center gap-1">
                            <?php if ($page > 1): ?>
                                <a href="<?= $link_prefix ?>page=<?= $page - 1 ?>" class="flex size-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Previous Page">
                                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                                </a>
                            <?php else: ?>
                                <span class="flex size-8 items-center justify-center rounded-lg border border-slate-100 dark:border-slate-800/60 bg-slate-50 dark:bg-slate-900 text-slate-300 dark:text-slate-700 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                                </span>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<a href="' . $link_prefix . 'page=1" class="flex size-8 items-center justify-center rounded-lg border border-transparent text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="px-1 text-slate-400 text-xs">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++):
                                if ($i == $page): ?>
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary text-xs font-bold text-white shadow-sm"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= $link_prefix ?>page=<?= $i ?>" class="flex size-8 items-center justify-center rounded-lg border border-transparent text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"><?= $i ?></a>
                                <?php endif;
                            endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="px-1 text-slate-400 text-xs">...</span>';
                                }
                                echo '<a href="' . $link_prefix . 'page=' . $total_pages . '" class="flex size-8 items-center justify-center rounded-lg border border-transparent text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">' . $total_pages . '</a>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?= $link_prefix ?>page=<?= $page + 1 ?>" class="flex size-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Next Page">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </a>
                            <?php else: ?>
                                <span class="flex size-8 items-center justify-center rounded-lg border border-slate-100 dark:border-slate-800/60 bg-slate-50 dark:bg-slate-900 text-slate-300 dark:text-slate-700 cursor-not-allowed">
                                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    <!-- CREATE OR EDIT MODE -->
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <?php
        $is_edit = ($action === 'edit');
        
        $hero_settings = [
            'hero_active' => 0,
            'hero_image' => '',
            'hero_title' => '',
            'hero_subtitle' => '',
            'hero_description' => '',
            'hero_btn_text' => '',
            'hero_btn_link' => '',
            'hero_btn_color' => '#38b6ff'
        ];

        if ($is_edit && !empty($edit_item['hero_json'])) {
            $decoded_hero = json_decode($edit_item['hero_json'], true);
            if (is_array($decoded_hero)) {
                $hero_settings = array_replace($hero_settings, $decoded_hero);
            }
        }
        ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-4 sm:px-6 py-3.5 sm:py-4">
                <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white"><?= $is_edit ? 'Edit Custom Page' : 'Create New Custom Page' ?></h2>
            </div>
            
            <form id="dynamic_page_form" method="POST" action="" enctype="multipart/form-data" novalidate onsubmit="tinymce.triggerSave()" class="p-4 sm:p-6 space-y-6 sm:space-y-8">
                <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'create' ?>"/>
                <?php if ($is_edit): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit_id ?>"/>
                <?php endif; ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($is_edit ? (!empty($edit_item['type']) ? $edit_item['type'] : 'page') : ($_GET['type'] ?? $_POST['type'] ?? 'page')) ?>"/>

                <!-- Section 1: Page Information -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-base sm:text-lg">info</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Information</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Title -->
                        <div class="space-y-1">
                            <label for="title" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Title *</label>
                            <input type="text" name="title" id="title" placeholder="e.g. Terms and Conditions"
                                   value="<?= $is_edit ? htmlspecialchars($edit_item['title']) : '' ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- Slug -->
                        <div class="space-y-1">
                            <label for="slug" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Page Slug *</label>
                            <input type="text" name="slug" id="slug" placeholder="e.g. terms-conditions"
                                   value="<?= $is_edit ? htmlspecialchars($edit_item['slug'] ?? '') : '' ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- Page Heading -->
                        <div class="space-y-1">
                            <label for="page_heading" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Page Heading</label>
                            <input type="text" name="page_heading" id="page_heading" placeholder="e.g. IACCS Terms and Conditions"
                                   value="<?= $is_edit ? htmlspecialchars($edit_item['page_heading'] ?? '') : '' ?>"
                                   class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- Status -->
                        <div class="space-y-1">
                            <label for="status" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Status</label>
                            <select name="status" id="status" class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-primary outline-none transition-all">
                                <option value="active" <?= ($is_edit && $edit_item['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($is_edit && $edit_item['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- SEO Meta Information -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 pt-2">
                        <!-- Meta Keywords -->
                        <div class="space-y-1">
                            <label for="meta_keyword" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Meta Keywords</label>
                            <textarea name="meta_keyword" id="meta_keyword" rows="3" placeholder="e.g. key1, key2, key3"
                                      class="w-full p-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= $is_edit ? htmlspecialchars($edit_item['meta_keyword'] ?? '') : '' ?></textarea>
                        </div>

                        <!-- Meta Description -->
                        <div class="space-y-1">
                            <label for="meta_description" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Meta Description</label>
                            <textarea name="meta_description" id="meta_description" rows="3" placeholder="SEO Description..."
                                      class="w-full p-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= $is_edit ? htmlspecialchars($edit_item['meta_description'] ?? '') : '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Hero Section Settings -->
                <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">campaign</span>
                            <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Hero Banner settings</h3>
                        </div>
                        <div class="flex items-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="hero_active" value="1" <?= $hero_settings['hero_active'] ? 'checked' : '' ?> class="sr-only peer">
                                <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-650 peer-checked:bg-primary"></div>
                                <span class="ms-3 text-sm font-semibold text-slate-700 dark:text-slate-300">Enable Hero Section</span>
                            </label>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Hero Background Image</label>
                            <div class="flex items-center gap-3">
                                <input type="file" name="hero_image" id="hero_image" accept="image/*" class="hidden" onchange="updateHeroImagePreview(this, 'hero_file_name', 'hero_img_preview', 'hero_img_box')" />
                                <label for="hero_image" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm cursor-pointer transition-colors shrink-0">
                                    <span class="material-symbols-outlined text-sm">upload</span>
                                    <span>Choose Image</span>
                                </label>
                                <span id="hero_file_name" class="text-xs text-slate-400 truncate">No file chosen</span>
                            </div>
                            <input type="hidden" name="existing_hero_image" value="<?= htmlspecialchars($hero_settings['hero_image']) ?>" />
                        </div>
                        <div id="hero_img_box" class="<?= !empty($hero_settings['hero_image']) ? '' : 'hidden' ?> relative w-full max-w-[200px] aspect-video border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                            <img id="hero_img_preview" src="<?= !empty($hero_settings['hero_image']) ? '../' . htmlspecialchars(ltrim($hero_settings['hero_image'], '/')) : '' ?>" class="w-full h-full object-cover" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                        <!-- Hero Title -->
                        <div class="space-y-1">
                            <label for="hero_title" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Hero Title</label>
                            <input type="text" name="hero_title" id="hero_title" placeholder="e.g. Welcome to our organization"
                                   value="<?= htmlspecialchars($hero_settings['hero_title']) ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- Hero Subtitle -->
                        <div class="space-y-1">
                            <label for="hero_subtitle" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Hero Subtitle</label>
                            <input type="text" name="hero_subtitle" id="hero_subtitle" placeholder="e.g. Standards of Excellence"
                                   value="<?= htmlspecialchars($hero_settings['hero_subtitle']) ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>
                    </div>

                    <!-- Hero Description -->
                    <div class="space-y-1 pt-4">
                        <label for="hero_description" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Hero Description Text</label>
                        <textarea name="hero_description" id="hero_description" rows="3" placeholder="Introductory hero text description..."
                                  class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= htmlspecialchars($hero_settings['hero_description']) ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                        <!-- CTA button label -->
                        <div class="space-y-1">
                            <label for="hero_btn_text" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">CTA Button Label</label>
                            <input type="text" name="hero_btn_text" id="hero_btn_text" placeholder="e.g. Read More"
                                   value="<?= htmlspecialchars($hero_settings['hero_btn_text']) ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- CTA button URL -->
                        <div class="space-y-1">
                            <label for="hero_btn_link" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">CTA URL</label>
                            <input type="text" name="hero_btn_link" id="hero_btn_link" placeholder="e.g. /membership"
                                   value="<?= htmlspecialchars($hero_settings['hero_btn_link']) ?>"
                                   class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"/>
                        </div>

                        <!-- CTA button Color -->
                        <div class="space-y-1">
                            <label for="hero_btn_color" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">CTA Button Color</label>
                            <input type="color" name="hero_btn_color" id="hero_btn_color"
                                   value="<?= htmlspecialchars($hero_settings['hero_btn_color']) ?>"
                                   class="w-full h-11 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent cursor-pointer transition-all"/>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Page Content -->
                <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">article</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Rich Content</h3>
                    </div>
                    <div class="space-y-2">
                        <textarea name="page_content" id="page_content" rows="12" placeholder="HTML and text content for the page..."
                                  class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= $is_edit ? htmlspecialchars($edit_item['page_content'] ?? '') : '' ?></textarea>
                    </div>
                </div>

                <!-- Section 4: Custom Stylesheet -->
                <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">css</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Custom Stylesheet</h3>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Custom CSS Rules (Applied dynamically to page)</label>
                        <textarea name="custom_css" id="custom_css" rows="6" placeholder="/* Custom page specific styling */"
                                  class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= $is_edit ? htmlspecialchars($edit_item['custom_css'] ?? '') : '' ?></textarea>
                    </div>
                </div>
            </form>
        </div>

        <!-- Form Controls (Sticky Footer Bar) -->
        <div class="fixed bottom-0 left-64 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-center shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.03),0_-2px_4px_-1px_rgba(0,0,0,0.02)]">
            <div class="w-full flex justify-end gap-3">
                <a href="?action=list" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-lg transition-all flex items-center justify-center">
                    Cancel
                </a>
                <button type="submit" form="dynamic_page_form" class="px-8 py-3 bg-primary text-white text-sm font-bold rounded-lg shadow-md shadow-primary/20 hover:bg-primary/95 hover:scale-[1.01] active:scale-[0.99] transition-all">
                    Save
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#page_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | code removeformat',
        height: 480,
        menubar: true,
        branding: false,
        promotion: false,
        setup: function (editor) {
            editor.on('change', function () {
                tinymce.triggerSave();
            });
        }
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

    function updateHeroImagePreview(input, nameSpanId, previewImgId, previewBoxId) {
        updateFileName(input, nameSpanId);
        const box = document.getElementById(previewBoxId);
        const img = document.getElementById(previewImgId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (img) img.src = e.target.result;
                if (box) box.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function copyPageLink(url) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Page link copied to clipboard!\n\n' + url);
            }).catch(() => {
                prompt('Copy page link:', url);
            });
        } else {
            prompt('Copy page link:', url);
        }
    }

    function openDynamicPageInfoModal(item) {
        if (!item) return;
        
        document.getElementById('infoModalTitle').textContent = item.title || 'Page Details';
        document.getElementById('infoTitle').textContent = item.title || '-';
        document.getElementById('infoSlug').textContent = '/' + (item.slug || '');
        document.getElementById('infoHeading').textContent = item.page_heading || '-';
        document.getElementById('infoMetaDesc').textContent = item.meta_description || 'None provided';
        document.getElementById('infoMetaKeys').textContent = item.meta_keyword || 'None provided';
        document.getElementById('infoType').textContent = item.type || 'page';

        const statusEl = document.getElementById('infoStatus');
        if (item.status === 'active') {
            statusEl.className = 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400';
            statusEl.textContent = 'Active';
        } else {
            statusEl.className = 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400';
            statusEl.textContent = 'Inactive';
        }

        const pageUrl = window.location.origin + '/' + (item.slug ? item.slug.replace(/^\/+/, '') : '');
        const liveLinkEl = document.getElementById('infoLiveLink');
        liveLinkEl.href = pageUrl;
        liveLinkEl.textContent = pageUrl;

        // Hero details
        let heroHtml = '<div class="text-slate-400 font-medium">Hero Section is Disabled</div>';
        if (item.hero_json) {
            try {
                const hero = typeof item.hero_json === 'string' ? JSON.parse(item.hero_json) : item.hero_json;
                const isActive = hero.hero_active == 1 || hero.hero_active == '1' || hero.hero_active === true;
                if (isActive) {
                    let imagePreview = '';
                    if (hero.hero_image) {
                        const imgUrl = (hero.hero_image.startsWith('http://') || hero.hero_image.startsWith('https://') || hero.hero_image.startsWith('/')) 
                            ? hero.hero_image 
                            : window.location.origin + '/' + hero.hero_image.replace(/^\/+/, '');
                        imagePreview = `<div class="mt-2 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 max-h-32"><img src="${imgUrl}" class="w-full h-24 object-cover" alt="Hero Banner Preview"/></div>`;
                    }
                    heroHtml = `
                        <div class="space-y-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Hero Active</span>
                            ${hero.hero_title ? `<p><strong>Title:</strong> ${hero.hero_title}</p>` : ''}
                            ${hero.hero_subtitle ? `<p><strong>Subtitle:</strong> ${hero.hero_subtitle}</p>` : ''}
                            ${hero.hero_description ? `<p class="line-clamp-2"><strong>Desc:</strong> ${hero.hero_description}</p>` : ''}
                            ${hero.hero_btn_text ? `<p><strong>Button:</strong> ${hero.hero_btn_text} (${hero.hero_btn_link || '#'})</p>` : ''}
                            ${imagePreview}
                        </div>
                    `;
                }
            } catch(e) {}
        }
        document.getElementById('infoHeroDetails').innerHTML = heroHtml;

        const modal = document.getElementById('dynamicPageInfoModal');
        modal.classList.remove('hidden');
    }

    function closeDynamicPageInfoModal() {
        const modal = document.getElementById('dynamicPageInfoModal');
        modal.classList.add('hidden');
    }
</script>

<!-- Dynamic Page Info Modal Markup -->
<div id="dynamicPageInfoModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-opacity">
    <div class="bg-white dark:bg-slate-900 w-full max-w-xl rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500">info</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white" id="infoModalTitle">Page Details</h3>
            </div>
            <button type="button" onclick="closeDynamicPageInfoModal()" class="p-1 rounded-lg text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-600 transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto text-sm text-slate-700 dark:text-slate-300">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Page Title</span>
                    <span class="font-bold text-slate-900 dark:text-white" id="infoTitle">-</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Page Slug</span>
                    <code class="text-xs font-mono bg-white dark:bg-slate-900 px-2 py-0.5 rounded border text-primary" id="infoSlug">/</code>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Status</span>
                    <span id="infoStatus">-</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Type</span>
                    <span class="font-medium" id="infoType">page</span>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Page Heading</span>
                <span class="font-medium text-slate-800 dark:text-slate-200" id="infoHeading">-</span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Meta Description</span>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed" id="infoMetaDesc">-</p>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Meta Keywords</span>
                <p class="text-xs text-slate-600 dark:text-slate-400" id="infoMetaKeys">-</p>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">view_carousel</span> Hero Banner Configuration
                </h4>
                <div id="infoHeroDetails" class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800 text-xs space-y-2">
                    <!-- Dynamic Hero Details -->
                </div>
            </div>

            <div class="bg-slate-100 dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-500 block font-medium">Live URL:</span>
                    <a id="infoLiveLink" href="#" target="_blank" class="text-xs font-semibold text-primary hover:underline break-all"></a>
                </div>
                <button type="button" onclick="copyPageLink(document.getElementById('infoLiveLink').href)" class="px-3 py-1.5 bg-primary text-white font-semibold rounded-lg text-xs hover:bg-primary/90 transition-colors shrink-0">
                    Copy Link
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
            <button type="button" onclick="closeDynamicPageInfoModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-white rounded-xl text-xs font-semibold transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
