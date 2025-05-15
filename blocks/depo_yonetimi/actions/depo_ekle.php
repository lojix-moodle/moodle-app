<?php
// Depo ekleme formu
require_once('../../../../config.php');
// Moodle global objeleri
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->libdir . '/formslib.php');

// Yetki kontrolü
require_login();
require_capability('block/depo_yonetimi:manage', context_system::instance());

// Sayfa yapılandırması
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title(get_string('depo_ekle', 'block_depo_yonetimi'));
$PAGE->set_heading(get_string('depo_ekle', 'block_depo_yonetimi'));
$PAGE->navbar->add(get_string('plugins', 'admin'), new moodle_url('/admin/search.php#linkblocks'));
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_depo_yonetimi'), new moodle_url('/blocks/depo_yonetimi/index.php'));
$PAGE->navbar->add(get_string('depo_ekle', 'block_depo_yonetimi'));
// Form sınıfı
class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        // Depo adı
        $mform->addElement('text', 'name', get_string('depo_adi', 'block_depo_yonetimi'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Depo sorumlusu seçimi
        $admins = get_admins();
        $admin_ids = array_map(fn($admin) => $admin->id, $admins);

        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = is_array($teachers) ? array_keys($teachers) : [];

        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));

        if (!empty($user_ids)) {
            $users = $DB->get_records_list('user', 'id', $user_ids, 'lastname, firstname');

            $user_options = [0 => get_string('choosedots')];
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user, true);
            }

            $mform->addElement('select', 'sorumluid', get_string('depo_sorumlusu', 'block_depo_yonetimi'), $user_options);
            $mform->setType('sorumluid', PARAM_INT);
            $mform->addRule('sorumluid', null, 'required', null, 'client');
        }

        $this->add_action_buttons();
    }
}

// Form oluşturma
$form = new depo_ekle_form();

// Form işleme
if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
} else if ($data = $form->get_data()) {
    global $DB;

    $newdepo = new stdClass();
    $newdepo->name = $data->name;
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);

    \core\notification::success(get_string('depo_eklendi', 'block_depo_yonetimi'));
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
}

// Çıktı
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('depo_ekle', 'block_depo_yonetimi'));
echo '<div class="depo-form-container">';
$form->display();
echo '</div>';
echo $OUTPUT->footer();