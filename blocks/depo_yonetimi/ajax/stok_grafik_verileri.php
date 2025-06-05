<?php
// Hata ayıklama için
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Çıktı tamponlama başlat (header hatası olmaması için)
ob_start();

require_once('../../../config.php');
global $DB, $CFG;

try {
    // Parametre kontrolü
    $depoid = required_param('depoid', PARAM_INT);
    $gun = optional_param('gun', 30, PARAM_INT);
    $urunid = optional_param('urunid', 0, PARAM_INT);
    $tip = optional_param('tip', 'hareketler', PARAM_ALPHA); // 'hareketler' veya 'stokseviye'

    // Güvenlik kontrolü
    require_login();

    // Tarih hesaplama
    $simdi = time();
    $baslangic = $simdi - ($gun * 86400);
    $sonuc = [];

    // Stok seviyesi grafiği
    if ($tip === 'stokseviye' && $urunid > 0) {
        // Mevcut stok durumunu al
        $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid], '*', MUST_EXIST);
        $guncel_stok = (int)$urun->adet;

        // Tüm hareketleri kronolojik sırayla getir (yeniden eskiye)
        $sql = "SELECT id, tarih, islemtipi, miktar 
                FROM {block_depo_yonetimi_stok_hareketleri} 
                WHERE urunid = :urunid AND tarih >= :baslangic 
                ORDER BY tarih DESC";

        $hareketler = $DB->get_records_sql($sql, [
            'urunid' => $urunid,
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
            // Hesaplanan son değeri başlangıç noktası olarak kullan
            $stok_gecmisi[$baslangic] = $mevcut_stok;
        }

        // Tarihe göre artan şekilde sırala (eskiden yeniye)
        ksort($stok_gecmisi);

        // Veri formatını hazırla
        $sonuc = [
            'labels' => [],
            'stokSeviyesi' => [],
            'success' => true,
            'count' => count($stok_gecmisi)
        ];

        foreach ($stok_gecmisi as $tarih => $miktar) {
            $sonuc['labels'][] = date('d.m.Y H:i', $tarih);
            $sonuc['stokSeviyesi'][] = $miktar;
        }
    }
    // Gün bazlı giriş/çıkış grafik verisi (eski formatı da koru)
    else {
        $params = ['depoid' => $depoid, 'baslangic' => $baslangic];
        $urunFilter = '';

        if ($urunid > 0) {
            $urunFilter = 'AND sh.urunid = :urunid';
            $params['urunid'] = $urunid;
        }

        $sql = "SELECT DATE_FORMAT(FROM_UNIXTIME(sh.tarih), '%d.%m.%Y') as tarih_gun,
                SUM(CASE WHEN sh.islemtipi = 'giris' THEN sh.miktar ELSE 0 END) as toplam_giris,
                SUM(CASE WHEN sh.islemtipi = 'cikis' THEN sh.miktar ELSE 0 END) as toplam_cikis
                FROM {block_depo_yonetimi_stok_hareketleri} sh
                JOIN {block_depo_yonetimi_urunler} ur ON ur.id = sh.urunid
                WHERE ur.depoid = :depoid AND sh.tarih >= :baslangic $urunFilter
                GROUP BY DATE_FORMAT(FROM_UNIXTIME(sh.tarih), '%d.%m.%Y')
                ORDER BY MIN(sh.tarih) ASC";

        $hareketler = $DB->get_records_sql($sql, $params);

        // Tüm günleri doldur
        $tarihler = [];
        $girisler = [];
        $cikislar = [];

        for ($i = $gun; $i >= 0; $i--) {
            $tarih = date('d.m.Y', $simdi - ($i * 86400));
            $tarihler[$tarih] = $tarih;
            $girisler[$tarih] = 0;
            $cikislar[$tarih] = 0;
        }

        foreach ($hareketler as $hareket) {
            if (isset($tarihler[$hareket->tarih_gun])) {
                $girisler[$hareket->tarih_gun] = (int)$hareket->toplam_giris;
                $cikislar[$hareket->tarih_gun] = (int)$hareket->toplam_cikis;
            }
        }

        $sonuc = [
            'labels' => array_values($tarihler),
            'girisler' => array_values($girisler),
            'cikislar' => array_values($cikislar),
            'success' => true
        ];
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
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}