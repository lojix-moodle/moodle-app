<?php

require_once(__DIR__ . '/../../../config.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

$context = context_system::instance();
require_capability('block/depo_yonetimi:viewall', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_title('Ürün Düzenle');
$PAGE->set_heading('Ürün Düzenle');

$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid], '*', MUST_EXIST);
$kategoriler = $DB->get_records('depo_yonetimi_kategoriler', null, 'name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $urun->name = required_param('name', PARAM_TEXT);
    $urun->adet = required_param('adet', PARAM_INT);
    $urun->kategori_id = required_param('kategori_id', PARAM_INT);

    if ($DB->update_record('block_depo_yonetimi_urunler', $urun)) {
        redirect(new moodle_url('/blocks/depo_yonetimi/liste_urunler.php', ['depoid' => $depoid]), 'Ürün başarıyla güncellendi.', 2);
    } else {
        print_error('Güncelleme başarısız oldu.');
    }
}

echo $OUTPUT->header();
?>

    <h3>Ürünü Güncelle</h3>

    <form method="post">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <div class="form-group">
            <label for="name">Ürün Adı:</label>
            <input type="text" name="name" id="name" value="<?php echo s($urun->name); ?>" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="adet">Adet:</label>
            <input type="number" name="adet" id="adet" value="<?php echo $urun->adet; ?>" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="kategori_id">Kategori:</label>
            <select name="kategori_id" id="kategori_id" class="form-control" required>
                <?php foreach ($kategoriler as $kategori): ?>
                    <option value="<?php echo $kategori->id; ?>" <?php if ($kategori->id == $urun->kategori_id) echo 'selected'; ?>>
                        <?php echo format_string($kategori->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <br>
        <button type="submit" class="btn btn-success">Kaydet</button>
        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/liste_urunler.php', ['depoid' => $depoid]); ?>" class="btn btn-secondary">Vazgeç</a>
    </form>

<?php
echo $OUTPUT->footer();
