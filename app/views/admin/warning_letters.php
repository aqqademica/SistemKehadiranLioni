<?php
// app/views/admin/warning_letters.php
?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h5 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-exclamation-triangle"></i> Daftar Surat Peringatan (SP) & Pembatasan Kerja
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($warningLetters)): ?>
            <div style="padding:40px; text-align:center; color:var(--text-muted);">
                <i class="fas fa-check-circle" style="font-size:40px; color:var(--success-color); opacity:0.5; display:block; margin-bottom:12px;"></i>
                Tidak ada data Surat Peringatan (SP) yang diterbitkan.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan/Dept</th>
                            <th>Tingkat SP</th>
                            <th>Pemicu Pelanggaran</th>
                            <th>Tanggal Pelanggaran</th>
                            <th>Diterbitkan Oleh</th>
                            <th>Status Acknowledge</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warningLetters as $wl): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($wl['employee_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($wl['first_name'] . ' ' . $wl['last_name']) ?></strong></td>
                                <td><small><?= htmlspecialchars($wl['pos_name']) ?> / <?= htmlspecialchars($wl['dept_name']) ?></small></td>
                                <td>
                                    <?php if ($wl['wl_type'] === 'WL1'): ?>
                                        <span class="badge badge-warning">SP 1 (Pertama)</span>
                                    <?php elseif ($wl['wl_type'] === 'WL2'): ?>
                                        <span class="badge badge-danger">SP 2 (Kedua)</span>
                                    <?php elseif ($wl['wl_type'] === 'WL3'): ?>
                                        <span class="badge badge-dark">SP 3 (Ketiga)</span>
                                    <?php elseif ($wl['wl_type'] === 'TERMINATION'): ?>
                                        <span class="badge badge-dark" style="background-color: #000; color: #fff;">PHK / Termination</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= $wl['wl_type'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($wl['trigger_reason']) ?></td>
                                <td><?= date('d M Y', strtotime($wl['trigger_date'])) ?></td>
                                <td>
                                    <small><?= htmlspecialchars($wl['issuer_name']) ?></small><br>
                                    <span style="font-size:11px; color:#999;"><?= date('d M Y H:i', strtotime($wl['issued_at'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($wl['acknowledged_at']): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Acknowledged</span>
                                        <?php if ($wl['notes']): ?>
                                            <div style="font-size: 11px; margin-top: 5px; color:#555; font-style:italic;">
                                                "<?= htmlspecialchars($wl['notes']) ?>"
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Belum Acknowledge</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if (!$wl['acknowledged_at']): ?>
                                        <button class="btn btn-primary btn-sm" onclick="openAckModal(<?= $wl['id'] ?>)">
                                            Acknowledge
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm" disabled>
                                            Done
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Acknowledge -->
<div id="ackModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:450px; margin:0;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-title"><i class="fas fa-check-circle"></i> Acknowledge Surat Peringatan</div>
            <button class="btn btn-outline btn-sm" onclick="closeAckModal()"><i class="fas fa-times"></i></button>
        </div>
        <form action="<?= APP_URL ?>/hrd/warning-letters/acknowledge" method="POST">
            <div class="card-body">
                <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="id" id="ack_wl_id" value="">
                
                <div class="form-group">
                    <label for="ack_notes">Catatan Tindak Lanjut / Keterangan</label>
                    <textarea name="notes" id="ack_notes" class="form-control" rows="4" placeholder="Tuliskan catatan konseling atau persetujuan karyawan..." required></textarea>
                </div>
            </div>
            <div class="card-footer" style="text-align:right;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeAckModal()">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAckModal(id) {
    document.getElementById('ack_wl_id').value = id;
    document.getElementById('ack_notes').value = '';
    document.getElementById('ackModal').style.display = 'flex';
}
function closeAckModal() {
    document.getElementById('ackModal').style.display = 'none';
}
</script>
