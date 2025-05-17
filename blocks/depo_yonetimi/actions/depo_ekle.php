<?php
require_once(__DIR__ . '/../../../config.php');
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

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-warehouse fa-fw me-2"></i>Yeni Depo Ekle
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label"><i class="fas fa-tag me-2"></i>Depo Adı</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Depo adını girin" required>
                                <div class="invalid-feedback">Lütfen depo adı girin</div>
                            </div>

                            <div class="mb-3">
                                <label for="sorumluid" class="form-label"><i class="fas fa-user me-2"></i>Depo Sorumlusu</label>
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
                                <div class="invalid-feedback">Lütfen bir depo sorumlusu seçin</div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Depoyu Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/index.php'); ?>" class="btn btn-outline-secondary ms-auto">
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

            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    } else {
                        // Form geçerli ise gönderme işlemi burada
                        var name = document.getElementById('name').value.trim()
                        var sorumluid = document.getElementById('sorumluid').value

                        if (name === '' || sorumluid === '') {
                            event.preventDefault()
                            return false
                        }

                        // Form post işlemi
                        var formData = new FormData()
                        formData.append('name', name)
                        formData.append('sorumluid', sorumluid)
                        formData.append('submit', 'true')

                        // AJAX yerine POST form submission
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
            $DB->start_delegated_transaction();
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
            $DB->rollback_delegated_transaction();
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