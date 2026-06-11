<?php
// app/views/requests/approvals.php
?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-check-double"></i> Antrian Approval</div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($requests)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted)">
                <i class="fas fa-clipboard-check" style="font-size:40px;opacity:0.2;display:block;margin-bottom:12px"></i>
                Tidak ada pengajuan yang menunggu approval Anda.
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Jenis Pengajuan</th>
                        <th>Tanggal</th>
                        <th>Alasan/Notes</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></strong><br>
                                <small class="text-muted"><?= $req['employee_code'] ?></small>
                            </td>
                            <td>
                                <span class="badge badge-outline"><?= ucwords(str_replace('_', ' ', $req['request_type'])) ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($req['attendance_date'])) ?></td>
                            <td><small><?= htmlspecialchars($req['notes'] ?? '-') ?></small></td>
                            <td>
                                <div style="display:flex;gap:5px">
                                    <button class="btn btn-success btn-sm" onclick='showApprovalModal(<?= json_encode($req) ?>, "approve")'>
                                        Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick='showApprovalModal(<?= json_encode($req) ?>, "reject")'>
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Approval (Sederhana via prompt atau hidden form) -->
<div id="approvalModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:400px; margin:0">
        <div class="card-header">
            <div class="card-title" id="modalTitle">Konfirmasi Approval</div>
        </div>
        <div class="card-body">
            <form action="<?= APP_URL ?>/requests/approvals" method="POST">
                <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="request_id" id="modalRequestId">
                <input type="hidden" name="decision" id="modalDecision">

                <div style="background:#f8f9fa; padding:10px; border-radius:5px; margin-bottom:15px; font-size:14px;">
                    <table class="table" style="margin:0; background:transparent;">
                        <tr><th style="width:40%; padding:5px 0;">Nama Karyawan</th><td id="m_emp_name" style="padding:5px 0;"></td></tr>
                        <tr><th style="padding:5px 0;">Waktu Pengajuan</th><td id="m_submitted_at" style="padding:5px 0;"></td></tr>
                        <tr><th style="padding:5px 0;">Jenis Pengajuan</th><td id="m_type" style="padding:5px 0;"></td></tr>
                        <tr><th style="padding:5px 0;">Tanggal Absensi</th><td id="m_date" style="padding:5px 0;"></td></tr>
                    </table>
                    
                    <div id="m_detail_sakit" style="display:none; margin-top:10px; border-top:1px dashed #ccc; padding-top:10px;">
                        <strong>Detail Sakit:</strong><br>
                        Faskes: <span id="m_sakit_faskes"></span><br>
                        Dokumen: <span id="m_sakit_doc"></span><br>
                        <div id="m_sakit_label" style="margin-top:5px;"></div>
                    </div>

                    <div id="m_detail_leave" style="display:none; margin-top:10px; border-top:1px dashed #ccc; padding-top:10px;">
                        <strong>Detail Cuti/Izin:</strong><br>
                        Sampai Tanggal: <span id="m_leave_end"></span> (<span id="m_leave_days"></span> hari)
                    </div>
                </div>
                
                <div class="form-group mb-3" id="sakit_verification_group" style="display:none; background:#e0f2fe; padding:15px; border-radius:5px;">
                    <label>Tindakan Verifikasi Faskes</label>
                    <div style="font-size:13px; color:var(--text-muted); margin-bottom:8px;">Silakan cek kesesuaian dokumen dengan daftar mitra.</div>
                    <select name="health_partner_id" id="verify_health_partner" class="form-control">
                        <option value="0">Lainnya (Bukan Mitra - Butuh Approval Manager)</option>
                        <?php if(!empty($healthPartners)): ?>
                            <?php foreach($healthPartners as $hp): ?>
                                <option value="<?= $hp['id'] ?>"><?= htmlspecialchars($hp['name']) ?> (<?= ucwords(str_replace('_', ' ', $hp['type'])) ?>)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label>Catatan / Alasan Karyawan</label>
                    <textarea id="m_notes_read" class="form-control" rows="2" readonly style="background:#eee;"></textarea>
                </div>

                <div class="form-group mb-3">
                    <label>Catatan Approval (Opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Tambahkan alasan atau catatan..."></textarea>
                </div>
                
                <div style="display:flex;gap:10px">
                    <button type="button" class="btn btn-outline" style="flex:1" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn" style="flex:1">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showApprovalModal(req, decision) {
    document.getElementById('modalRequestId').value = req.id;
    document.getElementById('modalDecision').value = decision;
    document.getElementById('modalTitle').innerText = decision === 'approve' ? 'Konfirmasi Approve' : 'Konfirmasi Reject';
    document.getElementById('modalSubmitBtn').className = decision === 'approve' ? 'btn btn-success' : 'btn btn-danger';
    document.getElementById('modalSubmitBtn').innerText = decision === 'approve' ? 'Approve' : 'Reject';

    // Isi Data
    document.getElementById('m_emp_name').innerText = req.first_name + ' ' + req.last_name + ' (' + req.employee_code + ')';
    document.getElementById('m_submitted_at').innerText = req.submitted_at;
    document.getElementById('m_type').innerText = req.request_type.replace('_', ' ').toUpperCase();
    document.getElementById('m_date').innerText = req.attendance_date;
    document.getElementById('m_notes_read').value = req.notes || '-';

    // Reset details
    document.getElementById('m_detail_sakit').style.display = 'none';
    document.getElementById('m_detail_leave').style.display = 'none';
    const sakitGroup = document.getElementById('sakit_verification_group');
    if (sakitGroup) sakitGroup.style.display = 'none';

    if (req.request_type === 'sakit' && req.detail) {
        document.getElementById('m_detail_sakit').style.display = 'block';
        document.getElementById('m_sakit_faskes').innerText = req.detail.provider_name + ' (' + req.detail.provider_type + ')';
        
        const docSpan = document.getElementById('m_sakit_doc');
        if (req.detail.document_path) {
            docSpan.innerHTML = `<a href="<?= APP_URL ?>${req.detail.document_path}" target="_blank" style="color:var(--primary); font-weight:bold;"><i class="fas fa-file-medical"></i> Lihat Dokumen</a>`;
        } else {
            docSpan.innerHTML = `<span class="text-muted">Tidak ada dokumen</span>`;
        }

        const labelDiv = document.getElementById('m_sakit_label');
        if (req.detail.health_partner_id) {
            labelDiv.innerHTML = `<span class="badge badge-success" style="font-size:11px;"><i class="fas fa-check-circle"></i> Faskes Terdaftar (Mitra Resmi)</span>`;
            if (sakitGroup && decision === 'approve') {
                sakitGroup.style.display = 'block';
                document.getElementById('verify_health_partner').value = req.detail.health_partner_id;
            }
        } else {
            labelDiv.innerHTML = `<span class="badge badge-warning" style="font-size:11px;"><i class="fas fa-exclamation-triangle"></i> Faskes Bukan Mitra (Lainnya)</span>`;
            if (sakitGroup && decision === 'approve') {
                sakitGroup.style.display = 'block';
                document.getElementById('verify_health_partner').value = '0';
            }
        }

    } else if ((req.request_type === 'paid_leave' || req.request_type === 'tidak_hadir') && req.detail) {
        document.getElementById('m_detail_leave').style.display = 'block';
        document.getElementById('m_leave_end').innerText = req.detail.end_date || req.attendance_date;
        document.getElementById('m_leave_days').innerText = req.detail.total_days || 1;
    }

    document.getElementById('approvalModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('approvalModal').style.display = 'none';
}
</script>
