-- ============================================================
--  Kalori Hesaplama Uygulaması — Veritabanı Şeması
-- ============================================================

CREATE DATABASE IF NOT EXISTS kalori_hesap
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kalori_hesap;

-- -------------------------------------------------------
-- Kullanıcılar
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100)  NOT NULL,
  email           VARCHAR(150)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NOT NULL,
  age             TINYINT UNSIGNED NOT NULL DEFAULT 25,
  gender          ENUM('male','female') NOT NULL DEFAULT 'male',
  height_cm       DECIMAL(5,1)  NOT NULL DEFAULT 170,
  weight_kg       DECIMAL(5,1)  NOT NULL DEFAULT 70,
  activity_level  ENUM('sedentary','light','moderate','active','very_active') NOT NULL DEFAULT 'moderate',
  goal            ENUM('lose','maintain','gain') NOT NULL DEFAULT 'maintain',
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Besin Öğeleri (100g başına değerler)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_items (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(150)  NOT NULL,
  category        VARCHAR(80)   NOT NULL DEFAULT 'Diğer',
  calories        DECIMAL(7,2)  NOT NULL DEFAULT 0,
  protein         DECIMAL(6,2)  NOT NULL DEFAULT 0,
  carbs           DECIMAL(6,2)  NOT NULL DEFAULT 0,
  fat             DECIMAL(6,2)  NOT NULL DEFAULT 0,
  fiber           DECIMAL(6,2)  NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Günlük Besin Kayıtları
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_logs (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL,
  food_item_id    INT UNSIGNED,
  custom_name     VARCHAR(150),
  grams           DECIMAL(7,1)  NOT NULL DEFAULT 100,
  meal_type       ENUM('breakfast','lunch','dinner','snack') NOT NULL DEFAULT 'lunch',
  logged_at       DATE          NOT NULL DEFAULT (CURDATE()),
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
  FOREIGN KEY (food_item_id) REFERENCES food_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Örnek Besin Veritabanı (~90 besin)
-- -------------------------------------------------------
INSERT INTO food_items (name, category, calories, protein, carbs, fat, fiber) VALUES

-- Et & Tavuk & Balık
('Tavuk göğsü (ızgara)',  'Et & Tavuk',   165, 31.0,  0.0, 3.6, 0.0),
('Tavuk but (ızgara)',    'Et & Tavuk',   209, 26.0,  0.0,11.0, 0.0),
('Dana kıyma (yağlı)',   'Et & Tavuk',   250, 17.2,  0.0,20.0, 0.0),
('Dana kıyma (yağsız)',  'Et & Tavuk',   172, 21.4,  0.0, 9.7, 0.0),
('Kuzu eti',             'Et & Tavuk',   294, 16.6,  0.0,25.0, 0.0),
('Hindi göğsü',          'Et & Tavuk',   135, 29.9,  0.0, 1.0, 0.0),
('Somon (ızgara)',        'Balık & Deniz',208, 20.4,  0.0,13.4, 0.0),
('Ton balığı (konserve)', 'Balık & Deniz',116, 25.5,  0.0, 0.9, 0.0),
('Uskumru',              'Balık & Deniz',205, 19.0,  0.0,14.0, 0.0),
('Levrek (ızgara)',      'Balık & Deniz',124, 24.0,  0.0, 2.8, 0.0),
('Çipura',               'Balık & Deniz',121, 22.8,  0.0, 3.0, 0.0),
('Karides',              'Balık & Deniz', 99, 18.0,  0.9, 1.8, 0.0),

-- Yumurta & Süt Ürünleri
('Yumurta (tam)',         'Yumurta & Süt', 155,  12.6, 1.1, 11.0, 0.0),
('Yumurta beyazı',       'Yumurta & Süt',  52,  10.9, 0.7,  0.2, 0.0),
('Yoğurt (sade)',        'Yumurta & Süt',  61,   3.5, 4.7,  3.3, 0.0),
('Yoğurt (light)',       'Yumurta & Süt',  42,   4.3, 4.5,  0.4, 0.0),
('Süt (tam yağlı)',      'Yumurta & Süt',  61,   3.2, 4.7,  3.3, 0.0),
('Süt (yağsız)',         'Yumurta & Süt',  34,   3.4, 4.9,  0.1, 0.0),
('Beyaz peynir',         'Yumurta & Süt', 264,  17.5, 1.2, 21.0, 0.0),
('Kaşar peyniri',        'Yumurta & Süt', 380,  25.0, 2.0, 30.0, 0.0),
('Lor peyniri',          'Yumurta & Süt',  98,  11.4, 2.7,  4.6, 0.0),
('Kefir',                'Yumurta & Süt',  52,   3.4, 4.9,  1.4, 0.0),
('Süzme yoğurt',         'Yumurta & Süt', 110,  10.0, 4.0,  5.5, 0.0),
('Labne',                'Yumurta & Süt', 220,  15.0, 3.0, 16.0, 0.0),

-- Tahıllar & Ekmek
('Ekmek (tam buğday)',   'Tahıl & Ekmek', 247,   9.7,41.4,  3.3, 5.5),
('Ekmek (beyaz)',        'Tahıl & Ekmek', 265,   9.0,49.0,  3.2, 2.7),
('Pirinç (pişmiş)',      'Tahıl & Ekmek', 130,   2.7,28.2,  0.3, 0.4),
('Makarna (pişmiş)',     'Tahıl & Ekmek', 131,   5.0,25.0,  1.1, 1.8),
('Yulaf ezmesi',         'Tahıl & Ekmek', 389,  16.9,66.3,  6.9,10.6),
('Bulgur (pişmiş)',      'Tahıl & Ekmek',  83,   3.1,18.6,  0.2, 4.5),
('Kuskus (pişmiş)',      'Tahıl & Ekmek', 112,   3.8,23.2,  0.2, 1.4),
('Kepekli makarna',      'Tahıl & Ekmek', 124,   5.3,26.5,  0.5, 3.2),
('Mısır gevreği',        'Tahıl & Ekmek', 357,   8.0,84.0,  0.8, 1.2),
('Kinoa (pişmiş)',       'Tahıl & Ekmek', 120,   4.4,21.3,  1.9, 2.8),

-- Sebzeler
('Domates',              'Sebzeler',  18,  0.9,  3.9, 0.2, 1.2),
('Salatalık',            'Sebzeler',  15,  0.7,  3.6, 0.1, 0.5),
('Biber (kırmızı)',      'Sebzeler',  31,  1.0,  6.0, 0.3, 2.1),
('Brokoli',              'Sebzeler',  34,  2.8,  7.0, 0.4, 2.6),
('Ispanak',              'Sebzeler',  23,  2.9,  3.6, 0.4, 2.2),
('Havuç',                'Sebzeler',  41,  0.9,  9.6, 0.2, 2.8),
('Patates (haşlanmış)',  'Sebzeler',  87,  1.9, 20.1, 0.1, 1.8),
('Tatlı patates',        'Sebzeler',  86,  1.6, 20.1, 0.1, 3.0),
('Patlıcan',             'Sebzeler',  25,  1.0,  5.9, 0.2, 3.0),
('Marul',                'Sebzeler',  15,  1.4,  2.9, 0.2, 1.3),
('Kabak',                'Sebzeler',  17,  1.2,  3.1, 0.3, 1.0),
('Mantar',               'Sebzeler',  22,  3.1,  3.3, 0.3, 1.0),
('Soğan',                'Sebzeler',  40,  1.1,  9.3, 0.1, 1.7),
('Sarımsak',             'Sebzeler', 149,  6.4, 33.1, 0.5, 2.1),

-- Meyveler
('Elma',                 'Meyveler',  52,  0.3, 13.8, 0.2, 2.4),
('Muz',                  'Meyveler',  89,  1.1, 22.8, 0.3, 2.6),
('Portakal',             'Meyveler',  47,  0.9, 11.8, 0.1, 2.4),
('Çilek',                'Meyveler',  32,  0.7,  7.7, 0.3, 2.0),
('Karpuz',               'Meyveler',  30,  0.6,  7.6, 0.2, 0.4),
('Kavun',                'Meyveler',  34,  0.8,  8.2, 0.2, 0.9),
('Üzüm',                 'Meyveler',  67,  0.6, 17.2, 0.4, 0.9),
('Şeftali',              'Meyveler',  39,  0.9,  9.5, 0.3, 1.5),
('Armut',                'Meyveler',  57,  0.4, 15.2, 0.1, 3.1),
('Kiraz',                'Meyveler',  63,  1.1, 16.0, 0.2, 2.1),
('Yaban mersini',        'Meyveler',  57,  0.7, 14.5, 0.3, 2.4),

-- Baklagiller
('Mercimek (kırmızı)',   'Baklagiller',116,  9.0, 20.1, 0.4, 7.9),
('Nohut (pişmiş)',       'Baklagiller',164,  8.9, 27.4, 2.6, 7.6),
('Fasulye (kuru)',        'Baklagiller',337, 23.4, 60.0, 1.4,15.2),
('Barbunya (pişmiş)',    'Baklagiller',127,  8.7, 22.8, 0.5, 6.4),
('Bezelye',              'Baklagiller', 81,  5.4, 14.5, 0.4, 5.1),

-- Kuruyemiş & Tohum
('Badem',                'Kuruyemiş',  579, 21.2, 21.6,49.9,12.5),
('Ceviz',                'Kuruyemiş',  654, 15.2, 13.7,65.2, 6.7),
('Yer fıstığı',          'Kuruyemiş',  567, 25.8, 16.1,49.2, 8.5),
('Fındık',               'Kuruyemiş',  628, 15.0, 16.7,60.8, 9.7),
('Kaju',                 'Kuruyemiş',  553, 18.2, 30.2,43.9, 3.3),
('Ayçiçek çekirdeği',   'Kuruyemiş',  584, 20.8, 20.0,51.5, 8.6),
('Chia tohumu',          'Kuruyemiş',  486, 16.5, 42.1,30.7,34.4),

-- Yağlar & Soslar
('Zeytinyağı',           'Yağ & Sos',  884,  0.0,  0.0,100.0,0.0),
('Tereyağı',             'Yağ & Sos',  717,  0.9,  0.1, 81.1, 0.0),
('Avokado',              'Yağ & Sos',  160,  2.0,  8.5, 14.7, 6.7),
('Mayonez',              'Yağ & Sos',  680,  1.0,  0.6, 74.8, 0.0),

-- Hazır & Abur Cubur
('Pizza (ortalama)',      'Hazır Gıda', 266, 11.4, 33.1, 9.8, 2.3),
('Burger (et+ekmek)',    'Hazır Gıda', 295, 17.0, 24.0,14.0, 1.0),
('Patates kızartması',   'Hazır Gıda', 312,  3.4, 41.4,15.0, 3.8),
('Çikolata (sütlü)',     'Abur Cubur', 535,  7.7, 59.4,29.7, 3.4),
('Cips',                 'Abur Cubur', 547,  7.0, 52.5,37.4, 4.4),
('Bisküvi (tam tahıl)',  'Abur Cubur', 418,  9.0, 73.5, 9.0, 7.0),

-- İçecekler
('Süt kahvesi (latte)',  'İçecekler',   50,  3.0,  5.0, 1.8, 0.0),
('Portakal suyu',        'İçecekler',   45,  0.7, 10.4, 0.2, 0.2),
('Kola',                 'İçecekler',   42,  0.0, 10.6, 0.0, 0.0),
('Protein shake',        'İçecekler',   90, 18.0,  5.0, 0.5, 0.5),

-- Türk Mutfağı
('Mercimek çorbası',     'Türk Yemekleri', 55,  3.5,  9.0, 0.8, 2.0),
('Pilav (sade)',         'Türk Yemekleri',150,  3.0, 32.0, 1.5, 0.5),
('Köfte (ızgara)',       'Türk Yemekleri',227, 19.0,  4.0,15.0, 0.3),
('Döner (tavuk)',        'Türk Yemekleri',190, 20.0,  5.0,10.0, 0.5),
('Menemen',              'Türk Yemekleri', 95,  5.5,  4.5, 6.5, 1.0),
('İmam bayıldı',        'Türk Yemekleri',120,  2.0,  8.0, 9.0, 2.5),
('Ayran',                'Türk Yemekleri', 36,  3.0,  3.0, 1.5, 0.0),
('Simit',                'Türk Yemekleri',292,  9.5, 54.0, 4.5, 3.0);
