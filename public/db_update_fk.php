<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();

try {
    $db->query("ALTER TABLE users DROP FOREIGN KEY users_ibfk_1;");
    $db->query("ALTER TABLE users MODIFY employee_id INT UNSIGNED NULL;");
    $db->query("ALTER TABLE users ADD CONSTRAINT users_ibfk_1 FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL;");
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
