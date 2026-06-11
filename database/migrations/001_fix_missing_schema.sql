-- ============================================================
-- Migration 001: Fix Missing Schema Elements
-- Issues: 6.1, 6.2, 6.3, 6.4
-- ============================================================

USE `kehadiran_app`;

-- 6.1 — Add force_change_password to users table
ALTER TABLE `users`
    ADD COLUMN `force_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`;

-- 6.3 — Add deduction_amount to late_deduction_rules
ALTER TABLE `late_deduction_rules`
    ADD COLUMN `deduction_amount` DECIMAL(15,2) NOT NULL DEFAULT 0
    COMMENT 'Jumlah rupiah tetap yang dipotong (alternatif dari persen)'
    AFTER `deduction_percent`;

-- 6.4 — Add urgent_phone and company_name to employees
ALTER TABLE `employees`
    ADD COLUMN `urgent_phone` VARCHAR(25) AFTER `phone`,
    ADD COLUMN `company_name` VARCHAR(150) AFTER `notes`,
    ADD COLUMN `photo_ktp` VARCHAR(255) AFTER `photo`,
    ADD COLUMN `photo_ijazah` VARCHAR(255) AFTER `photo_ktp`;

-- 6.2 — Create leave_types table
CREATE TABLE IF NOT EXISTS `leave_types` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `max_days`    SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = sesuai UU',
    `description` TEXT,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default leave types
INSERT INTO `leave_types` (`name`, `max_days`, `description`) VALUES
    ('Cuti Tahunan', 12, 'Cuti tahunan untuk karyawan masa kerja > 12 bulan'),
    ('Cuti Kehamilan', 90, 'Cuti melahirkan sesuai UU'),
    ('Cuti Orang Tua Meninggal', 3, 'Cuti duka orang tua meninggal'),
    ('Cuti Istri/Suami Meninggal', 3, 'Cuti duka pasangan meninggal'),
    ('Cuti Anak Kandung Meninggal', 3, 'Cuti duka anak kandung meninggal'),
    ('Cuti Saudara Kandung Meninggal', 2, 'Cuti duka saudara kandung meninggal');

-- 6.2 — Create position_salary_components table
CREATE TABLE IF NOT EXISTS `position_salary_components` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `position_id` SMALLINT UNSIGNED NOT NULL,
    `name`        VARCHAR(120) NOT NULL,
    `type`        ENUM('earning','deduction') NOT NULL DEFAULT 'earning',
    `amount`      DECIMAL(15,2) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.2 — Create global_deductions table
CREATE TABLE IF NOT EXISTS `global_deductions` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(120) NOT NULL,
    `percent_amount` DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'Potongan dalam persen dari gaji pokok',
    `fixed_amount`   DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Potongan dalam nominal tetap',
    `description`    TEXT,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.5 — Unique constraint on camera_attendance_logs
ALTER TABLE `camera_attendance_logs`
    ADD UNIQUE KEY `uq_camera_emp_date` (`employee_id`, `log_date`);
