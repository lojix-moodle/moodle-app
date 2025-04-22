<?php
require('../../config.php');

require_login();

$depoid = required_param('depoid', PARAM_INT);
$actionurl = new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]);

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $adet = required_param('adet', PARAM_INT);

    // Kayıt ekle
    $urun = new stdClass();
    $urun->depoid = $depoid;
    $urun->name = $name;
    $urun->adet = $adet;

    $DB->insert_record('block_depo_yonetimi_urunler', $urun);

    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla eklendi.');
}

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
