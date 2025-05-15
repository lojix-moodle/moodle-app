<?php
// Depo ekleme formu
require_once('../../../../config.php');
require_once($CFG->libdir . '/formslib.php');

// Yetki kontrolü
require_login();
require_capability('block/depo_yonetimi:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));
$PAGE->set_heading(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));
$PAGE->navbar->add(get_string('plugins', 'admin'), new moodle_url('/admin/search.php#linkblocks'));
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_depo_yonetimi'), new moodle_url('/blocks/depo_yonetimi/index.php'));
$PAGE->navbar->add(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));

// Form sınıfı
class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        // Depo adı
        $mform->addElement('text', 'name', get_string('depo_adi', 'block_depo_yonetimi', 'Depo Adı'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Depo sorumlusu seçimi
        // Admin ve öğretmen rolüne sahip kullanıcıları çek
        $admins = get_admins();
        $admin_ids = array_map(function($admin) {
            return $admin->id;
        }, $admins);

        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = array_keys($teachers);

        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));

        if (!empty($user_ids)) {
            $users = $DB->get_records_list('user', 'id', $user_ids, 'lastname, firstname');

            $user_options = [0 => 'Seçiniz...'];
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user);
            }

            $mform->addElement('select', 'sorumluid', get_string('depo_sorumlusu', 'block_depo_yonetimi', 'Depo Sorumlusu'), $user_options);
            $mform->setType('sorumluid', PARAM_INT);
        }

        // Form butonları
        $this->add_action_buttons();
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
    $newdepo->name = $data->name;
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);

    // Başarı mesajı
    \core\notification::success(get_string('depo_eklendi', 'block_depo_yonetimi', 'Depo başarıyla eklendi.'));
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
}

// Çıktı
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));

echo '<div class="depo-form-container">';
$form->display();
echo '</div>';

echo $OUTPUT->footer();