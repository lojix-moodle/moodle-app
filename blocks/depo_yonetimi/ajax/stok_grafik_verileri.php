<?php
require_once('../../../config.php');
global $DB, $CFG;

// Parametre kontrolü
$depoid = required_param('depoid', PARAM_INT);
$gun = optional_param('gun', 30, PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT);

// Güvenlik kontrolü
require_login();

// Tarih aralığı
$baslangic = time() - ($gun * 86400);

// SQL sorgusu parametreleri
$params = ['depoid' => $depoid, 'baslangic' => $baslangic];
$urunFilter = '';

if ($urunid) {
    $urunFilter = 'AND sh.urunid = :urunid';
    $params['urunid'] = $urunid;
}

// Son X günlük stok hareketlerini al
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
    $tarih = $hareket->tarih_gun;
    $girisler[$tarih] = (int)$hareket->toplam_giris;
    $cikislar[$tarih] = (int)$hareket->toplam_cikis;
}

// JSON yanıtı hazırla
$sonuc = [
    'tarihler' => array_values($tarihler),
    'girisler' => array_values($girisler),
    'cikislar' => array_values($cikislar)
];

// JSON döndür
header('Content-Type: application/json');
echo json_encode($sonuc);