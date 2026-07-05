<?php
// app/views/payroll/history.php
?>
<div class="card shadow mb-4">
    <div class="card-header py-3" style="display:flex; justify-content:space-between; align-items:center;">
        <h5 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-history"></i> Rincian Payroll: <?= date('F Y', mktime(0,0,0, $period['month'], 1)) ?> <?= $period['year'] ?>
        </h5>
        <a href="<?= APP_URL ?>/payroll" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <table class="table table-bordered" style="width:auto; font-size:14px;">
                <tr>
                    <td width="150"><strong>Status Periode</strong></td>
                    <td>
                        <span class="badge badge-<?= $period['status'] === 'closed' ? 'success' : 'warning' ?>">
                            <?= strtoupper($period['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tanggal Periode</strong></td>
                    <td><?= date('d M Y', strtotime($period['start_date'])) ?> s/d <?= date('d M Y', strtotime($period['end_date'])) ?></td>
                </tr>
                <?php if ($period['closed_at']): ?>
                    <tr>
                        <td><strong>Ditutup Pada</strong></td>
                        <td><?= date('d M Y H:i', strtotime($period['closed_at'])) ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <?php if (empty($details)): ?>
            <div style="padding:40px; text-align:center; color:var(--text-muted);">
                <i class="fas fa-file-invoice-dollar" style="font-size:40px; opacity:0.2; display:block; margin-bottom:12px;"></i>
                Belum ada rincian perhitungan payroll yang dijalankan untuk periode ini.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan/Dept</th>
                            <th style="text-align:right;">Gaji Pokok</th>
                            <th style="text-align:right;">Pendapatan (+)</th>
                            <th style="text-align:right;">Potongan (-)</th>
                            <th style="text-align:right;">Gaji Bersih (Net)</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($d['employee_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></strong></td>
                                <td><small><?= htmlspecialchars($d['position_name']) ?> / <?= htmlspecialchars($d['dept_name']) ?></small></td>
                                <td style="text-align:right;">Rp <?= number_format($d['base_salary'], 0, ',', '.') ?></td>
                                <td style="text-align:right; color:var(--success-color);">Rp <?= number_format($d['total_earnings'] + $d['bonus_amount'], 0, ',', '.') ?></td>
                                <td style="text-align:right; color:var(--danger-color);">-Rp <?= number_format($d['total_deductions'] + $d['tax_deduction'] + $d['loan_deduction'] + $d['other_deduction'], 0, ',', '.') ?></td>
                                <td style="text-align:right; font-weight:700; color:var(--primary-color);">Rp <?= number_format($d['net_pay'], 0, ',', '.') ?></td>
                                <td style="text-align:center;">
                                    <a href="<?= APP_URL ?>/payroll/detail?id=<?= $d['id'] ?>" class="btn btn-primary btn-sm" title="Lihat Slip Gaji">
                                        <i class="fas fa-eye"></i> Slip Gaji
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
