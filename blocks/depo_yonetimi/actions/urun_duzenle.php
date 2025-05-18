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
$PAGE->set_pagelayout('admin');

// Gömülü CSS
$PAGE->requires->css(new moodle_url('/lib/jquery/themes/base/jquery.ui.all.css'));
$PAGE->requires->js_call_amd('block_depo_yonetimi/validation', 'init');

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
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');

if (!$urun) {
    print_error('Ürün bulunamadı.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $yeni_ad = required_param('name', PARAM_TEXT);
    $yeni_adet = required_param('adet', PARAM_INT);
    $kategoriid = required_param('kategoriid', PARAM_INT);
    $stok_miktari = required_param('stok_miktari', PARAM_INT);

    $urun->name = $yeni_ad;
    $urun->adet = $yeni_adet;
    $urun->kategoriid = $kategoriid;
    $urun->stok_miktari = $stok_miktari;
    $DB->update_record('block_depo_yonetimi_urunler', $urun);

    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla güncellendi.', null, \core\output\notification::NOTIFY_SUCCESS);
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
                            <i class="fas fa-box me-2"></i>
                            <h5 class="mb-0">Ürün Düzenle</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="kategoriid" class="form-label">
                                    <i class="fas fa-tags me-2"></i>Kategori
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-list-ul"></i></span>
                                    <select name="kategoriid" id="kategoriid" class="form-select" required>
                                        <?php foreach ($kategoriler as $kategori): ?>
                                            <option value="<?php echo $kategori->id; ?>" <?php echo ($kategori->id == $urun->kategoriid) ? 'selected' : ''; ?>>
                                                <?php echo s($kategori->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="invalid-feedback">Lütfen bir kategori seçin</div>
                            </div>

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-box-open me-2"></i>Ürün Adı
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <input type="text" id="name" name="name" value="<?php echo s($urun->name); ?>"
                                           class="form-control" placeholder="Ürün adını girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen ürün adını girin</div>
                            </div>

                            <div class="mb-4">
                                <label for="adet" class="form-label">
                                    <i class="fas fa-sort-numeric-up me-2"></i>Adet
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    <input type="number" id="adet" name="adet" value="<?php echo s($urun->adet); ?>"
                                           class="form-control" placeholder="Ürün adedini girin" required min="0">
                                </div>
                                <div class="invalid-feedback">Lütfen geçerli bir adet girin</div>
                            </div>

                            <div class="mb-4">
                                <label for="stok_miktari" class="form-label">
                                    <i class="fas fa-warehouse me-2"></i>Stok Miktarı
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                    <input type="number" id="stok_miktari" name="stok_miktari"
                                           value="<?php echo isset($urun) ? s($urun->stok_miktari) : '0'; ?>"
                                           class="form-control" placeholder="Stok miktarını girin" required min="0">
                                </div>
                                <div class="invalid-feedback">Lütfen geçerli bir stok miktarı girin</div>
                                <small class="text-muted">Mevcut toplam stok miktarını giriniz</small>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" name="submitbutton" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>"
                                   class="btn btn-outline-secondary ms-auto">
                                    <i class="fas fa-arrow-left me-2"></i>Vazgeç
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