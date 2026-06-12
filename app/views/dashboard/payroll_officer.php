<?php
// app/views/dashboard/payroll_officer.php
?>
<div class="stat-grid">
    <div class="stat-card info">
        <div class="stat-info">
            <div class="stat-value"><?= $stats['total_emp'] ?? 0 ?></div>
            <div class="stat-label">Total Karyawan Aktif</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-info">
            <div class="stat-value"><?= $stats['open_periods'] ?? 0 ?></div>
            <div class="stat-label">Payroll Terbuka</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-info">
            <div class="stat-value">Rp <?= number_format($stats['total_net_bulan_ini'] ?? 0, 0, ',', '.') ?></div>
            <div class="stat-label">Total Payroll Bulan Ini</div>
        </div>
    </div>
</div>
