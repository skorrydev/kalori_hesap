<?php
require_once __DIR__ . '/../config/helpers.php';

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
    exit;
}

$name     = trim($_POST['name'] ?? '');
$cal      = (float)($_POST['calories'] ?? 0);
$pro      = (float)($_POST['protein'] ?? 0);
$carb     = (float)($_POST['carbs'] ?? 0);
$fat      = (float)($_POST['fat'] ?? 0);
$grams    = (float)($_POST['grams'] ?? 100);
$mealType = $_POST['meal_type'] ?? 'snack';
$date     = $_POST['date'] ?? date('Y-m-d');

if (!$name || $cal < 0) {
    echo json_encode(['success' => false, 'error' => 'Besin adı ve kalori gereklidir.']);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // 1. Besini food_items'a ekle (Kullanıcıya özel veya genel yapılabilir, şimdilik genel ama kategorisi 'Manuel')
    $stmt = $pdo->prepare("INSERT INTO food_items (name, calories, protein, carbs, fat, category) VALUES (?, ?, ?, ?, ?, 'Manuel')");
    $stmt->execute([$name, $cal, $pro, $carb, $fat]);
    $foodId = $pdo->lastInsertId();

    // 2. food_logs'a kaydet
    $logStmt = $pdo->prepare("INSERT INTO food_logs (user_id, food_item_id, grams, meal_type, logged_at) VALUES (?, ?, ?, ?, ?)");
    $logStmt->execute([$user['id'], $foodId, $grams, $mealType, $date]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'totals'  => getTodayTotals((int)$user['id'], $date)
    ]);

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
