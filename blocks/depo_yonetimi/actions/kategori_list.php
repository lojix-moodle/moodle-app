<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
global $DB, $PAGE, $OUTPUT;

$PAGE->set_title('Ürün Ekle');
$PAGE->set_heading('Ürün Ekle');

$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');


$templatecontext = [
    'kategoriler' => [],
    'back_url' => $PAGE->url->out(false),
    'kategori_ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'),
];

foreach ($kategoriler as $index => $kategori) {

    $templatecontext['kategoriler'][] = [
        'name' => $kategori->name,
        'duzenle_url' => (new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', [
            'kategoriid' => $kategori->id
        ]))->out(false),
        'sil_url' => (new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', [
            'kategoriid' => $kategori->id
        ]))->out(false),

    ];
}


return $OUTPUT->render_from_template('block_depo_yonetimi/kategori_tablo', $templatecontext);


?>