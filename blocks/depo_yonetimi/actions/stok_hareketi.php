<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_login();

global $DB, $PAGE, $OUTPUT, $USER;

$urunid = required_param('urunid', PARAM_INT);
$depoid = required_param('depoid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketi.php', [
    'urunid' => $urunid,
    'depoid' => $depoid
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketi');
$PAGE->set_heading('Stok Hareketi');

// Depo ve ürün kontrolü
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);
$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid], '*', MUST_EXIST);

// Yetki kontrolü
if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
    (!has_capability('block/depo_yonetimi:viewown', context_system::instance()) || $depo->sorumluid != $USER->id)) {
    throw new moodle_exception('accessdenied', 'admin');
}

// Varyasyonları decode et
$varyasyonlar = json_decode($urun->varyasyonlar, true);
$renkler = json_decode($urun->colors, true);
$bedenler = json_decode($urun->sizes, true);
$min_stok = isset($urun->min_stok_seviyesi) ? $urun->min_stok_seviyesi : 0;

// Form gönderildiğinde işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $islemtipi = required_param('islemtipi', PARAM_ALPHA);
    $renk = required_param('renk', PARAM_TEXT);
    $beden = required_param('beden', PARAM_TEXT);
    $miktar = required_param('miktar', PARAM_INT);
    $aciklama = optional_param('aciklama', '', PARAM_TEXT);

    // Miktar kontrolü
    if ($miktar <= 0) {
        \core\notification::error('Miktar 0\'dan büyük olmalıdır.');
        redirect($PAGE->url);
    }

    // Stok miktarını güncelle
    $mevcut_stok = isset($varyasyonlar[$renk][$beden]) ? $varyasyonlar[$renk][$beden] : 0;

    if ($islemtipi === 'giris') {
        $varyasyonlar[$renk][$beden] = $mevcut_stok + $miktar;
        $hareket_miktari = $miktar; // Stok hareketi için pozitif değer
    } else {
        if ($mevcut_stok < $miktar) {
            \core\notification::error('Çıkış yapılan miktar mevcut stoktan fazla olamaz.');
            redirect($PAGE->url);
        }
        $varyasyonlar[$renk][$beden] = $mevcut_stok - $miktar;
        $hareket_miktari = -$miktar; // Stok hareketi için negatif değer
    }

    // Varyasyonları güncelle
    $urun_update = new stdClass();
    $urun_update->id = $urun->id;
    $urun_update->varyasyonlar = json_encode($varyasyonlar);

    // Toplam stok hesaplama
    $toplam_stok = 0;
    foreach ($varyasyonlar as $r => $bedenler_array) {
        foreach ($bedenler_array as $b => $stok) {
            $toplam_stok += $stok;
        }
    }
    $urun_update->adet = $toplam_stok;

    // Veritabanını güncelle
    $DB->update_record('block_depo_yonetimi_urunler', $urun_update);

    // Stok hareketini kaydet
    $stok_hareketi = new stdClass();
    $stok_hareketi->urunid = $urunid;
    $stok_hareketi->renk = $renk;
    $stok_hareketi->beden = $beden;
    $stok_hareketi->miktar = $hareket_miktari;
    $stok_hareketi->aciklama = $aciklama;
    $stok_hareketi->islemtipi = $islemtipi;
    $stok_hareketi->userid = $USER->id;
    $stok_hareketi->tarih = time();

    $DB->insert_record('block_depo_yonetimi_stok_hareketleri', $stok_hareketi);

    // Minimum stok kontrolü ve uyarı
    if ($varyasyonlar[$renk][$beden] < $min_stok) {
        \core\notification::warning("Dikkat: $renk / $beden kombinasyonu için stok miktarı minimum seviyenin altına düşmüştür.");
    }

    \core\notification::success('Stok hareketi başarıyla kaydedildi.');
    redirect(new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]));
}

