<?php
// app/views/payroll/my_history.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Riwayat Gaji & Slip</div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($history)): ?>
            <div style="padding:40px; text-align:center; color:var(--text-muted);">
                <i class="fas fa-receipt" style="font-size:40px; opacity:0.2; display:block; margin-bottom:12px;"></i>
                Belum ada data slip gaji yang tersedia.
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Gaji Bersih (Net)</th>
                        <th>Tanggal Hitung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><strong><?= date('F Y', mktime(0,0,0, $h['month'], 1)) ?></strong></td>
                            <td style="color:var(--success-color); font-weight:bold;">
                                Rp <?= number_format($h['net_pay'], 0, ',', '.') ?>
                            </td>
                            <td><?= date('d M Y', strtotime($h['created_at'])) ?></td>
                            <td>
                                <a href="/KehadiranApp/public/payroll/detail?id=<?= $h['id'] ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-eye"></i> Lihat Slip
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
