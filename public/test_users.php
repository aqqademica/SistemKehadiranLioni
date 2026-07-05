<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $usersCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $employeesCount = $db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $rolesCount = $db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    
    echo "<h3>Database Statistics</h3>";
    echo "Users count: " . $usersCount . "<br>";
    echo "Employees count: " . $employeesCount . "<br>";
    echo "Roles count: " . $rolesCount . "<br>";
    
    echo "<h3>Users List</h3>";
    $users = $db->query("SELECT id, employee_id, username, role_id, is_active FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($users, true) . "</pre>";
    
    echo "<h3>Employees List</h3>";
    $employees = $db->query("SELECT id, employee_code, first_name, last_name FROM employees")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($employees, true) . "</pre>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
