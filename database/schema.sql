-- ============================================================
-- KehadiranApp — Database Schema
-- Versi: 1.0 | Dibuat: 2026-05
-- ============================================================

CREATE DATABASE IF NOT EXISTS `kehadiran_app`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `kehadiran_app`;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================
-- TABEL MASTER
-- ============================================================

CREATE TABLE IF NOT EXISTS `roles` (
    `id`           TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(30)  NOT NULL UNIQUE COMMENT 'employee|supervisor|hrd_admin|hrd_manager|payroll_officer',
    `display_name` VARCHAR(60)  NOT NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `code`        VARCHAR(20)  NOT NULL UNIQUE,
    `description` TEXT,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `positions` (
    `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `department_id` SMALLINT UNSIGNED NOT NULL,
    `name`          VARCHAR(100) NOT NULL,
    `code`          VARCHAR(20)  NOT NULL UNIQUE,
    `level`         ENUM('manager','supervisor','staff','operator') NOT NULL DEFAULT 'staff',
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shifts` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(60)  NOT NULL,
    `start_time`  TIME         NOT NULL,
    `end_time`    TIME         NOT NULL,
    `is_overnight` TINYINT(1)  NOT NULL DEFAULT 0 COMMENT 'Shift melewati tengah malam',
    `work_hours`  DECIMAL(4,2) NOT NULL DEFAULT 8.00,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `national_holidays` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `date`        DATE         NOT NULL,
    `name`        VARCHAR(120) NOT NULL,
    `description` VARCHAR(255),
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_holiday_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `health_partners` (
    `id`               SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(150) NOT NULL,
    `type`             ENUM('klinik','rumah_sakit') NOT NULL,
    `address`          TEXT,
    `phone`            VARCHAR(25),
    `is_bpjs_affiliated` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active`        TINYINT(1)   NOT NULL DEFAULT 1,
    `notes`            TEXT,
    `created_by`       INT UNSIGNED,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KONFIGURASI SISTEM (Configurable oleh HRD)
-- ============================================================

CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(80)  NOT NULL UNIQUE,
    `value`       TEXT         NOT NULL,
    `type`        ENUM('integer','decimal','string','boolean','json') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255),
    `updated_by`  INT UNSIGNED,
    `updated_at`  TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `late_deduction_rules` (
    `id`                SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `min_minutes`       SMALLINT UNSIGNED NOT NULL COMMENT 'Mulai dari menit ke-N',
    `max_minutes`       SMALLINT UNSIGNED NOT NULL COMMENT 'Sampai menit ke-N (0 = tidak terbatas)',
    `deduction_percent` DECIMAL(5,2)      NOT NULL COMMENT '% dari hourly rate yang dipotong',
    `description`       VARCHAR(150),
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`        INT UNSIGNED,
    `created_at`        TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP  NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `allowance_deduction_rules` (
    `id`                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(120) NOT NULL,
    `condition_type`      ENUM('late_count','absent_count','wl_count') NOT NULL,
    `condition_threshold` SMALLINT UNSIGNED NOT NULL COMMENT 'Jumlah kejadian pemicu',
    `condition_period`    ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    `affected_component`  VARCHAR(80)  NOT NULL COMMENT 'Kode komponen yang dipotong/dihapus',
    `action`              ENUM('remove','reduce_percent','reduce_amount') NOT NULL DEFAULT 'remove',
    `action_value`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    `description`         TEXT,
    `is_active`           TINYINT(1)   NOT NULL DEFAULT 1,
    `created_by`          INT UNSIGNED,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KARYAWAN & AKUN
-- ============================================================

CREATE TABLE IF NOT EXISTS `employees` (
    `id`                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `employee_code`     VARCHAR(20)      NOT NULL UNIQUE COMMENT 'ID Karyawan, misal EMP001',
    `first_name`        VARCHAR(60)      NOT NULL,
    `last_name`         VARCHAR(60)      NOT NULL,
    `department_id`     SMALLINT UNSIGNED NOT NULL,
    `position_id`       SMALLINT UNSIGNED NOT NULL,
    `join_date`         DATE             NOT NULL,
    `employment_status` ENUM('active','inactive','resigned','terminated') NOT NULL DEFAULT 'active',
    `phone`             VARCHAR(25),
    `email`             VARCHAR(120),
    `address`           TEXT,
    `photo`             VARCHAR(255),
    `base_salary`       DECIMAL(15,2)    NOT NULL DEFAULT 0,
    `notes`             TEXT,
    `created_by`        INT UNSIGNED,
    `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`position_id`)   REFERENCES `positions`(`id`)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`   INT UNSIGNED NOT NULL UNIQUE,
    `username`      VARCHAR(120) NOT NULL UNIQUE COMMENT 'IDKaryawan_FirstName_LastName',
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id`       TINYINT UNSIGNED NOT NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `last_login`    DATETIME,
    `created_by`    INT UNSIGNED,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`)     REFERENCES `roles`(`id`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_shifts` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `employee_id`    INT UNSIGNED      NOT NULL,
    `shift_id`       SMALLINT UNSIGNED NOT NULL,
    `effective_date` DATE              NOT NULL,
    `end_date`       DATE,
    `created_by`     INT UNSIGNED,
    `created_at`     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`shift_id`)    REFERENCES `shifts`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_leave_balances` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`    INT UNSIGNED NOT NULL,
    `year`           SMALLINT     NOT NULL,
    `total_days`     TINYINT      NOT NULL DEFAULT 12,
    `used_days`      DECIMAL(4,1) NOT NULL DEFAULT 0,
    `remaining_days` DECIMAL(4,1) NOT NULL DEFAULT 12,
    `updated_at`     TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_emp_leave_year` (`employee_id`, `year`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `two_factor_tokens` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `token`      VARCHAR(64)  NOT NULL,
    `type`       ENUM('reset_password','login_2fa') NOT NULL DEFAULT 'reset_password',
    `expires_at` DATETIME     NOT NULL,
    `used`       TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
