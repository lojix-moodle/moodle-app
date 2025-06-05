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
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="counter text-success"><?php echo $toplam_giris_miktari; ?></div>
                            <div class="counter-label">Toplam Giriş Miktarı</div>
                        </div>
                        <div class="icon-container bg-success bg-opacity-10">
                            <i class="fas fa-arrow-up text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #dc3545!important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="counter text-danger"><?php echo $toplam_cikis_miktari; ?></div>
                            <div class="counter-label">Toplam Çıkış Miktarı</div>
                        </div>
                        <div class="icon-container bg-danger bg-opacity-10">
                            <i class="fas fa-arrow-down text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #0d6efd!important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="counter text-primary"><?php echo count($hareketler); ?></div>
                            <div class="counter-label">Toplam İşlem Sayısı</div>
                        </div>
                        <div class="icon-container bg-primary bg-opacity-10">
                            <i class="fas fa-exchange-alt text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card border-0" style="border-left-color: #ffc107!important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="counter text-warning"><?php echo $son_24_saat; ?></div>
                            <div class="counter-label">Son 24 Saatteki İşlemler</div>
                        </div>
                        <div class="icon-container bg-warning bg-opacity-10">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Kartı -->
    <div class="card stok-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line me-2"></i>Stok Hareketleri Grafiği
            </h5>
            <div>
                <div class="btn-group btn-group-sm" role="group">
                    <button id="haftalikGrafik" type="button" class="btn btn-outline-primary">Son 7 Gün</button>
                    <button id="aylikGrafik" type="button" class="btn btn-outline-primary active">Son 30 Gün</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <canvas id="stokHareketleriGrafigi" height="250"></canvas>
        </div>
    </div>

    <!-- Varyasyon Stokları -->
