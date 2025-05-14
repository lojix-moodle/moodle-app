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

// Add custom CSS from styles file
$PAGE->requires->css('/blocks/depo_yonetimi/styles.css');

echo $OUTPUT->header();

// Özel CSS Stillerini Inline Olarak Ekleyelim
?>
    <style>
        /* Depo Yönetimi için CSS Kodları */

        /* Genel Stil Ayarları */
        .depo-dashboard {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .depo-dashboard h2 {
            font-weight: 600;
            color: #333;
        }

        .depo-dashboard .text-primary {
            color: #1177d1 !important;
        }

        .depo-dashboard .btn-primary {
            background-color: #1177d1;
            border-color: #1177d1;
        }

        .depo-dashboard .btn-primary:hover {
            background-color: #0e62b0;
            border-color: #0e62b0;
        }

        .depo-dashboard .btn-outline-primary {
            color: #1177d1;
            border-color: #1177d1;
        }

        .depo-dashboard .btn-outline-primary:hover {
            background-color: #1177d1;
            color: white;
        }

        /* Dashboard Header */
        .dashboard-header {
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,.1);
        }

        /* Depo Kartları */
        .depo-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 8px;
            overflow: hidden;
        }

        .depo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,.1) !important;
        }

        .depo-icon-container {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #1177d1;
            background-color: #f8f9fa;
            margin-right: 15px;
        }

        .depo-icon-container i {
            font-size: 1.5rem;
        }

        /* Form Stil Ayarları */
        .form-control-lg {
            font-size: 1.1rem;
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .needs-validation input:invalid {
            border-color: #dc3545;
        }

        .needs-validation input:valid {
            border-color: #28a745;
        }

        /* Animasyonlar */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .depo-dashboard {
            animation: fadeIn 0.3s ease-in-out;
        }
    </style>

    <div class="container py-4 depo-dashboard">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm depo-card">
                    <div class="card-header bg-primary text-white p-3 dashboard-header">
                        <div class="d-flex align-items-center">
                            <div class="depo-icon-container bg-white me-3">
                                <i class="icon fa fa-edit fa-fw" aria-hidden="true"></i>
                            </div>
                            <h4 class="mb-0 ml-2">Depo Bilgilerini Düzenle</h4>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold">
                                    <i class="icon fa fa-tag fa-fw" aria-hidden="true"></i> Depo Adı
                                </label>
                                <input type="text" id="name" name="name" value="<?php echo s($depo->name); ?>"
                                       class="form-control form-control-lg" required>
                                <div class="invalid-feedback">Lütfen geçerli bir depo adı giriniz.</div>
                                <small class="text-muted">Depo için benzersiz bir isim belirleyin.</small>
                            </div>

                            <div class="mb-4">
                                <label for="sorumlu_id" class="form-label fw-bold">
                                    <i class="icon fa fa-user fa-fw" aria-hidden="true"></i> Depo Sorumlusu
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
                                        <i class="icon fa fa-times fa-fw" aria-hidden="true"></i> İptal
                                    </a>
                                </div>
                                <div class="col-sm-6">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="icon fa fa-save fa-fw" aria-hidden="true"></i> Değişiklikleri Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Son düzenleme bilgisi -->
                <div class="text-center text-muted mt-4">
                    <small>
                        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
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