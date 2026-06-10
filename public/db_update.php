<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();

try {
    $db->query("ALTER TABLE employees 
        ADD COLUMN `photo_ktp` VARCHAR(255) NULL AFTER `photo`,
        ADD COLUMN `photo_ijazah` VARCHAR(255) NULL AFTER `photo_ktp`,
        ADD COLUMN `urgent_phone` VARCHAR(25) NULL AFTER `phone`");
    echo "Success!";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
