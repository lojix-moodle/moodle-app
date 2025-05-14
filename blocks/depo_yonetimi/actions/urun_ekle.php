<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT;

$depoid = required_param('depoid', PARAM_INT);

// BU İKİ SATIR ŞART!!!
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance()); // veya doğru context (örneğin depo context'i varsa)

$PAGE->set_title('Ürün Ekle');
$PAGE->set_heading('Ürün Ekle');

$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $adet = required_param('adet', PARAM_INT);
    $kategoriid = required_param('kategoriid', PARAM_INT);

    // Kayıt ekle
    $urun = new stdClass();
    $urun->depoid = $depoid;
    $urun->name = $name;
    $urun->adet = $adet;
    $urun->kategoriid = $kategoriid;

    $DB->insert_record('block_depo_yonetimi_urunler', $urun);

    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla eklendi.');
}

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Ürün Ekle');

?>

<form method="post">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
    <div class="form-group">
        <label for="kategoriid">Kategori :</label>
        <select name="kategoriid" id="kategoriid">
            <?php

            foreach ($kategoriler as $kategori) {
                echo '<option value="' . $kategori->id . '">' . $kategori->name . '</option>';
            }

            ?>
        </select>
    </div>
    <div class="form-group">
        <label>Ürün Adı:</label>
        <input type="text" name="name" required>
    </div>
    <div class="form-group">
        <label>Adet:</label>
        <input type="text" name="adet" required>
    </div>
    <br>
    <button type="submit" name="submitbutton" class="btn btn-success">Kaydet</button>
    <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-secondary">Vazgeç</a>
</form>
<?php
echo $OUTPUT->footer();
?>