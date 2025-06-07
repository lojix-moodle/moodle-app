<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
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
    $kategoriid = required_param('kategoriid', PARAM_INT);
    $min_stok_seviyesi = optional_param('min_stok_seviyesi', 0, PARAM_INT);

    $colors = $_POST['colors'];
    $sizes = $_POST['sizes'];
    $varyasyonlar = $_POST['varyasyon'];

    $yeni_urun = new stdClass();
    $yeni_urun->depoid = $depoid;
    $yeni_urun->name = $name;
    $yeni_urun->kategoriid = $kategoriid;
    $yeni_urun->colors = json_encode($colors);
    $yeni_urun->sizes = json_encode($sizes);
    $yeni_urun->varyasyonlar = json_encode($varyasyonlar);
    $yeni_urun->min_stok_seviyesi = $min_stok_seviyesi;

    // Toplam adet hesaplama
    $toplam_adet = 0;
    if (!empty($varyasyonlar)) {
        foreach ($varyasyonlar as $renk => $boyutlar) {
            foreach ($boyutlar as $boyut => $miktar) {
                $toplam_adet += (int)$miktar;
            }
        }
    }

    $ana_urun = new stdClass();
    $ana_urun->depoid = $depoid;
    $ana_urun->name = $name;
    $ana_urun->adet = $toplam_adet;
    $ana_urun->kategoriid = $kategoriid;
    $ana_urun->colors = json_encode($colors);
    $ana_urun->sizes = json_encode($sizes);
    $ana_urun->varyasyonlar = json_encode($varyasyonlar);

    $raf = optional_param('raf', '', PARAM_TEXT);
    $bolum = optional_param('bolum', '', PARAM_TEXT);

    $ana_urun->raf = $raf;
    $ana_urun->bolum = $bolum;

    try {
        $depo_kontrol = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);
        $kategori_kontrol = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $ana_urun->kategoriid], '*', MUST_EXIST);

        $ana_urun_id = $DB->insert_record('block_depo_yonetimi_urunler', $ana_urun);
        \core\notification::success('Ürün başarıyla eklendi.');

        redirect(new moodle_url('/my', ['depo' => $depoid]));
    } catch (dml_missing_record_exception $e) {
        error_log("Veritabanı hatası: " . $e->getMessage());
        \core\notification::error('Depo veya kategori kaydı bulunamadı: ' . $e->getMessage());
    } catch (Exception $e) {
        error_log("Genel hata: " . $e->getMessage());
        \core\notification::error('Bir hata oluştu: ' . $e->getMessage());
    }

    $ana_urun_id = $DB->insert_record('block_depo_yonetimi_urunler', $ana_urun);
    \core\notification::success('Ürün başarıyla eklendi.');
    redirect(new moodle_url('/my', ['depo' => $depoid]));
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
        /* CSS kodu aynı kalacak, uzunluk nedeniyle tekrar eklenmedi */
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

                            <!-- Barkod bileşeni -->
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0"><i class="fas fa-barcode me-2"></i>Ürün Barkodu</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="barkod" class="form-label">Barkod:</label>
                                                <div class="input-group">
                                                    <input type="text" id="barkod" name="barkod" class="form-control" placeholder="Barkod numarası girin veya otomatik oluşturun">
                                                    <button type="button" class="btn btn-outline-secondary" id="generate-random-barcode">Otomatik Oluştur</button>
                                                </div>
                                                <div class="form-text text-muted">Benzersiz bir barkod numarası girin veya otomatik oluşturun.</div>
                                            </div>
                                            <button type="button" class="btn btn-primary" id="generate-barcode">Barkod Görüntüle</button>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <div class="mb-3">
                                                <div class="p-3 border rounded bg-white">
                                                    <svg id="barcode-svg"></svg>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary" id="print-barcode" disabled>
                                                <i class="fas fa-print me-2"></i>Yazdır
                                            </button>
                                        </div>
                                    </div>
                                </div>
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

                // Tüm varyasyonları oluştur
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
            });

            // Tüm varyasyonları göster (sayfalama olmadan)
            function displayVariantsByPage() {
                // Tabloyu temizle
                varyasyonTablo.innerHTML = '';

                // Tüm varyasyonları göster
                allVariants.forEach(variant => {
                    const row = document.createElement('tr');

                    // Renk + Boyut hücresi
                    const variantCell = document.createElement('td');
                    variantCell.className = 'd-flex align-items-center';

                    // Renk göstergesi
                    const colorBadge = document.createElement('span');
                    colorBadge.className = 'badge me-2';
                    colorBadge.style.backgroundColor = getColorHex(variant.color.value);
                    colorBadge.style.color = getContrastColor(variant.color.value);
                    colorBadge.innerHTML = '&nbsp;&nbsp;&nbsp;';

                    variantCell.appendChild(colorBadge);
                    variantCell.appendChild(document.createTextNode(variant.color.text + ' / ' + variant.size.text));

                    // Stok miktarı hücresi
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

        // Bu script'i ürün ekle sayfanızdaki <script> etiketleri arasına ekleyin
        document.addEventListener('DOMContentLoaded', function() {
            // Bölüm ve raf elementlerini al
            const bolumSelect = document.getElementById("bolum");
            const rafSelect = document.getElementById("raf");

            if (bolumSelect && rafSelect) {
                // Bölüm seçildiğinde rafları güncelleme
                bolumSelect.addEventListener("change", updateRaflar);

                // Sayfa yüklendiğinde mevcut bölüm seçimine göre rafları ayarla
                if (bolumSelect.value) {
                    updateRaflar.call(bolumSelect);
                }

                // Form gönderimini kontrol et
                const form = document.getElementById("urunForm");
                if (form) {
                    form.addEventListener("submit", function(event) {
                        const bolumValue = bolumSelect.value;
                        const rafValue = rafSelect.value;

                        // Bölüm seçilmiş ama raf seçilmemişse
                        if (bolumValue && !rafValue) {
                            event.preventDefault();
                            alert("Lütfen raf seçimi yapınız.");
                            return false;
                        }

                        // Debug için konsola yazdır
                        console.log("Form gönderiliyor - Bölüm:", bolumValue, "Raf:", rafValue);
                    });
                }
            }

            // Rafları güncelleme fonksiyonu
            function updateRaflar() {
                const bolum = this.value;
                const rafSelect = document.getElementById("raf");

                // Raf seçimini temizle
                rafSelect.innerHTML = '<option value="">-- Raf Seçin --</option>';

                console.log("Seçilen bölüm:", bolum);

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

                // Seçenek sayısını kontrol et
                console.log("Raf seçenekleri güncellendi:", rafSelect.options.length);
            }

            // Raf seçeneği ekleme yardımcı fonksiyonu
            function addRafOption(select, value) {
                const option = document.createElement("option");
                option.value = value;
                option.text = value;
                select.appendChild(option);
            }
        });

        // Barkod işlemleri
        document.addEventListener('DOMContentLoaded', function() {
            const barkodInput = document.getElementById('barkod');
            const generateRandomBtn = document.getElementById('generate-random-barcode');
            const generateBtn = document.getElementById('generate-barcode');
            const barcodeSvg = document.getElementById('barcode-svg');
            const printBarcodeBtn = document.getElementById('print-barcode');

            // Rastgele barkod oluştur
            generateRandomBtn.addEventListener('click', function() {
                // EAN-13 formatı için 12 haneli rakam oluştur
                let randomBarcode = '';
                for (let i = 0; i < 12; i++) {
                    randomBarcode += Math.floor(Math.random() * 10);
                }
                barkodInput.value = randomBarcode;
                generateBarcode();
            });

            // Barkodu oluştur ve görüntüle
            generateBtn.addEventListener('click', generateBarcode);

            function generateBarcode() {
                const barkodValue = barkodInput.value.trim();
                if (barkodValue) {
                    try {
                        // Barkod SVG'sini oluştur
                        JsBarcode(barcodeSvg, barkodValue, {
                            format: "CODE128",
                            lineColor: "#000",
                            width: 2,
                            height: 100,
                            displayValue: true
                        });
                        // Yazdır butonunu etkinleştir
                        printBarcodeBtn.disabled = false;
                    } catch (e) {
                        console.error("Barkod oluşturma hatası:", e);
                        alert("Geçersiz barkod değeri! Lütfen doğru bir değer girin.");
                    }
                }
            }

            // Ana barkodu yazdır
            printBarcodeBtn.addEventListener('click', function() {
                const barkodValue = barkodInput.value.trim();
                if (barkodValue) {
                    const productName = document.getElementById('name').value || 'Yeni Ürün';
                    const printWindow = window.open('', '_blank', 'height=600,width=800');
                    printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Barkod Yazdır</title>
                        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                        <style>
                            body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                            .barcode-container { margin: 20px auto; }
                        </style>
                    </head>
                    <body>
                        <h3>${productName}</h3>
                        <div class="barcode-container">
                            <svg id="temp-barcode-svg"></svg>
                        </div>
                        <script>
                            window.onload = function() {
                                JsBarcode("#temp-barcode-svg", "${barkodValue}", {
                                    format: "CODE128",
                                    lineColor: "#000",
                                    width: 2,
                                    height: 100,
                                    displayValue: true
                                });
                                window.print();
                            }
                        <\/script>
                    </body>
                    </html>
                `);
                    printWindow.document.close();
                }
            });
        });
    </script>

    <!-- JSBarcode kütüphanesi -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
echo $OUTPUT->footer();