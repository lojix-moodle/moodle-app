<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Düzenle');
$PAGE->set_heading('Ürün Düzenle');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin) {
    $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
    if (!$user_depo || $user_depo != $depoid) {
        print_error('Erişim izniniz yok.');
    }
}

$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
if (!$urun) {
    print_error('Ürün bulunamadı.');
}

// Kategorileri veritabanından al
$kategoriler = $DB->get_records('depo_yonetimi_kategoriler', null, 'name ASC');

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $yeni_ad = required_param('name', PARAM_TEXT);
    $yeni_adet = required_param('adet', PARAM_INT);
    $kategori_id = required_param('kategori_id', PARAM_INT);  // Kategori seçimi

    $urun->name = $yeni_ad;
    $urun->adet = $yeni_adet;
    $urun->kategori_id = $kategori_id;  // Kategori bilgisini güncelle

    $DB->update_record('block_depo_yonetimi_urunler', $urun);

    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla güncellendi.', 2);
}

echo $OUTPUT->header();
?>

<form method="post">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

    <div class="form-group">
        <label for="name">Ürün Adı:</label>
        <input type="text" id="name" name="name" value="<?php echo s($urun->name); ?>" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="adet">Adet:</label>
        <input type="number" id="adet" name="adet" value="<?php echo s($urun->adet); ?>" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="kategori_id">Kategori Seçin:</label>
        <select id="kategori_id" name="kategori_id" class="form-control" required>
            <?php
            foreach ($kategoriler as $kategori) {
                echo "<option value='{$kategori->id}'" . ($kategori->id == $urun->kategori_id ? ' selected' : '') . ">{$kategori->name}</option>";
            }
            ?>
        </select>
    </div>

    <br>
    <button type="submit" name="submitbutton" class="btn btn-success">Kaydet</button>
    <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-secondary">Vazgeç</a>
</form>

<?php
echo $OUTPUT->footer();
?>
