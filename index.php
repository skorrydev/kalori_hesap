<?php
require_once __DIR__ . '/config/helpers.php';
$user    = requireLogin();
$targets = calculateTargets($user);
$totals  = getTodayTotals((int)$user['id']);
$flash   = getFlash();

// Bugünkü öğün kayıtları
$pdo  = getPDO();
$stmt = $pdo->prepare("
    SELECT fl.id, fl.grams, fl.meal_type,
           fi.name, fi.calories, fi.protein, fi.carbs, fi.fat
    FROM food_logs fl
    JOIN food_items fi ON fi.id = fl.food_item_id
    WHERE fl.user_id = ? AND fl.logged_at = ?
    ORDER BY fl.created_at ASC
");
$stmt->execute([$user['id'], date('Y-m-d')]);
$logs = $stmt->fetchAll();

$meals = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snack' => []];
$mealNames = ['breakfast'=>'Kahvaltı','lunch'=>'Öğle','dinner'=>'Akşam','snack'=>'Ara Öğün'];
$mealIcons = ['breakfast'=>'🌅','lunch'=>'☀️','dinner'=>'🌙','snack'=>'🍎'];

foreach ($logs as $log) {
    $meals[$log['meal_type']][] = $log;
}

// Son 7 günlük geçmiş (min grafik için)
$histStmt = $pdo->prepare("
    SELECT fl.logged_at,
           COALESCE(SUM(fi.calories * fl.grams / 100),0) AS calories
    FROM food_logs fl
    JOIN food_items fi ON fi.id = fl.food_item_id
    WHERE fl.user_id = ?
      AND fl.logged_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY fl.logged_at
    ORDER BY fl.logged_at
");
$histStmt->execute([$user['id']]);
$history = [];
for ($i = 6; $i >= 0; $i--) {
    $history[date('Y-m-d', strtotime("-$i days"))] = 0;
}
foreach ($histStmt as $row) {
    $history[$row['logged_at']] = round($row['calories']);
}

// Kategoriler (besin arama filtresi için)
$catStmt = $pdo->query('SELECT DISTINCT category FROM food_items ORDER BY category');
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

$calProgress  = min(100, $targets['calories'] > 0 ? round($totals['calories'] / $targets['calories'] * 100) : 0);
$proProgress  = min(100, $targets['protein']  > 0 ? round($totals['protein']  / $targets['protein']  * 100) : 0);
$carbProgress = min(100, $targets['carbs']    > 0 ? round($totals['carbs']    / $targets['carbs']    * 100) : 0);
$fatProgress  = min(100, $targets['fat']      > 0 ? round($totals['fat']      / $targets['fat']      * 100) : 0);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — KaloriAI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="app-page">

<!-- ============ NAVBAR ============ -->
<nav class="navbar glass-nav">
  <div class="nav-brand">
    <div class="logo-icon logo-icon--sm">
      <svg viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="22" fill="url(#lgn)" opacity="0.2"/><path d="M24 8C15.2 8 8 15.2 8 24s7.2 16 16 16 16-7.2 16-16S32.8 8 24 8zm0 4c6.6 0 12 5.4 12 12s-5.4 12-12 12S12 30.6 12 24 17.4 12 24 12z" fill="url(#lgn)"/><path d="M24 16c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8z" fill="url(#lgn)"/><defs><linearGradient id="lgn" x1="8" y1="8" x2="40" y2="40" gradientUnits="userSpaceOnUse"><stop stop-color="#a78bfa"/><stop offset="1" stop-color="#38bdf8"/></linearGradient></defs></svg>
    </div>
    <span class="logo-text">KaloriAI</span>
  </div>
  <div class="nav-center">
    <span class="nav-date">
      <?php 
        $fmt = new IntlDateFormatter('tr_TR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        echo $fmt->format(time());
      ?>
    </span>
  </div>
  <div class="nav-actions">
    <a href="/profile.php" class="nav-btn" title="Profil">
      <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
      <span><?= htmlspecialchars($user['name']) ?></span>
    </a>
    <a href="/auth/logout.php" class="nav-btn nav-btn--logout" title="Çıkış">
      <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
    </a>
  </div>
</nav>

<main class="app-main">

  <?php if ($flash): ?>
    <div class="toast toast--<?= $flash['type'] ?>" id="flashToast">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- ============ HEDEF ÖZET ============ -->
  <div class="bmi-banner glass-card" id="bmiBanner">
    <div class="bmi-info">
      <h2>Merhaba, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h2>
      <p>BMR: <strong><?= $targets['bmr'] ?> kcal</strong> &nbsp;|&nbsp; TDEE: <strong><?= $targets['tdee'] ?> kcal</strong> &nbsp;|&nbsp; Hedef: <strong><?= ['lose'=>'Kilo Ver','maintain'=>'Koru','gain'=>'Kilo Al'][$user['goal']] ?></strong></p>
    </div>
    <div class="bmi-weight">
      <?php
        $bmi = round($user['weight_kg'] / (($user['height_cm']/100)**2), 1);
        if ($bmi < 18.5)    $bmiLabel = ['Zayıf','#38bdf8'];
        elseif ($bmi < 25)  $bmiLabel = ['Normal','#4ade80'];
        elseif ($bmi < 30)  $bmiLabel = ['Fazla','#fb923c'];
        else                $bmiLabel = ['Obez','#f87171'];
      ?>
      <div class="bmi-circle" style="--bmi-color:<?= $bmiLabel[1] ?>">
        <span class="bmi-val"><?= $bmi ?></span>
        <span class="bmi-lbl">BMI</span>
      </div>
      <span class="bmi-status" style="color:<?= $bmiLabel[1] ?>"><?= $bmiLabel[0] ?></span>
    </div>
  </div>

  <!-- ============ MAKRO HALKA GRAFİKLERİ ============ -->
  <div class="macros-grid">

    <!-- Kalori -->
    <div class="macro-card glass-card macro-card--main">
      <div class="ring-wrap">
        <svg class="ring-svg" viewBox="0 0 120 120">
          <circle class="ring-bg"  cx="60" cy="60" r="50" stroke-dasharray="314" stroke-dashoffset="0"/>
          <circle class="ring-fill ring-cal" cx="60" cy="60" r="50"
            stroke-dasharray="314"
            stroke-dashoffset="<?= 314 - (314 * $calProgress / 100) ?>"/>
        </svg>
        <div class="ring-center">
          <span class="ring-val" id="calVal"><?= $totals['calories'] ?></span>
          <span class="ring-unit">kcal</span>
        </div>
      </div>
      <div class="macro-meta">
        <h3>Kalori</h3>
        <p id="calRemain"><?= max(0, $targets['calories'] - $totals['calories']) ?> kcal kaldı</p>
        <div class="macro-target">Hedef: <strong><?= $targets['calories'] ?></strong> kcal</div>
      </div>
    </div>

    <!-- Protein -->
    <div class="macro-card glass-card">
      <div class="ring-wrap ring-wrap--sm">
        <svg class="ring-svg" viewBox="0 0 80 80">
          <circle class="ring-bg"  cx="40" cy="40" r="32" stroke-dasharray="201" stroke-dashoffset="0"/>
          <circle class="ring-fill ring-pro" cx="40" cy="40" r="32"
            stroke-dasharray="201"
            stroke-dashoffset="<?= 201 - (201 * $proProgress / 100) ?>" id="proRing"/>
        </svg>
        <div class="ring-center">
          <span class="ring-val ring-val--sm" id="proVal"><?= $totals['protein'] ?></span>
          <span class="ring-unit ring-unit--xs">g</span>
        </div>
      </div>
      <div class="macro-meta">
        <h3>Protein</h3>
        <p id="proRemain"><?= max(0, $targets['protein'] - $totals['protein']) ?> g kaldı</p>
        <div class="macro-target">Hedef: <strong><?= $targets['protein'] ?></strong>g</div>
      </div>
    </div>

    <!-- Karbonhidrat -->
    <div class="macro-card glass-card">
      <div class="ring-wrap ring-wrap--sm">
        <svg class="ring-svg" viewBox="0 0 80 80">
          <circle class="ring-bg"  cx="40" cy="40" r="32" stroke-dasharray="201" stroke-dashoffset="0"/>
          <circle class="ring-fill ring-carb" cx="40" cy="40" r="32"
            stroke-dasharray="201"
            stroke-dashoffset="<?= 201 - (201 * $carbProgress / 100) ?>" id="carbRing"/>
        </svg>
        <div class="ring-center">
          <span class="ring-val ring-val--sm" id="carbVal"><?= $totals['carbs'] ?></span>
          <span class="ring-unit ring-unit--xs">g</span>
        </div>
      </div>
      <div class="macro-meta">
        <h3>Karbonhidrat</h3>
        <p id="carbRemain"><?= max(0, $targets['carbs'] - $totals['carbs']) ?> g kaldı</p>
        <div class="macro-target">Hedef: <strong><?= $targets['carbs'] ?></strong>g</div>
      </div>
    </div>

    <!-- Yağ -->
    <div class="macro-card glass-card">
      <div class="ring-wrap ring-wrap--sm">
        <svg class="ring-svg" viewBox="0 0 80 80">
          <circle class="ring-bg"  cx="40" cy="40" r="32" stroke-dasharray="201" stroke-dashoffset="0"/>
          <circle class="ring-fill ring-fat" cx="40" cy="40" r="32"
            stroke-dasharray="201"
            stroke-dashoffset="<?= 201 - (201 * $fatProgress / 100) ?>" id="fatRing"/>
        </svg>
        <div class="ring-center">
          <span class="ring-val ring-val--sm" id="fatVal"><?= $totals['fat'] ?></span>
          <span class="ring-unit ring-unit--xs">g</span>
        </div>
      </div>
      <div class="macro-meta">
        <h3>Yağ</h3>
        <p id="fatRemain"><?= max(0, $targets['fat'] - $totals['fat']) ?> g kaldı</p>
        <div class="macro-target">Hedef: <strong><?= $targets['fat'] ?></strong>g</div>
      </div>
    </div>

  </div><!-- /macros-grid -->

  <!-- ============ ANA PANEL ============ -->
  <div class="panel-grid">

    <!-- Sol: Besin Ekleme -->
    <div class="panel glass-card" id="addPanel">
      <!-- Panel Sekmeleri -->
      <div class="panel-tabs">
        <button class="tab-btn active" onclick="switchTab('search')">🔍 Hızlı Arama</button>
        <button class="tab-btn" onclick="switchTab('manual')">➕ Manuel Ekle</button>
      </div>

      <!-- Arama Bölümü -->
      <div id="tab-search" class="form-step active">
        <div class="search-bar">
          <div class="input-wrapper">
            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
            <input type="text" id="foodSearch" placeholder="Besin ara... (örn: yulaf)" autocomplete="off">
          </div>
          <select id="catFilter">
            <option value="">Tüm Kategoriler</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Arama Sonuçları -->
        <div id="searchResults" class="search-results hidden"></div>

        <!-- Akıllı Arama İpucu -->
        <div id="smartSearchHint" class="search-hint hidden">
          <p>Veritabanında bulamadın mı? İnternette ara!</p>
          <button class="btn btn-smart" onclick="triggerSmartSearch()">🌐 Akıllı Arama yap</button>
        </div>
      </div>

      <!-- Manuel Ekleme Bölümü -->
      <div id="tab-manual" class="form-step">
        <div class="manual-form">
          <div class="form-group">
            <label>Besin Adı</label>
            <input type="text" id="manualName" placeholder="Örn: Annemin Köftesi">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Kalori (100g için)</label>
              <input type="number" id="manualCal" placeholder="0" step="0.1">
            </div>
            <div class="form-group">
              <label>P (g)</label>
              <input type="number" id="manualPro" placeholder="0" step="0.1">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>K (g)</label>
              <input type="number" id="manualCarb" placeholder="0" step="0.1">
            </div>
            <div class="form-group">
              <label>Y (g)</label>
              <input type="number" id="manualFat" placeholder="0" step="0.1">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Miktar (gram)</label>
              <input type="number" id="manualGrams" value="100">
            </div>
            <div class="form-group">
              <label>Öğün</label>
              <select id="manualMealType">
                <option value="breakfast">🌅 Kahvaltı</option>
                <option value="lunch">☀️ Öğle</option>
                <option value="dinner">🌙 Akşam</option>
                <option value="snack" selected>Ara Öğün</option>
              </select>
            </div>
          </div>
          <button class="btn btn-primary" onclick="addManualFood()">💾 Kaydet ve Ekle</button>
        </div>
      </div>

      <!-- Seçili besin formu -->
      <div id="addForm" class="add-form hidden">
        <div class="selected-food-card" id="selectedFoodInfo"></div>
        <div class="form-row">
          <div class="form-group">
            <label for="grams">Miktar (gram)</label>
            <div class="input-wrapper">
              <input type="number" id="grams" value="100" min="1" max="2000" step="1">
            </div>
          </div>
          <div class="form-group">
            <label for="mealType">Öğün</label>
            <select id="mealType">
              <option value="breakfast">🌅 Kahvaltı</option>
              <option value="lunch" selected>☀️ Öğle</option>
              <option value="dinner">🌙 Akşam</option>
              <option value="snack">🍎 Ara Öğün</option>
            </select>
          </div>
        </div>
        <!-- Anlık makro önizleme -->
        <div class="macro-preview" id="macroPreview"></div>
        <button class="btn btn-primary btn-full" onclick="logFood()">
          ✅ Kaydet
        </button>
        <button class="btn btn-ghost btn-full" onclick="clearFood()">İptal</button>
      </div>

      <!-- Hızlı seçim (varsayılan) -->
      <div id="quickPicks" class="quick-picks">
        <p class="quick-title">Sık kullanılanlar</p>
        <div class="quick-grid">
          <?php
            // Önce kullanıcının en çok yediği 6 besini çek
            $quickPdo  = getPDO();
            $quickStmt = $quickPdo->prepare("
                SELECT fi.id, fi.name, fi.calories, COUNT(fl.id) as log_count
                FROM food_items fi
                JOIN food_logs fl ON fl.food_item_id = fi.id
                WHERE fl.user_id = ?
                GROUP BY fi.id
                ORDER BY log_count DESC
                LIMIT 6
            ");
            $quickStmt->execute([$user['id']]);
            $userQuick = $quickStmt->fetchAll();
            
            // Eğer 6'dan az varsa, genel popüler olanlarla tamamla
            if (count($userQuick) < 6) {
                $countNeeded = 6 - count($userQuick);
                $idsToExclude = array_column($userQuick, 'id') ?: [0];
                $excludePlaceholders = implode(',', array_fill(0, count($idsToExclude), '?'));
                
                $popStmt = $quickPdo->prepare("
                    SELECT id, name, calories 
                    FROM food_items 
                    WHERE id NOT IN ($excludePlaceholders)
                    AND name IN ('Yumurta (tam)','Tavuk göğsü (ızgara)','Yulaf ezmesi','Muz','Pirinç (pişmiş)','Beyaz peynir','Süt (%3 yağlı)','Tam buğday ekmeği')
                    LIMIT $countNeeded
                ");
                $popStmt->execute($idsToExclude);
                $userQuick = array_merge($userQuick, $popStmt->fetchAll());
            }

            foreach ($userQuick as $q): ?>
            <button class="quick-item" onclick="selectFood(<?= $q['id'] ?>,'<?= addslashes($q['name']) ?>',false,false)">
              <span class="quick-name"><?= htmlspecialchars($q['name']) ?></span>
              <span class="quick-cal"><?= $q['calories'] ?> kcal</span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div><!-- /addPanel -->

    <!-- Sağ: Günlük Log -->
    <div class="panel glass-card" id="logPanel">
      <div class="panel-header">
        <h3>📋 Günlük Öğünler</h3>
        <span class="log-date"><?= date('d.m.Y') ?></span>
      </div>

      <?php if (array_sum(array_map('count', $meals)) === 0): ?>
        <div class="empty-log">
          <div class="empty-icon">🥗</div>
          <p>Henüz besin eklemediniz.</p>
          <p class="empty-sub">Sol panelden besin ekleyerek başlayın!</p>
        </div>
      <?php else: ?>
        <?php foreach ($meals as $type => $items): if (!$items) continue; ?>
          <div class="meal-section">
            <div class="meal-header" onclick="toggleMeal('<?= $type ?>')">
              <span><?= $mealIcons[$type] ?> <?= $mealNames[$type] ?></span>
              <div class="meal-summary">
                <?php
                  $mCal = round(array_sum(array_map(fn($l) => $l['calories'] * $l['grams'] / 100, $items)));
                  $mPro = round(array_sum(array_map(fn($l) => $l['protein']  * $l['grams'] / 100, $items)), 1);
                ?>
                <span><?= $mCal ?> kcal · <?= $mPro ?>g protein</span>
                <svg class="chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </div>
            </div>
            <div class="meal-items" id="meal-<?= $type ?>">
              <?php foreach ($items as $item):
                $itemCal = round($item['calories'] * $item['grams'] / 100);
                $itemPro = round($item['protein']  * $item['grams'] / 100, 1);
                $itemCarb= round($item['carbs']    * $item['grams'] / 100, 1);
                $itemFat = round($item['fat']      * $item['grams'] / 100, 1);
              ?>
              <div class="log-item" id="log-<?= $item['id'] ?>">
                <div class="log-info">
                  <span class="log-name"><?= htmlspecialchars($item['name']) ?></span>
                  <span class="log-detail"><?= $item['grams'] ?>g &nbsp;·&nbsp; <?= $itemCal ?> kcal &nbsp;·&nbsp; P:<?= $itemPro ?>g K:<?= $itemCarb ?>g Y:<?= $itemFat ?>g</span>
                </div>
                <button class="del-btn" onclick="deleteLog(<?= $item['id'] ?>)" title="Sil">
                  <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                </button>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div><!-- /logPanel -->

  </div><!-- /panel-grid -->

  <!-- ============ 7 GÜNLÜK GRAFİK ============ -->
  <div class="chart-card glass-card">
    <div class="panel-header">
      <h3>📈 Son 7 Günlük Kalori</h3>
      <span class="chart-target-line">Hedef: <?= $targets['calories'] ?> kcal</span>
    </div>
    <div class="chart-wrap">
      <?php
        $maxCal = max(max(array_values($history)), $targets['calories'], 1);
        $days   = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
        $dayIdx = (int)date('N') - 1; // 0=Mon
        $i = 0;
        foreach ($history as $d => $cal):
          $h = round($cal / $maxCal * 100);
          $dayLabel = $days[($dayIdx - 6 + $i + 7) % 7];
          $isToday  = ($d === date('Y-m-d'));
          $i++;
      ?>
        <div class="bar-col <?= $isToday ? 'bar-today' : '' ?>">
          <div class="bar-tooltip"><?= $cal ?> kcal</div>
          <div class="bar" style="height:<?= max(4,$h) ?>%"></div>
          <span class="bar-label"><?= $dayLabel ?></span>
        </div>
      <?php endforeach; ?>
      <!-- Hedef çizgisi -->
      <div class="target-line" style="bottom:<?= round($targets['calories'] / $maxCal * 100) ?>%"></div>
    </div>
  </div>

</main><!-- /app-main -->

<!-- Hidden data for JS -->
<script>
const TARGETS = <?= json_encode($targets) ?>;
const FOOD_DB = {};
let selectedFood = null;

// ---- Besin Arama ----
let searchTimer;
document.getElementById('foodSearch').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  if (q.length < 1) { hideResults(); return; }
  searchTimer = setTimeout(() => searchFood(q), 260);
});
document.getElementById('catFilter').addEventListener('change', function() {
  const q = document.getElementById('foodSearch').value.trim();
  if (q) searchFood(q);
});

async function searchFood(q) {
  const cat = document.getElementById('catFilter').value;
  const res = await fetch(`/api/search_food.php?q=${encodeURIComponent(q)}&cat=${encodeURIComponent(cat)}`);
  const data = await res.json();
  showResults(data);
}

function showResults(items, isSmart = false) {
  const box = document.getElementById('searchResults');
  const hint = document.getElementById('smartSearchHint');
  
  if (!items.length) {
    box.innerHTML = '<div class="search-empty">Sonuç bulunamadı.</div>';
    box.classList.remove('hidden');
    if (!isSmart) hint.classList.remove('hidden');
    return;
  }
  
  if (!isSmart) hint.classList.add('hidden');

  // Cache and Assign IDs FIRST
  items.forEach(f => {
    if (!f.id) f.id = 'smart_' + Math.random().toString(36).substr(2,9);
    FOOD_DB[f.id] = f;
  });
  
  box.innerHTML = items.map(f => {
    const fid = typeof f.id === 'string' ? `'${f.id}'` : f.id;
    return `
      <div class="result-item" onclick="selectFood(${fid}, '${escJs(f.name)}', false, ${isSmart ? 'true' : 'false'})">
        <div class="result-name">${escHtml(f.name)} ${isSmart ? '🌐' : ''}</div>
        <div class="result-macro">
          <span class="tag-cal">${f.calories} kcal</span>
          <span class="tag-pro">P:${f.protein}g</span>
          <span class="tag-carb">K:${f.carbs}g</span>
          <span class="tag-fat">Y:${f.fat}g</span>
        </div>
        <div class="result-cat">${escHtml(f.category || '')}</div>
      </div>
    `;
  }).join('');
  box.classList.remove('hidden');
}

function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
  
  if (tab === 'search') {
    document.querySelector('.tab-btn:first-child').classList.add('active');
    document.getElementById('tab-search').classList.add('active');
  } else {
    document.querySelector('.tab-btn:last-child').classList.add('active');
    document.getElementById('tab-manual').classList.add('active');
  }
}

async function triggerSmartSearch() {
  const q = document.getElementById('foodSearch').value.trim();
  if (!q) return;
  
  showToast('🛠️ İnternet taranıyor...', 'success');
  const res = await fetch(`/api/smart_search.php?q=${encodeURIComponent(q)}`);
  const data = await res.json();
  showResults(data, true);
}

async function addManualFood() {
  const name = document.getElementById('manualName').value.trim();
  const cal = document.getElementById('manualCal').value;
  if (!name || !cal) { showToast('Ad ve kalori alanı boş bırakılamaz!', 'error'); return; }

  const fd = new FormData();
  fd.append('name', name);
  fd.append('calories', cal);
  fd.append('protein', document.getElementById('manualPro').value || 0);
  fd.append('carbs', document.getElementById('manualCarb').value || 0);
  fd.append('fat', document.getElementById('manualFat').value || 0);
  fd.append('grams', document.getElementById('manualGrams').value || 100);
  fd.append('meal_type', document.getElementById('manualMealType').value);
  fd.append('date', new Date().toISOString().slice(0,10));

  const res = await fetch('/api/add_manual_food.php', { method: 'POST', body: fd });
  const data = await res.json();

  if (data.success) {
    updateRings(data.totals);
    showToast('✅ Manuel besin başarıyla eklendi!', 'success');
    setTimeout(() => location.reload(), 900);
  } else {
    showToast('Hata: ' + data.error, 'error');
  }
}

function hideResults() {
  document.getElementById('searchResults').classList.add('hidden');
}

function selectFood(id, name, isTemp = false, isSmart = false) {
  if (!FOOD_DB[id]) return;
  const food = FOOD_DB[id];
  selectedFood = food;
  selectedFood.isSmart = isSmart;

  hideResults();
  document.getElementById('smartSearchHint').classList.add('hidden');
  document.getElementById('quickPicks').classList.add('hidden');
  document.getElementById('addForm').classList.remove('hidden');

  document.getElementById('selectedFoodInfo').innerHTML = `
    <strong>${escHtml(food.name)} ${isSmart ? '🌐' : ''}</strong>
    <span class="category-badge">${escHtml(food.category || 'İnternet')}</span>
    <div class="food-macros-row">
      <span>🔥 ${food.calories} kcal</span>
      <span>🥩 ${food.protein}g P</span>
      <span>🌾 ${food.carbs}g K</span>
      <span>🫙 ${food.fat}g Y</span>
      <small>(100g başına)</small>
    </div>
  `;
  updatePreview();
  // Listener'ı sadece bir kez ekleyelim (ya da önce temizleyelim)
  const gInput = document.getElementById('grams');
  gInput.oninput = updatePreview; 
}

function updatePreview() {
  if (!selectedFood) return;
  const g = parseFloat(document.getElementById('grams').value) || 0;
  const cal  = round1(selectedFood.calories * g / 100);
  const pro  = round1(selectedFood.protein  * g / 100);
  const carb = round1(selectedFood.carbs    * g / 100);
  const fat  = round1(selectedFood.fat      * g / 100);
  document.getElementById('macroPreview').innerHTML = `
    <div class="preview-row">
      <div class="preview-item"><span class="p-val cal-col">${cal}</span><span class="p-lbl">kcal</span></div>
      <div class="preview-item"><span class="p-val pro-col">${pro}g</span><span class="p-lbl">Protein</span></div>
      <div class="preview-item"><span class="p-val carb-col">${carb}g</span><span class="p-lbl">Karbonhidrat</span></div>
      <div class="preview-item"><span class="p-val fat-col">${fat}g</span><span class="p-lbl">Yağ</span></div>
    </div>
  `;
}

function clearFood() {
  selectedFood = null;
  document.getElementById('addForm').classList.add('hidden');
  document.getElementById('quickPicks').classList.remove('hidden');
  document.getElementById('foodSearch').value = '';
}

async function logFood() {
  if (!selectedFood) return;
  const grams    = parseFloat(document.getElementById('grams').value);
  const mealType = document.getElementById('mealType').value;
  if (!grams || grams <= 0) { showToast('Geçerli bir miktar girin!','error'); return; }

  const fd = new FormData();
  let endpoint = '/api/log_food.php';

  if (selectedFood.isSmart) {
    // İnternetten geliyorsa önce veritabanına ekle ve logla (add_manual_food aynı işi yapar)
    endpoint = '/api/add_manual_food.php';
    fd.append('name',     selectedFood.name);
    fd.append('calories', selectedFood.calories);
    fd.append('protein',  selectedFood.protein);
    fd.append('carbs',    selectedFood.carbs);
    fd.append('fat',      selectedFood.fat);
  } else {
    fd.append('food_id',  selectedFood.id);
  }

  fd.append('grams',     grams);
  fd.append('meal_type', mealType);
  fd.append('date',      new Date().toISOString().slice(0,10));

  showToast('💾 Kaydediliyor...', 'success');
  const res  = await fetch(endpoint, { method:'POST', body: fd });
  const text = await res.text();
  let data;
  try { data = JSON.parse(text); } catch(e) { 
    showToast('Sunucu hatası: ' + text.substring(0,50), 'error'); 
    return; 
  }

  if (data.success) {
    updateRings(data.totals);
    showToast('✅ Başarıyla kaydedildi!', 'success');
    clearFood();
    setTimeout(() => location.reload(), 900);
  } else {
    showToast('Hata: ' + (data.error || 'Bilinmeyen hata'), 'error');
  }
}

async function deleteLog(id) {
  if (!confirm('Bu kaydı silmek istiyor musunuz?')) return;
  const fd = new FormData();
  fd.append('log_id', id);
  const res  = await fetch('/api/delete_log.php', { method:'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    document.getElementById('log-' + id)?.remove();
    updateRings(data.totals);
    showToast('🗑️ Kayıt silindi.', 'success');
  }
}

// ---- Halka Güncelle ----
function updateRings(totals) {
  const keys = ['cal','pro','carb','fat'];
  const tmap  = { cal: 'calories', pro: 'protein', carb: 'carbs', fat: 'fat' };
  const da    = { cal: 314, pro: 201, carb: 201, fat: 201 };
  keys.forEach(k => {
    const actual = totals[tmap[k]] ?? 0;
    const target = TARGETS[tmap[k]] ?? 1;
    const pct    = Math.min(1, actual / target);
    const ring   = document.getElementById(k + 'Ring');
    if (ring) ring.style.strokeDashoffset = da[k] - (da[k] * pct);
    const val = document.getElementById(k + 'Val');
    if (val) val.textContent = round1(actual);
    const rem = document.getElementById(k + 'Remain');
    if (rem) rem.textContent = Math.max(0, Math.round(target - actual)) + (k === 'cal' ? ' kcal kaldı' : ' g kaldı');
  });
}

function toggleMeal(type) {
  const el = document.getElementById('meal-' + type);
  el.classList.toggle('open');
}

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = `toast toast--${type}`;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 3000);
}

function round1(n) { return Math.round(n * 10) / 10; }
function escHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function escJs(s)   { return s.replace(/'/g,"\\'"); }

// Flash auto-hide
const flash = document.getElementById('flashToast');
if (flash) { setTimeout(() => flash.classList.add('show'), 50); setTimeout(() => flash.remove(), 4000); }

// Open today's meals by default
document.querySelectorAll('.meal-items').forEach(el => el.classList.add('open'));
</script>
</body>
</html>
