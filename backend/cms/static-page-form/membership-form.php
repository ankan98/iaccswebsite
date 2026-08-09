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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? 'Membership');
    $heading = trim($_POST['heading'] ?? 'IACCS Membership');
    $subheading = trim($_POST['subheading'] ?? 'Join The Association for Critical Care Sciences');
    $content = trim($_POST['content'] ?? '');
    $custom_css = trim($_POST['custom_css'] ?? '');
    $type = trim($_POST['type'] ?? 'static');

    $stmt = $conn->prepare("UPDATE cms_pages SET title = ?, heading = ?, subheading = ?, content = ?, custom_css = ?, type = ? WHERE slug = 'membership' OR slug = 'membership-status' OR slug = 'application-status-check'");
    $stmt->bind_param("ssssss", $title, $heading, $subheading, $content, $custom_css, $type);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Membership Page updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update Membership Page: ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();

    header("Location: membership-form.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$page_title = 'Membership Custom Editor';
include '../include/header.php';

// Fetch page data
$stmt = $conn->prepare("SELECT * FROM cms_pages WHERE slug = 'membership' OR slug = 'membership-status' OR slug = 'application-status-check' LIMIT 1");
$stmt->execute();
$page_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Resolve existing details
$title = $page_data['title'] ?? 'Membership';
$heading = $page_data['heading'] ?? 'IACCS Membership';
$subheading = $page_data['subheading'] ?? 'Join The Association for Critical Care Sciences';
$content = $page_data['content'] ?? '';
$custom_css = $page_data['custom_css'] ?? '';
?>

<div class="max-w-6xl mx-auto space-y-6 pb-28">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Membership Visual Editor</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Easily configure page title, banner headings, intro content, and custom CSS for Membership.</p>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div id="status-alert" class="p-4 rounded-xl flex items-center gap-3 border shadow-sm transition-all duration-300 <?= $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900/50 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900/50 dark:text-red-400' ?>">
            <span class="material-symbols-outlined"><?= $message_type === 'success' ? 'check_circle' : 'error' ?></span>
            <span class="font-medium text-sm"><?= htmlspecialchars($message) ?></span>
            <button type="button" onclick="document.getElementById('status-alert').remove()" class="ml-auto flex items-center justify-center p-1 rounded-lg text-slate-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-slate-600 dark:hover:text-slate-350 transition-colors" title="Dismiss Alert">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <form id="membership_visual_form" method="POST" action="">
        <input type="hidden" name="type" value="<?= htmlspecialchars($page_data['type'] ?? 'static') ?>" />

        <!-- SINGLE CARD CONTAINER -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-6 py-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit Membership Page Content</h2>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- Section 1: Page Header Details -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">info</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Header Details</h3>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Page Title *</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($title) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                    </div>
                </div>

                <!-- Section 2: Banner Heading & Subheading -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">title</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Banner Heading & Subheading</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Page Heading (Banner Title)</label>
                            <input type="text" name="heading" value="<?= htmlspecialchars($heading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Page Subheading (Subtitle)</label>
                            <input type="text" name="subheading" value="<?= htmlspecialchars($subheading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Page Rich Content -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">article</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Intro/Instruction Content (Rich Text)</h3>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Description (Rich HTML editor - shows above membership form)</label>
                        <textarea id="membership_content" name="content" rows="8" class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary transition-all"><?= htmlspecialchars($content) ?></textarea>
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
            <button type="submit" form="membership_visual_form" class="px-8 py-3 bg-primary text-white text-sm font-bold rounded-lg shadow-md shadow-primary/20 hover:bg-primary/95 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Save
            </button>
        </div>
    </div>
</div>

<!-- TinyMCE Rich Text Editor script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#membership_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontsize | bold italic underline | link table | numlist bullist | code removeformat',
        height: 300,
        branding: false,
        promotion: false
    });
</script>

<?php include '../include/footer.php'; ?>
