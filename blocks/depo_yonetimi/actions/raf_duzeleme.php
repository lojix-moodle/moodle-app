<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$rafid = optional_param('rafid', 0, PARAM_INT);
$bolum = optional_param('bolum', '', PARAM_TEXT);
$raf = optional_param('raf', '', PARAM_TEXT);
$kapasite = optional_param('kapasite', 100, PARAM_INT);
$aciklama = optional_param('aciklama', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/raf_duzeleme.php', [
    'depoid' => $depoid,
    'rafid' => $rafid
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title($rafid ? 'Raf Düzenle' : 'Yeni Raf Ekle');
$PAGE->set_heading($rafid ? 'Raf Düzenle' : 'Yeni Raf Ekle');

// CSS dosyası doğru şekilde yükleniyor
$PAGE->requires->css('/blocks/depo_yonetimi/assets/css/styles.css');

// Depo bilgisini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Eğer rafid mevcutsa, raf bilgilerini getir
$raf_detay = null;
if ($rafid) {
    $raf_detay = $DB->get_record('block_depo_yonetimi_raflar', ['id' => $rafid, 'depoid' => $depoid]);
    if ($raf_detay) {
        $bolum = $raf_detay->bolum;
        $raf = $raf_detay->rafkodu;
        $kapasite = $raf_detay->kapasite;
        $aciklama = $raf_detay->aciklama;
    }
}

// Form gönderildiğinde işleme mantığı
if ($data = data_submitted() && confirm_sesskey()) {
    $bolum = required_param('bolum', PARAM_TEXT);
    $raf = required_param('raf', PARAM_TEXT);
    $kapasite = required_param('kapasite', PARAM_INT);
    $aciklama = optional_param('aciklama', '', PARAM_TEXT);

    $hata = false;
    $mesaj = "";

    // Temel doğrulama
    if (empty($bolum)) {
        $hata = true;
        $mesaj = "Bölüm adı boş olamaz.";
    } else if (empty($raf)) {
        $hata = true;
        $mesaj = "Raf kodu boş olamaz.";
    }

    // Aynı kodlu raf var mı kontrolü
    $raf_kontrol_params = ['depoid' => $depoid, 'bolum' => $bolum, 'rafkodu' => $raf];
    if ($rafid) {
        $raf_kontrol_params['id'] = $rafid;
    }

    $raf_var = $DB->record_exists_select(
        'block_depo_yonetimi_raflar',
        'depoid = :depoid AND bolum = :bolum AND rafkodu = :rafkodu' . ($rafid ? ' AND id <> :id' : ''),
        $raf_kontrol_params
    );

    if ($raf_var) {
        $hata = true;
        $mesaj = "Bu bölümde aynı kodlu bir raf zaten bulunmaktadır.";
    }

    if (!$hata) {
        try {
            // Raf kaydı oluştur veya güncelle
            $raf_kayit = new stdClass();
            $raf_kayit->depoid = $depoid;
            $raf_kayit->bolum = $bolum;
            $raf_kayit->rafkodu = $raf;
            $raf_kayit->kapasite = $kapasite;
            $raf_kayit->aciklama = $aciklama;
            $raf_kayit->duzenleyen = $USER->id;
            $raf_kayit->duzenlemetarihi = time();

            if ($rafid) {
                // Güncelleme
                $raf_kayit->id = $rafid;
                $DB->update_record('block_depo_yonetimi_raflar', $raf_kayit);
                $message = "Raf bilgileri başarıyla güncellendi.";
            } else {
                // Yeni kayıt
                $raf_kayit->olusturan = $USER->id;
                $raf_kayit->olusturmatarihi = time();
                $DB->insert_record('block_depo_yonetimi_raflar', $raf_kayit);
                $message = "Yeni raf başarıyla eklendi.";
            }

            redirect(
                new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]),
                $message,
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );

        } catch (Exception $e) {
            $hata = true;
            $mesaj = "İşlem sırasında bir hata oluştu: " . $e->getMessage();
        }
    }
}

