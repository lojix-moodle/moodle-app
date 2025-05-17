<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/lib.php'); // fullname için gerekli

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'));
$PAGE->set_title('Depo Ekle');
$PAGE->set_heading('Depo Ekle');
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'));

if (has_capability('block/depo_yonetimi:viewall', $context)) {
    $yetki = 'admin';
} elseif (has_capability('block/depo_yonetimi:viewown', $context)) {
    $yetki = 'depoyetkilisi';
}

class depo_ekle_form extends moodleform {
    protected function definition() {
        global $DB;
        $mform = $this->_form;

        // Depo adı
        $mform->addElement('text', 'name', 'Depo Adı', [
            'class' => 'form-control',
            'placeholder' => 'Depo Adı',
            'id' => 'depoAdi'
        ]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Bu alan zorunludur', 'required');

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

        $mform->addElement('select', 'sorumluid', 'Depo Sorumlusu', $user_options, [
            'class' => 'form-select',
            'id' => 'depoSorumlusu'
        ]);
        $mform->setType('sorumluid', PARAM_INT);
        $mform->addRule('sorumluid', 'Bu alan zorunludur', 'required');

        // Butonlar
        $mform->addElement('submit', 'submitbutton', 'Depoyu Kaydet', [
            'class' => 'btn btn-primary btn-lg'
        ]);
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

    .btn-primary {
        background: #0f6fc5;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 500;
    }

    .btn-primary:hover {
        background: #0d5aa1;
    }

    label {
        font-weight: 500;
        margin-bottom: .3rem;
    }

    select, input {
        margin-bottom: 1rem;
    }
</style>

<div class="form-container">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="icon-circle bg-primary bg-opacity-10 mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-warehouse text-primary fa-2x"></i>
                </div>
                <h3 class="form-title">Yeni Depo Ekle</h3>
                <p class="text-muted">Lütfen aşağıdaki formu doldurun</p>
            </div>
            <?php $form->display(); ?>
            <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-light btn-lg w-100 mt-2">
                <i class="fas fa-arrow-left me-2"></i> Geri
            </a>
