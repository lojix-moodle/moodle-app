<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

// Gelen parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT); // BURASI DÜZELDİ! index DEĞİL urunid

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Sil');
$PAGE->set_heading('Ürün Sil');

// Yetki kontrolü (isteğe bağlı yapabilirsin)
if (!has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
    print_error('Erişim izniniz yok.');
}

// Ürün var mı kontrol et
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
if (!$urun) {
    print_error('Ürün bulunamadı.');
}

// Ürünü sil
$DB->delete_records('block_depo_yonetimi_urunler', ['id' => $urunid]);

// Başarıyla silindi, geri dön
redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla silindi.', 2);
?>
