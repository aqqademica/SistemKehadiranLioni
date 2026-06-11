<?php
// app/views/dashboard/supervisor.php
?>
<div class="stat-grid">
    <div class="stat-card success">
        <div class="stat-icon success"><i class="fas fa-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $teamSummary['hadir'] ?></div>
            <div class="stat-label">Hadir Hari Ini (Tim)</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon warning"><i class="fas fa-user-clock"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= count($pendingApprovals) ?></div>
            <div class="stat-label">Menunggu Approval Anda</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon danger"><i class="fas fa-user-times"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $teamSummary['unpaid'] ?></div>
            <div class="stat-label">Total Alpha (Bulan Ini)</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">
    <!-- Pending Approvals -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-tasks"></i> Pengajuan Tim (Termasuk Info Sakit)</div>
            <a href="<?= APP_URL ?>/requests/approvals" class="btn btn-primary btn-sm">Buka Antrian</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($pendingApprovals)): ?>
                <div style="padding:30px; text-align:center; color:var(--text-muted)">Tidak ada antrian approval.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingApprovals as $req): ?>
                            <tr>
                                <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $req['request_type'])) ?></td>
                                <td><?= date('d M', strtotime($req['attendance_date'])) ?></td>
                                <td>
                                    <?php if ($req['request_type'] === 'sakit'): ?>
                                        <span class="badge badge-info">Info Sakit</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Butuh Approval</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Team Status -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-user-slash"></i> Belum Absen Hari Ini</div>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($notPresent)): ?>
                <div style="padding:30px; text-align:center; color:var(--text-muted)">Semua anggota tim sudah absen.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notPresent as $emp): ?>
                            <tr>
                                <td><?= $emp['employee_code'] ?></td>
                                <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
