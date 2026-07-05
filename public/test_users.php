<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== PAYROLL PERIODS ===\n";
    $periods = $db->query("SELECT * FROM payroll_periods")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($periods, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "=== PAYROLL RUNS ===\n";
    $runs = $db->query("SELECT * FROM payroll_runs")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($runs, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "=== PAYROLL DETAILS COUNT ===\n";
    $detailsCount = $db->query("SELECT COUNT(*) FROM payroll_details")->fetchColumn();
    echo "Count: " . $detailsCount . "\n\n";
    
    echo "=== ROLES MAPPING ===\n";
    $roles = $db->query("SELECT u.username, r.name as role_name FROM users u JOIN roles r ON r.id = u.role_id")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($roles, JSON_PRETTY_PRINT) . "\n\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
