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
    redirect(new moodle_url('/blocks/depo_yonetimi/view.php'), 'Depo bulunamadı', null, \core\output\notification::NOTIFY_ERROR);
}

// Ürün seçildiyse bilgilerini al
$urun = null;
$baslik = 'Tüm Stok Hareketleri';
if ($urunid) {
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if (!$urun) {
        redirect(new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]), 'Ürün bulunamadı', null, \core\output\notification::NOTIFY_ERROR);
    }
    $baslik = htmlspecialchars($urun->name) . ' - Stok Hareketleri';
}

// Tarih parametrelerini çevirme yardımcı fonksiyonu
function convert_date_param($date_str) {
    if (empty($date_str)) return 0;

    $parts = explode('/', $date_str);
    if (count($parts) === 3) {
        $day = intval($parts[0]);
        $month = intval($parts[1]);
        $year = intval($parts[2]);

        if (checkdate($month, $day, $year)) {
            return mktime(0, 0, 0, $month, $day, $year);
        }
    }

    return 0;
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
    // Bitiş tarihine 1 gün ekle (23:59:59'a kadar olan kayıtlar dahil olsun)
    $tarih_bitis_end = $tarih_bitis + 86400;
    $sql_where .= " AND sh.tarih < :tarih_bitis";
    $params['tarih_bitis'] = $tarih_bitis_end;
}

// Sıralama
$sql_sort = "";
switch ($sira) {
    case 'tarih_asc':
        $sql_sort = "ORDER BY sh.tarih ASC";
        break;
    case 'tarih_desc':
    default:
        $sql_sort = "ORDER BY sh.tarih DESC";
        break;
}

// Ana SQL sorgusu
$sql = "SELECT sh.*, sh.islemtipi as hareket_tipi, u.firstname, u.lastname, ur.name as urun_adi
        FROM {block_depo_yonetimi_stok_hareketleri} sh
        INNER JOIN {user} u ON sh.userid = u.id
        INNER JOIN {block_depo_yonetimi_urunler} ur ON sh.urunid = ur.id
        WHERE $sql_where
        $sql_sort";

$hareketler = $DB->get_records_sql($sql, $params);

// İstatistik için toplam sayıları hesapla
$giris_sayisi = 0;
$cikis_sayisi = 0;
$toplam_giris_miktari = 0;
$toplam_cikis_miktari = 0;
$son_24_saat = 0;
$simdi = time();

foreach ($hareketler as $hareket) {
    if ($hareket->hareket_tipi == 'giris') {
        $giris_sayisi++;
        $toplam_giris_miktari += $hareket->miktar;
    } else {
        $cikis_sayisi++;
        $toplam_cikis_miktari += $hareket->miktar;
    }

    if ($simdi - $hareket->tarih <= 86400) {
        $son_24_saat++;
    }
}

// Sayfayı render et
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css'));
echo $OUTPUT->header();
?>

<!-- Kütüphaneleri ekleyin -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://unpkg.com/file-saver/dist/FileSaver.min.js"></script>
<script src="https://unpkg.com/tableexport/dist/js/tableexport.min.js"></script>

<style>
    .stok-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        margin-bottom: 24px;
        background-color: #fff;
    }

    .stok-card .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        padding: 16px 20px;
        border-bottom: 1px solid rgba(0,0,0,.05);
        font-weight: 600;
    }

    .summary-card {
        border-radius: 10px;
        border-left-width: 5px;
        overflow: hidden;
    }

    .summary-card .counter {
        font-size: 1.5rem; /* Daha önce 2rem idi, küçülttük */
        font-weight: 600; /* 700'den 600'e düşürdük */
    }

    .icon-container {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,.08);
    }

    .filter-card {
        background-color: #f8f9fa;
        padding: 20px;
    }

    .filter-card .form-label {
        font-size: 0.85rem;
        font-weight: 600;
    }

    .chart-container {
        height: 300px;
        width: 100%;
        margin-bottom: 20px;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .color-badge {
        display: inline-block;
        width: 16px;
        height: 16px;
        border-radius: 4px;
        margin-right: 8px;
        border: 1px solid rgba(0,0,0,.1);
    }

    .badge-giris {
        background-color: #198754;
        color: white;
    }

    .badge-cikis {
        background-color: #dc3545;
        color: white;
    }

    .recent-row {
        background-color: rgba(0, 123, 255, 0.05);
        animation: highlight-fade 2s ease-out;
    }

    @keyframes highlight-fade {
        from { background-color: rgba(0, 123, 255, 0.2); }
        to { background-color: rgba(0, 123, 255, 0.05); }
    }

    .stok-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 2rem rgba(0,0,0,.1);
    }

    .btn-excel {
        background-color: #1D6F42;
        color: white;
        transition: all 0.3s;
    }

    .btn-excel:hover {
        background-color: #0E5A32;
        color: white;
        transform: translateY(-2px);
    }
