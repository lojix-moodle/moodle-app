<?php
require_once('../../../config.php');
global $DB, $PAGE, $OUTPUT, $USER, $CFG;

require_once($CFG->dirroot.'/blocks/depo_yonetimi/lib.php');

$depoid = required_param('depoid', PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT);

// Sayfa ayarları
$PAGE->set_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $urunid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketleri');
$PAGE->set_heading('Stok Hareketleri');
$PAGE->navbar->add('Depo Yönetimi', new moodle_url('/blocks/depo_yonetimi/view.php'));
$PAGE->navbar->add('Stok Hareketleri');

// Depo bilgilerini kontrol et
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
if (!$depo) {
    redirect(new moodle_url('/blocks/depo_yonetimi/view.php'), 'Depo bulunamadı.', null, \core\output\notification::NOTIFY_ERROR);
}

// Ürün seçildiyse bilgilerini al
$urun = null;
$baslik = 'Tüm Stok Hareketleri';
if ($urunid) {
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if ($urun) {
        $baslik = htmlspecialchars($urun->name) . ' - Stok Hareketleri';
    } else {
        $urunid = 0; // Ürün bulunamadıysa filtreleme yapma
    }
}

// Filtreleme ve sıralama
$tarih_baslangic = optional_param('tarih_baslangic', 0, PARAM_INT);
$tarih_bitis = optional_param('tarih_bitis', 0, PARAM_INT);
$hareket_tipi = optional_param('hareket_tipi', '', PARAM_ALPHA);
$sira = optional_param('sira', 'tarih_desc', PARAM_TEXT);

// SQL sorgusu oluştur
$params = array('depoid' => $depoid);
$sql_where = "ur.depoid = :depoid";

if ($urunid) {
    $sql_where .= " AND sh.urunid = :urunid";
    $params['urunid'] = $urunid;
}

if ($hareket_tipi) {
    $sql_where .= " AND sh.islemtipi = :hareket_tipi";
    $params['hareket_tipi'] = $hareket_tipi;
}

// Tarih filtreleme
if ($tarih_baslangic) {
    $sql_where .= " AND sh.tarih >= :tarih_baslangic";
    $params['tarih_baslangic'] = $tarih_baslangic;
}

if ($tarih_bitis) {
    $sql_where .= " AND sh.tarih <= :tarih_bitis";
    $params['tarih_bitis'] = $tarih_bitis;
}

// Sıralama
switch ($sira) {
    case 'tarih_asc':
        $sort = "sh.tarih ASC";
        break;
    case 'tarih_desc':
        $sort = "sh.tarih DESC";
        break;
    case 'miktar_asc':
        $sort = "sh.miktar ASC";
        break;
    case 'miktar_desc':
        $sort = "sh.miktar DESC";
        break;
    default:
        $sort = "sh.tarih DESC";
}

// Ana SQL sorgusu
$sql = "SELECT sh.*, sh.islemtipi as hareket_tipi, u.firstname, u.lastname, ur.name as urun_adi
        FROM {block_depo_yonetimi_stok_hareketleri} sh
        JOIN {user} u ON u.id = sh.userid
        JOIN {block_depo_yonetimi_urunler} ur ON ur.id = sh.urunid
        WHERE $sql_where
        ORDER BY $sort";

$hareketler = $DB->get_records_sql($sql, $params);

// İstatistik için toplam sayıları hesapla
$giris_sayisi = 0;
$cikis_sayisi = 0;
$toplam_giris_miktari = 0;
$toplam_cikis_miktari = 0;
$son_24_saat = 0;
$simdi = time();

foreach ($hareketler as $hareket) {
    if ($hareket->hareket_tipi === 'giris') {
        $giris_sayisi++;
        $toplam_giris_miktari += $hareket->miktar;
    } else {
        $cikis_sayisi++;
        $toplam_cikis_miktari += $hareket->miktar;
    }

    if ($simdi - $hareket->tarih <= 86400) { // Son 24 saat içinde
        $son_24_saat++;
    }
}

// Sayfayı render et
echo $OUTPUT->header();
?>

