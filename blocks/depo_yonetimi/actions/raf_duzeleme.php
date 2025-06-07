<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$rafid = optional_param('rafid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/raf_duzeleme.php', ['depoid' => $depoid, 'rafid' => $rafid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Raf Düzenleme');
$PAGE->set_heading('Raf Düzenleme');

// CSS dosyası
$PAGE->requires->css('/blocks/depo_yonetimi/assets/css/styles.css');

// Depo bilgisini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Form gönderildiğinde
if ($action === 'update' && confirm_sesskey()) {
    $response = new stdClass();

    try {
        $raf_adi = required_param('raf_adi', PARAM_TEXT);
        $bolum = required_param('bolum', PARAM_TEXT);
        $kapasite = required_param('kapasite', PARAM_INT);
        $durum = required_param('durum', PARAM_ALPHA);

        $data = new stdClass();
        $data->depoid = $depoid;
        $data->raf_adi = $raf_adi;
        $data->bolum = $bolum;
        $data->kapasite = $kapasite;
        $data->durum = $durum;
        $data->timemodified = time();

        if ($rafid) {
            // Güncelleme
            $data->id = $rafid;
            $DB->update_record('block_depo_yonetimi_raflar', $data);
            redirect(new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]),
                'Raf başarıyla güncellendi.', null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Yeni raf
            $data->timecreated = time();
            $rafid = $DB->insert_record('block_depo_yonetimi_raflar', $data);
            redirect(new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]),
                'Yeni raf başarıyla oluşturuldu.', null, \core\output\notification::NOTIFY_SUCCESS);
        }
    } catch (Exception $e) {
        redirect(new moodle_url('/blocks/depo_yonetimi/actions/raf_duzeleme.php',
            ['depoid' => $depoid, 'rafid' => $rafid]),
            'Hata: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Raf verisi (düzenleme için)
$raf = null;
if ($rafid) {
    $raf = $DB->get_record('block_depo_yonetimi_raflar', ['id' => $rafid, 'depoid' => $depoid]);
    if (!$raf) {
        redirect(new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]),
            'Raf bulunamadı.', null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
?>

    <!-- Modern CSS Kütüphaneleri -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
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
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background-color: white;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
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
    </style>

    <div class="container-fluid py-4">
        <!-- Ana Başlık -->
        <div class="app-header mb-5 fade-in animate__animated animate__fadeIn">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex justify-content-center align-items-center bg-white rounded-circle p-3" style="width: 70px; height: 70px">
                            <i class="bx bx-edit text-primary" style="font-size: 36px"></i>
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
                <div class="card shadow-sm rounded-3 border-0 animate__animated animate__fadeIn" style="animation-delay: 0.2s">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                <i class="bx bx-layer text-primary" style="font-size: 24px"></i>
                            </div>
                            <h5 class="mb-0 fw-bold"><?php echo $rafid ? 'Raf Bilgilerini Güncelle' : 'Yeni Raf Oluştur'; ?></h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form method="post" action="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_duzeleme.php'); ?>" id="rafForm" class="needs-validation" novalidate>
                            <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                            <input type="hidden" name="rafid" value="<?php echo $rafid; ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-4">
                                <label for="raf_adi" class="form-label text-muted small text-uppercase fw-semibold">Raf Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-server text-primary"></i></span>
                                    <input type="text" class="form-control form-control-lg" id="raf_adi" name="raf_adi"
                                           placeholder="Örn: A1 Rafı, B2 Rafı..."
                                           value="<?php echo $raf ? htmlspecialchars($raf->raf_adi) : ''; ?>" required>
                                    <div class="invalid-feedback">
                                        Lütfen raf adı girin.
                                    </div>
                                </div>
                                <small class="form-text text-muted">Rafı tanımlayan benzersiz bir isim kullanın.</small>
                            </div>

                            <div class="mb-4">
                                <label for="bolum" class="form-label text-muted small text-uppercase fw-semibold">Bağlı Olduğu Bölüm</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-cabinet text-primary"></i></span>
                                    <select class="form-select form-select-lg" id="bolum" name="bolum" required>
                                        <option value="" selected disabled>Bölüm Seçin</option>
                                        <option value="Tişört" <?php echo $raf && $raf->bolum == 'Tişört' ? 'selected' : ''; ?>>Tişört</option>
                                        <option value="Pantolon" <?php echo $raf && $raf->bolum == 'Pantolon' ? 'selected' : ''; ?>>Pantolon</option>
                                        <option value="Ayakkabı" <?php echo $raf && $raf->bolum == 'Ayakkabı' ? 'selected' : ''; ?>>Ayakkabı</option>
                                        <option value="Gömlek" <?php echo $raf && $raf->bolum == 'Gömlek' ? 'selected' : ''; ?>>Gömlek</option>
                                        <option value="Elbise" <?php echo $raf && $raf->bolum == 'Elbise' ? 'selected' : ''; ?>>Elbise</option>
                                        <option value="Ceket" <?php echo $raf && $raf->bolum == 'Ceket' ? 'selected' : ''; ?>>Ceket</option>
                                        <option value="Aksesuar" <?php echo $raf && $raf->bolum == 'Aksesuar' ? 'selected' : ''; ?>>Aksesuar</option>
                                        <option value="Çanta" <?php echo $raf && $raf->bolum == 'Çanta' ? 'selected' : ''; ?>>Çanta</option>
                                        <option value="İç Giyim" <?php echo $raf && $raf->bolum == 'İç Giyim' ? 'selected' : ''; ?>>İç Giyim</option>
                                        <option value="Diğer" <?php echo $raf && $raf->bolum == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Lütfen bir bölüm seçin.
                                    </div>
                                </div>
                                <small class="form-text text-muted">Rafın hangi bölümde olduğunu belirtin.</small>
                            </div>

                            <div class="mb-4">
                                <label for="kapasite" class="form-label text-muted small text-uppercase fw-semibold">Raf Kapasitesi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-box text-primary"></i></span>
                                    <input type="number" class="form-control form-control-lg" id="kapasite" name="kapasite"
                                           min="1" max="1000" placeholder="Örn: 50, 100..."
                                           value="<?php echo $raf ? htmlspecialchars($raf->kapasite) : '50'; ?>" required>
                                    <div class="invalid-feedback">
                                        Lütfen geçerli bir kapasite değeri girin (1-1000 arası).
                                    </div>
                                </div>
                                <small class="form-text text-muted">Rafın maksimum ürün kapasitesini belirtin.</small>
                            </div>

                            <div class="mb-4">
                                <label for="durum" class="form-label text-muted small text-uppercase fw-semibold">Raf Durumu</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bx bx-check-circle text-primary"></i></span>
                                    <select class="form-select form-select-lg" id="durum" name="durum" required>
                                        <option value="aktif" <?php echo $raf && $raf->durum == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="bakimda" <?php echo $raf && $raf->durum == 'bakimda' ? 'selected' : ''; ?>>Bakımda</option>
                                        <option value="dolu" <?php echo $raf && $raf->durum == 'dolu' ? 'selected' : ''; ?>>Dolu</option>
                                        <option value="pasif" <?php echo $raf && $raf->durum == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Lütfen raf durumu seçin.
                                    </div>
                                </div>
                                <small class="form-text text-muted">Rafın mevcut kullanım durumunu belirtin.</small>
                            </div>

                            <div class="d-flex justify-content-end mt-5 border-top pt-4">
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>"
                                   class="btn btn-light me-2">
                                    <i class="bx bx-x me-1"></i> İptal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-check me-1"></i> <?php echo $rafid ? 'Değişiklikleri Kaydet' : 'Raf Oluştur'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap ve Form Doğrulama -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form doğrulama
            const form = document.getElementById('rafForm');

            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                });
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>