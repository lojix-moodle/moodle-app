<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametre al
$depoid = required_param('depoid', PARAM_INT);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Depo Düzenle');
$PAGE->set_heading('Depo Düzenle');

// Yetki kontrolü
require_capability('block/depo_yonetimi:viewall', context_system::instance());

// Depo var mı kontrol et
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
if (!$depo) {
    print_error('Depo bulunamadı.');
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $yeni_ad = required_param('name', PARAM_TEXT);

    $depo->name = $yeni_ad;
    $DB->update_record('block_depo_yonetimi_depolar', $depo);

    redirect(new moodle_url('/my'), 'Depo başarıyla güncellendi.', 2);
}

// Formu gösterelim
echo $OUTPUT->header();
echo html_writer::start_div('container mt-4');
echo html_writer::start_div('card border-info');
echo html_writer::start_div('card-header bg-info text-white');
echo html_writer::tag('h4', 'Depo Bilgilerini Düzenle', ['class' => 'mb-0']);
echo html_writer::end_div(); // card-header

echo html_writer::start_div('card-body');
echo '<form method="POST" class="needs-validation" novalidate>';
echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Depo Adı', ['for' => 'name', 'class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'name',
    'id' => 'name',
    'class' => 'form-control form-control-lg',
    'value' => s($depo->name),
    'required' => 'required'
]);
echo html_writer::end_div(); // form-group

echo html_writer::start_div('d-flex justify-content-between align-items-center mt-4');
echo html_writer::tag(
    'button',
    'Kaydet',
    [
        'type' => 'submit',
        'class' => 'btn btn-success btn-lg px-4'
    ]
);
echo html_writer::link(
    new moodle_url('/my'),
    '<i class="fas fa-arrow-left mr-2"></i> Paneline Dön',
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div(); // d-flex

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey()
]);
echo '</form>';
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // container

echo $OUTPUT->footer();
