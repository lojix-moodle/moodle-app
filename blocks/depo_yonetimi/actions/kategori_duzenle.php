<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametreleri al
$kategoriid = required_param('kategoriid', PARAM_INT);

// Kategori var mı kontrol et
$kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $kategoriid], '*', MUST_EXIST);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['kategoriid' => $kategoriid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Düzenle');
$PAGE->set_heading('Kategori Düzenle');

//// Yetki kontrolü
//$kullanici_depo_eslesme = [
//    2 => 3,
//    5 => 1,
//];
//
//if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
//    (!isset($kullanici_depo_eslesme[$USER->id]) || $kullanici_depo_eslesme[$USER->id] != $depoid)) {
//    print_error('Bu depoya erişim izniniz yok.');
//}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $yeni_ad = required_param('name', PARAM_TEXT);

    $kategori->name = $yeni_ad;
    $kategori->timemodified = time();

    $DB->update_record('block_depo_yonetimi_kategoriler', $kategori);

    // Başarılı mesajı ile ana sayfaya yönlendir
    redirect(new moodle_url('/my', ['view' => 'kategoriler']), 'Kategori başarıyla güncellendi.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
?>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">"<?php echo s($kategori->name); ?>" Kategorisini Düzenle</h4>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Kategori Adı:</label>
                        <input type="text" id="name" name="name" value="<?php echo s($kategori->name); ?>" class="form-control form-control-lg" required>
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