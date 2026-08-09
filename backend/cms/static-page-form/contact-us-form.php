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
$check_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE 'contact_json'");
if ($check_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_pages ADD COLUMN contact_json LONGTEXT NULL AFTER about_json");
}

$check_type_col = $conn->query("SHOW COLUMNS FROM cms_pages LIKE 'type'");
if ($check_type_col->num_rows === 0) {
    $conn->query("ALTER TABLE cms_pages ADD COLUMN type ENUM('static', 'dynamic') NOT NULL DEFAULT 'static'");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Server-side Email Validation (Required & Email Format)
    if (empty($email)) {
        $_SESSION['message'] = 'Official Email Address is required and cannot be empty.';
        $_SESSION['message_type'] = 'error';
        header("Location: contact-us-form.php");
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Please enter a valid Official Email Address (e.g. admin@iaccs.org.in).';
        $_SESSION['message_type'] = 'error';
        header("Location: contact-us-form.php");
        exit();
    }

    // Collect settings for JSON payload
    $payload = [
        'email' => $email,
    ];

    $contact_json_str = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Core fields
    $title = trim($_POST['title'] ?? 'Contact Us');
    $heading = trim($_POST['heading'] ?? 'Head Office Address');
    $subheading = trim($_POST['subheading'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $custom_css = trim($_POST['custom_css'] ?? '');
    $type = trim($_POST['type'] ?? 'static');

    $stmt = $conn->prepare("UPDATE cms_pages SET title = ?, heading = ?, subheading = ?, content = ?, custom_css = ?, contact_json = ?, type = ? WHERE slug = 'contact-us'");
    $stmt->bind_param("sssssss", $title, $heading, $subheading, $content, $custom_css, $contact_json_str, $type);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Contact Us Page updated successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update Contact Us Page: ' . $conn->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();

    header("Location: contact-us-form.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$page_title = 'Contact Us Custom Editor';
include '../include/header.php';

// Fetch contact us page data
$stmt = $conn->prepare("SELECT * FROM cms_pages WHERE slug = 'contact-us' LIMIT 1");
$stmt->execute();
$contact_page = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Resolve existing details
$title = $contact_page['title'] ?? 'Contact Us';
$heading = $contact_page['heading'] ?? 'Head Office Address';
$subheading = $contact_page['subheading'] ?? 'Address: Mathkal, Nazrul Sarani, Dumdum Cantonment, Kolkata, 700065';
$content = $contact_page['content'] ?? '';
$custom_css = $contact_page['custom_css'] ?? '';

// Defaults for JSON fields
$defaults = [
    'email' => 'admin@iaccs.org.in',
];

$contact_json = [];
if (!empty($contact_page['contact_json'])) {
    $contact_json = json_decode($contact_page['contact_json'], true);
}
if (!is_array($contact_json)) {
    $contact_json = [];
}

// Merge with defaults
$config = array_replace_recursive($defaults, $contact_json);
?>

<div class="max-w-6xl mx-auto space-y-6 pb-28">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Contact Us Visual Editor</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Easily configure and manage all contact details and headings on the Contact Us page.</p>
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

    <form id="contact_visual_form" method="POST" action="" novalidate onsubmit="return validateContactForm(event);">
        <input type="hidden" name="type" value="<?= htmlspecialchars($contact_page['type'] ?? 'static') ?>" />

        <!-- SINGLE CARD CONTAINER -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 px-6 py-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit Contact Us Page Content</h2>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- Section 1: Page Header Details -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">info</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Header Details</h3>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Page Title</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($title) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                    </div>
                </div>

                <!-- Section 2: Office Address & Email Details -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">location_on</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Office Address & Email Details</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Address Section Heading</label>
                            <input type="text" name="heading" required value="<?= htmlspecialchars($heading) ?>" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Official Email Address *</label>
                            <input type="text" name="email" id="official_email" value="<?= htmlspecialchars($config['email']) ?>" placeholder="admin@iaccs.org.in" class="w-full h-11 px-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" />
                            <p id="email_error" class="hidden text-xs text-red-500 font-medium mt-1"></p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Full Head Office Address Details</label>
                        <textarea name="subheading" rows="3" required class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary transition-all"><?= htmlspecialchars($subheading) ?></textarea>
                    </div>
                </div>

                <!-- Section 3: Page Intro/Instruction Content -->
                <div class="space-y-4 pt-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="material-symbols-outlined text-primary text-lg">article</span>
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Page Intro/Instruction Content (Rich Text)</h3>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Description (Rich HTML editor - shows below title)</label>
                        <textarea id="contact_content" name="content" rows="8" class="w-full p-4 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary transition-all"><?= htmlspecialchars($content) ?></textarea>
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
            <button type="submit" form="contact_visual_form" class="px-8 py-3 bg-primary text-white text-sm font-bold rounded-lg shadow-md shadow-primary/20 hover:bg-primary/95 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Save
            </button>
        </div>
    </div>
</div>

<!-- TinyMCE Rich Text Editor script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#contact_content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontsize | bold italic underline | link table | numlist bullist | code removeformat',
        height: 300,
        branding: false,
        promotion: false
    });

    function validateContactForm(e) {
        const emailInput = document.getElementById('official_email');
        const errorElem = document.getElementById('email_error');
        if (!emailInput) return true;

        const emailValue = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailValue) {
            if (e) e.preventDefault();
            emailInput.classList.add('border-red-500', 'focus:ring-red-500');
            if (errorElem) {
                errorElem.textContent = 'Official Email Address is required and cannot be empty.';
                errorElem.classList.remove('hidden');
            }
            emailInput.focus();
            return false;
        }

        if (!emailRegex.test(emailValue)) {
            if (e) e.preventDefault();
            emailInput.classList.add('border-red-500', 'focus:ring-red-500');
            if (errorElem) {
                errorElem.textContent = 'Please enter a valid email address (e.g. admin@iaccs.org.in).';
                errorElem.classList.remove('hidden');
            }
            emailInput.focus();
            return false;
        }

        emailInput.classList.remove('border-red-500', 'focus:ring-red-500');
        if (errorElem) errorElem.classList.add('hidden');
        return true;
    }
</script>

<?php include '../include/footer.php'; ?>
