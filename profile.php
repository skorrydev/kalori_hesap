<?php
require_once __DIR__ . '/config/helpers.php';
$user    = requireLogin();
$targets = calculateTargets($user);
$flash   = getFlash();

$activityLabels = [
    'sedentary'  => 'Hareketsiz (masa başı iş)',
    'light'      => 'Hafif aktif (haftada 1-3 gün)',
    'moderate'   => 'Orta aktif (haftada 3-5 gün)',
    'active'     => 'Çok aktif (haftada 6-7 gün)',
    'very_active'=> 'Aşırı aktif (günde 2 kez)',
];

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $age      = (int)($_POST['age'] ?? 0);
    $gender   = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : $user['gender'];
    $height   = (float)($_POST['height_cm'] ?? 0);
    $weight   = (float)($_POST['weight_kg'] ?? 0);
    $activity = $_POST['activity_level'] ?? 'moderate';
    $goal     = in_array($_POST['goal'] ?? '', ['lose','maintain','gain']) ? $_POST['goal'] : $user['goal'];

    if ($name && $age > 0 && $height > 0 && $weight > 0) {
        $pdo  = getPDO();
        $stmt = $pdo->prepare("
            UPDATE users SET name=?, age=?, gender=?, height_cm=?, weight_kg=?, activity_level=?, goal=?
            WHERE id=?
        ");
        $stmt->execute([$name, $age, $gender, $height, $weight, $activity, $goal, $user['id']]);
        setFlash('success', 'Profiliniz güncellendi! Kalori hedefiniz yeniden hesaplandı.');
        header('Location: /profile.php');
        exit;
    } else {
        setFlash('error', 'Lütfen tüm alanları doğru doldurun.');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profilim — KaloriAI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="app-page">

<nav class="navbar glass-nav">
  <div class="nav-brand">
    <a href="/index.php" class="nav-btn">
      <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
      Dashboard
    </a>
  </div>
  <div class="nav-center"><span class="logo-text">KaloriAI</span></div>
  <div class="nav-actions">
    <a href="/auth/logout.php" class="nav-btn nav-btn--logout">
      <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
      Çıkış
    </a>
  </div>
</nav>

<main class="app-main" style="max-width: 800px; margin: 0 auto;">

  <?php if ($flash): ?>
    <div class="toast toast--<?= $flash['type'] ?> show" id="flashToast"><?= htmlspecialchars($flash['msg']) ?></div>
    <script>setTimeout(() => document.getElementById('flashToast')?.remove(), 4000);</script>
  <?php endif; ?>

  <!-- Mevcut Hedefler Kartı -->
  <div class="glass-card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
    <h2 style="margin-bottom:1rem; font-size:1.1rem; color: var(--accent);">📊 Güncel Hedefleriniz</h2>
    <div class="targets-summary">
      <div class="target-item"><span class="t-val cal-col"><?= $targets['calories'] ?></span><span class="t-lbl">kcal/gün</span></div>
      <div class="target-item"><span class="t-val pro-col"><?= $targets['protein'] ?>g</span><span class="t-lbl">Protein</span></div>
      <div class="target-item"><span class="t-val carb-col"><?= $targets['carbs'] ?>g</span><span class="t-lbl">Karbonhidrat</span></div>
      <div class="target-item"><span class="t-val fat-col"><?= $targets['fat'] ?>g</span><span class="t-lbl">Yağ</span></div>
    </div>
    <p style="margin-top:.75rem; font-size:.82rem; color:var(--muted);">BMR: <?= $targets['bmr'] ?> kcal &nbsp;|&nbsp; TDEE: <?= $targets['tdee'] ?> kcal</p>
  </div>

  <!-- Profil Formu -->
  <div class="glass-card" style="padding: 2rem;">
    <h2 style="margin-bottom:1.5rem;">✏️ Profili Düzenle</h2>

    <form method="POST" class="auth-form">

      <div class="form-group">
        <label for="name">Ad Soyad</label>
        <div class="input-wrapper">
          <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="age">Yaş</label>
          <div class="input-wrapper">
            <input type="number" id="age" name="age" value="<?= $user['age'] ?>" min="10" max="100" required>
          </div>
        </div>
        <div class="form-group">
          <label>Cinsiyet</label>
          <div class="gender-toggle">
            <label class="gender-option">
              <input type="radio" name="gender" value="male" <?= $user['gender']==='male' ? 'checked' : '' ?>>
              <span>👨 Erkek</span>
            </label>
            <label class="gender-option">
              <input type="radio" name="gender" value="female" <?= $user['gender']==='female' ? 'checked' : '' ?>>
              <span>👩 Kadın</span>
            </label>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="height_cm">Boy (cm)</label>
          <div class="input-wrapper">
            <input type="number" id="height_cm" name="height_cm" value="<?= $user['height_cm'] ?>" min="100" max="250" required>
          </div>
        </div>
        <div class="form-group">
          <label for="weight_kg">Kilo (kg)</label>
          <div class="input-wrapper">
            <input type="number" id="weight_kg" name="weight_kg" value="<?= $user['weight_kg'] ?>" min="30" max="300" step="0.1" required>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="activity_level">Aktivite Düzeyi</label>
        <select id="activity_level" name="activity_level">
          <?php foreach ($activityLabels as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $user['activity_level']===$val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Hedefiniz</label>
        <div class="goal-cards">
          <label class="goal-card">
            <input type="radio" name="goal" value="lose" <?= $user['goal']==='lose' ? 'checked' : '' ?>>
            <div class="goal-card-inner"><span class="goal-icon">🔥</span><span class="goal-name">Kilo Ver</span><span class="goal-desc">-500 kcal/gün</span></div>
          </label>
          <label class="goal-card">
            <input type="radio" name="goal" value="maintain" <?= $user['goal']==='maintain' ? 'checked' : '' ?>>
            <div class="goal-card-inner"><span class="goal-icon">⚖️</span><span class="goal-name">Koru</span><span class="goal-desc">Dengeli beslen</span></div>
          </label>
          <label class="goal-card">
            <input type="radio" name="goal" value="gain" <?= $user['goal']==='gain' ? 'checked' : '' ?>>
            <div class="goal-card-inner"><span class="goal-icon">💪</span><span class="goal-name">Kilo Al</span><span class="goal-desc">+300 kcal/gün</span></div>
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">💾 Kaydet &amp; Hedefleri Güncelle</button>
    </form>
  </div>
</main>
</body>
</html>
