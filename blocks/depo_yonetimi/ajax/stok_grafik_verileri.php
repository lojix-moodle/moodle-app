<?php
require_once('../../../config.php');
global $DB, $CFG;

// Parametre kontrolü
$depoid = required_param('depoid', PARAM_INT);
$gun = optional_param('gun', 30, PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT);
$tip = optional_param('tip', 'hareketler', PARAM_ALPHA); // 'hareketler' veya 'stokseviye'

// Güvenlik kontrolü
require_login();

// JSON çıktısı için header
header('Content-Type: application/json');

// Tarih aralığı
$baslangic = time() - ($gun * 86400);

// SQL sorgusu parametreleri
$params = ['depoid' => $depoid, 'baslangic' => $baslangic];
$urunFilter = '';

if ($urunid) {
    $urunFilter = 'AND sh.urunid = :urunid';
    $params['urunid'] = $urunid;
}

// Stok seviyesi grafiği (yeni fonksiyon)
if ($tip === 'stokseviye' && $urunid) {
    // Mevcut stok durumunu al
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid], 'adet');
    $mevcut_stok = $urun ? $urun->adet : 0;

    // Şimdiki tarihe kadar olan tüm hareketleri zaman sırasıyla al
    $sql = "SELECT sh.id, sh.tarih, sh.islemtipi, sh.miktar, sh.renk, sh.beden,
            CONCAT(sh.renk, '/', sh.beden) as varyasyon
            FROM {block_depo_yonetimi_stok_hareketleri} sh
            WHERE sh.urunid = :urunid AND sh.tarih >= :baslangic
            ORDER BY sh.tarih DESC";

    $hareketler = $DB->get_records_sql($sql, ['urunid' => $urunid, 'baslangic' => $baslangic]);

    // İlk değeri hesapla (mevcut stoktan geçmişteki hareketleri geri alarak)
    $baslangic_stok = $mevcut_stok;
    foreach ($hareketler as $hareket) {
        if ($hareket->islemtipi == 'giris') {
            $baslangic_stok -= $hareket->miktar; // Girişleri çıkar
        } else {
            $baslangic_stok += $hareket->miktar; // Çıkışları ekle
        }
    }

    // Hareketleri kronolojik sıraya çevir (eskiden yeniye)
    $hareketler_sirali = array_reverse($hareketler);

    // Sonuç dizilerini hazırla
    $labels = [date('d.m.Y', $baslangic)]; // Başlangıç zamanı
    $stokSeviyesi = [$baslangic_stok];      // Başlangıç stok değeri

    $guncel_stok = $baslangic_stok;

    // Her hareket için stok seviye değişimini hesapla
    foreach ($hareketler_sirali as $hareket) {
        if ($hareket->islemtipi == 'giris') {
            $guncel_stok += $hareket->miktar; // Giriş işlemi stok arttırır
        } else {
            $guncel_stok -= $hareket->miktar; // Çıkış işlemi stok azaltır
        }

        $labels[] = date('d.m.Y H:i', $hareket->tarih);
        $stokSeviyesi[] = $guncel_stok;
    }

    // Eğer hareket yoksa şu anki stok değerini de ekle
    if (empty($hareketler)) {
        $labels[] = date('d.m.Y H:i', time());
        $stokSeviyesi[] = $mevcut_stok;
    }

    // Sonucu döndür
    $sonuc = [
        'labels' => $labels,
        'stokSeviyesi' => $stokSeviyesi,
        'success' => true
    ];

    echo json_encode($sonuc);
    exit;
}

// Orijinal gün bazlı grafik (hareketler)
$sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(sh.tarih), '%d.%m.%Y') as tarih_gun,
        SUM(CASE WHEN sh.islemtipi = 'giris' THEN sh.miktar ELSE 0 END) as toplam_giris,
        SUM(CASE WHEN sh.islemtipi = 'cikis' THEN sh.miktar ELSE 0 END) as toplam_cikis
        FROM {block_depo_yonetimi_stok_hareketleri} sh
        JOIN {block_depo_yonetimi_urunler} ur ON ur.id = sh.urunid
        WHERE ur.depoid = :depoid AND sh.tarih >= :baslangic $urunFilter
        GROUP BY DATE_FORMAT(FROM_UNIXTIME(sh.tarih), '%d.%m.%Y')
        ORDER BY sh.tarih ASC";

$hareketler = $DB->get_records_sql($sql, $params);

// Tüm günleri doldur (boş günler için sıfır değerli veri)
$tarihler = [];
$girisler = [];
$cikislar = [];

// Son X günlük boş dizi oluştur
for ($i = $gun; $i >= 0; $i--) {
    $tarih = date('d.m.Y', time() - ($i * 86400));
    $tarihler[$tarih] = $tarih;
    $girisler[$tarih] = 0;
    $cikislar[$tarih] = 0;
}

// Mevcut verileri ekle
foreach ($hareketler as $hareket) {
    if (isset($tarihler[$hareket->tarih_gun])) {
        $girisler[$hareket->tarih_gun] = (int)$hareket->toplam_giris;
        $cikislar[$hareket->tarih_gun] = (int)$hareket->toplam_cikis;
    }
}

// Sonuç dizisine aktar
$sonuc = [
    'labels' => array_values($tarihler),
    'girisler' => array_values($girisler),
    'cikislar' => array_values($cikislar),
    'success' => true
];

echo json_encode($sonuc);