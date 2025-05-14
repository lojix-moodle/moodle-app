<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Ekle');
$PAGE->set_heading('Kategori Ekle');

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $kategori_adi = required_param('name', PARAM_TEXT);

    $kategori = new stdClass();
    $kategori->name = $kategori_adi;
    $kategori->timecreated = time();
    $kategori->timemodified = time();

    $DB->insert_record('block_depo_yonetimi_kategoriler', $kategori);

    // Başarılı mesajı ile ana sayfaya yönlendir
    redirect(new moodle_url('/my', ['view' => 'kategoriler']), 'Kategori başarıyla eklendi.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Kategori Ekle</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Kategori Adı:</label>
                        <input type="text" id="name" name="name" class="form-control form-control-lg" required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success btn-lg">Kaydet</button>
                        <a href="<?php echo new moodle_url('/my', ['view' => 'kategoriler']); ?>" class="btn btn-secondary btn-lg">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
echo $OUTPUT->footer();
?>