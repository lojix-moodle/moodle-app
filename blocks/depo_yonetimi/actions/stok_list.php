<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stoklar');
$PAGE->set_heading('Stoklar');
$PAGE->set_pagelayout('admin');
$PAGE->requires->css('/blocks/depo_yonetimi/stok_list.css');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin) {
    if (!$is_depo_user)
    {
        throw new moodle_exception('Erişim izniniz yok.');
    }
}

$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);

if (!$urun) {
    print_error('Ürün bulunamadı.');
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

$action = $_GET['action'] ?? null;

if ($action && $action == 'talep')
{
    
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



<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-container">
        <div class="spinner"></div>
        <p class="mt-3 mb-0">İşleminiz Yapılıyor...</p>
    </div>
</div>

<div class="container-fluid py-4">


    <div class="mb-4" style="display: none">
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

    <!-- Renkler ve Boyutlar -->
    <div class="row mb-4" style="display: none">
        <!-- Renkler - Sol Kolon -->
        <div class="mb-4">
            <label for="colors" class="form-label">
                <i class="fas fa-palette me-2 text-primary"></i>Renkler
            </label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                <select multiple class="form-select" id="colors" name="colors[]" size="5">
                    <?php
                    $renkler = [
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

                    foreach ($renkler as $value => $label):
                        $selected = in_array($value, $mevcut_renkler) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
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
                    <?php
                    $boyutlar = range(17, 45);
                    foreach ($boyutlar as $boyut):
                        $selected = in_array($boyut, $mevcut_boyutlar) ? 'selected' : '';
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="fas fa-box-open text-white me-2"></i>
                <h5 class="mb-0"><?php echo htmlspecialchars($urun->name); ?> Adlı Ürüne Ait Stoklar</h5>
            </div>
            <a href="<?php echo new moodle_url('/my/index.php', ['depo' => $depoid]); ?>" class="btn btn-sm btn-outline-white">
                <i class="fas fa-arrow-left me-1"></i> Geri Dön
            </a>

        </div>
        <div class="card-body">
            <h4 class="section-title">Varyasyon Listesi</h4>

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
                            <th width="30%" style="text-align: center">Stok Miktarı</th>
                            <th width="40%" style="text-align: center">İşlemler</th>
                        </tr>
                        </thead>
                        <tbody id="varyasyonTablo">
                        <!-- JavaScript ile dinamik oluşturulacak -->
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama Bilgisi -->
                <div id="pageInfo" class="text-center text-muted mt-2"></div>

                <!-- Sayfalama Kontrolleri -->
                <div id="varyasyonPagination" class="d-flex justify-content-between align-items-center mt-3">
                    <button id="prevPage" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chevron-left me-1"></i> Önceki
                    </button>
                    <button id="nextPage" class="btn btn-sm btn-outline-primary">
                        Sonraki <i class="fas fa-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>

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
            displayVariantsByPage();

            // Sayfalama kontrollerini güncelle
            updatePaginationControls();
        });

        // Belirli bir sayfadaki varyasyonları göster
        // Belirli bir sayfadaki varyasyonları göster
        function displayVariantsByPage() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, allVariants.length);
            const pageVariants = allVariants.slice(startIndex, endIndex);

            // Tabloyu temizle
            varyasyonTablo.innerHTML = '';

            // Seçili sayfadaki varyasyonları ekle
            pageVariants.forEach(variant => {
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
                const stockInput = document.createElement('span');

                // Mevcut varyasyon verisinden değeri al
                stockInput.innerText = 0; // Varsayılan değer
                if (mevcutVaryasyonlar &&
                    mevcutVaryasyonlar[variant.color.value] &&
                    mevcutVaryasyonlar[variant.color.value][variant.size.value] !== undefined) {
                    stockInput.innerText = mevcutVaryasyonlar[variant.color.value][variant.size.value];
                }

                stockCell.style.textAlign = 'center';
                stockCell.appendChild(stockInput);

                // İşlemler hücresi
                const actionsCell = document.createElement('td');
                actionsCell.className = 'd-flex justify-content-center gap-2';

                // Ürün Talep Et butonu
                const requestBtn = document.createElement('button');
                requestBtn.className = 'btn btn-sm btn-primary';
                requestBtn.innerHTML = '<i class="fas fa-shopping-cart me-1"></i> Ürün Talep Et';
                requestBtn.addEventListener('click', function() {
                    requestProduct(variant.color.value, variant.size.value, variant.color.text, variant.size.text);
                });

                // Ürün Aktar butonu
                const transferBtn = document.createElement('button');
                transferBtn.className = 'btn btn-sm btn-success';
                transferBtn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i> Ürün Aktar';
                transferBtn.addEventListener('click', function() {
                    transferProduct(variant.color.value, variant.size.value, variant.color.text, variant.size.text);
                });

                actionsCell.appendChild(requestBtn);
                actionsCell.appendChild(transferBtn);

                row.appendChild(variantCell);
                row.appendChild(stockCell);
                row.appendChild(actionsCell);
                varyasyonTablo.appendChild(row);
            });

            document.getElementById('pageInfo').textContent = `Sayfa ${currentPage} / ${Math.ceil(allVariants.length / itemsPerPage)}`;
        }


        // Ürün talep işlevi
        function requestProduct(colorValue, sizeValue, colorText, sizeText) {
            Swal.fire({
                title: 'Ürün Talebi',
                text: `${colorText} / ${sizeText} varyasyonu için ürün talebinde bulunmak istiyor musunuz?`,
                icon: 'question',
                input: 'number',
                inputPlaceholder: 'Bu alana kaç adet talep ettiğinizi yazın',
                showCancelButton: true,
                confirmButtonText: 'Evet, Talep Et',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#3e64ff',
                inputValidator: (value) => {
                    // Eğer zorunlu olmasını isterseniz şu satırı aktif edebilirsiniz:
                    // if (!value) return 'Lütfen bir açıklama girin!';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const aciklama = encodeURIComponent(result.value || ''); // Boş olabilir
                    // PHP tarafındaki değerleri JS'e geçirmeniz gerektiğini unutmayın
                    const urunid = "<?php echo $urunid; ?>";
                    const depoid = "<?php echo $depoid; ?>";

                    // Yönlendirme
                    window.location.href = `/blocks/depo_yonetimi/actions/urun_talep.php?urunid=${urunid}&depoid=${depoid}&renk=${colorValue}&beden=${sizeValue}&aciklama=${aciklama}`;
                }
            });

        }

