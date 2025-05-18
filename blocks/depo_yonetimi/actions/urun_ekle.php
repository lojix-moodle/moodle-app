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

    // Varyasyon verisini al (boş olabilir)
    $varyasyonlar = optional_param_array('varyasyon', [], PARAM_RAW);

    // JSON'a dönüştür
    $colors_json = json_encode($colors);
    $sizes_json = json_encode($sizes);
    $varyasyonlar_json = json_encode($varyasyonlar);

    $urun = new stdClass();
    $urun->depoid = $depoid;
    $urun->name = $name;
    $urun->adet = $adet;
    $urun->kategoriid = $kategoriid;
    $urun->colors = $colors_json;
    $urun->sizes = $sizes_json;
    $urun->varyasyonlar = $varyasyonlar_json;

    $DB->insert_record('block_depo_yonetimi_urunler', $urun);
    \core\notification::success('Ürün başarıyla eklendi.');
    redirect(new moodle_url('/my', ['depo' => $depoid]));
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

                            <div class="mb-4">
                                <label for="adet" class="form-label">
                                    <i class="fas fa-hashtag me-2 text-primary"></i>Adet
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                                    <input type="number" class="form-control" id="adet" name="adet"
                                           min="0" placeholder="Ürün adedini girin" required>
                                </div>
                                <div class="invalid-feedback">Lütfen geçerli bir adet girin.</div>
                                <div class="form-text">Depoya eklemek istediğiniz ürünün miktarını girin</div>
                            </div>

                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label for="colors" class="form-label">
                                            <i class="fas fa-palette me-2 text-primary"></i>Renkler
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                            <select multiple class="form-select" id="colors" name="colors[]" size="4">
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
                                        <div class="form-text">
                                            <small>Birden fazla renk seçmek için CTRL tuşuna basılı tutarak seçim yapabilirsiniz</small>
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label for="sizes" class="form-label">
                                            <i class="fas fa-ruler-combined me-2 text-primary"></i>Boyutlar
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-expand-arrows-alt"></i></span>
                                            <select multiple class="form-select" id="sizes" name="sizes[]" size="4">
                                                <option value="xs">XS</option>
                                                <option value="s">S</option>
                                                <option value="m">M</option>
                                                <option value="l">L</option>
                                                <option value="xl">XL</option>
                                                <option value="xxl">XXL</option>
                                                <option value="xxxl">XXXL</option>
                                            </select>
                                        </div>
                                        <div class="form-text">
                                            <small>Birden fazla boyut seçmek için CTRL tuşuna basılı tutarak seçim yapabilirsiniz</small>
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
                    return { value: opt.value, text: opt.text };
                });

                const selectedSizes = Array.from(sizeSelect.selectedOptions).map(opt => {
                    return { value: opt.value, text: opt.text };
                });

                // Hiçbir seçim yapılmadıysa uyarı ver
                if (selectedColors.length === 0 || selectedSizes.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Eksik Seçim',
                        text: 'Lütfen en az bir renk ve bir boyut seçin!',
                        confirmButtonText: 'Tamam',
                        confirmButtonColor: '#3e64ff'
                    });
                    return;
                }

                // Varyasyon bölümünü göster
                varyasyonBolumu.classList.remove('d-none');

                // Uyarı mesajını kaldır
                const alertElement = varyasyonBolumu.querySelector('.alert');
                if (alertElement) {
                    alertElement.remove();
                }

                // Tabloyu temizle
                varyasyonTablo.innerHTML = '';

                // Tüm renk-boyut kombinasyonları için satır oluştur
                selectedColors.forEach(color => {
                    selectedSizes.forEach(size => {
                        const row = document.createElement('tr');

                        const varyasyonCell = document.createElement('td');
                        varyasyonCell.className = 'align-middle';
                        varyasyonCell.innerHTML = `
                        <div class="d-flex align-items-center">
                            <span class="badge rounded-pill me-2" style="background-color: ${getColorHex(color.value)}; color: ${getContrastColor(color.value)}">${color.text}</span>
                            <span class="fw-medium mx-2">/</span>
                            <span class="badge bg-light text-dark border">${size.text}</span>
                        </div>
                    `;

                        const stokCell = document.createElement('td');
                        stokCell.innerHTML = `
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                            <input type="number"
                                class="form-control"
                                name="varyasyon[${color.value}][${size.value}]"
                                value="0"
                                min="0"
                                title="${color.text} - ${size.text} varyasyonu için stok miktarı">
                        </div>
                    `;

                        row.appendChild(varyasyonCell);
                        row.appendChild(stokCell);
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

            // Kontrast rengi hesapla (koyu arka plan için beyaz, açık arka plan için siyah)
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
                    } else {
                        // Form geçerli ise yükleme animasyonunu göster
                        loadingOverlay.style.display = 'flex';
                        submitBtn.disabled = true;
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