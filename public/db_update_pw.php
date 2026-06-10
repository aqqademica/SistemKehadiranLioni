<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();

try {
    $db->query("ALTER TABLE users ADD COLUMN force_change_password TINYINT(1) NOT NULL DEFAULT 0;");
    echo "Success!";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
