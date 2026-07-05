<?php
// app/views/admin/statistics.php
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-pie text-primary"></i> Statistik & Analitik Kehadiran</h2>
            <p class="text-muted">Ikhtisar data kepegawaian dan performa kedisiplinan per <strong><?= $currentMonth ?></strong></p>
        </div>
    </div>

    <!-- Summary Stats Row -->
    <div class="row">
        <!-- Active Employees Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Karyawan Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $activeEmp ?> Orang</div>
                            <small class="text-muted"><?= $termEmp ?> dinonaktifkan (Terminated)</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lates Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Kasus Terlambat Bulan Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $attStats['late_count'] ?: 0 ?> Kali</div>
                            <small class="text-muted">Total kehadiran terlambat terdeteksi</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-business-time fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Letters Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total SP Diterbitkan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= array_sum($wlCounts) ?> Kasus</div>
                            <small class="text-muted"><?= $wlCounts['TERMINATION'] ?> Berujung PHK</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Rate Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kehadiran Normal</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $attStats['present_days'] ?: 0 ?> Hari</div>
                            <small class="text-muted">Status HADIR terverifikasi</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-double fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Department Breakdown Column -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sitemap"></i> Distribusi Departemen</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($deptStats)): ?>
                        <p class="text-muted">Tidak ada data departemen.</p>
                    <?php else: ?>
                        <?php 
                        $totalActive = max(1, $activeEmp);
                        foreach ($deptStats as $ds): 
                            $percent = round(($ds['emp_count'] / $totalActive) * 100);
                        ?>
                            <h4 class="small font-weight-bold"><?= htmlspecialchars($ds['name']) ?> <span class="float-right"><?= $ds['emp_count'] ?> Karyawan (<?= $percent ?>%)</span></h4>
                            <div class="progress mb-4">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percent ?>%" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Discipline Breakdowns Column -->
        <div class="col-lg-6 mb-4">
            <!-- Attendance Summary Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-check"></i> Rekap Status Absen Bulan Ini</h6>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="table" style="margin:0;">
                        <tbody>
                            <tr>
                                <td style="padding-left:20px;"><strong>Hadir Normal</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-success"><?= $attStats['present_days'] ?: 0 ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px;"><strong>Terlambat</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-warning"><?= $attStats['late_count'] ?: 0 ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px;"><strong>Cuti Tahunan (Paid Leave)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-info"><?= $attStats['leave_days'] ?: 0 ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px;"><strong>Sakit (Sick Leave)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-info"><?= $attStats['sick_days'] ?: 0 ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px;"><strong>Unpaid Leave (Dengan Keterangan)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-secondary"><?= $attStats['unpaid_excused_days'] ?: 0 ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px; color:var(--danger-color);"><strong>Alpha (Tanpa Keterangan)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-danger"><?= $attStats['alpha_days'] ?: 0 ?></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Warning Letter Levels -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-exclamation-triangle"></i> Breakdown Surat Peringatan (SP)</h6>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="table" style="margin:0;">
                        <tbody>
                            <tr>
                                <td style="padding-left:20px;"><strong>SP 1 (Tingkat Pertama)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-warning"><?= $wlCounts['WL1'] ?> Kasus</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px;"><strong>SP 2 (Tingkat Kedua)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-danger"><?= $wlCounts['WL2'] ?> Kasus</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px;"><strong>SP 3 (Tingkat Ketiga)</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-dark"><?= $wlCounts['WL3'] ?> Kasus</span></td>
                            </tr>
                            <tr>
                                <td style="padding-left:20px; font-weight:700;"><strong>PHK / Pemutusan Hubungan Kerja</strong></td>
                                <td style="text-align:right; padding-right:20px;"><span class="badge badge-dark" style="background-color: #000; color: #fff;"><?= $wlCounts['TERMINATION'] ?> Kasus</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