</style>

<!-- Yükleniyor ekranı -->
<div class="loading-overlay" id="loadingOverlay" style="display:none;">
    <div class="d-flex flex-column align-items-center">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <div class="fw-medium">İşleminiz gerçekleştiriliyor...</div>
    </div>
</div>

<div class="container-fluid p-0">
    <!-- Üst Başlık ve Geri Dönüş Butonu -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h2 class="mb-1"><?php echo $baslik; ?></h2>
            <p class="text-muted mb-0">Depo: <?php echo htmlspecialchars($depo->name); ?></p>
        </div>
        <div>
            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Depoya Dön
            </a>
            <button type="button" class="btn btn-excel ms-2" id="exportBtn">
                <i class="fas fa-file-excel me-1"></i> Excel'e Aktar
            </button>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row summary-cards mb-4">
        <!-- Toplam Kayıt Sayısı -->
        <div class="col-md-3 mb-3">
            <div class="card summary-card h-100 border-primary">
                <div class="card-body d-flex">
                    <div class="icon-container bg-primary-subtle text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-uppercase text-muted mb-0">Toplam Kayıt</h6>
                        <span class="counter text-primary"><?php echo count($hareketler); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Giriş İşlemleri -->
        <div class="col-md-3 mb-3">
            <div class="card summary-card h-100 border-success">
                <div class="card-body d-flex">
                    <div class="icon-container bg-success-subtle text-success">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-uppercase text-muted mb-0">Toplam Giriş</h6>
                        <span class="counter text-success"><?php echo $giris_sayisi; ?></span>
                        <span class="text-muted ms-2">(<?php echo $toplam_giris_miktari; ?> adet)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Çıkış İşlemleri -->
        <div class="col-md-3 mb-3">
            <div class="card summary-card h-100 border-danger">
                <div class="card-body d-flex">
                    <div class="icon-container bg-danger-subtle text-danger">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-uppercase text-muted mb-0">Toplam Çıkış</h6>
                        <span class="counter text-danger"><?php echo $cikis_sayisi; ?></span>
                        <span class="text-muted ms-2">(<?php echo $toplam_cikis_miktari; ?> adet)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Son 24 Saat İşlemleri -->
        <div class="col-md-3 mb-3">
            <div class="card summary-card h-100 border-info">
                <div class="card-body d-flex">
                    <div class="icon-container bg-info-subtle text-info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="text-uppercase text-muted mb-0">Son 24 Saat</h6>
                        <span class="counter text-info"><?php echo $son_24_saat; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stok Hareketleri Grafiği -->
    <div class="card stok-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line text-primary me-2"></i>
                Stok Hareketleri Grafiği
            </h5>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="haftalikGrafik">
                    Son 7 Gün
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary active" id="aylikGrafik">
                    Son 30 Gün
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="stokHareketleriGrafik"></canvas>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <!-- Filtreler -->
    <div class="card stok-card filter-card">
        <form method="get" action="" id="filterForm">
            <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
            <?php if ($urunid): ?>
                <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
            <?php endif; ?>

            <div class="row g-3 align-items-end">
                <!-- Tarih Aralığı -->
                <div class="col-md-3"> <!-- Genişliği azalttık -->
                    <div class="row">
                        <div class="col-md-6">
                            <label for="tarih-baslangic" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="tarih-baslangic"
                                   value="<?php echo $tarih_baslangic ? date('Y-m-d', $tarih_baslangic) : ''; ?>">
                            <input type="hidden" id="tarih_baslangic_hidden" name="tarih_baslangic" value="<?php echo $tarih_baslangic; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="tarih-bitis" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="tarih-bitis"
                                   value="<?php echo $tarih_bitis ? date('Y-m-d', $tarih_bitis) : ''; ?>">
                            <input type="hidden" id="tarih_bitis_hidden" name="tarih_bitis" value="<?php echo $tarih_bitis; ?>">
                        </div>
                    </div>
                </div>

                <!-- İşlem Tipi - Genişliği artırdık ve konumunu öne taşıdık -->
                <div class="col-md-3">
                    <label for="hareket_tipi" class="form-label">İşlem Tipi</label>
                    <select name="hareket_tipi" id="hareket_tipi" class="form-select">
                        <option value="">Tüm İşlemler</option>
                        <option value="giris" <?php echo $hareket_tipi == 'giris' ? 'selected' : ''; ?>>Stok Girişleri</option>
                        <option value="cikis" <?php echo $hareket_tipi == 'cikis' ? 'selected' : ''; ?>>Stok Çıkışları</option>
                    </select>
                </div>

                <!-- Sıralama -->
                <div class="col-md-3">
                    <label for="sira" class="form-label">Sıralama</label>
                    <select name="sira" id="sira" class="form-select">
                        <option value="tarih_desc" <?php echo $sira == 'tarih_desc' ? 'selected' : ''; ?>>En Yeni</option>
                        <option value="tarih_asc" <?php echo $sira == 'tarih_asc' ? 'selected' : ''; ?>>En Eski</option>
                    </select>
                </div>

                <!-- Filtrele Butonu -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </button>
                </div>
            </div>
        </form>
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
            </div> <!-- .table-responsive kapanış -->
        </div> <!-- .card-body kapanış -->
    </div> <!-- .card kapanış -->

    <script>
        // Grafikler için veri hazırlama
        // Grafikler için veri hazırlama
        document.addEventListener('DOMContentLoaded', function() {
            // Tooltip'leri aktifleştir
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Veri hazırlama (PHP verilerini JS'de kullanmak için)
            const hareketVerileri = <?php
                // Son 30 günlük stok hareketleri
                $son30Gun = time() - (30 * 24 * 60 * 60);
                $grafik_verileri = [];

                foreach ($hareketler as $hareket) {
                    if ($hareket->tarih >= $son30Gun) {
                        $tarih = date('Y-m-d', $hareket->tarih);

                        if (!isset($grafik_verileri[$tarih])) {
                            $grafik_verileri[$tarih] = [
                                'giris' => 0,
                                'cikis' => 0
                            ];
                        }

                        if ($hareket->hareket_tipi == 'giris') {
                            $grafik_verileri[$tarih]['giris'] += $hareket->miktar;
                        } else {
                            $grafik_verileri[$tarih]['cikis'] += $hareket->miktar;
                        }
                    }
                }

                // JSON formatında döndürme
                echo json_encode($grafik_verileri);
                ?>;

            // Grafik oluşturma
            const ctx = document.getElementById('stokHareketleriGrafik').getContext('2d');
            let stokGrafik = null;

            // Grafik verilerini düzenle
            function grafikVerileriniDuzenle(gunSayisi) {
                const labels = [];
                const girisVerileri = [];
                const cikisVerileri = [];

                const baslangicTarihi = new Date();
                baslangicTarihi.setDate(baslangicTarihi.getDate() - gunSayisi);

                for (let i = 0; i <= gunSayisi; i++) {
                    const tarih = new Date(baslangicTarihi);
                    tarih.setDate(tarih.getDate() + i);

                    const tarihStr = tarih.toISOString().split('T')[0];
                    labels.push(tarih.getDate() + '/' + (tarih.getMonth() + 1));

                    girisVerileri.push(hareketVerileri[tarihStr] ? hareketVerileri[tarihStr].giris : 0);
                    cikisVerileri.push(hareketVerileri[tarihStr] ? hareketVerileri[tarihStr].cikis : 0);
                }

                return { labels, girisVerileri, cikisVerileri };
            }

            // Grafik oluştur veya güncelle
            function grafigiGuncelle(gunSayisi) {
                const { labels, girisVerileri, cikisVerileri } = grafikVerileriniDuzenle(gunSayisi);

                if (stokGrafik) {
                    stokGrafik.data.labels = labels;
                    stokGrafik.data.datasets[0].data = girisVerileri;
                    stokGrafik.data.datasets[1].data = cikisVerileri;
                    stokGrafik.update();
                } else {
                    stokGrafik = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Stok Girişleri',
                                    data: girisVerileri,
                                    backgroundColor: 'rgba(25, 135, 84, 0.6)',
                                    borderColor: 'rgb(25, 135, 84)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Stok Çıkışları',
                                    data: cikisVerileri,
                                    backgroundColor: 'rgba(220, 53, 69, 0.6)',
                                    borderColor: 'rgb(220, 53, 69)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        font: {
                                            size: 12
                                        }
                                    }
                                },
                                title: {
                                    display: false
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Varsayılan olarak aylık grafiği göster
            grafigiGuncelle(30);

            // Butonlar için olay dinleyicileri
            document.getElementById('haftalikGrafik').addEventListener('click', function() {
                document.getElementById('aylikGrafik').classList.remove('active');
                this.classList.add('active');
                grafigiGuncelle(7);
            });

            document.getElementById('aylikGrafik').addEventListener('click', function() {
                document.getElementById('haftalikGrafik').classList.remove('active');
                this.classList.add('active');
                grafigiGuncelle(30);
            });

            // Excel dışa aktarma
            const exportBtn = document.getElementById('exportBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    loadingOverlay.style.display = 'flex';

                    setTimeout(() => {
                        try {
                            const table = document.getElementById('stokHareketleriTable');
                            const exporter = new TableExport(table, {
                                headers: true,
                                footers: false,
                                formats: ['xlsx'],
                                filename: 'stok-hareketleri-raporu',
                                bootstrap: true,
                                exportButtons: false,
                                position: 'top',
                                ignoreRows: [],
                                ignoreCols: [],
                                trimWhitespace: true
                            });

                            const exportData = exporter.getExportData()['stokHareketleriTable']['xlsx'];
                            exporter.export2file(
                                exportData.data,
                                exportData.mimeType,
                                exportData.filename,
                                exportData.fileExtension
                            );

                            loadingOverlay.style.display = 'none';

                            // Başarı mesajı göster
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı!',
                                text: 'Excel raporu başarıyla oluşturuldu.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } catch (error) {
                            console.error('Excel dışa aktarma hatası:', error);
                            loadingOverlay.style.display = 'none';

                            Swal.fire({
                                icon: 'error',
                                title: 'Excel Aktarma Hatası',
                                text: 'Rapor oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.'
                            });
                        }
                    }, 500);
                });
            }

            // Tarih filtrelerini işle
            const tarihBaslangic = document.getElementById('tarih-baslangic');
            const tarihBitis = document.getElementById('tarih-bitis');
            const filterForm = document.getElementById('filterForm');

            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.style.display = 'flex';

                // Tarih verilerini hidden input'lara ekle
                if (tarihBaslangic.value) {
                    const timestamp = Math.floor(new Date(tarihBaslangic.value).getTime() / 1000);
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tarih_baslangic';
                    input.value = timestamp;
                    this.appendChild(input);
                }

                if (tarihBitis.value) {
                    const timestamp = Math.floor(new Date(tarihBitis.value).getTime() / 1000 + 86399); // Günün sonuna kadar
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tarih_bitis';
                    input.value = timestamp;
                    this.appendChild(input);
                }

                // Formu gönder
                this.submit();
            });
        });
    </script>
</div> <!-- .container-fluid kapanış -->

<?php
echo $OUTPUT->footer();
?>