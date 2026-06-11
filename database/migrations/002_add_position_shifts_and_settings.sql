-- ============================================================
-- Migration 002: Position Shifts and Overtime Settings
-- ============================================================

USE `kehadiran_app`;

-- 1. Create position_shifts table
CREATE TABLE IF NOT EXISTS `position_shifts` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `position_id` SMALLINT UNSIGNED NOT NULL UNIQUE,
    `shift_id`    SMALLINT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insert new system settings
INSERT INTO `system_settings` (`key`, `value`, `type`, `description`) VALUES
('work_week_type', '5', 'integer', 'Tipe hari kerja dalam seminggu (5 atau 6 hari)'),
('overtime_15x_threshold', '1', 'integer', 'Batas jam pertama lembur dengan multiplier 1.5x (UU Cipta Kerja = 1)')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`);
