<?php
require_once('../../../config.php');
global $DB, $PAGE, $OUTPUT, $USER, $CFG;

require_once($CFG->dirroot.'/blocks/depo_yonetimi/lib.php');

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

// Sayfa ayarları
$PAGE->set_url('/blocks/depo_yonetimi/actions/stok_ekle.php', ['depoid' => $depoid, 'urunid' => $urunid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketi');
$PAGE->set_heading('Stok Hareketi');
$PAGE->navbar->add('Depo Yönetimi', new moodle_url('/blocks/depo_yonetimi/view.php'));
$PAGE->navbar->add('Stok Hareketi');

// Depo ve ürün bilgileri
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);

if (!$depo || !$urun) {
    redirect(new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]), 'Ürün veya depo bulunamadı', null, \core\output\notification::NOTIFY_ERROR);
}

// Kategori bilgisi
$kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $urun->kategoriid]);
$kategori_adi = $kategori ? $kategori->name : 'Kategorisiz';

// İşlem yapıldı mı?
$islem_yapildi = false;
$islem_mesaji = '';

// Form gönderildi mi?
if ($data = data_submitted() && confirm_sesskey()) {
    $miktar = required_param('miktar', PARAM_INT);
    $hareket_tipi = required_param('hareket_tipi', PARAM_ALPHA);
    $aciklama = optional_param('aciklama', '', PARAM_TEXT);
    $renk = optional_param('renk', '', PARAM_TEXT);
    $beden = optional_param('beden', '', PARAM_TEXT);

    // Stok hareketi kaydet
    $sonuc = block_depo_yonetimi_stok_hareketi_kaydet(
        $urunid,
        $depoid,
        $miktar,
        $hareket_tipi,
        $aciklama,
        $renk,
        $beden
    );

    if ($sonuc) {
        $islem_yapildi = true;
        $islem_mesaji = ($hareket_tipi === 'giris' ? 'Stok girişi' : 'Stok çıkışı') . ' başarıyla kaydedildi.';

        // Ürün bilgisini güncelle (sayfadaki gösterim için)
        $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    } else {
        $islem_mesaji = 'Stok hareketi kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.';
        if ($hareket_tipi === 'cikis' && $miktar > $urun->adet) {
            $islem_mesaji = 'Yetersiz stok! Çıkış yapılmak istenen miktar mevcut stok miktarından fazla.';
        }
    }
}

// Varyasyonlu bir ürün mü?
$varyasyonlu = (!empty($urun->colors) && $urun->colors !== '0') && (!empty($urun->sizes) && $urun->sizes !== '0');
$colors = $varyasyonlu ? json_decode($urun->colors) : [];
$sizes = $varyasyonlu ? json_decode($urun->sizes) : [];


echo $OUTPUT->header();

