<?php
define('ENVIRONMENT', 'production');
define('CAN_DELETE', FALSE);
/**
 * ACCS Membership Admin Panel
 * Features: Sorting, Filtering, XLS Export, Persistent Columns, Pagination, Status Updates
 */

require 'conn.php';
          
$table   = 'membership_requests'; 

return $pdo ?? $GLOBALS['pdo'];