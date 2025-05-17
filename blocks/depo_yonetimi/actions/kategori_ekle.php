<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Ekle');
$PAGE->set_heading('Kategori Ekle');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $kategori_adi = required_param('name', PARAM_TEXT);

    $kategori = new stdClass();
    $kategori->name = $kategori_adi;
    $kategori->timecreated = time();
    $kategori->timemodified = time();

    $DB->insert_record('block_depo_yonetimi_kategoriler', $kategori);
    \core\notification::success('Kategori başarıyla eklendi.');
    redirect(new moodle_url('/my', ['view' => 'kategoriler']));
}

echo $OUTPUT->header();
?>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-folder-plus me-2"></i>
                            <h5 class="mb-0">Yeni Kategori Ekle</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag me-2"></i>Kategori Adı
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Lütfen kategori adını girin.</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/my', ['view' => 'kategoriler']); ?>"
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
                    } else {
                        document.getElementById('loadingOverlay').style.display = 'flex'
                    }
                    form.classList.add('was-validated')
                }, false)
            })

            window.addEventListener('load', function() {
                document.getElementById('loadingOverlay').style.display = 'none'
            })
        })()
    </script>

<?php
echo $OUTPUT->footer();
?>