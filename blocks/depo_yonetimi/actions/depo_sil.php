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
                            <strong>"' . htmlspecialchars($depo_adi) . '"</strong> isimli depoyu silmek üzeresiniz.
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



// 5. Silme onayı alındıysa ve ürün kontrolü
require_sesskey();

// Depo içinde ürün var mı kontrol et
$urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

if ($urun_sayisi > 0) {
    // Ürünler varsa silme ve kullanıcıya bilgi ver
    redirect(
        new moodle_url('/my'),
        'Bu depoda ' . $urun_sayisi . ' adet ürün bulunmaktadır. Lütfen önce bu ürünleri siliniz.',
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Ürün yoksa depoyu sil
$DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

redirect(
    new moodle_url('/my'),
    'Depo başarıyla silindi.',
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
