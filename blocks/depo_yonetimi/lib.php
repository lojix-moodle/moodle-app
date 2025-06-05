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

    $sql = "SELECT sh.*, u.firstname, u.lastname 
            FROM {block_depo_yonetimi_stok_hareketleri} sh
            JOIN {user} u ON sh.userid = u.id
            WHERE sh.urunid = :urunid AND sh.depoid = :depoid
            ORDER BY sh.tarih DESC";

    return $DB->get_records_sql($sql, ['urunid' => $urunid, 'depoid' => $depoid], 0, $limit);
}