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
    redirect(new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]), 'Depo veya ürün bulunamadı.', null, \core\output\notification::NOTIFY_ERROR);
}

// İşlem yapıldı mı?
$islem_yapildi = false;
$islem_mesaji = '';

// Form gönderildi mi?
if ($data = data_submitted() && confirm_sesskey()) {
    $hareket_tipi = required_param('hareket_tipi', PARAM_ALPHA);
    $miktar = required_param('miktar', PARAM_INT);
    $aciklama = optional_param('aciklama', '', PARAM_TEXT);
    $renk = optional_param('renk', '', PARAM_TEXT);
    $beden = optional_param('beden', '', PARAM_TEXT);

    // Renk ve beden varyasyonu var mı kontrol et
    $renk = empty($renk) ? null : $renk;
    $beden = empty($beden) ? null : $beden;

    if ($miktar <= 0) {
        $islem_mesaji = 'Miktar 0\'dan büyük olmalıdır.';
    } else {
        $sonuc = block_depo_yonetimi_stok_hareketi_kaydet(
            $urunid, $depoid, $miktar, $hareket_tipi, $aciklama, $renk, $beden
        );

        if ($sonuc) {
            $islem_yapildi = true;
            $islem_tipi = ($hareket_tipi == 'giris') ? 'girişi' : 'çıkışı';
            $islem_mesaji = "$miktar adet ürün stok $islem_tipi başarıyla kaydedildi.";
        } else {
            $islem_mesaji = 'Stok hareketi kaydedilirken bir hata oluştu.';
        }
    }
}

echo $OUTPUT->header();