// Ürün aktarma işlevi
        function transferProduct(colorValue, sizeValue, colorText, sizeText) {
            Swal.fire({
                title: 'Ürün Aktarımı',
                text: `${colorText} / ${sizeText} varyasyonunu başka bir depoya aktarmak istiyor musunuz?`,
                icon: 'question',
                input: 'number',
                inputPlaceholder: 'Bu alana kaç adet gönderilmesini istediğinizi yazın',
                showCancelButton: true,
                confirmButtonText: 'Evet, Aktar',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Aktarma sayfasına yönlendir
                    window.location.href = `<?php echo new moodle_url('/blocks/depo_yonetimi/actions/urun_aktar.php'); ?>?urunid=<?php echo $urunid; ?>&depoid=<?php echo $depoid; ?>&renk=${colorValue}&beden=${sizeValue}`;
                }
            });
        }

        // Sayfalama kontrollerini güncelle
        function updatePaginationControls() {
            const totalPages = Math.ceil(allVariants.length / itemsPerPage);
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');

            // Önceki sayfa butonunu güncelle
            prevPageBtn.disabled = currentPage <= 1;

            // Sonraki sayfa butonunu güncelle
            nextPageBtn.disabled = currentPage >= totalPages;

            // Sayfa bilgisini güncelle
            document.getElementById('pageInfo').textContent = `Sayfa ${currentPage} / ${totalPages}`;
        }

        // Önceki sayfa butonuna tıklama
        document.getElementById('prevPage').addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayVariantsByPage();
                updatePaginationControls();
            }
        });

        // Sonraki sayfa butonuna tıklama
        document.getElementById('nextPage').addEventListener('click', function() {
            const totalPages = Math.ceil(allVariants.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                displayVariantsByPage();
                updatePaginationControls();
            }
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
</script>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<?php
echo $OUTPUT->footer();
?>

