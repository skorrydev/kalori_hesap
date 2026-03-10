<?php
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php');
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$age      = (int)($_POST['age'] ?? 0);
$gender   = in_array($_POST['gender'] ?? '', ['male','female']) ? $_POST['gender'] : 'male';
$height   = (float)($_POST['height_cm'] ?? 0);
$weight   = (float)($_POST['weight_kg'] ?? 0);
$activity = $_POST['activity_level'] ?? 'moderate';
$goal     = in_array($_POST['goal'] ?? '', ['lose','maintain','gain']) ? $_POST['goal'] : 'maintain';

// Doğrulama
$errors = [];
if (!$name)                             $errors[] = 'Ad Soyad gereklidir.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
if (strlen($password) < 6)              $errors[] = 'Şifre en az 6 karakter olmalıdır.';
if ($age < 10 || $age > 100)            $errors[] = 'Geçerli bir yaş girin (10-100).';
if ($height < 100 || $height > 250)     $errors[] = 'Geçerli bir boy girin (100-250 cm).';
if ($weight < 30 || $weight > 300)      $errors[] = 'Geçerli bir kilo girin (30-300 kg).';

if ($errors) {
    setFlash('error', implode(' ', $errors));
    header('Location: /register.php');
    exit;
}

$pdo = getPDO();

// E-posta benzersizliği kontrolü
$check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    setFlash('error', 'Bu e-posta adresi zaten kayıtlı.');
    header('Location: /register.php');
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$ins  = $pdo->prepare("
    INSERT INTO users (name, email, password_hash, age, gender, height_cm, weight_kg, activity_level, goal)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$ins->execute([$name, $email, $hash, $age, $gender, $height, $weight, $activity, $goal]);

$_SESSION['user_id'] = $pdo->lastInsertId();
setFlash('success', 'Hoş geldiniz ' . $name . '! Kalori hedefiniz hesaplandı.');
header('Location: /index.php');
exit;
