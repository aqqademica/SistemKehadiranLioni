<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0 text-gray-800">Pengajuan Lembur (Overtime)</h2>
        </div>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/requests/overtime" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="mb-3">
                    <label for="attendance_date" class="form-label">Tanggal Lembur <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="attendance_date" name="attendance_date" required max="<?= date('Y-m-d') ?>">
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="start_time" class="form-label">Waktu Mulai <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    <div class="col-md-6">
                        <label for="end_time" class="form-label">Waktu Selesai <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Alasan / Keterangan Pekerjaan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Jelaskan pekerjaan yang dilakukan selama lembur..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>/requests" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>

