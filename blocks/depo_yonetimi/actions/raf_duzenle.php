<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

// Parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

// Sayfayı yapılandır
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/raf_duzenle.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Raf ve Bölüm Düzenle');
$PAGE->set_heading('Raf ve Bölüm Düzenle');

// CSS dosyalarını ekle - Moodle API kullanarak
$PAGE->requires->css('/blocks/depo_yonetimi/assets/css/styles.css');
// CDN kullanarak Bootstrap ve diğer CSS'leri ekle
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css'));
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css'));

// JavaScript bağımlılıkları (Bootstrap JS için)
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js'), true);

// Ürün ve depo bilgisini al
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid], '*', MUST_EXIST);
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);
$kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $urun->kategoriid]);

// Form işleme
$message = '';
$message_type = '';

if (optional_param('islem', '', PARAM_ALPHA) === 'kaydet' && confirm_sesskey()) {
    $yeni_bolum = optional_param('bolum', '', PARAM_TEXT);
    $yeni_raf = optional_param('raf', '', PARAM_TEXT);

    try {
        // Veritabanında güncelleme yap
        $urun->bolum = $yeni_bolum;
        $urun->raf = $yeni_raf;
        $DB->update_record('block_depo_yonetimi_urunler', $urun);

        // Başarı mesajı
        $message = 'Raf ve bölüm bilgileri başarıyla güncellendi.';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Hata: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Sayfa çıktısı
echo $OUTPUT->header();
?>

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
            --border-radius: 0.5rem;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .app-header {
            background-image: linear-gradient(135deg, var(--primary-dark), var(--primary));
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.625rem 0.75rem;
            border-color: var(--gray-300);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: var(--gray-100);
            border-color: var(--gray-300);
        }

        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            padding: 0.625rem 1rem;
        }

        .product-info {
            padding: 1rem;
            background-color: var(--gray-100);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .product-info .product-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-info .detail {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .product-info .detail i {
            margin-right: 0.5rem;
            color: var(--primary);
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="container py-4">
        <!-- Ana Başlık -->
        <div class="app-header mb-5 animate-fade-in">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex justify-content-center align-items-center bg-white rounded-circle p-3" style="width: 70px; height: 70px">
                            <i class="bx bx-edit text-primary" style="font-size: 36px"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h1 class="display-6 fw-bold mb-0">Raf ve Bölüm Düzenle</h1>
                        <p class="lead mb-0 opacity-75"><?php echo htmlspecialchars($depo->name); ?> - Ürün Konumu Düzenleme</p>
                    </div>
                    <div class="col-auto">
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>" class="btn btn-light">
                            <i class="bx bx-arrow-back me-2"></i>Raf Yönetimine Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show mb-4" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4 animate-fade-in">
                    <div class="card-body p-4">
                        <!-- Ürün Bilgisi -->
                        <div class="product-info mb-4">
                            <h3 class="product-title"><?php echo htmlspecialchars($urun->name); ?></h3>
                            <div class="detail">
                                <i class="bx bx-barcode"></i>
                                <span><?php echo !empty($urun->barkod) ? htmlspecialchars($urun->barkod) : 'Barkod belirtilmemiş'; ?></span>
                            </div>
                            <div class="detail">
                                <i class="bx bx-category"></i>
                                <span>Kategori: <?php echo $kategori ? htmlspecialchars($kategori->name) : 'Belirtilmemiş'; ?></span>
                            </div>
                            <div class="detail">
                                <i class="bx bx-package"></i>
                                <span>Stok Miktarı: <?php echo $urun->adet; ?> adet</span>
                            </div>
                        </div>
                        <!-- Düzenleme Formu -->
                        <form action="<?php echo $PAGE->url; ?>" method="post">
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                            <input type="hidden" name="islem" value="kaydet">

                            <div class="mb-4">
                                <label for="edit_bolum" class="form-label text-muted small text-uppercase fw-semibold">Bölüm</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-white"><i class="bx bx-cabinet text-primary"></i></span>
                                    <select class="form-select" id="edit_bolum" name="bolum">
                                        <option value="">-- Bölüm Seçin --</option>
                                        <option value=" Spor Ayakkabılar">Spor Ayakkabılar</option>
                                        <option value="Klasik Ayakkabılar">Klasik Ayakkabılar</option>
                                        <option value="Günlük Ayakkabılar">Günlük Ayakkabılar</option>
                                        <option value="Bot & Çizmeler">Bot & Çizmeler</option>
                                        <option value="Sandalet & Terlik">Sandalet & Terlik</option>
                                        <option value="Outdoor / Trekking Ayakkabıları">Outdoor / Trekking Ayakkabıları</option>

                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="edit_raf" class="form-label text-muted small text-uppercase fw-semibold">Raf</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-white"><i class="bx bx-server text-primary"></i></span>
                                    <select class="form-select" id="edit_raf" name="raf">
                                        <option value="">-- Önce Bölüm Seçin --</option>
                                        <!-- Raflar JavaScript ile doldurulacak -->
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>" class="btn btn-outline-secondary">
                                    İptal
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bx bx-save me-2"></i>Değişiklikleri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
// Inline JavaScript daha güvenli bir şekilde eklemek için Moodle API'sini kullanıyoruz
// JavaScript kısmında, sayfa yüklenirken mevcut bölüm ve rafı seçili getir:
$js = "
    document.addEventListener('DOMContentLoaded', function() {
        const bolumSelect = document.getElementById('edit_bolum');
        const rafSelect = document.getElementById('edit_raf');
        const mevcutBolum = '".addslashes($urun->bolum)."';
        const mevcutRaf = '".addslashes($urun->raf)."';

        // Sayfa yüklendiğinde mevcut bölüm ve rafı seçili getir
        if (mevcutBolum) {
            bolumSelect.value = mevcutBolum;
        }
        updateRaflar(mevcutBolum, mevcutRaf);

        bolumSelect.addEventListener('change', function() {
            updateRaflar(this.value, '');
        });

        function updateRaflar(bolum, selectedRaf) {
            rafSelect.innerHTML = '<option value=\"\">-- Raf Seçin --</option>';
            if (bolum === 'Spor Ayakkabılar') {
                for (let i = 1; i <= 10; i++) {
                    addRafOption(rafSelect, 'E' + i + ' Rafı');
                }
            } else if (bolum === 'Klasik Ayakkabılar') {
                for (let i = 1; i <= 5; i++) {
                    addRafOption(rafSelect, 'K' + i + ' Rafı');
                }
            } else if (bolum === 'Günlük Ayakkabılar') {
                for (let i = 1; i <= 5; i++) {
                    addRafOption(rafSelect, 'G' + i + ' Rafı');
                }
            } else if (bolum === 'Bot & Çizmeler') {
                for (let i = 1; i <= 5; i++) {
                    addRafOption(rafSelect, 'B' + i + ' Rafı');
                }
            } else if (bolum === 'Sandalet & Terlik') {
                for (let i = 1; i <= 3; i++) {
                    addRafOption(rafSelect, 'S' + i + ' Rafı');
                }
            } else if (bolum === 'Outdoor / Trekking Ayakkabıları') {
                for (let i = 1; i <= 3; i++) {
                    addRafOption(rafSelect, 'O' + i + ' Rafı');
                }
            }
            // Mevcut rafı seçili yap
            if (selectedRaf) {
                for(let i = 0; i < rafSelect.options.length; i++) {
                    if(rafSelect.options[i].value === selectedRaf) {
                        rafSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }
        function addRafOption(select, value) {
            const option = document.createElement('option');
            option.value = value;
            option.text = value;
            select.appendChild(option);
        }
    });
";
$PAGE->requires->js_init_code($js);

echo $OUTPUT->footer();
?>