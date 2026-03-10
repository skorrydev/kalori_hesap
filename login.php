<?php
require_once __DIR__ . '/config/helpers.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giriş Yap — KaloriAI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-bg">
  <div class="auth-orb orb-1"></div>
  <div class="auth-orb orb-2"></div>
  <div class="auth-orb orb-3"></div>
</div>

<div class="auth-container">
  <div class="auth-card glass-card">

    <!-- Logo -->
    <div class="auth-logo">
      <div class="logo-icon">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="24" cy="24" r="22" fill="url(#lg1)" opacity="0.2"/>
          <path d="M24 8C15.2 8 8 15.2 8 24s7.2 16 16 16 16-7.2 16-16S32.8 8 24 8zm0 4c6.6 0 12 5.4 12 12s-5.4 12-12 12S12 30.6 12 24 17.4 12 24 12z" fill="url(#lg1)"/>
          <path d="M24 16c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8z" fill="url(#lg1)"/>
          <defs>
            <linearGradient id="lg1" x1="8" y1="8" x2="40" y2="40" gradientUnits="userSpaceOnUse">
              <stop stop-color="#a78bfa"/>
              <stop offset="1" stop-color="#38bdf8"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
      <span class="logo-text">KaloriAI</span>
    </div>

    <h1 class="auth-title">Hoş Geldiniz</h1>
    <p class="auth-subtitle">Hesabınıza giriş yapın</p>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/auth/login_action.php" class="auth-form">
      <div class="form-group">
        <label for="email">E-posta Adresi</label>
        <div class="input-wrapper">
          <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
          </svg>
          <input type="email" id="email" name="email" placeholder="ornek@email.com" required autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Şifre</label>
        <div class="input-wrapper">
          <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
          </svg>
          <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
          <button type="button" class="toggle-password" onclick="togglePass(this)">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        <span>Giriş Yap</span>
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      </button>
    </form>

    <p class="auth-switch">
      Hesabınız yok mu? <a href="/register.php">Ücretsiz Kaydolun</a>
    </p>
  </div>
</div>

<script>
function togglePass(btn) {
  const input = btn.previousElementSibling;
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
