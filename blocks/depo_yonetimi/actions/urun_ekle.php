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

    // Renk ve boyut verilerini al (boş olabilir)
    $colors = optional_param_array('colors', [], PARAM_TEXT);
    $sizes = optional_param_array('sizes', [], PARAM_TEXT);

    // JSON'a dönüştür
    $colors_json = json_encode($colors);
    $sizes_json = json_encode($sizes);

    $urun = new stdClass();
    $urun->depoid = $depoid;
    $urun->name = $name;
    $urun->adet = $adet;
    $urun->kategoriid = $kategoriid;
    $urun->colors = $colors_json;
    $urun->sizes = $sizes_json;

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

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .card {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .btn {
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-box-open me-2"></i>
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
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                    <select name="kategoriid" id="kategoriid" class="form-select" required>
                                        <option value="">Kategori Seçiniz</option>
                                        <?php foreach ($kategoriler as $kategori): ?>
                                            <option value="<?php echo $kategori->id; ?>">
                                                <?php echo htmlspecialchars($kategori->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="invalid-feedback">Lütfen bir kategori seçin.</div>
                                <small class="form-text text-muted">Ürünün ait olduğu kategoriyi seçin</small>
                            </div>

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-box me-2"></i>Ürün Adı
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <input type="text" class="form-control" id="name" name="name"
                                           placeholder="Ürün adını girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen ürün adını girin.</div>
                                <small class="form-text text-muted">Depoya eklemek istediğiniz ürünün adını girin</small>
                            </div>

                            <div class="mb-4">
                                <label for="adet" class="form-label">
                                    <i class="fas fa-hashtag me-2"></i>Adet
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                                    <input type="number" class="form-control" id="adet" name="adet"
                                           min="0" placeholder="Ürün adedini girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen geçerli bir adet girin.</div>
                                <small class="form-text text-muted">Depoya eklemek istediğiniz ürünün miktarını girin</small>
                            </div>

                            <div class="mb-4">
                                <label for="colors" class="form-label">
                                    <i class="fas fa-palette me-2"></i>Renkler
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                    <select multiple class="form-control" id="colors" name="colors[]" size="4">
                                        <option value="kirmizi">Kırmızı</option>
                                        <option value="mavi">Mavi</option>
                                        <option value="siyah">Siyah</option>
                                        <option value="beyaz">Beyaz</option>
                                        <option value="yesil">Yeşil</option>
                                        <option value="sari">Sarı</option>
                                        <option value="turuncu">Turuncu</option>
                                        <option value="mor">Mor</option>
                                        <option value="pembe">Pembe</option>
                                        <option value="gri">Gri</option>
                                    </select>
                                </div>
                                <div class="form-text text-muted">
                                    <small>Birden fazla renk seçmek için CTRL tuşuna basılı tutarak seçim yapabilirsiniz</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="sizes" class="form-label">
                                    <i class="fas fa-ruler-combined me-2"></i>Boyutlar
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-expand-arrows-alt"></i></span>
                                    <select multiple class="form-control" id="sizes" name="sizes[]" size="4">
                                        <option value="xs">XS</option>
                                        <option value="s">S</option>
                                        <option value="m">M</option>
                                        <option value="l">L</option>
                                        <option value="xl">XL</option>
                                        <option value="xxl">XXL</option>
                                        <option value="xxxl">XXXL</option>
                                    </select>
                                </div>
                                <div class="form-text text-muted">
                                    <small>Birden fazla boyut seçmek için CTRL tuşuna basılı tutarak seçim yapabilirsiniz</small>
                                </div>
                            </div>


                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Ürünü Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>"
                                   class="btn btn-outline-secondary ms-auto">
                                    <i class="fas fa-arrow-left me-2"></i>Geri
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

            // Form doğrulama
            var forms = document.querySelectorAll('.needs-validation')
            var loadingOverlay = document.getElementById('loadingOverlay')
            var submitBtn = document.getElementById('submitBtn')

            // Sayfa yüklendiğinde loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none'
            })

            Array.prototype.slice.call(forms).forEach(function (form) {
                // Dinamik doğrulama - alan değiştiğinde
                var inputs = form.querySelectorAll('input, select')
                Array.prototype.slice.call(inputs).forEach(function(input) {
                    input.addEventListener('change', function() {
                        // Geçerlilik kontrolü
                        if (input.checkValidity()) {
                            input.classList.remove('is-invalid')
                            input.classList.add('is-valid')
                        } else {
                            input.classList.remove('is-valid')
                            input.classList.add('is-invalid')
                        }
                    })
                })

                // Form gönderildiğinde
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()

                        // Geçersiz alanları işaretle
                        Array.prototype.slice.call(inputs).forEach(function(input) {
                            if (!input.checkValidity()) {
                                input.classList.add('is-invalid')
                            }
                        })
                    } else {
                        // Form geçerli ise yükleme animasyonunu göster
                        loadingOverlay.style.display = 'flex'
                        submitBtn.disabled = true
                    }

                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>

<?php
echo $OUTPUT->footer();
?>