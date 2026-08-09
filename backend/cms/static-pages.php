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

// Check and add missing columns in 'cms_pages' table to support Dynamic Sections
$pages_cols = [
    'heading' => 'VARCHAR(255) NULL AFTER title',
    'subheading' => 'TEXT NULL AFTER heading',
    'btn_text' => 'VARCHAR(100) NULL AFTER subheading',
    'btn_link' => 'VARCHAR(255) NULL AFTER btn_text',
    'custom_css' => 'TEXT NULL AFTER content'
];

foreach ($pages_cols as $col_name => $col_definition) {
    $check_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE '$col_name'");
    if ($check_col->num_rows === 0) {
        $conn->query("ALTER TABLE cms_pages ADD COLUMN $col_name $col_definition");
    }
}

// Seed standard pages if they don't exist
$standard_pages = [
    [
        'title' => 'Home',
        'slug' => 'home',
        'heading' => 'Welcome to ACCS The Association for Critical Care Sciences',
        'subheading' => 'RECOGNITION . STANDARDS . EXCELLENCE .',
        'btn_text' => 'JOIN US TODAY',
        'btn_link' => '/membership',
        'content' => '<p>ACCS is dedicated to advancing clinical excellence, promoting education, and strengthening the future workforce in Critical Care Science. Together, we work for recognition, standardization, and growth of our profession.</p>',
        'status' => 'published'
    ],
    [
        'title' => 'Notices & Announcements',
        'slug' => 'notices-announcements',
        'heading' => 'Notices & Announcements',
        'subheading' => 'Stay updated with latest announcements',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Membership',
        'slug' => 'membership',
        'heading' => 'Membership Registration',
        'subheading' => 'Join the critical care sciences network',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ],
    [
        'title' => 'Application Status Check',
        'slug' => 'membership-status',
        'heading' => '',
        'subheading' => 'Enter your Membership ID / Reference ID and Date of Birth to check your status.',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ],
    [
        'title' => 'About Us',
        'slug' => 'about-us',
        'heading' => 'About Us',
        'subheading' => 'Delivering Free Healthcare to Underserved Communities Worldwide',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '<p>The Association for Critical Care Sciences (ACCS) was founded to represent, strengthen, and advance the discipline of Critical Care Sciences in India. As an association, we work to foster collaboration between healthcare delivery systems, universities, government authorities, regulatory bodies, and industry stakeholders.</p>',
        'status' => 'published'
    ],
    [
        'title' => 'Contact Us',
        'slug' => 'contact-us',
        'heading' => 'Contact Us',
        'subheading' => 'Get in touch with us today',
        'btn_text' => '',
        'btn_link' => '',
        'content' => '',
        'status' => 'published'
    ]
];

foreach ($standard_pages as $sp) {
    $check_page = $conn->prepare("SELECT id FROM cms_pages WHERE slug = ?");
    $check_page->bind_param("s", $sp['slug']);
    $check_page->execute();
    $check_page->store_result();
    if ($check_page->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO cms_pages (title, slug, heading, subheading, btn_text, btn_link, content, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $sp['title'], $sp['slug'], $sp['heading'], $sp['subheading'], $sp['btn_text'], $sp['btn_link'], $sp['content'], $sp['status']);
        $stmt->execute();
        $stmt->close();
    }
    $check_page->close();
}

$action = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;

