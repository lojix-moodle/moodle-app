// blocks/depo_yonetimi/actions/depo_haritasi.php
<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Sayfa parametreleri
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_haritasi.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('depoyerlesimplani', 'block_depo_yonetimi'));
$PAGE->set_heading(get_string('depoyerlesimplani', 'block_depo_yonetimi'));

// CSS ve JS
$PAGE->requires->css('/blocks/depo_yonetimi/styles/depo_haritasi.css');
$PAGE->requires->js_call_amd('block_depo_yonetimi/depo_haritasi', 'init');

// Sadece yetkili kullanıcılar erişebilir
require_login();
require_capability('block/depo_yonetimi:yonet', context_system::instance());

// Depo ID'sini al
$depo_id = optional_param('depo_id', 0, PARAM_INT);

// Depo bilgilerini ve yerleşim verilerini getir
$depo_verisi = array();
if ($depo_id > 0) {
    $depo = $DB->get_record('block_depo_yonetimi_depolar', array('id' => $depo_id), '*', MUST_EXIST);
    $yerlesim_verileri = $DB->get_records('block_depo_yonetimi_yerlesim', array('depo_id' => $depo_id));

    // Depodaki ürünleri al
    $sql = "SELECT y.*, u.ad AS urun_adi, u.stok_kodu 
            FROM {block_depo_yonetimi_yerlesim} y
            LEFT JOIN {block_depo_yonetimi_stok} s ON y.raf_kodu = s.raf_kodu
            LEFT JOIN {block_depo_yonetimi_urunler} u ON s.urun_id = u.id
            WHERE y.depo_id = :depo_id";
    $depo_verisi = $DB->get_records_sql($sql, array('depo_id' => $depo_id));
}

// Tüm depoları getir (dropdown için)
$depolar = $DB->get_records('block_depo_yonetimi_depolar');

echo $OUTPUT->header();
?>

<!-- Depo seçimi -->
<div class="mb-4">
    <form method="get" action="<?php echo $PAGE->url; ?>" class="form-inline">
        <div class="form-group mr-2">
            <label for="depo_id" class="mr-2">Depo Seçin:</label>
            <select name="depo_id" id="depo_id" class="form-control" onchange="this.form.submit()">
                <option value="">Depo Seçin</option>
                <?php foreach ($depolar as $d): ?>
                    <option value="<?php echo $d->id; ?>" <?php echo ($depo_id == $d->id) ? 'selected' : ''; ?>>
                        <?php echo format_string($d->ad); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Göster</button>
    </form>
</div>

<?php if ($depo_id > 0): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><?php echo format_string($depo->ad); ?> - Yerleşim Planı</h4>
            <button class="btn btn-sm btn-outline-primary" id="edit-mode-btn">Düzenleme Modunu Aç</button>
        </div>
        <div class="card-body">
            <div class="depo-haritasi-container">
                <div id="depo-haritasi" class="depo-haritasi" data-depo-id="<?php echo $depo_id; ?>">
                    <!-- Harita buraya JavaScript ile çizilecek -->
                </div>
            </div>

            <div class="mt-3 d-none" id="edit-panel">
                <h5>Raf Ekle/Düzenle</h5>
                <form id="raf-form" class="form-inline">
                    <input type="hidden" id="raf-id" name="id" value="0">
                    <input type="hidden" name="depo_id" value="<?php echo $depo_id; ?>">

                    <div class="form-group mr-2">
                        <label for="raf_kodu" class="mr-1">Raf Kodu:</label>
                        <input type="text" id="raf_kodu" name="raf_kodu" class="form-control form-control-sm" required>
                    </div>

                    <div class="form-group mr-2">
                        <label for="bolge" class="mr-1">Bölge:</label>
                        <input type="text" id="bolge" name="bolge" class="form-control form-control-sm">
                    </div>

                    <button type="submit" class="btn btn-sm btn-success">Kaydet</button>
                    <button type="button" id="cancel-btn" class="btn btn-sm btn-secondary ml-2">İptal</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Raf detayları kutusu -->
    <div id="raf-detay" class="card mt-3 d-none">
        <div class="card-header">
            <h5 class="mb-0">Raf Detayları: <span id="raf-baslik"></span></h5>
        </div>
        <div class="card-body">
            <div id="raf-urunler"></div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        Yerleşim planını görüntülemek için lütfen bir depo seçin.
    </div>
<?php endif; ?>

<?php
echo $OUTPUT->footer();
?>