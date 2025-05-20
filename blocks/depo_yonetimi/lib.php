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

// Stok hareketleri tablosu
$table = new xmldb_table('block_depo_yonetimi_stok_hareketleri');

$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table->add_field('urunid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$table->add_field('renk', XMLDB_TYPE_CHAR, '50', null, null, null, null);
$table->add_field('beden', XMLDB_TYPE_CHAR, '50', null, null, null, null);
$table->add_field('miktar', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null); // Pozitif: giriş, Negatif: çıkış
$table->add_field('aciklama', XMLDB_TYPE_TEXT, null, null, null, null, null);
$table->add_field('islemtipi', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null); // 'giris', 'cikis', 'duzeltme'
$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$table->add_field('tarih', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

// Tablo oluşturma
if (!$DB->table_exists($table->getName())) {
    $dbman = $DB->get_manager();
    $dbman->create_table($table);
}
//
//    return $result;
//}
