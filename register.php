<?php
require_once __DIR__ . '/config/helpers.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$flash = getFlash();
$activityLabels = [
    'sedentary'  => 'Hareketsiz (masa başı iş)',
    'light'      => 'Hafif aktif (haftada 1-3 gün)',
    'moderate'   => 'Orta aktif (haftada 3-5 gün)',
    'active'     => 'Çok aktif (haftada 6-7 gün)',
    'very_active'=> 'Aşırı aktif (günde 2 kez)',
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kayıt Ol — KaloriAI</title>
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

<div class="auth-container auth-container--wide">
  <div class="auth-card glass-card">

    <div class="auth-logo">
      <div class="logo-icon">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="24" cy="24" r="22" fill="url(#lg2)" opacity="0.2"/>
          <path d="M24 8C15.2 8 8 15.2 8 24s7.2 16 16 16 16-7.2 16-16S32.8 8 24 8zm0 4c6.6 0 12 5.4 12 12s-5.4 12-12 12S12 30.6 12 24 17.4 12 24 12z" fill="url(#lg2)"/>
          <path d="M24 16c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8z" fill="url(#lg2)"/>
          <defs>
            <linearGradient id="lg2" x1="8" y1="8" x2="40" y2="40" gradientUnits="userSpaceOnUse">
              <stop stop-color="#a78bfa"/>
              <stop offset="1" stop-color="#38bdf8"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
      <span class="logo-text">KaloriAI</span>
    </div>

    <h1 class="auth-title">Hesap Oluştur</h1>
    <p class="auth-subtitle">Kişisel kalori hedefinizi hesaplayalım</p>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- Progress Steps -->
    <div class="steps-indicator">
      <div class="step active" id="step-dot-1">1</div>
      <div class="step-line"></div>
      <div class="step" id="step-dot-2">2</div>
      <div class="step-line"></div>
      <div class="step" id="step-dot-3">3</div>
    </div>

    <form method="POST" action="/auth/register_action.php" class="auth-form" id="registerForm">

      <!-- ADIM 1: Hesap Bilgileri -->
      <div class="form-step active" id="step-1">
        <h3 class="step-title">Hesap Bilgileri</h3>
        <div class="form-group">
          <label for="name">Ad Soyad</label>
          <div class="input-wrapper">
            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
            <input type="text" id="name" name="name" placeholder="Adınız Soyadınız" required>
          </div>
        </div>
        <div class="form-group">
          <label for="reg-email">E-posta Adresi</label>
          <div class="input-wrapper">
            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
            <input type="email" id="reg-email" name="email" placeholder="ornek@email.com" required autocomplete="email">
          </div>
        </div>
        <div class="form-group">
          <label for="reg-password">Şifre</label>
          <div class="input-wrapper">
            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <input type="password" id="reg-password" name="password" placeholder="En az 6 karakter" required minlength="6" autocomplete="new-password">
            <button type="button" class="toggle-password" onclick="togglePass(this)">
              <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
            </button>
          </div>
        </div>
        <button type="button" class="btn btn-primary btn-full" onclick="nextStep(2)">
          Devam <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </button>
      </div>

      <!-- ADIM 2: Fiziksel Bilgiler -->
      <div class="form-step" id="step-2">
        <h3 class="step-title">Fiziksel Bilgileriniz</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="age">Yaş</label>
            <div class="input-wrapper">
              <input type="number" id="age" name="age" placeholder="25" min="10" max="100" required>
            </div>
          </div>
          <div class="form-group">
            <label>Cinsiyet</label>
            <div class="gender-toggle">
              <label class="gender-option">
                <input type="radio" name="gender" value="male" checked>
                <span>👨 Erkek</span>
              </label>
              <label class="gender-option">
                <input type="radio" name="gender" value="female">
                <span>👩 Kadın</span>
              </label>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="height_cm">Boy (cm)</label>
            <div class="input-wrapper">
              <input type="number" id="height_cm" name="height_cm" placeholder="170" min="100" max="250" required>
            </div>
          </div>
          <div class="form-group">
            <label for="weight_kg">Kilo (kg)</label>
            <div class="input-wrapper">
              <input type="number" id="weight_kg" name="weight_kg" placeholder="70" min="30" max="300" step="0.1" required>
            </div>
          </div>
        </div>
        <div class="step-buttons">
          <button type="button" class="btn btn-ghost" onclick="prevStep(1)">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
            Geri
          </button>
          <button type="button" class="btn btn-primary" onclick="nextStep(3)">
            Devam <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
          </button>
        </div>
      </div>

      <!-- ADIM 3: Aktivite & Hedef -->
      <div class="form-step" id="step-3">
        <h3 class="step-title">Aktivite & Hedefiniz</h3>
        <div class="form-group">
          <label for="activity_level">Aktivite Düzeyi</label>
          <select id="activity_level" name="activity_level" required>
            <?php foreach ($activityLabels as $val => $lbl): ?>
              <option value="<?= $val ?>"><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Hedefiniz</label>
          <div class="goal-cards">
            <label class="goal-card">
              <input type="radio" name="goal" value="lose">
              <div class="goal-card-inner">
                <span class="goal-icon">🔥</span>
                <span class="goal-name">Kilo Ver</span>
                <span class="goal-desc">-500 kcal/gün</span>
              </div>
            </label>
            <label class="goal-card">
              <input type="radio" name="goal" value="maintain" checked>
              <div class="goal-card-inner">
                <span class="goal-icon">⚖️</span>
                <span class="goal-name">Koru</span>
                <span class="goal-desc">Dengeli beslen</span>
              </div>
            </label>
            <label class="goal-card">
              <input type="radio" name="goal" value="gain">
              <div class="goal-card-inner">
                <span class="goal-icon">💪</span>
                <span class="goal-name">Kilo Al</span>
                <span class="goal-desc">+300 kcal/gün</span>
              </div>
            </label>
          </div>
        </div>
        <div class="step-buttons">
          <button type="button" class="btn btn-ghost" onclick="prevStep(2)">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
            Geri
          </button>
          <button type="submit" class="btn btn-primary">
            🚀 Hesabı Oluştur
          </button>
        </div>
      </div>

    </form>

    <p class="auth-switch">
      Zaten hesabınız var mı? <a href="/login.php">Giriş Yapın</a>
    </p>
  </div>
</div>

<script>
function togglePass(btn) {
  const input = btn.previousElementSibling;
  input.type = input.type === 'password' ? 'text' : 'password';
}
let currentStep = 1;
function nextStep(n) {
  document.getElementById('step-' + currentStep).classList.remove('active');
  document.getElementById('step-dot-' + currentStep).classList.remove('active');
  currentStep = n;
  document.getElementById('step-' + n).classList.add('active');
  document.getElementById('step-dot-' + n).classList.add('active');
}
function prevStep(n) { nextStep(n); }
</script>
</body>
</html>
