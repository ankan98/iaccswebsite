<?php
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

define('ENVIRONMENT', 'production');
define('CAN_DELETE', TRUE);
/**
 * ACCS Membership Admin Panel
 * Features: Sorting, Filtering, XLS Export, Persistent Columns, Pagination, Status Updates
 */

// --- 1. CONFIGURATION ---
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs';     
    $db_user = 'root';        
    $db_pass = '';  
    $base_url = 'http://localhost:8008/iaccs/';
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs'; 
    $db_user = 'agcinfos_iaccs';    
    $db_pass = 'iaccs#1234X';   
    $base_url = 'https://iaccs.org.in/';
}
          
$table   = 'membership_requests'; 

// --- 2. DATABASE CONNECTION ---
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// --- 3. HELPER FUNCTIONS ---
function clean($data) {
    return htmlspecialchars(strip_tags($data ?? ''));
}

function fmt_date($date) {
    return $date && $date !== '0000-00-00' ? date('M d, Y', strtotime($date)) : '-';
}

function get_badge_class($type, $value) {
    $value = strtolower($value ?? '');
    if ($type === 'status') {
        if ($value === 'approved') return 'bg-green-100 text-green-800 border-green-200';
        if ($value === 'pending') return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        if ($value === 'rejected') return 'bg-red-100 text-red-800 border-red-200';
        return 'bg-blue-50 text-blue-800 border-blue-200'; 
    }
    if ($type === 'payment') {
        if ($value === 'paid') return 'bg-green-100 text-green-800 border-green-200';
        if ($value === 'failed') return 'bg-red-100 text-red-800 border-red-200';
        return 'bg-slate-100 text-slate-800 border-slate-200'; // Unpaid
    }
    return 'bg-white border-slate-200';
}

function addQueryParam($url, $param, $value) {
    $url_parts = parse_url($url);
    if (isset($url_parts['query'])) {
        parse_str($url_parts['query'], $params);
    } else {
        $params = array();
    }
    $params[$param] = $value;
    $url_parts['query'] = http_build_query($params);
    return $url_parts['path'] . '?' . $url_parts['query'];
}

// --- FLASH MESSAGE LOGIC ---
$flash_message = '';
$flash_type = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $flash_message = 'Record deleted successfully.';
        $flash_type = 'success';
    } elseif ($_GET['msg'] === 'updated') {
        $flash_message = 'Status updated successfully.';
        $flash_type = 'success';
    }
    // Clean the URL
    // echo "<script>history.replaceState(null, null, window.location.pathname);</script>";
}

// --- 4. DEFINITIONS ---
$togglable_cols = [
    'father_name' => "Father's Name",
    'dob' => 'DOB',
    'age' => 'Age',
    'gender' => 'Gender',
    'address' => 'Address',
    'city' => 'City',
    'district' => 'District',
    'pin' => 'PIN',
    'state' => 'State',
    'mobile' => 'Mobile',
    'email' => 'Email',
    'nationality' => 'Nationality',
    'education' => 'Education',
    'education_status' => 'Edu Status',
    'academic_session' => 'Session',
    'college_name' => 'College',
    'university_name' => 'University',
    'employed' => 'Employed',
    'employment_type' => 'Emp Type',
    'hospital_name' => 'Hospital',
    'designation' => 'Designation',
    'employee_id' => 'Emp ID',
    'membership_plan' => 'Plan',
    'amount' => 'Amount',
    'order_id' => 'Order ID',
    'payment_status' => 'Payment Status',
    'payment_date' => 'Payment Date',
    'transaction_id' => 'Txn ID',
    'payment_response' => 'Pay Response',
    'photo' => 'Photo',
    'id_proof' => 'ID Proof',
    // 'education_doc' => 'Edu Doc',
    // 'student_id' => 'Student ID',
    // 'employment_proof' => 'Emp Proof',
    'status' => 'Status',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At'
];

// --- 5. SORTING LOGIC ---
$allowed_sort = array_merge(['reference_number', 'name', 'membership_id'], array_keys($togglable_cols));
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

