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

    <style>
        .form-control, .form-select {
            border-color: #dee2e6 !important;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0f6cbf;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
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