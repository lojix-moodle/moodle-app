<?php
// Hata ayıklama için
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Çıktı tamponlama başlat
ob_start();

require_once('../../../config.php');
global $DB, $CFG;

try {
    // Parametre kontrolü
    $depoid = required_param('depoid', PARAM_INT);
    $urunid = required_param('urunid', PARAM_INT);
    $renk = required_param('renk', PARAM_TEXT);
    $beden = required_param('beden', PARAM_TEXT);
    $gun = optional_param('gun', 30, PARAM_INT);

    // Güvenlik kontrolü
    require_login();

    // Tarih hesaplama
    $simdi = time();
    $baslangic = $simdi - ($gun * 86400);
    $sonuc = [];

    // Varyasyon stok geçmişini al
    $stok_gecmisi = varyasyon_stok_gecmisi_hesapla($urunid, $renk, $beden, $baslangic, $simdi);

    // Tarihe göre sırala
    ksort($stok_gecmisi);

    // Veri formatını hazırla
    $sonuc = [
        'labels' => [],
        'stokSeviyesi' => [],
        'success' => true,
        'count' => count($stok_gecmisi)
    ];

    foreach ($stok_gecmisi as $tarih => $miktar) {
        $sonuc['labels'][] = date('d.m', $tarih);
        $sonuc['stokSeviyesi'][] = $miktar;
    }

    // Tampon belleği temizle
    ob_end_clean();

    // JSON çıktısı
    header('Content-Type: application/json');
    echo json_encode($sonuc);

} catch (Exception $e) {
    // Tampon belleği temizle
    ob_end_clean();

    // Hata JSON çıktısı
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Varyasyon stok geçmişini hesapla
function varyasyon_stok_gecmisi_hesapla($urunid, $renk, $beden, $baslangic, $simdi) {
    global $DB;

    // Güncel varyasyon stokunu al
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid], '*', MUST_EXIST);
    $varyasyonlar = json_decode($urun->varyasyonlar, true);

    $guncel_stok = 0;
    if (isset($varyasyonlar[$renk][$beden])) {
        $guncel_stok = (int)$varyasyonlar[$renk][$beden];
    }

    // Renk ve beden için tüm stok hareketlerini getir
    $sql = "SELECT id, tarih, islemtipi, miktar 
            FROM {block_depo_yonetimi_stok_hareketleri} 
            WHERE urunid = :urunid 
              AND renk = :renk 
              AND beden = :beden 
              AND tarih >= :baslangic 
            ORDER BY tarih DESC";

    $hareketler = $DB->get_records_sql($sql, [
        'urunid' => $urunid,
        'renk' => $renk,
        'beden' => $beden,
        'baslangic' => $baslangic
    ]);

    // Geriye doğru stok hesaplaması yap
    $stok_gecmisi = [];
    $stok_gecmisi[$simdi] = $guncel_stok; // Şimdiki stok

    $mevcut_stok = $guncel_stok;
    foreach ($hareketler as $hareket) {
        // Geriye doğru gidiyoruz - işlemleri tersine çevir
        if ($hareket->islemtipi === 'giris') {
            $mevcut_stok -= (int)$hareket->miktar;  // Giriş öncesi daha az stok vardı
        } else {
            $mevcut_stok += (int)$hareket->miktar;  // Çıkış öncesi daha fazla stok vardı
        }

        $stok_gecmisi[$hareket->tarih] = $mevcut_stok;
    }

    // Başlangıç değerini ekle
    if (!isset($stok_gecmisi[$baslangic])) {
        $stok_gecmisi[$baslangic] = $mevcut_stok;
    }

    return $stok_gecmisi;
}