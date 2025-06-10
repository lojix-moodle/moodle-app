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

// Sayfa çıktısı
echo $OUTPUT->header();

// Mevcut değerleri al
$current_bolum = !empty($urun->bolum) ? $urun->bolum : '';
$current_raf = !empty($urun->raf) ? $urun->raf : '';
?>

    <!-- ... diğer HTML kodları ... -->

    <select class="form-select" id="edit_bolum" name="bolum">
        <option value="">-- Bölüm Seçin --</option>
        <option value="Spor Ayakkabılar" <?php echo $current_bolum == 'Spor Ayakkabılar' ? 'selected' : ''; ?>>Spor Ayakkabılar</option>
        <option value="Klasik Ayakkabılar" <?php echo $current_bolum == 'Klasik Ayakkabılar' ? 'selected' : ''; ?>>Klasik Ayakkabılar</option>
        <option value="Günlük Ayakkabılar" <?php echo $current_bolum == 'Günlük Ayakkabılar' ? 'selected' : ''; ?>>Günlük Ayakkabılar</option>
        <option value="Bot & Çizmeler" <?php echo $current_bolum == 'Bot & Çizmeler' ? 'selected' : ''; ?>>Bot & Çizmeler</option>
        <option value="Sandalet & Terlik" <?php echo $current_bolum == 'Sandalet & Terlik' ? 'selected' : ''; ?>>Sandalet & Terlik</option>
        <option value="Outdoor / Trekking Ayakkabıları" <?php echo $current_bolum == 'Outdoor / Trekking Ayakkabıları' ? 'selected' : ''; ?>>Outdoor / Trekking Ayakkabıları</option>
    </select>

    <!-- ... formun devamı ... -->

<?php
// JavaScript kodunu formdan sonra ekleyin
$js = "
    document.addEventListener('DOMContentLoaded', function() {
        const bolumSelect = document.getElementById('edit_bolum');
        const rafSelect = document.getElementById('edit_raf');
        
        // Sayfa yüklendiğinde mevcut bölüm ve raf için seçimleri yap
        const currentBolum = '" . addslashes($current_bolum) . "';
        const currentRaf = '" . addslashes($current_raf) . "';
        
        if(currentBolum) {
            // Bölüm seçiliyse, rafları yükle
            updateRaflar(currentBolum, currentRaf);
        }

        // Bölüm değiştiğinde rafları güncelle
        bolumSelect.addEventListener('change', function() {
            updateRaflar(this.value, '');
        });

        // Rafları güncelleme fonksiyonu
        function updateRaflar(bolum, selectedRaf) {
            // Raf seçimini temizle
            rafSelect.innerHTML = '<option value=\"\">-- Raf Seçin --</option>';

            // Bölüme göre rafları ayarla
            if (bolum === \"Outdoor / Trekking Ayakkabıları\") {
                addRafOption(rafSelect, \"A1 Rafı\");
                addRafOption(rafSelect, \"A2 Rafı\");
                addRafOption(rafSelect, \"A3 Rafı\");
                addRafOption(rafSelect, \"A4 Rafı\");
                addRafOption(rafSelect, \"A5 Rafı\");
                addRafOption(rafSelect, \"A6 Rafı\");
                addRafOption(rafSelect, \"A7 Rafı\");
                addRafOption(rafSelect, \"A8 Rafı\");
                addRafOption(rafSelect, \"A9 Rafı\");
                addRafOption(rafSelect, \"A10 Rafı\");
            } else if (bolum === \"Bot & Çizmeler\") {
                addRafOption(rafSelect, \"B1 Rafı\");
                addRafOption(rafSelect, \"B2 Rafı\");
                addRafOption(rafSelect, \"B3 Rafı\");
                addRafOption(rafSelect, \"B4 Rafı\");
                addRafOption(rafSelect, \"B5 Rafı\");
                addRafOption(rafSelect, \"B6 Rafı\");
                addRafOption(rafSelect, \"B7 Rafı\");
                addRafOption(rafSelect, \"B8 Rafı\");
                addRafOption(rafSelect, \"B9 Rafı\");
                addRafOption(rafSelect, \"B10 Rafı\");
            } else if (bolum === \"Klasik Ayakkabılar\") {
                addRafOption(rafSelect, \"C1 Rafı\");
                addRafOption(rafSelect, \"C2 Rafı\");
                addRafOption(rafSelect, \"C3 Rafı\");
                addRafOption(rafSelect, \"C4 Rafı\");
                addRafOption(rafSelect, \"C5 Rafı\");
                addRafOption(rafSelect, \"C6 Rafı\");
                addRafOption(rafSelect, \"C7 Rafı\");
                addRafOption(rafSelect, \"C8 Rafı\");
                addRafOption(rafSelect, \"C9 Rafı\");
                addRafOption(rafSelect, \"C10 Rafı\");
            } else if (bolum === \"Sandalet & Terlik\") {
                addRafOption(rafSelect, \"D1 Rafı\");
                addRafOption(rafSelect, \"D2 Rafı\");
                addRafOption(rafSelect, \"D3 Rafı\");
                addRafOption(rafSelect, \"D4 Rafı\");
                addRafOption(rafSelect, \"D5 Rafı\");
                addRafOption(rafSelect, \"D6 Rafı\");
                addRafOption(rafSelect, \"D7 Rafı\");
                addRafOption(rafSelect, \"D8 Rafı\");
                addRafOption(rafSelect, \"D9 Rafı\");
                addRafOption(rafSelect, \"D10 Rafı\");
            } else if (bolum === \"Spor Ayakkabılar\") {
                addRafOption(rafSelect, \"E1 Rafı\");
                addRafOption(rafSelect, \"E2 Rafı\");
                addRafOption(rafSelect, \"E3 Rafı\");
                addRafOption(rafSelect, \"E4 Rafı\");
                addRafOption(rafSelect, \"E5 Rafı\");
                addRafOption(rafSelect, \"E6 Rafı\");
                addRafOption(rafSelect, \"E7 Rafı\");
                addRafOption(rafSelect, \"E8 Rafı\");
                addRafOption(rafSelect, \"E9 Rafı\");
                addRafOption(rafSelect, \"E10 Rafı\");
            } else if (bolum) {
                // Diğer tüm bölümler için
                addRafOption(rafSelect, \"F1 Rafı\");
                addRafOption(rafSelect, \"F2 Rafı\");
                addRafOption(rafSelect, \"F3 Rafı\");
                addRafOption(rafSelect, \"F4 Rafı\");
                addRafOption(rafSelect, \"F5 Rafı\");
                addRafOption(rafSelect, \"F6 Rafı\");
                addRafOption(rafSelect, \"F7 Rafı\");
                addRafOption(rafSelect, \"F8 Rafı\");
                addRafOption(rafSelect, \"F9 Rafı\");
                addRafOption(rafSelect, \"F10 Rafı\");
            }

            // Eğer önceden seçilmiş bir raf varsa onu seç
            if (selectedRaf) {
                for(let i = 0; i < rafSelect.options.length; i++) {
                    if(rafSelect.options[i].value === selectedRaf) {
                        rafSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }

        // Raf seçeneği ekleme yardımcı fonksiyonu
        function addRafOption(select, value) {
            const option = document.createElement(\"option\");
            option.value = value;
            option.text = value;
            select.appendChild(option);
        }
    });
";
$PAGE->requires->js_init_code($js);

echo $OUTPUT->footer();