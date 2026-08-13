<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

$current_page = basename($_SERVER['PHP_SELF']);
$script_path = str_replace('\\', '/', $_SERVER['PHP_SELF']);

if (strpos($script_path, '/cms/static-page-form/') !== false) {
    $cms_root = '../';
    $backend_root = '../../';
} elseif (strpos($script_path, '/cms/') !== false || substr($script_path, -4) === '/cms') {
    $cms_root = '';
    $backend_root = '../';
} else {
    $cms_root = 'cms/';
    $backend_root = '';
}

// Session validation: check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: " . $backend_root . "login.php");
    exit();
}

// Role validation: admin users can ONLY access Membership Submissions!
$is_admin_restricted_page = (strpos($script_path, '/cms/') !== false || $current_page === 'notices-announcements-management.php');
if ($is_admin_restricted_page && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: " . $backend_root . "membership-submission.php");
    exit();
}

// Database Connection
require_once dirname(__DIR__, 2) . '/conn.php';
$conn = $GLOBALS['conn'] ?? (($conn instanceof mysqli) ? $conn : null);

// Fetch site logo from cms_settings
$cms_site_logo = '';
$cms_settings_res = ($conn && ($conn instanceof mysqli)) ? $conn->query("SELECT site_logo FROM cms_settings ORDER BY id ASC LIMIT 1") : false;
if ($cms_settings_res && $cms_settings_res->num_rows > 0) {
    $cms_settings_row = $cms_settings_res->fetch_assoc();
    if (!empty($cms_settings_row['site_logo'])) {
        $cms_site_logo = $cms_settings_row['site_logo'];
    }
}

