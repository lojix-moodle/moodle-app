<?php
// Temel ayarlar ve kimlik doğrulama
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketi.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketi');
$PAGE->set_heading('Stok Hareketi');
$PAGE->set_pagelayout('admin');
$PAGE->requires->css('/blocks/depo_yonetimi/stok_list.css');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin) {
    $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
    if (!$user_depo || $user_depo != $depoid) {
        print_error('Erişim izniniz yok.');
    }
}

// Ürün bilgisini al
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
if (!$urun) {
    print_error('Ürün bulunamadı.');
}

// Minimum stok seviyesini al
$min_stok = $urun->min_stok_seviyesi ?? 0;

// Varyasyon bilgilerini al
$renkler = json_decode($urun->colors, true);
$bedenler = json_decode($urun->sizes, true);
$varyasyonlar = json_decode($urun->varyasyonlar, true);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $renk = required_param('renk', PARAM_ALPHANUMEXT);
    $beden = required_param('beden', PARAM_ALPHANUMEXT);
    $miktar = required_param('miktar', PARAM_INT);
    $islemtipi = required_param('islemtipi', PARAM_ALPHA);
    $aciklama = optional_param('aciklama', '', PARAM_TEXT);

    // Miktar değerini işlem tipine göre ayarla (giriş: pozitif, çıkış: negatif)
    if ($islemtipi == 'cikis') {
        $miktar = -$miktar;
    }

    // Mevcut varyasyon stok miktarını al
    $mevcut_miktar = $varyasyonlar[$renk][$beden] ?? 0;

    // Yeni miktar hesapla
    $yeni_miktar = $mevcut_miktar + $miktar;

    // Negatif stok kontrolü
    if ($yeni_miktar < 0) {
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]),
            'Stok miktarı negatif olamaz!',
            null,
            \core\output\notification::NOTIFY_ERROR
        );
        exit;
    }

    // Varyasyon güncelle
    $varyasyonlar[$renk][$beden] = $yeni_miktar;

    // Toplam stok hesapla
    $toplam_stok = 0;
    foreach ($varyasyonlar as $r => $bedenler) {
        foreach ($bedenler as $b => $adet) {
            $toplam_stok += $adet;
        }
    }

    // Ürün güncellemesi
    $update = new stdClass();
    $update->id = $urunid;
    $update->varyasyonlar = json_encode($varyasyonlar);
    $update->adet = $toplam_stok;
    $DB->update_record('block_depo_yonetimi_urunler', $update);

    // Stok hareketi kaydı
    $hareket = new stdClass();
    $hareket->urunid = $urunid;
    $hareket->renk = $renk;
    $hareket->beden = $beden;
    $hareket->miktar = $miktar;
    $hareket->aciklama = $aciklama;
    $hareket->islemtipi = $islemtipi;
    $hareket->userid = $USER->id;
    $hareket->tarih = time();
    $DB->insert_record('block_depo_yonetimi_stok_hareketleri', $hareket);

    // Minimum stok kontrolü
    $uyari_mesaji = '';
    if ($yeni_miktar < $min_stok) {
        $uyari_mesaji = "Uyarı: $renk renk, $beden beden için stok miktarı minimum seviyenin altına düştü! ($yeni_miktar adet kaldı)";
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]),
            'İşlem başarıyla kaydedildi. ' . $uyari_mesaji,
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    } else {
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]),
            'İşlem başarıyla kaydedildi.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exchange-alt text-white me-2"></i>
                    <h5 class="mb-0"><?php echo htmlspecialchars($urun->name); ?> - Stok Hareketi</h5>
                </div>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-sm btn-outline-white">
                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
                </a>
            </div>
            <div class="card-body">
                <form id="stokForm" method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                    <div class="row">
                        <!-- İşlem Tipi -->
                        <div class="col-md-6 mb-3">
                            <label for="islemtipi" class="form-label">
                                <i class="fas fa-directions me-2 text-primary"></i>İşlem Tipi
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-exchange-alt"></i></span>
                                <select class="form-select" id="islemtipi" name="islemtipi" required>
                                    <option value="" selected disabled>İşlem seçin</option>
                                    <option value="giris">Stok Girişi</option>
                                    <option value="cikis">Stok Çıkışı</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">Lütfen işlem tipini seçin.</div>
                        </div>

                        <!-- Renk Seçimi -->
                        <div class="col-md-6 mb-3">
                            <label for="renk" class="form-label">
                                <i class="fas fa-palette me-2 text-primary"></i>Renk
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-fill-drip"></i></span>
                                <select class="form-select" id="renk" name="renk" required>
                                    <option value="" selected disabled>Renk seçin</option>
                                    <?php foreach ($renkler as $renk): ?>
                                        <option value="<?php echo $renk; ?>"><?php echo get_string_from_value($renk, 'color'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="invalid-feedback">Lütfen bir renk seçin.</div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Beden Seçimi -->
                        <div class="col-md-6 mb-3">
                            <label for="beden" class="form-label">
                                <i class="fas fa-ruler-combined me-2 text-primary"></i>Beden
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tshirt"></i></span>
                                <select class="form-select" id="beden" name="beden" required>
                                    <option value="" selected disabled>Beden seçin</option>
                                    <?php foreach ($bedenler as $beden): ?>
                                        <option value="<?php echo $beden; ?>"><?php echo $beden; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="invalid-feedback">Lütfen bir beden seçin.</div>
                        </div>

                        <!-- Miktar -->
                        <div class="col-md-6 mb-3">
                            <label for="miktar" class="form-label">
                                <i class="fas fa-sort-numeric-up me-2 text-primary"></i>Miktar
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                <input type="number" class="form-control" id="miktar" name="miktar" min="1" required>
                            </div>
                            <div class="invalid-feedback">Geçerli bir miktar girin.</div>
                        </div>
                    </div>

                    <!-- Mevcut Stok Bilgisi -->
                    <div id="mevcutStokBilgisi" class="alert alert-info d-flex align-items-center mb-3" style="display: none;">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            <strong>Mevcut Stok:</strong> <span id="mevcutStokDeger">0</span> adet
                        </div>
                    </div>

                    <!-- Minimum Stok Uyarısı -->
                    <div id="minStokUyarisi" class="alert alert-warning d-flex align-items-center mb-3" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                        <div>
                            <strong>Uyarı:</strong> Bu işlem sonucunda stok miktarı minimum seviyenin (<span id="minStokDeger"><?php echo $min_stok; ?></span>) altına düşecek!
                        </div>
                    </div>

                    <!-- Açıklama -->
                    <div class="mb-3">
                        <label for="aciklama" class="form-label">
                            <i class="fas fa-comment-alt me-2 text-primary"></i>Açıklama (İsteğe bağlı)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-pen"></i></span>
                            <textarea class="form-control" id="aciklama" name="aciklama" rows="2" placeholder="İşlem ile ilgili açıklama ekleyebilirsiniz"></textarea>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-1"></i> İptal
                        </a>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const varyasyonlar = <?php echo $urun->varyasyonlar; ?>;
            const renkSelect = document.getElementById('renk');
            const bedenSelect = document.getElementById('beden');
            const mevcutStokBilgisi = document.getElementById('mevcutStokBilgisi');
            const mevcutStokDeger = document.getElementById('mevcutStokDeger');
            const islemTipi = document.getElementById('islemtipi');
            const miktar = document.getElementById('miktar');
            const stokForm = document.getElementById('stokForm');
            const minStokSeviyesi = <?php echo $min_stok; ?>;
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Renk ve beden seçildiğinde stok bilgisini göster
            function updateStokBilgisi() {
                const renk = renkSelect.value;
                const beden = bedenSelect.value;

                if (renk && beden) {
                    const stokMiktari = varyasyonlar[renk] && varyasyonlar[renk][beden] ? varyasyonlar[renk][beden] : 0;
                    mevcutStokDeger.textContent = stokMiktari;
                    mevcutStokBilgisi.style.display = 'flex';

                    // Stok çıkışı için maksimum değeri ayarla
                    if (islemTipi.value === 'cikis') {
                        miktar.max = stokMiktari;
                        miktar.setAttribute('max', stokMiktari);
                    } else {
                        miktar.removeAttribute('max');
                    }

                    // İşlem tipi değiştiğinde miktar kontrolünü de yap
                    kontrolEtVeUyar();
                } else {
                    mevcutStokBilgisi.style.display = 'none';
                }
            }

            renkSelect.addEventListener('change', updateStokBilgisi);
            bedenSelect.addEventListener('change', updateStokBilgisi);
            islemTipi.addEventListener('change', updateStokBilgisi);

            // Minimum stok kontrolü ve uyarı gösterimi
            function kontrolEtVeUyar() {
                if (!renkSelect.value || !bedenSelect.value || !islemTipi.value) return;

                const renk = renkSelect.value;
                const beden = bedenSelect.value;
                const stokMiktari = varyasyonlar[renk] && varyasyonlar[renk][beden] ? varyasyonlar[renk][beden] : 0;
                const yeniMiktar = parseInt(miktar.value) || 0;

                if (islemTipi.value === 'cikis') {
                    const kalanStok = stokMiktari - yeniMiktar;

                    if (kalanStok < minStokSeviyesi) {
                        document.getElementById('minStokUyarisi').style.display = 'flex';
                    } else {
                        document.getElementById('minStokUyarisi').style.display = 'none';
                    }
                } else {
                    document.getElementById('minStokUyarisi').style.display = 'none';
                }
            }

            // Miktar değiştiğinde kontrol et
            miktar.addEventListener('input', kontrolEtVeUyar);

            // Form gönderilmeden önce kontrol
            stokForm.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return false;
                }

                const renk = renkSelect.value;
                const beden = bedenSelect.value;
                const miktarValue = parseInt(miktar.value);

                if (islemTipi.value === 'cikis') {
                    const stokMiktari = varyasyonlar[renk] && varyasyonlar[renk][beden] ? varyasyonlar[renk][beden] : 0;

                    if (miktarValue > stokMiktari) {
                        e.preventDefault();
                        alert('Çıkış yapılan miktar mevcut stoktan fazla olamaz!');
                        return false;
                    }

                    // Minimum stok seviyesi kontrolü
                    const kalanStok = stokMiktari - miktarValue;
                    if (kalanStok < minStokSeviyesi) {
                        // Form gönderilmeden önce onay iste
                        if (!confirm(`DİKKAT: Bu işlem sonucunda stok miktarı minimum seviyenin (${minStokSeviyesi}) altına düşecek!\n\nDevam etmek istiyor musunuz?`)) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }

                // İşlem başlıyor, yükleniyor göster
                loadingOverlay.style.display = 'flex';
            });

            // Sayfa yüklendiğinde loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none';
            });
        });
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// get_string_from_value fonksiyonu - renk ve boyut görüntü adları için
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
    }
    return $value;
}

echo $OUTPUT->footer();
?>