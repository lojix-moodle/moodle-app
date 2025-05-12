<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT;

// Parametreleri al
$depoid = required_param('depoid', PARAM_INT);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Depo Düzenle');
$PAGE->set_heading('Depo Düzenle');

// Yetki kontrolü
require_capability('block/depo_yonetimi:viewall', context_system::instance());

// Depo var mı kontrol et
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $yeni_ad = required_param('name', PARAM_TEXT);

    $depo->name = $yeni_ad;
    $DB->update_record('block_depo_yonetimi_depolar', $depo);

    redirect(new moodle_url('/my'), 'Depo başarıyla güncellendi.', 2);
}

echo $OUTPUT->header();
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Depo Bilgilerini Düzenle</h4>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="form-group mb-3">
                    <label for="name" class="form-label">Depo Adı:</label>
                    <input type="text" id="name" name="name" value="<?php echo s($depo->name); ?>" class="form-control form-control-lg" required>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success btn-lg">Kaydet</button>
                    <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-secondary btn-lg">İptal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
?>
