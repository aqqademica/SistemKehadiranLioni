<?php
// app/views/admin/form_employee.php
$isEdit = !empty($employee);
?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <i class="fas <?= $isEdit ? 'fa-user-edit' : 'fa-user-plus' ?>"></i> 
            <?= $isEdit ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru' ?>
        </div>
    </div>
    <div class="card-body">
        <form action="/KehadiranApp/public/admin/employees/<?= $isEdit ? 'update' : 'store' ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $employee['id'] ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <!-- Kolom Kiri -->
                <div>
                    <h4 style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">Informasi Dasar</h4>
                    
                    <div class="form-group mb-3">
                        <label>ID / NIP Karyawan</label>
                        <?php $codeVal = $isEdit ? ($employee['employee_code'] ?? '') : ($suggestedCode ?? 'EMP0001'); ?>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" name="employee_code" id="employee_code" class="form-control" value="<?= htmlspecialchars($codeVal) ?>" required <?= $isEdit ? 'readonly' : '' ?> style="font-family:monospace; font-weight:600; letter-spacing:1px;">
                            <?php if (!$isEdit): ?>
                                <button type="button" onclick="toggleCodeEdit()" id="btn_code_edit" class="btn btn-outline btn-sm" title="Edit manual">
                                    <i class="fas fa-lock" id="icon_code_lock"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">
                            <?= $isEdit ? 'NIP tidak dapat diubah.' : 'ID dibuat otomatis. Klik <i class="fas fa-lock"></i> untuk mengubah manual.' ?>
                        </small>
                    </div>

                    
                    <div class="form-group mb-3">
                        <label>Nama Depan</label>
                        <input type="text" name="first_name" class="form-control" value="<?= $employee['first_name'] ?? '' ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Nama Belakang</label>
                        <input type="text" name="last_name" class="form-control" value="<?= $employee['last_name'] ?? '' ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label>Departemen</label>
                        <select name="department_id" class="form-control" required>
                            <option value="">-- Pilih Departemen --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($employee['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Jabatan</label>
                        <select name="position_id" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?= $pos['id'] ?>" <?= ($employee['position_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pos['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Tanggal Bergabung</label>
                        <input type="date" name="join_date" class="form-control" value="<?= $employee['join_date'] ?? date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Status Karyawan</label>
                        <select name="employment_status" class="form-control" required>
                            <option value="active" <?= ($employee['employment_status'] ?? '') == 'active' ? 'selected' : '' ?>>Aktif</option>
                            <option value="inactive" <?= ($employee['employment_status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                            <option value="resigned" <?= ($employee['employment_status'] ?? '') == 'resigned' ? 'selected' : '' ?>>Resigned</option>
                            <option value="terminated" <?= ($employee['employment_status'] ?? '') == 'terminated' ? 'selected' : '' ?>>Terminated</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Nama Perusahaan (Subcontract/Under Contract)</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($employee['company_name'] ?? '') ?>" placeholder="Misal: PT. ABC">
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div>
                    <h4 style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">Kontak & Dokumen</h4>
                    
                    <div class="form-group mb-3">
                        <label>Nomor Telepon (WhatsApp)</label>
                        <input type="text" name="phone" class="form-control" value="<?= $employee['phone'] ?? '' ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label>Alamat Email</label>
                        <input type="email" name="email" class="form-control" value="<?= $employee['email'] ?? '' ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label>Urgent Number</label>
                        <input type="text" name="urgent_phone" class="form-control" value="<?= $employee['urgent_phone'] ?? '' ?>" placeholder="Nomor keluarga / kerabat">
                    </div>

                    <div class="form-group mb-3">
                        <label>Alamat Tinggal</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label>Gaji Pokok (Rp)</label>
                        <input type="number" name="base_salary" class="form-control" value="<?= $employee['base_salary'] ?? '0' ?>">
                    </div>

                    <div style="background:#f8f9fa; padding:15px; border-radius:5px; margin-top:10px;">
                        <h5 style="margin-top:0;">Upload Dokumen (Max 100KB)</h5>
                        <div class="form-group mb-2">
                            <label>Foto Karyawan</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
                            <?php if (!empty($employee['photo'])): ?>
                                <small><a href="/KehadiranApp/public<?= $employee['photo'] ?>" target="_blank">Lihat Foto Saat Ini</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group mb-2">
                            <label>Foto KTP</label>
                            <input type="file" name="photo_ktp" class="form-control" accept=".jpg,.jpeg,.png">
                            <?php if (!empty($employee['photo_ktp'])): ?>
                                <small><a href="/KehadiranApp/public<?= $employee['photo_ktp'] ?>" target="_blank">Lihat KTP Saat Ini</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group mb-0">
                            <label>Foto Ijazah</label>
                            <input type="file" name="photo_ijazah" class="form-control" accept=".jpg,.jpeg,.png">
                            <?php if (!empty($employee['photo_ijazah'])): ?>
                                <small><a href="/KehadiranApp/public<?= $employee['photo_ijazah'] ?>" target="_blank">Lihat Ijazah Saat Ini</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
                <a href="/KehadiranApp/public/admin/employees" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Karyawan Baru' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var codeIsLocked = true;
function toggleCodeEdit() {
    var input = document.getElementById('employee_code');
    var icon  = document.getElementById('icon_code_lock');
    if (codeIsLocked) {
        input.removeAttribute('readonly');
        input.focus();
        icon.className = 'fas fa-lock-open';
        codeIsLocked = false;
    } else {
        input.setAttribute('readonly', true);
        icon.className = 'fas fa-lock';
        codeIsLocked = true;
    }
}
</script>
