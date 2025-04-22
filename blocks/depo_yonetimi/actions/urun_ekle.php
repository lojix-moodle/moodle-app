<?php

require('../../config.php');
require_once($CFG->dirroot.'/blocks/depo_yonetimi/classes/form/urun_form.php');

$depoid = required_param('depoid', PARAM_INT);
$url = new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]);

$mform = new urun_form($url->out(false));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/my', ['depo' => $depoid]));
} else if ($data = $mform->get_data()) {
    // JSON dosyası veya DB işlemleri yapılır
    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün eklendi');
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
