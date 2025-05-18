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
    // adet parametresini kaldırdık
    $kategoriid = required_param('kategoriid', PARAM_INT);

    // Renk ve boyut verilerini al
    $colors = optional_param_array('colors', [], PARAM_TEXT);
    $sizes = optional_param_array('sizes', [], PARAM_TEXT);
    $varyasyonlar = optional_param_array('varyasyon', [], PARAM_RAW);

    $transaction = $DB->start_delegated_transaction();
    try {
        // Varyasyonlardan toplam stok miktarını hesapla
        $total_stock = 0;
        if (!empty($varyasyonlar)) {
            foreach ($varyasyonlar as $color => $color_sizes) {
                foreach ($color_sizes as $size => $stok) {
                    $total_stock += intval($stok);
                }
            }
        }

        // Ana ürünü ekle
        $ana_urun = new stdClass();
        $ana_urun->depoid = $depoid;
        $ana_urun->name = $name;
        $ana_urun->adet = $total_stock; // Varyasyonların toplam stok miktarını kullan
        $ana_urun->kategoriid = $kategoriid;
        $ana_urun->is_parent = 1; // Ana ürün olduğunu belirten alan
        $ana_urun->colors = json_encode($colors);
        $ana_urun->sizes = json_encode($sizes);
        $ana_urun->varyasyonlar = json_encode($varyasyonlar);
        $ana_urun->timecreated = time();
        $ana_urun->timemodified = time();

        // Ana ürünü ekle ve ID'sini al
        $ana_urun_id = $DB->insert_record('block_depo_yonetimi_urunler', $ana_urun);

        // Varyasyonları ekle (eğer varsa)
        if (!empty($varyasyonlar) && !empty($colors) && !empty($sizes)) {
            $total_variants = 0;

            foreach ($colors as $color) {
                foreach ($sizes as $size) {
                    // Bu renk ve boyut kombinasyonu için varyasyon var mı kontrol et
                    if (isset($varyasyonlar[$color]) && isset($varyasyonlar[$color][$size])) {
                        $stok_miktari = intval($varyasyonlar[$color][$size]);

                        // Stok miktarı 0'dan büyükse varyasyonu ekle
                        if ($stok_miktari > 0) {
                            // Renk ve boyut adlarını al
                            $color_text = get_string_from_value($color, 'color');
                            $size_text = get_string_from_value($size, 'size');

                            $varyasyon = new stdClass();
                            $varyasyon->depoid = $depoid;
                            $varyasyon->name = $name . ' - ' . $color_text . ' / ' . $size_text;
                            $varyasyon->adet = $stok_miktari;
                            $varyasyon->kategoriid = $kategoriid;
                            $varyasyon->parent_id = $ana_urun_id; // Ana ürün bağlantısı
                            $varyasyon->is_parent = 0; // Varyasyon olduğunu belirt
                            $varyasyon->color = $color;
                            $varyasyon->size = $size;
                            $varyasyon->timecreated = time();
                            $varyasyon->timemodified = time();

                            $DB->insert_record('block_depo_yonetimi_urunler', $varyasyon);
                            $total_variants++;
                        }
                    }
                }
            }
        }

        $DB->commit_delegated_transaction($transaction);

        // Başarılı mesajı göster
        \core\notification::success($ana_urun->is_parent && !empty($varyasyonlar)
            ? 'Ürün ve ' . $total_variants . ' varyasyon başarıyla eklendi. Toplam stok: ' . $total_stock
            : 'Ürün başarıyla eklendi.');

        redirect(new moodle_url('/my', ['depo' => $depoid]));

    } catch (Exception $e) {
        $DB->rollback_delegated_transaction($transaction);
        \core\notification::error('Ürün eklenirken hata oluştu: ' . $e->getMessage());
    }
}

// Renk ve boyutlar için etiketleri elde etme yardımcı fonksiyonu
function get_string_from_value($value, $type) {
    if ($type == 'color') {
        $colors = [
            'kirmizi' => 'Kırmızı',
            'mavi' => 'Mavi',
            'siyah' => 'Siyah',
            'beyaz' => 'Beyaz',
            'yesil' => 'Yeşil',
            'sari' => 'Sarı',
            'turuncu' => 'Turuncu',
            'mor' => 'Mor',
            'pembe' => 'Pembe',
            'gri' => 'Gri'
        ];
        return isset($colors[$value]) ? $colors[$value] : $value;
    } else if ($type == 'size') {
        $sizes = [
            'xs' => 'XS',
            's' => 'S',
            'm' => 'M',
            'l' => 'L',
            'xl' => 'XL',
            'xxl' => 'XXL',
            'xxxl' => 'XXXL'
        ];
        return isset($sizes[$value]) ? $sizes[$value] : $value;
    }
    return $value;
}

