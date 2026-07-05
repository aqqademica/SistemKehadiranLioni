<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Add updated_at to payroll_details if it doesn't exist
    try {
        $db->exec("ALTER TABLE payroll_details ADD COLUMN updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP");
        echo "SUCCESS: Added updated_at column to payroll_details.\n";
    } catch (Exception $e) {
        echo "INFO: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
