<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametreleri al
$depoid = required_param('depoid', PARAM_INT);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Depo Düzenle');
$PAGE->set_heading('Depo Düzenle');

// Yetki kontrolü
require_capability('block/depo_yonetimi:viewall', context_system::instance());

// Depo var mı kontrol et
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Potansiyel depo sorumlularını getir (aktif kullanıcılar)
$sql = "SELECT u.id, " . $DB->sql_concat('u.firstname', "' '", 'u.lastname') . " AS fullname
        FROM {user} u
        WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
        ORDER BY u.firstname ASC, u.lastname ASC";
$users = $DB->get_records_sql($sql);

// Mevcut depo sorumlusunu al (varsa)
$sorumlu_id = $depo->sorumlu_id ?? 0;

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $yeni_ad = required_param('name', PARAM_TEXT);
    $yeni_sorumlu = optional_param('sorumlu_id', 0, PARAM_INT);

    $depo->name = $yeni_ad;
    $depo->sorumlu_id = $yeni_sorumlu;
    $DB->update_record('block_depo_yonetimi_depolar', $depo);

    redirect(new moodle_url('/my'), 'Depo başarıyla güncellendi.', null, \core\output\notification::NOTIFY_SUCCESS);
}

// CSS ve JS eklemeleri
$PAGE->requires->css_init();
$PAGE->requires->js_init();

echo $OUTPUT->header();
?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb bg-light p-3 rounded">
                        <li class="breadcrumb-item"><a href="<?php echo new moodle_url('/my'); ?>">Ana Sayfa</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo new moodle_url('/blocks/depo_yonetimi/index.php'); ?>">Depo Yönetimi</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Depo Düzenle</li>
                    </ol>
                </nav>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white p-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-warehouse me-2" style="font-size: 1.25rem;"></i>
                            <h4 class="mb-0">Depo Bilgilerini Düzenle</h4>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold">
                                    <i class="fas fa-tag me-2 text-muted"></i>Depo Adı
                                </label>
                                <input type="text" id="name" name="name" value="<?php echo s($depo->name); ?>"
                                       class="form-control form-control-lg" required>
                                <div class="invalid-feedback">Lütfen geçerli bir depo adı giriniz.</div>
                                <small class="text-muted">Depo için benzersiz bir isim belirleyin.</small>
                            </div>

                            <div class="mb-4">
                                <label for="sorumlu_id" class="form-label fw-bold">
                                    <i class="fas fa-user-shield me-2 text-muted"></i>Depo Sorumlusu
                                </label>
                                <select id="sorumlu_id" name="sorumlu_id" class="form-select form-select-lg">
                                    <option value="0">-- Sorumlu Seçiniz --</option>
                                    <?php foreach ($users as $user) : ?>
                                        <option value="<?php echo $user->id; ?>" <?php echo ($user->id == $sorumlu_id) ? 'selected' : ''; ?>>
                                            <?php echo s($user->fullname); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Bu deponun yönetiminden sorumlu olacak kişiyi seçin.</small>
                            </div>

                            <div class="row mt-5">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-outline-secondary btn-lg w-100">
                                        <i class="fas fa-times me-2"></i>İptal
                                    </a>
                                </div>
                                <div class="col-sm-6">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Son düzenleme bilgisi -->
                <div class="text-center text-muted mt-4">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Son düzenleme: <?php echo userdate($depo->timemodified ?? time()); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form doğrulama için Bootstrap validator
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>

<?php
echo $OUTPUT->footer();
?>