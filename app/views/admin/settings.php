<?php
// app/views/admin/settings.php
$activeTab = $tab ?? '';
?>
<style>
.cfg-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.cfg-tab{padding:14px 20px;border-radius:12px;background:var(--bg-card);border:1px solid var(--border);cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;transition:all .2s;flex:1;min-width:180px;text-decoration:none;color:var(--text-dark)}
.cfg-tab:hover,.cfg-tab.active{background:linear-gradient(135deg,var(--primary),#818cf8);color:#fff;border-color:transparent;transform:translateY(-2px);box-shadow:0 4px 15px rgba(99,102,241,.3)}
.cfg-tab .t-icon{font-size:20px;opacity:.8}
.cfg-tab .t-count{margin-left:auto;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:8px;font-size:12px}
.cfg-section{display:none}.cfg-section.active{display:block}
.mini-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end;padding:15px;background:#f8f9fa;border-radius:10px;margin-bottom:15px}
.mini-form .form-group{margin:0}
.mini-form label{font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;display:block}
</style>

<!-- Tab Buttons -->
<div class="cfg-tabs">
  <a href="?tab=shifts" class="cfg-tab <?= $activeTab==='shifts'?'active':'' ?>">
    <i class="fas fa-clock t-icon"></i><div><div>Kelola Jam Kerja</div><small style="font-weight:400;opacity:.7">Shift & Jadwal</small></div>
    <span class="t-count"><?= count($shifts) ?></span>
  </a>
  <a href="?tab=positions" class="cfg-tab <?= $activeTab==='positions'?'active':'' ?>">
    <i class="fas fa-sitemap t-icon"></i><div><div>Kelola Jabatan</div><small style="font-weight:400;opacity:.7">Posisi Karyawan</small></div>
    <span class="t-count"><?= count($positions) ?></span>
  </a>
  <a href="?tab=late" class="cfg-tab <?= $activeTab==='late'?'active':'' ?>">
    <i class="fas fa-exclamation-triangle t-icon"></i><div><div>Aturan Terlambat</div><small style="font-weight:400;opacity:.7">Potongan Gaji</small></div>
    <span class="t-count"><?= count($lateRules) ?></span>
  </a>
  <a href="?tab=leave" class="cfg-tab <?= $activeTab==='leave'?'active':'' ?>">
    <i class="fas fa-umbrella-beach t-icon"></i><div><div>Kelola Leave/Cuti</div><small style="font-weight:400;opacity:.7">Jenis & Kuota</small></div>
    <span class="t-count"><?= count($leaveTypes) ?></span>
  </a>
</div>

<?php if (!$activeTab): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:60px 20px;color:var(--text-muted)">
  <i class="fas fa-cogs" style="font-size:48px;opacity:.3;display:block;margin-bottom:15px"></i>
  <h3 style="margin:0 0 8px 0">Pengaturan HRD</h3>
  <p>Pilih salah satu kategori di atas untuk mulai mengelola konfigurasi sistem.</p>
</div></div>
<?php endif; ?>

<!-- ============ SHIFTS ============ -->
<?php if ($activeTab === 'shifts'): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-clock"></i> Daftar Jam Kerja</div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('formAddShift').style.display=document.getElementById('formAddShift').style.display==='none'?'block':'none'"><i class="fas fa-plus"></i> Tambah</button>
  </div>
  <div class="card-body" style="padding:0">
    <div id="formAddShift" style="display:none;padding:15px;border-bottom:1px solid var(--border)">
      <form action="<?= APP_URL ?>/hrd/shifts/store" method="POST">
        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
        <div class="mini-form">
          <div class="form-group"><label>Nama Shift</label><input type="text" name="name" class="form-control" required></div>
          <div class="form-group"><label>Jam Masuk</label><input type="time" name="start_time" class="form-control" required></div>
          <div class="form-group"><label>Jam Keluar</label><input type="time" name="end_time" class="form-control" required></div>
          <div class="form-group"><label>Jumlah Jam</label><input type="number" step="0.5" name="work_hours" class="form-control" value="8" required></div>
          <div class="form-group"><label>Overnight?</label><select name="is_overnight" class="form-control"><option value="0">Tidak</option><option value="1">Ya</option></select></div>
          <div class="form-group"><button type="submit" class="btn btn-success btn-sm" style="width:100%"><i class="fas fa-save"></i> Simpan</button></div>
        </div>
      </form>
    </div>
    <?php if (empty($shifts)): ?>
      <div style="padding:30px;text-align:center;color:var(--text-muted)">Belum ada data jam kerja.</div>
    <?php else: ?>
      <table class="table"><thead><tr><th>ID</th><th>Nama</th><th>Masuk</th><th>Keluar</th><th>Jam Kerja</th><th>Overnight</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($shifts as $s): ?>
        <tr>
          <td><?= $s['id'] ?></td><td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
          <td><?= $s['start_time'] ?></td><td><?= $s['end_time'] ?></td><td><?= $s['work_hours'] ?> jam</td>
          <td><?= $s['is_overnight']?'<span class="badge badge-warning">Ya</span>':'Tidak' ?></td>
          <td><?= $s['is_active']?'<span class="badge badge-success">Aktif</span>':'<span class="badge badge-danger">Nonaktif</span>' ?></td>
          <td><div style="display:flex;gap:5px">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit('shift_<?= $s['id'] ?>')"><i class="fas fa-edit"></i></button>
            <form action="<?= APP_URL ?>/hrd/shifts/delete" method="POST" onsubmit="return confirm('Hapus jam kerja ini?')"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color);border-color:var(--danger-color)"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <tr id="shift_<?= $s['id'] ?>" style="display:none"><td colspan="8">
          <form action="<?= APP_URL ?>/hrd/shifts/update" method="POST"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <div class="mini-form">
              <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($s['name']) ?>" required></div>
              <div class="form-group"><label>Masuk</label><input type="time" name="start_time" class="form-control" value="<?= $s['start_time'] ?>" required></div>
              <div class="form-group"><label>Keluar</label><input type="time" name="end_time" class="form-control" value="<?= $s['end_time'] ?>" required></div>
              <div class="form-group"><label>Jam</label><input type="number" step="0.5" name="work_hours" class="form-control" value="<?= $s['work_hours'] ?>" required></div>
              <div class="form-group"><label>Overnight</label><select name="is_overnight" class="form-control"><option value="0" <?= !$s['is_overnight']?'selected':'' ?>>Tidak</option><option value="1" <?= $s['is_overnight']?'selected':'' ?>>Ya</option></select></div>
              <div class="form-group"><button type="submit" class="btn btn-primary btn-sm" style="width:100%"><i class="fas fa-save"></i> Update</button></div>
            </div>
          </form>
        </td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ============ POSITIONS ============ -->
<?php if ($activeTab === 'positions'): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-sitemap"></i> Daftar Jabatan</div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('formAddPos').style.display=document.getElementById('formAddPos').style.display==='none'?'block':'none'"><i class="fas fa-plus"></i> Tambah</button>
  </div>
  <div class="card-body" style="padding:0">
    <div id="formAddPos" style="display:none;padding:15px;border-bottom:1px solid var(--border)">
      <form action="<?= APP_URL ?>/hrd/positions/store" method="POST">
        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
        <div class="mini-form">
          <div class="form-group"><label>Nama Jabatan</label><input type="text" name="name" class="form-control" required></div>
          <div class="form-group"><label>Departemen</label><select name="department_id" class="form-control" required><option value="">-- Pilih --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Level</label><select name="level" class="form-control"><option value="staff">Staff</option><option value="operator">Operator</option><option value="supervisor">Supervisor</option><option value="manager">Manager</option></select></div>
          <div class="form-group"><button type="submit" class="btn btn-success btn-sm" style="width:100%"><i class="fas fa-save"></i> Simpan</button></div>
        </div>
      </form>
    </div>
    <?php if (empty($positions)): ?>
      <div style="padding:30px;text-align:center;color:var(--text-muted)">Belum ada data jabatan.</div>
    <?php else: ?>
      <table class="table"><thead><tr><th>ID</th><th>Nama</th><th>Kode</th><th>Departemen</th><th>Level</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($positions as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td><td><strong><?= htmlspecialchars($p['name']) ?></strong></td><td><code><?= $p['code'] ?></code></td>
          <td><?= htmlspecialchars($p['dept_name'] ?? '-') ?></td><td><?= ucfirst($p['level']) ?></td>
          <td><?= $p['is_active']?'<span class="badge badge-success">Aktif</span>':'<span class="badge badge-danger">Nonaktif</span>' ?></td>
          <td><div style="display:flex;gap:5px">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit('pos_<?= $p['id'] ?>')"><i class="fas fa-edit"></i></button>
            <form action="<?= APP_URL ?>/hrd/positions/delete" method="POST" onsubmit="return confirm('Hapus jabatan ini?')"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color);border-color:var(--danger-color)"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <tr id="pos_<?= $p['id'] ?>" style="display:none"><td colspan="7">
          <form action="<?= APP_URL ?>/hrd/positions/update" method="POST"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>">
            <div class="mini-form">
              <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required></div>
              <div class="form-group"><label>Departemen</label><select name="department_id" class="form-control" required><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>" <?= $p['department_id']==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?></select></div>
              <div class="form-group"><label>Level</label><select name="level" class="form-control"><?php foreach(['staff','operator','supervisor','manager'] as $lv): ?><option value="<?= $lv ?>" <?= $p['level']===$lv?'selected':'' ?>><?= ucfirst($lv) ?></option><?php endforeach; ?></select></div>
              <div class="form-group"><button type="submit" class="btn btn-primary btn-sm" style="width:100%"><i class="fas fa-save"></i> Update</button></div>
            </div>
          </form>
        </td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ============ LATE RULES ============ -->
<?php if ($activeTab === 'late'): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-exclamation-triangle"></i> Aturan Terlambat</div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('formAddLate').style.display=document.getElementById('formAddLate').style.display==='none'?'block':'none'"><i class="fas fa-plus"></i> Tambah</button>
  </div>
  <div class="card-body" style="padding:0">
    <div id="formAddLate" style="display:none;padding:15px;border-bottom:1px solid var(--border)">
      <form action="<?= APP_URL ?>/hrd/late-rules/store" method="POST">
        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
        <div class="mini-form">
          <div class="form-group"><label>Dari Menit</label><input type="number" name="min_minutes" class="form-control" required></div>
          <div class="form-group"><label>Sampai Menit</label><input type="number" name="max_minutes" class="form-control" value="0" required><small>0 = tak terbatas</small></div>
          <div class="form-group"><label>Potongan (%)</label><input type="number" step="0.01" name="deduction_percent" class="form-control" value="0"></div>
          <div class="form-group"><label>Potongan (Rp)</label><input type="number" name="deduction_amount" class="form-control" value="0"></div>
          <div class="form-group"><label>Keterangan</label><input type="text" name="description" class="form-control"></div>
          <div class="form-group"><button type="submit" class="btn btn-success btn-sm" style="width:100%"><i class="fas fa-save"></i> Simpan</button></div>
        </div>
      </form>
    </div>
    <?php if (empty($lateRules)): ?>
      <div style="padding:30px;text-align:center;color:var(--text-muted)">Belum ada aturan terlambat.</div>
    <?php else: ?>
      <table class="table"><thead><tr><th>ID</th><th>Dari (mnt)</th><th>Sampai (mnt)</th><th>Potongan %</th><th>Potongan Rp</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($lateRules as $r): ?>
        <tr>
          <td><?= $r['id'] ?></td><td><?= $r['min_minutes'] ?></td><td><?= $r['max_minutes'] ?: '∞' ?></td>
          <td><?= $r['deduction_percent'] ?>%</td><td>Rp <?= number_format($r['deduction_amount'] ?? 0, 0, ',', '.') ?></td>
          <td><?= htmlspecialchars($r['description'] ?? '-') ?></td>
          <td><div style="display:flex;gap:5px">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit('late_<?= $r['id'] ?>')"><i class="fas fa-edit"></i></button>
            <form action="<?= APP_URL ?>/hrd/late-rules/delete" method="POST" onsubmit="return confirm('Hapus aturan ini?')"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color);border-color:var(--danger-color)"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <tr id="late_<?= $r['id'] ?>" style="display:none"><td colspan="7">
          <form action="<?= APP_URL ?>/hrd/late-rules/update" method="POST"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <div class="mini-form">
              <div class="form-group"><label>Dari Menit</label><input type="number" name="min_minutes" class="form-control" value="<?= $r['min_minutes'] ?>" required></div>
              <div class="form-group"><label>Sampai Menit</label><input type="number" name="max_minutes" class="form-control" value="<?= $r['max_minutes'] ?>" required></div>
              <div class="form-group"><label>Potongan %</label><input type="number" step="0.01" name="deduction_percent" class="form-control" value="<?= $r['deduction_percent'] ?>"></div>
              <div class="form-group"><label>Potongan Rp</label><input type="number" name="deduction_amount" class="form-control" value="<?= $r['deduction_amount'] ?? 0 ?>"></div>
              <div class="form-group"><label>Keterangan</label><input type="text" name="description" class="form-control" value="<?= htmlspecialchars($r['description'] ?? '') ?>"></div>
              <div class="form-group"><button type="submit" class="btn btn-primary btn-sm" style="width:100%"><i class="fas fa-save"></i> Update</button></div>
            </div>
          </form>
        </td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ============ LEAVE TYPES ============ -->