// Resolve edit_id from slug if edit_id is not specified
if ($edit_id <= 0 && isset($_GET['slug'])) {
    $stmt_resolve = $conn->prepare("SELECT id FROM cms_pages WHERE slug = ?");
    $slug_resolve = trim($_GET['slug']);
    $stmt_resolve->bind_param("s", $slug_resolve);
    $stmt_resolve->execute();
    $stmt_resolve->bind_result($resolved_id);
    if ($stmt_resolve->fetch()) {
        $edit_id = intval($resolved_id);
    }
    $stmt_resolve->close();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// System page slugs that cannot be deleted
$system_slugs = ['home', 'notices-announcements', 'membership', 'membership-status', 'about-us', 'contact-us'];

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $heading = trim($_POST['heading'] ?? '');
        $subheading = trim($_POST['subheading'] ?? '');
        $btn_text = '';
        $btn_link = '';
        $content = trim($_POST['content'] ?? '');
        $custom_css = trim($_POST['custom_css'] ?? '');
        $status = 'published';
        $meta_description = '';

        // Auto-generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        if (empty($title) || empty($slug)) {
            $_SESSION['message'] = 'Title is a required field.';
            $_SESSION['message_type'] = 'error';
            header("Location: static-pages.php?action=create");
            exit();
        } else {
            // Check for duplicate slug
            $check = $conn->prepare("SELECT id FROM cms_pages WHERE slug = ?");
            $check->bind_param("s", $slug);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $_SESSION['message'] = "The slug '{$slug}' is already in use by another page.";
                $_SESSION['message_type'] = 'error';
                header("Location: static-pages.php?action=create");
                exit();
            } else {
                $stmt = $conn->prepare("INSERT INTO cms_pages (title, slug, heading, subheading, btn_text, btn_link, content, custom_css, status, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $title, $slug, $heading, $subheading, $btn_text, $btn_link, $content, $custom_css, $status, $meta_description);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Static page created successfully!';
                    $_SESSION['message_type'] = 'success';
                    header("Location: static-pages.php?action=list");
                    exit();
                } else {
                    $_SESSION['message'] = 'Failed to create page: ' . $conn->error;
                    $_SESSION['message_type'] = 'error';
                    header("Location: static-pages.php?action=create");
                    exit();
                }
                $stmt->close();
            }
            $check->close();
        }
    } elseif ($post_action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $heading = trim($_POST['heading'] ?? '');
        $subheading = trim($_POST['subheading'] ?? '');
        $btn_text = '';
        $btn_link = '';
        $content = trim($_POST['content'] ?? '');
        $custom_css = trim($_POST['custom_css'] ?? '');
        $status = 'published';
        $meta_description = '';

        if (empty($title) || $edit_id <= 0) {
            $_SESSION['message'] = 'Title and valid Page ID are required.';
            $_SESSION['message_type'] = 'error';
            header("Location: static-pages.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
            exit();
        } else {
            $stmt = $conn->prepare("UPDATE cms_pages SET title = ?, heading = ?, subheading = ?, btn_text = ?, btn_link = ?, content = ?, custom_css = ?, status = ?, meta_description = ? WHERE id = ?");
            $stmt->bind_param("sssssssssi", $title, $heading, $subheading, $btn_text, $btn_link, $content, $custom_css, $status, $meta_description, $edit_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Static page updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to update page: ' . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
            header("Location: static-pages.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
            exit();
        }
    } elseif ($post_action === 'delete') {
        $delete_id = intval($_POST['delete_id'] ?? 0);
        if ($delete_id > 0) {
            // Confirm it's not a system page
            $check_sys = $conn->prepare("SELECT slug FROM cms_pages WHERE id = ?");
            $check_sys->bind_param("i", $delete_id);
            $check_sys->execute();
            $slug_res = $check_sys->get_result()->fetch_assoc();
            $check_sys->close();

            if ($slug_res && in_array($slug_res['slug'], $system_slugs)) {
                $_SESSION['message'] = 'Standard system pages cannot be deleted.';
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM cms_pages WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Static page deleted successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Failed to delete page: ' . $conn->error;
                    $_SESSION['message_type'] = 'error';
                }
                $stmt->close();
            }
        }
        header("Location: static-pages.php?action=list");
        exit();
    }
}

// Fetch Page Details if Editing
$edit_page = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cms_pages WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_page = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit_page) {
        $_SESSION['message'] = 'Requested page was not found.';
        $_SESSION['message_type'] = 'error';
        header("Location: static-pages.php?action=list");
        exit();
    } else {
        $slug = strtolower(trim($edit_page['slug']));
        if ($slug === 'home') {
            header("Location: static-page-form/home-form.php");
            exit();
        } elseif ($slug === 'about-us') {
            header("Location: static-page-form/about-us-form.php");
            exit();
        } elseif ($slug === 'contact-us') {
            header("Location: static-page-form/contact-us-form.php");
            exit();
        } elseif ($slug === 'notices-announcements') {
            header("Location: static-page-form/notices-announcements-form.php");
            exit();
        } elseif ($slug === 'membership') {
            header("Location: static-page-form/membership-form.php");
            exit();
        }
    }
}


$page_title = 'Static Pages';
include 'include/header.php';

// Pagination & Search logic
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$search = trim($_GET['search'] ?? '');
$where_clause = "";
if ($search !== '') {
    $escaped = $conn->real_escape_string($search);
    $where_clause = " WHERE title LIKE '%$escaped%' OR slug LIKE '%$escaped%' OR heading LIKE '%$escaped%'";
}

