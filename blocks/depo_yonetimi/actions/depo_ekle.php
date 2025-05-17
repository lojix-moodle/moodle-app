<?php
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
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'));

class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        // Form container başlangıcı
        $mform->addElement('html', '<div class="card shadow-sm border-0">
            <div class="card-body p-4">');

        // Başlık
        $mform->addElement('html', '<div class="form-header text-center mb-4">
            <div class="icon-circle bg-primary bg-opacity-10 mx-auto mb-3">
                <i class="fas fa-warehouse text-primary fa-2x"></i>
            </div>
            <h3 class="form-title">Yeni Depo Ekle</h3>
            <p class="text-muted">Lütfen depo bilgilerini giriniz</p>
        </div>');

        // Depo adı
        $mform->addElement('html', '<div class="form-group mb-4">
            <label class="form-label fw-bold mb-2">Depo Adı</label>');
        $mform->addElement('text', 'name', '', [
            'class' => 'form-control form-control-lg',
            'placeholder' => 'Depo adını giriniz'
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addElement('html', '</div>');

        // Depo sorumlusu
        $admins = get_admins();
        $admin_ids = array_map(fn($a) => $a->id, $admins);
        $teachers = get_users_by_capability(context_system::instance(), 'moodle/course:manageactivities');
        $teacher_ids = array_keys($teachers);
        $user_ids = array_unique(array_merge($admin_ids, $teacher_ids));
        $user_options = [0 => 'Depo Sorumlusu Seçiniz...'];

        if (!empty($user_ids)) {
            $users = $DB->get_records_list('user', 'id', $user_ids, 'lastname, firstname');
            foreach ($users as $user) {
                $user_options[$user->id] = fullname($user);
            }
        }

        $mform->addElement('html', '<div class="form-group mb-4">
            <label class="form-label fw-bold mb-2">Depo Sorumlusu</label>');
        $mform->addElement('select', 'sorumluid', '', $user_options, [
            'class' => 'form-select form-select-lg'
        ]);
        $mform->setType('sorumluid', PARAM_INT);
        $mform->addElement('html', '</div>');

        // Butonlar
        $mform->addElement('html', '<div class="form-buttons d-flex gap-2 mt-4">');
        $mform->addElement('submit', 'submitbutton', 'Depoyu Kaydet', [
            'class' => 'btn btn-primary btn-lg flex-grow-1'
        ]);
        $mform->addElement('html', '
            <a href="' . new moodle_url('/my') . '" class="btn btn-light btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Geri
            </a>
        </div>');

        $mform->addElement('html', '</div></div>');
    }
}

$form = new depo_ekle_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/my'));
} elseif ($data = $form->get_data()) {
    $newdepo = new stdClass();
    $newdepo->name = $data->name;
    $newdepo->sorumluid = $data->sorumluid;
    $newdepo->timecreated = time();
    $newdepo->timemodified = time();

    if ($DB->insert_record('block_depo_yonetimi_depolar', $newdepo)) {
        redirect(
            new moodle_url('/my'),
            'Depo başarıyla eklendi.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

echo $OUTPUT->header();
?>

    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .card {
            border-radius: 15px;
            overflow: hidden;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dde2e5;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0f6fc5;
            box-shadow: 0 0 0 0.25rem rgba(15, 111, 197, 0.1);
        }

        .form-label {
            font-size: 0.9rem;
            color: #495057;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #0f6fc5;
            border: none;
        }

        .btn-primary:hover {
            background: #0d5aa1;
            transform: translateY(-1px);
        }

        .btn-light {
            background: #f8f9fa;
            border: 1px solid #dde2e5;
        }

        .btn-light:hover {
            background: #e9ecef;
        }
    </style>

    <div class="form-container">
        <?php $form->display(); ?>
    </div>

<?php
echo $OUTPUT->footer();
?>