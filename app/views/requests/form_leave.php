<?php
// app/views/requests/form_leave.php
?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-calendar-plus"></i> Form Pengajuan Leave / Cuti</div>
    </div>
    <div class="card-body">
        <form action="<?= APP_URL ?>/requests/leave" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            
            <div class="form-group mb-3">
                <label>Jenis Pengajuan</label>
                <select name="request_type" id="request_type" class="form-control" required onchange="toggleFields()">
                    <option value="">-- Pilih Jenis --</option>
                    <option value="paid_leave">Cuti Tahunan</option>
                    <option value="tidak_hadir">Tidak Hadir (Izin / Unpaid)</option>
                    <option value="sakit">Sakit</option>
                    <option value="hourly_leave">Hourly Leave (Izin Jam)</option>
                </select>
            </div>

            <!-- Date Fields -->
            <div class="form-group mb-3" id="field_start_date">
                <label id="label_start_date">Tanggal Mulai</label>
                <input type="date" name="start_date" id="start_date" class="form-control">
            </div>
            
            <div class="form-group mb-3" id="field_end_date">
                <label>Tanggal Selesai (Opsional jika hanya 1 hari)</label>
                <input type="date" name="end_date" id="end_date" class="form-control">
            </div>

            <!-- Hourly Leave Fields -->
            <div id="fields_hourly" style="display:none; background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:15px;">
                <div class="form-group mb-3">
                    <label>Jumlah Jam Izin</label>
                    <input type="number" name="hours_requested" class="form-control" min="1" max="4" placeholder="Misal: 2">
                </div>
                <div class="form-group mb-0">
                    <label>Mulai Jam</label>
                    <input type="time" name="start_time" class="form-control">
                </div>
            </div>

            <!-- Sakit Fields -->
            <div id="fields_sakit" style="display:none; background:#e0f2fe; padding:15px; border-radius:5px; margin-bottom:15px;">
                <div class="form-group mb-3">
                    <label>Pilih Faskes (Klinik / Rumah Sakit)</label>
                    <select name="health_partner_id" id="health_partner_id" class="form-control" onchange="toggleManualFaskes()">
                        <option value="">-- Pilih Mitra Kesehatan --</option>
                        <?php foreach($healthPartners as $hp): ?>
                            <option value="<?= $hp['id'] ?>"><?= htmlspecialchars($hp['name']) ?> (<?= ucwords(str_replace('_', ' ', $hp['type'])) ?>)</option>
                        <?php endforeach; ?>
                        <option value="0">Lainnya (Bukan Mitra)</option>
                    </select>
                </div>

                <div id="manual_faskes_fields" style="display:none; padding:10px; background:rgba(0,0,0,0.05); border-radius:5px; margin-bottom:15px;">
                    <div class="form-group mb-3">
                        <label>Tipe Faskes</label>
                        <select name="provider_type" class="form-control">
                            <option value="klinik">Klinik</option>
                            <option value="rumah_sakit">Rumah Sakit</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Nama Klinik / Rumah Sakit</label>
                        <input type="text" name="provider_name" class="form-control" placeholder="Nama instansi kesehatan">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label>Upload Surat Keterangan Dokter (Wajib)</label>
                    <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">Format: JPG, PNG, PDF. Maksimal 2MB.</small>
                </div>
            </div>

            <div class="form-group mb-3">
                <label>Keterangan / Alasan</label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="Tuliskan alasan pengajuan Anda..."></textarea>
            </div>

            <div style="display:flex; justify-content:space-between;">
                <a href="<?= APP_URL ?>/requests" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('request_type').value;
    
    const fieldEnd = document.getElementById('field_end_date');
    const labelStart = document.getElementById('label_start_date');
    const fieldsHourly = document.getElementById('fields_hourly');
    const fieldsSakit = document.getElementById('fields_sakit');

    // Default resets
    fieldEnd.style.display = 'block';
    labelStart.innerText = 'Tanggal Mulai';
    fieldsHourly.style.display = 'none';
    fieldsSakit.style.display = 'none';
    document.getElementById('start_date').required = true;

    if (type === 'hourly_leave') {
        fieldEnd.style.display = 'none';
        labelStart.innerText = 'Tanggal Izin';
        fieldsHourly.style.display = 'block';
    } else if (type === 'sakit') {
        fieldsSakit.style.display = 'block';
    }
}

function toggleManualFaskes() {
    const hpId = document.getElementById('health_partner_id').value;
    const manualFields = document.getElementById('manual_faskes_fields');
    if (hpId === '0') {
        manualFields.style.display = 'block';
    } else {
        manualFields.style.display = 'none';
    }
}

// Inisialisasi saat load
toggleFields();
</script>
