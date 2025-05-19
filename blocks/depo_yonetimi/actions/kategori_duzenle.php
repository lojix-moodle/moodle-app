<?php
// Hataları gösterelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');

require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametreleri al - URL'de id kullanıldığını göz önüne alarak
$kategoriid = required_param('id', PARAM_INT);

// Kategori var mı kontrol et
$kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $kategoriid], '*', MUST_EXIST);

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['id' => $kategoriid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Düzenle');
$PAGE->set_heading('Kategori Düzenle');

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $yeni_ad = required_param('name', PARAM_TEXT);

    // Aynı isimde başka bir kategori var mı diye kontrol et (kendi ID'si hariç)
    $mevcut_kategori = $DB->get_record_sql(
        "SELECT * FROM {block_depo_yonetimi_kategoriler} WHERE name = ? AND id != ?",
        [$yeni_ad, $kategoriid]
    );

    if ($mevcut_kategori) {
        // Aynı isimde kategori zaten var, hata mesajı göster
        \core\notification::error('Bu isimde bir kategori zaten mevcut. Lütfen farklı bir isim girin.');
        redirect(new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['id' => $kategoriid]));
    }

    $kategori->name = $yeni_ad;
    $kategori->timemodified = time();

    $DB->update_record('block_depo_yonetimi_kategoriler', $kategori);

    // Başarılı mesajı ile ana sayfaya yönlendir
    redirect(new moodle_url('/my', ['view' => 'kategoriler']), 'Kategori başarıyla güncellendi.', null, \core\output\notification::NOTIFY_SUCCESS);
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
                            <i class="fas fa-edit me-2"></i>
                            <h5 class="mb-0">Kategori Düzenle</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag me-2"></i>Kategori Adı
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="<?php echo s($kategori->name); ?>"
                                           placeholder="Kategori adını girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen kategori adını girin.</div>
                                <small class="form-text text-muted">Ürünleri sınıflandırmak için kullanılacak kategori adını girin</small>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                </button>
                                <a href="<?php echo new moodle_url('/my', ['view' => 'kategoriler']); ?>"
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
            var nameInput = document.getElementById('name')
            var originalName = '<?php echo addslashes($kategori->name); ?>'

            // Sayfa yüklendiğinde loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none'
            })

            // Kategori adı için AJAX ile kontrol yapma
            nameInput.addEventListener('blur', function() {
                var kategoriAdi = nameInput.value.trim();

                // İsim değişmediyse kontrol etmeye gerek yok
                if (kategoriAdi === originalName) {
                    nameInput.setCustomValidity('');
                    nameInput.classList.remove('is-invalid');
                    return;
                }

                if (kategoriAdi !== '') {
                    // AJAX isteği gönder - kategoriid parametresini de ekleyerek
                    fetch('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/check_kategori.php?name=' +
                        encodeURIComponent(kategoriAdi) + '&exclude_id=<?php echo $kategoriid; ?>')
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                nameInput.setCustomValidity('Bu isimde bir kategori zaten mevcut.');
                                nameInput.classList.remove('is-valid');
                                nameInput.classList.add('is-invalid');
                                // Özel hata mesajını göster
                                var invalidFeedback = nameInput.parentNode.parentNode.querySelector('.invalid-feedback');
                                invalidFeedback.textContent = 'Bu isimde bir kategori zaten mevcut. Lütfen farklı bir isim girin.';
                            } else {
                                nameInput.setCustomValidity('');
                                if (nameInput.checkValidity()) {
                                    nameInput.classList.remove('is-invalid');
                                    nameInput.classList.add('is-valid');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Kategori kontrolü sırasında hata:', error);
                        });
                }
            });

            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()

                        // Geçersiz alanları işaretle
                        var inputs = form.querySelectorAll('input, select, textarea');
                        Array.prototype.slice.call(inputs).forEach(function(input) {
                            if (!input.checkValidity()) {
                                input.classList.add('is-invalid')
                            }
                        })
                    } else {
                        // Form geçerli ise - isim değişmemişse direkt submit et
                        if (nameInput.value.trim() === originalName) {
                            loadingOverlay.style.display = 'flex';
                            submitBtn.disabled = true;
                            return true;
                        }

                        // Form geçerli ise, kategori adını bir kez daha kontrol et
                        event.preventDefault();

                        fetch('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/check_kategori.php?name=' +
                            encodeURIComponent(nameInput.value.trim()) + '&exclude_id=<?php echo $kategoriid; ?>')
                            .then(response => response.json())
                            .then(data => {
                                if (data.exists) {
                                    nameInput.setCustomValidity('Bu isimde bir kategori zaten mevcut.');
                                    nameInput.classList.remove('is-valid');
                                    nameInput.classList.add('is-invalid');
                                    var invalidFeedback = nameInput.parentNode.parentNode.querySelector('.invalid-feedback');
                                    invalidFeedback.textContent = 'Bu isimde bir kategori zaten mevcut. Lütfen farklı bir isim girin.';
                                    form.classList.add('was-validated');
                                } else {
                                    // Form geçerli ve kategori adı benzersiz ise yükleme animasyonunu göster
                                    loadingOverlay.style.display = 'flex';
                                    submitBtn.disabled = true;
                                    form.submit();
                                }
                            })
                            .catch(error => {
                                console.error('Kategori kontrolü sırasında hata:', error);
                                // Hata durumunda formun gönderilmesine izin ver
                                form.submit();
                            });
                    }

                    form.classList.add('was-validated');
                }, false)
            })
        })()
    </script>

<?php
echo $OUTPUT->footer();
?>