<?php

require_once(__DIR__ . '/../../config.php');
require_login();

$PAGE->set_url('/blocks/depo_yonetimi/home.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title("Ana Sayfa");
$PAGE->set_heading("Ana Sayfa");
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/homestyle.css', ['v' => time()]));
$PAGE->requires->js_call_amd('core/bootstrap', 'init');

echo $OUTPUT->header();

echo '
<div class="container">
    <div class="row">
        <div class="col">
            <div class="box depo"><a href="/blocks/depo_yonetimi/actions/depolar.php">Depolar</a></div>
        </div>
        <div class="col">
            <div class="box urun">Ürünler</div>
        </div>
        <div class="col">
            <div class="box satis">Satışlar</div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="box stok">Stok</div>
        </div>
        <div class="col">
            <div class="box talep">Talep</div>
        </div>
        <div class="col">
            <!-- Boş kutu -->
        </div>
    </div>
</div>
';

echo $OUTPUT->footer();
