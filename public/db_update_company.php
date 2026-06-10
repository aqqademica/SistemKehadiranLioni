<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $db->query("ALTER TABLE employees ADD COLUMN company_name VARCHAR(150) NULL AFTER employment_status;");
    echo "Column company_name added successfully to employees table.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column company_name already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
