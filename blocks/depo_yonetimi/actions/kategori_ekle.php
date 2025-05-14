<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametreleri al
$depoid = required_param('depoid', PARAM_INT);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Ekle');
$PAGE->set_heading('Kategori Ekle');

// Depo var mı kontrol et
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Yetki kontrolü
$kullanici_depo_eslesme = [
    2 => 3,
    5 => 1,
];

if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
    (!isset($kullanici_depo_eslesme[$USER->id]) || $kullanici_depo_eslesme[$USER->id] != $depoid)) {
    print_error('Bu depoya erişim izniniz yok.');
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $kategori_adi = required_param('name', PARAM_TEXT);

    $kategori = new stdClass();
    $kategori->depoid = $depoid;
    $kategori->name = $kategori_adi;
    $kategori->timecreated = time();
    $kategori->timemodified = time();

    $DB->insert_record('block_depo_yonetimi_kategoriler', $kategori);

    // Başarılı mesajı ile ana sayfaya yönlendir
    redirect(new moodle_url('/my', ['depo' => $depoid, 'view' => 'kategoriler']), 'Kategori başarıyla eklendi.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">"<?php echo s($depo->name); ?>" Deposuna Kategori Ekle</h4>
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
                        <a href="<?php echo new moodle_url('/my', ['depo' => $depoid, 'view' => 'kategoriler']); ?>" class="btn btn-secondary btn-lg">İptal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
echo $OUTPUT->footer();
?>