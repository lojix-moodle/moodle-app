<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Diziyi temizler ve güvenli hale getirir
 *
 * @param array $array Temizlenecek dizi
 * @param int $paramtype PARAM_ sabiti
 * @return array Temizlenmiş dizi
 *
 *
 */

global $DB;
//function clean_array($array, $paramtype = PARAM_TEXT) {
//    $result = [];
//
//    if (!is_array($array)) {
//        return $result;
//    }
//
//    foreach ($array as $key => $value) {
//        if (is_string($value)) {
//            // String değerleri güvenli hale getir
//            $result[$key] = clean_param($value, $paramtype);
//        } else if (is_array($value)) {
//            // İç içe diziler için fonksiyonu tekrar çağır
//            $result[$key] = clean_array($value, $paramtype);
//        } else {
//            $result[$key] = $value;
//        }
//    }


//
//    return $result;
//}


/**
 * Belirli bir ürün ve depoya ait stok hareketlerini getirir
 *
 * @param int $urunid Ürün ID
 * @param int $depoid Depo ID
 * @param int $limit Sonuç sayısı limiti (0=limitsiz)
 * @return array Stok hareketleri listesi
 */
function block_depo_yonetimi_stok_hareketleri_getir($urunid, $depoid, $limit = 0) {
    global $DB;

    // Parametrelerin geçerliliğini kontrol et
    $urunid = (int)$urunid;
    $depoid = (int)$depoid;
    $limit = (int)$limit;

    if ($urunid <= 0 || $depoid <= 0) {
        error_log("Geçersiz parametreler: urunid=$urunid, depoid=$depoid");
        return array();
    }

    try {
        $sql = "SELECT sh.*, sh.islemtipi as hareket_tipi, u.firstname, u.lastname, ur.name as urun_adi
        FROM {block_depo_yonetimi_stok_hareketleri} sh
        INNER JOIN {user} u ON sh.userid = u.id
        INNER JOIN {block_depo_yonetimi_urunler} ur ON ur.id = sh.urunid
        WHERE sh.urunid = :urunid AND ur.depoid = :depoid
        ORDER BY sh.tarih DESC";

        $params = ['urunid' => $urunid, 'depoid' => $depoid];
        $sonuc = $DB->get_records_sql($sql, $params, 0, $limit);

        if (empty($sonuc)) {
            error_log("Stok hareketleri bulunamadı: urunid=$urunid, depoid=$depoid");
        }

        return $sonuc;
    } catch (Exception $e) {
        error_log('Stok hareketleri sorgusu hatası: ' . $e->getMessage());
        return array();
    }
}

/**
 * Renk adına göre hex kodunu döndürür
 *
 * @param string $colorName Renk adı
 * @return string Renk hex kodu
 */
function getColorHex($colorName) {
    $colorMap = [
        'kirmizi' => '#dc3545',
        'mavi' => '#0d6efd',
        'siyah' => '#212529',
        'beyaz' => '#f8f9fa',
        'yesil' => '#198754',
        'sari' => '#ffc107',
        'turuncu' => '#fd7e14',
        'mor' => '#6f42c1',
        'pembe' => '#d63384',
        'gri' => '#6c757d',
        'bej' => '#E4DAD2',
        'lacivert' => '#11098A',
        'kahverengi' => '#8B4513',
        'haki' => '#8A9A5B',
        'vizon' => '#A89F91',
        'bordo' => '#800000'
    ];

    return isset($colorMap[$colorName]) ? $colorMap[$colorName] : '#6c757d';
}

/**
 * Stok hareketini kaydeder ve ürün miktarını günceller
 *
 * @param int $urunid Ürün ID
 * @param int $depoid Depo ID
 * @param int $miktar Hareket miktarı
 * @param string $hareket_tipi Hareket tipi (giris/cikis)
 * @param string $aciklama Hareket açıklaması
 * @param string $renk Ürün rengi (opsiyonel)
 * @param string $beden Ürün bedeni (opsiyonel)
 * @return bool İşlem başarılı mı?
 */
function block_depo_yonetimi_stok_hareketi_kaydet($urunid, $depoid, $miktar, $hareket_tipi, $aciklama = '', $renk = '', $beden = '') {
    global $DB, $USER;

    // Hata kontrolü
    if ($miktar <= 0 || !in_array($hareket_tipi, ['giris', 'cikis'])) {
        return false;
    }

    // Ürünü kontrol et
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if (!$urun) {
        return false;
    }

    // Stok çıkışında yeteri kadar stok var mı kontrol et
    if ($hareket_tipi === 'cikis' && $urun->adet < $miktar) {
        return false; // Yetersiz stok
    }

    // Transaction başlat
    $transaction = $DB->start_delegated_transaction();

    try {
        // Stok hareketi kaydını oluştur
        $hareket = new stdClass();
        $hareket->urunid = $urunid;
        $hareket->depoid = $depoid;
        $hareket->miktar = $miktar;
        $hareket->islemtipi = $hareket_tipi;
        $hareket->aciklama = $aciklama;
        $hareket->renk = $renk;
        $hareket->beden = $beden;
        $hareket->tarih = time();
        $hareket->userid = $USER->id;

        $DB->insert_record('block_depo_yonetimi_stok_hareketleri', $hareket);

        // Ürün stok miktarını güncelle
        if ($hareket_tipi === 'giris') {
            $urun->adet += $miktar;
        } else {
            $urun->adet -= $miktar;
        }

        $DB->update_record('block_depo_yonetimi_urunler', $urun);

        // Transaction'ı tamamla
        $transaction->allow_commit();

        return true;
    } catch (Exception $e) {
        $transaction->rollback($e);
        return false;
    }
}