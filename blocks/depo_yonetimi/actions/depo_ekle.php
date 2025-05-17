<?php
require_once(__DIR__ . '/../../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT;
require_once($CFG->libdir . '/formslib.php');

require_login();
if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
    $yetki = 'admin';
} elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
    $yetki = 'depoyetkilisi';
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');

// Bootstrap ve Font Awesome ekle
$PAGE->requires->css('/blocks/depo_yonetimi/styles/custom.css');
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'));

class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        // Form container başlangıcı
        $mform->addElement('html', '<div class="card shadow-sm">
            <div class="card-body">
            <div class="form-container p-4">');

        // Depo adı alanı
        $mform->addElement('html', '<div class="form-group mb-4">');
        $mform->addElement('text', 'name', '', [
            'class' => 'form-control form-control-lg',
            'placeholder' => 'Depo Adı',
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addElement('html', '</div>');

        // Depo sorumlusu seçimi
        $admins = get_admins();
        $admin_ids = array_map(function($admin) { return $admin->id; }, $admins);
        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = array_keys($teachers);
        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));

        if (!empty($user_ids)) {
            $users = $DB->get_records_list('user', 'id', $user_ids, 'lastname, firstname');
            $user_options = [0 => 'Depo Sorumlusu Seçiniz...'];
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user);
            }

            $mform->addElement('html', '<div class="form-group mb-4">');
            $mform->addElement('select', 'sorumluid', '', $user_options, [
                'class' => 'form-select form-select-lg'
            ]);
            $mform->setType('sorumluid', PARAM_INT);
            $mform->addElement('html', '</div>');
        }

        // Butonlar
        $mform->addElement('html', '<div class="d-flex gap-2">');
        $mform->addElement('submit', 'submitbutton', 'Kaydet', [
            'class' => 'btn btn-primary btn-lg'
        ]);
        $mform->addElement('html', '
            <a href="' . new moodle_url('/my') . '" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Geri
            </a>
        </div>');

        // Form container bitişi
        $mform->addElement('html', '</div></div></div>');
    }
}

$form = new depo_ekle_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/my'));
} else if ($data = $form->get_data()) {
    $newdepo = new stdClass();
    $newdepo->name = $data->name;
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    $depoid = $DB->insert_record('block_depo_yonetimi_depolar', $newdepo);
    \core\notification::success('Depo başarıyla eklendi.');
    redirect(new moodle_url('/my'));
}

echo $OUTPUT->header();
?>

    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            background: #fff;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #dde2e5;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
        }
        .btn-primary {
            background: #0f6fc5;
            border: none;
        }
        .btn-primary:hover {
            background: #0d5aa1;
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
    </style>

<?php
$form->display();
echo $OUTPUT->footer();