echo $OUTPUT->header();
?>

    <style>
        :root {
            --primary: #3e64ff;
            --primary-light: rgba(62, 100, 255, 0.1);
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.10);
            --shadow-lg: 0 1rem 2rem rgba(0, 0, 0, 0.12);
            --border-radius: 0.5rem;
            --transition: all 0.2s ease-in-out;
        }

        body {
            background-color: #f9fafb;
        }

        .card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--shadow);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            background: linear-gradient(to right, var(--primary), #5a77ff);
        }

        .card-header h5 {
            font-weight: 600;
            margin-bottom: 0;
            color: white;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-color: #e2e8f0;
            padding: 0.65rem 1rem;
            border-radius: 0.4rem;
            box-shadow: none;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(62, 100, 255, 0.15);
        }

        .input-group-text {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .btn {
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 0.4rem;
            transition: all 0.25s ease;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), #5a77ff);
            border: none;
            box-shadow: 0 4px 12px rgba(62, 100, 255, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(62, 100, 255, 0.35);
        }

        .btn-success {
            background: linear-gradient(to right, #28a745, #48c76a);
            border: none;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.25);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.35);
        }

        .btn-outline-secondary {
            border-color: #dde1e7;
            color: #64748b;
        }

        .btn-outline-secondary:hover {
            background-color: #f8fafc;
            color: #334155;
            border-color: #cbd5e1;
        }

        .section-title {
            position: relative;
            color: #334155;
            font-weight: 600;
            padding-bottom: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 40px;
            background: var(--primary);
            border-radius: 3px;
        }

        .badge {
            padding: 0.45em 0.8em;
            font-weight: 500;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }

        .spinner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid rgba(62, 100, 255, 0.1);
            border-left: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .form-text {
            color: #64748b !important;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        table.table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 0 0 1px #e5e7eb;
        }

        .table thead th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #334155;
            border-top: none;
            border-bottom: 1px solid #e5e7eb;
        }

        .table tbody td {
            vertical-align: middle;
            border-bottom: 1px solid #e5e7eb;
        }

        .depo-info-bar {
            background-color: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
        }

        .depo-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), #5a77ff);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .card {
                margin-bottom: 1.5rem;
            }
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p class="mt-3 mb-0">İşleminiz Yapılıyor...</p>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Depo Bilgi Başlığı -->
        <div class="depo-info-bar mb-4">
            <div class="depo-icon">
                <i class="fas fa-warehouse"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($depo->name); ?></h6>
                <small class="text-muted">Yeni ürün ekleniyor</small>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i class="fas fa-box-open text-white me-2"></i>
                    <h5 class="mb-0">Yeni Ürün Ekle</h5>
                </div>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate id="urunForm">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                    <div class="row g-4">
                        <!-- Sol Sütun - Ürün Bilgileri -->
                        <div class="col-lg-6 pe-lg-4">
                            <h4 class="section-title">Ürün Temel Bilgileri</h4>

                            <div class="mb-4">
                                <label for="kategoriid" class="form-label">
                                    <i class="fas fa-tags me-2 text-primary"></i>Kategori
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
                                <div class="form-text">Ürünün ait olduğu kategoriyi seçin</div>
                            </div>

                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    <i class="fas fa-box me-2 text-primary"></i>Ürün Adı
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <input type="text" class="form-control" id="name" name="name"
                                           placeholder="Ürün adını girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen ürün adını girin.</div>
                                <div class="form-text">Depoya eklemek istediğiniz ürünün adını girin</div>
                            </div>

                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Renkler - Sol Kolon -->
                                        <div class="col-md-6">
                                            <label for="colors" class="form-label">
                                                <i class="fas fa-palette me-2 text-primary"></i>Renkler
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                                <select multiple class="form-select" id="colors" name="colors[]" size="5">
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
                                            <div class="form-text small">
                                                <i class="fas fa-info-circle"></i> CTRL ile çoklu seçim yapabilirsiniz
                                            </div>
                                        </div>

                                        <!-- Boyutlar - Sağ Kolon -->
                                        <div class="col-md-6 mt-3 mt-md-0">
                                            <label for="sizes" class="form-label">
                                                <i class="fas fa-ruler-combined me-2 text-primary"></i>Boyutlar
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-expand-arrows-alt"></i></span>
                                                <select multiple class="form-select" id="sizes" name="sizes[]" size="5">
                                                    <option value="xs">XS</option>
                                                    <option value="s">S</option>
                                                    <option value="m">M</option>
                                                    <option value="l">L</option>
                                                    <option value="xl">XL</option>
                                                    <option value="xxl">XXL</option>
                                                    <option value="xxxl">XXXL</option>
                                                </select>
                                            </div>
                                            <div class="form-text small">
                                                <i class="fas fa-info-circle"></i> CTRL ile çoklu seçim yapabilirsiniz
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <!-- Sağ Sütun - Varyasyonlar -->
                        <div class="col-lg-6 ps-lg-4">
                            <h4 class="section-title">Varyasyon Yönetimi</h4>

                            <div class="alert alert-primary d-flex align-items-center mb-4">
                                <i class="fas fa-info-circle fs-5 me-3"></i>
                                <div>
                                    Seçtiğiniz renk ve boyut kombinasyonlarıyla varyasyonlar oluşturabilirsiniz.
                                    Varyasyonlar, aynı ürünün farklı versiyonlarını yönetmenize olanak tanır.
                                </div>
                            </div>

                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="d-grid mb-3">
                                        <button type="button" id="varyasyonOlustur" class="btn btn-success d-flex justify-content-center align-items-center" title="Seçili renk ve boyutlar için varyasyonlar oluştur">
                                            <i class="fas fa-cubes me-2"></i>Varyasyon Oluştur
                                        </button>
                                    </div>

                                    <div class="text-center text-muted">
                                        <small>Önce renk ve boyut seçimi yapmanız gerekiyor</small>
                                    </div>
                                </div>
                            </div>

                            <div id="varyasyonBolumu" class="mt-4 d-none">
                                <div class="alert alert-info d-flex">
                                    <i class="fas fa-info-circle me-3 fs-5"></i>
                                    <div>Lütfen önce renk ve boyut seçimi yapıp "Varyasyon Oluştur" butonuna tıklayın</div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                        <tr>
                                            <th>Varyasyon</th>
                                            <th width="40%">Stok Miktarı</th>
                                        </tr>
                                        </thead>
                                        <tbody id="varyasyonTablo">
                                        <!-- JavaScript ile dinamik oluşturulacak -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Butonları -->
                    <div class="d-flex flex-wrap gap-3 mt-4 pt-4 border-top">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Ürünü Kaydet
                        </button>
                        <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>"
                           class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Geri
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        (function () {
            'use strict';

            // Form doğrulama
            const forms = document.querySelectorAll('.needs-validation');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const submitBtn = document.getElementById('submitBtn');

            // Renk ve boyut seçimleri
            const colorSelect = document.getElementById('colors');
            const sizeSelect = document.getElementById('sizes');
            const varyasyonOlusturBtn = document.getElementById('varyasyonOlustur');
            const varyasyonBolumu = document.getElementById('varyasyonBolumu');
            const varyasyonTablo = document.getElementById('varyasyonTablo');

            // Varyasyon oluşturma
            varyasyonOlusturBtn.addEventListener('click', function() {
                // Seçilen renkler ve boyutları al
                const selectedColors = Array.from(colorSelect.selectedOptions).map(opt => {
                    return {
                        value: opt.value,
                        text: opt.text
                    };
                });

                const selectedSizes = Array.from(sizeSelect.selectedOptions).map(opt => {
                    return {
                        value: opt.value,
                        text: opt.text
                    };
                });

                // Hiçbir seçim yapılmadıysa uyarı ver
                if (selectedColors.length === 0 || selectedSizes.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Eksik Seçim',
                        text: 'Lütfen en az bir renk ve bir boyut seçin.',
                        confirmButtonText: 'Tamam',
                        confirmButtonColor: '#3e64ff'
                    });
                    return;
                }

                // Varyasyon bölümünü göster
                varyasyonBolumu.classList.remove('d-none');

                // Tabloyu temizle
                varyasyonTablo.innerHTML = '';

                // Tüm renk-boyut kombinasyonları için satır oluştur
                selectedColors.forEach(color => {
                    selectedSizes.forEach(size => {
                        const row = document.createElement('tr');

                        // Renk + Boyut hücresi
                        const variantCell = document.createElement('td');
                        variantCell.className = 'd-flex align-items-center';

                        // Renk göstergesi
                        const colorBadge = document.createElement('span');
                        colorBadge.className = 'badge me-2';
                        colorBadge.style.backgroundColor = getColorHex(color.value);
                        colorBadge.style.color = getContrastColor(color.value);
                        colorBadge.innerHTML = '&nbsp;&nbsp;&nbsp;';

                        variantCell.appendChild(colorBadge);
                        variantCell.appendChild(document.createTextNode(color.text + ' / ' + size.text));

                        // Stok miktarı hücresi
                        const stockCell = document.createElement('td');
                        const stockInput = document.createElement('input');
                        stockInput.type = 'number';
                        // Doğru name formatı - sorun buradaydı
                        stockInput.name = `varyasyon[${color.value}][${size.value}]`;
                        stockInput.className = 'form-control form-control-sm';
                        stockInput.min = 0;
                        stockInput.value = 0;
                        stockInput.required = true;

                        stockCell.appendChild(stockInput);

                        row.appendChild(variantCell);
                        row.appendChild(stockCell);
                        varyasyonTablo.appendChild(row);
                    });
                });
            });

            // Renk kodlarını al
            function getColorHex(colorName) {
                const colorMap = {
                    'kirmizi': '#dc3545',
                    'mavi': '#0d6efd',
                    'siyah': '#212529',
                    'beyaz': '#f8f9fa',
                    'yesil': '#198754',
                    'sari': '#ffc107',
                    'turuncu': '#fd7e14',
                    'mor': '#6f42c1',
                    'pembe': '#d63384',
                    'gri': '#6c757d'
                };

                return colorMap[colorName] || '#6c757d';
            }

            // Kontrast rengi hesapla
            function getContrastColor(colorName) {
                const lightColors = ['beyaz', 'sari', 'acik-mavi', 'acik-yesil', 'acik-pembe'];
                return lightColors.includes(colorName) ? '#212529' : '#ffffff';
            }

            // Sayfa yüklendiğinde loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none';
            });
            // Form doğrulama
            Array.prototype.slice.call(forms).forEach(function (form) {
                // Dinamik doğrulama - alan değiştiğinde
                const inputs = form.querySelectorAll('input, select');
                Array.prototype.slice.call(inputs).forEach(function(input) {
                    input.addEventListener('change', function() {
                        // Geçerlilik kontrolü
                        if (input.checkValidity()) {
                            input.classList.remove('is-invalid');
                            input.classList.add('is-valid');
                        } else {
                            input.classList.remove('is-valid');
                            input.classList.add('is-invalid');
                        }
                    });
                });

                // Form gönderildiğinde
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();

                        // Geçersiz alanları işaretle
                        Array.prototype.slice.call(inputs).forEach(function(input) {
                            if (!input.checkValidity()) {
                                input.classList.add('is-invalid');
                            }
                        });

                        // Hata mesajı göster
                        Swal.fire({
                            icon: 'error',
                            title: 'Form Hatası',
                            text: 'Lütfen zorunlu alanları doldurun!',
                            confirmButtonText: 'Tamam',
                            confirmButtonColor: '#3e64ff'
                        });
                    } else {
                        // Varyasyonlar var mı kontrol et
                        const hasVariations = !varyasyonBolumu.classList.contains('d-none') &&
                            varyasyonTablo.querySelectorAll('tr').length > 0;

                        if (hasVariations) {
                            // Varyasyon girişlerini kontrol et
                            const varyasyonInputs = varyasyonTablo.querySelectorAll('input[type="number"]');
                            let varyasyonToplam = 0;
                            let validVariants = 0;

                            varyasyonInputs.forEach(function(input) {
                                const value = parseInt(input.value);
                                if (!isNaN(value) && value > 0) {
                                    varyasyonToplam += value;
                                    validVariants++;
                                }
                            });

                            if (validVariants === 0) {
                                event.preventDefault();
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Varyasyon Hatası',
                                    text: 'En az bir varyasyon için stok miktarı girmelisiniz!',
                                    confirmButtonText: 'Tamam',
                                    confirmButtonColor: '#3e64ff'
                                });
                                return;
                            }

                            // Onay mesajı göster
                            event.preventDefault();
                            Swal.fire({
                                icon: 'question',
                                title: 'Onay',
                                html: `<p>${validVariants} farklı varyasyon için toplam <strong>${varyasyonToplam}</strong> adet stok eklemek üzeresiniz.</p>` +
                                    `<p>Devam etmek istiyor musunuz?</p>`,
                                showCancelButton: true,
                                confirmButtonText: 'Evet, Kaydet',
                                cancelButtonText: 'İptal',
                                confirmButtonColor: '#3e64ff',
                                cancelButtonColor: '#6c757d'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    loadingOverlay.style.display = 'flex';
                                    submitBtn.disabled = true;
                                    form.submit();
                                }
                            });
                        } else {
                            // Varyasyon yok, normal form gönderimi
                            loadingOverlay.style.display = 'flex';
                            submitBtn.disabled = true;
                        }
                    }

                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
echo $OUTPUT->footer();
?>
