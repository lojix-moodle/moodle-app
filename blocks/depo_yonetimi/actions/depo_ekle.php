<?php
require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $OUTPUT, $DB, $USER;

require_once($CFG->libdir . '/formslib.php');

require_login();
$context = context_system::instance();

$canManage = false;
if (has_capability('block/depo_yonetimi:viewall', $context)) {
    $yetki = 'admin';
    $canManage = true;
} elseif (has_capability('block/depo_yonetimi:viewown', $context)) {
    $yetki = 'depoyetkilisi';
    $canManage = true;
}
if (!$canManage) {
    redirect(new moodle_url('/my'), 'Bu sayfaya erişim yetkiniz bulunmamaktadır.', null, \core\output\notification::NOTIFY_ERROR);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');
$PAGE->set_pagelayout('admin');

// Gömülü CSS
$PAGE->requires->css(new moodle_url('/lib/jquery/themes/base/jquery.ui.all.css'));
$PAGE->requires->js_call_amd('block_depo_yonetimi/validation', 'init');

echo $OUTPUT->header();
?>

    <style>
        .form-control, .form-select {
            border-color: #dee2e6 !important;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0f6cbf;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .card {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .btn {
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-warehouse me-2"></i>
                            <h5 class="mb-0">Yeni Depo Ekle</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" action="" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag me-2"></i>Depo Adı
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                                    <input type="text" class="form-control" id="name" name="name"
                                           placeholder="Depo adını girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen depo adı girin</div>
                                <small class="form-text text-muted">Benzersiz bir depo adı girin</small>
                            </div>

                            <div class="mb-4">
                                <label for="sorumluid" class="form-label">
                                    <i class="fas fa-user me-2"></i>Depo Sorumlusu
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                    <select class="form-select" id="sorumluid" name="sorumluid" required>
                                        <option value="">Depo Sorumlusu Seçiniz...</option>
                                        <?php
                                        $admins = get_admins();
                                        $admin_ids = array_map(fn($a) => $a->id, $admins);
                                        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
                                        $teacher_ids = array_keys($teachers);
                                        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));

                                        if (!empty($user_ids)) {
                                            list($insql, $inparams) = $DB->get_in_or_equal($user_ids);
                                            $users = $DB->get_records_select('user', "id $insql AND deleted = 0", $inparams, 'lastname, firstname');
                                            foreach ($users as $user) {
                                                echo '<option value="'.$user->id.'">'.fullname($user).'</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="invalid-feedback">Lütfen bir depo sorumlusu seçin</div>
                                <small class="form-text text-muted">Depo sorumlusu, depo ile ilgili tüm işlemleri yönetebilir</small>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Depoyu Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/index.php'); ?>"
                                   class="btn btn-outline-secondary ms-auto">
                                    <i class="fas fa-arrow-left me-2"></i>Geri
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            'use strict'

            // Form doğrulama
            var forms = document.querySelectorAll('.needs-validation')
            var loadingOverlay = document.getElementById('loadingOverlay')
            var submitBtn = document.getElementById('submitBtn')

            // Sayfa yüklendiğinde loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none'
            })

            Array.prototype.slice.call(forms).forEach(function (form) {
                // Dinamik doğrulama - alan değiştiğinde
                var inputs = form.querySelectorAll('input, select')
                Array.prototype.slice.call(inputs).forEach(function(input) {
                    input.addEventListener('change', function() {
                        // Geçerlilik kontrolü
                        if (input.checkValidity()) {
                            input.classList.remove('is-invalid')
                            input.classList.add('is-valid')
                        } else {
                            input.classList.remove('is-valid')
                            input.classList.add('is-invalid')
                        }
                    })
                })

                // Form gönderildiğinde
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()

                        // Geçersiz alanları işaretle
                        Array.prototype.slice.call(inputs).forEach(function(input) {
                            if (!input.checkValidity()) {
                                input.classList.add('is-invalid')
                            }
                        })
                    } else {
                        // Form geçerli ise yükleme animasyonunu göster
                        loadingOverlay.style.display = 'flex'
                        submitBtn.disabled = true

                        // Form verileri
                        var name = document.getElementById('name').value.trim()
                        var sorumluid = document.getElementById('sorumluid').value

                        if (name === '' || sorumluid === '') {
                            event.preventDefault()
                            loadingOverlay.style.display = 'none'
                            submitBtn.disabled = false
                            return false
                        }

                        // Form post işlemi
                        var formData = new FormData()
                        formData.append('name', name)
                        formData.append('sorumluid', sorumluid)
                        formData.append('submit', 'true')
                        formData.append('sesskey', document.querySelector('input[name="sesskey"]').value)

                        // Normal form gönderimi devam edecek
                        return true
                    }

                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>

<?php
// Form işleme
if (isset($_POST['submit']) || (isset($_POST['name']) && isset($_POST['sorumluid']))) {
    require_sesskey();

    $name = required_param('name', PARAM_TEXT);
    $sorumluid = required_param('sorumluid', PARAM_INT);

    // Doğrulama
    $errors = [];

    if (empty($name)) {
        $errors['name'] = 'Depo adı zorunludur';
    } else if ($DB->record_exists('block_depo_yonetimi_depolar', ['name' => $name])) {
        $errors['name'] = 'Bu isimde bir depo zaten mevcut';
    }

    if (empty($sorumluid)) {
        $errors['sorumluid'] = 'Lütfen bir depo sorumlusu seçin';
    }

    // Hata yoksa kaydet
    if (empty($errors)) {
        $newdepo = new stdClass();
        $newdepo->name = trim($name);
        $newdepo->sorumluid = $sorumluid;
        $newdepo->timecreated = time();
        $newdepo->timemodified = time();
        $newdepo->createdby = $USER->id;

        try {
            $transaction = $DB->start_delegated_transaction();
            $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);

            $log = new stdClass();
            $log->depoid = $depoid;
            $log->userid = $USER->id;
            $log->action = 'create';
            $log->details = 'Depo oluşturuldu';
            $log->timecreated = time();
            $DB->insert_record('block_depo_yonetimi_logs', $log);

            $DB->commit_delegated_transaction();

            redirect(new moodle_url('/blocks/depo_yonetimi/index.php'), 'Depo başarıyla eklendi.', null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            $DB->rollback_delegated_transaction($transaction, $e);
            redirect(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'), 'Depo eklenirken bir hata oluştu: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        // Hata varsa göster
        foreach ($errors as $key => $error) {
            \core\notification::error($error);
        }
    }
}

echo $OUTPUT->footer();
?>