// CSS eklemeleri
?>
    <style>
        .stok-giris {
            color: #28a745;
            animation: fadeInUp 0.5s ease-out;
        }

        .stok-cikis {
            color: #dc3545;
            animation: fadeInDown 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeInDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .stok-preview {
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .stok-giris-preview {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .stok-cikis-preview {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>

    <!-- İşlem başarılıysa notification göster -->
<?php if ($islem_yapildi): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $islem_mesaji; ?>
    </div>
<?php elseif (!empty($islem_mesaji)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-times-circle mr-2"></i> <?php echo $islem_mesaji; ?>
    </div>
<?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-exchange-alt text-primary mr-2"></i>
                    Stok Hareketi
                </h4>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Geri Dön
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Ürün bilgisi -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2">Ürün Bilgileri</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Depo:</strong> <?php echo htmlspecialchars($depo->name); ?></p>
                        <p><strong>Ürün:</strong> <?php echo htmlspecialchars($urun->name); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Mevcut Stok:</strong>
                            <span class="badge bg-<?php echo ($urun->adet > 10) ? 'success' : (($urun->adet > 3) ? 'warning' : 'danger'); ?> rounded-pill px-3 py-2">
                            <?php echo $urun->adet; ?> adet
                        </span>
                        </p>
                    </div>
                </div>
            </div>

            <form method="post" action="<?php echo $PAGE->url; ?>">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <!-- Hareket tipi -->
                <div class="form-group mb-3">
                    <label for="hareket_tipi" class="form-label"><i class="fas fa-exchange-alt mr-1"></i> Hareket Tipi</label>
                    <select class="form-select" id="hareket_tipi" name="hareket_tipi" required>
                        <option value="giris">Stok Girişi (Ekleme)</option>
                        <option value="cikis">Stok Çıkışı (Azaltma)</option>
                    </select>
                </div>

                <!-- Varyasyon alanı -->
                <?php
                $has_variations = !empty($urun->colors) && $urun->colors != '0' && !empty($urun->sizes) && $urun->sizes != '0';
                if ($has_variations):
                    // Renk ve beden bilgilerini al
                    $colors = json_decode($urun->colors, true);
                    $sizes = json_decode($urun->sizes, true);
                    ?>
                    <div class="form-group mb-3">
                        <label for="renk" class="form-label"><i class="fas fa-palette mr-1"></i> Renk Seçimi</label>
                        <select class="form-select" id="renk" name="renk">
                            <option value="">Renk Seçin</option>
                            <?php foreach ($colors as $color): ?>
                                <option value="<?php echo htmlspecialchars($color['value']); ?>"><?php echo htmlspecialchars($color['text']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="beden" class="form-label"><i class="fas fa-ruler mr-1"></i> Beden Seçimi</label>
                        <select class="form-select" id="beden" name="beden">
                            <option value="">Beden Seçin</option>
                            <?php foreach ($sizes as $size): ?>
                                <option value="<?php echo htmlspecialchars($size['value']); ?>"><?php echo htmlspecialchars($size['text']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Miktar -->
                <div class="form-group mb-3">
                    <label for="miktar" class="form-label"><i class="fas fa-sort-numeric-up mr-1"></i> Miktar</label>
                    <input type="number" class="form-control" id="miktar" name="miktar" min="1" value="1" required>
                    <div id="stok-preview" class="stok-preview"></div>
                </div>

                <!-- Açıklama -->
                <div class="form-group mb-3">
                    <label for="aciklama" class="form-label"><i class="fas fa-comment-alt mr-1"></i> Açıklama</label>
                    <textarea class="form-control" id="aciklama" name="aciklama" rows="3" placeholder="İsteğe bağlı açıklama ekleyebilirsiniz"></textarea>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-times mr-1"></i> İptal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Stok Hareketini Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stok Geçmişi -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">
                <i class="fas fa-history text-primary mr-2"></i> Son Stok Hareketleri
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Hareket</th>
                        <th>Miktar</th>
                        <th>Açıklama</th>
                        <th>Kullanıcı</th>
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
                                    <i class="fas fa-arrow-up mr-1"></i> Giriş
                                </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                    <i class="fas fa-arrow-down mr-1"></i> Çıkış
                                </span>
                                    <?php endif; ?>

                                    <?php if (!empty($hareket->renk) || !empty($hareket->beden)): ?>
                                        <small class="ms-2 text-muted">
                                            <?php
                                            $varyasyon_detay = [];
                                            if (!empty($hareket->renk)) $varyasyon_detay[] = $hareket->renk;
                                            if (!empty($hareket->beden)) $varyasyon_detay[] = $hareket->beden;
                                            echo implode(' / ', $varyasyon_detay);
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $hareket->miktar; ?> adet</td>
                                <td><?php echo !empty($hareket->aciklama) ? htmlspecialchars($hareket->aciklama) : '-'; ?></td>
                                <td><?php echo fullname($hareket); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-list mr-1"></i> Tüm Hareketleri Görüntüle
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hareketTipi = document.getElementById('hareket_tipi');
            const miktarInput = document.getElementById('miktar');
            const stokPreview = document.getElementById('stok-preview');
            const mevcutStok = <?php echo $urun->adet; ?>;

            function updateStokDurumu() {
                const tip = hareketTipi.value;
                const miktar = parseInt(miktarInput.value) || 0;

                if (miktar <= 0) {
                    stokPreview.innerHTML = '';
                    stokPreview.className = 'stok-preview';
                    return;
                }

                let yeniStok = mevcutStok;
                let iconClass = '';
                let previewClass = '';
                let message = '';

                if (tip === 'giris') {
                    yeniStok += miktar;
                    iconClass = 'stok-giris';
                    previewClass = 'stok-giris-preview';
                    message = '<i class="fas fa-arrow-up"></i> Stok girişi sonrası: <strong>' + yeniStok + ' adet</strong>';
                } else {
                    yeniStok -= miktar;
                    iconClass = 'stok-cikis';
                    previewClass = 'stok-cikis-preview';

                    if (yeniStok < 0) {
                        message = '<i class="fas fa-exclamation-triangle"></i> <strong>Uyarı:</strong> Yeterli stok bulunmuyor. Mevcut stok: ' + mevcutStok + ' adet';
                    } else {
                        message = '<i class="fas fa-arrow-down"></i> Stok çıkışı sonrası: <strong>' + yeniStok + ' adet</strong>';
                    }
                }

                stokPreview.innerHTML = '<div class="' + iconClass + '">' + message + '</div>';
                stokPreview.className = 'stok-preview ' + previewClass;
            }

            // Olay dinleyicileri
            hareketTipi.addEventListener('change', updateStokDurumu);
            miktarInput.addEventListener('input', updateStokDurumu);

            // Sayfa yüklenince çalıştır
            updateStokDurumu();
        });
    </script>

<?php
echo $OUTPUT->footer();
?>