<?php
define('ENVIRONMENT', 'development');
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs';     
    $DB_USER = 'root';        
    $DB_PASS = '';  
    define('BASE_URL', 'http://localhost:8008/accs/payment');
} else {
    // ini_set('display_errors', 0);
    // error_reporting(0);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $DB_HOST = 'localhost';
    $DB_NAME = 'agcinfos_iaccs'; 
    $DB_USER = 'agcinfos_iaccs';    
    $DB_PASS = 'iaccs#1234X';   
    define('BASE_URL', 'https://iaccs.org.in'); 
}

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

// Set charset (important)
$conn->set_charset('utf8mb4');

return $conn;