<style>
    /* Genel stil ayarları */
    .stok-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.2s;
        margin-bottom: 20px;
    }
    .stok-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .stok-card .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        border-radius: 8px 8px 0 0;
        padding: 15px 20px;
    }
    .stok-card .card-title {
        font-weight: 600;
        color: #3e64ff;
    }
    .stok-card .card-body {
        padding: 20px;
    }

    /* Özet kartları */
    .summary-card {
        border-left: 4px solid;
        border-radius: 5px;
        transition: all 0.2s;
    }
    .summary-card:hover {
        transform: translateY(-2px);
    }
    .summary-card .counter {
        font-size: 1.8rem;
        font-weight: 600;
    }
    .summary-card .counter-label {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .summary-card .icon-container {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    /* Tablo stilleri */
    .table-hover tbody tr:hover {
        background-color: rgba(62, 100, 255, 0.04);
    }
    .table th {
        border-top: none;
        font-weight: 600;
    }
    .badge-giris {
        background-color: #28a745;
        color: #fff;
    }
    .badge-cikis {
        background-color: #dc3545;
        color: #fff;
    }
    .pulse-animation {
        animation: pulse 1.5s infinite;
    }

    /* Renk işaretleyici */
    .color-badge {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 3px;
        margin-right: 5px;
    }

    /* Form elemanları */
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #ced4da;
        padding: 8px 12px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #3e64ff;
        box-shadow: 0 0 0 0.25rem rgba(62, 100, 255, 0.25);
    }
    .filter-card {
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    /* Animasyonlar */
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.9; }
        100% { transform: scale(1); opacity: 1; }
    }
    .recent-row {
        border-left: 3px solid #3e64ff;
    }

    /* Responsive ayarlar */
    @media (max-width: 768px) {
        .summary-cards .col-md-3 {
            margin-bottom: 15px;
        }
    }
</style>

