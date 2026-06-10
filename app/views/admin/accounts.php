<?php
// app/views/admin/accounts.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-user-shield"></i> Kelola Akun Sistem</div>
        <button class="btn btn-primary btn-sm" onclick="showCreateModal()"><i class="fas fa-plus"></i> Buat Akun Baru</button>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($users)): ?>
            <div style="padding:30px; text-align:center; color:var(--text-muted)">Belum ada data akun pengguna.</div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Karyawan Terkait</th>
                        <th>Role Akses</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td>
                                <?php if ($u['employee_id']): ?>
                                    <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?> <small class="text-muted">(<?= $u['employee_code'] ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-outline"><?= ucwords(str_replace('_', ' ', $u['role_name'] ?? '')) ?></span>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-outline btn-sm" onclick="showEditModal(<?= $u['id'] ?>, <?= $u['role_id'] ?>, <?= $u['is_active'] ?>, '<?= htmlspecialchars($u['username']) ?>')">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create Account -->
<div id="createModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:600px; margin:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title"><i class="fas fa-user-plus"></i> Buat Akun Baru</div>
            <button class="btn btn-outline btn-sm" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            
            <div class="form-group mb-3">
                <label>Cari Karyawan (Tanpa Akun)</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="searchQuery" class="form-control" placeholder="Ketik nama atau ID Karyawan..." style="flex:1" onkeypress="if(event.key === 'Enter') searchEmployee()">
                    <button type="button" class="btn btn-primary" onclick="searchEmployee()"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>

            <div id="searchResults" style="margin-bottom:20px; max-height:150px; overflow-y:auto; border:1px solid #eee; border-radius:5px; display:none;">
                <!-- Hasil pencarian via AJAX -->
            </div>

            <form action="/KehadiranApp/public/hrd/accounts/store" method="POST" id="formCreate" style="display:none; border-top:1px solid #eee; padding-top:20px;">
                <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="employee_id" id="c_employee_id">
                
                <div class="alert alert-info" style="margin-bottom:15px;">
                    <strong>Terpilih:</strong> <span id="c_employee_name"></span> (<span id="c_employee_code"></span>)
                </div>

                <div class="form-group mb-3">
                    <label>Pilih Role / Hak Akses</label>
                    <select name="role_id" class="form-control" required>
                        <option value="">-- Pilih Role --</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-4">
                    <label>Password Awal</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small class="text-muted">Username akan digenerate otomatis menggunakan format <strong>ID_NamaDepan_NamaBelakang</strong>.</small>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Buat Akun</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Modal Edit Account -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:400px; margin:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title"><i class="fas fa-edit"></i> Edit Akun</div>
            <button class="btn btn-outline btn-sm" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <form action="/KehadiranApp/public/hrd/accounts/update" method="POST">
                <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="user_id" id="e_user_id">
                
                <div class="form-group mb-3">
                    <label>Username</label>
                    <input type="text" id="e_username" class="form-control" disabled>
                </div>

                <div class="form-group mb-3">
                    <label>Pilih Role</label>
                    <select name="role_id" id="e_role_id" class="form-control" required>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Status Akun</label>
                    <select name="is_active" id="e_is_active" class="form-control" required>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>

                <div class="form-group mb-4">
                    <label>Ubah Password (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').style.display = 'flex';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('formCreate').style.display = 'none';
    document.getElementById('searchQuery').value = '';
}

function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

function showEditModal(userId, roleId, isActive, username) {
    document.getElementById('e_user_id').value = userId;
    document.getElementById('e_role_id').value = roleId;
    document.getElementById('e_is_active').value = isActive;
    document.getElementById('e_username').value = username;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function searchEmployee() {
    const query = document.getElementById('searchQuery').value;
    const resultsDiv = document.getElementById('searchResults');
    
    fetch(`/KehadiranApp/public/hrd/accounts/search?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = '';
            
            if(data.results.length === 0) {
                resultsDiv.innerHTML = '<div style="padding:15px; text-align:center;">Tidak ada karyawan yang cocok atau semua sudah memiliki akun.</div>';
                return;
            }

            data.results.forEach(emp => {
                const row = document.createElement('div');
                row.style.padding = '10px 15px';
                row.style.borderBottom = '1px solid #eee';
                row.style.display = 'flex';
                row.style.justifyContent = 'space-between';
                row.style.alignItems = 'center';

                row.innerHTML = `
                    <div>
                        <strong>${emp.employee_code}</strong> - ${emp.first_name} ${emp.last_name}<br>
                        <small class="text-muted">${emp.department}</small>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="selectEmployee(${emp.id}, '${emp.employee_code}', '${emp.first_name}', '${emp.last_name}')">
                        Pilih
                    </button>
                `;
                resultsDiv.appendChild(row);
            });
        })
        .catch(err => alert('Gagal mencari data.'));
}

function selectEmployee(id, code, firstName, lastName) {
    document.getElementById('c_employee_id').value = id;
    document.getElementById('c_employee_code').innerText = code;
    document.getElementById('c_employee_name').innerText = firstName + ' ' + lastName;
    
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('formCreate').style.display = 'block';
}
</script>