// Total records count
$count_query = "SELECT COUNT(*) as total FROM cms_pages" . $where_clause;
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
$query = "SELECT * FROM cms_pages" . $where_clause . " ORDER BY id ASC LIMIT $offset, $per_page";
$pages_result = $conn->query($query);
$pages = [];
if ($pages_result) {
    while ($row = $pages_result->fetch_assoc()) {
        $pages[] = $row;
    }
}

// Build query parameter prefix for pagination links
$query_params = $_GET;
unset($query_params['page']);
$base_query_string = http_build_query($query_params);
$link_prefix = '?' . ($base_query_string ? $base_query_string . '&' : '');
?>

<div class="max-w-6xl mx-auto space-y-3 sm:space-y-4 pb-16">
    <!-- Top Action Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Static Pages</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">Manage static website pages, content sections, and custom styling layouts.</p>
        </div>
        <div>
            <?php if ($action !== 'list'): ?>
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
            <div class="p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <form method="GET" action="" class="m-0 flex flex-col sm:flex-row items-center gap-2 relative w-full sm:max-w-md">
                    <div class="relative flex-1 w-full">
                        <span class="material-symbols-outlined absolute left-3 top-2 text-[18px] text-slate-400">search</span>
                        <input name="search" value="<?= htmlspecialchars($search) ?>" class="h-8.5 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 pl-9 pr-4 text-xs sm:text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary outline-none transition-all" placeholder="Search pages..." type="text" />
                    </div>
                    <?php if ($search): ?>
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
                            <th class="px-4 py-3 font-semibold min-w-[130px]">Last Updated</th>
                            <th class="md:sticky md:right-0 bg-slate-50 dark:bg-slate-900 px-4 py-3 min-w-[100px] text-right font-semibold text-slate-900 dark:text-white shadow-[-4px_0_4px_-2px_rgba(0,0,0,0.05)] z-10">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-400">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-700 mb-1.5">article</span>
                                    <p class="text-xs sm:text-sm">No pages found matching criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <?php $is_system = in_array($p['slug'], $system_slugs); ?>
                                <tr class="group hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="md:sticky md:left-0 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 px-4 py-3 shadow-[1px_0_0_0_rgba(0,0,0,0.05)]">
                                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                            <span><?= htmlspecialchars($p['title']) ?></span>
                                        </div>
                                        <?php if (!empty($p['heading'])): ?>
                                            <div class="text-xs text-slate-400 truncate max-w-[220px] mt-0.5"><?= htmlspecialchars($p['heading']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-xs font-mono">/<?= htmlspecialchars($p['slug']) ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $p['status'] === 'published' ? 'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400' : 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/20 dark:text-yellow-450' ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <?= date('M d, Y H:i', strtotime($p['updated_at'])) ?>
                                    </td>
                                    <td class="md:sticky md:right-0 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 px-4 py-3 text-right shadow-[-4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="http://localhost:8000/<?= htmlspecialchars(ltrim($p['slug'], '/')) ?>" target="_blank" rel="noopener noreferrer" class="p-1.5 text-slate-400 hover:text-green-600 transition-colors" title="View Page (Opens in new tab)">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            </a>
                                            <button type="button" onclick="copyPageLink('http://localhost:8000/<?= htmlspecialchars(ltrim($p['slug'], '/')) ?>')" class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors" title="Copy Page Link">
                                                <span class="material-symbols-outlined text-[18px]">content_copy</span>
                                            </button>
                                            <?php
                                            $custom_edit_link = "?action=edit&edit_id=" . $p['id'];
                                            $p_slug = strtolower(trim($p['slug']));
                                            if ($p_slug === 'home') {
                                                $custom_edit_link = "static-page-form/home-form.php";
                                            } elseif ($p_slug === 'about-us') {
                                                $custom_edit_link = "static-page-form/about-us-form.php";
                                            } elseif ($p_slug === 'contact-us') {
                                                $custom_edit_link = "static-page-form/contact-us-form.php";
                                            } elseif ($p_slug === 'notices-announcements') {
                                                $custom_edit_link = "static-page-form/notices-announcements-form.php";
                                            } elseif ($p_slug === 'membership') {
                                                $custom_edit_link = "static-page-form/membership-form.php";
                                            }
                                            ?>
                                            <a href="<?= $custom_edit_link ?>" class="p-1.5 text-slate-400 hover:text-primary transition-colors" title="Edit Page">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </a>
                                            <?php if (!$is_system): ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this static page?');" class="inline m-0">
                                                    <input type="hidden" name="action" value="delete" />
                                                    <input type="hidden" name="delete_id" value="<?= $p['id'] ?>" />
                                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-red-650 transition-colors" title="Delete Page">
                                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="p-1.5 text-slate-200 dark:text-slate-800 cursor-not-allowed" title="System Pages Cannot Be Deleted" disabled>
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            <?php endif; ?>
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
        $is_system = ($is_edit && in_array($edit_page['slug'], $system_slugs));
        ?>
        <form id="static_page_form" method="POST" action="" novalidate onsubmit="tinymce.triggerSave()">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-4 sm:px-6 py-3.5 sm:py-4 flex items-center justify-between">
                    <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white"><?= $is_edit ? 'Edit Page Content' : 'Create New Static Page' ?></h2>
                </div>

                <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
                    <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'create' ?>" />

                    <!-- Title -->
                    <div class="space-y-1">
                        <label for="title" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Page Title *</label>
                        <input type="text" name="title" id="title" placeholder="e.g. Terms and Conditions"
                            value="<?= $is_edit ? htmlspecialchars($edit_page['title']) : '' ?>"
                            class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Page Heading -->
                        <div class="space-y-1">
                            <label for="heading" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Page Heading (Text Overlay)</label>
                            <input type="text" name="heading" id="heading" placeholder="Main header title overlay"
                                value="<?= $is_edit ? htmlspecialchars($edit_page['heading'] ?? '') : '' ?>"
                                class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>

                        <!-- Page Subheading / Short text description -->
                        <div class="space-y-1">
                            <label for="subheading" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Page Subheading / Text section</label>
                            <input type="text" name="subheading" id="subheading" placeholder="Sub-heading description overlay"
                                value="<?= $is_edit ? htmlspecialchars($edit_page['subheading'] ?? '') : '' ?>"
                                class="w-full h-10 sm:h-11 px-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                    </div>

                    <!-- Content (TinyMCE) -->
                    <div class="space-y-2">
                        <label for="content" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Page Rich Content (HTML body)</label>
                        <textarea name="content" id="content" rows="12" placeholder="HTML and text content for the page..."
                            class="w-full p-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= $is_edit ? htmlspecialchars($edit_page['content']) : '' ?></textarea>
                    </div>

                    <!-- Custom Page CSS -->
                    <div class="space-y-2">
                        <label for="custom_css" class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">Custom CSS Styles (Optional)</label>
                        <textarea name="custom_css" id="custom_css" rows="6" placeholder="/* Custom page specific styling */&#10;.hero-section { background-color: #f0f4f8; }"
                            class="w-full p-3.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-xs sm:text-sm text-slate-900 dark:text-white font-mono focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"><?= $is_edit ? htmlspecialchars($edit_page['custom_css'] ?? '') : '' ?></textarea>
                    </div>

                </div>
            </div>
        </form>

        <!-- Form Controls (Responsive Sticky Footer Bar) -->
        <div class="fixed bottom-0 left-0 md:left-64 right-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-4 sm:px-6 py-3 border-t border-slate-200 dark:border-slate-800 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.03),0_-2px_4px_-1px_rgba(0,0,0,0.02)]">
            <div class="w-full max-w-6xl mx-auto flex items-center justify-end gap-2.5 sm:gap-3">
                <a href="?action=list" class="px-4 sm:px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-semibold rounded-xl transition-all flex items-center justify-center flex-1 sm:flex-none">
                    Cancel
                </a>
                <button type="submit" form="static_page_form" class="px-6 sm:px-8 py-2.5 bg-primary text-white text-xs sm:text-sm font-bold rounded-xl shadow-md shadow-primary/20 hover:bg-primary/95 active:scale-[0.99] transition-all flex items-center justify-center flex-1 sm:flex-none">
                    <?= $is_edit ? 'Save' : 'Create' ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | code removeformat',
        height: 480,
        menubar: true,
        branding: false,
        promotion: false,
        setup: function(editor) {
            editor.on('change', function() {
                tinymce.triggerSave();
            });
        }
    });

    // Autofill slug from title on typing
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    if (titleInput && slugInput && !slugInput.readOnly) {
        titleInput.addEventListener('input', function() {
            if (!slugInput.dataset.touched) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }
        });
        slugInput.addEventListener('change', function() {
            this.dataset.touched = true;
        });
    }
</script>

<script>
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
</script>
<?php include 'include/footer.php'; ?>