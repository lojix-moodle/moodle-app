<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$depoid = required_param('depoid', PARAM_INT);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Ekle');
$PAGE->set_heading('Ürün Ekle');

// Depo bilgisini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $adet = required_param('adet', PARAM_INT);
    $kategoriid = required_param('kategoriid', PARAM_INT);

    $urun = new stdClass();
    $urun->depoid = $depoid;
    $urun->name = $name;
    $urun->adet = $adet;
    $urun->kategoriid = $kategoriid;

    $DB->insert_record('block_depo_yonetimi_urunler', $urun);
    \core\notification::success('Ürün başarıyla eklendi.');
    redirect(new moodle_url('/my', ['depo' => $depoid]));
}

echo $OUTPUT->header();
?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-secondary text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-plus-circle me-2"></i>
                            <h5 class="mb-0">Yeni Ürün Ekle</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-warehouse me-2"></i>
                                <span>Depo: <strong><?php echo htmlspecialchars($depo->name); ?></strong></span>
                            </div>
                        </div>

                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="kategoriid" class="form-label">
                                    <i class="fas fa-tags me-2"></i>Kategori
                                </label>
                                <select name="kategoriid" id="kategoriid" class="form-select" required>
                                    <option value="">Kategori Seçiniz</option>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <option value="<?php echo $kategori->id; ?>">
                                            <?php echo htmlspecialchars($kategori->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Lütfen bir kategori seçin.</div>
                            </div>

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-box me-2"></i>Ürün Adı
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Lütfen ürün adını girin.</div>
                            </div>

                            <div class="mb-4">
                                <label for="adet" class="form-label">
                                    <i class="fas fa-hashtag me-2"></i>Adet
                                </label>
                                <input type="number" class="form-control" id="adet" name="adet" min="0" required>
                                <div class="invalid-feedback">Lütfen geçerli bir adet girin.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Vazgeç
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>

<?php
echo $OUTPUT->footer();
?>