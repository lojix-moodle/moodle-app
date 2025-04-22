<?php

require('../../config.php');
require_once(__DIR__ . '/form/urun_form.php');
require_once(__DIR__ . '/classes/urun_manager.php');

use block_depo_yonetimi\form\urun_form;
use block_depo_yonetimi\urun_manager;

require_login();

$depoid = required_param('depoid', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url('/blocks/depo_yonetimi/urunmanager.php', ['depoid' => $depoid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading('Ürün İşlemleri');

if ($action == 'delete' && $id) {
    urun_manager::delete_product($depoid, $id);
    redirect(new moodle_url('/blocks/depo_yonetimi/urunmanager.php', ['depoid' => $depoid]));
}

$product = ($id) ? urun_manager::get_product($depoid, $id) : ['id' => 0, 'name' => '', 'adet' => 1];
$form = new urun_form(null, ['depoid' => $depoid]);
$form->set_data($product + ['depoid' => $depoid]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/urunmanager.php', ['depoid' => $depoid]));
} elseif ($data = $form->get_data()) {
    if ($data->id) {
        urun_manager::update_product($depoid, (array)$data);
    } else {
        urun_manager::add_product($depoid, (array)$data);
    }
    redirect(new moodle_url('/blocks/depo_yonetimi/urunmanager.php', ['depoid' => $depoid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Ürün Yönetimi');
$form->display();

// Ürün listesi
$urunler = urun_manager::get_products($depoid);
echo html_writer::start_tag('ul');
foreach ($urunler as $urun) {
    $editurl = new moodle_url('/blocks/depo_yonetimi/urunmanager.php', ['depoid' => $depoid, 'id' => $urun['id']]);
    $deleteurl = new moodle_url('/blocks/depo_yonetimi/urunmanager.php', ['depoid' => $depoid, 'action' => 'delete', 'id' => $urun['id']]);
    echo html_writer::tag('li', "{$urun['name']} ({$urun['adet']}) " .
        html_writer::link($editurl, '[Düzenle]') . ' ' .
        html_writer::link($deleteurl, '[Sil]'));
}
echo html_writer::end_tag('ul');

echo $OUTPUT->footer();
