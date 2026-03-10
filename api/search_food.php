<?php
require_once __DIR__ . '/../config/helpers.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Yetkisiz']);
    exit;
}

$q   = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$pdo  = getPDO();
$sql  = 'SELECT id, name, category, calories, protein, carbs, fat FROM food_items WHERE name LIKE ?';
$args = ["%$q%"];

if ($cat) {
    $sql  .= ' AND category = ?';
    $args[] = $cat;
}
$sql .= ' ORDER BY name LIMIT 20';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
echo json_encode($stmt->fetchAll());
