<?php
// Moodle gerekli dosyaları
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

// Giriş kontrolü
require_login();
$context = context_system::instance();

// Yetki kontrolü
$canManage = false;
if (has_capability('block/depo_yonetimi:viewall', $context)) {
    $yetki = 'admin';
    $canManage = true;
} elseif (has_capability('block/depo_yonetimi:viewown', $context)) {
    $yetki = 'depoyetkilisi';
    $canManage = true;
}

// Yetkisi yoksa ana sayfaya yönlendir
if (!$canManage) {
    redirect(new moodle_url('/my'), 'Bu sayfaya erişim yetkiniz bulunmamaktadır.', null, \core\output\notification::NOTIFY_ERROR);
}

// Sayfa ayarları
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title(get_string('depot_add', 'block_depo_yonetimi', 'Depo Ekle'));
$PAGE->set_heading(get_string('depot_add', 'block_depo_yonetimi', 'Depo Ekle'));
$PAGE->set_pagelayout('admin');

// CSS ve JS eklemeleri
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'));
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles/depo.css'));

// JS ekleme - form doğrulama için
$PAGE->requires->js_init_code('
    document.addEventListener("DOMContentLoaded", function() {
        const requiredFields = document.querySelectorAll(".depo-required");
        const inputs = document.querySelectorAll(".depo-form-control");
        
        // Hide required icons initially
        requiredFields.forEach(icon => {
            icon.style.display = "none";
        });
        
        // Show required icon only when input is empty and has been focused
        inputs.forEach(input => {
            input.addEventListener("blur", function() {
                const parent = this.closest(".depo-form-group");
                const icon = parent.querySelector(".depo-required");
                if(icon && !this.value.trim()) {
                    icon.style.display = "inline-block";
                }
            });
            
            input.addEventListener("input", function() {
                const parent = this.closest(".depo-form-group");
                const icon = parent.querySelector(".depo-required");
                if(icon) {
                    icon.style.display = this.value.trim() ? "none" : "inline-block";
                }
            });
        });
    });
');

/**
 * Depo ekleme formu sınıfı
 */
class depo_ekle_form extends moodleform {
    /**
     * Form tanımlaması
     */
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        // Form başlığı ve açıklaması
        $mform->addElement('html', '<div class="depo-form-card">
            <div class="depo-form-header">
                <div class="depo-icon-circle">
                    <i class="fas fa-warehouse fa-2x"></i>
                </div>
                <h3>Yeni Depo Ekle</h3>
                <p>Lütfen tüm gerekli alanları doldurun</p>
            </div>');

        // Depo adı
        $mform->addElement('html', '<div class="depo-form-group">');
        $mform->addElement('text', 'name', 'Depo Adı <span class="depo-required text-danger">*</span>', [
            'class' => 'depo-form-control',
            'placeholder' => 'Depo adını girin'
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Depo adı zorunludur', 'required', null, 'client');
        $mform->addElement('html', '<div class="depo-form-hint">Benzersiz bir depo adı girin</div>');
        $mform->addElement('html', '</div>');

        // Depo sorumlusu seçimi
        $mform->addElement('html', '<div class="depo-form-group">');

        // Yetkili kullanıcılar
        $admins = get_admins();
        $admin_ids = array_map(fn($a) => $a->id, $admins);
        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = array_keys($teachers);
        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));
        $user_options = [0 => 'Depo Sorumlusu Seçiniz...'];

        if (!empty($user_ids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($user_ids);
            $users = $DB->get_records_select('user', "id $insql AND deleted = 0", $inparams, 'lastname, firstname');
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user);
            }
        }

        $mform->addElement('select', 'sorumluid', 'Depo Sorumlusu <span class="depo-required text-danger">*</span>', $user_options, [
            'class' => 'depo-form-control'
        ]);
        $mform->setType('sorumluid', PARAM_INT);
        $mform->addRule('sorumluid', 'Lütfen bir depo sorumlusu seçin', 'required', null, 'client');
        $mform->addElement('html', '<div class="depo-form-hint">Depo sorumlusu, depo ile ilgili tüm işlemleri yönetebilir</div>');
        $mform->addElement('html', '</div>');

        // Butonlar
        $mform->addElement('html', '<div class="depo-form-buttons">');
        $mform->addElement('submit', 'submitbutton', 'Depoyu Kaydet', [
            'class' => 'depo-btn depo-btn-primary'
        ]);
        $mform->addElement('html', '
                <a href="' . new moodle_url('/blocks/depo_yonetimi/index.php') . '" class="depo-btn depo-btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Geri
                </a>
            </div>');

        $mform->addElement('html', '</div>'); // depo-form-card kapatma
    }

    /**
     * Form doğrulama
     */
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Depo adı kontrol
        if (!empty($data['name'])) {
            // Aynı isimde depo var mı kontrol et
            if ($DB->record_exists('block_depo_yonetimi_depolar', ['name' => $data['name']])) {
                $errors['name'] = 'Bu isimde bir depo zaten mevcut';
            }
        }

        // Sorumlu seçimi kontrol
        if (empty($data['sorumluid']) || $data['sorumluid'] == 0) {
            $errors['sorumluid'] = 'Lütfen bir depo sorumlusu seçin';
        }

        return $errors;
    }
}