<?php if ($activeTab === 'leave'): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-umbrella-beach"></i> Jenis Leave / Cuti</div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('formAddLeave').style.display=document.getElementById('formAddLeave').style.display==='none'?'block':'none'"><i class="fas fa-plus"></i> Tambah</button>
  </div>
  <div class="card-body" style="padding:0">
    <div id="formAddLeave" style="display:none;padding:15px;border-bottom:1px solid var(--border)">
      <form action="<?= APP_URL ?>/hrd/leave-types/store" method="POST">
        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
        <div class="mini-form">
          <div class="form-group"><label>Nama Cuti</label><input type="text" name="name" class="form-control" required placeholder="Cuti Melahirkan"></div>
          <div class="form-group"><label>Jumlah Hari</label><input type="number" name="max_days" class="form-control" required placeholder="90"></div>
          <div class="form-group"><label>Keterangan</label><input type="text" name="description" class="form-control"></div>
          <div class="form-group"><button type="submit" class="btn btn-success btn-sm" style="width:100%"><i class="fas fa-save"></i> Simpan</button></div>
        </div>
      </form>
    </div>
    <?php if (empty($leaveTypes)): ?>
      <div style="padding:30px;text-align:center;color:var(--text-muted)">Belum ada jenis cuti.</div>
    <?php else: ?>
      <table class="table"><thead><tr><th>ID</th><th>Nama Cuti</th><th>Jumlah Hari</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($leaveTypes as $lt): ?>
        <tr>
          <td><?= $lt['id'] ?></td><td><strong><?= htmlspecialchars($lt['name']) ?></strong></td>
          <td><span class="badge badge-info"><?= $lt['max_days'] ?> hari</span></td>
          <td><?= htmlspecialchars($lt['description'] ?? '-') ?></td>
          <td><?= $lt['is_active']?'<span class="badge badge-success">Aktif</span>':'<span class="badge badge-danger">Nonaktif</span>' ?></td>
          <td><div style="display:flex;gap:5px">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit('lv_<?= $lt['id'] ?>')"><i class="fas fa-edit"></i></button>
            <form action="<?= APP_URL ?>/hrd/leave-types/delete" method="POST" onsubmit="return confirm('Hapus jenis cuti ini?')"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $lt['id'] ?>"><button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color);border-color:var(--danger-color)"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <tr id="lv_<?= $lt['id'] ?>" style="display:none"><td colspan="6">
          <form action="<?= APP_URL ?>/hrd/leave-types/update" method="POST"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $lt['id'] ?>">
            <div class="mini-form">
              <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($lt['name']) ?>" required></div>
              <div class="form-group"><label>Hari</label><input type="number" name="max_days" class="form-control" value="<?= $lt['max_days'] ?>" required></div>
              <div class="form-group"><label>Keterangan</label><input type="text" name="description" class="form-control" value="<?= htmlspecialchars($lt['description'] ?? '') ?>"></div>
              <div class="form-group"><button type="submit" class="btn btn-primary btn-sm" style="width:100%"><i class="fas fa-save"></i> Update</button></div>
            </div>
          </form>
        </td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<script>
function toggleEdit(id){var el=document.getElementById(id);el.style.display=el.style.display==='none'?'table-row':'none';}
</script>
