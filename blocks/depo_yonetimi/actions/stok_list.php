<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php'); // stok_hareketi_kaydet fonksiyonunu içe aktar
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stoklar');
$PAGE->set_heading('Stoklar');
$PAGE->set_pagelayout('admin');

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

$urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);

if (!$urun) {
    print_error('Ürün bulunamadı.');
}

// Form gönderildiğinde işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    // Ana stok miktarını güncelle
    $eski_miktar = $urun->adet;
    $yeni_miktar = required_param('adet', PARAM_INT);

    // Stoku güncelle
    $urun->adet = $yeni_miktar;

    // Stok hareketi türünü belirle
    if ($yeni_miktar > $eski_miktar) {
        $islem_turu = 'ekleme';
    } elseif ($yeni_miktar < $eski_miktar) {
        $islem_turu = 'azaltma';
    } else {
        $islem_turu = 'guncelleme';
    }

    // Stok hareketini kaydet (ana ürün için)
    if ($yeni_miktar != $eski_miktar) {
        stok_hareketi_kaydet(
            $depoid,
            $urunid,
            null, // renk
            null, // boyut
            $eski_miktar,
            $yeni_miktar,
            $islem_turu,
            'Ana stok güncellemesi'
        );
    }

    // Varyasyon stoklarını güncelle
    $varyasyonlar = [];
    if (isset($_POST['varyasyonlar']) && is_array($_POST['varyasyonlar'])) {
        foreach ($_POST['varyasyonlar'] as $color => $sizes) {
            foreach ($sizes as $size => $yeni_miktar) {
                // Eski miktarı bul
                $eski_miktar = 0;
                if (isset($mevcut_varyasyonlar[$color][$size])) {
                    $eski_miktar = $mevcut_varyasyonlar[$color][$size];
                }

                // Varyasyon değerini kaydet
                $varyasyonlar[$color][$size] = $yeni_miktar;

                // Stok hareketi türünü belirle
                if ($yeni_miktar > $eski_miktar) {
                    $islem_turu = 'ekleme';
                } elseif ($yeni_miktar < $eski_miktar) {
                    $islem_turu = 'azaltma';
                } else {
                    $islem_turu = 'guncelleme';
                }

                // Stok değişimi varsa kaydet
                if ($yeni_miktar != $eski_miktar) {
                    stok_hareketi_kaydet(
                        $depoid,
                        $urunid,
                        $color,
                        $size,
                        $eski_miktar,
                        $yeni_miktar,
                        $islem_turu,
                        'Varyasyon stok güncellemesi'
                    );
                }
            }
        }
    }

    // Ürünü güncelle
    $urun->varyasyonlar = json_encode($varyasyonlar);
    $DB->update_record('block_depo_yonetimi_urunler', $urun);

    \core\notification::success('Stok başarıyla güncellendi.');
    redirect($PAGE->url);
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


// Renk ve boyutlar için etiketleri elde etme yardımcı fonksiyonu
function get_string_from_value($value, $type) {
    if ($type == 'color') {
        $colors = [
            'siyah' => 'Siyah',
            'beyaz' => 'Beyaz',
            'kirmizi' => 'Kırmızı',
            'mavi' => 'Mavi',
            'yesil' => 'Yeşil',
            'sari' => 'Sarı',
            'turuncu' => 'Turuncu',
            'mor' => 'Mor',
            'pembe' => 'Pembe',
            'gri' => 'Gri',
            'kahverengi' => 'Kahverengi',
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
            'xxl' => 'XXL'
        ];
        return isset($sizes[$value]) ? $sizes[$value] : $value;
    }
    return $value;
}

echo $OUTPUT->header();
?>

    <style>
        /* CSS kodu buraya */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner-container {
            text-align: center;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .color-sample {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }

        .siyah { background-color: #000; }
        .beyaz { background-color: #fff; border: 1px solid #ddd; }
        .kirmizi { background-color: #dc3545; }
        .mavi { background-color: #0d6efd; }
        .yesil { background-color: #198754; }
        .sari { background-color: #ffc107; }
        .turuncu { background-color: #fd7e14; }
        .mor { background-color: #6f42c1; }
        .pembe { background-color: #d63384; }
        .gri { background-color: #6c757d; }
        .kahverengi { background-color: #8b4513; }
        .bordo { background-color: #800000; }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p class="mt-3 mb-0">İşleminiz Yapılıyor...</p>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo new moodle_url('/my'); ?>">Ana Sayfa</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>"><?php echo $depo->name; ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $urun->name; ?> - Stok</li>
                        </ol>
                    </nav>
                </div>

                <div>
                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-outline-info">
                        <i class="fas fa-history"></i> Stok Hareketleri
                    </a>
                    <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            </div>

            <div class="card-body">
                <h3 class="mb-4">
                    <i class="fas fa-box-open text-primary me-2"></i>
                    <?php echo htmlspecialchars($urun->name); ?> - Stok Bilgisi
                </h3>

                <form action="" method="POST" id="stokForm">
                    <?php echo html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey())); ?>

                    <div class="mb-4">
                        <h5 class="mb-3">Ana Stok</h5>
                        <div class="mb-3 row">
                            <label for="adet" class="col-sm-2 col-form-label">Adet:</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control" id="adet" name="adet" value="<?php echo $urun->adet; ?>" min="0" required>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($mevcut_renkler) && !empty($mevcut_boyutlar)): ?>
                        <div class="mb-4">
                            <h5 class="mb-3">Varyasyonlar</h5>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Renk</th>
                                        <?php foreach ($mevcut_boyutlar as $boyut): ?>
                                            <th><?php echo get_string_from_value($boyut, 'size'); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($mevcut_renkler as $renk): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <span class="color-sample <?php echo $renk; ?>"></span>
                                                    <?php echo get_string_from_value($renk, 'color'); ?>
                                                </div>
                                            </td>
                                            <?php foreach ($mevcut_boyutlar as $boyut): ?>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                           name="varyasyonlar[<?php echo $renk; ?>][<?php echo $boyut; ?>]"
                                                           value="<?php echo isset($mevcut_varyasyonlar[$renk][$boyut]) ? $mevcut_varyasyonlar[$renk][$boyut] : 0; ?>"
                                                           min="0">
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Stok Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('stokForm');
            const submitBtn = document.getElementById('submitBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');

            form.addEventListener('submit', function() {
                loadingOverlay.style.display = 'flex';
                submitBtn.disabled = true;
            });
        });
    </script>

<?php
echo $OUTPUT->footer();
?>