function sort_th($col_key, $label, $current_sort, $current_order) {
    $next_order = ($current_sort === $col_key && $current_order === 'DESC') ? 'ASC' : 'DESC';
    $params = $_GET;
    $params['sort'] = $col_key;
    $params['order'] = $next_order;
    unset($params['page']); 
    $url = "?" . http_build_query($params);
    $icon = 'unfold_more';
    $active_class = 'text-slate-400';
    if ($current_sort === $col_key) {
        $icon = ($current_order === 'ASC') ? 'arrow_upward' : 'arrow_downward';
        $active_class = 'text-primary';
    }
    return '<a href="'.$url.'" class="flex items-center gap-1 hover:text-primary transition-colors cursor-pointer group">
                <span>'.$label.'</span>
                <span class="material-symbols-outlined text-[16px] '.$active_class.'">'.$icon.'</span>
            </a>';
}

// --- 6. ACTION HANDLERS ---

// Handle Record Deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $redirect_url = addQueryParam($_SERVER['HTTP_REFERER'], 'msg', 'deleted');
    header("Location: " . $redirect_url);
    exit;
}

// Handle Status Updates (New Feature)
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && !empty($_POST['id'])) {
    $field = $_POST['field']; // 'status' or 'payment_status'
    $val = $_POST['value'];
    $id = $_POST['id'];

    // Security: Only allow specific columns
    if (in_array($field, ['status', 'payment_status'])) {
        $stmt = $pdo->prepare("UPDATE $table SET $field = ? WHERE id = ?");
        $stmt->execute([$val, $id]);
    }
    
    $redirect_url = addQueryParam($_SERVER['REQUEST_URI'], 'msg', 'updated');
    header("Location: " . $redirect_url);
    exit;
}

// Handle Membership ID Updates
if (isset($_POST['action']) && $_POST['action'] === 'update_membership_id' && !empty($_POST['id'])) {
    $membership_id = clean($_POST['membership_id']);
    $id = $_POST['id'];

    $stmt = $pdo->prepare("UPDATE $table SET membership_id = ? WHERE id = ?");
    $stmt->execute([$membership_id, $id]);
    
    // Redirect to self (preserves GET params like page/search)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle Export (XLS)
if (isset($_GET['export']) && $_GET['export'] === 'xls') {
    if (ob_get_level()) ob_end_clean();
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=members_export_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $where = ["1=1"];
    $params = [];
    if (!empty($_GET['search'])) {
        $term = "%" . $_GET['search'] . "%";
        $where[] = "(reference_number LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ? OR membership_id LIKE ?)";
        array_push($params, $term, $term, $term, $term, $term);
    }
    if (!empty($_GET['status'])) { $where[] = "status = ?"; $params[] = $_GET['status']; }
    if (!empty($_GET['payment'])) { $where[] = "payment_status = ?"; $params[] = $_GET['payment']; }
    
    $sql = "SELECT * FROM $table WHERE " . implode(" AND ", $where) . " ORDER BY $sort_by $sort_order";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body><table border="1">';
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo '<thead><tr>';
        foreach (array_keys($row) as $h) echo '<th style="background-color:#f0f0f0; font-weight:bold;">' . strtoupper(str_replace('_', ' ', $h)) . '</th>';
        echo '</tr></thead><tbody>';
        do {
            echo '<tr>';
            foreach ($row as $key => $cell) echo '<td>' . (in_array($key, ['photo', 'id_proof', 'education_doc', 'employment_proof', 'student_id']) ? ("<a href=\"$base_url" . clean($cell). '">' . htmlspecialchars($cell ?? '') . "</a>" ) : (htmlspecialchars($cell ?? ''))) . '</td>';
            echo '</tr>';
        } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));
        echo '</tbody>';
    }
    echo '</table></body></html>';
    exit;
}

// --- 7. DATA FETCHING ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = ["1=1"];
$params = [];
if (!empty($_GET['search'])) {
    $term = "%" . $_GET['search'] . "%";
    $where[] = "(reference_number LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ? OR membership_id LIKE ?)";
    array_push($params, $term, $term, $term, $term, $term);
}
if (!empty($_GET['status'])) { $where[] = "status = ?"; $params[] = $_GET['status']; }
if (!empty($_GET['payment'])) { $where[] = "payment_status = ?"; $params[] = $_GET['payment']; }
$where_sql = implode(" AND ", $where);

