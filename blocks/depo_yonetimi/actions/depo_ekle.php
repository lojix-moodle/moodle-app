<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
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

// ... önceki kodlar ...

echo $OUTPUT->header();
echo html_writer::start_div('container mt-4');
echo html_writer::start_div('card border-primary');
echo html_writer::start_div('card-header bg-primary text-white');
echo html_writer::tag('h4', 'Yeni Depo Oluştur', ['class' => 'mb-0']);
echo html_writer::end_div(); // card-header

echo html_writer::start_div('card-body');
echo '<form method="POST" class="needs-validation" novalidate>';
echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Depo Adı', ['class' => 'font-weight-bold']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'name',
    'class' => 'form-control form-control-lg',
    'placeholder' => 'Örnek: Merkez Depo',
    'required' => 'required'
]);
echo html_writer::end_div(); // form-group

echo html_writer::start_div('d-flex justify-content-between align-items-center mt-4');
echo html_writer::tag(
    'button',
    'Depo Ekle',
    [
        'type' => 'submit',
        'class' => 'btn btn-primary btn-lg px-4'
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
