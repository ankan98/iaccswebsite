<?php
date_default_timezone_set('Asia/Kolkata');

if (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof mysqli)) {
    $conn = $GLOBALS['conn'];
    $pdo = $GLOBALS['pdo'] ?? null;
    return $conn;
}
// Set ENVIRONMENT dynamically: 'development', 'staging', or 'production'
if (!defined('ENVIRONMENT')) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (strpos($host, 'agcinfosystem.com') !== false || getenv('ENVIRONMENT') === 'staging') {
        define('ENVIRONMENT', 'staging');
    } elseif ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost:') === 0 || getenv('ENVIRONMENT') === 'development') {
        define('ENVIRONMENT', 'development');
    } else {
        define('ENVIRONMENT', 'production');
    }
}

if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs';     
    $DB_USER = 'root';        
    $DB_PASS = '';  
} elseif (ENVIRONMENT === 'staging') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs_test'; 
    $DB_USER = 'agcinfos_iaccs_test';    
    $DB_PASS = 'iaccs#1234X';   
    define('BASE_URL', 'https://iaccs.agcinfosystem.com'); 
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs'; 
    $DB_USER = 'agcinfos_iaccs';    
    $DB_PASS = 'iaccs#1234X';   
    define('BASE_URL', 'https://iaccs.org.in');
}

// Define BASE_URL dynamically from environment variable or current HTTP request host if not defined above
if (!defined('BASE_URL')) {
    $env_base = getenv('BASE_URL') ?: (isset($_ENV['BASE_URL']) ? $_ENV['BASE_URL'] : null);
    if (!empty($env_base)) {
        define('BASE_URL', rtrim($env_base, '/'));
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        define('BASE_URL', !empty($host) ? ($protocol . $host) : '');
    }
}

@$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Fallback logic if staging database credentials fail
if ($conn->connect_error) {
    @$conn = new mysqli('localhost', 'agcinfos_iaccs', 'iaccs#1234X', 'agcinfos_iaccs');
}

if ($conn->connect_error) {
    @$conn = new mysqli('localhost', 'root', '', 'agcinfos_iaccs');
}

// Check connection final status
if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

// Set charset & timezone (Asia/Kolkata +05:30)
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+05:30'");

// Create PDO instance for scripts that require PDO
$pdo = null;
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '+05:30'");
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=agcinfos_iaccs;charset=utf8mb4", "agcinfos_iaccs", "iaccs#1234X");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("SET time_zone = '+05:30'");
    } catch (PDOException $e2) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=agcinfos_iaccs;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e3) {
            $pdo = null;
        }
    }
}

$GLOBALS['pdo'] = $pdo;
$GLOBALS['conn'] = $conn;

return $conn;
