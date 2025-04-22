<?php
require('../../config.php');

$depoid = required_param('depoid', PARAM_INT);
$actionurl = new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]);

// Sahte veri deposu (örnek amaçlı)
$urunler_json = $CFG->dataroot . '/urunler.json';
if (!file_exists($urunler_json)) {
    file_put_contents($urunler_json, json_encode([]));
}
$urunler = json_decode(file_get_contents($urunler_json), true);

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $adet = required_param('adet', PARAM_INT);

    if (!isset($urunler[$depoid])) {
        $urunler[$depoid] = [];
    }

    $urunler[$depoid][] = [
        'name' => $name,
        'adet' => $adet,
    ];

    file_put_contents($urunler_json, json_encode($urunler));
    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün eklendi');
}

// Sayfa çıktısı
echo $OUTPUT->header();
echo html_writer::tag('h2', 'Ürün Ekle');

echo '<form method="POST">';
echo '<label>Ürün Adı:</label><br>';
echo '<input type="text" name="name" required><br><br>';

echo '<label>Adet:</label><br>';
echo '<input type="number" name="adet" required><br><br>';

echo '<input type="submit" value="Ekle">';
echo '</form>';

echo html_writer::link(new moodle_url('/my', ['depo' => $depoid]), '← Geri Dön');
echo $OUTPUT->footer();
