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
        redirect(new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]), 'Ürün bulunamadı.', null, \core\output\notification::NOTIFY_ERROR);
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

        /* Loading Overlay */
        #loadingOverlay {
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

        /* Varyasyon grafikler için stiller */
        .varyasyon-grafik-card {
            transition: all 0.2s;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .varyasyon-grafik-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .varyasyon-grafik-card .card-header {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
    </style>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <div class="mt-2">Veriler yükleniyor...</div>
        </div>
    </div>

    <div class="container-fluid p-0">
    <!-- Üst Başlık ve Geri Dönüş Butonu -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3 class="mb-0"><?php echo $baslik; ?></h3>
            <p class="text-muted mb-0">
                <i class="fas fa-warehouse me-1"></i> <?php echo htmlspecialchars($depo->name); ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Depoya Dön
            </a>
            <?php if ($urunid): ?>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_ekle.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Stok Hareketi Ekle
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row summary-cards mb-4">
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #28a745!important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="counter text-success"><?php echo $giris_sayisi; ?></div>
                            <div class="counter-label">Stok Girişi</div>
                        </div>
                        <div class="icon-container bg-success bg-opacity-10">
                            <i class="fas fa-sign-in-alt fa-lg text-success"></i>
                        </div>
                    </div>
                    <div class="text-muted mt-2 small">Toplam: <?php echo $toplam_giris_miktari; ?> adet</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #dc3545!important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="counter text-danger"><?php echo $cikis_sayisi; ?></div>
                            <div class="counter-label">Stok Çıkışı</div>
                        </div>
                        <div class="icon-container bg-danger bg-opacity-10">
                            <i class="fas fa-sign-out-alt fa-lg text-danger"></i>
                        </div>
                    </div>
                    <div class="text-muted mt-2 small">Toplam: <?php echo $toplam_cikis_miktari; ?> adet</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #0d6efd!important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="counter text-primary"><?php echo count($hareketler); ?></div>
                            <div class="counter-label">Toplam İşlem</div>
                        </div>
                        <div class="icon-container bg-primary bg-opacity-10">
                            <i class="fas fa-exchange-alt fa-lg text-primary"></i>
                        </div>
                    </div>
                    <div class="text-muted mt-2 small">Son işlem: <?php echo !empty($hareketler) ? date('d.m.Y H:i', array_values($hareketler)[0]->tarih) : '-'; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #ffc107!important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="counter text-warning"><?php echo $son_24_saat; ?></div>
                            <div class="counter-label">Son 24 Saat</div>
                        </div>
                        <div class="icon-container bg-warning bg-opacity-10">
                            <i class="fas fa-clock fa-lg text-warning"></i>
                        </div>
                    </div>
                    <div class="text-muted mt-2 small">Günlük ortalama: <?php echo count($hareketler) > 0 ? round(count($hareketler) / 30, 1) : 0; ?> işlem</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Grafik Kartı (küçültüldü) -->
    <div class="card stok-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line me-2"></i>Genel Stok Grafiği
            </h5>
            <div>
                <div class="btn-group btn-group-sm" role="group">
                    <button id="haftalikGrafik" type="button" class="btn btn-outline-primary">Son 7 Gün</button>
                    <button id="aylikGrafik" type="button" class="btn btn-outline-primary active">Son 30 Gün</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <canvas id="stokHareketleriGrafigi" height="150"></canvas>
        </div>
    </div>

    <!-- Varyasyon Bazlı Mini Grafikler -->
<?php if ($urun && !empty($urun->varyasyonlar) && $urun->varyasyonlar !== '0'): ?>
    <div class="card stok-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-area me-2"></i>Varyasyon Bazlı Stok Grafikleri
            </h5>
            <div>
                <div class="btn-group btn-group-sm" role="group">
                    <button id="haftalikVaryasyonGrafik" type="button" class="btn btn-outline-primary">Son 7 Gün</button>
                    <button id="aylikVaryasyonGrafik" type="button" class="btn btn-outline-primary active">Son 30 Gün</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row" id="varyasyonGrafikContainer">
                <?php
                $varyasyonlar = json_decode($urun->varyasyonlar, true);
                if ($varyasyonlar) {
                    $index = 0;
                    foreach ($varyasyonlar as $renk => $bedenler) {
                        foreach ($bedenler as $beden => $miktar) {
                            $canvasId = 'varyasyonGrafik_' . $index;
                            ?>
                            <div class="col-md-3 mb-4">
                                <div class="card varyasyon-grafik-card h-100">
                                    <div class="card-header bg-light d-flex justify-content-between">
                                        <div>
                                            <span class="badge me-1" style="background-color: <?php echo getColorHex($renk); ?>">&nbsp;</span>
                                            <?php echo htmlspecialchars($renk . ' / ' . $beden); ?>
                                        </div>
                                        <div>
                                            <span class="badge bg-primary"><?php echo $miktar; ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body p-2">
                                        <canvas id="<?php echo $canvasId; ?>" class="varyasyon-grafik" data-renk="<?php echo htmlspecialchars($renk); ?>" data-beden="<?php echo htmlspecialchars($beden); ?>" height="100"></canvas>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $index++;
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Varyasyon Stokları -->
<?php if ($urun && !empty($urun->varyasyonlar) && $urun->varyasyonlar !== '0'): ?>
    <div class="card stok-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-layer-group me-2"></i>Varyasyon Stok Durumu
            </h5>
            <span class="badge bg-light text-dark border">
                    Toplam: <strong><?php echo $urun->adet; ?></strong> adet
                </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Renk</th>
                        <th>Beden</th>
                        <th>Stok</th>
                        <th>İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($varyasyonlar) {
                        foreach ($varyasyonlar as $renk => $bedenler) {
                            foreach ($bedenler as $beden => $miktar) {
                                ?>
                                <tr>
                                    <td>
                                        <span class="color-badge" style="background-color: <?php echo getColorHex($renk); ?>;"></span>
                                        <?php echo htmlspecialchars($renk); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($beden); ?></td>
                                    <td><strong><?php echo $miktar; ?></strong> adet</td>
                                    <td>
                                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_ekle.php', ['depoid' => $depoid, 'urunid' => $urunid, 'renk' => $renk, 'beden' => $beden]); ?>" class="btn btn-sm btn-outline-success me-1">
                                            <i class="fas fa-plus-circle"></i> Giriş
                                        </a>
                                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_ekle.php', ['depoid' => $depoid, 'urunid' => $urunid, 'renk' => $renk, 'beden' => $beden, 'tip' => 'cikis']); ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-minus-circle"></i> Çıkış
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="4" class="text-center py-3">Ürün varyasyonu bulunamadı</td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Filtreleme Formu -->
    <div class="card stok-card filter-card mb-4">
        <div class="card-body py-3">
            <form method="get" action="" class="row g-3 align-items-end">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                <?php if ($urunid): ?>
                    <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <label for="tarih_baslangic" class="form-label small text-muted">Başlangıç Tarihi</label>
                    <input type="date" class="form-control" id="tarih_baslangic" name="tarih_baslangic" value="<?php echo $tarih_baslangic ? date('Y-m-d', $tarih_baslangic) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="tarih_bitis" class="form-label small text-muted">Bitiş Tarihi</label>
                    <input type="date" class="form-control" id="tarih_bitis" name="tarih_bitis" value="<?php echo $tarih_bitis ? date('Y-m-d', $tarih_bitis) : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="hareket_tipi" class="form-label small text-muted">İşlem Tipi</label>
                    <select class="form-select" id="hareket_tipi" name="hareket_tipi">
                        <option value="">Tümü</option>
                        <option value="giris" <?php echo $hareket_tipi === 'giris' ? 'selected' : ''; ?>>Giriş</option>
                        <option value="cikis" <?php echo $hareket_tipi === 'cikis' ? 'selected' : ''; ?>>Çıkış</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sira" class="form-label small text-muted">Sıralama</label>
                    <select class="form-select" id="sira" name="sira">
                        <option value="tarih_desc" <?php echo $sira === 'tarih_desc' ? 'selected' : ''; ?>>Tarihe Göre (Yeni-Eski)</option>
                        <option value="tarih_asc" <?php echo $sira === 'tarih_asc' ? 'selected' : ''; ?>>Tarihe Göre (Eski-Yeni)</option>
                        <option value="miktar_desc" <?php echo $sira === 'miktar_desc' ? 'selected' : ''; ?>>Miktara Göre (Azalan)</option>
                        <option value="miktar_asc" <?php echo $sira === 'miktar_asc' ? 'selected' : ''; ?>>Miktara Göre (Artan)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stok Hareketleri Tablosu -->
    <div class="card stok-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-history me-2"></i>Stok Hareketleri
        </h5>
        <?php if (count($hareketler) > 0): ?>
            <span class="badge bg-light text-dark border">
                    Toplam: <?php echo count($hareketler); ?> kayıt
                </span>
        <?php endif; ?>
    </div>

    <div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Tarih</th>
                <?php if (!$urunid): ?><th>Ürün</th><?php endif; ?>
                <th>İşlem</th>
                <th>Miktar</th>
                <th>Varyasyon</th>
                <th>İşlemi Yapan</th>
                <th>Açıklama</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($hareketler)): ?>
                <tr>
                    <td colspan="<?php echo $urunid ? '6' : '7'; ?>" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Kayıtlı stok hareketi bulunamadı.
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($hareketler as $hareket): ?>
                    <tr class="<?php echo ($simdi - $hareket->tarih <= 86400) ? 'recent-row' : ''; ?>">
                        <td><?php echo date('d.m.Y H:i', $hareket->tarih); ?></td>
                        <?php if (!$urunid): ?>
                            <td>
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $hareket->urunid]); ?>">
                                    <?php echo htmlspecialchars($hareket->urun_adi); ?>
                                </a>
                            </td>
                        <?php endif; ?>
                        <td>
                            <?php if ($hareket->hareket_tipi === 'giris'): ?>
                                <span class="badge bg-success">
                        <i class="fas fa-arrow-up me-1"></i> Giriş
                    </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                        <i class="fas fa-arrow-down me-1"></i> Çıkış
                    </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $hareket->miktar; ?></td>
                        <td>
                            <?php
                            if (!empty($hareket->renk) || !empty($hareket->beden)) {
                                if (!empty($hareket->renk)) {
                                    echo '<span class="color-badge" style="background-color: '.getColorHex($hareket->renk).';"></span>';
                                    echo htmlspecialchars($hareket->renk);
                                }
                                if (!empty($hareket->renk) && !empty($hareket->beden)) {
                                    echo ' / ';
                                }
                                if (!empty($hareket->beden)) {
                                    echo htmlspecialchars($hareket->beden);
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($hareket->firstname . ' ' . $hareket->lastname); ?></td>
                        <td>
                            <?php
                            if (!empty($hareket->aciklama)) {
                                echo nl2br(htmlspecialchars($hareket->aciklama));
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Stok hareketleri grafik değişkeni
        // Stok hareketleri grafik değişkeni
        let stokChart = null;
        let grafikSuresi = 30; // Varsayılan 30 gün

        // Grafik verilerini yükleme
        function grafikVerileriYukle() {
            document.getElementById('loadingOverlay').style.display = 'flex';

            // URL parametrelerini hazırla
            const url = new URL('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/stok_grafik_verileri.php');
            url.searchParams.append('depoid', <?php echo $depoid; ?>);
            url.searchParams.append('gun', grafikSuresi);

            <?php if ($urunid): ?>
            url.searchParams.append('urunid', <?php echo $urunid; ?>);
            url.searchParams.append('tip', 'stokseviye');
            <?php endif; ?>

            fetch(url.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Ağ yanıtı başarısız oldu');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    console.log('Grafik verileri yüklendi:', data);
                    grafikCiz(data);
                })
                .catch(error => {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    console.error('Veri yükleme hatası:', error);
                    alert('Grafik verileri yüklenirken bir hata oluştu: ' + error.message);
                });
        }

        // Grafik çizme fonksiyonu - profesyonel stil
        function grafikCiz(data) {
            const ctx = document.getElementById('stokHareketleriGrafigi').getContext('2d');

            if (stokChart) {
                stokChart.destroy();
            }

            if (!data || !data.labels || data.labels.length === 0) {
                console.error('Grafik verileri boş veya hatalı format');
                return;
            }

            // Profesyonel renk paleti
            const primaryColor = '#4361ee';
            const secondaryColor = '#3a0ca3';
            const successColor = '#4cc9f0';
            const warningColor = '#f72585';

            // Grafik arka planı için gelişmiş gradientler
            const gradientPrimary = ctx.createLinearGradient(0, 0, 0, 400);
            gradientPrimary.addColorStop(0, 'rgba(67, 97, 238, 0.7)');
            gradientPrimary.addColorStop(0.5, 'rgba(67, 97, 238, 0.3)');
            gradientPrimary.addColorStop(1, 'rgba(67, 97, 238, 0.02)');

            const gradientSuccess = ctx.createLinearGradient(0, 0, 0, 400);
            gradientSuccess.addColorStop(0, 'rgba(76, 201, 240, 0.6)');
            gradientSuccess.addColorStop(0.6, 'rgba(76, 201, 240, 0.2)');
            gradientSuccess.addColorStop(1, 'rgba(76, 201, 240, 0.02)');

            const gradientWarning = ctx.createLinearGradient(0, 0, 0, 400);
            gradientWarning.addColorStop(0, 'rgba(247, 37, 133, 0.6)');
            gradientWarning.addColorStop(0.6, 'rgba(247, 37, 133, 0.2)');
            gradientWarning.addColorStop(1, 'rgba(247, 37, 133, 0.02)');

            // Grafik verilerini hazırla
            const datasets = [];
            const fontFamily = "'Inter', 'Segoe UI', 'Roboto', 'Helvetica', sans-serif";

            // Stok seviyesi grafiği
            if (data.stokSeviyesi) {
                datasets.push({
                    label: 'Stok Seviyesi',
                    data: data.stokSeviyesi,
                    backgroundColor: gradientPrimary,
                    borderColor: primaryColor,
                    borderWidth: 3,
                    tension: 0.3,
                    pointRadius: 2,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#fff',
                    pointHoverBackgroundColor: primaryColor,
                    pointBorderColor: primaryColor,
                    pointHoverBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBorderWidth: 2,
                    fill: true,
                    cubicInterpolationMode: 'monotone'
                });
            }
            // Giriş/çıkış hareketleri grafiği
            else if (data.girisler && data.cikislar) {
                datasets.push(
                    {
                        label: 'Giriş',
                        data: data.girisler,
                        backgroundColor: gradientSuccess,
                        borderColor: successColor,
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#fff',
                        pointHoverBackgroundColor: successColor,
                        pointBorderColor: successColor,
                        pointHoverBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBorderWidth: 2,
                    },
                    {
                        label: 'Çıkış',
                        data: data.cikislar,
                        backgroundColor: gradientWarning,
                        borderColor: warningColor,
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#fff',
                        pointHoverBackgroundColor: warningColor,
                        pointBorderColor: warningColor,
                        pointHoverBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBorderWidth: 2,
                    }
                );
            }

            stokChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 10,
                            right: 25,
                            bottom: 10,
                            left: 25
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyleWidth: 10,
                                boxWidth: 10,
                                padding: 20,
                                font: {
                                    family: fontFamily,
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#222',
                            bodyColor: '#444',
                            borderColor: 'rgba(0,0,0,0.05)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 12,
                            titleFont: {
                                family: fontFamily,
                                size: 14,
                                weight: '600'
                            },
                            bodyFont: {
                                family: fontFamily,
                                size: 13
                            },
                            boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                labelPointStyle: function() {
                                    return {
                                        pointStyle: 'circle',
                                        rotation: 0
                                    };
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#666',
                                font: {
                                    family: fontFamily,
                                    size: 11
                                },
                                padding: 10
                            },
                            grid: {
                                color: 'rgba(200,200,200,0.08)',
                                drawBorder: false,
                                lineWidth: 1
                            },
                            border: {
                                dash: [4, 4]
                            },
                            title: {
                                display: true,
                                text: 'Stok Miktarı',
                                color: '#666',
                                font: {
                                    family: fontFamily,
                                    size: 12,
                                    weight: '600'
                                },
                                padding: {bottom: 10}
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                color: '#666',
                                font: {
                                    family: fontFamily,
                                    size: 10
                                },
                                padding: 8
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            border: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Tarih',
                                color: '#666',
                                font: {
                                    family: fontFamily,
                                    size: 12,
                                    weight: '600'
                                },
                                padding: {top: 10}
                            }
                        }
                    },
                    animation: {
                        duration: 1200,
                        easing: 'easeOutQuart',
                        delay: function(context) {
                            return context.dataIndex * 10;
                        }
                    },
                    elements: {
                        line: {
                            borderJoinStyle: 'round',
                            capBezierPoints: true
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                },
                plugins: [{
                    id: 'customCanvasBackgroundColor',
                    beforeDraw: (chart) => {
                        const ctx = chart.canvas.getContext('2d');
                        ctx.save();
                        ctx.globalCompositeOperation = 'destination-over';
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, chart.width, chart.height);
                        ctx.restore();
                    }
                }]
            });
        }

        // Varyasyon grafiklerini yönetme
        let varyasyonGrafikleri = {};
        let grafikSuresiVaryasyon = 30;

        function varyasyonGrafikleriniYukle() {
            document.getElementById('loadingOverlay').style.display = 'flex';
            const varyasyonGrafikleriElements = document.querySelectorAll('.varyasyon-grafik');

            if (varyasyonGrafikleriElements.length === 0) {
                document.getElementById('loadingOverlay').style.display = 'none';
                return;
            }

            const islemleriTamamla = function(index) {
                if (index >= varyasyonGrafikleriElements.length) {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    return;
                }

                const canvas = varyasyonGrafikleriElements[index];
                const renk = canvas.dataset.renk;
                const beden = canvas.dataset.beden;

                // Varyasyon grafik verilerini yükle
                const url = new URL('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/stok_grafik_verileri.php');
                url.searchParams.append('depoid', <?php echo $depoid; ?>);
                url.searchParams.append('urunid', <?php echo $urunid; ?>);
                url.searchParams.append('renk', renk);
                url.searchParams.append('beden', beden);
                url.searchParams.append('gun', grafikSuresiVaryasyon);
                url.searchParams.append('tip', 'stokseviye');

                fetch(url.toString())
                    .then(response => response.json())
                    .then(data => {
                        varyasyonGrafigiCiz(canvas.id, data, renk, beden);
                        islemleriTamamla(index + 1);
                    })
                    .catch(error => {
                        console.error(`Varyasyon grafik verisi yüklenirken hata: ${renk}/${beden}`, error);
                        islemleriTamamla(index + 1);
                    });
            };

            islemleriTamamla(0);
        }

        function varyasyonGrafigiCiz(canvasId, data, renk, beden) {
            const ctx = document.getElementById(canvasId).getContext('2d');

            if (varyasyonGrafikleri[canvasId]) {
                varyasyonGrafikleri[canvasId].destroy();
            }

            // Renk kodunu al ve gelişmiş gradient oluştur
            const renkHex = getColorHex(renk);
            const renkRgb = hexToRgb(renkHex);

            const gradient = ctx.createLinearGradient(0, 0, 0, 120);
            gradient.addColorStop(0, `rgba(${renkRgb.r}, ${renkRgb.g}, ${renkRgb.b}, 0.6)`);
            gradient.addColorStop(0.5, `rgba(${renkRgb.r}, ${renkRgb.g}, ${renkRgb.b}, 0.2)`);
            gradient.addColorStop(1, `rgba(${renkRgb.r}, ${renkRgb.g}, ${renkRgb.b}, 0.05)`);

            // Daha koyu kenarlık rengi
            const borderRgb = darkenColor(renkRgb, 20); // %20 daha koyu
            const borderColor = `rgb(${borderRgb.r}, ${borderRgb.g}, ${borderRgb.b})`;

            const fontFamily = "'Inter', 'Segoe UI', 'Roboto', 'Helvetica', sans-serif";

            varyasyonGrafikleri[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: renk + '/' + beden,
                        data: data.stokSeviyesi,
                        backgroundColor: gradient,
                        borderColor: borderColor,
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointHoverBackgroundColor: borderColor,
                        pointBorderColor: borderColor,
                        pointHoverBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                        pointHoverBorderWidth: 2,
                        cubicInterpolationMode: 'monotone'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    layout: {
                        padding: {
                            top: 5,
                            right: 10,
                            bottom: 5,
                            left: 10
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#333',
                            bodyColor: '#333',
                            borderColor: 'rgba(0,0,0,0.05)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: false,
                            padding: 10,
                            titleFont: {
                                family: fontFamily,
                                size: 13,
                                weight: '600'
                            },
                            bodyFont: {
                                family: fontFamily,
                                size: 12
                            },
                            callbacks: {
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label;
                                },
                                label: function(context) {
                                    return `Stok: ${context.raw} adet`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            display: false
                        },
                        x: {
                            display: false
                        }
                    },
                    elements: {
                        line: {
                            borderWidth: 2.5,
                            borderJoinStyle: 'round',
                            capBezierPoints: true
                        }
                    },
                    animation: {
                        duration: 800,
                        easing: 'easeOutQuart',
                        delay: function(context) {
                            return context.dataIndex * 5;
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                },
                plugins: [{
                    id: 'customCanvasBackgroundColor',
                    beforeDraw: (chart) => {
                        const ctx = chart.canvas.getContext('2d');
                        ctx.save();
                        ctx.globalCompositeOperation = 'destination-over';
                        ctx.fillStyle = 'rgba(255,255,255,0.6)';
                        ctx.fillRect(0, 0, chart.width, chart.height);
                        ctx.restore();
                    }
                }]
            });
        }

        // Renk koyulaştırma fonksiyonu
        function darkenColor(rgb, percent) {
            return {
                r: Math.max(0, Math.floor(rgb.r * (1 - percent / 100))),
                g: Math.max(0, Math.floor(rgb.g * (1 - percent / 100))),
                b: Math.max(0, Math.floor(rgb.b * (1 - percent / 100)))
            };
        }

        // HEX renk kodunu RGB değerlere dönüştürme
        function hexToRgb(hex) {
            // HEX # ile başlıyorsa kaldır
            hex = hex.replace(/^#/, '');

            // Kısa formatı (3 karakter) tam formata (6 karakter) dönüştür
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }

            // RGB değerlerini hesapla
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);

            return { r, g, b };
        }

        // Renk kodları - modern renk paleti ile güncellendi
        function getColorHex(colorName) {
            const colorMap = {
                'kirmizi': '#e63946',
                'mavi': '#4361ee',
                'siyah': '#1d3557',
                'beyaz': '#f8f9fa',
                'yesil': '#2ec4b6',
                'sari': '#ffbe0b',
                'turuncu': '#fb8500',
                'mor': '#7b2cbf',
                'pembe': '#ff006e',
                'gri': '#8d99ae',
                'bej': '#E4DAD2',
                'lacivert': '#1a52b3',
                'kahverengi': '#a05d34',
                'haki': '#588157',
                'vizon': '#9a8c7f',
                'bordo': '#9d0208'
            };

            return colorMap[colorName] || '#8d99ae';
        }

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            // Grafik butonları
            document.getElementById('haftalikGrafik').addEventListener('click', function() {
                document.getElementById('haftalikGrafik').classList.add('active');
                document.getElementById('aylikGrafik').classList.remove('active');
                grafikSuresi = 7;
                grafikVerileriYukle();
            });

            document.getElementById('aylikGrafik').addEventListener('click', function() {
                document.getElementById('aylikGrafik').classList.add('active');
                document.getElementById('haftalikGrafik').classList.remove('active');
                grafikSuresi = 30;
                grafikVerileriYukle();
            });

            // Varyasyon grafik butonları
            if (document.getElementById('haftalikVaryasyonGrafik')) {
                document.getElementById('haftalikVaryasyonGrafik').addEventListener('click', function() {
                    document.getElementById('haftalikVaryasyonGrafik').classList.add('active');
                    document.getElementById('aylikVaryasyonGrafik').classList.remove('active');
                    grafikSuresiVaryasyon = 7;
                    varyasyonGrafikleriniYukle();
                });
            }

            if (document.getElementById('aylikVaryasyonGrafik')) {
                document.getElementById('aylikVaryasyonGrafik').addEventListener('click', function() {
                    document.getElementById('aylikVaryasyonGrafik').classList.add('active');
                    document.getElementById('haftalikVaryasyonGrafik').classList.remove('active');
                    grafikSuresiVaryasyon = 30;
                    varyasyonGrafikleriniYukle();
                });
            }

            // Grafikleri yükle
            grafikVerileriYukle();

            // Varyasyon grafiklerini yükle
            if (document.querySelector('.varyasyon-grafik')) {
                varyasyonGrafikleriniYukle();
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>