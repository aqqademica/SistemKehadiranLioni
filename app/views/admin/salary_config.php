<?php
// app/views/admin/salary_config.php
$activeTab = $tab ?: 'positions';
?>
<style>
.cfg-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.cfg-tab{padding:14px 20px;border-radius:12px;background:var(--bg-card);border:1px solid var(--border);cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;transition:all .2s;flex:1;min-width:180px;text-decoration:none;color:var(--text-dark)}
.cfg-tab:hover,.cfg-tab.active{background:linear-gradient(135deg,var(--primary),#818cf8);color:#fff;border-color:transparent;transform:translateY(-2px);box-shadow:0 4px 15px rgba(99,102,241,.3)}
.cfg-tab .t-icon{font-size:20px;opacity:.8}
.cfg-tab .t-count{margin-left:auto;background:rgba(255,255,255,.2);padding:2px 8px;border-radius:8px;font-size:12px}
.mini-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end;padding:15px;background:#f8f9fa;border-radius:10px;margin-bottom:15px}
.mini-form .form-group{margin:0}
.mini-form label{font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px;display:block}
</style>

<div class="cfg-tabs">
  <a href="?tab=positions" class="cfg-tab <?= $activeTab==='positions'?'active':'' ?>">
    <i class="fas fa-sitemap t-icon"></i><div><div>Komponen Jabatan</div><small style="font-weight:400;opacity:.7">Standar Gaji</small></div>
    <span class="t-count"><?= count($positionComponents) ?></span>
  </a>
  <a href="?tab=overtime" class="cfg-tab <?= $activeTab==='overtime'?'active':'' ?>">
    <i class="fas fa-clock t-icon"></i><div><div>Variabel Overtime</div><small style="font-weight:400;opacity:.7">Pembagi Default</small></div>
  </a>
  <a href="?tab=deductions" class="cfg-tab <?= $activeTab==='deductions'?'active':'' ?>">
    <i class="fas fa-percentage t-icon"></i><div><div>Variabel Potongan</div><small style="font-weight:400;opacity:.7">Deductions Global</small></div>
    <span class="t-count"><?= count($globalDeductions) ?></span>
  </a>
</div>

<!-- ============ POSITIONS SALARY COMPONENTS ============ -->
<?php if ($activeTab === 'positions'): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-money-check"></i> Komponen Gaji per Jabatan</div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('formAddPos').style.display=document.getElementById('formAddPos').style.display==='none'?'block':'none'"><i class="fas fa-plus"></i> Tambah</button>
  </div>
  <div class="card-body" style="padding:0">
    <div id="formAddPos" style="display:none;padding:15px;border-bottom:1px solid var(--border)">
      <form action="<?= APP_URL ?>/hrd-manager/salary-config/position/store" method="POST">
        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
        <div class="mini-form">
          <div class="form-group"><label>Jabatan</label><select name="position_id" class="form-control" required><option value="">-- Pilih --</option><?php foreach($positions as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Nama Komponen</label><input type="text" name="name" class="form-control" placeholder="Tunjangan Jabatan" required></div>
          <div class="form-group"><label>Tipe</label><select name="type" class="form-control"><option value="earning">Penambahan (Earning)</option><option value="deduction">Potongan (Deduction)</option></select></div>
          <div class="form-group"><label>Nominal (Rp)</label><input type="number" name="amount" class="form-control" required></div>
          <div class="form-group"><button type="submit" class="btn btn-success btn-sm" style="width:100%"><i class="fas fa-save"></i> Simpan</button></div>
        </div>
      </form>
    </div>
    <?php if (empty($positionComponents)): ?>
      <div style="padding:30px;text-align:center;color:var(--text-muted)">Belum ada data komponen gaji jabatan.</div>
    <?php else: ?>
      <table class="table"><thead><tr><th>Jabatan</th><th>Nama Komponen</th><th>Tipe</th><th>Nominal (Rp)</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($positionComponents as $c): ?>
        <tr>
          <td><strong><?= htmlspecialchars($c['position_name']) ?></strong></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= $c['type'] === 'earning' ? '<span class="badge badge-success">Penambahan</span>' : '<span class="badge badge-danger">Potongan</span>' ?></td>
          <td>Rp <?= number_format($c['amount'], 0, ',', '.') ?></td>
          <td><div style="display:flex;gap:5px">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit('pc_<?= $c['id'] ?>')"><i class="fas fa-edit"></i></button>
            <form action="<?= APP_URL ?>/hrd-manager/salary-config/position/delete" method="POST" onsubmit="return confirm('Hapus komponen ini?')"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color);border-color:var(--danger-color)"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <tr id="pc_<?= $c['id'] ?>" style="display:none"><td colspan="5">
          <form action="<?= APP_URL ?>/hrd-manager/salary-config/position/update" method="POST"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <div class="mini-form">
              <div class="form-group"><label>Jabatan</label><select name="position_id" class="form-control" required><?php foreach($positions as $p): ?><option value="<?= $p['id'] ?>" <?= $c['position_id']==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
              <div class="form-group"><label>Nama Komponen</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($c['name']) ?>" required></div>
              <div class="form-group"><label>Tipe</label><select name="type" class="form-control"><option value="earning" <?= $c['type']==='earning'?'selected':'' ?>>Penambahan</option><option value="deduction" <?= $c['type']==='deduction'?'selected':'' ?>>Potongan</option></select></div>
              <div class="form-group"><label>Nominal</label><input type="number" name="amount" class="form-control" value="<?= $c['amount'] ?>" required></div>
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

<!-- ============ OVERTIME DIVIDER ============ -->
<?php if ($activeTab === 'overtime'): ?>
<div class="card" style="max-width: 600px;">
  <div class="card-header"><div class="card-title"><i class="fas fa-clock"></i> Konfigurasi Overtime</div></div>
  <div class="card-body">
    <div class="alert alert-info">
      <i class="fas fa-info-circle"></i> Sesuai UU Tenaga Kerja, variabel pembagi default untuk perhitungan upah lembur per jam adalah <strong>173</strong> (Rumus: 1/173 x Upah Sebulan). Anda dapat mengubahnya jika ada kebijakan perusahaan khusus.
    </div>
    <form action="<?= APP_URL ?>/hrd-manager/salary-config/overtime/update" method="POST">
      <input type="hidden" name="_token" value="<?= $csrf_token ?>">
      <div class="form-group mb-3">
        <label>Variabel Pembagi Overtime</label>
        <div style="display:flex;gap:10px;">
          <input type="number" name="overtime_divider" id="ot_div" class="form-control" value="<?= $otDivider ?>" required readonly>
          <button type="button" class="btn btn-outline" onclick="document.getElementById('ot_div').removeAttribute('readonly');document.getElementById('ot_div').focus();"><i class="fas fa-unlock"></i> Buka Kunci</button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Variabel</button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ============ GLOBAL DEDUCTIONS ============ -->
<?php if ($activeTab === 'deductions'): ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-percentage"></i> Variabel Potongan Gaji (Deductions)</div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('formAddDed').style.display=document.getElementById('formAddDed').style.display==='none'?'block':'none'"><i class="fas fa-plus"></i> Tambah</button>
  </div>
  <div class="card-body" style="padding:0">
    <div id="formAddDed" style="display:none;padding:15px;border-bottom:1px solid var(--border)">
      <form action="<?= APP_URL ?>/hrd-manager/salary-config/deduction/store" method="POST">
        <input type="hidden" name="_token" value="<?= $csrf_token ?>">
        <div class="mini-form">
          <div class="form-group"><label>Nama Potongan</label><input type="text" name="name" class="form-control" placeholder="BPJS Kesehatan" required></div>
          <div class="form-group"><label>Potongan (%)</label><input type="number" step="0.01" name="percent_amount" class="form-control" value="0"></div>
          <div class="form-group"><label>Potongan Tetap (Rp)</label><input type="number" name="fixed_amount" class="form-control" value="0"></div>
          <div class="form-group"><label>Keterangan</label><input type="text" name="description" class="form-control"></div>
          <div class="form-group"><button type="submit" class="btn btn-success btn-sm" style="width:100%"><i class="fas fa-save"></i> Simpan</button></div>
        </div>
        <small class="text-muted" style="display:block;margin-top:10px;">* Jika Persentase (%) diisi, sistem akan menghitung persentase dari Gaji Pokok. Jika nominal tetap (Rp) diisi, sistem akan langsung memotong nominal tersebut. Jika keduanya diisi, keduanya akan diakumulasikan.</small>
      </form>
    </div>
    <?php if (empty($globalDeductions)): ?>
      <div style="padding:30px;text-align:center;color:var(--text-muted)">Belum ada variabel potongan.</div>
    <?php else: ?>
      <table class="table"><thead><tr><th>Nama Potongan</th><th>Persentase (%)</th><th>Nominal Tetap (Rp)</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($globalDeductions as $d): ?>
        <tr>
          <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
          <td><?= $d['percent_amount'] ?>%</td>
          <td>Rp <?= number_format($d['fixed_amount'], 0, ',', '.') ?></td>
          <td><?= htmlspecialchars($d['description'] ?? '-') ?></td>
          <td><div style="display:flex;gap:5px">
            <button class="btn btn-outline btn-sm" onclick="toggleEdit('ded_<?= $d['id'] ?>')"><i class="fas fa-edit"></i></button>
            <form action="<?= APP_URL ?>/hrd-manager/salary-config/deduction/delete" method="POST" onsubmit="return confirm('Hapus potongan ini?')"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger-color);border-color:var(--danger-color)"><i class="fas fa-trash"></i></button></form>
          </div></td>
        </tr>
        <tr id="ded_<?= $d['id'] ?>" style="display:none"><td colspan="5">
          <form action="<?= APP_URL ?>/hrd-manager/salary-config/deduction/update" method="POST"><input type="hidden" name="_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $d['id'] ?>">
            <div class="mini-form">
              <div class="form-group"><label>Nama</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($d['name']) ?>" required></div>
              <div class="form-group"><label>Potongan (%)</label><input type="number" step="0.01" name="percent_amount" class="form-control" value="<?= $d['percent_amount'] ?>"></div>
              <div class="form-group"><label>Tetap (Rp)</label><input type="number" name="fixed_amount" class="form-control" value="<?= $d['fixed_amount'] ?>"></div>
              <div class="form-group"><label>Keterangan</label><input type="text" name="description" class="form-control" value="<?= htmlspecialchars($d['description'] ?? '') ?>"></div>
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
