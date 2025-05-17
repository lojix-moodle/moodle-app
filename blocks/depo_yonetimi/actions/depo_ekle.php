<?php

// Depo ekleme formu

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->libdir . '/formslib.php');

// Yetki kontrolü
require_login();
if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
    $yetki = 'admin';
} elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
    $yetki = 'depoyetkilisi';
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));
$PAGE->set_heading(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));
$PAGE->navbar->add(get_string('plugins', 'admin'), new moodle_url('/admin/search.php#linkblocks'));
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_depo_yonetimi'), new moodle_url('/blocks/depo_yonetimi/index.php'));
$PAGE->navbar->add(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'));

// CSS dosyası eklemeden önce custom stil tanımlıyoruz (tek sayfada)
$PAGE->requires->js_call_amd('core/first', 'init', []);
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php?style=1'));

// Eğer sadece stil istendiyse CSS çıktısı ver
if (optional_param('style', 0, PARAM_INT)) {
    header("Content-Type: text/css");
    echo <<<CSS
.depo-form-container {
    max-width: 700px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    border-radius: 12px;
    font-family: "Segoe UI", sans-serif;
}

.depo-form-container h2 {
    text-align: center;
    color: #333;
    margin-bottom: 25px;
}

.mform fieldset {
    border: none;
    padding: 0;
}

.mform .fitem {
    margin-bottom: 20px;
}

.mform .fitem label {
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
    color: #333;
}

.mform .fitem .felement input[type="text"],
.mform .fitem .felement select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    background: #fdfdfd;
    box-sizing: border-box;
}

.mform .fitem .felement select {
    background-color: #f9f9f9;
}

.mform .fgroup .felement {
    text-align: right;
    margin-top: 20px;
}

input[type=submit], input[type=reset] {
    background-color: #2d89ef;
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    margin-left: 8px;
}

input[type=submit]:hover, input[type=reset]:hover {
    background-color: #1b5fb4;
}
CSS;
    exit;
}

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
        $admins = get_admins();
        $admin_ids = array_map(fn($admin) => $admin->id, $admins);

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
    redirect(new moodle_url('/my'));
} else if ($data = $form->get_data()) {
    $newdepo = new stdClass();
    $newdepo->name = $data->name;
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);

    \core\notification::success(get_string('depo_eklendi', 'block_depo_yonetimi', 'Depo başarıyla eklendi.'));
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
}

// Çıktı
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('depo_ekle', 'block_depo_yonetimi', 'Depo Ekle'), 2);

echo '<div class="depo-form-container">';
$form->display();
echo '</div>';

echo $OUTPUT->footer();
