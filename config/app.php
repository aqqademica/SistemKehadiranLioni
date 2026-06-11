<?php
// ============================================================
// Konfigurasi Utama Aplikasi KehadiranApp
// ============================================================

// Setup Env
require_once dirname(__DIR__) . '/core/Env.php';
Env::load(dirname(__DIR__) . '/.env');

define('APP_NAME',    'KehadiranApp');
define('APP_VERSION', '1.0.0');
define('APP_URL',     $_ENV['APP_URL'] ?? 'https://internalsys.my.id');
define('APP_ENV',     $_ENV['APP_ENV'] ?? 'production');

// Path
define('ROOT_PATH',   dirname(__DIR__));
define('APP_PATH',    ROOT_PATH . '/app');
define('VIEW_PATH',   APP_PATH  . '/views');
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads');

// Upload limits
define('MAX_UPLOAD_SIZE',   10 * 1024 * 1024); // 10 MB
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// Session
define('SESSION_LIFETIME', 7200); // 2 jam (detik)

// Pagination
define('DEFAULT_PER_PAGE', 20);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (matikan di production)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
