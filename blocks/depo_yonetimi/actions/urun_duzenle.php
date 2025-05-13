<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Düzenle');
$PAGE->set_heading('Ürün Düzenle');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);

if (!$is_admin) {
    $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
    if (!$user_depo || $user_depo != $depoid) {
        print_error('Erişim izniniz yok.');
    }
}

// Ürünü al
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
if (!$urun) {
    print_error('Ürün bulunamadı.');
}

// Kategori verisi
$kategoriler = $DB->get_records('depo_yonetimi_kategoriler', null, 'name ASC');

// Form gönderildiyse güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $urun->name = required_param('name', PARAM_TEXT);
    $urun->adet = required_param('adet', PARAM_INT);
    $urun->kategori_id = required_param('kategori_id', PARAM_INT);

    if ($DB->update_record('block_depo_yonetimi_urunler', $urun)) {
        redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla güncellendi.', 2);
    } else {
        print_error('Güncelleme başarısız oldu.');
    }
}

echo $OUTPUT->header();
?>

<h3>Ürün Güncelle</h3>

<form method="post">
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

    <label>Ürün Adı:</label><br>
    <input type="text" name="name" value="<?php echo s($urun->name); ?>" required><br><br>

    <label>Adet:</label><br>
    <input type="number" name="adet" value="<?php echo s($urun->adet); ?>" required><br><br>

    <label>Kategori:</label><br>
    <select name="kategori_id" required>
        <?php foreach ($kategoriler as $kategori): ?>
            <option value="<?php echo $kategori->id; ?>" <?php if ($kategori->id == $urun->kategori_id) echo 'selected'; ?>>
                <?php echo format_string($kategori->name); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Kaydet</button>
    <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>">Vazgeç</a>
</form>

<hr>

<h4>Ürünler Listesi</h4>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
    <tr>
        <th>Ürün Adı</th>
        <th>Adet</th>
        <th>İşlemler</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);
    foreach ($urunler as $urun_item):
        $duzenle_url = new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'urunid' => $urun_item->id]);
        $sil_url = new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', ['depoid' => $depoid, 'urunid' => $urun_item->id]);
        ?>
        <tr>
            <td><?php echo s($urun_item->name); ?></td>
            <td><?php echo s($urun_item->adet); ?></td>
            <td>
                <a href="<?php echo $duzenle_url; ?>">Düzenle</a> |
                <a href="<?php echo $sil_url; ?>" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php echo $OUTPUT->footer(); ?>
