<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

// Parametreleri ve güvenliği kontrol et
$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('index', PARAM_INT);
require_sesskey();

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'index' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Düzenle');
$PAGE->set_heading('Ürün Düzenle');

// Yetki kontrolü (Hem admin hem de depo yetkilisi için)
$context = context_system::instance();
if (!has_capability('block/depo_yonetimi:viewall', $context)) {
    // Depo yetkilisi mi kontrol et
    $user_depo = $DB->get_field('block_depo_yonetimi_kullanicilar', 'depoid', ['userid' => $USER->id]);
    if ($user_depo != $depoid) {
        throw new moodle_exception('nopermission', 'block_depo_yonetimi');
    }
}

// Ürün varlık kontrolü
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
if (!$urun) {
    throw new moodle_exception('urunyok', 'block_depo_yonetimi');
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $yeni_ad = required_param('name', PARAM_TEXT);
    $yeni_adet = required_param('adet', PARAM_INT);

    // Güncelleme işlemi
    $urun->name = $yeni_ad;
    $urun->adet = $yeni_adet;
    $DB->update_record('block_depo_yonetimi_urunler', $urun);

    // Başarılı yönlendirme
    redirect(
        new moodle_url('/my', ['depo' => $depoid]),
        get_string('guncellendi', 'block_depo_yonetimi'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Form render
echo $OUTPUT->header();
?>

    <form method="post">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <div class="form-group row">
            <label class="col-md-3 col-form-label">Ürün Adı:</label>
            <div class="col-md-9">
                <input type="text" name="name" value="<?php echo format_string($urun->name); ?>"
                       class="form-control" required maxlength="255">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-form-label">Adet:</label>
            <div class="col-md-9">
                <input type="number" name="adet" value="<?php echo $urun->adet; ?>"
                       class="form-control" min="0" step="1" required>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-9 offset-md-3">
                <button type="submit" class="btn btn-primary"><?php echo get_string('kaydet'); ?></button>
                <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>"
                   class="btn btn-secondary"><?php echo get_string('iptal'); ?></a>
            </div>
        </div>
    </form>

<?php
echo $OUTPUT->footer();