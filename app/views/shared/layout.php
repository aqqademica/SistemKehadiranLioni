<?php
// Shared layout — semua halaman yang sudah login menggunakan layout ini
// Variabel yang harus tersedia: $pageTitle, $content_view, $activePage
$user     = $_SESSION['user']     ?? [];
$roleName = $_SESSION['role']     ?? '';
$fullName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));

$unreadCount = 0;
$empData = null;
try {
    $db = Database::getInstance();
    $unreadCount = (int) $db->query(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
        [$_SESSION['user_id'] ?? 0]
    )->fetchColumn();

    if (!empty($_SESSION['employee_id'])) {
        $empData = $db->query(
            "SELECT e.employee_code, e.join_date, e.company_name, p.name as position_name 
             FROM employees e 
             LEFT JOIN positions p ON e.position_id = p.id 
             WHERE e.id = ?", 
            [$_SESSION['employee_id']]
        )->fetch();
    }
} catch(Exception $e) {}

// Role display
$roleLabels = [
    'employee'        => 'Karyawan',
    'supervisor'      => 'Supervisor',
    'hrd_admin'       => 'Admin HRD',
    'hrd_manager'     => 'Manager HRD',
    'payroll_officer' => 'Payroll Officer',
];
$roleDisplay = $roleLabels[$roleName] ?? $roleName;

// Menu berdasarkan role
$menus = [];