<div class="container-fluid p-0">
    <!-- Üst Başlık ve Geri Dönüş Butonu -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-exchange-alt text-primary me-2"></i>
                <?php echo $baslik; ?>
            </h2>
            <p class="text-muted mb-0">
                <i class="fas fa-warehouse me-1"></i>
                <?php echo htmlspecialchars($depo->name); ?> deposunda stok hareketleri
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($urunid): ?>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_ekle.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Yeni Hareket Ekle
                </a>
            <?php endif; ?>
            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Depoya Dön
            </a>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row summary-cards mb-4">
        <div class="col-md-3">
            <div class="summary-card card border-0 shadow-sm" style="border-left-color: #3e64ff;">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <div class="counter"><?php echo count($hareketler); ?></div>
                        <div class="counter-label">Toplam Hareket</div>
                    </div>
                    <div class="icon-container bg-primary bg-opacity-10">
                        <i class="fas fa-exchange-alt fa-fw text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card card border-0 shadow-sm" style="border-left-color: #28a745;">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <div class="counter"><?php echo $giris_sayisi; ?></div>
                        <div class="counter-label">Stok Girişi</div>
                    </div>
                    <div class="icon-container bg-success bg-opacity-10">
                        <i class="fas fa-arrow-up fa-fw text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card card border-0 shadow-sm" style="border-left-color: #dc3545;">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <div class="counter"><?php echo $cikis_sayisi; ?></div>
                        <div class="counter-label">Stok Çıkışı</div>
                    </div>
                    <div class="icon-container bg-danger bg-opacity-10">
                        <i class="fas fa-arrow-down fa-fw text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card card border-0 shadow-sm" style="border-left-color: #17a2b8;">
                <div class="card-body d-flex justify-content-between align-items-center p-3">
                    <div>
                        <div class="counter"><?php echo $son_24_saat; ?></div>
                        <div class="counter-label">Son 24 Saatte</div>
                    </div>
                    <div class="icon-container bg-info bg-opacity-10">
                        <i class="fas fa-clock fa-fw text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Varyasyon Stokları -->
    <?php if ($urun && !empty($urun->varyasyonlar) && $urun->varyasyonlar !== '0'): ?>
        <div class="card stok-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tags me-2"></i>Varyasyon Stok Durumu
                </h5>
                <span class="badge bg-light text-dark  border">
                Toplam: <strong><?php echo $urun->adet; ?></strong> adet
            </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th style="width: 60%">Varyasyon</th>
                            <th style="width: 40%">Mevcut Stok</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $varyasyonlar = json_decode($urun->varyasyonlar, true);
                        if ($varyasyonlar) {
                            foreach ($varyasyonlar as $renk => $bedenler) {
                                foreach ($bedenler as $beden => $miktar) {
                                    $stokDurumu = '';
                                    if ($miktar <= 0) {
                                        $stokDurumu = 'bg-danger text-white';
                                    } elseif ($miktar <= 5) {
                                        $stokDurumu = 'bg-warning text-dark';
                                    } else {
                                        // Normal stok durumu için metin rengini siyah yap
                                        $stokDurumu = 'text-dark';
                                    }

                                    echo '<tr>';
                                    echo '<td class="align-middle">
                <div class="d-flex align-items-center">
                    <span class="color-badge" style="background-color: '.getColorHex($renk).';"></span>
                    <strong style="color: #212529;">'.htmlspecialchars($renk).'</strong> / '.htmlspecialchars($beden).'
                </div>
              </td>';
                                    echo '<td><span class="badge ' . $stokDurumu . '" style="min-width: 60px;">' . $miktar . ' adet</span></td>';
                                    echo '</tr>';
                                }
                            }
                        } else {
                            echo '<tr><td colspan="2" class="text-center py-3">Varyasyon bilgisi bulunamadı.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtreler -->
    <div class="card stok-card filter-card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtreleme ve Sıralama
            </h5>
        </div>
        <div class="card-body">
            <form method="get" action="" id="filterForm" class="mb-0">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                <?php if ($urunid): ?>
                    <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
                <?php endif; ?>

                <div class="row align-items-end g-3">
                    <?php if (!$urunid): ?>
                        <div class="col-md-3 col-sm-6">
                            <label for="urun-filtre" class="form-label fw-medium">Ürün Seç</label>
                            <select id="urun-filtre" name="urunid" class="form-select">
                                <option value="">Tüm Ürünler</option>
                                <?php
                                $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid], 'name ASC');
                                foreach ($urunler as $u) {
                                    echo '<option value="' . $u->id . '">' . htmlspecialchars($u->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-<?php echo $urunid ? '3' : '2'; ?> col-sm-6">
                        <label for="hareket-filtre" class="form-label fw-medium">Hareket Tipi</label>
                        <select id="hareket-filtre" name="hareket_tipi" class="form-select">
                            <option value="">Tümü</option>
                            <option value="giris" <?php echo $hareket_tipi === 'giris' ? 'selected' : ''; ?>>
                                <i class="fas fa-arrow-up"></i> Stok Girişi
                            </option>
                            <option value="cikis" <?php echo $hareket_tipi === 'cikis' ? 'selected' : ''; ?>>
                                <i class="fas fa-arrow-down"></i> Stok Çıkışı
                            </option>
                        </select>
                    </div>

                    <div class="col-md-<?php echo $urunid ? '3' : '2'; ?> col-sm-6">
                        <label for="tarih-baslangic" class="form-label fw-medium">Başlangıç Tarihi</label>
                        <input type="date" id="tarih-baslangic" class="form-control"
                               value="<?php echo $tarih_baslangic ? date('Y-m-d', $tarih_baslangic) : ''; ?>">
                    </div>

                    <div class="col-md-<?php echo $urunid ? '3' : '2'; ?> col-sm-6">
                        <label for="tarih-bitis" class="form-label fw-medium">Bitiş Tarihi</label>
                        <input type="date" id="tarih-bitis" class="form-control"
                               value="<?php echo $tarih_bitis ? date('Y-m-d', $tarih_bitis) : ''; ?>">
                    </div>

                    <div class="col-md-<?php echo $urunid ? '3' : '2'; ?> col-sm-6">
                        <label for="sira-filtre" class="form-label fw-medium">Sıralama</label>
                        <select id="sira-filtre" name="sira" class="form-select">
                            <option value="tarih_desc" <?php echo $sira === 'tarih_desc' ? 'selected' : ''; ?>>En Yeni</option>
                            <option value="tarih_asc" <?php echo $sira === 'tarih_asc' ? 'selected' : ''; ?>>En Eski</option>
                            <option value="miktar_desc" <?php echo $sira === 'miktar_desc' ? 'selected' : ''; ?>>Miktar (Çok → Az)</option>
                            <option value="miktar_asc" <?php echo $sira === 'miktar_asc' ? 'selected' : ''; ?>>Miktar (Az → Çok)</option>
                        </select>
                    </div>

                    <div class="col-md-<?php echo $urunid ? '12' : '4'; ?> col-sm-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-1"></i> Filtrele
                        </button>

                        <?php if (!empty($_GET) && (count($_GET) > 1)): ?>
                            <a href="?depoid=<?php echo $depoid; ?><?php echo $urunid ? '&urunid='.$urunid : ''; ?>"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Filtreleri Temizle
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($hareketler)): ?>
                            <button type="button" class="btn btn-success" id="exportBtn">
                                <i class="fas fa-download me-1"></i> Excel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hareket Tablosu -->
    <div class="card stok-card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-alt me-2"></i>
                Stok Hareketleri Listesi
            </h5>
            <span class="badge bg-primary"><?php echo count($hareketler); ?> kayıt</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="stokHareketleriTable">
                    <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Ürün</th>
                        <th>İşlem</th>
                        <th>Miktar</th>
                        <th>Varyasyon</th>
                        <th>Açıklama</th>
                        <th>İşlemi Yapan</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($hareketler)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-search text-muted mb-3" style="font-size: 2.5rem; opacity: 0.6;"></i>
                                    <h5 class="text-muted">Kayıt Bulunamadı</h5>
                                    <p class="text-muted">Filtreleme kriterlerinize uygun stok hareketi bulunamadı.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $simdi = time();
                        foreach ($hareketler as $hareket):
                            $son24saat = ($simdi - $hareket->tarih <= 86400);
                            ?>
                            <tr class="<?php echo $son24saat ? 'recent-row' : ''; ?>">
                                <td data-sort="<?php echo $hareket->tarih; ?>">
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium"><?php echo date('d.m.Y', $hareket->tarih); ?></span>
                                        <small class="text-muted"><?php echo date('H:i', $hareket->tarih); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($hareket->urun_adi); ?></td>
                                <td>
                                    <?php if ($hareket->hareket_tipi == 'giris'): ?>
                                        <span class="badge badge-giris">
                                            <i class="fas fa-arrow-up me-1"></i> Giriş
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-cikis">
                                            <i class="fas fa-arrow-down me-1"></i> Çıkış
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $hareket->miktar; ?></strong> adet
                                </td>
                                <td>
                                    <?php
                                    if (!empty($hareket->renk) || !empty($hareket->beden)) {
                                        $varyasyon_detay = [];

                                        if (!empty($hareket->renk)) {
                                            $renk = $hareket->renk;
                                            if (strpos($renk, '[') === 0) {
                                                $renk = trim(str_replace(['"', "'", '[', ']'], '', $renk));
                                            }
                                            echo '<span class="color-badge" style="background-color: '.getColorHex($renk).'"></span>';
                                            $varyasyon_detay[] = $renk;
                                        }

                                        if (!empty($hareket->beden)) {
                                            $beden = $hareket->beden;
                                            if (strpos($beden, '[') === 0) {
                                                $beden = trim(str_replace(['"', "'", '[', ']'], '', $beden));
                                            }
                                            $varyasyon_detay[] = $beden;
                                        }

                                        echo implode(' / ', $varyasyon_detay);
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($hareket->aciklama)): ?>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                              data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($hareket->aciklama); ?>">
                                            <?php echo htmlspecialchars($hareket->aciklama); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2 bg-light text-primary d-flex align-items-center justify-content-center"
                                             style="width: 30px; height: 30px; border-radius: 50%;">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <?php echo fullname($hareket); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div

                <!-- JavaScript - Tarih filtrelerini ve tooltip işlemleri -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Tooltip'leri aktifleştir
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

                    // Tarih filtrelerini işle
                    const tarihBaslangic = document.getElementById('tarih-baslangic');
                    const tarihBitis = document.getElementById('tarih-bitis');
                    const filterForm = document.getElementById('filterForm');

                    filterForm.addEventListener('submit', function(e) {
                        e.preventDefault();

                        // Başlangıç tarihini unix timestamp'e dönüştür
                        if (tarihBaslangic.value) {
                            const baslangicDate = new Date(tarihBaslangic.value);
                            const baslangicTimestamp = Math.floor(baslangicDate.getTime() / 1000);

                            const hiddenBaslangic = document.createElement('input');
                            hiddenBaslangic.type = 'hidden';
                            hiddenBaslangic.name = 'tarih_baslangic';
                            hiddenBaslangic.value = baslangicTimestamp;
                            filterForm.appendChild(hiddenBaslangic);
                        }

                        // Bitiş tarihini unix timestamp'e dönüştür
                        if (tarihBitis.value) {
                            const bitisDate = new Date(tarihBitis.value);
                            bitisDate.setHours(23, 59, 59); // Günün sonuna ayarla
                            const bitisTimestamp = Math.floor(bitisDate.getTime() / 1000);

                            const hiddenBitis = document.createElement('input');
                            hiddenBitis.type = 'hidden';
                            hiddenBitis.name = 'tarih_bitis';
                            hiddenBitis.value = bitisTimestamp;
                            filterForm.appendChild(hiddenBitis);
                        }

                        // Formu gönder
                        filterForm.submit();
                    });

                    // Excel export işlemi
                    const exportBtn = document.getElementById('exportBtn');
                    if (exportBtn) {
                        exportBtn.addEventListener('click', function() {
                            const table = document.getElementById('stokHareketleriTable');
                            const fileName = '<?php echo $baslik; ?> - Stok Hareketleri.xlsx';

                            // TableExport kütüphanesi ile Excel export işlemi
                            const exporter = new TableExport(table, {
                                headers: true,
                                footers: false,
                                formats: ['xlsx'],
                                filename: fileName,
                                bootstrap: true,
                                exportButtons: false,
                                position: 'bottom',
                                ignoreRows: null,
                                ignoreCols: null,
                                trimWhitespace: true,
                                RTL: false,
                                sheetname: 'Stok Hareketleri'
                            });

                            const exportData = exporter.getExportData()[table.id]['xlsx'];
                            exporter.export2file(
                                exportData.data,
                                exportData.mimeType,
                                exportData.filename,
                                exportData.fileExtension
                            );
                        });
                    }
                });
            </script>
        </div> <!-- .container-fluid kapanış -->

        <?php
        echo $OUTPUT->footer();
        ?>