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

        $mform->addElement('html', '<div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="form-header text-center mb-4">
                    <div class="icon-circle bg-primary bg-opacity-10 mx-auto mb-3">
                        <i class="fas fa-warehouse text-primary fa-2x"></i>
                    </div>
                    <h3 class="form-title">Yeni Depo Ekle</h3>
                    <p class="text-muted">Lütfen aşağıdaki formu doldurun</p>
                </div>

                <div class="form-floating mb-4">');

        $mform->addElement('text', 'name', '', [
            'class' => 'form-control',
            'placeholder' => 'Depo Adı',
            'id' => 'depoAdi'
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('html', '<label for="depoAdi">Depo Adı <span class="text-danger">*</span></label>
                </div>');

        // Depo sorumlusu seçimi
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

        $mform->addElement('html', '<div class="form-floating mb-4">');
        $mform->addElement('select', 'sorumluid', '', $user_options, [
            'class' => 'form-select',
            'id' => 'depoSorumlusu'
        ]);
        $mform->setType('sorumluid', PARAM_INT);
        $mform->addElement('html', '<label for="depoSorumlusu">Depo Sorumlusu <span class="text-danger">*</span></label>
                </div>');

        // Butonlar
        $mform->addElement('html', '<div class="d-grid gap-2">');
        $mform->addElement('submit', 'submitbutton', 'Depoyu Kaydet', [
            'class' => 'btn btn-primary btn-lg'
        ]);
        $mform->addElement('html', '
                <a href="' . new moodle_url('/my') . '" class="btn btn-light btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Geri
                </a>
            </div>
        </div></div>');
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
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        .form-floating {
            position: relative;
        }

        .form-floating > .form-control,
        .form-floating > .form-select {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }

        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
            color: #6c757d;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label,
        .form-floating > .form-select ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
            background-color: white;
            height: auto;
            padding: 0 5px;
            margin-left: 5px;
        }

        .form-control, .form-select {
            border: 2px solid #edf2f7;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0f6fc5;
            box-shadow: 0 0 0 0.25rem rgba(15, 111, 197, 0.1);
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
            border: 2px solid #edf2f7;
        }

        .btn-light:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .text-danger {
            color: #dc3545;
        }
    </style>

    <div class="form-container">
        <?php $form->display(); ?>
    </div>

<?php
echo $OUTPUT->footer();
?>