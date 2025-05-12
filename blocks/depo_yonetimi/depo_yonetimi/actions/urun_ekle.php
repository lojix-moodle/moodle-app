<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT;

$depoid = required_param('depoid', PARAM_INT);

// BU İKİ SATIR ŞART!!!
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance()); // veya doğru context (örneğin depo context'i varsa)

$PAGE->set_title('Ürün Ekle');
$PAGE->set_heading('Ürün Ekle');

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
