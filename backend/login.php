<?php
session_start();
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
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === '03ac674216f3e15') {
        // 1. Set the variable
        $_SESSION['loggedin'] = true;
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
    <title>Login - ACCS Membership</title>
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
<body class="font-display bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-white flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8 space-y-6 bg-white dark:bg-slate-800 rounded-xl shadow-lg">
        <h1 class="text-3xl font-bold text-center">ACCS Admin Login</h1>
        <?php if ($error): ?>
            <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label for="username" class="text-sm font-medium text-slate-700 dark:text-slate-200">Username</label>
                <input type="text" name="username" id="username" required 
                       class="mt-1 h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-transparent dark:text-white dark:placeholder:text-slate-400" 
                       placeholder="Enter your username">
            </div>
            <div>
                <label for="password" class="text-sm font-medium text-slate-700 dark:text-slate-200">Password</label>
                <input type="password" name="password" id="password" required
                       class="mt-1 h-10 w-full rounded-lg border-slate-300 bg-transparent px-4 text-sm font-medium text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-primary dark:border-slate-700 dark:bg-transparent dark:text-white dark:placeholder:text-slate-400"
                       placeholder="Enter your password">
            </div>
            <button type="submit" 
                    class="w-full flex justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-primary/30 transition-all hover:bg-primary/90">
                Login
            </button>
        </form>
    </div>
</body>
</html>
