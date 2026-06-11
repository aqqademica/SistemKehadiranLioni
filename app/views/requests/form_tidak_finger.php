<?php
// app/views/requests/form_tidak_finger.php
?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-fingerprint"></i> Pengajuan Tidak Finger</div>
    </div>
    <div class="card-body">
        <form action="<?= APP_URL ?>/requests/tidak-finger" method="POST">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            
            <div class="form-group mb-3">
                <label>Tanggal Kejadian</label>
                <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                <small class="text-muted">Maksimal 2 jam setelah jam masuk/keluar seharusnya.</small>
            </div>

            <div class="form-group mb-3">
                <label>Jenis Lupa Finger</label>
                <select name="finger_type" class="form-control" required>
                    <option value="in">Lupa Finger Masuk</option>
                    <option value="out">Lupa Finger Keluar</option>
                    <option value="both">Lupa Keduanya</option>
                </select>
            </div>

            <div class="form-group mb-4">
                <label>Alasan / Keterangan</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Misal: Perangkat finger error, atau alasan mendesak lainnya" required></textarea>
            </div>

            <div class="alert alert-warning" style="font-size: 13px;">
                <i class="fas fa-info-circle"></i> Pengajuan ini akan melalui proses verifikasi oleh Supervisor, Admin HRD, dan Manager HRD. Jika ditolak, status akan berubah menjadi HOURLY UNPAID LEAVE secara otomatis.
            </div>

            <div style="display:flex;gap:10px;margin-top:20px">
                <a href="<?= APP_URL ?>/requests" class="btn btn-outline" style="flex:1">Batal</a>
                <button type="submit" class="btn btn-primary" style="flex:2">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>
