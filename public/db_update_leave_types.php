<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    
    // Create leave_types table
    $db->query("CREATE TABLE IF NOT EXISTS `leave_types` (
        `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name`        VARCHAR(100) NOT NULL,
        `max_days`    SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Jumlah hari cuti',
        `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
        `description` TEXT,
        `created_by`  INT UNSIGNED,
        `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    
    echo "Table leave_types created successfully.\n";

    // Also add deduction_amount column to late_deduction_rules for flat amount deductions
    try {
        $db->query("ALTER TABLE late_deduction_rules ADD COLUMN deduction_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER deduction_percent;");
        echo "Column deduction_amount added to late_deduction_rules.\n";
    } catch(Exception $e) {
        echo "deduction_amount: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
