<?php
require_once('../../config.php');
require_login();

require_once($CFG->dirroot . '/blocks/depo_yonetimi/classes/form/kategori_form.php');

global $DB, $PAGE, $OUTPUT;

// URL ve context ayarları
$id = required_param('id', PARAM_INT);
$kategori = $DB->get_record('depo_yonetimi_kategoriler', ['id' => $id], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Düzenle');
$PAGE->set_heading('Kategori Düzenle');

// Form nesnesi
$form = new \block_depo_yonetimi\form\kategori_form(null, ['kategori' => $kategori]);

// İptal edildiğinde yönlendir
if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/actions/kategori_listesi.php'));
}
// Form gönderildiyse güncelle
else if ($data = $form->get_data()) {
    $guncel_kategori = new stdClass();
    $guncel_kategori->id = $kategori->id;
    $guncel_kategori->isim = $data->isim;

    $DB->update_record('depo_yonetimi_kategoriler', $guncel_kategori);

    redirect(new moodle_url('/blocks/depo_yonetimi/actions/kategori_listesi.php'), 'Kategori başarıyla güncellendi', 2);
}

// Sayfayı göster
echo $OUTPUT->header();
$form->set_data($kategori);
$form->display();
echo $OUTPUT->footer();