// Sayfa çıktısı başlat
echo $OUTPUT->header();
?>

    <!-- Modern CSS Kütüphaneleri -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">

    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --primary-dark: #1e40af;
            --secondary: #475569;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --border-radius: 0.5rem;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .app-header {
            background-image: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }

        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            padding: 0.625rem 1rem;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.625rem 0.75rem;
            border-color: var(--gray-300);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.1);
        }

        .input-group-text {
            background-color: var(--gray-100);
            border-color: var(--gray-300);
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .is-invalid {
            border-color: var(--danger) !important;
        }

        .invalid-feedback {
            color: var(--danger);
            display: block;
            margin-top: 0.25rem;
            font-size: 0.875em;
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Ana Başlık -->
        <div class="app-header mb-5 fade-in animate__animated animate__fadeIn">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex justify-content-center align-items-center bg-white rounded-circle p-3" style="width: 70px; height: 70px">
                            <i class="bx bx-layer text-primary" style="font-size: 36px"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h1 class="display-6 fw-bold mb-0"><?php echo $rafid ? 'Raf Düzenle' : 'Yeni Raf Ekle'; ?></h1>
                        <p class="lead mb-0 opacity-75"><?php echo htmlspecialchars($depo->name); ?> Depo Yönetimi</p>
                    </div>
                    <div class="col-auto">
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>" class="btn btn-light">
                            <i class="bx bx-arrow-back me-2"></i>Raf Yönetimine Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raf Düzenleme Formu -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-3 animate__animated animate__fadeIn" style="animation-delay: 0.2s">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center">
                        <span class="p-2 rounded-circle bg-primary-subtle me-3">
                            <i class="bx bx-server text-primary"></i>
                        </span>
                            <h5 class="fw-bold mb-0"><?php echo $rafid ? 'Raf Bilgilerini Güncelle' : 'Yeni Raf Bilgileri'; ?></h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($hata) && $hata): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bx bx-error-circle me-2"></i><?php echo $mesaj; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="rafForm">
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                            <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                            <?php if ($rafid): ?>
                                <input type="hidden" name="rafid" value="<?php echo $rafid; ?>">
                            <?php endif; ?>

                            <div class="mb-4">
                                <label for="bolum" class="form-label text-muted small text-uppercase fw-semibold">Bölüm Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-cabinet text-primary"></i></span>
                                    <select class="form-select form-select-lg" id="bolum" name="bolum" required>
                                        <option value="">-- Bölüm Seçin --</option>
                                        <option value="Tişört" <?php echo $bolum === 'Tişört' ? 'selected' : ''; ?>>Tişört</option>
                                        <option value="Pantolon" <?php echo $bolum === 'Pantolon' ? 'selected' : ''; ?>>Pantolon</option>
                                        <option value="Ayakkabı" <?php echo $bolum === 'Ayakkabı' ? 'selected' : ''; ?>>Ayakkabı</option>
                                        <option value="Gömlek" <?php echo $bolum === 'Gömlek' ? 'selected' : ''; ?>>Gömlek</option>
                                        <option value="Elbise" <?php echo $bolum === 'Elbise' ? 'selected' : ''; ?>>Elbise</option>
                                        <option value="Ceket" <?php echo $bolum === 'Ceket' ? 'selected' : ''; ?>>Ceket</option>
                                        <option value="Aksesuar" <?php echo $bolum === 'Aksesuar' ? 'selected' : ''; ?>>Aksesuar</option>
                                        <option value="Çanta" <?php echo $bolum === 'Çanta' ? 'selected' : ''; ?>>Çanta</option>
                                        <option value="İç Giyim" <?php echo $bolum === 'İç Giyim' ? 'selected' : ''; ?>>İç Giyim</option>
                                    </select>
                                </div>
                                <div class="invalid-feedback">Lütfen bir bölüm seçin</div>
                            </div>

                            <div class="mb-4">
                                <label for="raf" class="form-label text-muted small text-uppercase fw-semibold">Raf Kodu</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-layer text-primary"></i></span>
                                    <input type="text" class="form-control form-control-lg" id="raf" name="raf"
                                           required placeholder="Örn: A1 Rafı" value="<?php echo htmlspecialchars($raf); ?>">
                                </div>
                                <div class="invalid-feedback">Lütfen bir raf kodu girin</div>
                            </div>

                            <div class="mb-4">
                                <label for="kapasite" class="form-label text-muted small text-uppercase fw-semibold">Kapasite</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-cube text-primary"></i></span>
                                    <input type="number" class="form-control form-control-lg" id="kapasite" name="kapasite"
                                           min="1" max="1000" required value="<?php echo $kapasite; ?>">
                                    <span class="input-group-text bg-white">birim</span>
                                </div>
                                <small class="text-muted">Rafın maksimum kapasitesi</small>
                            </div>

                            <div class="mb-4">
                                <label for="aciklama" class="form-label text-muted small text-uppercase fw-semibold">Açıklama</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-info-circle text-primary"></i></span>
                                    <textarea class="form-control" id="aciklama" name="aciklama" rows="3"
                                              placeholder="Raf hakkında ek bilgiler..."><?php echo htmlspecialchars($aciklama); ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-5">
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-2"></i>İptal
                                </a>
                                <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                                    <i class="bx bx-save me-2"></i><?php echo $rafid ? 'Güncelle' : 'Kaydet'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($rafid): ?>
                    <div class="text-center mt-4">
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_sil.php', ['rafid' => $rafid, 'depoid' => $depoid]); ?>"
                           class="btn btn-link text-danger"
                           onclick="return confirm('Bu rafı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                            <i class="bx bx-trash me-1"></i>Bu rafı sil
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('rafForm');
            const submitBtn = document.getElementById('submitBtn');

            // Form doğrulama
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Geçersiz alanları işaretle
                    Array.from(form.elements).forEach(input => {
                        if (input.type !== 'hidden' && input.type !== 'submit' && !input.checkValidity()) {
                            input.classList.add('is-invalid');
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });

                    // Hata mesajı göster
                    Swal.fire({
                        icon: 'error',
                        title: 'Form Hatası',
                        text: 'Lütfen tüm zorunlu alanları doldurun!',
                        confirmButtonText: 'Tamam',
                        confirmButtonColor: '#3e64ff'
                    });
                } else {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bx bx-loader-circle bx-spin me-2"></i>İşleniyor...';
                }

                form.classList.add('was-validated');
            });

            // Alan değişikliğinde doğrulama durumunu güncelle
            Array.from(form.elements).forEach(input => {
                if (input.type !== 'hidden' && input.type !== 'submit') {
                    input.addEventListener('change', function() {
                        if (this.checkValidity()) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                        }
                    });
                }
            });
        });
    </script>

<?php
echo $OUTPUT->footer();
?>