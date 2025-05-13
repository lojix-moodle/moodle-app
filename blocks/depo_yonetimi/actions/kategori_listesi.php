<?php
require_once('../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_listesi.php'));
$PAGE->set_title('Kategoriler');
$PAGE->set_heading('Kategori Listesi');

// Kategorileri veritabanından çek
$kategoriler = $DB->get_records('depo_yonetimi_kategoriler');

// Her kategori için sil ve düzenle linklerini hazırla
$veri = [
    'kategori_ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'),
    'kategoriler' => []
];

foreach ($kategoriler as $kategori) {
    $veri['kategoriler'][] = [
        'id' => $kategori->id,
        'isim' => $kategori->isim,
        'duzenle_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['id' => $kategori->id]),
        'sil_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', ['id' => $kategori->id])
    ];
}

// Sayfayı göster
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_depo_yonetimi/kategori_tablo', $veri);
echo $OUTPUT->footer();
