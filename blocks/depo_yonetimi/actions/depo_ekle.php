<?php
require('../../config.php');

require_login();


$actionurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php');

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $adet = required_param('adet', PARAM_INT);

    // Kayıt ekle
    $depo = new stdClass();
    $depo->name = $name;

    $DB->insert_record('block_depo_yonetimi_urunler', $depo);

    redirect(new moodle_url('/my'), 'Depo başarıyla eklendi.');
}

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Depo Ekle');

echo '<form method="POST">';
echo '<label>Depo Adı:</label><br>';
echo '<input type="text" name="name" required><br><br>';


echo '<input type="submit" value="Ekle">';
echo '</form>';

echo html_writer::link(new moodle_url('/my'), '← Geri Dön');
echo $OUTPUT->footer();
