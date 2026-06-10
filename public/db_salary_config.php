<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    
    // Create position_salary_components table
    $db->query("CREATE TABLE IF NOT EXISTS `position_salary_components` (
        `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `position_id`  SMALLINT UNSIGNED NOT NULL,
        `name`         VARCHAR(100) NOT NULL,
        `type`         ENUM('earning','deduction') NOT NULL DEFAULT 'earning',
        `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
        `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Table position_salary_components created successfully.\n";

    // Insert overtime_divider into system_settings if not exists
    $check = $db->query("SELECT id FROM system_settings WHERE `key` = 'overtime_divider'")->fetch();
    if (!$check) {
        $db->query("INSERT INTO system_settings (`key`, `value`, `type`, `description`) VALUES ('overtime_divider', '173', 'integer', 'Variabel Pembagi Overtime sesuai UU Tenaga Kerja')");
        echo "system_settings: overtime_divider added.\n";
    }

    // Create global_deductions table
    $db->query("CREATE TABLE IF NOT EXISTS `global_deductions` (
        `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name`         VARCHAR(100) NOT NULL,
        `percent_amount` DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'Persentase potongan',
        `fixed_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Nominal tetap',
        `description`  TEXT,
        `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
        `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Table global_deductions created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
