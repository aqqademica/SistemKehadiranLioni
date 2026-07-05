<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $username = 'MGR001_Budi_Santoso';
    
    echo "USER_ROW: ";
    $userRow = $db->query("SELECT id, employee_id, username, role_id, is_active FROM users WHERE username = 'MGR001_Budi_Santoso'")->fetch(PDO::FETCH_ASSOC);
    echo json_encode($userRow) . "\n";
    
    if ($userRow) {
        echo "EMPLOYEE_ROW: ";
        $empRow = $db->query("SELECT id, first_name, last_name FROM employees WHERE id = " . (int)$userRow['employee_id'])->fetch(PDO::FETCH_ASSOC);
        echo json_encode($empRow) . "\n";
        
        echo "ROLE_ROW: ";
        $roleRow = $db->query("SELECT id, name FROM roles WHERE id = " . (int)$userRow['role_id'])->fetch(PDO::FETCH_ASSOC);
        echo json_encode($roleRow) . "\n";
    }
    
    echo "JOIN_ROW: ";
    $q = "SELECT u.id, u.username, u.is_active, e.first_name, r.name AS role_name
          FROM users u
          JOIN employees e ON e.id = u.employee_id
          JOIN roles r ON r.id = u.role_id
          WHERE u.username = ? LIMIT 1";
    $stmt = $db->prepare($q);
    $stmt->execute([$username]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC)) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
