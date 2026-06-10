<?php
// ============================================================
// Entry Point — Front Controller
// ============================================================

// Load konfigurasi
require_once dirname(__DIR__) . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/core/Router.php';
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Model.php';

// Start session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Perpanjang session jika aktif
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    header('Location: ' . APP_URL . '/login?expired=1');
    exit;
}
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

// ============================================================
// Daftar Semua Routes
// ============================================================
$router = new Router();

// Auth
$router->get('/',          'AuthController@login');
$router->get('/login',     'AuthController@login');
$router->post('/login',    'AuthController@processLogin');
$router->get('/logout',    'AuthController@logout');
$router->get('/change-password',  'AuthController@changePassword');
$router->post('/update-password', 'AuthController@updatePassword');

// Dashboard (redirect berdasarkan role)
$router->get('/dashboard', 'DashboardController@index');

// Attendance
$router->get('/attendance',        'AttendanceController@index');
$router->post('/attendance/finger', 'AttendanceController@logFinger');
$router->get('/attendance/camera',  'AttendanceController@camera');
$router->post('/attendance/camera', 'AttendanceController@logCamera');

// Requests
$router->get('/requests',                    'RequestController@index');
$router->get('/requests/tidak-finger',       'RequestController@createTidakFinger');
$router->post('/requests/tidak-finger',      'RequestController@storeTidakFinger');
$router->get('/requests/leave',              'RequestController@createLeave');
$router->post('/requests/leave',             'RequestController@storeLeave');
$router->post('/requests/cancel',            'RequestController@cancelRequest');
$router->post('/requests/update-leave',      'RequestController@updateLeave');
$router->get('/requests/approvals',          'RequestController@approvals');
$router->post('/requests/approvals',         'RequestController@processApproval');

// Admin
$router->get('/admin/sync',                  'AdminController@syncAttendance');
$router->get('/admin/employees',             'AdminController@employees');
$router->get('/admin/employees/create',      'AdminController@createEmployee');
$router->post('/admin/employees/store',      'AdminController@storeEmployee');
$router->get('/admin/employees/edit',        'AdminController@editEmployee');
$router->post('/admin/employees/update',     'AdminController@updateEmployee');
$router->post('/admin/employees/delete',     'AdminController@deleteEmployee');
$router->get('/hrd/attendance',              'AdminController@attendance');
$router->get('/hrd/health-partners',         'AdminController@healthPartners');
$router->post('/hrd/health-partners/store',  'AdminController@storeHealthPartner');
$router->post('/hrd/health-partners/update', 'AdminController@updateHealthPartner');
$router->post('/hrd/health-partners/delete', 'AdminController@deleteHealthPartner');
$router->get('/hrd/settings',                'AdminController@settings');
$router->post('/hrd/shifts/store',           'AdminController@storeShift');
$router->post('/hrd/shifts/update',          'AdminController@updateShift');
$router->post('/hrd/shifts/delete',          'AdminController@deleteShift');
$router->post('/hrd/positions/store',        'AdminController@storePosition');
$router->post('/hrd/positions/update',       'AdminController@updatePosition');
$router->post('/hrd/positions/delete',       'AdminController@deletePosition');
$router->post('/hrd/late-rules/store',       'AdminController@storeLateRule');
$router->post('/hrd/late-rules/update',      'AdminController@updateLateRule');
$router->post('/hrd/late-rules/delete',      'AdminController@deleteLateRule');
$router->post('/hrd/leave-types/store',      'AdminController@storeLeaveType');
$router->post('/hrd/leave-types/update',     'AdminController@updateLeaveType');
$router->post('/hrd/leave-types/delete',     'AdminController@deleteLeaveType');
$router->get('/hrd/accounts',                'AdminController@accounts');
$router->get('/hrd/accounts/search',         'AdminController@searchEmployeeNoAccount');
$router->post('/hrd/accounts/store',         'AdminController@storeAccount');
$router->post('/hrd/accounts/update',        'AdminController@updateAccount');

// HRD Manager Salary Config
$router->get('/hrd-manager/salary-config',                   'HrConfigController@salaryConfig');
$router->post('/hrd-manager/salary-config/position/store',   'HrConfigController@storePositionSalary');
$router->post('/hrd-manager/salary-config/position/update',  'HrConfigController@updatePositionSalary');
$router->post('/hrd-manager/salary-config/position/delete',  'HrConfigController@deletePositionSalary');
$router->post('/hrd-manager/salary-config/overtime/update',  'HrConfigController@updateOvertimeDivider');
$router->post('/hrd-manager/salary-config/deduction/store',  'HrConfigController@storeGlobalDeduction');
$router->post('/hrd-manager/salary-config/deduction/update', 'HrConfigController@updateGlobalDeduction');
$router->post('/hrd-manager/salary-config/deduction/delete', 'HrConfigController@deleteGlobalDeduction');

// Notifications
$router->get('/notifications',               'NotificationController@index');

// Payroll
$router->get('/payroll',                    'PayrollController@index');
$router->post('/payroll/run',               'PayrollController@run');
$router->get('/payroll/detail',             'PayrollController@detail');
$router->get('/my-salary',                  'PayrollController@mySalary');

// ============================================================
// Dispatch
// ============================================================
$uri    = $_SERVER['REQUEST_URI'];
$base   = parse_url(APP_URL, PHP_URL_PATH); // /KehadiranApp/public
$uri    = substr($uri, strlen($base)) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);
