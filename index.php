<?php
// config.php dosyasını oluştur, yoksa basit bir yapı tanımla
if (!file_exists('config.php')) {
    // Basit bir config dosyası oluştur
    $CFG = new stdClass();
    $CFG->libdir = 'lib';
    $CFG->wwwroot = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

    // config.php oluştur
    file_put_contents('config.php', '<?php
    $CFG = new stdClass();
    $CFG->libdir = "lib";
    $CFG->wwwroot = "' . $CFG->wwwroot . '";
    ?>');

    // lib dizini oluştur
    if (!is_dir('lib')) {
        mkdir('lib', 0755, true);
    }

    // filelib.php oluştur
    if (!file_exists('lib/filelib.php')) {
        file_put_contents('lib/filelib.php', '<?php
        function get_file($url) {
            return file_get_contents($url);
        }
        ?>');
    }
} else {
    require_once('config.php');
}

// Moodle bileşenleri tanımla
if (!isset($CFG->libdir) && file_exists('lib')) {
    $CFG->libdir = 'lib';
}

// $PAGE ve $OUTPUT sınıflarını tanımla
class Page {
    public $title;
    public $heading;
    public $pagelayout;
    public $requires;

    public function __construct() {
        $this->requires = new PageRequires();
    }

    public function set_title($title) {
        $this->title = $title;
        return $this;
    }

    public function set_heading($heading) {
        $this->heading = $heading;
        return $this;
    }

    public function set_pagelayout($layout) {
        $this->pagelayout = $layout;
        return $this;
    }
}

class PageRequires {
    public function css($url) {
        echo '<link rel="stylesheet" href="'.$url.'">';
        return $this;
    }

    public function js($url, $footer = false) {
        if (!$footer) {
            echo '<script src="'.$url.'"></script>';
        } else {
            // Footer'da eklenecek scriptler için bir diziye eklenebilir
        }
        return $this;
    }
}

class Output {
    public function header() {
        global $PAGE;
        echo '<!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>'.(isset($PAGE->title) ? $PAGE->title : 'Depo Yönetimi').'</title>
        </head>
        <body>';
    }

    public function footer() {
        echo '</body></html>';
    }
}

class moodle_url {
    private $url;

    public function __construct($url) {
        $this->url = $url;
    }

    public function __toString() {
        return $this->url;
    }
}

// Değişkenleri tanımla
$PAGE = new Page();
$OUTPUT = new Output();

if (file_exists($CFG->libdir.'/filelib.php')) {
    require_once($CFG->libdir.'/filelib.php');
}

// Sayfa başlığı ayarla
$PAGE->set_title('Depo Yönetimi Sistemi');
$PAGE->set_heading('Depo Yönetimi Sistemi');
$PAGE->set_pagelayout('frontpage');

echo $OUTPUT->header();
?>

<style>
    /* ======= TEMEL DEĞIŞKENLER VE STIL TEMELLERI ======= */
    :root {
        --primary: #3498db;
        --primary-dark: #2980b9;
        --secondary: #2c3e50;
        --success: #2ecc71;
        --warning: #f39c12;
        --danger: #e74c3c;
        --info: #3498db;
        --light: #ecf0f1;
        --dark: #34495e;
        --gray: #95a5a6;
        --gray-light: #f5f7fa;

        --shadow-sm: 0 2px 6px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);

        --border-radius: 10px;
        --border-radius-sm: 6px;
        --border-radius-lg: 16px;

        --transition: all 0.25s ease;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: var(--gray-light);
        color: var(--secondary);
        line-height: 1.6;
    }

    /* ======= BAŞLIK VE HEADER STILLER ======= */
    .header-container {
        background: linear-gradient(135deg, var(--secondary), var(--primary));
        color: white;
        padding: 2.5rem 0;
        border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-lg);
    }

    .header-container h1 {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .header-container .lead {
        opacity: 0.9;
        font-weight: 300;
    }

    .header-container .btn-light {
        font-weight: 600;
    }

    /* ======= KART VE CONTAINER STILLER ======= */
    .card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        font-weight: 600;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
    }

    .card-header i {
        margin-right: 0.5rem;
        opacity: 0.8;
    }

    .card-footer {
        background-color: var(--gray-light);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.5rem;
    }

    /* ======= İSTATISTIK KARTLARI ======= */
    .status-card {
        text-align: center;
        padding: 1.75rem 1rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .status-card i {
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 1.25rem;
        opacity: 0.85;
    }

    .status-card h2 {
        font-weight: 700;
        margin-bottom: 0.25rem;
        font-size: 2rem;
    }

    .status-card p {
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    /* ======= HIZLI İŞLEMLER ======= */
    .quick-action {
        height: 100%;
        padding: 1.5rem;
        text-align: center;
        transition: var(--transition);
        border-radius: var(--border-radius);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .quick-action:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: var(--shadow-lg);
        cursor: pointer;
    }

    .quick-action i {
        font-size: 2.25rem;
        margin-bottom: 1.25rem;
        transition: var(--transition);
    }

    .quick-action h5 {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .quick-action p {
        font-size: 0.85rem;
        margin-bottom: 0;
    }

    .action-1 { color: var(--primary); }
    .action-2 { color: var(--success); }
    .action-3 { color: var(--warning); }
    .action-4 { color: var(--danger); }

    .quick-action:hover .action-1 { color: var(--primary-dark); }

    /* ======= BARKOD TARAYICI ======= */
    .barcode-scanner {
        background-color: white;
        border-radius: var(--border-radius);
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
    }

    .barcode-scanner i {
        color: var(--secondary);
        opacity: 0.8;
    }

    .barcode-scanner h5 {
        font-weight: 600;
    }

    #barcode-result {
        background-color: var(--gray-light);
        padding: 1rem;
        border-radius: var(--border-radius-sm);
    }

    /* ======= STOK GÖSTERGELERI ======= */
    .stock-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .stock-low { background-color: var(--danger); }
    .stock-medium { background-color: var(--warning); }
    .stock-high { background-color: var(--success); }

    /* ======= TABLOLAR VE LISTELER ======= */
    .table {
        margin-bottom: 0;
    }

    .table > :not(caption) > * > * {
        padding: 1rem 1.5rem;
    }

    .table-light {
        background-color: var(--gray-light);
    }

    .progress {
        background-color: rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
    }

    /* ======= GRAFIKLER ======= */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* ======= RESPONSIVE AYARLAR ======= */
    @media (max-width: 992px) {
        .header-container {
            padding: 2rem 0;
        }

        .status-card {
            margin-bottom: 1rem;
        }

        .header-container .text-end {
            text-align: left !important;
            margin-top: 1rem;
        }
    }

    @media (max-width: 768px) {
        .quick-actions-container .col-md-3 {
            width: 50%;
            margin-bottom: 1rem;
        }

        .barcode-scanner {
            padding: 1.5rem;
        }

        .barcode-scanner .col-md-2 {
            margin-bottom: 1rem;
            text-align: left !important;
        }

        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .card-header div {
            margin-top: 0.75rem;
        }
    }

    @media (max-width: 576px) {
        .quick-actions-container .col-md-3 {
            width: 100%;
        }

        .status-card i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .header-container h1 {
            font-size: 1.75rem;
        }
    }
</style>

<!-- Ana Başlık Bölümü -->
<div class="header-container">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-warehouse me-3"></i>Depo Yönetimi Sistemi</h1>
                <p class="lead">Stok takibi, envanter yönetimi ve depo operasyonları için entegre platform</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-light"><i class="fas fa-refresh me-2"></i>Yenile</button>
                <button class="btn btn-outline-light ms-2"><i class="fas fa-cog me-2"></i>Ayarlar</button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Barkod Tarayıcı -->
    <div class="barcode-scanner">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <i class="fas fa-barcode fa-3x text-secondary"></i>
            </div>
            <div class="col-md-8">
                <h5 class="mb-3">Hızlı Ürün Arama</h5>
                <div class="input-group">
                    <input type="text" class="form-control" id="barcodeInput" placeholder="Barkod numarasını girin veya taratın">
                    <button class="btn btn-primary" id="searchBtn" type="button"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="col-md-2 text-center">
                <button class="btn btn-outline-secondary" id="generateBarcode"><i class="fas fa-tag me-2"></i>Barkod Oluştur</button>
            </div>
        </div>
        <div id="barcode-result" class="mt-3 text-center" style="display: none;">
            <svg id="barcodeSvg"></svg>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card status-card">
                <i class="fas fa-boxes"></i>
                <h2 id="totalProducts">1,425</h2>
                <p class="text-muted">Toplam Ürün</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card status-card">
                <i class="fas fa-exchange-alt"></i>
                <h2 id="monthlyTransactions">368</h2>
                <p class="text-muted">Aylık İşlem</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card status-card">
                <i class="fas fa-box-open"></i>
                <h2 id="lowStock">7</h2>
                <p class="text-muted">Kritik Stok</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card status-card">
                <i class="fas fa-truck"></i>
                <h2 id="pendingOrders">12</h2>
                <p class="text-muted">Bekleyen Sipariş</p>
            </div>
        </div>
    </div>

    <!-- Hızlı İşlemler -->
    <h4 class="mb-3">Hızlı İşlemler</h4>
    <div class="row quick-actions-container mb-4">
        <div class="col-md-3 col-6">
            <div class="card status-card quick-action">
                <i class="fas fa-plus-circle action-1"></i>
                <h5>Yeni Ürün</h5>
                <p class="text-muted small mb-0">Envantere ürün ekle</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card status-card quick-action">
                <i class="fas fa-arrow-down action-2"></i>
                <h5>Stok Girişi</h5>
                <p class="text-muted small mb-0">Mevcut ürün girişi</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card status-card quick-action">
                <i class="fas fa-arrow-up action-3"></i>
                <h5>Stok Çıkışı</h5>
                <p class="text-muted small mb-0">Ürün çıkışı kaydet</p>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card status-card quick-action">
                <i class="fas fa-chart-bar action-4"></i>
                <h5>Raporlar</h5>
                <p class="text-muted small mb-0">Detaylı analiz</p>
            </div>
        </div>
    </div>
    <!-- Grafik ve Listeler -->
    <div class="row">
        <!-- Sol Taraf - Grafikler -->
        <div class="col-md-7">
            <!-- Stok Hareketleri Grafiği -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Son 30 Günlük Stok Hareketleri</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Kategori Dağılımı -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Ürün Kategorileri Dağılımı</span>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf - Analiz Kartları -->
        <div class="col-md-5">
            <!-- Depo Kullanım İstatistiği -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-warehouse me-2"></i>Depo Kullanım İstatistiği</span>
                    <div>
                        <span class="badge bg-danger me-2">2 Kritik</span>
                        <button class="btn btn-sm btn-outline-primary">Depo Detayı</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>A Deposu (Tekstil)</span>
                            <span class="fw-bold">85%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>B Deposu (Elektronik)</span>
                            <span class="fw-bold">62%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 62%" aria-valuenow="62" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>C Deposu (Gıda)</span>
                            <span class="fw-bold">41%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 41%" aria-valuenow="41" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>D Deposu (Mobilya)</span>
                            <span class="fw-bold">78%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <button class="btn btn-sm btn-outline-secondary">Kapasite Planlaması</button>
                    </div>
                </div>
            </div>

            <!-- Kritik Stok Bildirimleri -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-bell me-2"></i>Kritik Stok Bildirimleri</span>
                    <div>
                        <span class="badge bg-danger me-2">5 Kritik</span>
                        <button class="btn btn-sm btn-outline-primary">Tümünü Gör</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Ürün</th>
                            <th>Durum</th>
                            <th>Stok</th>
                            <th>İşlem</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stock-indicator stock-low me-2"></div>
                                    <span>Beyaz Gömlek L Beden</span>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">Kritik</span></td>
                            <td><strong>2</strong> / 10</td>
                            <td><button class="btn btn-sm btn-outline-danger py-0">Sipariş</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stock-indicator stock-low me-2"></div>
                                    <span>Spor Ayakkabı 42 No</span>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">Kritik</span></td>
                            <td><strong>3</strong> / 12</td>
                            <td><button class="btn btn-sm btn-outline-danger py-0">Sipariş</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stock-indicator stock-medium me-2"></div>
                                    <span>Deri Cüzdan Kahve</span>
                                </div>
                            </td>
                            <td><span class="badge bg-warning">Uyarı</span></td>
                            <td><strong>5</strong> / 8</td>
                            <td><button class="btn btn-sm btn-outline-warning py-0">Planla</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stock-indicator stock-low me-2"></div>
                                    <span>Yazlık Elbise M Beden</span>
                                </div>
                            </td>
                            <td><span class="badge bg-danger">Kritik</span></td>
                            <td><strong>1</strong> / 8</td>
                            <td><button class="btn btn-sm btn-outline-danger py-0">Sipariş</button></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="stock-indicator stock-medium me-2"></div>
                                    <span>Kış Montu XL Beden</span>
                                </div>
                            </td>
                            <td><span class="badge bg-warning">Uyarı</span></td>
                            <td><strong>4</strong> / 6</td>
                            <td><button class="btn btn-sm btn-outline-warning py-0">Planla</button></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-center bg-light">
                    <small class="text-muted">Son güncelleme: 17.07.2023 - 15:30</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Yaklaşan Teslimatlar -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-truck-loading me-2"></i>Yaklaşan Teslimatlar</span>
                    <div>
                        <span class="badge bg-primary me-2">3 Geciken</span>
                        <button class="btn btn-sm btn-outline-primary">Tüm Siparişler</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Sipariş Kodu</th>
                            <th>Tedarikçi</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><strong>SP-32145</strong></td>
                            <td>ABC Tekstil Ltd.</td>
                            <td>20.07.2023</td>
                            <td><span class="badge bg-primary">Yolda</span></td>
                        </tr>
                        <tr>
                            <td><strong>SP-32157</strong></td>
                            <td>Mega Ayakkabı A.Ş.</td>
                            <td>18.07.2023</td>
                            <td><span class="badge bg-warning">Gecikiyor</span></td>
                        </tr>
                        <tr>
                            <td><strong>SP-32162</strong></td>
                            <td>Trend Aksesuar</td>
                            <td>22.07.2023</td>
                            <td><span class="badge bg-success">Hazırlanıyor</span></td>
                        </tr>
                        <tr>
                            <td><strong>SP-32169</strong></td>
                            <td>Star Konfeksiyon</td>
                            <td>15.07.2023</td>
                            <td><span class="badge bg-danger">Beklemede</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light text-end">
                    <button class="btn btn-sm btn-outline-secondary">Yeni Sipariş Oluştur</button>
                </div>
            </div>
        </div>

        <!-- Ürün Hareket Analizi -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exchange-alt me-2"></i>Ürün Hareket Analizi</span>
                    <div>
                        <span class="badge bg-danger me-2">4 Durağan</span>
                        <button class="btn btn-sm btn-outline-primary">Detaylı Analiz</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Ürün</th>
                            <th>Kategori</th>
                            <th>Stokta Kalma</th>
                            <th>Hareket</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Spor Ayakkabı (42 No)</td>
                            <td>Ayakkabı</td>
                            <td><small>3 gün</small></td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 95%"></div>
                                </div>
                                <small class="text-success">Çok Hızlı</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Kapüşonlu Sweatshirt</td>
                            <td>Üst Giyim</td>
                            <td><small>7 gün</small></td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 75%"></div>
                                </div>
                                <small class="text-primary">Hızlı</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Deri Cüzdan (Kahve)</td>
                            <td>Aksesuar</td>
                            <td><small>14 gün</small></td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 45%"></div>
                                </div>
                                <small class="text-warning">Orta</small>
                            </td>
                        </tr>
                        <tr>
                            <!-- Bu kod "Ürün Hareket Analizi" kartının devamıdır -->
                            <td>Kış Montu (XL)</td>
                            <td>Dış Giyim</td>
                            <td><small>38 gün</small></td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 20%"></div>
                                </div>
                                <small class="text-danger">Yavaş</small>
                            </td>
                        </tr>
                        <tr>
                            <td>Klasik Takım Elbise</td>
                            <td>Resmi Giyim</td>
                            <td><small>52 gün</small></td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 10%"></div>
                                </div>
                                <small class="text-danger">Durağan</small>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <small class="text-muted">Son analiz: 17.07.2023</small>
                    <div>
                        <button class="btn btn-sm btn-outline-danger me-2">
                            <i class="fas fa-tag me-1"></i>İndirim Öner
                        </button>
                        <button class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-chart-line me-1"></i>Trend Analizi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik ve Barkod işlevleri için JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Stok Hareketleri Grafiği
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        const stockChart = new Chart(stockCtx, {
            type: 'line',
            data: {
                labels: ['1 Tem', '5 Tem', '10 Tem', '15 Tem', '20 Tem', '25 Tem', '30 Tem'],
                datasets: [
                    {
                        label: 'Giriş',
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        data: [65, 59, 80, 81, 56, 55, 72],
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Çıkış',
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        data: [32, 48, 40, 79, 63, 35, 60],
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Kategori Dağılımı Grafiği
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Giyim', 'Ayakkabı', 'Aksesuar', 'Elektronik', 'Ev Eşyası'],
                datasets: [{
                    data: [35, 25, 15, 15, 10],
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#f39c12',
                        '#9b59b6',
                        '#e74c3c'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Barkod Oluşturma İşlevi
        const barcodeInput = document.getElementById('barcodeInput');
        const generateBarcode = document.getElementById('generateBarcode');
        const barcodeResult = document.getElementById('barcode-result');

        generateBarcode.addEventListener('click', function() {
            const code = barcodeInput.value.trim() || '123456789012';

            if (code) {
                JsBarcode("#barcodeSvg", code, {
                    format: "CODE128",
                    lineColor: "#000000",
                    width: 2,
                    height: 50,
                    displayValue: true
                });
                barcodeResult.style.display = 'block';
            }
        });
    });
</script>

    <!-- Bootstrap ve diğer JavaScript kütüphanelerini dahil et -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

    <!-- İstatistik kartlarını güncelleyen JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // İstatistik kartlarını rastgele güncelleme simülasyonu
            function updateStatistics() {
                // Rastgele değişim oranları
                const productChange = Math.floor(Math.random() * 10) - 5; // -5 ile +5 arası değişim
                const transactionChange = Math.floor(Math.random() * 8) - 3; // -3 ile +5 arası değişim
                const lowStockChange = Math.floor(Math.random() * 3) - 1; // -1 ile +2 arası değişim
                const ordersChange = Math.floor(Math.random() * 4) - 2; // -2 ile +2 arası değişim

                // Mevcut değerleri al ve güncelle
                let products = parseInt(document.getElementById('totalProducts').innerText.replace(',', ''));
                let transactions = parseInt(document.getElementById('monthlyTransactions').innerText);
                let lowStock = parseInt(document.getElementById('lowStock').innerText);
                let orders = parseInt(document.getElementById('pendingOrders').innerText);

                // Değerleri değiştir
                products += productChange;
                transactions += transactionChange;
                lowStock += lowStockChange;
                orders += ordersChange;

                // Değerleri güncelle
                document.getElementById('totalProducts').innerText = products.toLocaleString();
                document.getElementById('monthlyTransactions').innerText = transactions;
                document.getElementById('lowStock').innerText = lowStock;
                document.getElementById('pendingOrders').innerText = orders;
            }

            // Her 30 saniyede bir istatistikleri güncelle
            setInterval(updateStatistics, 30000);

            // Hızlı işlem butonlarına tıklama olayları ekle
            document.querySelectorAll('.quick-action').forEach(function(button) {
                button.addEventListener('click', function() {
                    const actionText = this.querySelector('h5').innerText;

                    // SweetAlert ile bildirim göster
                    Swal.fire({
                        title: actionText,
                        text: `${actionText} işlemi başlatılıyor...`,
                        icon: 'info',
                        confirmButtonText: 'Tamam'
                    });
                });
            });

            // Arama butonuna işlevsellik ekle
            document.getElementById('searchBtn').addEventListener('click', function() {
                const searchValue = document.getElementById('barcodeInput').value.trim();

                if (searchValue) {
                    Swal.fire({
                        title: 'Ürün Arama',
                        text: `"${searchValue}" barkodlu ürün aranıyor...`,
                        icon: 'info',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Uyarı',
                        text: 'Lütfen bir barkod numarası girin',
                        icon: 'warning',
                        confirmButtonText: 'Tamam'
                    });
                }
            });
        });
    </script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Çıktıyı sonlandır
echo $OUTPUT->footer();
?>