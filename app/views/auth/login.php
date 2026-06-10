<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — KehadiranApp</title>
  <meta name="description" content="Sistem Informasi Kehadiran dan Penggajian">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/KehadiranApp/public/css/app.css">
</head>
<body>

<div class="login-page">
  <div class="login-box">

    <!-- Logo -->
    <div class="login-logo">
      <div class="logo-icon"><i class="fas fa-fingerprint"></i></div>
      <h1>KehadiranApp</h1>
      <p>Sistem Informasi Kehadiran &amp; Penggajian</p>
    </div>

    <!-- Alert Error -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom:20px">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Form Login -->
    <form method="POST" action="/KehadiranApp/public/login" id="loginForm">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf ?? '') ?>">

      <?php if (!empty($expired)): ?>
        <div class="alert alert-warning" style="margin-bottom:20px">
          <i class="fas fa-clock"></i>
          Sesi Anda telah berakhir. Silakan login kembali.
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="username">Username</label>
        <div class="login-input-group">
          <i class="fas fa-user"></i>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($oldUsername ?? '') ?>"
            autocomplete="username"
            required
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="login-input-group" style="position:relative">
          <i class="fas fa-lock"></i>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          >
          <button type="button" id="togglePwd"
            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px;padding:0;">
            <i class="fas fa-eye" id="pwdIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login" id="loginBtn">
        <span id="loginText"><i class="fas fa-sign-in-alt"></i> &nbsp;Masuk</span>
        <span id="loginSpinner" style="display:none"><i class="fas fa-spinner fa-spin"></i> &nbsp;Memproses...</span>
      </button>
    </form>

    <!-- Info default creds (dev only, hapus saat production) -->
    <div style="margin-top:28px;padding:14px;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:10px;font-size:12px;color:var(--text-muted)">
      <div style="color:var(--primary);font-weight:600;margin-bottom:8px"><i class="fas fa-info-circle"></i> Demo Credentials</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 12px">
        <span>Manager HRD:</span>   <span style="color:var(--text-light)">MGR001_Budi_Santoso</span>
        <span>Admin HRD:</span>     <span style="color:var(--text-light)">HRD001_Siti_Rahayu</span>
        <span>Payroll:</span>       <span style="color:var(--text-light)">PAY001_Agus_Wijaya</span>
        <span>Supervisor:</span>    <span style="color:var(--text-light)">SPV001_Dian_Pratama</span>
        <span>Karyawan:</span>      <span style="color:var(--text-light)">EMP001_Ahmad_Fauzi</span>
        <span>Password (admin):</span>  <span style="color:var(--warning)">Admin@1234</span>
        <span>Password (emp):</span><span style="color:var(--warning)">Karyawan@1234</span>
      </div>
    </div>

    <div style="text-align:center;margin-top:20px;font-size:12px;color:var(--text-muted)">
      <a href="#" onclick="showForgotModal()" style="color:var(--primary); text-decoration:none;">Lupa password? Pengajuan Reset Password.</a>
    </div>

  </div>
</div>

<!-- Modal Lupa Password -->
<div id="forgotModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
  <div class="login-box" style="margin:0;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
      <h3 style="margin:0;"><i class="fas fa-question-circle"></i> Lupa Password</h3>
      <button style="background:none; border:none; cursor:pointer;" onclick="closeForgotModal()"><i class="fas fa-times"></i></button>
    </div>
    <div style="font-size:14px; color:var(--text-color); margin-bottom:20px;">
      Untuk keamanan sistem, pengaturan ulang password hanya dapat dilakukan melalui Administrator HRD.
      <br><br>
      Silakan mengajukan permohonan reset password dengan menghubungi bagian HRD secara langsung. Jika password Anda di-reset, Anda akan diwajibkan untuk mengubahnya pada saat pertama kali login.
    </div>
    <div style="text-align:right;">
      <button class="btn-login" style="padding:10px 15px; width:auto;" onclick="closeForgotModal()">Tutup</button>
    </div>
  </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePwd').addEventListener('click', () => {
  const pwd  = document.getElementById('password');
  const icon = document.getElementById('pwdIcon');
  const show = pwd.type === 'password';
  pwd.type   = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
});

function showForgotModal() {
  document.getElementById('forgotModal').style.display = 'flex';
}
function closeForgotModal() {
  document.getElementById('forgotModal').style.display = 'none';
}


// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', () => {
  document.getElementById('loginText').style.display    = 'none';
  document.getElementById('loginSpinner').style.display = 'inline';
  document.getElementById('loginBtn').disabled = true;
});
</script>
</body>
</html>
