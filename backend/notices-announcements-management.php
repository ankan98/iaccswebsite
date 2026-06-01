<?php
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // header('Location: login.php');
    // exit; // Uncomment in production
}

define('ENVIRONMENT', 'production'); // Change to 'production' as needed
define('CAN_DELETE', TRUE);

// --- 1. CONFIGURATION ---
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs';     
    $db_user = 'root';        
    $db_pass = '';  
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs_test'; 
    $db_user = 'agcinfos_iaccs_test';    
    $db_pass = 'iaccs#1234X';   
}
          
$table = 'notices'; 

// --- 2. DATABASE CONNECTION ---
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Ensure uploads directory exists
$upload_dir = 'uploads/notices/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// --- Helper Functions ---
function clean($data) { return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8'); }
function set_flash($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// --- 3. HANDLE POST ACTIONS (Add, Edit, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_notice') {
        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? 'notice';
        $status = $_POST['status'] ?? 'active';
        
        // Handle File Upload
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            if ($file_ext === 'pdf') {
                $new_filename = time() . '_' . uniqid() . '.pdf';
                $dest_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dest_path)) {
                    $file_path = $dest_path;
                }
            } else {
                set_flash('Only PDF files are allowed.', 'error');
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }

        if ($id) {
            // Update
            if ($file_path) {
                $stmt = $pdo->prepare("UPDATE $table SET title=?, description=?, type=?, status=?, file_path=? WHERE id=?");
                $stmt->execute([$title, $description, $type, $status, $file_path, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE $table SET title=?, description=?, type=?, status=? WHERE id=?");
                $stmt->execute([$title, $description, $type, $status, $id]);
            }
            set_flash('Notice updated successfully.');
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO $table (title, description, type, status, file_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $type, $status, $file_path]);
            set_flash('Notice added successfully.');
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'delete' && CAN_DELETE) {
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Optional: Delete physical file
            $stmt = $pdo->prepare("SELECT file_path FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['file_path'] && file_exists($row['file_path'])) {
                unlink($row['file_path']);
            }

            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('Notice deleted successfully.');
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// --- 4. DATA FETCHING & PAGINATION ---
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_type) {
    $where_clauses[] = "type = ?";
    $params[] = $filter_type;
}

$where_sql = implode(' AND ', $where_clauses);

// Get Total Rows
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

// Get Data
$data_stmt = $pdo->prepare("SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$data_stmt->execute($params);
$notices = $data_stmt->fetchAll();

// Pagination Links Setup
$link_params = $_GET;
unset($link_params['page']);
$link_prefix = '?' . http_build_query($link_params) . (count($link_params) > 0 ? '&' : '');
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manage Notices & Announcements</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { "primary": "#137fec", "background-light": "#f6f7f8", "background-dark": "#101922" },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                },
            },
        }
    </script>
    <style>
        html { font-size: 13px; }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        .desc-truncate { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        #flashMessage { opacity: 0; transform: translateY(-20px); transition: opacity 0.3s ease, transform 0.3s ease; }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-white antialiased overflow-x-hidden">

<?php if ($flash_message): ?>
<div id="flashMessage" class="fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg text-white <?= $flash_type === 'success' ? 'bg-green-500' : 'bg-red-500' ?>">
    <span class="font-semibold"><?= $flash_message ?></span>
</div>
<?php endif; ?>

<div class="relative flex min-h-screen w-full flex-col">
    <main class="flex-1 px-4 py-8 md:px-8">
        <div class="mx-auto flex max-w-[1400px] flex-col gap-6">
            
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div class="flex flex-col gap-1">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Notices & Announcements</h1>
                    <p class="text-slate-500 dark:text-slate-400">Total Records: <?php echo $total_rows; ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="membership-submission.php" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">groups</span> Membership Submissions
                    </a>
                    
                    <a href="logout.php" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                    </a>
                </div>
            </div>

            <div class="flex w-full flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-slate-800">
                
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <form method="GET" action="" class="m-0 flex max-w-sm flex-1 items-center relative">
                            <input type="hidden" name="type" value="<?php echo clean($filter_type); ?>">
                            <span class="material-symbols-outlined absolute left-3 text-[20px] text-slate-400">search</span>
                            <input name="search" value="<?php echo clean($search); ?>" class="h-10 w-full rounded-lg border-slate-300 bg-white pl-10 pr-4 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-white" placeholder="Search title or description..." type="text"/>
                        </form>
                        <div class="flex items-center gap-4">
                            <form method="GET" action="" class="m-0 flex items-center gap-2">
                                <input type="hidden" name="search" value="<?php echo clean($search); ?>">
                                <select name="type" onchange="this.form.submit()" class="h-10 rounded-lg border-slate-300 bg-white px-4 pr-8 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <option value="">All Types</option>
                                    <option value="notice" <?php echo $filter_type === 'notice' ? 'selected' : ''; ?>>Notice</option>
                                    <option value="announcement" <?php echo $filter_type === 'announcement' ? 'selected' : ''; ?>>Announcement</option>
                                    <option value="report" <?php echo $filter_type === 'report' ? 'selected' : ''; ?>>Report</option>
                                </select>
                            </form>
                            <button onclick="openFormModal()" class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-primary/30 hover:bg-primary/90 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">add</span> Add New
                            </button>
                        </div>
                    </div>
                </div>

                <div class="custom-scrollbar overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white w-1/4">Title</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white w-1/3">Description</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white">Type</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white">Status</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 dark:text-white text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                            <?php if(count($notices) > 0): ?>
                                <?php foreach($notices as $row): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                        <?php echo clean($row['title']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                                        <div class="desc-truncate" title="<?php echo clean($row['description']); ?>">
                                            <?php echo clean($row['description']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                            $typeColors = [
                                                'notice' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'announcement' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                                'report' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                            ];
                                            $tColor = $typeColors[$row['type']] ?? 'bg-slate-100 text-slate-700';
                                        ?>
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium <?php echo $tColor; ?> ring-1 ring-inset ring-current/20 capitalize">
                                            <?php echo clean($row['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 capitalize">Active</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 capitalize">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <?php if ($row['file_path']): ?>
                                                <a href="<?php echo clean($row['file_path']); ?>" download class="text-slate-500 hover:text-primary transition-colors" title="Download File">
                                                    <span class="material-symbols-outlined text-[20px]">download</span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-300 dark:text-slate-700 cursor-not-allowed" title="No file attached"><span class="material-symbols-outlined text-[20px]">download</span></span>
                                            <?php endif; ?>
                                            
                                            <button type="button" onclick='openFormModal(<?php echo json_encode($row); ?>)' class="text-slate-500 hover:text-primary transition-colors" title="Edit">
                                                <span class="material-symbols-outlined text-[20px]">edit</span>
                                            </button>
                                            
                                            <?php if(CAN_DELETE): ?>
                                            <button type="button" onclick="openDeleteModal(<?php echo $row['id']; ?>)" class="text-slate-500 hover:text-red-600 transition-colors" title="Delete">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No notices found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-center justify-between gap-4 border-t border-slate-200 px-6 py-4 dark:border-slate-800 sm:flex-row">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Showing <span class="font-bold text-slate-900 dark:text-white"><?php echo $total_rows > 0 ? $offset + 1 : 0; ?></span> to <span class="font-bold text-slate-900 dark:text-white"><?php echo min($offset + $per_page, $total_rows); ?></span> of <span class="font-bold text-slate-900 dark:text-white"><?php echo $total_rows; ?></span> entries
                    </p>
                    <div class="flex items-center gap-1">
                        <?php if($page > 1): ?>
                            <a href="<?php echo $link_prefix; ?>page=<?php echo $page - 1; ?>" class="flex size-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"><span class="material-symbols-outlined text-[18px]">chevron_left</span></a>
                        <?php endif; ?>

                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="flex size-9 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-white shadow-sm"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $link_prefix; ?>page=<?php echo $i; ?>" class="flex size-9 items-center justify-center rounded-lg border border-transparent bg-transparent text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="<?php echo $link_prefix; ?>page=<?php echo $page + 1; ?>" class="flex size-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"><span class="material-symbols-outlined text-[18px]">chevron_right</span></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="formModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-slate-900 rounded-xl shadow-2xl ring-1 ring-slate-900/10">
        <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white" id="modalTitle">Add Notice</h3>
            <button onclick="closeFormModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" value="save_notice">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Title *</label>
                    <input type="text" name="title" id="formTitle" required class="h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-primary dark:border-slate-700 dark:text-white placeholder:text-slate-400" placeholder="Enter title">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Description *</label>
                    <textarea name="description" id="formDesc" required rows="4" class="w-full rounded-lg border-slate-300 bg-transparent p-4 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-primary dark:border-slate-700 dark:text-white placeholder:text-slate-400" placeholder="Enter description"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Type *</label>
                        <select name="type" id="formType" required class="h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-primary dark:border-slate-700 dark:text-white">
                            <option value="notice">Notice</option>
                            <option value="announcement">Announcement</option>
                            <option value="report">Report</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Status *</label>
                        <select name="status" id="formStatus" required class="h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-primary dark:border-slate-700 dark:text-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Upload Document (PDF Only)</label>
                    <input type="file" name="file" accept=".pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 dark:text-slate-400">
                    <p id="currentFileText" class="text-xs text-slate-500 hidden mt-1">Current file uploaded. Leave empty to keep it.</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2 bg-slate-50 dark:bg-slate-800/50">
                <button type="button" onclick="closeFormModal()" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm shadow-primary/30 transition-all hover:bg-primary/90">Save Record</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteConfirmationModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-xl shadow-2xl ring-1 ring-slate-900/10">
        <div class="p-6 text-center">
            <span class="material-symbols-outlined text-5xl text-red-500">delete_forever</span>
            <h3 class="mt-2 text-lg font-bold text-slate-900 dark:text-white">Delete Record</h3>
            <p class="text-sm text-slate-500 mt-1">Are you sure you want to permanently delete this notice? This action cannot be undone.</p>
        </div>
        <div class="px-6 pb-6 flex justify-center gap-3">
            <button type="button" onclick="closeDeleteModal()" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</button>
            <button id="confirmDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-red-600/30 transition-all hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const flashMessage = document.getElementById('flashMessage');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.opacity = '1';
                flashMessage.style.transform = 'translateY(0)';
            }, 100);
            setTimeout(() => {
                flashMessage.style.opacity = '0';
                flashMessage.style.transform = 'translateY(-20px)';
                setTimeout(() => flashMessage.remove(), 300);
            }, 3000);
        }
    });

    // Form Modal Logic
    function openFormModal(data = null) {
        document.getElementById('formModal').classList.remove('hidden');
        if (data) {
            document.getElementById('modalTitle').textContent = 'Edit Record';
            document.getElementById('formId').value = data.id;
            document.getElementById('formTitle').value = data.title;
            document.getElementById('formDesc').value = data.description;
            document.getElementById('formType').value = data.type;
            document.getElementById('formStatus').value = data.status;
            if(data.file_path) {
                document.getElementById('currentFileText').classList.remove('hidden');
            } else {
                document.getElementById('currentFileText').classList.add('hidden');
            }
        } else {
            document.getElementById('modalTitle').textContent = 'Add New Notice';
            document.getElementById('formId').value = '';
            document.getElementById('formTitle').value = '';
            document.getElementById('formDesc').value = '';
            document.getElementById('formType').value = 'notice';
            document.getElementById('formStatus').value = 'active';
            document.getElementById('currentFileText').classList.add('hidden');
        }
    }

    function closeFormModal() {
        document.getElementById('formModal').classList.add('hidden');
    }

    // Delete Modal Logic
    const deleteModal = document.getElementById('deleteConfirmationModal');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    let deleteId = null;

    function openDeleteModal(id) {
        deleteId = id;
        deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        deleteId = null;
        deleteModal.classList.add('hidden');
    }

    confirmDeleteBtn.addEventListener('click', () => {
        if (deleteId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = deleteId;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
        }
    });

    // Close Modals on Escape
    document.addEventListener('keydown', e => { 
        if (e.key === "Escape") {
            closeFormModal();
            closeDeleteModal();
        }
    });
</script>
</body>
</html>