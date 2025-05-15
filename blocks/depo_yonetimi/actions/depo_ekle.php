<?php
require_once('../../../../config.php');
global $DB, $PAGE, $OUTPUT, $CFG;
require_once($CFG->libdir . '/formslib.php');



// Yetki kontrolü
require_login();
require_capability('block/depo_yonetimi:manage', context_system::instance());

// Sayfa ayarları
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');

// Breadcrumb
$PAGE->navbar->add(get_string('plugins', 'admin'), new moodle_url('/admin/search.php#linkblocks'));
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add('Depo Yönetimi', new moodle_url('/blocks/depo_yonetimi/index.php'));
$PAGE->navbar->add('Depo Ekle');

// Form sınıfı
class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        // Depo adı
        $mform->addElement('text', 'name', 'Depo Adı', ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Depo adı gereklidir', 'required', null, 'client');

        // Depo sorumlusu seçimi
        $admins = get_admins();
        $admin_ids = array_map(function($admin) {
            return $admin->id;
        }, $admins);

        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = array_keys($teachers);

        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));

        if (!empty($user_ids)) {
            list($insql, $params) = $DB->get_in_or_equal($user_ids);
            $users = $DB->get_records_select('user', "id $insql", $params, 'lastname, firstname');

            $user_options = [0 => 'Seçiniz...'];
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user);
            }

            $mform->addElement('select', 'sorumluid', 'Depo Sorumlusu', $user_options);
            $mform->setType('sorumluid', PARAM_INT);
            $mform->addRule('sorumluid', 'Depo sorumlusu seçmelisiniz', 'required');
        }

        // Form butonları
        $this->add_action_buttons();
    }

    // Form doğrulama
    function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Aynı isimde depo var mı kontrolü
        if ($DB->record_exists('block_depo_yonetimi_depolar', ['name' => $data['name']])) {
            $errors['name'] = 'Bu isimde bir depo zaten mevcut';
        }

        return $errors;
    }
}

// Form oluşturma
$form = new depo_ekle_form();

// Form işleme
if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
} else if ($data = $form->get_data()) {
    // Yeni depo oluştur
    $newdepo = new stdClass();
    $newdepo->name = trim($data->name);
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    try {
        $DB->start_delegated_transaction();
        $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);
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
            new moodle_url('/blocks/depo_yonetimi/index.php'),
            'Depo eklenirken bir hata oluştu: ' . $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Sayfa çıktısı
echo $OUTPUT->header();
echo $OUTPUT->heading('Depo Ekle');

echo '<div class="depo-form-container">';
$form->display();
echo '</div>';

echo $OUTPUT->footer();