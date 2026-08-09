<?php
define('ENVIRONMENT', 'production');
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs';     
    $DB_USER = 'root';        
    $DB_PASS = '';  
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs'; 
    $DB_USER = 'agcinfos_iaccs';    
    $DB_PASS = 'iaccs#1234X';   
}

// Define BASE_URL dynamically from environment variable or current HTTP request host
if (!defined('BASE_URL')) {
    $env_base = getenv('BASE_URL') ?: (isset($_ENV['BASE_URL']) ? $_ENV['BASE_URL'] : null);
    if (!empty($env_base)) {
        define('BASE_URL', rtrim($env_base, '/'));
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:8000';
        define('BASE_URL', $protocol . $host);
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