$count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $sort_by $sort_order LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$query_params = $_GET;
unset($query_params['page']);
$base_query = http_build_query($query_params);
$link_prefix = "?" . ($base_query ? $base_query . "&" : "");

// Pagination Window
$max_links = 5;
$start_page = max(1, $page - 2);
$end_page = min($total_pages, $page + 2);
if ($end_page - $start_page < $max_links - 1) {
    if ($start_page == 1) {
        $end_page = min($total_pages, $start_page + $max_links - 1);
    } elseif ($end_page == $total_pages) {
        $start_page = max(1, $end_page - $max_links + 1);
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>ACCS Membership Submissions</title>
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
        .custom-scrollbar::-webkit-scrollbar { height: 10px; width: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .dark .custom-scrollbar::-webkit-scrollbar-track { background: #1f2937; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; }
        
        .sticky-col-left { position: sticky; left: 0; z-index: 20; background-color: inherit; }
        .sticky-col-right { position: sticky; right: 0; z-index: 20; background-color: inherit; }
        th.sticky-col-left, th.sticky-col-right { z-index: 30; }
        
        #colsDropdown { transition: all 0.2s ease-in-out; transform-origin: top right; }
        #colsDropdown.hidden { opacity: 0; transform: scale(0.95); pointer-events: none; }
        #colsDropdown:not(.hidden) { opacity: 1; transform: scale(1); pointer-events: auto; }
        
        td { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* Style for small select to look like badge */
        .status-select { 
            background-position: right 0.2rem center; 
            padding-right: 1.2rem;
            padding-left: 0.5rem;
            padding-top: 0.1rem;
            padding-bottom: 0.1rem;
        }
        #confirmationModal { display: none; }
        #confirmationModal.show { display: flex; }
        #flashMessage {
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
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
        <div class="mx-auto flex max-w-[1600px] flex-col gap-6">
            
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                <div class="flex flex-col gap-1">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Membership Submissions</h1>
                    <p class="text-slate-500 dark:text-slate-400">Total Records: <?php echo $total_rows; ?></p>
                </div>
                <div class="flex flex-col justify-between flex-nowrap gap-3 sm:flex-row sm:items-center sm:gap-4 md:gap-6">
                    <div class="relative">
                        <button id="colsBtn" onclick="toggleDropdown()" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[20px]">view_column</span>
                            <span>Select Columns</span>
                        </button>
                        <div id="colsDropdown" class="hidden absolute right-0 top-full mt-2 w-64 max-h-[60vh] overflow-y-auto custom-scrollbar rounded-xl bg-white p-3 shadow-lg ring-1 ring-slate-900/10 dark:bg-slate-900 dark:ring-slate-700 z-50">
                            <div class="space-y-1">
                                <?php foreach ($togglable_cols as $key => $label): ?>
                                <label class="flex items-center gap-3 px-2 py-1.5 rounded hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer">
                                    <input type="checkbox" class="col-toggle form-checkbox size-4 rounded border-slate-300 text-primary focus:ring-primary" 
                                           data-col="<?php echo $key; ?>" 
                                           <?php echo in_array($key, ['email','mobile','status','payment_status','amount','created_at']) ? 'checked' : ''; ?>>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <a href="logout.php" class="w-[100px] flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>

            <form method="GET" action="" class="flex flex-col gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-slate-800 lg:flex-row lg:items-center lg:justify-between" autocomplete="off">
                <input type="hidden" name="sort" value="<?php echo clean($sort_by); ?>">
                <input type="hidden" name="order" value="<?php echo clean($sort_order); ?>">
                <div class="relative flex max-w-lg flex-1 items-center">
                    <span class="material-symbols-outlined absolute left-3 text-[20px] text-slate-400">search</span>
                    <input name="search" value="<?php echo clean($_GET['search'] ?? ''); ?>" 
                           class="h-10 w-full rounded-lg border-none bg-slate-100 pl-10 pr-4 text-sm font-medium text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-400" 
                           placeholder="Search Ref, Name, Email..." type="text" onchange="this.form.submit()"/>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <select name="status" onchange="this.form.submit()" class="h-10 rounded-lg border-none bg-slate-100 px-3 pr-8 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-slate-200">
                        <option value="">All Statuses</option>
                        <option value="Approved" <?php echo (($_GET['status']??'') == 'Approved')?'selected':''; ?>>Approved</option>
                        <option value="Pending" <?php echo (($_GET['status']??'') == 'Pending')?'selected':''; ?>>Pending</option>
                        <option value="Rejected" <?php echo (($_GET['status']??'') == 'Rejected')?'selected':''; ?>>Rejected</option>
                    </select>
                    <select name="payment" onchange="this.form.submit()" class="h-10 rounded-lg border-none bg-slate-100 px-3 pr-8 text-sm font-medium text-slate-700 focus:ring-2 focus:ring-primary dark:bg-slate-800 dark:text-slate-200">
                        <option value="">All Payments</option>
                        <option value="Paid" <?php echo (($_GET['payment']??'') == 'Paid')?'selected':''; ?>>Paid</option>
                        <option value="Unpaid" <?php echo (($_GET['payment']??'') == 'Unpaid')?'selected':''; ?>>Unpaid</option>
                        <option value="Failed" <?php echo (($_GET['payment']??'') == 'Failed')?'selected':''; ?>>Failed</option>
                    </select>
                    <div class="h-8 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>
                    <a href="<?php echo $link_prefix; ?>export=xls" class="flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-primary/30 transition-all hover:bg-primary/90">
                        <span class="material-symbols-outlined text-[20px]">download</span>
                        <span>Export XLS</span>
                    </a>
                </div>
            </form>

            <div class="flex w-full flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 dark:bg-slate-900 dark:ring-slate-800">
                <div class="custom-scrollbar overflow-x-auto">
                    <table class="w-full min-w-max border-collapse text-left text-sm" id="mainTable">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky-col-left left-0 min-w-[140px] bg-slate-50 px-4 py-3 font-semibold text-slate-900 dark:bg-slate-900 dark:text-white shadow-[1px_0_0_0_rgba(0,0,0,0.05)]">
                                    <?php echo sort_th('reference_number', 'Ref No', $sort_by, $sort_order); ?>
                                </th>
                                <th class="sticky-col-left left-[140px] min-w-[150px] bg-slate-50 px-4 py-3 font-semibold text-slate-900 dark:bg-slate-900 dark:text-white shadow-[4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                    <?php echo sort_th('membership_id', 'Membership ID', $sort_by, $sort_order); ?>
                                </th>
                                <th class="sticky-col-left left-[290px] min-w-[200px] bg-slate-50 px-4 py-3 font-semibold text-slate-900 dark:bg-slate-900 dark:text-white shadow-[4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                    <?php echo sort_th('name', 'Name', $sort_by, $sort_order); ?>
                                </th>
                                <?php foreach ($togglable_cols as $key => $label): ?>
                                    <th class="px-4 py-3 font-medium text-slate-500 dark:text-slate-400 col-<?php echo $key; ?>">
                                        <?php echo sort_th($key, $label, $sort_by, $sort_order); ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="sticky-col-right right-0 bg-slate-50 px-4 py-3 font-medium text-slate-900 dark:bg-slate-900 dark:text-white shadow-[-4px_0_4px_-2px_rgba(0,0,0,0.05)]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                            <?php if(count($members) > 0): ?>
                                <?php foreach($members as $row): ?>
                                <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="sticky-col-left left-0 bg-white px-4 py-3 font-medium text-primary dark:bg-slate-900 dark:text-primary group-hover:bg-slate-50 dark:group-hover:bg-slate-800 shadow-[1px_0_0_0_rgba(0,0,0,0.05)]">
                                        <?php echo clean($row['reference_number']); ?>
                                    </td>
                                    <td class="sticky-col-left left-[140px] bg-white px-4 py-3 text-slate-600 dark:text-slate-400 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 shadow-[4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                        <?php echo clean($row['membership_id']); ?>
                                    </td>
                                    <td class="sticky-col-left left-[290px] bg-white px-4 py-3 text-slate-700 dark:bg-slate-900 dark:text-slate-300 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 shadow-[4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="font-semibold text-slate-900 dark:text-white"><?php echo clean($row['name']); ?></div>
                                    </td>
                                    <?php foreach ($togglable_cols as $key => $label): ?>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 col-<?php echo $key; ?>">
                                            <?php 
                                            $val = $row[$key] ?? '';
                                            
                                            // --- UPDATE 1: Interactive Status Dropdown ---
                                            if ($key === 'status') {
                                                $opts = ['Pending', 'Approved', 'Rejected'];
                                                $cls = get_badge_class('status', $val);
                                                echo '<form method="POST" action="" class="m-0 status-update-form" autocomplete="off">';
                                                echo '<input type="hidden" name="action" value="update_status">';
                                                echo '<input type="hidden" name="id" value="'.$row['id'].'">';
                                                echo '<input type="hidden" name="field" value="status">';
                                                echo '<select name="value" data-member-id="'.$row['id'].'" data-original-value="'.$val.'" class="status-select status-change-trigger block w-full rounded-md border-0 py-1 text-xs font-medium shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-xs sm:leading-4 cursor-pointer '.$cls.'">';
                                                foreach($opts as $opt) {
                                                    $sel = (strtolower($opt) == strtolower($val)) ? 'selected' : '';
                                                    echo "<option value='$opt' $sel>$opt</option>";
                                                }
                                                echo '</select></form>';
                                            } 
                                            // --- UPDATE 2: Interactive Payment Dropdown ---
                                            elseif ($key === 'payment_status') {
                                                $opts = ['Unpaid', 'Paid', 'Failed'];
                                                $cls = get_badge_class('payment', $val);
                                                echo '<form method="POST" action="" class="m-0" autocomplete="off">';
                                                echo '<input type="hidden" name="action" value="update_status">';
                                                echo '<input type="hidden" name="id" value="'.$row['id'].'">';
                                                echo '<input type="hidden" name="field" value="payment_status">';
                                                echo '<select name="value" onchange="this.form.submit()" class="status-select block w-full rounded-md border-0 py-1 text-xs font-medium shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-xs sm:leading-4 cursor-pointer '.$cls.'">';
                                                foreach($opts as $opt) {
                                                    $sel = (strtolower($opt) == strtolower($val)) ? 'selected' : '';
                                                    echo "<option value='$opt' $sel>$opt</option>";
                                                }
                                                echo '</select></form>';
                                            } 
                                            // --- Standard Read-Only Fields ---
                                            elseif (strpos($key, 'date') !== false || $key === 'created_at' || $key === 'updated_at' || $key === 'dob') {
                                                echo fmt_date($val);
                                            } elseif (in_array($key, ['photo', 'id_proof', 'education_doc', 'employment_proof', 'student_id']) && !empty($val)) {
                                                if(filter_var($val, FILTER_VALIDATE_URL) || strpos($val, '/') !== false) {
                                                     echo '<a href="' . clean($val) . '" target="_blank" class="text-primary hover:text-primary/80" title="View"><span class="material-symbols-outlined text-[20px]">description</span></a>';
                                                } else {
                                                    echo clean($val) ? clean($val) : '-';
                                                }
                                            } elseif ($key === 'payment_response' || $key === 'address') {
                                                $txt = clean($val);
                                                echo '<span title="' . $txt . '">' . (strlen($txt) > 20 ? substr($txt, 0, 20) . '...' : $txt) . '</span>';
                                            } else {
                                                echo clean($val) ? clean($val) : '-';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="sticky-col-right right-0 bg-white px-4 py-3 dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800 shadow-[-4px_0_4px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" onclick='openEditModal(<?php echo json_encode($row); ?>)' class="text-slate-500 hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">edit</span>
                                            </button>
                                            <button type="button" onclick='openModal(<?php echo json_encode($row); ?>)' class="text-slate-500 hover:text-primary transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                                            </button>
                                            <?php if(CAN_DELETE): ?>
                                            <button type="button" onclick="openDeleteModal(<?php echo $row['id']; ?>)" class="text-slate-500 hover:text-red-600 transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="50" class="px-4 py-8 text-center text-slate-500">No records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-center justify-between gap-4 border-t border-slate-200 px-4 py-4 dark:border-slate-800 sm:flex-row">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Showing <span class="font-bold text-slate-900 dark:text-white"><?php echo ($offset + 1); ?></span> to <span class="font-bold text-slate-900 dark:text-white"><?php echo min($offset + $per_page, $total_rows); ?></span> of <span class="font-bold text-slate-900 dark:text-white"><?php echo $total_rows; ?></span> entries
                    </p>
                    <div class="flex items-center gap-1">
                        <?php if($page > 1): ?>
                            <a href="<?php echo $link_prefix; ?>page=<?php echo $page - 1; ?>" class="flex size-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"><span class="material-symbols-outlined text-[18px]">chevron_left</span></a>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
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

<div id="deleteConfirmationModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
    <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-xl shadow-2xl ring-1 ring-slate-900/10">
        <div class="p-6 text-center">
            <span class="material-symbols-outlined text-5xl text-red-500">delete_forever</span>
            <h3 class="mt-2 text-lg font-bold text-slate-900 dark:text-white">Delete Record</h3>
            <p class="text-sm text-slate-500 mt-1">Are you sure you want to permanently delete this record? This action cannot be undone.</p>
        </div>
        <div class="px-6 pb-6 flex justify-center gap-3">
            <button type="button" onclick="closeDeleteModal()" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</button>
            <button id="confirmDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-red-600/30 transition-all hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<div id="viewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
    <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto bg-white dark:bg-slate-900 rounded-xl shadow-2xl ring-1 ring-slate-900/10">
        <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <div><h3 class="text-lg font-bold text-slate-900 dark:text-white" id="modalName">Details</h3><p class="text-sm text-slate-500" id="modalRef">REF</p></div>
            <button onclick="closeModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="modalContent"></div>
    </div>
</div>

<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
    <div class="w-full max-w-md max-h-[90vh] overflow-y-auto bg-white dark:bg-slate-900 rounded-xl shadow-2xl ring-1 ring-slate-900/10">
        <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <div><h3 class="text-lg font-bold text-slate-900 dark:text-white" id="editModalName">Update Membership ID</h3><p class="text-sm text-slate-500" id="editModalRef">REF</p></div>
            <button onclick="closeEditModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" action="">
            <div class="p-6">
                <input type="hidden" name="action" value="update_membership_id">
                <input type="hidden" name="id" id="editId">
                <div class="flex flex-col gap-2">
                    <label for="membership_id" class="text-sm font-medium text-slate-700 dark:text-slate-200">Membership ID</label>
                    <input type="text" name="membership_id" id="membership_id" class="h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-transparent dark:text-white dark:placeholder:text-slate-400" placeholder="Enter Membership ID">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm shadow-primary/30 transition-all hover:bg-primary/90">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="confirmationModal" class="fixed inset-0 z-50 items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-xl shadow-2xl ring-1 ring-slate-900/10">
        <div class="p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Confirm Action</h3>
            <p class="text-sm text-slate-500 mt-1">An action is required for this status change.</p>
        </div>
        <div class="px-6 pb-6 flex justify-end gap-3">
            <button id="confirmCancel" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</button>
            <button id="confirmSave" class="rounded-lg bg-slate-200 dark:bg-slate-700 px-4 py-2 text-sm font-bold text-slate-800 dark:text-white hover:bg-slate-300 dark:hover:bg-slate-600">Save</button>
            <button id="confirmSaveAndSend" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm shadow-primary/30 transition-all hover:bg-primary/90">Save and Send Membership ID</button>
        </div>
    </div>
</div>


<script>
    function toggleDropdown() {
        document.getElementById('colsDropdown').classList.toggle('hidden');
    }
    document.addEventListener('click', function(e) {
        if (!document.getElementById('colsBtn').contains(e.target) && !document.getElementById('colsDropdown').contains(e.target)) {
            document.getElementById('colsDropdown').classList.add('hidden');
        }
    });

    const toggles = document.querySelectorAll('.col-toggle');
    function loadColPrefs() {
        toggles.forEach(toggle => {
            const colName = toggle.dataset.col;
            const stored = localStorage.getItem('col_' + colName);
            const isVisible = stored !== null ? (stored === 'true') : toggle.checked;
            toggle.checked = isVisible;
            updateColumnVisibility(colName, isVisible);
        });
    }

    function updateColumnVisibility(colName, isVisible) {
        const cells = document.querySelectorAll('.col-' + colName);
        cells.forEach(el => {
            if (isVisible) el.classList.remove('hidden');
            else el.classList.add('hidden');
        });
    }

    toggles.forEach(toggle => {
        toggle.addEventListener('change', (e) => {
            const colName = e.target.dataset.col;
            const isVisible = e.target.checked;
            localStorage.setItem('col_' + colName, isVisible);
            updateColumnVisibility(colName, isVisible);
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadColPrefs();

        const flashMessage = document.getElementById('flashMessage');
        if (flashMessage) {
            // Animate in
            setTimeout(() => {
                flashMessage.style.opacity = '1';
                flashMessage.style.transform = 'translateY(0)';
            }, 100);

            // Clean URL
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            history.replaceState(null, '', url);

            // Animate out after 3 seconds
            setTimeout(() => {
                flashMessage.style.opacity = '0';
                flashMessage.style.transform = 'translateY(-20px)';
                setTimeout(() => flashMessage.remove(), 300); // Remove from DOM after transition
            }, 3000);
        }
    });

    function openModal(data) {
        document.getElementById('viewModal').classList.remove('hidden');
        document.getElementById('modalName').textContent = data.name;
        document.getElementById('modalRef').textContent = data.reference_number;
        let html = '';
        for (const [key, value] of Object.entries(data)) {
            if(value && key !== 'payment_response') {
                 html += `<div class="flex flex-col gap-1 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <span class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">${key.replace(/_/g, ' ')}</span>
                            <p class="text-sm text-slate-900 dark:text-white font-medium break-words">${value}</p>
                         </div>`;
            }
        }
        document.getElementById('modalContent').innerHTML = html;
    }

    function closeModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
    
    function openEditModal(data) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModalName').textContent = "Update Membership ID for " + data.name;
        document.getElementById('editModalRef').textContent = data.reference_number;
        document.getElementById('editId').value = data.id;
        document.getElementById('membership_id').value = data.membership_id;
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    document.addEventListener('keydown', e => { 
        if (e.key === "Escape") {
            closeModal(); 
            closeEditModal();
            closeConfirmationModal();
            closeDeleteModal();
        }
    });

    // --- New Confirmation Modal Logic ---
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmCancel = document.getElementById('confirmCancel');
    const confirmSave = document.getElementById('confirmSave');
    const confirmSaveAndSend = document.getElementById('confirmSaveAndSend');
    let activeSelect = null;

    function showConfirmationModal(selectElement) {
        activeSelect = selectElement;
        confirmationModal.classList.add('show');
    }

    function closeConfirmationModal() {
        if (activeSelect) {
            // Revert selection
            activeSelect.value = activeSelect.dataset.originalValue;
        }
        confirmationModal.classList.remove('show');
        activeSelect = null;
    }

    document.querySelectorAll('.status-change-trigger').forEach(select => {
        select.addEventListener('change', function(e) {
            const newValue = e.target.value;
            if (newValue.toLowerCase() === 'approved') {
                e.preventDefault();
                showConfirmationModal(e.target);
            } else {
                // For other statuses, submit the form directly
                e.target.closest('form').submit();
            }
        });
    });
    
    confirmCancel.addEventListener('click', () => {
        closeConfirmationModal();
    });

    confirmSave.addEventListener('click', () => {
        if (activeSelect) {
            activeSelect.closest('form').submit();
        }
    });

    confirmSaveAndSend.addEventListener('click', () => {
        if (activeSelect) {
            const memberId = activeSelect.dataset.memberId;
            const button = confirmSaveAndSend;
            const originalText = button.innerHTML;
            button.innerHTML = 'Sending...';
            button.disabled = true;

            fetch('send-membership-card.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ id: memberId })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Action completed.');
                if(data.success) {
                    // Reload the page to show the 'Approved' status
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                closeConfirmationModal();
            });
        }
    });

    // --- Delete Confirmation Modal ---
    const deleteModal = document.getElementById('deleteConfirmationModal');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    let deleteId = null;

    function openDeleteModal(id) {
        deleteId = id;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
    }

    function closeDeleteModal() {
        deleteId = null;
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    }

    confirmDeleteBtn.addEventListener('click', () => {
        if (deleteId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = ''; // Post to self

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


</script>

</body>
</html>