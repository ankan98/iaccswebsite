<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect based on role
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        header("Location: cms/general-settings.php");
        exit();
    } else {
        header("Location: membership-submission.php");
        exit();
    }
}

define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_ATTEMPT_WINDOW', 60 * 15); // 15 minutes

function get_login_attempts() {
    $attempts = json_decode(@file_get_contents('login_attempts.json'), true) ?: [];
    foreach ($attempts as $ip => &$ip_attempts) {
        $ip_attempts = array_filter($ip_attempts, function($time) {
            return (time() - $time) < LOGIN_ATTEMPT_WINDOW;
        });
        if (empty($ip_attempts)) {
            unset($attempts[$ip]);
        }
    }
    return $attempts;
}

function record_login_attempt() {
    $attempts = get_login_attempts();
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = [];
    }
    $attempts[$ip][] = time();
    file_put_contents('login_attempts.json', json_encode($attempts));
}

function is_rate_limited() {
    $attempts = get_login_attempts();
    $ip = $_SERVER['REMOTE_ADDR'];
    return isset($attempts[$ip]) && count($attempts[$ip]) >= LOGIN_ATTEMPT_LIMIT;
}

$error = '';
if (is_rate_limited()) {
    $error = 'Too many login attempts. Please try again later.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Credentials logic for Super Admin and Admin
    if ($username === 'superadmin' && $password === '8f4b9a1d2c6e750') {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = 'superadmin';
        $_SESSION['user_role'] = 'super_admin';
        $_SESSION['user_name'] = 'Super Admin';
        header("Location: cms/general-settings.php");
        exit();
    } elseif ($username === 'admin' && $password === '03ac674216f3e15' ) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_name'] = 'Admin';
        header("Location: membership-submission.php");
        exit();
    } else {
        record_login_attempt();
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - ACCS Control Panel</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { "primary": "#137fec" },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                },
            },
        }
    </script>
</head>
<body class="font-display bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md p-8 space-y-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">ACCS Control Panel</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">Log in to manage site content and memberships</p>
        </div>

        <?php if ($error): ?>
            <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800 font-medium" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">Username</label>
                <input type="text" name="username" id="username" required 
                       class="mt-1 h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-transparent dark:text-white" 
                       placeholder="Enter username">
            </div>
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">Password</label>
                <input type="password" name="password" id="password" required
                       class="mt-1 h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-transparent dark:text-white"
                       placeholder="Enter password">
            </div>
            <button type="submit" 
                    class="w-full flex justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-primary/30 transition-all hover:bg-primary/90">
                Log In
        </form>
    </div>
</body>
</html>
