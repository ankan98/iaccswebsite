<?php
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

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

// Set charset (important)
$conn->set_charset('utf8mb4');

return $conn;
