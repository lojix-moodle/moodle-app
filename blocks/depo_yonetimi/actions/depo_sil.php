<?php
require_once(__DIR__ . '/../../../config.php');

require_login();

// 1. Parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// 2. Kullanıcı yetkili mi kontrol et
require_capability('block/depo_yonetimi:viewall', context_system::instance());

// 3. Depo bilgisi var mı?
if (!$DB->record_exists('block_depo_yonetimi_depolar', ['id' => $depoid])) {
    print_error('invaliddepoid', 'block_depo_yonetimi');
}

// 4. Eğer onaylanmadıysa, onay ekranı göster
if (!$confirm) {
    $yesurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depoid, 'confirm' => 1, 'sesskey' => sesskey()]);
    $nourl = new moodle_url('/my'); // veya depoların listelendiği sayfa

    echo $OUTPUT->header();
    echo $OUTPUT->confirm('Bu depoyu silmek istediğinize emin misiniz?', $yesurl, $nourl);
    echo $OUTPUT->footer();
    exit;
}

// 5. Sesskey kontrolü
require_sesskey();

// 6. Depoyu sil
$DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

// 7. O depodaki ürünleri de sil (istersen)
// $DB->delete_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

// 8. Bitince yönlendir
redirect(new moodle_url('/my'), 'Depo başarıyla silindi.');