// Form oluştur
$form = new depo_ekle_form();

// Form işleme
if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
} else if ($data = $form->get_data()) {
    $newdepo = new stdClass();
    $newdepo->name = trim($data->name);
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();
    $newdepo->createdby = $USER->id;

    try {
        $DB->start_delegated_transaction();
        $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);

        // İşlem kaydı oluştur
        $log = new stdClass();
        $log->depoid = $depoid;
        $log->userid = $USER->id;
        $log->action = 'create';
        $log->details = 'Depo oluşturuldu';
        $log->timecreated = time();
        $DB->insert_record('block_depo_yonetimi_logs', $log);

        $DB->commit_delegated_transaction();

        redirect(
            new moodle_url('/blocks/depo_yonetimi/index.php'),
            'Depo başarıyla eklendi.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Exception $e) {
        $DB->rollback_delegated_transaction();
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'),
            'Depo eklenirken bir hata oluştu: ' . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Sayfa çıktısı
echo $OUTPUT->header();
?>

    <style>
        /* Ana konteyner */
        .depo-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        /* Form kartı */
        .depo-form-card {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        /* Form başlık bölümü */
        .depo-form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .depo-icon-circle {
            width: 80px;
            height: 80px;
            background-color: #f0f7ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            color: #0f6fc5;
        }

        .depo-form-header h3 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.75rem;
        }

        .depo-form-header p {
            color: #6c757d;
            font-size: 1rem;
        }

        /* Form grupları */
        .depo-form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        .depo-form-control {
            display: block;
            width: 100%;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 2px solid #edf2f7;
            border-radius: 12px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .depo-form-control:focus {
            border-color: #0f6fc5;
            box-shadow: 0 0 0 0.25rem rgba(15, 111, 197, 0.1);
            outline: 0;
        }

        .depo-form-hint {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* Butonlar */
        .depo-form-buttons {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }

        .depo-btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            line-height: 1.5;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .depo-btn-primary {
            color: #fff;
            background-color: #0f6fc5;
            border: none;
            min-width: 140px;
        }

        .depo-btn-primary:hover {
            background-color: #0d5aa1;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 111, 197, 0.2);
        }

        .depo-btn-secondary {
            color: #495057;
            background-color: #f8f9fa;
            border: 2px solid #edf2f7;
            min-width: 100px;
            text-align: center;
        }

        .depo-btn-secondary:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        /* Hata mesajları */
        .error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: block;
        }

        /* Label genişliği artırıldı */
        .fitem_id_name .fitemtitle,
        .fitem_id_sorumluid .fitemtitle {
            min-width: 180px;
            display: inline-block;
        }

        /* Duyarlı tasarım */
        @media (max-width: 768px) {
            .depo-form-buttons {
                flex-direction: column;
            }

            .depo-btn {
                width: 100%;
            }
        }
    </style>

    <div class="depo-container">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo new moodle_url('/my'); ?>">Ana Sayfa</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo new moodle_url('/blocks/depo_yonetimi/index.php'); ?>">Depo Yönetimi</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Depo Ekle</li>
                </ol>
            </nav>
        </div>

        <?php $form->display(); ?>
    </div>

<?php
echo $OUTPUT->footer();
?>