<?php if ($urun && !empty($urun->varyasyonlar) && $urun->varyasyonlar !== '0'): ?>
    <div class="card stok-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-tags me-2"></i>Varyasyon Stok Durumu
            </h5>
            <span class="badge bg-light text-dark border">
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
                                }

                                echo '<tr>';
                                echo '<td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <span class="color-badge" style="background-color: '.getColorHex($renk).';"></span>
                                            <span style="color: #212529;"><strong>'.htmlspecialchars($renk).'</strong> / '.htmlspecialchars($beden).'</span>
                                        </div>
                                      </td>';
                                echo '<td><span class="badge ' . $stokDurumu . '" style="min-width: 60px; color: #212529;">' . $miktar . ' adet</span></td>';
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

    <!-- Filtreleme Formu -->
    <div class="card stok-card filter-card mb-4">
        <div class="card-body py-3">
            <form method="get" action="" class="row gx-2 gy-2 align-items-center">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                <?php if ($urunid): ?>
                    <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" class="form-control" name="tarih_baslangic_str" id="tarih_baslangic_str"
                               value="<?php echo $tarih_baslangic ? date('Y-m-d', $tarih_baslangic) : ''; ?>"
                               placeholder="Başlangıç">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        <input type="date" class="form-control" name="tarih_bitis_str" id="tarih_bitis_str"
                               value="<?php echo $tarih_bitis ? date('Y-m-d', $tarih_bitis) : ''; ?>"
                               placeholder="Bitiş">
                    </div>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="hareket_tipi">
                        <option value="">Tüm İşlemler</option>
                        <option value="giris" <?php echo $hareket_tipi === 'giris' ? 'selected' : ''; ?>>Giriş</option>
                        <option value="cikis" <?php echo $hareket_tipi === 'cikis' ? 'selected' : ''; ?>>Çıkış</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="sira">
                        <option value="tarih_desc" <?php echo $sira === 'tarih_desc' ? 'selected' : ''; ?>>En Yeni</option>
                        <option value="tarih_asc" <?php echo $sira === 'tarih_asc' ? 'selected' : ''; ?>>En Eski</option>
                        <option value="miktar_desc" <?php echo $sira === 'miktar_desc' ? 'selected' : ''; ?>>Miktar (↓)</option>
                        <option value="miktar_asc" <?php echo $sira === 'miktar_asc' ? 'selected' : ''; ?>>Miktar (↑)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-1"></i> Filtrele
                        </button>
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </div>
                </div>

                <input type="hidden" id="tarih_baslangic" name="tarih_baslangic" value="<?php echo $tarih_baslangic; ?>">
                <input type="hidden" id="tarih_bitis" name="tarih_bitis" value="<?php echo $tarih_bitis; ?>">
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
        <?php echo count($hareketler); ?> kayıt listeleniyor
    </span>
        <?php endif; ?>
    </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <?php if (!$urunid): ?>
                            <th>Ürün</th>
                        <?php endif; ?>
                        <th>İşlem Tipi</th>
                        <th>Miktar</th>
                        <th>Varyasyon</th>
                        <th>Kullanıcı</th>
                        <th>Açıklama</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($hareketler)): ?>
                        <tr>
                            <td colspan="<?php echo !$urunid ? '7' : '6'; ?>" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Stok hareketi bulunamadı.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hareketler as $hareket): ?>
                            <?php
                            $isRecent = ($simdi - $hareket->tarih) <= 3600; // Son 1 saat içinde
                            $rowClass = $isRecent ? 'recent-row' : '';
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <div>
                                        <?php if ($isRecent): ?>
                                            <span class="badge bg-info rounded-pill me-1 pulse-animation"><i class="fas fa-bolt"></i></span>
                                        <?php endif; ?>
                                        <?php echo date('d.m.Y H:i', $hareket->tarih); ?>
                                    </div>
                                    <small class="text-muted"><?php echo date_format(date_create("@".$hareket->tarih), 'l'); ?></small>
                                </td>

                                <?php if (!$urunid): ?>
                                    <td>
                                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $hareket->urunid]); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($hareket->urun_adi); ?>
                                        </a>
                                    </td>
                                <?php endif; ?>

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

                                        // Renk JSON formatında gelebilir (["siyah"] gibi)
                                        if (!empty($hareket->renk)) {
                                            $renk = $hareket->renk;
                                            if (strpos($renk, '[') === 0) {
                                                // JSON formatındaysa parse et
                                                $renk = trim(str_replace(['"', "'", '[', ']'], '', $renk));
                                            }
                                            echo '<span class="color-badge" style="background-color: '.getColorHex($renk).';"></span>';
                                            $varyasyon_detay[] = $renk;
                                        }

                                        // Beden JSON formatında gelebilir
                                        if (!empty($hareket->beden)) {
                                            $beden = $hareket->beden;
                                            if (strpos($beden, '[') === 0) {
                                                $beden = trim(str_replace(['"', "'", '[', ']'], '', $beden));
                                            }
                                            $varyasyon_detay[] = $beden;
                                        }

                                        echo '<span style="color: #212529;">' . implode(' / ', $varyasyon_detay) . '</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>

                                <td><?php echo fullname($hareket); ?></td>

                                <td>
                                    <?php
                                    if (!empty($hareket->aciklama)) {
                                        echo htmlspecialchars(substr($hareket->aciklama, 0, 60));
                                        if (strlen($hareket->aciklama) > 60) {
                                            echo '...';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($hareketler)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Kayıtlı stok hareketi bulunamadı</h5>
                    <p class="text-secondary">Seçilen filtrelere uygun stok hareketi kaydı bulunmamaktadır.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($hareketler) && count($hareketler) > 20): ?>
                <div class="card-footer text-center bg-light py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>Toplam <strong><?php echo count($hareketler); ?></strong> kayıt listeleniyor</div>
                        <div>
                            <a href="<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/actions/stok_export.php?depoid=<?php echo $depoid; ?><?php echo $urunid ? '&urunid='.$urunid : ''; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel me-1"></i> Excel'e Aktar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filtreleme formu için tarih seçicileri
            const baslangicDateInput = document.getElementById('tarih_baslangic_str');
            const bitisDateInput = document.getElementById('tarih_bitis_str');
            const baslangicValueInput = document.getElementById('tarih_baslangic');
            const bitisValueInput = document.getElementById('tarih_bitis');

            if (baslangicDateInput && bitisDateInput) {
                // Tarih seçicilerin değişim olayları
                baslangicDateInput.addEventListener('change', function() {
                    if (this.value) {
                        const date = new Date(this.value);
                        baslangicValueInput.value = Math.floor(date.getTime() / 1000);
                    } else {
                        baslangicValueInput.value = '';
                    }
                });

                bitisDateInput.addEventListener('change', function() {
                    if (this.value) {
                        const date = new Date(this.value);
                        // Günün sonuna kadar (23:59:59)
                        date.setHours(23, 59, 59);
                        bitisValueInput.value = Math.floor(date.getTime() / 1000);
                    } else {
                        bitisValueInput.value = '';
                    }
                });
            }

            // Grafik verilerini yükleme ve gösterme
            let stokChart;
            let grafikSuresi = 30; // Varsayılan 30 gün
            let grafikTipi = 'stokseviye'; // Ürün stok seviyesi grafiği

            function grafikVerileriYukle() {
                document.getElementById('loadingOverlay').style.display = 'flex';

                // Gerçek parametreleri URL'ye ekle
                const url = new URL('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/stok_grafik_verileri.php');

                // URL parametrelerini ekle
                url.searchParams.append('depoid', <?php echo $depoid; ?>);
                url.searchParams.append('gun', grafikSuresi);
                <?php if ($urunid): ?>
                url.searchParams.append('urunid', <?php echo $urunid; ?>);
                url.searchParams.append('tip', 'stokseviye');
                <?php endif; ?>

                fetch(url.toString())
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Ağ yanıtı başarısız: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        console.log('Grafik verileri yüklendi:', data); // Hata ayıklama
                        grafikCiz(data);
                    })
                    .catch(error => {
                        document.getElementById('loadingOverlay').style.display = 'none';
                        console.error('Veri yükleme hatası:', error);
                        alert('Grafik verileri yüklenirken bir hata oluştu: ' + error.message);
                    });
            }

            function grafikCiz(data) {
                const ctx = document.getElementById('stokHareketleriGrafigi').getContext('2d');

                if (stokChart) {
                    stokChart.destroy();
                }

                if (!data || !data.labels || data.labels.length === 0) {
                    console.error('Grafik verileri boş veya hatalı format');
                    return;
                }

                // Grafik verilerini hazırla
                const datasets = [];

                // Stok seviyesi grafiği
                if (data.stokSeviyesi) {
                    datasets.push({
                        label: 'Stok Seviyesi',
                        data: data.stokSeviyesi,
                        backgroundColor: 'rgba(13, 110, 253, 0.2)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 4,
                        fill: true,
                        cubicInterpolationMode: 'monotone',
                        pointBackgroundColor: function(context) {
                            const index = context.dataIndex;
                            const value = context.dataset.data[index];
                            const previousValue = index > 0 ? context.dataset.data[index - 1] : value;
                            return value >= previousValue ? 'rgba(40, 167, 69, 1)' : 'rgba(220, 53, 69, 1)';
                        },
                        segment: {
                            borderColor: function(context) {
                                if (context.p1.parsed.y > context.p0.parsed.y) {
                                    return 'rgba(40, 167, 69, 1)'; // Artışlar yeşil
                                }
                                return 'rgba(220, 53, 69, 1)';   // Azalışlar kırmızı
                            }
                        }
                    });
                }
                // Giriş/çıkış hareketleri grafiği
                else if (data.girisler && data.cikislar) {
                    datasets.push(
                        {
                            label: 'Giriş',
                            data: data.girisler,
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 2
                        },
                        {
                            label: 'Çıkış',
                            data: data.cikislar,
                            backgroundColor: 'rgba(220, 53, 69, 0.2)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 2
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
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                },
                                title: {
                                    display: true,
                                    text: 'Miktar (adet)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Tarih'
                                }
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            intersect: false,
                            axis: 'x'
                        }
                    }
                });
            }

            // Grafik periyot butonları
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

            // Sayfa yüklendiğinde grafik verilerini yükle
            if (document.getElementById('stokHareketleriGrafigi')) {
                grafikVerileriYukle();
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>