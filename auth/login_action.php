<?php
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    setFlash('error', 'E-posta ve şifre gereklidir.');
    header('Location: /login.php');
    exit;
}

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    setFlash('error', 'E-posta veya şifre hatalı.');
    header('Location: /login.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
header('Location: /index.php');
exit;
