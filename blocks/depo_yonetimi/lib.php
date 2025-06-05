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
/**
 * Renk adına göre hex kodunu ve uygun metin rengini döndürür
 *
 * @param string $colorName Renk adı
 * @param bool $returnTextColor true ise metin rengi de döndürülür
 * @return string|array Renk hex kodu veya [hex, textColor] dizisi
 */
function getColorHex($colorName, $returnTextColor = false) {
    $colorMap = [
        'kirmizi' => ['#dc3545', '#ffffff'],
        'mavi' => ['#0d6efd', '#ffffff'],
        'siyah' => ['#212529', '#ffffff'],
        'beyaz' => ['#f8f9fa', '#212529'],
        'yesil' => ['#198754', '#ffffff'],
        'sari' => ['#ffc107', '#212529'],
        'turuncu' => ['#fd7e14', '#212529'],
        'mor' => ['#6f42c1', '#ffffff'],
        'pembe' => ['#d63384', '#ffffff'],
        'gri' => ['#6c757d', '#ffffff'],
        'bej' => ['#E4DAD2', '#212529'],
        'lacivert' => ['#11098A', '#ffffff'],
        'kahverengi' => ['#8B4513', '#ffffff'],
        'haki' => ['#8A9A5B', '#212529'],
        'vizon' => ['#A89F91', '#212529'],
        'bordo' => ['#800000', '#ffffff']
    ];

    $defaultColor = ['#6c757d', '#ffffff'];
    $colorData = isset($colorMap[$colorName]) ? $colorMap[$colorName] : $defaultColor;

    return $returnTextColor ? $colorData : $colorData[0];
}

/**
 * Stok hareketi kaydeder ve stok miktarını günceller
 *
 * @param int $urunid Ürün ID
 * @param int $depoid Depo ID
 * @param int $miktar İşlem miktarı
 * @param string $hareket_tipi İşlem tipi (giris veya cikis)
 * @param string $aciklama İşlem açıklaması
 * @param string $renk Varyasyon rengi (opsiyonel)
 * @param string $beden Varyasyon bedeni (opsiyonel)
 * @return bool İşlem sonucu
 */
function block_depo_yonetimi_stok_hareketi_kaydet($urunid, $depoid, $miktar, $hareket_tipi, $aciklama = '', $renk = '', $beden = '') {
    global $DB, $USER;

    // Ürün bilgilerini al
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if (!$urun) {
        return false;
    }

    // Çıkış işlemlerinde stok kontrolü
    if ($hareket_tipi === 'cikis') {
        // Genel stok kontrolü
        if ($miktar > $urun->adet) {
            return false;
        }

        // Varyasyon seçiliyse varyasyon stoğu kontrolü
        if (!empty($renk) && !empty($beden) && !empty($urun->varyasyonlar) && $urun->varyasyonlar !== '0') {
            $varyasyonlar = json_decode($urun->varyasyonlar, true);
            if (isset($varyasyonlar[$renk][$beden]) && $varyasyonlar[$renk][$beden] < $miktar) {
                return false; // Varyasyon stoğu yetersiz
            }
        }
    }

    // Transaction başlat
    $transaction = $DB->start_delegated_transaction();

    try {
        // Stok hareketi kaydı oluştur
        $stok_hareketi = new stdClass();
        $stok_hareketi->urunid = $urunid;
        $stok_hareketi->depoid = $depoid;
        $stok_hareketi->miktar = $miktar;
        $stok_hareketi->islemtipi = $hareket_tipi;
        $stok_hareketi->aciklama = $aciklama;
        $stok_hareketi->tarih = time();
        $stok_hareketi->userid = $USER->id;

        // Varyasyon bilgileri varsa ekle
        if (!empty($renk)) {
            $stok_hareketi->renk = $renk;
        }

        if (!empty($beden)) {
            $stok_hareketi->beden = $beden;
        }

        // Stok hareketi kaydını veritabanına ekle
        $DB->insert_record('block_depo_yonetimi_stok_hareketleri', $stok_hareketi);

        // Ürün stok miktarını güncelle
        if ($hareket_tipi === 'giris') {
            $urun->adet += $miktar;
        } else {
            $urun->adet -= $miktar;
        }

        // Varyasyon stoğunu güncelle
        if (!empty($renk) && !empty($beden) && !empty($urun->varyasyonlar) && $urun->varyasyonlar !== '0') {
            $varyasyonlar = json_decode($urun->varyasyonlar, true);

            if (is_array($varyasyonlar) && isset($varyasyonlar[$renk][$beden])) {
                // Stok giriş/çıkış işlemine göre varyasyon stoğunu güncelle
                if ($hareket_tipi === 'giris') {
                    $varyasyonlar[$renk][$beden] += $miktar;
                } else {
                    $varyasyonlar[$renk][$beden] -= $miktar;
                }

                // Negatif stok kontrolü
                if ($varyasyonlar[$renk][$beden] < 0) {
                    $varyasyonlar[$renk][$beden] = 0;
                }

                // Güncellenmiş varyasyon bilgisini JSON olarak kaydet
                $urun->varyasyonlar = json_encode($varyasyonlar);
            }
        }

        // Ürün kaydını güncelle
        $DB->update_record('block_depo_yonetimi_urunler', $urun);

        // Transaction'ı tamamla
        $transaction->allow_commit();

        return true;
    } catch (Exception $e) {
        // Hata durumunda transaction'ı geri al
        $transaction->rollback($e);
        return false;
    }

}