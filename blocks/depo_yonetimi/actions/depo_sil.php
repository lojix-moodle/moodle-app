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
    $ana_sayfa_url = new moodle_url('/my');

    $yesurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', [
        'depoid' => $depoid,
        'confirm' => 1,
        'sesskey' => sesskey()
    ]);

    $nourl = $ana_sayfa_url;

    echo $OUTPUT->header();

    // Profesyonel onay kartı
    echo '
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h4 class="m-0"><i class="fas fa-exclamation-triangle me-2"></i>Depo Silme Onayı</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-center mb-4">
                            <strong>"' . htmlspecialchars($depo_adi) . '"</strong> deposunu silmek istediğinize emin misiniz?
                        </h5>
                        <p class="text-muted text-center mb-4">
                            Bu işlem geri alınamaz ve depodaki tüm ürünler de silinecektir.
                        </p>
                        
                        <div class="d-flex justify-content-center gap-3">
                            <a href="' . $nourl . '" class="btn btn-lg btn-outline-secondary px-4">
                                <i class="fas fa-times me-2"></i>Vazgeç
                            </a>
                            <a href="' . $yesurl . '" class="btn btn-lg btn-danger px-4">
                                <i class="fas fa-check me-2"></i>Evet, Sil
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="' . $ana_sayfa_url . '" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </div>';

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