if ($roleName === 'employee') {
    $menus = [
        ['section' => 'Menu Utama'],
        ['icon' => 'fas fa-home',         'label' => 'Dashboard',        'href' => '/KehadiranApp/public/dashboard'],
        ['icon' => 'fas fa-fingerprint',  'label' => 'Absensi Saya',     'href' => '/KehadiranApp/public/attendance'],
        ['section' => 'Pengajuan'],
        ['icon' => 'fas fa-file-alt',     'label' => 'Tidak Finger',     'href' => '/KehadiranApp/public/requests/tidak-finger'],
        ['icon' => 'fas fa-calendar-plus','label'=> 'Leave/Cuti',        'href' => '/KehadiranApp/public/requests/leave'],
        ['section' => 'Informasi'],
        ['icon' => 'fas fa-tasks',        'label' => 'Status Pengajuan', 'href' => '/KehadiranApp/public/requests'],
        ['icon' => 'fas fa-money-bill',   'label' => 'Slip Gaji',        'href' => '/KehadiranApp/public/my-salary'],
        ['icon' => 'fas fa-bell',         'label' => 'Notifikasi',       'href' => '#'],
    ];
} elseif ($roleName === 'supervisor') {
    $menus = [
        ['section' => 'Menu Utama'],
        ['icon' => 'fas fa-tachometer-alt','label'=> 'Dashboard',        'href' => '/KehadiranApp/public/dashboard'],
        ['icon' => 'fas fa-users',        'label' => 'Tim Saya',         'href' => '/KehadiranApp/public/dashboard'],
        ['section' => 'Approval'],
        ['icon' => 'fas fa-check-circle', 'label' => 'Antrian Approval', 'href' => '/KehadiranApp/public/requests/approvals'],
        ['icon' => 'fas fa-calendar-check','label'=> 'Rekap Kehadiran',  'href' => '/KehadiranApp/public/dashboard'],
        ['section' => 'Informasi'],
        ['icon' => 'fas fa-exclamation-triangle','label'=> 'Warning Letter', 'href' => '#'],
        ['icon' => 'fas fa-bell',         'label' => 'Notifikasi',       'href' => '/KehadiranApp/public/notifications'],
    ];
} elseif ($roleName === 'hrd_admin') {
    $menus = [
        ['section' => 'Menu Utama'],
        ['icon' => 'fas fa-tachometer-alt','label'=> 'Dashboard',        'href' => '/KehadiranApp/public/dashboard'],
        ['section' => 'Kehadiran'],
        ['icon' => 'fas fa-calendar-alt', 'label' => 'Kehadiran (Semua Karyawan)',  'href' => '/KehadiranApp/public/hrd/attendance'],
        ['icon' => 'fas fa-check-circle', 'label' => 'Verifikasi',       'href' => '/KehadiranApp/public/requests/approvals'],
        ['section' => 'Manajemen'],
        ['icon' => 'fas fa-user-friends', 'label' => 'Data Karyawan',    'href' => '/KehadiranApp/public/admin/employees'],
        ['icon' => 'fas fa-hospital',     'label' => 'Mitra Kesehatan',  'href' => '/KehadiranApp/public/hrd/health-partners'],
        ['section' => 'Konfigurasi'],
        ['icon' => 'fas fa-cog',          'label' => 'Pengaturan HRD',   'href' => '/KehadiranApp/public/hrd/settings'],
        ['icon' => 'fas fa-user-plus',    'label' => 'Kelola Akun',      'href' => '/KehadiranApp/public/hrd/accounts'],
        ['icon' => 'fas fa-bell',         'label' => 'Notifikasi',       'href' => '/KehadiranApp/public/notifications'],
    ];
} elseif ($roleName === 'hrd_manager') {
    $menus = [
        ['section' => 'Menu Utama'],
        ['icon' => 'fas fa-tachometer-alt','label'=> 'Dashboard',        'href' => '/KehadiranApp/public/dashboard'],
        ['section' => 'Final Approval'],
        ['icon' => 'fas fa-check-double', 'label' => 'Final Approval',   'href' => '/KehadiranApp/public/requests/approvals'],
        ['icon' => 'fas fa-exclamation-triangle','label'=> 'Warning Letter', 'href' => '#'],
        ['section' => 'Laporan'],
        ['icon' => 'fas fa-chart-bar',    'label' => 'Statistik',        'href' => '#'],
        ['icon' => 'fas fa-bell',         'label' => 'Notifikasi',       'href' => '#'],
        ['section' => 'Konfigurasi'],
        ['icon' => 'fas fa-money-check-alt', 'label' => 'Standar Gaji',   'href' => '/KehadiranApp/public/hrd-manager/salary-config'],
    ];
} elseif ($roleName === 'payroll_officer') {
    $menus = [
        ['section' => 'Menu Utama'],
        ['icon' => 'fas fa-tachometer-alt','label'=> 'Dashboard',        'href' => '/KehadiranApp/public/dashboard'],
        ['section' => 'Payroll'],
        ['icon' => 'fas fa-calculator',   'label' => 'Manajemen Payroll','href' => '/KehadiranApp/public/payroll'],
        ['icon' => 'fas fa-file-invoice-dollar','label'=> 'Slip Gaji',   'href' => '/KehadiranApp/public/payroll'],
        ['icon' => 'fas fa-gift',         'label' => 'Bonus Jabatan',    'href' => '#'],
        ['icon' => 'fas fa-lock',         'label' => 'Payroll Closing',  'href' => '#'],
        ['section' => 'Laporan'],
        ['icon' => 'fas fa-chart-line',   'label' => 'Laporan Payroll',  'href' => '#'],
        ['icon' => 'fas fa-bell',         'label' => 'Notifikasi',       'href' => '#'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($this->generateCsrf(), ENT_QUOTES) ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'KehadiranApp') ?> — KehadiranApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/KehadiranApp/public/css/app.css">
</head>
<body>
<div class="app-wrapper">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-fingerprint"></i></div>
      <div class="brand-name">
        KehadiranApp
        <span><?= htmlspecialchars($roleDisplay) ?></span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($menus as $item): ?>
        <?php if (isset($item['section'])): ?>
          <div class="nav-section-label"><?= $item['section'] ?></div>
        <?php else: ?>
          <div class="nav-item">
            <a href="<?= $item['href'] ?>" class="nav-link <?= ($activePage ?? '') === $item['href'] ? 'active' : '' ?>">
              <i class="<?= $item['icon'] ?>"></i>
              <?= $item['label'] ?>
            </a>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
      <div class="avatar"><?= $initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role"><?= htmlspecialchars($roleDisplay) ?></div>
      </div>
      <a href="/KehadiranApp/public/logout" class="btn-logout" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main-content">

    <!-- NAVBAR -->
    <header class="navbar">
      <button class="navbar-btn" id="sidebarToggle" style="display:none">
        <i class="fas fa-bars"></i>
      </button>
      <div class="navbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></div>
      <div class="navbar-actions">

        <!-- Notification Button -->
        <div style="position:relative; margin-right:15px;">
          <a href="/KehadiranApp/public/notifications" class="navbar-btn" title="Notifikasi" style="text-decoration:none;">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="notif-dot" id="notifCount"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
            <?php else: ?>
              <span class="notif-dot" id="notifCount" style="display:none;"></span>
            <?php endif; ?>
          </a>
        </div>

        <!-- Employee Details -->
        <?php if ($empData): ?>
        <div style="display:flex; flex-direction:column; align-items:flex-end; margin-right:10px; font-size:11px; color:var(--text-muted); line-height:1.2;">
          <div style="font-weight:600; color:var(--text-dark); font-size:12px;"><?= htmlspecialchars($empData['position_name'] ?? 'Karyawan') ?> - <?= htmlspecialchars($empData['employee_code']) ?></div>
          <div>Join: <?= date('d M Y', strtotime($empData['join_date'])) ?></div>
          <?php if (!empty($empData['company_name'])): ?>
            <div style="font-style:italic;"><?= htmlspecialchars($empData['company_name']) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- User badge -->
        <div style="display:flex;align-items:center;gap:10px;padding:6px 12px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;">
          <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#818cf8);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff">
            <?= $initials ?>
          </div>
          <div style="font-size:13px;font-weight:600;color:var(--text-light)"><?= htmlspecialchars($fullName) ?></div>
        </div>
      </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="page-content">
      <?php
        // Flash message
        if (!empty($_SESSION['flash'])):
          $flash = $_SESSION['flash'];
          unset($_SESSION['flash']);
      ?>
        <div class="alert alert-<?= $flash['type'] ?>" id="flashMsg">
          <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
          <?= htmlspecialchars($flash['message']) ?>
        </div>
      <?php endif; ?>

      <?php if (isset($content_view) && file_exists($content_view)): ?>
        <?php include $content_view; ?>
      <?php endif; ?>
    </main>

  </div><!-- .main-content -->
</div><!-- .app-wrapper -->

<script src="/KehadiranApp/public/js/app.js"></script>
</body>
</html>
