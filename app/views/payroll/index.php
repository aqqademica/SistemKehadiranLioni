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
                <div style="display:flex; gap:10px;">
                    <form action="<?= APP_URL ?>/payroll/run" method="POST" style="margin:0;">
                        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="period_id" value="<?= $openPeriod['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Jalankan kalkulasi payroll untuk periode ini?')">
                            <i class="fas fa-calculator"></i> Run Payroll
                        </button>
                    </form>
                    <form action="<?= APP_URL ?>/payroll/close" method="POST" style="margin:0;">
                        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="period_id" value="<?= $openPeriod['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tutup periode payroll ini? Periode yang telah ditutup tidak dapat diubah lagi.')">
                            <i class="fas fa-lock"></i> Close Period
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> Tidak ada periode payroll yang sedang dibuka.
            </div>

            <div class="card bg-light mb-4 shadow-sm" style="border: 1px solid #ddd;">
                <div class="card-body">
                    <h6 class="card-title font-weight-bold text-dark mb-3"><i class="fas fa-folder-plus text-success"></i> Buka Periode Payroll Baru</h6>
                    <form action="<?= APP_URL ?>/payroll/open" method="POST" class="form-inline" style="gap:15px; display:flex; flex-wrap:wrap; align-items:center;">
                        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                        
                        <div class="form-group">
                            <label for="month" class="mr-2">Bulan: </label>
                            <select name="month" id="month" class="form-control form-control-sm" style="min-width:120px;" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == date('m') ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="year" class="mr-2 ml-md-3">Tahun: </label>
                            <select name="year" id="year" class="form-control form-control-sm" style="min-width:100px;" required>
                                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-folder-open"></i> Buka Periode Baru
                        </button>
                    </form>
                </div>
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
                            <a href="<?= APP_URL ?>/payroll/history?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Lihat Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
