<?php
// app/views/dashboard/hrd_manager.php
?>
<div class="stat-grid">
    <div class="stat-card warning">
        <div class="stat-info">
            <div class="stat-value"><?= $stats['pending_final'] ?? 0 ?></div>
            <div class="stat-label">Menunggu Final Approval</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-info">
            <div class="stat-value"><?= $stats['wl_bulan_ini'] ?? 0 ?></div>
            <div class="stat-label">Warning Letter (Bulan Ini)</div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_emp'] ?? 0 ?></div>
            <div class="stat-label">Total Karyawan Aktif</div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><div class="card-title">Antrian Final Approval</div></div>
    <div class="card-body">
        <?php if(empty($pendingFinal)): ?>
            <p class="text-muted">Tidak ada data.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Nama</th><th>Jenis</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach($pendingFinal as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                            <td><?= htmlspecialchars($req['request_type']) ?></td>
                            <td><a href="<?= APP_URL ?>/requests/approvals" class="btn btn-sm btn-primary">Proses</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
