<?php
require_once __DIR__ . '/../config/helpers.php';

// API isteği olduğu için redirect yerine JSON hatası döndür
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Yetkisiz']);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Yetkisiz']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. LOKAL VERİTABANI ARAMASI (Hızlı ve Güvenilir)
// Kullanıcı yerel olanları da görsün ki kafa karışıklığı olmasın
try {
    $stmt = $pdo->prepare("SELECT name, calories, protein, carbs, fat, category FROM food_items WHERE name LIKE ? LIMIT 5");
    $stmt->execute(["%$query%"]);
    $dbResults = $stmt->fetchAll();
    foreach ($dbResults as $r) {
        $results[] = [
            'name'     => $r['name'],
            'calories' => (float)$r['calories'],
            'protein'  => (float)$r['protein'],
            'carbs'    => (float)$r['carbs'],
            'fat'      => (float)$r['fat'],
            'category' => $r['category']
        ];
    }
} catch (Exception $e) {}

// 2. DOĞRULANMIŞ POPÜLER ÜRÜNLER (Manuel Liste - En Kesin Sonuçlar)
// API aramasına gerek kalmadan en çok arananları buradan veriyoruz
$brandedDictionary = [
    'tadelle' => [
        ['name' => 'Tadelle Sütlü Çikolata (30g)', 'calories' => 538, 'protein' => 6.2, 'carbs' => 55, 'fat' => 32],
        ['name' => 'Tadelle Bitter Çikolata', 'calories' => 545, 'protein' => 7.1, 'carbs' => 45, 'fat' => 36],
        ['name' => 'Tadelle Fındıklı Bar', 'calories' => 560, 'protein' => 8.5, 'carbs' => 48, 'fat' => 35]
    ],
    'eti puf' => [
        ['name' => 'Eti Puf (Kakaolu)', 'calories' => 401, 'protein' => 3.1, 'carbs' => 78, 'fat' => 8.5],
        ['name' => 'Eti Puf (Hindistan Cevizli)', 'calories' => 395, 'protein' => 2.8, 'carbs' => 80, 'fat' => 7.5]
    ],
    'ülker çikolatalı gofret' => [
        ['name' => 'Ülker Çikolatalı Gofret', 'calories' => 541, 'protein' => 5.8, 'carbs' => 59, 'fat' => 31]
    ],
    'browni' => [
        ['name' => 'Eti Browni Intense', 'calories' => 465, 'protein' => 4.8, 'carbs' => 54, 'fat' => 25],
        ['name' => 'Eti Browni Gold', 'calories' => 430, 'protein' => 5.2, 'carbs' => 50, 'fat' => 22]
    ],
    'canga' => [
        ['name' => 'Eti Canga', 'calories' => 528, 'protein' => 12, 'carbs' => 45, 'fat' => 32]
    ],
    'metro' => [
        ['name' => 'Ülker Metro', 'calories' => 485, 'protein' => 4.2, 'carbs' => 68, 'fat' => 21]
    ],
    'albeni' => [
        ['name' => 'Ülker Albeni', 'calories' => 510, 'protein' => 4.8, 'carbs' => 62, 'fat' => 26]
    ],
    'didoya' => [
        ['name' => 'Ülker Dido', 'calories' => 535, 'protein' => 6.5, 'carbs' => 55, 'fat' => 31]
    ]
];

foreach ($brandedDictionary as $key => $items) {
    if (stripos($query, $key) !== false) {
        foreach ($items as $item) {
            $item['category'] = '🌐 Popüler Ürün';
            $results[] = $item;
        }
    }
}

// 3. OPEN FOOD FACTS (Canlı Arama)
// Sadece eğer yeterli sonuç yoksa veya özel bir şey aranıyorsa
if (count($results) < 10) {
    $searchUrl = "https://world.openfoodfacts.org/cgi/search.pl?search_terms=" . urlencode($query) . "&search_simple=1&action=process&json=1&page_size=10";
    
    $context = stream_context_create([
        'http' => [
            'header'  => "User-Agent: KaloriAI-SearchModule/2.0 (skorry@example.com)\r\n",
            // Timeout'u azaltarak arayüzün donmasını engelliyoruz (API genelde çok yavaş olabiliyor)
            'timeout' => 1.5,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($searchUrl, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $name = $product['product_name_tr'] ?? $product['product_name'] ?? '';
                if (!$name || mb_strlen($name) < 2) continue;

                $nutriments = $product['nutriments'] ?? [];
                
                // Kalori bulma (kcal)
                $kcal = (float)($nutriments['energy-kcal_100g'] ?? $nutriments['energy-kcal'] ?? 0);
                if ($kcal == 0 && isset($nutriments['energy_100g'])) {
                    $kcal = round($nutriments['energy_100g'] / 4.184); // kJ to kcal
                }

                $protein = (float)($nutriments['proteins_100g'] ?? 0);
                $carbs   = (float)($nutriments['carbohydrates_100g'] ?? 0);
                $fat     = (float)($nutriments['fat_100g'] ?? 0);

                // Boş verileri ele (Su ve soda hariç)
                if ($kcal < 1 && $protein < 0.1 && $carbs < 0.1 && !stripos($name, 'su')) continue;

                $brand = $product['brands'] ?? '';
                $displayName = ($brand ? "[$brand] " : "") . $name;
                
                $results[] = [
                    'name'     => $displayName,
                    'calories' => round($kcal),
                    'protein'  => round($protein, 1),
                    'carbs'    => round($carbs, 1),
                    'fat'      => round($fat, 1),
                    'category' => '🌐 İnternet'
                ];
            }
        }
    }
}

// 4. SONUÇLARI TEMİZLE VE SIRALA
$finalResults = [];
$seenNames = [];

foreach ($results as $res) {
    $nameLower = mb_strtolower($res['name']);
    if (!isset($seenNames[$nameLower])) {
        $finalResults[] = $res;
        $seenNames[$nameLower] = true;
    }
}

// Arama sorgusuna en yakın olanı başa alalım (Basit sıralama)
usort($finalResults, function($a, $b) use ($query) {
    $scoreA = (stripos($a['name'], $query) === 0 ? 10 : (stripos($a['name'], $query) !== false ? 5 : 0));
    $scoreB = (stripos($b['name'], $query) === 0 ? 10 : (stripos($b['name'], $query) !== false ? 5 : 0));
    return $scoreB <=> $scoreA;
});

echo json_encode(array_slice($finalResults, 0, 15));
