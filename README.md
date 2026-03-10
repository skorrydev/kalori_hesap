# Kalori Hesaplama Uygulaması

Bu proje, kullanıcıların günlük kalori ihtiyaçlarını hesaplamalarına, tükettikleri besinleri takip etmelerine ve beslenme hedeflerine ulaşmalarına yardımcı olmak için geliştirilmiş bir web uygulamasıdır.

## 🚀 Özellikler

- **Kullanıcı Kayıt & Giriş:** Güvenli kullanıcı kimlik doğrulama sistemi.
- **Kişisel Profil Hesaplaması:** Yaş, cinsiyet, boy, kilo ve hareket seviyesine göre günlük kalori ihtiyacı (BMR) hesabı.
- **Hedefe Yönelik Beslenme:** Kilo verme, koruma veya alma hedeflerine göre kalori ayarı.
- **Besin Günlüğü:** Kahvaltı, öğle yemeği, akşam yemeği ve atıştırmalık gibi öğünlere göre tüketilen besinleri gramaj bazında ekleyebilme.
- **Geniş Besin Veritabanı:** 90'dan fazla besin ve makro besin (protein, karbonhidrat, yağ, lif) değerlerine sahip hazır MySQL veri tabanı.
- **Dinamik Takip:** Tüketilen kaloriyi, kalan kaloriyi ve günlük makro dağılımını gösteren özet paneli.

## 🛠 Kullanılan Teknolojiler

- **Backend:** PHP 8 (PDO - MySQL)
- **Frontend:** HTML5, CSS3 (özel tasarım, `assets/css/style.css`), JavaScript
- **Veritabanı:** MySQL / MariaDB

## 📦 Kurulum ve Başlangıç

Projeyi yerel ortamınızda (localhost) çalıştırmak için aşağıdaki adımları izleyin:

### 1. Dosyaları Klonlayın
Projeyi bir yerel sunucu ortamının (XAMPP, MAMP, Laravel Valet vb.) kök dizinine kopyalayın.

### 2. Veritabanını Hazırlayın
- MySQL/MariaDB sunucunuzu başlatın.
- Bir veritabanı yöneticisi (Örn: phpMyAdmin, TablePlus) ile sunucunuza bağlanın.
- Proje ana dizinindeki `db.sql` dosyasını içe aktarın (import edin). Bu işlem `kalori_hesap` adında bir veritabanı, gerekli tabloları ve örnek besin verilerini oluşturacaktır.

### 3. Veritabanı Yapılandırması
- `config/` dizinindeki `db.example.php` dosyasının adını `db.php` olarak değiştirin.
- `config/db.php` dosyasını açıp kendi veritabanı bağlantı bilgilerinizi girin:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kalori_hesap');
define('DB_USER', 'kendi_kullanici_adiniz');
define('DB_PASS', 'kendi_sifreniz');
```

> **Not:** `config/db.php` dosyası, hassas bilgiler içerdiği için `.gitignore` dosyasına eklenmiştir ve Git deposuna gönderilmeyecektir.

### 4. Uygulamayı Çalıştırın
Tarayıcınızda projenizin bulunduğu dizini açın (Örn: `http://localhost/kalori_hesap`). İlk olarak kayıt olma sayfasına yönlendirileceksiniz.

## 📂 Proje Yapısı

```
kalori_hesap/
├── api/             # Frontend ile haberleşen JSON tabanlı servisler
├── assets/          # Statik dosyalar (CSS, JS, resimler)
├── auth/            # Kimlik doğrulama işlemleri
├── config/          # Veritabanı ayarları
├── index.php        # Ana uygulama (Dashboard)
├── login.php        # Giriş sayfası
├── register.php     # Kayıt sayfası
├── profile.php      # Profil ve ayar sayfası
└── db.sql           # Veritabanı yedeği ve şeması
```

## 📜 Lisans
Bu proje geliştirme aşamasındadır.
