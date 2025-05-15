<?php
require_once('../../../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php'));
$PAGE->set_title('Depo Silme');
$PAGE->set_heading('Depo Silme');

require_capability('block/depo_yonetimi:manage', context_system::instance());

$depoid = required_param('depoid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Ana sayfa URL'i
$return_url = new moodle_url('/blocks/depo_yonetimi/index.php');

if (!$DB->record_exists('block_depo_yonetimi_depolar', ['id' => $depoid])) {
    redirect($return_url, 'Geçersiz depo ID\'si.', null, \core\output\notification::NOTIFY_ERROR);
}

if (!$confirm) {
    $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

    $yesurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', [
        'depoid' => $depoid,
        'confirm' => 1,
        'sesskey' => sesskey()
    ]);
    $nourl = $return_url;

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        "\"$depo->name\" deposunu silmek istediğinizden emin misiniz?",
        $yesurl,
        $nourl
    );

    // Geliştirilmiş profesyonel onay kartı
    echo '
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card border-0 shadow">
                    <div class="card-header bg-danger text-white py-3">
                        <h4 class="m-0 d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3"></i>
                            Depo Silme İşlemi
                        </h4>
                    </div>
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3 d-inline-flex mb-2">
                                <i class="fas fa-trash-alt text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                            <h4 class="fw-bold mt-3">Silme Onayı</h4>
                        </div>

                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 mb-4">
                            <strong>"' . htmlspecialchars($depo->name) . '"</strong> isimli depoyu silmek üzeresiniz.
                        </div>

                        <p class="text-muted mb-4">
                            Bu işlem kalıcıdır ve geri alınamaz. Depo ile ilişkili tüm ürün verileri ve kayıtlar sistemden tamamen silinecektir.
                        </p>

                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-between">
                            <a href="' . $nourl . '" class="btn btn-lg btn-outline-secondary flex-grow-1" style="min-width: 140px;">
                                <i class="fas fa-times me-2"></i>İptal
                            </a>
                            <a href="' . $yesurl . '" class="btn btn-lg btn-danger flex-grow-1" style="min-width: 140px;">
                                <i class="fas fa-check me-2"></i>Sil
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

require_sesskey();

// Depo içinde ürün kontrolü
if ($DB->record_exists('block_depo_yonetimi_urunler', ['depoid' => $depoid])) {
    redirect(
        $return_url,
        'Depo silinemedi. Bu depoda ürünler mevcut. Önce ürünleri başka bir depoya taşıyın veya silin.',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Depoyu sil
$DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

redirect(
    $return_url,
    'Depo başarıyla silindi.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);