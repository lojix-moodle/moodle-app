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
echo '<style>
    /* Form konteyneri */
    .depo-form-container {
        display: flex;
        justify-content: center;
        padding: 1rem;
    }
    
    /* Formu ortala ve genişliği küçült */
    .depo-form-card {
        background: #fff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        width: 550px; /* Sabit genişlik */
        margin: 1rem auto; /* Ortala */
    }
    
    /* Form başlık ve ikon */
    .depo-form-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .depo-icon-circle {
        background: #0073e6;
        color: white;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin: auto;
        margin-bottom: 1rem;
    }
    
    /* Label düzenle */
    .depo-label {
        display: inline-block;
        width: 200px; /* Genişlik arttırıldı */
        font-weight: 600;
        margin-bottom: 0.3rem;
        font-size: 1rem;
        color: #333;
        padding-right: 15px; /* Sağ tarafta boşluk */
    }
    
    /* Form grubu */
    .depo-form-group {
        margin-bottom: 1.5rem;
    }
    
    /* Form kontrol */
    .depo-form-control {
        width: calc(100% - 215px); /* Label genişliğinden sonra kalan alan */
        padding: 0.5rem 0.75rem;
        font-size: 0.95rem;
        border: 1px solid #ccc;
        border-radius: 0.5rem;
    }
    
    /* Input ipucu metni */
    .depo-form-hint {
        padding-left: 200px; /* Label hizasında başlasın */
        font-size: 0.8rem;
        color: #666;
        margin-top: 0.3rem;
    }
    
    /* Butonları düzenle */
    .depo-form-buttons {
        display: flex;
        justify-content: space-between; /* Sol ve sağa hizalama */
        margin-top: 1.5rem;
    }
    
    /* Buton stil */
    .depo-btn {
        height: 36px; /* Sabit yükseklik */
        min-width: 120px; /* Minimum genişlik */
        padding: 0 1rem;
        font-size: 0.85rem;
        border: none;
        border-radius: 0.5rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .depo-btn-primary {
        background-color: #0073e6;
        color: white;
    }
    
    .depo-btn-primary:hover {
        background-color: #0058b3;
    }
    
    .depo-btn-secondary {
        background-color: #e2e2e2;
        color: #333;
        text-decoration: none;
    }
    
    .depo-btn-secondary:hover {
        background-color: #d5d5d5;
    }
    
    /* Form hata mesajları */
    .fitem .felement .error,
    .fitem .felement .required {
        display: none !important;
    }
</style>';

class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('html', '<div class="depo-form-container"><div class="depo-form-card"><div class="depo-form-header">
            <div class="depo-icon-circle"><i class="fas fa-warehouse fa-2x"></i></div>
            <h3>Yeni Depo Ekle</h3><p>Lütfen tüm gerekli alanları doldurun</p></div>');

        // Depo Adı
        $mform->addElement('html', '<div class="depo-form-group">');
        $mform->addElement('text', 'name', '<span class="depo-label">Depo Adı</span>', [
            'class' => 'depo-form-control required-check',
            'placeholder' => 'Depo adını girin'
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Depo adı zorunludur', 'required', null, 'client');
        $mform->addElement('html', '<div class="depo-form-hint">Benzersiz bir depo adı girin</div>');
        $mform->addElement('html', '</div>');

        // Sorumlu Seçimi
        $mform->addElement('html', '<div class="depo-form-group">');
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

        $mform->addElement('select', 'sorumluid', '<span class="depo-label">Depo Sorumlusu</span>', $user_options, [
            'class' => 'depo-form-control required-check'
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
            </a>');
        $mform->addElement('html', '</div></div></div>');
    }

    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        if (!empty($data['name'])) {
            if ($DB->record_exists('block_depo_yonetimi_depolar', ['name' => $data['name']])) {
                $errors['name'] = 'Bu isimde bir depo zaten mevcut';
            }
        }

        if (empty($data['sorumluid']) || $data['sorumluid'] == 0) {
            $errors['sorumluid'] = 'Lütfen bir depo sorumlusu seçin';
        }

        return $errors;
    }
}

$form = new depo_ekle_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
} else if ($data = $form->get_data()) {
    global $DB, $USER;
    $newdepo = new stdClass();
    $newdepo->name = trim($data->name);
    $newdepo->sorumluid = $data->sorumluid;
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
}

// Form ekranı
$form->display();

echo $OUTPUT->footer();
?>