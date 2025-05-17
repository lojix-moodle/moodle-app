<?php
// Depo ekleme formu – tek dosyada CSS dahil

require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->libdir . '/formslib.php');

require_login();
$context = context_system::instance();
if (has_capability('block/depo_yonetimi:viewall', $context)) {
    $yetki = 'admin';
} elseif (has_capability('block/depo_yonetimi:viewown', $context)) {
    $yetki = 'depoyetkilisi';
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');

class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'header', 'Depo Bilgileri');

        $mform->addElement('text', 'name', 'Depo Adı');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $admins = get_admins();
        $admin_ids = array_map(fn($a) => $a->id, $admins);
        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = array_keys($teachers);
        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));
        $user_options = [0 => 'Seçiniz...'];

        if (!empty($user_ids)) {
            $users = $DB->get_records_list('user', 'id', $user_ids, 'lastname, firstname');
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user);
            }
        }

        $mform->addElement('select', 'sorumluid', 'Depo Sorumlusu', $user_options);
        $mform->setType('sorumluid', PARAM_INT);

        $this->add_action_buttons(true, 'Depoyu Kaydet');
    }
}

$form = new depo_ekle_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
} elseif ($data = $form->get_data()) {
    $newdepo = new stdClass();
    $newdepo->name = $data->name;
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);
    \core\notification::success('Depo başarıyla eklendi.');
    redirect(new moodle_url('/blocks/depo_yonetimi/index.php'));
}

// HTML ve CSS birlikte çıktı
echo $OUTPUT->header();

// Gömülü CSS (profesyonel görünüm)
echo <<<HTML
<style>
.depo-form-wrapper {
    max-width: 600px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.depo-form-baslik {
    font-size: 28px;
    text-align: center;
    margin-bottom: 30px;
    font-weight: 600;
    color: #333;
}
.mform .fitem label {
    font-weight: 600;
    color: #222;
}
.mform .fitem {
    margin-bottom: 20px;
}
input[type="text"], select {
    width: 100% !important;
    padding: 10px !important;
    font-size: 16px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
.mform .fgroup .form-buttons input[type="submit"],
.mform .fgroup .form-buttons input[type="submit"]:hover {
    background-color: #007bff;
    border: none;
    border-radius: 6px;
    color: white;
    font-size: 16px;
    padding: 10px 20px;
    margin-right: 10px;
}
.mform .fgroup .form-buttons input[type="submit"]:hover {
    background-color: #0056b3;
}
.mform .fgroup .form-buttons input[type="submit"]:last-child {
    background-color: #6c757d;
}
.mform .error {
    color: #dc3545;
}
</style>
HTML;

echo html_writer::start_div('depo-form-wrapper');
echo html_writer::tag('h2', 'Depo Ekle', ['class' => 'depo-form-baslik']);
$form->display();
echo html_writer::end_div();

echo $OUTPUT->footer();
