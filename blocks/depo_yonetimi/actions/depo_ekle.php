<?php
require('../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $name = required_param('name', PARAM_TEXT);

    $depo = new stdClass();
    $depo->name = $name;

    $DB->insert_record('block_depo_yonetimi_depolar', $depo);

    redirect(new moodle_url('/my'), 'Depo başarıyla eklendi.');
}

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Yeni Depo Ekle');

echo '<form method="POST">';
echo '<label>Depo Adı:</label><br>';
echo '<input type="text" name="name" required><br><br>';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<input type="submit" value="Ekle">';
echo '</form>';

echo html_writer::link(new moodle_url('/my'), '← Geri Dön');
echo $OUTPUT->footer();
?>