// CSS eklemeleri
?>
    <style>
        .stok-card {
            border: 0;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .stok-badge {
            font-size: 0.95rem;
            padding: 0.5rem 0.85rem;
        }
        .color-badge {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            border-radius: 3px;
        }
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>

    <!-- Loading Overlay -->
    <div id="loading" style="display: none;">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <div class="mt-2">İşlem gerçekleştiriliyor...</div>
        </div>
    </div>

<?php if ($islem_yapildi): ?>
    <!-- Başarı mesajı -->
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-check-circle"></i> Başarılı!</strong> <?php echo $islem_mesaji; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif (!empty($islem_mesaji)): ?>
    <!-- Hata mesajı -->
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-circle"></i> Hata!</strong> <?php echo $islem_mesaji; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <div class="row">
        <!-- Sol kolon - Ürün bilgileri ve stok hareketi formu -->
        <div class="col-md-6">
            <div class="card mb-4 stok-card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cube text-primary me-2"></i>
                            Ürün Bilgileri
                        </h5>
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Depoya Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h4 class="mb-3"><?php echo htmlspecialchars($urun->name); ?></h4>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted">Kategori:</span>
                                <span class="badge bg-light text-dark ms-1"><?php echo htmlspecialchars($kategori_adi); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted">Güncel Stok:</span>
                                <span class="badge bg-<?php echo ($urun->adet > 10 ? 'success' : ($urun->adet > 3 ? 'warning' : 'danger')); ?> ms-1 stok-badge">
                                <?php echo $urun->adet; ?> adet
                            </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($varyasyonlu): ?>
                        <div class="mb-3">
                            <span class="text-muted">Varyasyonlar:</span>
                            <div class="mt-2">
                                <?php foreach ($colors as $color): ?>
                                    <span class="badge bg-light text-dark me-1 mb-1">
                                <span class="color-badge" style="background-color: <?php echo getColorHex($color); ?>"></span>
                                <?php echo htmlspecialchars($color); ?>
                            </span>
                                <?php endforeach; ?>

                                <?php if (!empty($sizes)): ?>
                                    <div class="mt-2">
                                        <?php foreach ($sizes as $size): ?>
                                            <span class="badge bg-light text-dark me-1 mb-1">
                                        <?php echo htmlspecialchars($size); ?>
                                    </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stok Hareketi Formu -->
            <div class="card stok-card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exchange-alt text-primary me-2"></i>
                        Yeni Stok Hareketi
                    </h5>
                </div>
                <div class="card-body">
                    <form id="stokForm" method="post" action="">
                        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="hareket_tipi" class="form-label">Hareket Tipi</label>
                                <select id="hareket_tipi" name="hareket_tipi" class="form-select" required>
                                    <option value="giris">Stok Girişi</option>
                                    <option value="cikis">Stok Çıkışı</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="miktar" class="form-label">Miktar</label>
                                <input type="number" id="miktar" name="miktar" class="form-control" min="1" value="1" required>
                                <div class="invalid-feedback">
                                    Lütfen geçerli bir miktar girin.
                                </div>
                            </div>
                        </div>

                        <?php if ($varyasyonlu): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="renk" class="form-label">Renk</label>
                                    <select id="renk" name="renk" class="form-select">
                                        <option value="">Renk Seçin</option>
                                        <?php foreach ($colors as $color): ?>
                                            <option value="<?php echo htmlspecialchars($color); ?>"><?php echo htmlspecialchars($color); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="beden" class="form-label">Beden</label>
                                    <select id="beden" name="beden" class="form-select">
                                        <option value="">Beden Seçin</option>
                                        <?php foreach ($sizes as $size): ?>
                                            <option value="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="aciklama" class="form-label">Açıklama</label>
                            <textarea id="aciklama" name="aciklama" class="form-control" rows="2" placeholder="Hareket ile ilgili açıklama (opsiyonel)"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Stok Hareketini Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sağ kolon - Son Stok Hareketleri -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history text-primary me-2"></i>
                        Son Stok Hareketleri
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>İşlem</th>
                                <th>Miktar</th>
                                <th>Varyasyon</th>
                                <th>İşlemi Yapan</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $hareketler = block_depo_yonetimi_stok_hareketleri_getir($urunid, $depoid, 5);
                            if (empty($hareketler)):
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-info-circle text-muted mr-1"></i>
                                        Bu ürüne ait stok hareketi kaydı henüz bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($hareketler as $hareket): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', $hareket->tarih); ?></td>
                                        <td>
                                            <?php if ($hareket->hareket_tipi == 'giris'): ?>
                                                <span class="badge bg-success">
                                        <i class="fas fa-arrow-up me-1"></i> Giriş
                                    </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                        <i class="fas fa-arrow-down me-1"></i> Çıkış
                                    </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo $hareket->miktar; ?></strong> adet</td>
                                        <td>
                                            <?php
                                            if (!empty($hareket->renk) || !empty($hareket->beden)) {
                                                $varyasyon_detay = [];
                                                if (!empty($hareket->renk)) {
                                                    echo '<span class="badge me-1" style="background-color: '.getColorHex($hareket->renk).'">&nbsp;</span>';
                                                    $varyasyon_detay[] = $hareket->renk;
                                                }
                                                if (!empty($hareket->beden)) $varyasyon_detay[] = $hareket->beden;
                                                echo implode(' / ', $varyasyon_detay);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo fullname($hareket); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list me-1"></i> Tüm Stok Hareketlerini Görüntüle
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stokForm = document.getElementById('stokForm');
            const hareket_tipiSelect = document.getElementById('hareket_tipi');
            const miktarInput = document.getElementById('miktar');
            const submitBtn = document.getElementById('submitBtn');
            const loadingOverlay = document.getElementById('loading');

            // Form gönderimine kontroller
            stokForm.addEventListener('submit', function(e) {
                // Form validasyonu
                if (!stokForm.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    stokForm.classList.add('was-validated');
                    return;
                }

                // Stok çıkışı için kontrol
                if (hareket_tipiSelect.value === 'cikis') {
                    const guncelStok = <?php echo $urun->adet; ?>;
                    const istenenMiktar = parseInt(miktarInput.value);

                    if (istenenMiktar > guncelStok) {
                        e.preventDefault();
                        alert('Yetersiz stok! Çıkış yapılmak istenen miktar (' + istenenMiktar + ') mevcut stok miktarından (' + guncelStok + ') fazla olamaz.');
                        return;
                    }
                }

                // Form gönderilirken loading overlay göster
                loadingOverlay.style.display = 'flex';
                submitBtn.disabled = true;
            });

            // Miktar değeri için kontrol
            miktarInput.addEventListener('input', function() {
                if (parseInt(this.value) < 1) {
                    this.value = 1;
                }
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
    </script>

<?php
echo $OUTPUT->footer();

/**
 * Renk kodlarını almak için yardımcı fonksiyon
 *
 * @param string $colorName Renk adı
 * @return string Renk hex kodu
 */
function getColorHex($colorName) {
    $colorMap = [
        'kirmizi' => '#dc3545',
        'mavi' => '#0d6efd',
        'siyah' => '#212529',
        'beyaz' => '#f8f9fa',
        'yesil' => '#198754',
        'sari' => '#ffc107',
        'turuncu' => '#fd7e14',
        'mor' => '#6f42c1',
        'pembe' => '#d63384',
        'gri' => '#6c757d',
        'bej' => '#E4DAD2',
        'lacivert' => '#11098A',
        'kahverengi' => '#8B4513',
        'haki' => '#8A9A5B',
        'vizon' => '#A89F91',
        'bordo' => '#800000'
    ];

    return isset($colorMap[$colorName]) ? $colorMap[$colorName] : '#6c757d';
}
?>