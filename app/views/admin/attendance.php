<?php
// app/views/admin/attendance.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-calendar-check"></i> Rekap Kehadiran Karyawan</div>
        <div class="card-actions">
            <form action="<?= APP_URL ?>/hrd/attendance" method="GET" style="display:flex;gap:10px;">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" required>
                <button type="submit" class="btn btn-primary btn-sm">Filter Tanggal</button>
            </form>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($logs)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                <i class="fas fa-search" style="font-size:40px;opacity:0.2;display:block;margin-bottom:12px"></i>
                Tidak ada data kehadiran untuk tanggal ini. 
                <br>Pastikan Anda telah menjalankan Daily Sync.
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>ID Karyawan</th>
                        <th>Status</th>
                        <th>Terlambat</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></td>
                            <td><?= htmlspecialchars($log['employee_code']) ?></td>
                            <td>
                                <span class="badge badge-<?= match($log['final_status']) {
                                    'HADIR' => 'success',
                                    'NO_LOG', 'PENDING' => 'muted',
                                    'SAKIT', 'PAID_LEAVE' => 'info',
                                    default => 'danger' // Unpaid etc.
                                } ?>">
                                    <?= str_replace('_', ' ', $log['final_status']) ?>
                                </span>
                            </td>
                            <td><?= $log['late_minutes'] > 0 ? $log['late_minutes'] . ' menit' : '-' ?></td>
                            <td><small><?= htmlspecialchars($log['notes'] ?? '-') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
