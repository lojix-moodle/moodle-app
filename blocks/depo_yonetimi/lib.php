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


defined('MOODLE_INTERNAL') || die();

/**
 * Stok hareketi kaydeder ve ürün miktarını günceller
 *
 * @param int $urunid Ürün ID
 * @param int $depoid Depo ID
 * @param int $miktar Miktar (pozitif değer)
 * @param string $hareket_tipi 'giris' veya 'cikis'
 * @param string $aciklama İşlem açıklaması
 * @param string $renk Ürün rengi (varsa)
 * @param string $beden Ürün bedeni (varsa)
 * @return int|bool Başarılı ise hareket ID'si, değilse false
 */
function block_depo_yonetimi_stok_hareketi_kaydet($urunid, $depoid, $miktar, $hareket_tipi, $aciklama = '', $renk = null, $beden = null)
{
    global $DB, $USER;

    // Miktar pozitif olmalı
    $miktar = abs($miktar);

    // Ürün ve depo kontrolü
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if (!$urun) {
        return false;
    }

    // Stok miktarını güncelle
    if ($hareket_tipi == 'giris') {
        // Stok girişi (ekleme)
        $urun->adet += $miktar;
    } else if ($hareket_tipi == 'cikis') {
        // Stok çıkışı (azaltma) - yetersiz stok kontrolü
        if ($urun->adet < $miktar) {
            return false; // Yetersiz stok
        }
        $urun->adet -= $miktar;
    } else {
        return false; // Geçersiz hareket tipi
    }

    // Ürün stoğunu güncelle
    $DB->update_record('block_depo_yonetimi_urunler', $urun);

    // Stok hareketini kaydet
    $hareket = new stdClass();
    $hareket->urunid = $urunid;
    $hareket->depoid = $depoid;
    $hareket->miktar = $miktar;
    $hareket->hareket_tipi = $hareket_tipi;
    $hareket->aciklama = $aciklama;
    $hareket->tarih = time();
    $hareket->userid = $USER->id;
    $hareket->renk = $renk;
    $hareket->beden = $beden;

    $hareket_id = $DB->insert_record('block_depo_yonetimi_stok_hareketleri', $hareket);

    return $hareket_id;
}

/**
 * Ürünün stok hareketlerini getirir
 *
 * @param int $urunid Ürün ID
 * @param int $depoid Depo ID
 * @param int $limit Kayıt limiti
 * @return array Stok hareketleri
 */
function block_depo_yonetimi_stok_hareketleri_getir($urunid, $depoid, $limit = 10)
{
    global $DB;

    $sql = "SELECT sh.*, u.firstname, u.lastname, ur.name as urun_adi
            FROM {block_depo_yonetimi_stok_hareketleri} sh
            JOIN {user} u ON u.id = sh.userid
            JOIN {block_depo_yonetimi_urunler} ur ON ur.id = sh.urunid
            WHERE sh.urunid = :urunid AND sh.depoid = :depoid
            ORDER BY sh.tarih DESC";

    return $DB->get_records_sql($sql, ['urunid' => $urunid, 'depoid' => $depoid], 0, $limit);
}

/**
 * Ürünün son stok hareketini getirir
 *
 * @param int $urunid Ürün ID
 * @param int $depoid Depo ID
 * @return object|false Son stok hareketi veya false
 */
function block_depo_yonetimi_son_stok_hareketi_getir($urunid, $depoid)
{
    global $DB;

    $sql = "SELECT * FROM {block_depo_yonetimi_stok_hareketleri}
            WHERE urunid = :urunid AND depoid = :depoid
            ORDER BY tarih DESC LIMIT 1";

    $records = $DB->get_records_sql($sql, ['urunid' => $urunid, 'depoid' => $depoid], 0, 1);

    return reset($records);
}