<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ubah Password — KehadiranApp</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css">
</head>
<body>

<div class="login-page">
  <div class="login-box">

    <div class="login-logo">
      <div class="logo-icon"><i class="fas fa-key"></i></div>
      <h1>Ubah Password</h1>
      <p>Anda diwajibkan mengubah password Anda</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom:20px">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/update-password">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf ?? '') ?>">

      <div class="form-group">
        <label for="password">Password Baru</label>
        <div class="login-input-group">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
        </div>
      </div>

      <div class="form-group">
        <label for="confirm_password">Konfirmasi Password</label>
        <div class="login-input-group">
          <i class="fas fa-check-circle"></i>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password baru" required minlength="6">
        </div>
      </div>

      <button type="submit" class="btn-login" style="margin-top:20px;">
        <i class="fas fa-save"></i> &nbsp;Simpan Password
      </button>
      <a href="<?= APP_URL ?>/logout" style="display:block; text-align:center; margin-top:15px; color:var(--text-muted); text-decoration:none;">Batal (Logout)</a>
    </form>
  </div>
</div>

</body>
</html>
