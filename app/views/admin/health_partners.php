<?php
// app/views/admin/health_partners.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-hospital"></i> Daftar Mitra Kesehatan</div>
        <button class="btn btn-primary btn-sm" onclick="showPartnerModal()"><i class="fas fa-plus"></i> Tambah Mitra</button>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($partners)): ?>
            <div style="padding:30px; text-align:center; color:var(--text-muted)">Belum ada mitra kesehatan terdaftar.</div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Faskes</th>
                        <th>Tipe</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td><?= $partner['id'] ?></td>
                            <td><strong><?= htmlspecialchars($partner['name']) ?></strong></td>
                            <td><?= ucwords(str_replace('_', ' ', $partner['type'])) ?></td>
                            <td><?= htmlspecialchars($partner['address'] ?? '-') ?></td>
                            <td>
                                <?php if ($partner['is_active']): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <button class="btn btn-outline btn-sm" onclick='showPartnerModal(<?= json_encode($partner) ?>)'>Edit</button>
                                    <form action="<?= APP_URL ?>/hrd/health-partners/delete" method="POST" onsubmit="return confirm('Yakin ingin menghapus mitra ini?');" style="display:inline;">
                                        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="id" value="<?= $partner['id'] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create / Edit Mitra -->
<div id="partnerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:500px; margin:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title" id="pm_title"><i class="fas fa-hospital"></i> Tambah Mitra</div>
            <button class="btn btn-outline btn-sm" onclick="closePartnerModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <form id="pm_form" action="<?= APP_URL ?>/hrd/health-partners/store" method="POST">
                <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="id" id="pm_id">
                
                <div class="form-group mb-3">
                    <label>Nama Faskes</label>
                    <input type="text" name="name" id="pm_name" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label>Tipe Faskes</label>
                    <select name="type" id="pm_type" class="form-control" required>
                        <option value="klinik">Klinik</option>
                        <option value="rumah_sakit">Rumah Sakit</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label>Alamat (Opsional)</label>
                    <textarea name="address" id="pm_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group mb-3">
                    <label>No Telepon (Opsional)</label>
                    <input type="text" name="phone" id="pm_phone" class="form-control">
                </div>
                <div class="form-group mb-4">
                    <label>Status Aktif</label>
                    <select name="is_active" id="pm_is_active" class="form-control" required>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closePartnerModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showPartnerModal(partner = null) {
    const isEdit = !!partner;
    document.getElementById('pm_title').innerHTML = isEdit ? '<i class="fas fa-edit"></i> Edit Mitra' : '<i class="fas fa-hospital"></i> Tambah Mitra';
    document.getElementById('pm_form').action = isEdit ? '/KehadiranApp/public/hrd/health-partners/update' : '/KehadiranApp/public/hrd/health-partners/store';
    
    document.getElementById('pm_id').value = isEdit ? partner.id : '';
    document.getElementById('pm_name').value = isEdit ? partner.name : '';
    document.getElementById('pm_type').value = isEdit ? partner.type : 'klinik';
    document.getElementById('pm_address').value = isEdit ? (partner.address || '') : '';
    document.getElementById('pm_phone').value = isEdit ? (partner.phone || '') : '';
    document.getElementById('pm_is_active').value = isEdit ? partner.is_active : '1';

    document.getElementById('partnerModal').style.display = 'flex';
}

function closePartnerModal() {
    document.getElementById('partnerModal').style.display = 'none';
}
</script>
