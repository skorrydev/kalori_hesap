<?php
// ============================================================
//  Ortak Yardımcılar
// ============================================================

require_once __DIR__ . '/../config/db.php';

session_start();

// ---- Kalori & Makro Hesaplama (Mifflin-St Jeor + Harris-Benedict) ----

function calculateTargets(array $user): array {
    $w = (float)$user['weight_kg'];
    $h = (float)$user['height_cm'];
    $a = (int)$user['age'];
    $g = $user['gender'];

    // Mifflin-St Jeor BMR
    if ($g === 'male') {
        $bmr = 10 * $w + 6.25 * $h - 5 * $a + 5;
    } else {
        $bmr = 10 * $w + 6.25 * $h - 5 * $a - 161;
    }

    // TDEE (Toplam Günlük Enerji Harcaması)
    $multipliers = [
        'sedentary'  => 1.2,
        'light'      => 1.375,
        'moderate'   => 1.55,
        'active'     => 1.725,
        'very_active'=> 1.9,
    ];
    $tdee = $bmr * ($multipliers[$user['activity_level']] ?? 1.55);

    // Hedefe göre kalori ayarı
    switch ($user['goal']) {
        case 'lose':    $calories = $tdee - 500; break;
        case 'gain':    $calories = $tdee + 300; break;
        default:        $calories = $tdee;
    }
    $calories = max(1200, $calories);

    // Makro hedefleri
    switch ($user['goal']) {
        case 'lose':
            $protein = round($w * 2.0);       // 2.0g/kg
            $fat     = round($w * 0.8);        // 0.8g/kg
            break;
        case 'gain':
            $protein = round($w * 1.8);        // 1.8g/kg
            $fat     = round($w * 1.0);        // 1.0g/kg
            break;
        default:
            $protein = round($w * 1.6);        // 1.6g/kg
            $fat     = round($w * 0.9);        // 0.9g/kg
    }
    $proteinCal = $protein * 4;
    $fatCal     = $fat * 9;
    $carbCal    = max(0, $calories - $proteinCal - $fatCal);
    $carbs      = round($carbCal / 4);

    return [
        'calories' => round($calories),
        'protein'  => $protein,
        'carbs'    => $carbs,
        'fat'      => $fat,
        'bmr'      => round($bmr),
        'tdee'     => round($tdee),
    ];
}

// ---- Günlük besin toplamlarını getir ----
function getTodayTotals(int $userId, ?string $date = null): array {
    $pdo  = getPDO();
    $date = $date ?? date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(fi.calories * fl.grams / 100), 0) AS calories,
            COALESCE(SUM(fi.protein  * fl.grams / 100), 0) AS protein,
            COALESCE(SUM(fi.carbs    * fl.grams / 100), 0) AS carbs,
            COALESCE(SUM(fi.fat      * fl.grams / 100), 0) AS fat
        FROM food_logs fl
        JOIN food_items fi ON fi.id = fl.food_item_id
        WHERE fl.user_id = :uid AND fl.logged_at = :date
    ");
    $stmt->execute([':uid' => $userId, ':date' => $date]);
    $row = $stmt->fetch();
    return [
        'calories' => round($row['calories']),
        'protein'  => round($row['protein'], 1),
        'carbs'    => round($row['carbs'], 1),
        'fat'      => round($row['fat'], 1),
    ];
}

// ---- Oturumu doğrula – giriş yapın ----
function requireLogin(): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        header('Location: /login.php');
        exit;
    }
    return $user;
}

// ---- Flash mesajları ----
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
