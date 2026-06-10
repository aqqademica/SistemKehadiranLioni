<?php
// app/views/payroll/detail.php
?>
<div class="card" style="max-width: 800px; margin: 0 auto; border: 1px solid #eee;">
    <div class="card-body" style="padding: 40px;" id="payslip-content">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="margin:0; color:var(--primary-color);">SLIP GAJI</h1>
            <p style="margin:5px 0; color:var(--text-muted);">Periode: <?= date('F Y', mktime(0,0,0, $d['month'], 1)) ?></p>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid #f8f9fa;">
            <div>
                <table style="font-size: 14px;">
                    <tr><td width="120">Nama</td><td>: <strong><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></strong></td></tr>
                    <tr><td>ID Karyawan</td><td>: <?= $d['employee_code'] ?></td></tr>
                    <tr><td>Jabatan</td><td>: <?= $d['position_name'] ?></td></tr>
                    <tr><td>Departemen</td><td>: <?= $d['dept_name'] ?></td></tr>
                </table>
            </div>
            <div style="text-align: right;">
                <p style="margin:0; font-size:12px; color:var(--text-muted);">Dicetak pada: <?= date('d M Y H:i') ?></p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:40px;">
            <!-- Pendapatan -->
            <div>
                <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">PENDAPATAN (+)</h4>
                <table style="width: 100%; font-size: 14px;">
                    <tr><td>Gaji Pokok</td><td style="text-align:right;">Rp <?= number_format($d['base_salary'], 0, ',', '.') ?></td></tr>
                    <tr><td>Lembur</td><td style="text-align:right;">Rp <?= number_format($d['overtime_pay'], 0, ',', '.') ?></td></tr>
                    <tr style="font-weight: bold; border-top: 1px solid #eee;">
                        <td style="padding-top:10px;">Total Pendapatan</td>
                        <td style="text-align:right; padding-top:10px;">Rp <?= number_format($d['total_earnings'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>

            <!-- Potongan -->
            <div>
                <h4 style="border-bottom: 1px solid #eee; padding-bottom: 5px;">POTONGAN (-)</h4>
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td>Terlambat (<?= $d['late_minutes_total'] ?>m)</td>
                        <td style="text-align:right; color:var(--danger-color);">- Rp <?= number_format($d['late_deduction'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Unpaid Leave (<?= $d['unpaid_leave_days'] ?>d)</td>
                        <td style="text-align:right; color:var(--danger-color);">- Rp <?= number_format($d['unpaid_deduction'], 0, ',', '.') ?></td>
                    </tr>
                    <tr style="font-weight: bold; border-top: 1px solid #eee;">
                        <td style="padding-top:10px;">Total Potongan</td>
                        <td style="text-align:right; color:var(--danger-color); padding-top:10px;">Rp <?= number_format($d['total_deductions'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="margin-top: 40px; background: #f8f9fa; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 18px; font-weight: bold; color: #333;">TAKE HOME PAY (Net)</div>
            <div style="font-size: 24px; font-weight: 800; color: var(--success-color);">
                Rp <?= number_format($d['net_pay'], 0, ',', '.') ?>
            </div>
        </div>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 200px;">
                <p>Penerima,</p>
                <div style="height: 60px;"></div>
                <p><strong>( <?= htmlspecialchars($d['first_name']) ?> )</strong></p>
            </div>
            <div style="text-align: center; width: 200px;">
                <p>HR Department,</p>
                <div style="height: 60px;"></div>
                <p><strong>( KehadiranApp Admin )</strong></p>
            </div>
        </div>
    </div>
    <div class="card-footer" style="text-align: center;">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Slip Gaji
        </button>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #payslip-content, #payslip-content * { visibility: visible; }
    #payslip-content { position: absolute; left: 0; top: 0; width: 100%; }
    .card-footer { display: none; }
}
</style>
