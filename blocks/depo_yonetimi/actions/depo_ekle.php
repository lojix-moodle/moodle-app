<?php

require('../../../../config.php'); // config.php'ye doğru yolu ver
require_login();

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');

global $DB, $OUTPUT;

// Form gönderildiyse
if (optional_param('submit', null, PARAM_TEXT)) {
    require_sesskey(); // Güvenlik için

    $name = required_param('name', PARAM_TEXT);

    $depo = new stdClass();
    $depo->name = $name;

    $DB->insert_record('block_depo_yonetimi_depolar', $depo);

    redirect(new moodle_url('/my'), 'Depo başarıyla eklendi.');
}

// Sayfa başı
echo $OUTPUT->header();

// Form
echo '<h2>Yeni Depo Ekle</h2>';
echo '<form method="POST">';
echo '<label>Depo Adı:</label><br>';
echo '<input type="text" name="name" required><br><br>';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<input type="submit" name="submit" value="Depo Ekle" class="btn btn-primary">';
echo '</form>';

echo '<br><a href="' . new moodle_url('/my') . '">← Geri Dön</a>';

// Sayfa sonu
echo $OUTPUT->footer();
?>
