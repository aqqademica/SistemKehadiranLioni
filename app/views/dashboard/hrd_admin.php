<?php
// app/views/dashboard/hrd_admin.php
?>
<div class="stat-grid">
    <div class="stat-card primary">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_emp'] ?></div>
            <div class="stat-label">Total Karyawan</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['hadir_hari_ini'] ?></div>
            <div class="stat-label">Hadir Hari Ini</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['pending_verif'] ?></div>
            <div class="stat-label">Pending Approval HRD</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon danger"><i class="fas fa-file-medical"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $stats['pending_sakit'] ?></div>
            <div class="stat-label">Pengajuan Sakit</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns: 2fr 1fr; gap:20px; margin-top:20px;">
    <!-- Recent Requests -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-clock"></i> Pengajuan Terbaru</div>
            <a href="<?= APP_URL ?>/requests/approvals" class="btn btn-primary btn-sm">Lihat Antrian</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($recentRequests)): ?>
                <div style="padding:30px; text-align:center; color:var(--text-muted)">Tidak ada pengajuan terbaru.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRequests as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $req['request_type'])) ?></td>
                                <td><?= date('d M', strtotime($req['attendance_date'])) ?></td>
                                <td><span class="badge badge-warning"><?= $req['workflow_status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Tools -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-tools"></i> System Tools</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:10px;">
            <a href="<?= APP_URL ?>/admin/sync" class="btn btn-outline w-100" style="text-align:left">
                <i class="fas fa-sync"></i> Jalankan Daily Sync
            </a>
            <a href="<?= APP_URL ?>/admin/employees" class="btn btn-outline w-100" style="text-align:left">
                <i class="fas fa-user-cog"></i> Kelola Karyawan
            </a>
            <a href="<?= APP_URL ?>/hrd/attendance" class="btn btn-outline w-100" style="text-align:left">
                <i class="fas fa-calendar-check"></i> Rekap Kehadiran
            </a>
        </div>
    </div>
</div>
