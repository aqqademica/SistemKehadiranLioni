<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $username = 'MGR001_Budi_Santoso';
    
    echo "<h3>Testing findByUsername query for: " . htmlspecialchars($username) . "</h3>";
    
    // Check user row directly
    $userRow = $db->query("SELECT * FROM users WHERE username = 'MGR001_Budi_Santoso'")->fetch(PDO::FETCH_ASSOC);
    echo "Direct user query result:<pre>" . print_r($userRow, true) . "</pre>";
    
    // Check joins individually
    if ($userRow) {
        $empRow = $db->query("SELECT * FROM employees WHERE id = " . (int)$userRow['employee_id'])->fetch(PDO::FETCH_ASSOC);
        echo "Direct employee query result:<pre>" . print_r($empRow, true) . "</pre>";
        
        $roleRow = $db->query("SELECT * FROM roles WHERE id = " . (int)$userRow['role_id'])->fetch(PDO::FETCH_ASSOC);
        echo "Direct role query result:<pre>" . print_r($roleRow, true) . "</pre>";
    }
    
    // Full Join Query
    $q = "SELECT u.*, e.first_name, e.last_name, e.employee_code, e.department_id, e.position_id,
                 r.name AS role_name, r.display_name AS role_display
          FROM users u
          JOIN employees e ON e.id = u.employee_id
          JOIN roles r ON r.id = u.role_id
          WHERE u.username = ? LIMIT 1";
          
    $stmt = $db->prepare($q);
    $stmt->execute([$username]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Full JOIN query result:<pre>" . print_r($res, true) . "</pre>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
