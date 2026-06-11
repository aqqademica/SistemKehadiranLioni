<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 text-gray-800">Konfigurasi Shift & Jam Kerja</h2>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'shifts' ? 'active' : '' ?>" href="?tab=shifts">Daftar Shift</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'positions' ? 'active' : '' ?>" href="?tab=positions">Mapping Jabatan</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'settings' ? 'active' : '' ?>" href="?tab=settings">Pengaturan Sistem</a>
        </li>
    </ul>

    <div class="card shadow mb-4">
        <div class="card-body">
            
            <?php if ($tab === 'shifts'): ?>
            <!-- TAB SHIFTS -->
            <div class="d-flex justify-content-between mb-3">
                <h5 class="m-0 font-weight-bold text-primary">Daftar Shift Kerja</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddShift">
                    + Tambah Shift
                </button>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Shift</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['start_time']) ?></td>
                            <td><?= htmlspecialchars($s['end_time']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($shifts)): ?>
                        <tr><td colspan="3" class="text-center">Belum ada data shift.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal Add Shift -->
            <div class="modal fade" id="modalAddShift" tabindex="-1">
                <div class="modal-dialog">
                    <form action="<?= BASE_URL ?>/hrd/store-shift" method="POST" class="modal-content">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Shift Baru</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Nama Shift (Contoh: Shift Pagi)</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Jam Mulai (HH:MM)</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Jam Selesai (HH:MM)</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'positions'): ?>
            <!-- TAB POSITIONS MAPPING -->
            <div class="d-flex justify-content-between mb-3">
                <h5 class="m-0 font-weight-bold text-primary">Mapping Shift Jabatan</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMapShift">
                    + Map Shift ke Jabatan
                </button>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Jabatan</th>
                        <th>Shift yang Ditugaskan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($positionShifts as $ps): ?>
                        <tr>
                            <td><?= htmlspecialchars($ps['position_name']) ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($ps['shift_name']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($positionShifts)): ?>
                        <tr><td colspan="2" class="text-center">Belum ada mapping shift untuk jabatan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Modal Map Shift -->
            <div class="modal fade" id="modalMapShift" tabindex="-1">
                <div class="modal-dialog">
                    <form action="<?= BASE_URL ?>/hrd/store-position-shift" method="POST" class="modal-content">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Assign Shift ke Jabatan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Jabatan</label>
                                <select name="position_id" class="form-select" required>
                                    <option value="">-- Pilih Jabatan --</option>
                                    <?php foreach ($positions as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Pilih Shift</label>
                                <select name="shift_id" class="form-select" required>
                                    <option value="">-- Pilih Shift --</option>
                                    <?php foreach ($shifts as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['start_time'] ?> - <?= $s['end_time'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Simpan Mapping</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'settings'): ?>
            <!-- TAB SETTINGS -->
            <h5 class="m-0 font-weight-bold text-primary mb-3">Pengaturan Sistem Jam Kerja</h5>
            <form action="<?= BASE_URL ?>/hrd/update-work-week" method="POST" class="w-50">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="mb-3">
                    <label>Tipe Hari Kerja dalam Seminggu</label>
                    <select name="work_week_type" class="form-select">
                        <option value="5" <?= $workWeekType == 5 ? 'selected' : '' ?>>5 Hari Kerja (Senin-Jumat)</option>
                        <option value="6" <?= $workWeekType == 6 ? 'selected' : '' ?>>6 Hari Kerja (Senin-Sabtu)</option>
                    </select>
                    <small class="form-text text-muted">Mempengaruhi cara hitung lembur saat akhir pekan.</small>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            </form>

            <?php endif; ?>
        </div>
    </div>
</div>

</div>
