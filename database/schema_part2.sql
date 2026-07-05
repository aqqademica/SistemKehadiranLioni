-- ============================================================
-- KehadiranApp — Schema Part 2
-- Tabel: Kehadiran, Pengajuan, Payroll, Disiplin, Sistem
-- ============================================================

USE `kehadiran_app`;

SET FOREIGN_KEY_CHECKS = 0;

-- KEHADIRAN

CREATE TABLE IF NOT EXISTS `finger_logs` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`   INT UNSIGNED NOT NULL,
    `log_date`      DATE         NOT NULL,
    `timestamp_in`  DATETIME,
    `timestamp_out` DATETIME,
    `device_id`     VARCHAR(50),
    `location`      VARCHAR(100),
    `raw_status`    ENUM('valid','invalid','missing_in','missing_out','missing_both') NOT NULL DEFAULT 'valid',
    `is_dummy`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_finger_emp_date` (`employee_id`, `log_date`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `camera_attendance_logs` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`     INT UNSIGNED NOT NULL,
    `log_date`        DATE         NOT NULL,
    `timestamp_in`    DATETIME,
    `timestamp_out`   DATETIME,
    `photo_selfie`    VARCHAR(255),
    `photo_colleague` VARCHAR(255),
    `photo_client`    VARCHAR(255),
    `latitude`        DECIMAL(10,8),
    `longitude`       DECIMAL(11,8),
    `status`          ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    `verified_by`     INT UNSIGNED,
    `verified_at`     DATETIME,
    `notes`           TEXT,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_attendance_status` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `employee_id`     INT UNSIGNED  NOT NULL,
    `attendance_date` DATE          NOT NULL,
    `final_status`    ENUM('HADIR','UNPAID_TANPA','UNPAID_DENGAN','HOURLY_UNPAID','PAID_LEAVE','SAKIT','PENDING','NO_LOG') NOT NULL DEFAULT 'NO_LOG',
    `late_minutes`    SMALLINT      NOT NULL DEFAULT 0,
    `overtime_hours`  DECIMAL(4,2)  NOT NULL DEFAULT 0,
    `late_deduction`  DECIMAL(12,2) NOT NULL DEFAULT 0,
    `shift_id`        SMALLINT UNSIGNED,
    `source`          ENUM('finger','camera','manual') NOT NULL DEFAULT 'finger',
    `is_locked`       TINYINT(1)    NOT NULL DEFAULT 0,
    `notes`           TEXT,
    `resolved_by`     INT UNSIGNED,
    `resolved_at`     DATETIME,
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_att_emp_date` (`employee_id`, `attendance_date`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PENGAJUAN (WORKFLOW)

CREATE TABLE IF NOT EXISTS `attendance_requests` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`             INT UNSIGNED NOT NULL,
    `request_type`            ENUM('tidak_finger','tidak_hadir','hourly_leave','paid_leave','sakit','overtime') NOT NULL,
    `attendance_date`         DATE         NOT NULL,
    `workflow_status`         ENUM('draft','submitted','pending_supervisor','pending_hrd','pending_manager','approved','rejected','auto_converted') NOT NULL DEFAULT 'draft',
    `final_attendance_status` ENUM('HADIR','UNPAID_TANPA','UNPAID_DENGAN','HOURLY_UNPAID','PAID_LEAVE','SAKIT'),
    `submitted_at`            DATETIME,
    `deadline_submit`         DATETIME,
    `deadline_supervisor`     DATETIME,
    `deadline_hrd`            DATETIME,
    `notes`                   TEXT,
    `created_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `request_approvals` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`  INT UNSIGNED NOT NULL,
    `approver_id` INT UNSIGNED NOT NULL,
    `role`        VARCHAR(30)  NOT NULL,
    `decision`    ENUM('approve','reject') NOT NULL,
    `notes`       TEXT,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`request_id`)  REFERENCES `attendance_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approver_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tidak_finger_requests` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`  INT UNSIGNED NOT NULL UNIQUE,
    `finger_type` ENUM('in','out','both') NOT NULL DEFAULT 'in',
    `reason`      TEXT NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`request_id`) REFERENCES `attendance_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`      INT UNSIGNED NOT NULL UNIQUE,
    `leave_type`      ENUM('annual','half_day','legal') NOT NULL DEFAULT 'annual',
    `legal_type`      ENUM('kehamilan','ortu_meninggal','pasangan_meninggal','anak_meninggal','saudara_sedarah_meninggal','saudara_tdk_sedarah_meninggal'),
    `is_half_day`     TINYINT(1)   NOT NULL DEFAULT 0,
    `half_day_period` ENUM('morning','afternoon'),
    `start_date`      DATE         NOT NULL,
    `end_date`        DATE         NOT NULL,
    `total_days`      DECIMAL(4,1) NOT NULL DEFAULT 1,
    `reason`          TEXT,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`request_id`) REFERENCES `attendance_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sick_requests` (
    `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `request_id`           INT UNSIGNED  NOT NULL UNIQUE,
    `provider_type`        ENUM('klinik','rumah_sakit') NOT NULL,
    `provider_name`        VARCHAR(150)  NOT NULL,
    `health_partner_id`    SMALLINT UNSIGNED,
    `illness_description`  TEXT,
    `document_type`        ENUM('upload','bpjs_link') NOT NULL DEFAULT 'upload',
    `document_path`        VARCHAR(255),
    `bpjs_link`            VARCHAR(500),
    `bpjs_link_accessible` TINYINT(1),
    `upload_deadline`      DATE NOT NULL,
    `uploaded_at`          DATETIME,
    `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`request_id`)        REFERENCES `attendance_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`health_partner_id`) REFERENCES `health_partners`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hourly_leave_requests` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `request_id`      INT UNSIGNED     NOT NULL UNIQUE,
    `hours_requested` TINYINT UNSIGNED NOT NULL,
    `start_time`      TIME             NOT NULL,
    `reason`          TEXT,
    `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`request_id`) REFERENCES `attendance_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `overtime_requests` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`    INT UNSIGNED NOT NULL UNIQUE,
    `overtime_date` DATE         NOT NULL,
    `start_time`    TIME         NOT NULL,
    `end_time`      TIME         NOT NULL,
    `hours`         DECIMAL(4,2) NOT NULL,
    `reason`        TEXT,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`request_id`) REFERENCES `attendance_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAYROLL

CREATE TABLE IF NOT EXISTS `payroll_components` (
    `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`        VARCHAR(30)  NOT NULL UNIQUE,
    `name`        VARCHAR(100) NOT NULL,
    `type`        ENUM('earning','deduction','bonus') NOT NULL,
    `is_fixed`    TINYINT(1)   NOT NULL DEFAULT 1,
    `is_taxable`  TINYINT(1)   NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `description` TEXT,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_salary_components` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `employee_id`    INT UNSIGNED      NOT NULL,
    `component_id`   SMALLINT UNSIGNED NOT NULL,
    `amount`         DECIMAL(15,2)     NOT NULL DEFAULT 0,
    `effective_date` DATE              NOT NULL,
    `end_date`       DATE,
    `created_by`     INT UNSIGNED,
    `created_at`     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP         NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`)  REFERENCES `employees`(`id`)          ON DELETE CASCADE,
    FOREIGN KEY (`component_id`) REFERENCES `payroll_components`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_periods` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `month`      TINYINT      NOT NULL,
    `year`       SMALLINT     NOT NULL,
    `start_date` DATE         NOT NULL,
    `end_date`   DATE         NOT NULL,
    `status`     ENUM('open','processing','closed') NOT NULL DEFAULT 'open',
    `created_by` INT UNSIGNED,
    `closed_by`  INT UNSIGNED,
    `closed_at`  DATETIME,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payroll_period` (`month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_runs` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `period_id` INT UNSIGNED NOT NULL,
    `run_by`    INT UNSIGNED NOT NULL,
    `run_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes`     TEXT,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_details` (
    `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `run_id`             INT UNSIGNED  NOT NULL,
    `employee_id`        INT UNSIGNED  NOT NULL,
    `base_salary`        DECIMAL(15,2) NOT NULL DEFAULT 0,
    `work_days`          TINYINT       NOT NULL DEFAULT 0,
    `present_days`       TINYINT       NOT NULL DEFAULT 0,
    `late_count`         TINYINT       NOT NULL DEFAULT 0,
    `late_minutes_total` SMALLINT      NOT NULL DEFAULT 0,
    `late_deduction`     DECIMAL(15,2) NOT NULL DEFAULT 0,
    `unpaid_leave_days`  DECIMAL(4,1)  NOT NULL DEFAULT 0,
    `unpaid_deduction`   DECIMAL(15,2) NOT NULL DEFAULT 0,
    `hourly_leave_hours` DECIMAL(5,2)  NOT NULL DEFAULT 0,
    `hourly_deduction`   DECIMAL(15,2) NOT NULL DEFAULT 0,
    `overtime_hours`     DECIMAL(5,2)  NOT NULL DEFAULT 0,
    `overtime_pay`       DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_earnings`     DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_deductions`   DECIMAL(15,2) NOT NULL DEFAULT 0,
    `bonus_amount`       DECIMAL(15,2) NOT NULL DEFAULT 0,
    `gross_pay`          DECIMAL(15,2) NOT NULL DEFAULT 0,
    `tax_deduction`      DECIMAL(15,2) NOT NULL DEFAULT 0,
    `loan_deduction`     DECIMAL(15,2) NOT NULL DEFAULT 0,
    `other_deduction`    DECIMAL(15,2) NOT NULL DEFAULT 0,
    `net_pay`            DECIMAL(15,2) NOT NULL DEFAULT 0,
    `notes`              TEXT,
    `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_run_emp` (`run_id`, `employee_id`),
    FOREIGN KEY (`run_id`)      REFERENCES `payroll_runs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_component_details` (
    `id`                INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `payroll_detail_id` INT UNSIGNED      NOT NULL,
    `component_id`      SMALLINT UNSIGNED NOT NULL,
    `amount`            DECIMAL(15,2)     NOT NULL DEFAULT 0,
    `notes`             VARCHAR(255),
    PRIMARY KEY (`id`),
    FOREIGN KEY (`payroll_detail_id`) REFERENCES `payroll_details`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`component_id`)      REFERENCES `payroll_components`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `position_bonuses` (
    `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `period_id`   INT UNSIGNED      NOT NULL,
    `position_id` SMALLINT UNSIGNED NOT NULL,
    `amount`      DECIMAL(15,2)     NOT NULL DEFAULT 0,
    `notes`       TEXT,
    `approved_by` INT UNSIGNED,
    `approved_at` DATETIME,
    `created_by`  INT UNSIGNED,
    `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`)       ON DELETE RESTRICT,
    FOREIGN KEY (`period_id`)   REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payslips` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payroll_detail_id` INT UNSIGNED NOT NULL UNIQUE,
    `slip_number`       VARCHAR(40)  NOT NULL UNIQUE,
    `generated_at`      DATETIME     NOT NULL,
    `generated_by`      INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`payroll_detail_id`) REFERENCES `payroll_details`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DISIPLIN & SISTEM

CREATE TABLE IF NOT EXISTS `warning_letters` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`     INT UNSIGNED NOT NULL,
    `wl_type`         ENUM('WL1','WL2','WL3','TERMINATION') NOT NULL,
    `trigger_reason`  TEXT         NOT NULL,
    `trigger_date`    DATE         NOT NULL,
    `issued_by`       INT UNSIGNED NOT NULL,
    `issued_at`       DATETIME     NOT NULL,
    `acknowledged_by` INT UNSIGNED,
    `acknowledged_at` DATETIME,
    `notes`           TEXT,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `title`        VARCHAR(150) NOT NULL,
    `message`      TEXT         NOT NULL,
    `type`         VARCHAR(40)  NOT NULL DEFAULT 'info',
    `related_type` VARCHAR(40),
    `related_id`   INT UNSIGNED,
    `is_read`      TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user` (`user_id`, `is_read`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `endpoint`   TEXT         NOT NULL,
    `p256dh`     TEXT         NOT NULL,
    `auth`       TEXT         NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED,
    `action`     VARCHAR(60)  NOT NULL,
    `table_name` VARCHAR(60)  NOT NULL,
    `record_id`  INT UNSIGNED,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user`  (`user_id`),
    KEY `idx_audit_table` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