echo $OUTPUT->header();
?>

    <div class="container-fluid py-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exchange-alt me-2"></i>
                    <h5 class="mb-0">Stok Hareketi: <?php echo htmlspecialchars($urun->name); ?></h5>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4 class="fw-bold">Ürün Bilgileri</h4>
                        <p><strong>Ürün Adı:</strong> <?php echo htmlspecialchars($urun->name); ?></p>
                        <p><strong>Toplam Stok:</strong> <?php echo $urun->adet; ?> adet</p>
                        <p><strong>Minimum Stok Seviyesi:</strong> <?php echo $min_stok; ?> adet</p>
                    </div>
                    <div class="col-md-6">
                        <h4 class="fw-bold">Stok Hareketi Ekle</h4>
                        <form method="post" id="stokForm" class="needs-validation" novalidate>
                            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                            <div class="mb-3">
                                <label for="islemtipi" class="form-label">İşlem Tipi</label>
                                <select name="islemtipi" id="islemtipi" class="form-select" required>
                                    <option value="giris">Stok Girişi</option>
                                    <option value="cikis">Stok Çıkışı</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="renk" class="form-label">Renk</label>
                                <select name="renk" id="renk" class="form-select" required>
                                    <option value="">Renk Seçin</option>
                                    <?php foreach ($renkler as $renk): ?>
                                        <option value="<?php echo $renk; ?>"><?php echo ucfirst($renk); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="beden" class="form-label">Beden</label>
                                <select name="beden" id="beden" class="form-select" required>
                                    <option value="">Beden Seçin</option>
                                    <?php foreach ($bedenler as $beden): ?>
                                        <option value="<?php echo $beden; ?>"><?php echo $beden; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="miktar" class="form-label">Miktar</label>
                                <input type="number" class="form-control" id="miktar" name="miktar" min="1" required>
                            </div>

                            <!-- Minimum Stok Uyarısı -->
                            <div id="minStokUyarisi" class="alert alert-warning mt-3" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Uyarı:</strong> Bu işlem sonucunda stok miktarı minimum seviyenin (<?php echo $min_stok; ?>) altına düşecek!
                            </div>


                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Açıklama (İsteğe Bağlı)</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
                            </div>

                            <div class="mb-3" id="mevcutStokBilgisi" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>Mevcut Stok:</strong> <span id="mevcutStokDeger">-</span> adet
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Kaydet
                            </button>

                            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Geri
                            </a>
                        </form>
                    </div>
                </div>
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
            const minStokSeviyesi = <?php echo $min_stok; ?>; // Minimum stok seviyesi

            // Renk ve beden seçildiğinde stok bilgisini göster
            function updateStokBilgisi() {
                const renk = renkSelect.value;
                const beden = bedenSelect.value;

                if (renk && beden) {
                    const stokMiktari = varyasyonlar[renk] && varyasyonlar[renk][beden] ? varyasyonlar[renk][beden] : 0;
                    mevcutStokDeger.textContent = stokMiktari;
                    mevcutStokBilgisi.style.display = 'block';

                    // Stok çıkışı için maksimum değeri ayarla
                    if (islemTipi.value === 'cikis') {
                        miktar.max = stokMiktari;
                        miktar.setAttribute('max', stokMiktari);
                    } else {
                        miktar.removeAttribute('max');
                    }
                } else {
                    mevcutStokBilgisi.style.display = 'none';
                }
            }

            renkSelect.addEventListener('change', updateStokBilgisi);
            bedenSelect.addEventListener('change', updateStokBilgisi);
            islemTipi.addEventListener('change', updateStokBilgisi);

            // Miktar değiştiğinde minimum stok kontrolü
            miktar.addEventListener('input', function() {
                if (!renkSelect.value || !bedenSelect.value) return;

                const renk = renkSelect.value;
                const beden = bedenSelect.value;
                const stokMiktari = varyasyonlar[renk] && varyasyonlar[renk][beden] ? varyasyonlar[renk][beden] : 0;
                const yeniMiktar = parseInt(miktar.value) || 0;

                if (islemTipi.value === 'cikis') {
                    const kalanStok = stokMiktari - yeniMiktar;

                    // Kalan stok minimum seviyenin altına düşüyorsa uyarı göster
                    if (kalanStok < minStokSeviyesi) {
                        document.getElementById('minStokUyarisi').style.display = 'block';
                    } else {
                        document.getElementById('minStokUyarisi').style.display = 'none';
                    }
                }
            });

            // Form gönderilmeden önce kontrol
            stokForm.addEventListener('submit', function(e) {
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
            });
        });
    </script>

<?php
echo $OUTPUT->footer();
?>