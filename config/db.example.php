<?php
// ============================================================
//  Veritabanı Bağlantısı Örnek Dosyası
//  Bu dosyayı 'db.php' olarak kopyalayın ve kendi bilgilerinizi girin.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'veritabani_adi_buraya');
define('DB_USER', 'veritabani_kullanici_adi_buraya');
define('DB_PASS', 'veritabani_sifresi_buraya');
define('DB_CHARSET', 'utf8mb4');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Veritabanı bağlantısı kurulamadı. Lütfen ayarlarınızı kontrol edin.']));
        }
    }
    return $pdo;
}
