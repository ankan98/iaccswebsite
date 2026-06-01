<?php
define('ENVIRONMENT', 'development');
define('CAN_DELETE', FALSE);
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
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    $db_host = 'localhost';
    $db_name = 'agcinfos_iaccs'; 
    $db_user = 'agcinfos_iaccs';    
    $db_pass = 'iaccs#1234X';    
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

return $pdo;