<?php
require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Depo Silme');
$PAGE->set_heading('Depo Silme');

$depoid = required_param('depoid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_capability('block/depo_yonetimi:viewall', context_system::instance());

// Depo var mı kontrol et
if (!$DB->record_exists('block_depo_yonetimi_depolar', ['id' => $depoid])) {
    throw new moodle_exception('invaliddepoid', 'block_depo_yonetimi');
}

// Depoda ürün var mı kontrol et
$urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

if ($urun_sayisi > 0) {
    echo $OUTPUT->header();

    // Uyarı kartı
    echo '
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card border-0 shadow">
                    <div class="card-header bg-warning text-dark py-3">
                        <h4 class="m-0 d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-3"></i>
                            Depo Silinemedi
                        </h4>
                    </div>
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3 d-inline-flex mb-2">
                                <i class="fas fa-box text-warning" style="font-size: 2.5rem;"></i>
                            </div>
                            <h4 class="fw-bold mt-3">Depoda Ürünler Mevcut</h4>
                        </div>

                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 mb-4">
                            Bu depoda ' . $urun_sayisi . ' adet ürün bulunmaktadır.
                        </div>

                        <p class="text-muted mb-4">
                            Depoyu silebilmek için öncelikle içindeki tüm ürünleri silmeniz gerekmektedir.
                        </p>

                        <div class="d-grid gap-3">
                            <a href="' . new moodle_url('/blocks/depo_yonetimi/index.php', ['depo' => $depoid]) . '" 
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-box me-2"></i>Depo Ürünlerini Görüntüle
                            </a>
                            <a href="' . new moodle_url('/blocks/depo_yonetimi/index.php') . '" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Geri Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo $OUTPUT->footer();
    exit;
}

// Eğer ürün yoksa normal silme işlemine devam et
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

    // Silme onay kartı...
    // (Mevcut onay kartı kodu buraya)

    echo $OUTPUT->footer();
    exit;
}

require_sesskey();

$DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

redirect(
    new moodle_url('/my'),
    'Depo başarıyla silindi.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);