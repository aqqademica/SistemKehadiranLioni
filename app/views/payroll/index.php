<?php
// app/views/payroll/index.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-money-check-alt"></i> Manajemen Payroll</div>
    </div>
    <div class="card-body">
        <?php if ($openPeriod): ?>
            <div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h4 style="margin:0">Periode Aktif: <?= date('F Y', mktime(0,0,0, $openPeriod['month'], 1)) ?></h4>
                    <p style="margin:5px 0 0 0; font-size:13px;">Range: <?= date('d M', strtotime($openPeriod['start_date'])) ?> s/d <?= date('d M Y', strtotime($openPeriod['end_date'])) ?></p>
                </div>
                <form action="/KehadiranApp/public/payroll/run" method="POST">
                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="period_id" value="<?= $openPeriod['id'] ?>">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Jalankan kalkulasi payroll untuk periode ini?')">
                        <i class="fas fa-calculator"></i> Run Payroll
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Tidak ada periode payroll yang sedang dibuka.
            </div>
        <?php endif; ?>

        <h3 class="mt-4 mb-3">Riwayat Periode</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Bulan/Tahun</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $p): ?>
                    <tr>
                        <td><strong><?= date('F', mktime(0,0,0, $p['month'], 1)) ?> <?= $p['year'] ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $p['status'] === 'closed' ? 'success' : 'warning' ?>">
                                <?= strtoupper($p['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/KehadiranApp/public/payroll/history?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Lihat Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
