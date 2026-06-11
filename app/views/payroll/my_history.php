<?php
// app/views/payroll/my_history.php
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0 text-gray-800">Riwayat Gaji & Lembur</h2>
        </div>
    </div>

    <!-- Running Overtime Card -->
    <?php if (isset($openPeriod) && $openPeriod): ?>
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Lembur Berjalan (<?= date('M Y', strtotime($openPeriod['start_date'])) ?>)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($runningOvertimeHours, 1) ?> Jam
                            </div>
                            <small class="text-muted">Total jam lembur yang disetujui periode ini (Belum dibayarkan).</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history"></i> Riwayat Slip Gaji</h6>
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
</div> <!-- End Container -->
