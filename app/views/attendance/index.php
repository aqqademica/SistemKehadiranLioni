<?php
// app/views/attendance/index.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-calendar-alt"></i> Riwayat Kehadiran</div>
        <div class="card-actions">
            <form method="GET" action="<?= APP_URL ?>/attendance" style="display:flex;gap:10px">
                <select name="month" class="form-control" style="width:120px">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= sprintf('%02d', $m) ?>" <?= $month == $m ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-control" style="width:100px">
                    <?php for($y=date('Y'); $y>=date('Y')-2; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($logs)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                <i class="fas fa-info-circle" style="font-size:40px;opacity:0.2;display:block;margin-bottom:12px"></i>
                Tidak ada data kehadiran untuk periode ini.
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Terlambat</th>
                        <th>Sumber</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($log['attendance_date'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $log['final_status'] === 'HADIR' ? 'success' : ($log['final_status'] === 'NO_LOG' ? 'muted' : 'warning') ?>">
                                    <?= ucwords(str_replace('_', ' ', $log['final_status'])) ?>
                                </span>
                            </td>
                            <td><?= $log['late_minutes'] ?> menit</td>
                            <td><?= ucfirst($log['source']) ?></td>
                            <td><small><?= htmlspecialchars($log['notes'] ?? '-') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
