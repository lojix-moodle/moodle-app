<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);
$barkod = optional_param('barkod', '', PARAM_TEXT);

//$min_stok_seviyesi = required_param('min_stok_seviyesi', PARAM_INT);


$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Düzenle');
$PAGE->set_heading('Ürün Düzenle');
$PAGE->set_pagelayout('admin');
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'));

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);


if (!$is_admin) {
//    $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
//    if (!$user_depo || $user_depo != $depoid) {
    if (!$is_depo_user)
    {
        throw new moodle_exception('Erişim izniniz yok.');
    }
//    }
}

$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);

if (!$urun) {
    throw new moodle_exception('Ürün bulunamadı.');
}

// Mevcut renk ve boyut bilgilerini al
$mevcut_renkler = [];
$mevcut_boyutlar = [];

if (!empty($urun->colors)) {
    $mevcut_renkler = json_decode($urun->colors, true);
    if (is_string($mevcut_renkler)) {
        $mevcut_renkler = [$mevcut_renkler];
    }
}

if (!empty($urun->sizes)) {
    $mevcut_boyutlar = json_decode($urun->sizes, true);
    if (is_string($mevcut_boyutlar)) {
        $mevcut_boyutlar = [$mevcut_boyutlar];
    }
}

$mevcut_varyasyonlar = !empty($urun->varyasyonlar) ? json_decode($urun->varyasyonlar, true) : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $name = required_param('name', PARAM_TEXT);
    $kategoriid = required_param('kategoriid', PARAM_INT);
    $min_stok_seviyesi = optional_param('min_stok_seviyesi', 0, PARAM_INT);
    $barkod = required_param('barkod', PARAM_TEXT);

    $colors = $_POST['colors'];
    $sizes = $_POST['sizes'];
    $varyasyonlar = $_POST['varyasyon'];

    // Mevcut ürünün ID'sini koruyarak güncelleyelim
    $guncellenecekUrun = new stdClass();
    $guncellenecekUrun->id = $urunid; // Mevcut ürünün ID'sini koruyoruz
    $guncellenecekUrun->depoid = $depoid;
    $guncellenecekUrun->name = $name;
    $guncellenecekUrun->kategoriid = $kategoriid;
    $guncellenecekUrun->barkod = $barkod; // <-- EKLEYİN
    $guncellenecekUrun->colors = json_encode($colors);
    $guncellenecekUrun->sizes = json_encode($sizes);
    $guncellenecekUrun->varyasyonlar = json_encode($varyasyonlar);
    $guncellenecekUrun->min_stok_seviyesi = $min_stok_seviyesi;

    // Toplam adet hesaplama (tekrarlanan kod bloğu düzeltildi)
    $toplam_adet = 0;
    if (!empty($varyasyonlar)) {
        foreach ($varyasyonlar as $renk => $boyutlar) {
            foreach ($boyutlar as $boyut => $miktar) {
                $toplam_adet += (int)$miktar;
            }
        }
    }

    $guncellenecekUrun->adet = $toplam_adet;

    // insert_record yerine update_record kullanıyoruz
    $DB->update_record('block_depo_yonetimi_urunler', $guncellenecekUrun);
    redirect(new moodle_url('/my', ['depo' => $depoid]), 'Ürün başarıyla güncellendi.', null, \core\output\notification::NOTIFY_SUCCESS);
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
            'gri' => 'Gri',
            'bej' => 'Bej',
            'lacivert' => 'Lacivert',
            'kahverengi' => 'Kahverengi',
            'haki' => 'Haki',
            'vizon' => 'Vizon',
            'bordo' => 'Bordo'
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
            <small class="text-muted">Ürün düzenleniyor</small>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-box-open text-white me-2"></i>
                <h5 class="mb-0">Ürün Düzenle</h5>
            </div>
        </div>
        <div class="card-body">
            <form method="post" class="needs-validation" novalidate id="urunForm">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <div class="row g-4">
                    <!-- Sol Sütun - Tüm Ürün Bilgileri -->
                    <div class="col-lg-6 pe-lg-4">
                        <h4 class="section-title">Ürün Temel Bilgileri</h4>

                        <!-- Kategori -->
                        <div class="mb-4">
                            <label for="kategoriid" class="form-label">
                                <i class="fas fa-tags me-2 text-primary"></i>Kategori
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-folder"></i></span>
                                <select name="kategoriid" id="kategoriid" class="form-select" required>
                                    <option value="">Kategori Seçiniz</option>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <option value="<?php echo $kategori->id; ?>" <?php echo ($kategori->id == $urun->kategoriid) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="invalid-feedback">Lütfen bir kategori seçin.</div>
                            <div class="form-text">Ürünün ait olduğu kategoriyi seçin</div>
                        </div>

                        <!-- Ürün Adı -->
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <i class="fas fa-box me-2 text-primary"></i>Ürün Adı
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo htmlspecialchars($urun->name); ?>"
                                       placeholder="Ürün adını girin" required>
                            </div>
                            <div class="invalid-feedback">Lütfen ürün adını girin.</div>
                            <div class="form-text">Depodaki ürünün adını girin</div>
                        </div>

                        <!-- Barkod Alanı -->
                        <div class="mb-4">
                            <label for="barkod" class="form-label"><i class="fas fa-qrcode me-2"></i>Barkod</label>
                            <div class="input-group">
                                <input type="text" id="barkod" name="barkod" class="form-control"
                                       value="<?php echo htmlspecialchars($urun->barkod ?? ''); ?>"
                                       placeholder="Barkod değeri veya ürün kodu">
                                <button class="btn btn-outline-success" type="button" id="generate-barcode">
                                    <i class="fas fa-random me-1"></i>Barkod Oluştur
                                </button>
                            </div>
                            <div class="mb-3 text-center">
                                <label class="form-label">Önizleme:</label>
                                <div class="barcode-container p-3 border rounded bg-white">
                                    <svg id="barcode-svg"></svg>
                                </div>
                            </div>
                            <button class="btn btn-outline-primary" id="print-barcode" type="button">
                                <i class="fas fa-print me-2"></i>Yazdır
                            </button>
                        </div>

                        <!-- Minimum Stok Seviyesi -->
                        <div class="mb-4">
                            <label for="min_stok_seviyesi" class="form-label">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Minimum Stok Seviyesi
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-level-down-alt"></i></span>
                                <input type="number" class="form-control" id="min_stok_seviyesi" name="min_stok_seviyesi"
                                       value="<?php echo htmlspecialchars($urun->min_stok_seviyesi); ?>"
                                       placeholder="Minimum stok miktarı" min="0" required>
                            </div>
                            <div class="invalid-feedback">Lütfen geçerli bir minimum stok seviyesi girin.</div>
                            <div class="form-text">Bu değer altına düşüldüğünde uyarı verilecektir</div>
                        </div>

                        <!-- Renkler -->
                        <div class="mb-4">
                            <label for="colors" class="form-label">
                                <i class="fas fa-palette me-2 text-primary"></i>Renkler
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                <select multiple class="form-select" id="colors" name="colors[]" size="5">
                                    <?php
                                    $seciliRenkler = json_decode($urun->colors, true);
                                    $tumRenkler = [
                                        'beyaz' => 'Beyaz',
                                        'mavi' => 'Mavi',
                                        'siyah' => 'Siyah',
                                        'bej' => 'Bej',
                                        'gri' => 'Gri',
                                        'lacivert' => 'Lacivert',
                                        'kahverengi' => 'Kahverengi',
                                        'pembe' => 'Pembe',
                                        'mor' => 'Mor',
                                        'haki' => 'Haki',
                                        'vizon' => 'Vizon',
                                        'sari' => 'Sarı',
                                        'turuncu' => 'Turuncu',
                                        'kirmizi' => 'Kırmızı',
                                        'yesil' => 'Yeşil',
                                        'bordo' => 'Bordo'
                                    ];
                                    foreach ($tumRenkler as $value => $label):
                                        $selected = is_array($seciliRenkler) && in_array($value, $seciliRenkler) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-text small">
                                <i class="fas fa-info-circle"></i> CTRL ile çoklu seçim yapabilirsiniz
                            </div>
                        </div>

                        <!-- Boyutlar -->
                        <div class="mb-4">
                            <label for="sizes" class="form-label">
                                <i class="fas fa-ruler-combined me-2 text-primary"></i>Boyutlar
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-expand-arrows-alt"></i></span>
                                <select multiple class="form-select" id="sizes" name="sizes[]" size="5">
                                    <?php
                                    $seciliBoyutlar = json_decode($urun->sizes, true);
                                    $tumBoyutlar = range(17, 45);
                                    foreach ($tumBoyutlar as $boyut):
                                        $selected = is_array($seciliBoyutlar) && in_array($boyut, $seciliBoyutlar) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $boyut; ?>" <?php echo $selected; ?>><?php echo $boyut; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-text small">
                                <i class="fas fa-info-circle"></i> CTRL ile çoklu seçim yapabilirsiniz
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Sütun - Sadece Varyasyon Yönetimi -->
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
                        <div id="varyasyonBolumu" class="mt-4 <?php echo (!empty($mevcut_varyasyonlar)) ? '' : 'd-none'; ?>">
                            <div class="alert alert-info d-flex <?php echo (!empty($mevcut_varyasyonlar)) ? 'd-none' : ''; ?>">
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

                <!-- Form Butonları -->
                <div class="d-flex flex-wrap gap-3 mt-4 pt-4 border-top">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Ürünü Güncelle
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

        // Sayfalama değişkenleri
        let currentPage = 1;
        const itemsPerPage = 10;
        let allVariants = [];

        // Mevcut varyasyonları JSON'dan al
        const mevcutVaryasyonlar = <?php echo !empty($urun->varyasyonlar) ? $urun->varyasyonlar : '{}'; ?>;

        // Sayfa yüklendiğinde mevcut varyasyonları göster
        document.addEventListener('DOMContentLoaded', function() {
            const selectedColors = Array.from(colorSelect.selectedOptions).map(opt => {
                return {
                    value: opt.value,
                    text: opt.textContent
                };
            });

            const selectedSizes = Array.from(sizeSelect.selectedOptions).map(opt => {
                return {
                    value: opt.value,
                    text: opt.textContent
                };
            });

            if (selectedColors.length > 0 && selectedSizes.length > 0) {
                // Varyasyonları oluştur
                allVariants = [];
                selectedColors.forEach(color => {
                    selectedSizes.forEach(size => {
                        allVariants.push({
                            color: color,
                            size: size
                        });
                    });
                });

                // Varyasyonları göster
                displayVariantsByPage();
                updatePaginationControls();
            }
        });

        // Varyasyon oluşturma
        varyasyonOlusturBtn.addEventListener('click', function() {
            // Seçilen renkler ve boyutları al
            const selectedColors = Array.from(colorSelect.selectedOptions).map(opt => {
                return {
                    value: opt.value,
                    text: opt.textContent
                };
            });

            const selectedSizes = Array.from(sizeSelect.selectedOptions).map(opt => {
                return {
                    value: opt.value,
                    text: opt.textContent
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
            // Uyarı mesajını gizle
            const uyariMesaji = varyasyonBolumu.querySelector('.alert-info');
            if (uyariMesaji) {
                uyariMesaji.classList.add('d-none');
            }

            // Tüm varyasyonları oluştur ve saklayalım
            allVariants = [];
            selectedColors.forEach(color => {
                selectedSizes.forEach(size => {
                    allVariants.push({
                        color: color,
                        size: size
                    });
                });
            });

            // Sayfalama değişkenlerini sıfırla
            currentPage = 1;

            // Varyasyonları sayfayla göster
            displayAllVariants(); // Sadece bu fonksiyonu çağırın


            // Sayfalama kontrollerini güncelle
            updatePaginationControls();
        });

        // Belirli bir sayfadaki varyasyonları göster
        function displayAllVariants() {
            varyasyonTablo.innerHTML = '';
            allVariants.forEach(variant => {
                const row = document.createElement('tr');
                const variantCell = document.createElement('td');
                variantCell.className = 'd-flex align-items-center';

                const colorBadge = document.createElement('span');
                colorBadge.className = 'badge me-2';
                colorBadge.style.backgroundColor = getColorHex(variant.color.value);
                colorBadge.style.color = getContrastColor(variant.color.value);
                colorBadge.innerHTML = '&nbsp;&nbsp;&nbsp;';

                variantCell.appendChild(colorBadge);
                variantCell.appendChild(document.createTextNode(variant.color.text + ' / ' + variant.size.text));

                const stockCell = document.createElement('td');
                const stockInput = document.createElement('input');
                stockInput.type = 'number';
                stockInput.name = `varyasyon[${variant.color.value}][${variant.size.value}]`;
                stockInput.className = 'form-control form-control-sm';
                stockInput.min = 0;
                stockInput.value = 0;
                stockInput.required = true;

                stockCell.appendChild(stockInput);
                row.appendChild(variantCell);
                row.appendChild(stockCell);
                varyasyonTablo.appendChild(row);
            });
        }




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
                'gri': '#6c757d',
                'bej': '#E4DAD2',
                'lacivert': '#11098A',
                'kahverengi': '#8B4513',
                'haki': '#8A9A5B',
                'vizon': '#A89F91',
                'bordo': '#800000'
            };

            return colorMap[colorName] || '#6c757d';
        }

        // Kontrast rengi hesapla
        function getContrastColor(colorName) {
            const lightColors = ['beyaz', 'sari', 'acik-mavi', 'acik-yesil', 'acik-pembe', 'bej'];
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
                            html: `<p>${validVariants} farklı varyasyon için toplam <strong>${varyasyonToplam}</strong> adet stok güncellemek üzeresiniz.</p>` +
                                `<p>Devam etmek istiyor musunuz?</p>`,
                            showCancelButton: true,
                            confirmButtonText: 'Evet, Güncelle',
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


    document.addEventListener('DOMContentLoaded', function() {
        const barcodeValue = document.getElementById('barkod');
        const printBtn = document.getElementById('print-barcode');

        function generateBarcode() {
            if (barcodeValue.value.trim() !== '') {
                try {
                    JsBarcode("#barcode-svg", barcodeValue.value.trim(), {
                        format: "code128",
                        lineColor: "#000",
                        width: 2,
                        height: 100,
                        displayValue: true
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Barkod oluşturulamadı: ' + e.message
                    });
                }
            } else {
                document.getElementById('barcode-svg').innerHTML = '';
            }
        }

        barcodeValue.addEventListener('input', generateBarcode);
        if (barcodeValue.value.trim() !== '') {
            generateBarcode();
        }

        printBtn.addEventListener('click', function() {
            const printWindow = window.open('', '', 'height=500,width=800');
            printWindow.document.write('<html><head><title>Barkod Yazdır</title>');
            printWindow.document.write('<style>body { font-family: Arial; text-align: center; } .barcode-container { margin: 20px; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h4>Ürün: <?php echo htmlspecialchars($urun->name); ?></h4>');
            printWindow.document.write('<div class="barcode-container">' + document.querySelector('.barcode-container').innerHTML + '</div>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const generateBtn = document.getElementById('generate-barcode');
        const printBtn = document.getElementById('print-barcode');
        const barcodeType = document.getElementById('barcode-type');
        const barcodeValue = document.getElementById('barcode-value');

        // Barkod oluştur
        generateBtn.addEventListener('click', function() {
            if (barcodeValue.value.trim() !== '') {
                try {
                    JsBarcode("#barcode-svg", barcodeValue.value.trim(), {
                        format: barcodeType.value,
                        lineColor: "#000",
                        width: 2,
                        height: 100,
                        displayValue: true
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Barkod oluşturulamadı: ' + e.message
                    });
                }
            }
        });

        // Barkod yazdırma
        printBtn.addEventListener('click', function() {
            const printWindow = window.open('', '', 'height=500,width=800');
            printWindow.document.write('<html><head><title>Barkod Yazdır</title>');
            printWindow.document.write('<style>body { font-family: Arial; text-align: center; } .barcode-container { margin: 20px; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h4>Ürün: <?php echo htmlspecialchars($urun->name); ?></h4>');
            printWindow.document.write('<div class="barcode-container">' + document.querySelector('.barcode-container').innerHTML + '</div>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();

            // Kısa gecikme sonrası yazdır
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        });

        // Sayfa yüklendiğinde varsayılan barkod oluştur
        if (barcodeValue.value.trim() !== '') {
            generateBtn.click();
        }
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const barcodeValue = document.getElementById('barkod');
        const printBtn = document.getElementById('print-barcode');
        const generateBtn = document.getElementById('generate-barcode');

        function generateBarcode() {
            if (barcodeValue.value.trim() !== '') {
                try {
                    JsBarcode("#barcode-svg", barcodeValue.value.trim(), {
                        format: "code128",
                        lineColor: "#000",
                        width: 2,
                        height: 100,
                        displayValue: true
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Barkod oluşturulamadı: ' + e.message
                    });
                }
            } else {
                document.getElementById('barcode-svg').innerHTML = '';
            }
        }

        // Rastgele barkod üret
        generateBtn.addEventListener('click', function() {
            // 13 haneli rastgele sayı üret
            let randomBarcode = '';
            for (let i = 0; i < 13; i++) {
                randomBarcode += Math.floor(Math.random() * 10);
            }
            barcodeValue.value = randomBarcode;
            generateBarcode();
        });

        barcodeValue.addEventListener('input', generateBarcode);
        if (barcodeValue.value.trim() !== '') {
            generateBarcode();
        }

        printBtn.addEventListener('click', function() {
            const printWindow = window.open('', '', 'height=500,width=800');
            printWindow.document.write('<html><head><title>Barkod Yazdır</title>');
            printWindow.document.write('<style>body { font-family: Arial; text-align: center; } .barcode-container { margin: 20px; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h4>Ürün: <?php echo htmlspecialchars($urun->name); ?></h4>');
            printWindow.document.write('<div class="barcode-container">' + document.querySelector('.barcode-container').innerHTML + '</div>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        });
    });
</script>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<?php
echo $OUTPUT->footer();
?>

