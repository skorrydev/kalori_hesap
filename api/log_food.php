<?php
require_once __DIR__ . '/../config/helpers.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Yetkisiz']);
    exit;
}

$userId     = (int)$_SESSION['user_id'];
$foodId     = (int)($_POST['food_id'] ?? 0);
$grams      = (float)($_POST['grams'] ?? 100);
$mealType   = $_POST['meal_type'] ?? 'lunch';
$date       = $_POST['date'] ?? date('Y-m-d');
$validMeals = ['breakfast','lunch','dinner','snack'];

if (!$foodId || $grams <= 0 || !in_array($mealType, $validMeals)) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz veri']);
    exit;
}

$pdo  = getPDO();
$stmt = $pdo->prepare("
    INSERT INTO food_logs (user_id, food_item_id, grams, meal_type, logged_at)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$userId, $foodId, $grams, $mealType, $date]);

// Güncel toplamları döndür
$today = getTodayTotals($userId, $date);
echo json_encode(['success' => true, 'totals' => $today]);
