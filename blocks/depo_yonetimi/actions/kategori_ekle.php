<?php
require_once('../../config.php');
require_login();

require_once($CFG->dirroot . '/blocks/depo_yonetimi/classes/form/kategori_form.php');

global $DB, $PAGE, $OUTPUT;

$url = new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Ekle');
$PAGE->set_heading('Kategori Ekle');

$form = new \block_depo_yonetimi\form\kategori_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/block_depo_yonetimi.php'));
} else if ($data = $form->get_data()) {
    $yeni_kategori = new stdClass();
    $yeni_kategori->isim = $data->isim;

    $DB->insert_record('depo_yonetimi_kategoriler', $yeni_kategori);

    redirect(new moodle_url('/blocks/depo_yonetimi/block_depo_yonetimi.php'), 'Kategori başarıyla eklendi', 2);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
