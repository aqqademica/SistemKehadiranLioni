<?php
// app/views/admin/employees.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-user-friends"></i> Data Karyawan</div>
        <a href="/KehadiranApp/public/admin/employees/create" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Tambah Karyawan</a>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($employees)): ?>
            <div style="padding:30px; text-align:center; color:var(--text-muted)">Belum ada data karyawan terdaftar.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Departemen</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($emp['employee_code']) ?></strong></td>
                                <td><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                <td><?= htmlspecialchars($emp['dept_name']) ?></td>
                                <td><?= htmlspecialchars($emp['pos_name']) ?></td>
                                <td>
                                    <?php if ($emp['employment_status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php elseif ($emp['employment_status'] === 'terminated'): ?>
                                        <span class="badge badge-danger">Terminated</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= $emp['employment_status'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <a href="/KehadiranApp/public/admin/employees/edit?id=<?= $emp['id'] ?>" class="btn btn-outline btn-sm" title="Edit Data"><i class="fas fa-edit"></i></a>
                                        <button class="btn btn-outline btn-sm" title="Lihat Profil" onclick='showViewModal(<?= json_encode([
                                            "name" => htmlspecialchars($emp["first_name"] . " " . $emp["last_name"]),
                                            "code" => $emp["employee_code"],
                                            "department" => htmlspecialchars($emp["dept_name"]),
                                            "position" => htmlspecialchars($emp["pos_name"]),
                                            "join_date" => date('d M Y', strtotime($emp["join_date"])),
                                            "status" => ucwords(str_replace('_', ' ', $emp["employment_status"])),
                                            "base_salary" => number_format($emp["base_salary"], 0, ',', '.'),
                                            "address" => htmlspecialchars($emp["address"] ?? "-"),
                                            "phone" => htmlspecialchars($emp["phone"] ?? "-"),
                                            "email" => htmlspecialchars($emp["email"] ?? "-"),
                                            "urgent" => htmlspecialchars($emp["urgent_phone"] ?? "-"),
                                            "company" => htmlspecialchars($emp["company_name"] ?? "-"),
                                            "photo" => $emp["photo"] ? "/KehadiranApp/public" . $emp["photo"] : null,
                                            "ktp" => $emp["photo_ktp"] ? "/KehadiranApp/public" . $emp["photo_ktp"] : null,
                                            "ijazah" => $emp["photo_ijazah"] ? "/KehadiranApp/public" . $emp["photo_ijazah"] : null,
                                            "id" => $emp["id"]
                                        ]) ?>)'><i class="fas fa-eye"></i></button>
                                        <form action="/KehadiranApp/public/admin/employees/delete" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus/menonaktifkan karyawan ini? Akun mereka juga akan dinonaktifkan.');" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color); border-color:var(--danger-color);" title="Hapus Data"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal View Karyawan -->
<div id="viewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:600px; max-height:90vh; overflow-y:auto; margin:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title"><i class="fas fa-address-card"></i> Detail Karyawan</div>
            <button class="btn btn-outline btn-sm" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <div style="text-align:center; margin-bottom:20px;">
                <img id="v_photo" src="" alt="Foto" style="width:100px; height:100px; border-radius:50%; object-fit:cover; background:#eee; display:none; margin:0 auto;">
                <div id="v_no_photo" style="width:100px; height:100px; border-radius:50%; background:#eee; display:flex; align-items:center; justify-content:center; margin:0 auto; color:#aaa;">No Photo</div>
                <h3 id="v_name" style="margin:10px 0 5px 0;"></h3>
                <div id="v_code" style="color:var(--text-muted);"></div>
            </div>
            
            <table class="table" style="margin-bottom:20px;">
                <tr><th style="width:30%;">Nama Perusahaan</th><td id="v_company"></td></tr>
                <tr><th>Departemen</th><td id="v_department"></td></tr>
                <tr><th>Jabatan</th><td id="v_position"></td></tr>
                <tr><th>Tanggal Bergabung</th><td id="v_join_date"></td></tr>
                <tr><th>Status Karyawan</th><td id="v_status"></td></tr>
                <tr><th>Gaji Pokok</th><td id="v_salary"></td></tr>
                <tr><th>Alamat Tinggal</th><td id="v_address"></td></tr>
                <tr><th>Nomor Telepon</th><td id="v_phone"></td></tr>
                <tr><th>Alamat Email</th><td id="v_email"></td></tr>
                <tr><th>Urgent Number</th><td id="v_urgent"></td></tr>
            </table>

            <div style="display:flex; gap:15px; margin-top:15px; margin-bottom:20px;">
                <div style="flex:1;">
                    <strong>Foto KTP</strong>
                    <div style="margin-top:5px; padding:10px; border:1px solid #ddd; text-align:center; border-radius:5px;">
                        <img id="v_ktp" src="" alt="KTP" style="max-width:100%; max-height:150px; display:none;">
                        <span id="v_no_ktp" style="color:#aaa;">Belum diupload</span>
                    </div>
                </div>
                <div style="flex:1;">
                    <strong>Foto Ijazah</strong>
                    <div style="margin-top:5px; padding:10px; border:1px solid #ddd; text-align:center; border-radius:5px;">
                        <img id="v_ijazah" src="" alt="Ijazah" style="max-width:100%; max-height:150px; display:none;">
                        <span id="v_no_ijazah" style="color:#aaa;">Belum diupload</span>
                    </div>
                </div>
            </div>
            
            <div style="text-align:right; border-top:1px solid #eee; padding-top:15px;">
                <a href="#" id="v_edit_btn" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Data</a>
            </div>
        </div>
    </div>
</div>

<script>
function showViewModal(data) {
    document.getElementById('v_name').innerText = data.name;
    document.getElementById('v_code').innerText = data.code;
    
    document.getElementById('v_company').innerText = data.company;
    document.getElementById('v_department').innerText = data.department;
    document.getElementById('v_position').innerText = data.position;
    document.getElementById('v_join_date').innerText = data.join_date;
    document.getElementById('v_status').innerText = data.status;
    document.getElementById('v_salary').innerText = 'Rp ' + data.base_salary;
    
    document.getElementById('v_address').innerText = data.address;
    document.getElementById('v_phone').innerText = data.phone;
    document.getElementById('v_email').innerText = data.email;
    document.getElementById('v_urgent').innerText = data.urgent;
    
    document.getElementById('v_edit_btn').href = '/KehadiranApp/public/admin/employees/edit?id=' + data.id;

    const vPhoto = document.getElementById('v_photo');
    const vNoPhoto = document.getElementById('v_no_photo');
    if (data.photo) {
        vPhoto.src = data.photo;
        vPhoto.style.display = 'block';
        vNoPhoto.style.display = 'none';
    } else {
        vPhoto.style.display = 'none';
        vNoPhoto.style.display = 'flex';
    }

    const vKtp = document.getElementById('v_ktp');
    const vNoKtp = document.getElementById('v_no_ktp');
    if (data.ktp) {
        vKtp.src = data.ktp;
        vKtp.style.display = 'inline-block';
        vNoKtp.style.display = 'none';
    } else {
        vKtp.style.display = 'none';
        vNoKtp.style.display = 'inline';
    }

    const vIjazah = document.getElementById('v_ijazah');
    const vNoIjazah = document.getElementById('v_no_ijazah');
    if (data.ijazah) {
        vIjazah.src = data.ijazah;
        vIjazah.style.display = 'inline-block';
        vNoIjazah.style.display = 'none';
    } else {
        vIjazah.style.display = 'none';
        vNoIjazah.style.display = 'inline';
    }

    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}
</script>
