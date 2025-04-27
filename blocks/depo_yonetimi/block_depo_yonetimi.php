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
    // Onay URL'lerini oluştur
    $yesurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', [
        'depoid' => $depoid,
        'confirm' => 1,
        'sesskey' => sesskey() // CSRF koruması
    ]);
    $nourl = new moodle_url('/my'); // Geri dönüş URL'si

    // Sayfayı render et
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        "'" . $DB->get_field('block_depo_yonetimi_depolar', 'name', ['id' => $depoid]) . "' deposunu silmek istediğinize emin misiniz?",
        $yesurl,
        $nourl
    );
    echo $OUTPUT->footer();
    exit;
}

// 5. Onaylandıysa silme işlemi
require_sesskey(); // Güvenlik anahtarı kontrolü

// Depoyu sil
$DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

// Başarı mesajıyla yönlendir
redirect(
    new moodle_url('/my'),
    'Depo başarıyla silindi.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);