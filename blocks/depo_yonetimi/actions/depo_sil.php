<?php
require_once(__DIR__ . '/../../../config.php');

require_login(); // Kullanıcı giriş kontrolü
global $DB, $PAGE, $OUTPUT;

// Sayfa ayarlarını EN BAŞTA yapıyoruz
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php'));
$PAGE->set_context(context_system::instance()); // Context hatasını çözer
$PAGE->set_title('Depo Silme');
$PAGE->set_heading('Depo Silme');

// 1. Parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// 2. Yetki kontrolü
require_capability('block/depo_yonetimi:viewall', context_system::instance());

// 3. Depo var mı kontrol et
if (!$DB->record_exists('block_depo_yonetimi_depolar', ['id' => $depoid])) {
    throw new moodle_exception('invaliddepoid', 'block_depo_yonetimi');
}

// 4. Onay ekranı göster
if (!$confirm) {
    $depo_adi = $DB->get_field('block_depo_yonetimi_depolar', 'name', ['id' => $depoid]);

    $yesurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', [
        'depoid' => $depoid,
        'confirm' => 1,
        'sesskey' => sesskey()
    ]);
    $nourl = new moodle_url('/my');
    $duzenleurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', [
        'depoid' => $depoid
    ]);

    echo $OUTPUT->header();

    echo html_writer::tag('h3', "'{$depo_adi}' deposunu silmek istediğinize emin misiniz?", ['class' => 'mb-4']);

    echo html_writer::start_div('d-flex flex-column gap-2');

    // Silme onayı butonları
    echo html_writer::link($yesurl, 'Evet, Sil', ['class' => 'btn btn-danger mb-2']);
    echo html_writer::link($nourl, 'Hayır, Vazgeç', ['class' => 'btn btn-secondary mb-2']);

    // ✅ Depo düzenleme butonu
    echo html_writer::link($duzenleurl, 'Depo Bilgilerini Düzenle', ['class' => 'btn btn-info']);

    echo html_writer::end_div();

    echo $OUTPUT->footer();
    exit;
}

// 5. Silme onayı alındıysa depo sil
require_sesskey();

$DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

redirect(
    new moodle_url('/my'),
    'Depo başarıyla silindi.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
