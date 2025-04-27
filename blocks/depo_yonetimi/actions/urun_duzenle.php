<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

// Gelen parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('index', PARAM_INT); // index olarak geliyor, aslında ürün id'si

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'index' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Düzenle');
$PAGE->set_heading('Ürün Düzenle');

// Yetki kontrolü
if (!has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
    print_error('Erişim izniniz yok.');
}

// Ürün var mı kontrol et
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
if (!$urun) {
    print_error('Ürün bulunamadı.');
}

// Formdan veri geldiyse işle
if (optional_param('submitbutton', null, PARAM_RAW)) {
    $yeni_ad = required_param('name', PARAM_TEXT);
    $yeni_adet = required_param('adet', PARAM_INT);

    // Veritabanında güncelle
    $urun->name = $yeni_ad;
    $urun->adet = $yeni_adet;
    $DB->update_record('block_depo_yonetimi_urunler', $urun);

    // Başarıyla güncellendi, geri dön
    redirect(new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]), 'Ürün başarıyla güncellendi.', 2);

}

// Formu gösterelim
echo $OUTPUT->header();
?>

<form method="post">
    <div class="form-group">
        <label for="name">Ürün Adı:</label>
        <input type="text" id="name" name="name" value="<?php echo s($urun->name); ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="adet">Adet:</label>
        <input type="number" id="adet" name="adet" value="<?php echo s($urun->adet); ?>" class="form-control" required>
    </div>
    <br>
    <button type="submit" name="submitbutton" class="btn btn-success">Kaydet</button>
    <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-secondary">Vazgeç</a>
</form>

<?php
echo $OUTPUT->footer();
?>