$frontend_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ((isset($_SERVER['HTTP_HOST'])) ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) : 'https://iaccs.org.in');
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= isset($page_title) ? $page_title : 'ACCS Control Panel' ?> - ACCS</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { 
                        "primary": "#137fec",
                        "sidebar-dark": "#1e2d3d",
                        "sidebar-hover": "#2c3e50",
                        "background-light": "#f4f6f9",
                        "background-dark": "#101922"
                    },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                },
            },
        }
    </script>
    <style>
        html { font-size: 13px; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #475569;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-white flex h-screen overflow-hidden">

    <!-- Global CMS Page Loader -->
    <div id="cms-page-loader" class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-xl transition-all duration-300 pointer-events-auto">
        <div class="relative flex flex-col items-center gap-4">
            <div class="relative size-16 sm:size-20 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-[#38b6ff] border-r-[#38b6ff]/50 animate-spin"></div>
                <span class="font-bold text-white text-xs tracking-wider">ACCS</span>
            </div>
            <span class="text-xs text-slate-300 font-semibold tracking-widest uppercase">Loading...</span>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loader = document.getElementById('cms-page-loader');
                if (loader) {
                    loader.classList.add('opacity-0', 'pointer-events-none');
                    setTimeout(function() { loader.style.display = 'none'; }, 300);
                }
            }, 300);
        });
    </script>

    <!-- Sidebar Backdrop Mobile -->
    <div id="cms-sidebar-backdrop" onclick="toggleCmsSidebar()" class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs hidden md:hidden"></div>

    <!-- Sidebar -->
    <aside id="cms-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar-dark text-slate-300 flex flex-col shadow-lg border-r border-slate-700/30 shrink-0 transform -translate-x-full md:translate-x-0 md:static transition-transform duration-300 ease-in-out">
        <!-- Logo Area -->
        <div class="h-20 sm:h-22 flex items-center justify-between px-3 border-b border-slate-700/50 relative">
            <?php
            $display_logo_src = $backend_root . 'iaccslogo.png';
            if (!empty($cms_site_logo)) {
                $trimmed_logo = trim($cms_site_logo);
                if (strpos($trimmed_logo, 'http://') === 0 || strpos($trimmed_logo, 'https://') === 0) {
                    $display_logo_src = $trimmed_logo;
                } else {
                    $clean_logo = preg_replace('#^/?(cms/)?#i', '', $trimmed_logo);
                    $display_logo_src = $backend_root . ltrim($clean_logo, '/');
                }
            }
            ?>
            <div class="flex-1 flex items-center justify-center py-1 overflow-hidden">
                <img src="<?= $display_logo_src ?>" class="h-14 sm:h-16 max-h-[64px] max-w-[210px] object-contain" alt="Site Logo">
            </div>
            <button type="button" onclick="toggleCmsSidebar()" class="md:hidden absolute right-3 text-slate-400 hover:text-white p-1 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar">
            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'super_admin'): ?>
                <!-- Site Settings -->
                <a href="<?= $cms_root ?>general-settings.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all hover:bg-sidebar-hover hover:text-white group <?= ($current_page === 'general-settings.php' || $current_page === 'index.php') ? 'bg-primary text-white shadow-md' : 'text-slate-300' ?>">
                    <span class="material-symbols-outlined group-hover:scale-110 transition-transform">settings</span>
                    <span>Site Settings</span>
                </a>
                
                <!-- Manage Menus -->
                <a href="<?= $cms_root ?>manage-menus.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all hover:bg-sidebar-hover hover:text-white group <?= ($current_page === 'manage-menus.php') ? 'bg-primary text-white shadow-md' : 'text-slate-300' ?>">
                    <span class="material-symbols-outlined group-hover:scale-110 transition-transform">menu</span>
                    <span>Manage Menus</span>
                </a>
                
                <!-- Static Pages -->
                <a href="<?= $cms_root ?>static-pages.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all hover:bg-sidebar-hover hover:text-white group <?= ($current_page === 'static-pages.php' || $current_page === 'home-form.php' || $current_page === 'about-us-form.php' || $current_page === 'contact-us-form.php') ? 'bg-primary text-white shadow-md' : 'text-slate-300' ?>">
                    <span class="material-symbols-outlined group-hover:scale-110 transition-transform">article</span>
                    <span>Static Pages</span>
                </a>
                
                <!-- Custom Pages -->
                <a href="<?= $cms_root ?>dynamic-pages.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all hover:bg-sidebar-hover hover:text-white group <?= ($current_page === 'dynamic-pages.php') ? 'bg-primary text-white shadow-md' : 'text-slate-300' ?>">
                    <span class="material-symbols-outlined group-hover:scale-110 transition-transform">web_stories</span>
                    <span>Custom Pages</span>
                </a>

                <!-- Notices & Announcements -->
                <a href="<?= $backend_root ?>notices-announcements-management.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all hover:bg-sidebar-hover hover:text-white group <?= ($current_page === 'notices-announcements-management.php') ? 'bg-primary text-white shadow-md' : 'text-slate-300' ?>">
                    <span class="material-symbols-outlined group-hover:scale-110 transition-transform">campaign</span>
                    <span>Notices & Announcements</span>
                </a>
            <?php endif; ?>

            <!-- Membership Submissions -->
            <a href="<?= $backend_root ?>membership-submission.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all hover:bg-sidebar-hover hover:text-white group <?= ($current_page === 'membership-submission.php') ? 'bg-primary text-white shadow-md' : 'text-slate-300' ?>">
                <span class="material-symbols-outlined group-hover:scale-110 transition-transform">groups</span>
                <span>Membership Submissions</span>
            </a>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-700/50 text-xs text-center text-slate-500">
            &copy; 2026 ACCS Control Panel
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-3 sm:px-6 md:px-8 shadow-xs shrink-0">
            <!-- Left Header -->
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleCmsSidebar()" class="md:hidden flex items-center justify-center p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" title="Toggle Menu">
                    <span class="material-symbols-outlined text-xl">menu</span>
                </button>
            </div>
            
            <!-- Right Header Navigation -->
            <div class="flex items-center gap-1.5 sm:gap-3">
                <!-- Go Live Site -->
                <a href="<?= $frontend_url ?>" target="_blank" class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 transition-all shadow-xs" title="Go Live Site">
                    <span class="material-symbols-outlined text-[18px]">public</span>
                    <span class="hidden sm:inline">Go Live Site</span>
                </a>
                
                <!-- Logout -->
                <a href="<?= $backend_root ?>logout.php" class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800 px-2.5 sm:px-3.5 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 dark:hover:text-red-400 transition-all shadow-xs" title="Logout">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </header>

        <script>
        function toggleCmsSidebar() {
            const sidebar = document.getElementById('cms-sidebar');
            const backdrop = document.getElementById('cms-sidebar-backdrop');
            if (!sidebar) return;
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                if (backdrop) backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
            }
        }
        </script>

        <!-- Logo Lightbox Modal (Global) -->
        <div id="logo-lightbox-modal" onclick="closeLogoLightbox(event)" class="fixed inset-0 z-[100] bg-slate-950/80 backdrop-blur-md hidden items-center justify-center p-4 sm:p-6 transition-all duration-300">
            <div class="relative max-w-3xl w-full max-h-[85vh] flex flex-col items-center justify-center p-4 sm:p-6 bg-white/10 dark:bg-slate-900/60 border border-white/15 rounded-2xl shadow-2xl backdrop-blur-xl">
                <button type="button" onclick="closeLogoLightbox(event, true)" class="absolute -top-3 -right-3 sm:-top-4 sm:-right-4 size-9 sm:size-10 rounded-full bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center justify-center shadow-lg border border-slate-200 dark:border-slate-700 transition-transform active:scale-95 cursor-pointer z-10" title="Close Lightbox (Esc)">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
                <img id="lightbox-logo-img" src="" alt="Site Logo Large View" class="max-h-[75vh] w-auto max-w-full object-contain rounded-xl shadow-2xl p-2 bg-white/5" />
            </div>
        </div>

        <!-- Main Body Wrapper -->
        <main class="flex-1 p-3 sm:p-6 md:p-8 overflow-y-auto bg-slate-50 dark:bg-slate-950/50 custom-scrollbar">
