<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_login();
global $DB, $PAGE, $OUTPUT;

// Sayfanın temel yapılandırması
$depoid = required_param('depoid', PARAM_INT);
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Ekle');
$PAGE->set_heading('Ürün Ekle');

// Depo ve kategori bilgilerini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');

// Form gönderildiğinde işlem yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = required_param('name', PARAM_TEXT);
    $kategoriid = required_param('kategoriid', PARAM_INT);
    $min_stok_seviyesi = optional_param('min_stok_seviyesi', 0, PARAM_INT);

    $colors = $_POST['colors'];
    $sizes = $_POST['sizes'];
    $varyasyonlar = isset($_POST['varyasyon']) ? $_POST['varyasyon'] : [];

    // Toplam adet hesaplama
    $toplam_adet = 0;
    if (!empty($varyasyonlar)) {
        foreach ($varyasyonlar as $renk => $boyutlar) {
            foreach ($boyutlar as $boyut => $miktar) {
                $toplam_adet += (int)$miktar;
            }
        }
    }

    // Ana ürün objesi oluşturma
    $ana_urun = new stdClass();
    $ana_urun->depoid = $depoid;
    $ana_urun->name = $name;
    $ana_urun->adet = $toplam_adet;
    $ana_urun->kategoriid = $kategoriid;
    $ana_urun->colors = json_encode($colors);
    $ana_urun->sizes = json_encode($sizes);
    $ana_urun->varyasyonlar = json_encode($varyasyonlar);
    $ana_urun->min_stok_seviyesi = $min_stok_seviyesi;

    // Raf ve bölüm bilgileri
    $raf = optional_param('raf', '', PARAM_TEXT);
    $bolum = optional_param('bolum', '', PARAM_TEXT);

    // Ana ürüne raf ve bölüm bilgilerini ekle
    $ana_urun->raf = $raf;
    $ana_urun->bolum = $bolum;

    try {
        // Depo kaydının var olup olmadığını kontrol et
        $depo_kontrol = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

        // Kategori kaydının var olup olmadığını kontrol et
        $kategori_kontrol = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $ana_urun->kategoriid], '*', MUST_EXIST);

        // Ana ürünü ekle ve ID'sini al
        $ana_urun_id = $DB->insert_record('block_depo_yonetimi_urunler', $ana_urun);

        // Başarılı mesajı göster
        \core\notification::success('Ürün başarıyla eklendi.');

        // Anasayfaya yönlendir
        redirect(new moodle_url('/my', ['depo' => $depoid]));
    } catch (dml_missing_record_exception $e) {
        // Kayıt bulunamadığında
        error_log("Veritabanı hatası: " . $e->getMessage());
        \core\notification::error('Depo veya kategori kaydı bulunamadı: ' . $e->getMessage());
    } catch (Exception $e) {
        // Diğer hatalar için
        error_log("Genel hata: " . $e->getMessage());
        \core\notification::error('Bir hata oluştu: ' . $e->getMessage());
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
                                        <option value="<?php echo $kategori->id; ?>">
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
                                       placeholder="Ürün adını girin" required>
                            </div>
                            <div class="invalid-feedback">Lütfen ürün adını girin.</div>
                            <div class="form-text">Depoya eklemek istediğiniz ürünün adını girin</div>
                        </div>

                        <!-- Raf ve Bölüm Bilgileri -->
                        <div class="row mb-4">
                            <!-- Bölüm -->
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="bolum" class="form-label">Bölüm</label>
                                <select class="form-select" id="bolum" name="bolum">
                                    <option value="">-- Bölüm Seçin --</option>
                                    <option value="Tişört">Tişört</option>
                                    <option value="Pantolon">Pantolon</option>
                                    <option value="Ayakkabı">Ayakkabı</option>
                                    <option value="Gömlek">Gömlek</option>
                                    <option value="Elbise">Elbise</option>
                                    <option value="Ceket">Ceket</option>
                                    <option value="Aksesuar">Aksesuar</option>
                                    <option value="Çanta">Çanta</option>
                                    <option value="İç Giyim">İç Giyim</option>
                                </select>
                            </div>

                            <!-- Raf -->
                            <div class="col-md-6">
                                <label for="raf" class="form-label">Raf</label>
                                <select class="form-select" id="raf" name="raf">
                                    <option value="">-- Önce Bölüm Seçin --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Minimum Stok Seviyesi -->
                        <div class="mb-4">
                            <label for="min_stok_seviyesi" class="form-label">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Minimum Stok Seviyesi
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-level-down-alt"></i></span>
                                <input type="number" class="form-control" id="min_stok_seviyesi" name="min_stok_seviyesi"
                                       value="0" placeholder="Minimum stok miktarı" min="0" required>
                            </div>
                            <div class="invalid-feedback">Lütfen geçerli bir minimum stok seviyesi girin.</div>
                            <div class="form-text">Bu değer altına düşüldüğünde uyarı verilecektir</div>
                        </div>

                        <!-- Renkler ve Boyutlar (Yan Yana) -->
                        <div class="row mb-4">
                            <!-- Renkler - Sol Kolon -->
                            <div class="mb-4">
                                <label for="colors" class="form-label">
                                    <i class="fas fa-palette me-2 text-primary"></i>Renkler
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                    <select multiple class="form-select" id="colors" name="colors[]" size="5">
                                        <option value="beyaz">Beyaz</option>
                                        <option value="mavi">Mavi</option>
                                        <option value="siyah">Siyah</option>
                                        <option value="bej">Bej</option>
                                        <option value="gri">Gri</option>
                                        <option value="lacivert">Lacivert</option>
                                        <option value="kahverengi">Kahverengi</option>
                                        <option value="pembe">Pembe</option>
                                        <option value="mor">Mor</option>
                                        <option value="haki">Haki</option>
                                        <option value="vizon">Vizon</option>
                                        <option value="sari">Sarı</option>
                                        <option value="turuncu">Turuncu</option>
                                        <option value="kirmizi">Kırmızı</option>
                                        <option value="yesil">Yeşil</option>
                                        <option value="bordo">Bordo</option>
                                    </select>
                                </div>
                                <div class="form-text small">
                                    <i class="fas fa-info-circle"></i> CTRL ile çoklu seçim yapabilirsiniz
                                </div>
                            </div>

                            <!-- Boyutlar - Sağ Kolon -->
                            <div class="mb-4">
                                <label for="sizes" class="form-label">
                                    <i class="fas fa-ruler-combined me-2 text-primary"></i>Boyutlar
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-expand-arrows-alt"></i></span>
                                    <select multiple class="form-select" id="sizes" name="sizes[]" size="5">
                                        <option value="17">17</option>
                                        <option value="18">18</option>
                                        <option value="19">19</option>
                                        <option value="20">20</option>
                                        <option value="21">21</option>
                                        <option value="22">22</option>
                                        <option value="23">23</option>
                                        <option value="24">24</option>
                                        <option value="25">25</option>
                                        <option value="26">26</option>
                                        <option value="27">27</option>
                                        <option value="28">28</option>
                                        <option value="29">29</option>
                                        <option value="30">30</option>
                                        <option value="31">31</option>
                                        <option value="32">32</option>
                                        <option value="33">33</option>
                                        <option value="34">34</option>
                                        <option value="35">35</option>
                                        <option value="36">36</option>
                                        <option value="37">37</option>
                                        <option value="38">38</option>
                                        <option value="39">39</option>
                                        <option value="40">40</option>
                                        <option value="41">41</option>
                                        <option value="42">42</option>
                                        <option value="43">43</option>
                                        <option value="44">44</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                                <div class="form-text small">
                                    <i class="fas fa-info-circle"></i> CTRL ile çoklu seçim yapabilirsiniz
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

                            <!-- Sayfalama Bilgisi -->
                            <div id="pageInfo" class="text-center text-muted mt-2"></div>
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

        // Tüm varyasyonları tutacak dizi
        let allVariants = [];

        // Varyasyon oluşturma
        varyasyonOlusturBtn.addEventListener('click', function() {
            // Seçili renk ve boyutları al
            const selectedColors = Array.from(colorSelect.selectedOptions).map(option => option.value);
            const selectedSizes = Array.from(sizeSelect.selectedOptions).map(option => option.value);

            // Hiç renk veya boyut seçilmemişse uyarı ver
            if (selectedColors.length === 0 || selectedSizes.length === 0) {
                alert('En az bir renk ve bir boyut seçmelisiniz!');
                return;
            }

            // Varyasyon bölümünü göster
            varyasyonBolumu.classList.remove('d-none');

            // Tüm varyasyonları oluştur
            allVariants = [];
            selectedColors.forEach(color => {
                selectedSizes.forEach(size => {
                    allVariants.push({
                        color: color,
                        size: size,
                        stock: 0
                    });
                });
            });

            // Varyasyonları göster
            displayVariantsByPage();
        });

        // Tüm varyasyonları göster (sayfalama olmadan)
        function displayVariantsByPage() {
            // Tabloyu temizle
            varyasyonTablo.innerHTML = '';

            // Tüm varyasyonları göster
            allVariants.forEach(variant => {
                const row = document.createElement('tr');

                // Renk hücresi
                const colorCell = document.createElement('td');
                const colorBadge = document.createElement('span');
                colorBadge.className = 'color-badge me-2';
                colorBadge.style.backgroundColor = getColorHex(variant.color);
                colorBadge.style.color = getContrastColor(variant.color);
                colorBadge.innerText = variant.color.charAt(0).toUpperCase();

                const colorName = document.createElement('span');
                colorName.innerText = variant.color.charAt(0).toUpperCase() + variant.color.slice(1);

                colorCell.appendChild(colorBadge);
                colorCell.appendChild(colorName);
                row.appendChild(colorCell);

                // Boyut hücresi
                const sizeCell = document.createElement('td');
                sizeCell.innerText = variant.size;
                row.appendChild(sizeCell);

                // Stok hücresi
                const stockCell = document.createElement('td');
                const stockInput = document.createElement('input');
                stockInput.type = 'number';
                stockInput.className = 'form-control form-control-sm stock-input';
                stockInput.min = '0';
                stockInput.value = variant.stock;
                stockInput.name = `variant_stock[${variant.color}][${variant.size}]`;
                stockInput.addEventListener('change', function() {
                    variant.stock = parseInt(this.value) || 0;
                });

                stockCell.appendChild(stockInput);
                row.appendChild(stockCell);

                varyasyonTablo.appendChild(row);
            });
        }

        // Renk kodlarını al
        function getColorHex(colorName) {
            const colorMap = {
                'siyah': '#000000',
                'beyaz': '#ffffff',
                'kirmizi': '#dc3545',
                'mavi': '#0d6efd',
                'yesil': '#198754',
                'sari': '#ffc107',
                'turuncu': '#fd7e14',
                'mor': '#6f42c1',
                'pembe': '#d63384',
                'gri': '#6c757d',
                'kahverengi': '#8B4513',
                'lacivert': '#000080',
                'acik-mavi': '#0dcaf0',
                'acik-yesil': '#20c997',
                'acik-pembe': '#f8d7da',
                'acik-gri': '#ced4da'
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
                    if (!this.validity.valid) {
                        this.classList.add('is-invalid');
                        const feedback = this.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.style.display = 'block';
                        }
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        const feedback = this.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.style.display = 'none';
                        }
                    }
                });
            });

            // Form gönderildiğinde
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Tüm geçersiz alanlara odaklan
                    const invalidInputs = form.querySelectorAll(':invalid');
                    if (invalidInputs.length > 0) {
                        invalidInputs[0].focus();
                    }
                } else {
                    // Form geçerli, yükleme ekranını göster
                    loadingOverlay.style.display = 'flex';
                    submitBtn.disabled = true;
                }

                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Bu script'i ürün ekle sayfanızdaki <script> etiketleri arasına ekleyin
    document.addEventListener('DOMContentLoaded', function() {
        // Bölüm ve raf elementlerini al
        const bolumSelect = document.getElementById("bolum");
        const rafSelect = document.getElementById("raf");

        if (bolumSelect && rafSelect) {
            // Bölüm değiştiğinde rafları güncelle
            bolumSelect.addEventListener("change", updateRaflar);

            // Sayfa yüklendiğinde mevcut bölüm için rafları yükle
            updateRaflar();
        }

        // Rafları güncelleme fonksiyonu
        function updateRaflar() {
            // Seçilen bölümü al
            const bolum = bolumSelect.value;

            // Raf seçimini temizle
            rafSelect.innerHTML = '<option value="">-- Raf Seçin --</option>';

            // Bölüme göre rafları ayarla
            if (bolum === "Tişört" || bolum === "Gömlek") {
                addRafOption(rafSelect, "A1 Rafı");
                addRafOption(rafSelect, "A2 Rafı");
                addRafOption(rafSelect, "A3 Rafı");
            } else if (bolum === "Pantolon") {
                addRafOption(rafSelect, "B1 Rafı");
                addRafOption(rafSelect, "B2 Rafı");
                addRafOption(rafSelect, "B3 Rafı");
            } else if (bolum === "Ayakkabı") {
                addRafOption(rafSelect, "C1 Rafı");
                addRafOption(rafSelect, "C2 Rafı");
                addRafOption(rafSelect, "C3 Rafı");
                addRafOption(rafSelect, "C4 Rafı");
            } else if (bolum === "Aksesuar" || bolum === "Çanta") {
                addRafOption(rafSelect, "D1 Rafı");
                addRafOption(rafSelect, "D2 Rafı");
            } else if (bolum) {
                // Diğer tüm bölümler için
                addRafOption(rafSelect, "E1 Rafı");
                addRafOption(rafSelect, "E2 Rafı");
                addRafOption(rafSelect, "E3 Rafı");
            }
        }

        // Raf seçeneği ekleme yardımcı fonksiyonu
        function addRafOption(select, value) {
            const option = document.createElement("option");
            option.value = value;
            option.text = value;
            select.appendChild(option);
        }
    });

    // Form doğrulama
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

        // Tüm varyasyonları tutacak dizi
        let allVariants = [];

        // Varyasyon oluşturma
        varyasyonOlusturBtn.addEventListener('click', function() {
            // Seçilen renkler ve boyutları al
            const selectedColors = Array.from(colorSelect.selectedOptions).map(option => option.value);
            const selectedSizes = Array.from(sizeSelect.selectedOptions).map(option => option.value);

            // Her ikisinden de seçim yapıldı mı kontrol et
            if (selectedColors.length === 0 || selectedSizes.length === 0) {
                alert('Lütfen en az bir renk ve bir boyut seçin!');
                return;
            }

            // Varyasyonları oluştur
            allVariants = [];
            for (let color of selectedColors) {
                for (let size of selectedSizes) {
                    allVariants.push({
                        color: color,
                        size: size
                    });
                }
            }

            // Varyasyon bölümünü göster ve tabloyu oluştur
            varyasyonBolumu.classList.remove('d-none');
            displayVariantsByPage();
        });

        // Tüm varyasyonları göster (sayfalama olmadan)
        function displayVariantsByPage() {
            varyasyonTablo.innerHTML = '';

            if (allVariants.length === 0) {
                varyasyonTablo.innerHTML = '<tr><td colspan="4" class="text-center">Varyasyon oluşturmak için renk ve boyut seçin</td></tr>';
                return;
            }

            // Varyasyonları tabloya ekle
            allVariants.forEach((variant, index) => {
                const colorHex = getColorHex(variant.color);
                const contrastColor = getContrastColor(variant.color);

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>
                        <span class="color-badge" style="background-color: ${colorHex}; color: ${contrastColor}">
                            ${variant.color}
                        </span>
                    </td>
                    <td>${variant.size}</td>
                    <td>
                        <input type="number" name="variant_quantity[${index}]" class="form-control form-control-sm" value="1" min="0">
                        <input type="hidden" name="variant_color[${index}]" value="${variant.color}">
                        <input type="hidden" name="variant_size[${index}]" value="${variant.size}">
                    </td>
                `;
                varyasyonTablo.appendChild(row);
            });
        }

        // Renk kodlarını al
        function getColorHex(colorName) {
            const colorMap = {
                'Kırmızı': '#ff0000',
                'Mavi': '#0000ff',
                'Yeşil': '#00ff00',
                'Sarı': '#ffff00',
                'Siyah': '#000000',
                'Beyaz': '#ffffff',
                'Turuncu': '#ffa500',
                'Mor': '#800080',
                'Pembe': '#ffc0cb',
                'Gri': '#808080',
                'Lacivert': '#000080',
                'Bej': '#f5f5dc',
                'Kahverengi': '#a52a2a',
                'Bordo': '#800000',
                'Turkuaz': '#40e0d0'
            };

            return colorMap[colorName] || '#cccccc';
        }

        // Kontrast rengi hesapla
        function getContrastColor(colorName) {
            const darkColors = ['Siyah', 'Lacivert', 'Bordo', 'Kahverengi', 'Mavi', 'Mor'];
            return darkColors.includes(colorName) ? '#ffffff' : '#000000';
        }

        // Sayfa yüklendiğinde loading overlay'i gizle
        window.addEventListener('load', function() {
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        });

        // Form doğrulama
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    // Form geçerli, loading göster
                    if (loadingOverlay && submitBtn) {
                        loadingOverlay.style.display = 'flex';
                        submitBtn.disabled = true;
                    }
                }

                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php
// Sayfanın alt kısmı
echo $OUTPUT->footer();
?>