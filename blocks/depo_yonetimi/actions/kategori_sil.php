<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $USER;

// Parametreleri al
$kategoriid = required_param('kategoriid', PARAM_INT);

// Kategori var mı kontrol et
$kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $kategoriid], '*', MUST_EXIST);
$depoid = $kategori->depoid;

// Yetki kontrolü
$kullanici_depo_eslesme = [
    2 => 3,
    5 => 1,
];

if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
    (!isset($kullanici_depo_eslesme[$USER->id]) || $kullanici_depo_eslesme[$USER->id] != $depoid)) {
    print_error('Bu depoya erişim izniniz yok.');
}

// Bu kategoriye bağlı ürünleri kontrol et
$bagli_urunler = $DB->count_records('block_depo_yonetimi_urunler', ['kategori_id' => $kategoriid]);

if ($bagli_urunler > 0) {
    // Kategoriye bağlı ürünler varsa uyarı ver
    redirect(new moodle_url('/my', ['depo' => $depoid, 'view' => 'kategoriler']),
        'Bu kategoriye bağlı ' . $bagli_urunler . ' ürün var. Önce bu ürünlerin kategorisini değiştirin veya silin.',
        null,
        \core\output\notification::NOTIFY_ERROR);
} else {
    // Kategoriyi sil
    $DB->delete_records('block_depo_yonetimi_kategoriler', ['id' => $kategoriid]);

    // Başarılı mesajı ile ana sayfaya yönlendir
    redirect(new moodle_url('/my', ['depo' => $depoid, 'view' => 'kategoriler']),
        'Kategori başarıyla silindi.',
        null,
        \core\output\notification::NOTIFY_SUCCESS);
}
?>