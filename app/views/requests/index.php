<?php
// app/views/requests/index.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-file-alt"></i> Pengajuan Saya</div>
        <div class="card-actions">
        <div class="card-actions" style="display:flex; gap:10px;">
            <a href="<?= APP_URL ?>/requests/tidak-finger" class="btn btn-outline btn-sm"><i class="fas fa-fingerprint"></i> Tidak Finger</a>
            <a href="<?= APP_URL ?>/requests/leave" class="btn btn-primary btn-sm"><i class="fas fa-calendar-plus"></i> Cuti / Sakit / Izin</a>
        </div>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($requests)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                <i class="fas fa-file-invoice" style="font-size:40px;opacity:0.2;display:block;margin-bottom:12px"></i>
                Anda belum memiliki riwayat pengajuan.
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tgl Pengajuan</th>
                        <th>Jenis</th>
                        <th>Untuk Tanggal</th>
                        <th>Status</th>
                        <th>Update Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $req['request_type'])) ?></td>
                            <td>
                                <?php if ($req['start_date'] != $req['end_date']): ?>
                                    <?= date('d M Y', strtotime($req['start_date'])) ?> - <?= date('d M Y', strtotime($req['end_date'])) ?>
                                <?php else: ?>
                                    <?= date('d M Y', strtotime($req['start_date'])) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= match($req['workflow_status']) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'draft'    => 'muted',
                                    default    => 'warning'
                                } ?>" style="margin-bottom:5px; display:inline-block;">
                                    <?= ucwords(str_replace('_', ' ', $req['workflow_status'])) ?>
                                </span>
                                <?php if (!empty($req['approver_notes'])): ?>
                                    <div style="font-size:11px; color:var(--text-muted); background:#f8f9fa; padding:5px; border-radius:4px; border-left:3px solid var(--border);">
                                        <i class="fas fa-comment-dots"></i> <?= htmlspecialchars($req['approver_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y H:i', strtotime($req['updated_at'] ?: $req['created_at'])) ?></td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <?php if (in_array($req['request_type'], ['paid_leave', 'tidak_hadir'])): ?>
                                        <button class="btn btn-outline btn-sm" title="Edit Pengajuan" onclick="showEditModal(<?= $req['id'] ?>, '<?= $req['start_date'] ?>', '<?= $req['end_date'] ?>', '<?= htmlspecialchars($req['notes'] ?? '') ?>')"><i class="fas fa-edit"></i></button>
                                    <?php endif; ?>
                                    
                                    <?php if (strtotime($req['start_date']) > strtotime('today')): ?>
                                        <form action="<?= APP_URL ?>/requests/cancel" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan ini?');" style="display:inline;">
                                            <input type="hidden" name="_token" value="<?= $csrf_token ?? '' ?>">
                                            <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color); border-color:var(--danger-color);" title="Batal/Hapus"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Edit Pengajuan Cuti -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:400px; margin:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title"><i class="fas fa-edit"></i> Edit Tanggal Cuti/Izin</div>
            <button class="btn btn-outline btn-sm" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <form action="<?= APP_URL ?>/requests/update-leave" method="POST">
                <input type="hidden" name="_token" value="<?= $csrf_token ?? '' ?>">
                <input type="hidden" name="id" id="e_req_id">
                
                <div class="form-group mb-3">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" id="e_start_date" class="form-control" required>
                </div>
                
                <div class="form-group mb-3">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="end_date" id="e_end_date" class="form-control" required>
                </div>

                <div class="form-group mb-4">
                    <label>Alasan (Opsional)</label>
                    <textarea name="reason" id="e_reason" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="alert alert-warning" style="font-size:12px; margin-bottom:15px;">
                    <i class="fas fa-info-circle"></i> Mengubah pengajuan akan mereset status persetujuan, dan pengajuan Anda harus disetujui ulang oleh Atasan/HRD.
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showEditModal(id, start, end, reason) {
    document.getElementById('e_req_id').value = id;
    document.getElementById('e_start_date').value = start;
    document.getElementById('e_end_date').value = end;
    document.getElementById('e_reason').value = reason;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>
