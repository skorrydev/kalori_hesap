<?php
require_once __DIR__ . '/../config/helpers.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Yetkisiz']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$logId  = (int)($_POST['log_id'] ?? 0);

if (!$logId) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz kayıt ID']);
    exit;
}

$pdo  = getPDO();
// Güvenlik: sadece kendi kaydını silebilir
$stmt = $pdo->prepare('DELETE FROM food_logs WHERE id = ? AND user_id = ?');
$stmt->execute([$logId, $userId]);

$today = getTodayTotals($userId);
echo json_encode(['success' => true, 'totals' => $today]);
