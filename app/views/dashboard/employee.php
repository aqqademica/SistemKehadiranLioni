<?php
// Dashboard Karyawan
$statusLabels = [
    'HADIR'        => ['Hadir',         'success'],
    'UNPAID_TANPA' => ['Unpaid (Tanpa Ket.)', 'danger'],
    'UNPAID_DENGAN'=> ['Unpaid (Dengan Ket.)','warning'],
    'HOURLY_UNPAID'=> ['Hourly Unpaid', 'warning'],
    'PAID_LEAVE'   => ['Cuti Tahunan',  'info'],
    'SAKIT'        => ['Sakit',         'primary'],
    'PENDING'      => ['Pending',       'muted'],
    'NO_LOG'       => ['Belum Absen',   'muted'],
];
$todayLabel = $statusLabels[$todayStatus['final_status'] ?? 'NO_LOG'] ?? ['Belum Absen', 'muted'];
?>

<!-- Quick Actions (Aksi Cepat) -->
<div class="card mb-4" style="border:none; box-shadow:0 4px 20px rgba(0,0,0,0.05);">
  <div class="card-header" style="background:transparent; border:none; padding-bottom:0">
    <div class="card-title" style="font-size:1.1rem; color:var(--primary); font-weight:700"><i class="fas fa-bolt"></i> Aksi Cepat</div>
  </div>
  <div class="card-body" style="display:flex; gap:15px; flex-wrap:wrap; padding:20px">
    <a href="/KehadiranApp/public/attendance/camera" class="btn btn-primary btn-lg" style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:8px; padding:20px; border-radius:15px">
      <i class="fas fa-camera" style="font-size:24px"></i>
      <span>Absen Lapangan</span>
    </a>
    <a href="/KehadiranApp/public/requests/tidak-finger" class="btn btn-outline btn-lg" style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:8px; padding:20px; border-radius:15px">
      <i class="fas fa-fingerprint" style="font-size:24px"></i>
      <span>Pengajuan Tidak Finger</span>
    </a>
    <a href="/KehadiranApp/public/requests/leave" class="btn btn-outline btn-lg" style="flex:1; min-width:200px; display:flex; flex-direction:column; align-items:center; gap:8px; padding:20px; border-radius:15px">
      <i class="fas fa-calendar-plus" style="font-size:24px"></i>
      <span>LEAVE (Cuti / Sakit)</span>
    </a>
  </div>
</div>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card <?= $todayLabel[1] ?>">
    <div class="stat-icon <?= $todayLabel[1] ?>"><i class="fas fa-calendar-day"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= $todayLabel[0] ?></div>
      <div class="stat-label">Status Hari Ini — <?= date('d M Y') ?></div>
    </div>
  </div>
  <div class="stat-card success">
    <div class="stat-icon success"><i class="fas fa-check"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= (int)($summary['hadir'] ?? 0) ?></div>
      <div class="stat-label">Hadir Bulan Ini</div>
    </div>
  </div>
  <div class="stat-card info">
    <div class="stat-icon info"><i class="fas fa-umbrella-beach"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($leaveBalance['remaining_days'] ?? 0, 1) ?></div>
      <div class="stat-label">Sisa Cuti Tahunan</div>
    </div>
  </div>
  <div class="stat-card danger">
    <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= (int)($summary['unpaid'] ?? 0) ?></div>
      <div class="stat-label">Unpaid Leave Bulan Ini</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap">

  <!-- Pengajuan Aktif -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-file-alt"></i> Pengajuan Aktif</div>
      <a href="/KehadiranApp/public/requests" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($activeRequests)): ?>
        <div style="padding:30px;text-align:center;color:var(--text-muted)">
          <i class="fas fa-check-circle" style="font-size:32px;opacity:0.3;display:block;margin-bottom:8px"></i>
          Tidak ada pengajuan aktif
        </div>
      <?php else: ?>
        <table>
          <thead><tr>
            <th>Jenis</th><th>Tanggal</th><th>Status</th>
          </tr></thead>
          <tbody>
          <?php foreach ($activeRequests as $req): ?>
            <tr>
              <td><?= ucwords(str_replace('_', ' ', $req['request_type'])) ?></td>
              <td><?= date('d M Y', strtotime($req['attendance_date'])) ?></td>
              <td><span class="badge badge-warning"><?= ucwords(str_replace('_', ' ', $req['workflow_status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Notifikasi -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-bell"></i> Notifikasi Terbaru</div>
      <a href="/KehadiranApp/public/notifications" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($notifications)): ?>
        <div style="padding:30px;text-align:center;color:var(--text-muted)">
          <i class="fas fa-bell-slash" style="font-size:32px;opacity:0.3;display:block;margin-bottom:8px"></i>
          Belum ada notifikasi
        </div>
      <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
          <div class="notif-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
            <div class="notif-item-icon"><i class="fas fa-bell"></i></div>
            <div class="notif-item-text">
              <div class="notif-item-title"><?= htmlspecialchars($notif['title']) ?></div>
              <div class="notif-item-msg"><?= htmlspecialchars($notif['message']) ?></div>
              <div class="notif-item-time"><?= date('d M H:i', strtotime($notif['created_at'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Fingerprint Simulator (Dummy Mode) -->
<div class="card mt-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px dashed #adb5bd;">
  <div class="card-header">
    <div class="card-title text-muted"><i class="fas fa-fingerprint"></i> Fingerprint Simulator (Testing)</div>
  </div>
  <div class="card-body" style="display:flex;gap:12px;justify-content:center;padding:20px">
    <form action="/KehadiranApp/public/attendance/finger" method="POST">
      <input type="hidden" name="_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="type" value="in">
      <button type="submit" class="btn btn-success">
        <i class="fas fa-sign-in-alt"></i> Finger Masuk
      </button>
    </form>
    <form action="/KehadiranApp/public/attendance/finger" method="POST">
      <input type="hidden" name="_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="type" value="out">
      <button type="submit" class="btn btn-danger">
        <i class="fas fa-sign-out-alt"></i> Finger Keluar
      </button>
    </form>
  </div>
</div>

