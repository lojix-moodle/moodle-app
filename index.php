<?php
require_once('../../config.php'); // config.php yolu Moodle kurulumuna göre ayarlanmalı
// require_once($CFG->libdir.'/filelib.php'); // filelib.php genellikle global olarak yüklüdür veya özel bir ihtiyacınız varsa eklersiniz

// require_login() ile kullanıcının oturum açmış olduğunu ve yetkilerini kontrol et
// Bu aynı zamanda $PAGE, $OUTPUT, $CFG, $USER, $SESSION gibi global değişkenleri de ayarlar.
require_login(); // Moodle oturum kontrolü ve yetkilendirme için kritik

// Sayfa başlığı ve meta etiketler
$PAGE->set_title('Depo Yönetimi Sistemi');
$PAGE->set_heading('Depo Yönetimi Sistemi');
$PAGE->set_pagelayout('frontpage'); // Ana sayfa düzeni

// CSS ve JavaScript kütüphanelerini ekle
// Bu kütüphaneler Moodle'ın JS/CSS yükleme mekanizmasıyla eklenmeli.
// Bir Moodle bloğu geliştiriyorsanız, bu kütüphaneleri block'un lib.php veya version.php dosyalarında tanımlayarak
// Moodle'ın kendi sistemini kullanmanız şiddetle tavsiye edilir.
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css'));
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js'), true);
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'), true);

echo $OUTPUT->header();
?>

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #c0392b;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Arial', sans-serif; /* Daha genel bir font */
        }

        .container {
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 15px; /* Boostrap uyumluluğu için */
            padding-right: 15px; /* Boostrap uyumluluğu için */
        }

        .header-container {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 20px 20px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: bold;
            border-radius: 10px 10px 0 0 !important; /* !important ile override */
            padding: 1rem 1.25rem;
        }

        .status-card {
            text-align: center;
            padding: 1.5rem;
        }

        .status-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }

        .status-card h2 {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .quick-action {
            display: block; /* Link olarak tüm alanı kaplasın */
            border: none;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            padding: 1.2rem;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-decoration: none; /* Link alt çizgisini kaldır */
            color: inherit; /* Metin rengini miras al */
        }

        .quick-action:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            color: inherit; /* Hoverda renk değişimi olmasın */
        }

        .quick-action i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .action-1 { color: var(--secondary-color); }
        .action-2 { color: var(--success-color); }
        .action-3 { color: var(--warning-color); }
        .action-4 { color: var(--accent-color); }

        .barcode-scanner {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .stock-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .stock-low { background-color: var(--danger-color); }
        .stock-medium { background-color: var(--warning-color); }
        .stock-high { background-color: var(--success-color); }

        .critical-alert {
            background-color: #fef2f2;
            border-left: 4px solid var(--danger-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .status-card {
                margin-bottom: 1rem;
            }

            .quick-actions-container .col-md-3 {
                width: 50%; /* Mobil cihazlarda 2 sütun */
            }
        }

        .chart-container {
            position: relative;
            height: 300px; /* Grafikler için sabit yükseklik */
            width: 100%;
        }

        /* Daha küçük kategori grafiği için */
        .chart-container.small-chart {
            height: 250px;
        }

        /* Table header styles */
        .table thead th {
            background-color: var(--light-color); /* Hafif arka plan */
            color: var(--dark-color);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .table tbody tr:last-child td {
            border-bottom: none; /* Son satırın alt çizgisini kaldır */
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.03); /* Hafif hover efekti */
        }

        .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            border-radius: .2rem;
        }

    </style>

    <div class="header-container">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-warehouse me-3"></i>Depo Yönetimi Sistemi</h1>
                    <p class="lead">Stok takibi, envanter yönetimi ve depo operasyonları için entegre platform</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light"><i class="fas fa-sync-alt me-2"></i>Yenile</button>
                    <button class="btn btn-outline-light ms-2"><i class="fas fa-cog me-2"></i>Ayarlar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="barcode-scanner">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <i class="fas fa-barcode fa-3x text-secondary"></i>
                </div>
                <div class="col-md-8 mb-3 mb-md-0">
                    <h5 class="mb-3">Hızlı Ürün Arama</h5>
                    <div class="input-group">
                        <input type="text" class="form-control" id="barcodeInput" placeholder="Barkod numarasını girin veya taratın">
                        <button class="btn btn-primary" id="searchBtn" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <button class="btn btn-outline-secondary" id="generateBarcodeBtn" type="button"><i class="fas fa-tag me-2"></i>Barkod Oluştur</button>
                </div>
            </div>
            <div id="barcode-result" class="mt-3 text-center" style="display: none;">
                <svg id="barcodeSvg"></svg>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card status-card">
                    <i class="fas fa-boxes"></i>
                    <h2 id="totalProducts">0</h2> <p class="text-muted">Toplam Ürün</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card status-card">
                    <i class="fas fa-exchange-alt"></i>
                    <h2 id="monthlyTransactions">0</h2> <p class="text-muted">Aylık İşlem</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card status-card">
                    <i class="fas fa-box-open"></i>
                    <h2 id="lowStock">0</h2> <p class="text-muted">Kritik Stok</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card status-card">
                    <i class="fas fa-truck"></i>
                    <h2 id="pendingOrders">0</h2> <p class="text-muted">Bekleyen Sipariş</p>
                </div>
            </div>
        </div>

        <h4 class="mb-3">Hızlı İşlemler</h4>
        <div class="row quick-actions-container mb-4">
            <div class="col-md-3 col-6">
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/yeni_urun.php'); ?>" class="quick-action">
                    <i class="fas fa-plus-circle action-1"></i>
                    <h5>Yeni Ürün</h5>
                    <p class="text-muted small mb-0">Envantere ürün ekle</p>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_girisi.php'); ?>" class="quick-action">
                    <i class="fas fa-arrow-down action-2"></i>
                    <h5>Stok Girişi</h5>
                    <p class="text-muted small mb-0">Mevcut ürün girişi</p>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_cikisi.php'); ?>" class="quick-action">
                    <i class="fas fa-arrow-up action-3"></i>
                    <h5>Stok Çıkışı</h5>
                    <p class="text-muted small mb-0">Ürün çıkışı kaydet</p>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/reports.php'); ?>" class="quick-action">
                    <i class="fas fa-chart-bar action-4"></i>
                    <h5>Raporlar</h5>
                    <p class="text-muted small mb-0">Detaylı analiz</p>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
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

                <div class="card mb-4"> <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Ürün Kategorileri Dağılımı</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container small-chart"> <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-warehouse me-2"></i>Depo Kullanım İstatistiği</span>
                        <div>
                            <span class="badge bg-danger me-2">2 Kritik</span>
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
                    </div>
                </div>

                <div class="card mb-4"> <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-bell me-2"></i>Kritik Stok Bildirimleri</span>
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
                                        <span><?php echo s('Beyaz Gömlek L Beden'); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-danger">Kritik</span></td>
                                <td><strong><?php echo s('2'); ?></strong> / <?php echo s('10'); ?></td>
                                <td><button class="btn btn-sm btn-outline-danger py-0">Sipariş</button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stock-indicator stock-low me-2"></div>
                                        <span><?php echo s('Spor Ayakkabı 42 No'); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-danger">Kritik</span></td>
                                <td><strong><?php echo s('3'); ?></strong> / <?php echo s('12'); ?></td>
                                <td><button class="btn btn-sm btn-outline-danger py-0">Sipariş</button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stock-indicator stock-medium me-2"></div>
                                        <span><?php echo s('Deri Cüzdan Kahve'); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-warning">Uyarı</span></td>
                                <td><strong><?php echo s('5'); ?></strong> / <?php echo s('8'); ?></td>
                                <td><button class="btn btn-sm btn-outline-warning py-0">Planla</button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stock-indicator stock-low me-2"></div>
                                        <span><?php echo s('Yazlık Elbise M Beden'); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-danger">Kritik</span></td>
                                <td><strong><?php echo s('1'); ?></strong> / <?php echo s('8'); ?></td>
                                <td><button class="btn btn-sm btn-outline-danger py-0">Sipariş</button></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stock-indicator stock-medium me-2"></div>
                                        <span><?php echo s('Kış Montu XL Beden'); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-warning">Uyarı</span></td>
                                <td><strong><?php echo s('4'); ?></strong> / <?php echo s('6'); ?></td>
                                <td><button class="btn btn-sm btn-outline-warning py-0">Planla</button></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-center bg-light">
                        <small class="text-muted">Son güncelleme: <?php echo s(date('d.m.Y - H:i')); ?></small>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-truck-loading me-2"></i>Yaklaşan Teslimatlar</span>
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
                                <td><strong><?php echo s('SP-32145'); ?></strong></td>
                                <td><?php echo s('ABC Tekstil Ltd.'); ?></td>
                                <td><?php echo s('20.07.2023'); ?></td>
                                <td><span class="badge bg-primary">Yolda</span></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo s('SP-32157'); ?></strong></td>
                                <td><?php echo s('Mega Ayakkabı A.Ş.'); ?></td>
                                <td><?php echo s('18.07.2023'); ?></td>
                                <td><span class="badge bg-warning">Gecikiyor</span></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo s('SP-32162'); ?></strong></td>
                                <td><?php echo s('Trend Aksesuar'); ?></td>
                                <td><?php echo s('22.07.2023'); ?></td>
                                <td><span class="badge bg-success">Hazırlanıyor</span></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo s('SP-32169'); ?></strong></td>
                                <td><?php echo s('Star Konfeksiyon'); ?></td>
                                <td><?php echo s('15.07.2023'); ?></td>
                                <td><span class="badge bg-danger">Beklemede</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-exchange-alt me-2"></i>Ürün Hareket Analizi</span>
                        <div>
                            <span class="badge bg-danger me-2">4 Durağan</span>
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
                                <td><?php echo s('Spor Ayakkabı (42 No)'); ?></td>
                                <td><?php echo s('Ayakkabı'); ?></td>
                                <td><small><?php echo s('3 gün'); ?></small></td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 95%"></div>
                                    </div>
                                    <small class="text-success">Çok Hızlı</small>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo s('Kapüşonlu Sweatshirt'); ?></td>
                                <td><?php echo s('Üst Giyim'); ?></td>
                                <td><small><?php echo s('7 gün'); ?></small></td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 75%"></div>
                                    </div>
                                    <small class="text-primary">Hızlı</small>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo s('Deri Cüzdan (Kahve)'); ?></td>
                                <td><?php echo s('Aksesuar'); ?></td>
                                <td><small><?php echo s('14 gün'); ?></small></td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 45%"></div>
                                    </div>
                                    <small class="text-warning">Orta</small>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo s('Kış Montu (XL)'); ?></td>
                                <td><?php echo s('Dış Giyim'); ?></td>
                                <td><small><?php echo s('38 gün'); ?></small></td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 20%"></div>
                                    </div>
                                    <small class="text-danger">Yavaş</small>
                                </td>
                            </tr>
                            <tr>
                                <td><?php echo s('Klasik Takım Elbise'); ?></td>
                                <td><?php echo s('Resmi Giyim'); ?></td>
                                <td><small><?php echo s('52 gün'); ?></small></td>
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
                        <small class="text-muted">Son analiz: <?php echo s('17.07.2023'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Barkod işlemleri
            const barcodeInput = document.getElementById('barcodeInput');
            const searchBtn = document.getElementById('searchBtn');
            const generateBarcodeBtn = document.getElementById('generateBarcodeBtn'); // ID güncellendi
            const barcodeResult = document.getElementById('barcode-result');
            const barcodeSvg = document.getElementById('barcodeSvg');

            // Moodle URL'lerini JavaScript'e aktarmanın güvenli yolu
            // Bu şekilde, PHP'den gelen wwwroot değişkeni JavaScript içinde doğrudan kullanılabilir.
            const moodleBaseUrl = '<?php echo new moodle_url('/blocks/depo_yonetimi'); ?>';

            // Barkod arama butonu
            searchBtn.addEventListener('click', function() {
                const code = barcodeInput.value.trim();
                if (code) {
                    window.location.href = moodleBaseUrl + '/actions/barkod_ara.php?code=' + encodeURIComponent(code);
                }
            });

            // Enter tuşu ile arama
            barcodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Form submit etmeyi engelle
                    const code = barcodeInput.value.trim();
                    if (code) {
                        window.location.href = moodleBaseUrl + '/actions/barkod_ara.php?code=' + encodeURIComponent(code);
                    }
                }
            });

            // Barkod oluşturma
            generateBarcodeBtn.addEventListener('click', function() {
                let code = barcodeInput.value.trim();
                if (!code) {
                    // Eğer giriş boşsa rastgele bir barkod oluştur
                    for (let i = 0; i < 12; i++) {
                        code += Math.floor(Math.random() * 10);
                    }
                    barcodeInput.value = code; // Oluşturulan barkodu inputa yaz
                }

                try {
                    barcodeResult.style.display = 'block';
                    JsBarcode(barcodeSvg, code, {
                        format: "CODE128", // Yaygın barkod formatı
                        lineColor: "#000",
                        width: 2,
                        height: 50,
                        displayValue: true // Sayısal değeri göster
                    });
                } catch (e) {
                    alert("Geçersiz barkod değeri! Lütfen geçerli bir barkod girin.");
                    console.error("Barkod oluşturulurken hata oluştu:", e);
                }
            });

            // Stok hareketleri grafiği
            const stockCtx = document.getElementById('stockChart'); // Get the canvas element directly
            if (stockCtx) { // Check if the element exists
                new Chart(stockCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['1 Haz', '5 Haz', '10 Haz', '15 Haz', '20 Haz', '25 Haz', '30 Haz'],
                        datasets: [
                            {
                                label: 'Giriş',
                                data: [65, 78, 52, 91, 43, 58, 85],
                                borderColor: '#27ae60',
                                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Çıkış',
                                data: [28, 48, 40, 19, 36, 27, 50],
                                borderColor: '#e74c3c',
                                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });
            }


            // Kategori dağılımı grafiği
            const categoryCtx = document.getElementById('categoryChart'); // Get the canvas element directly
            if (categoryCtx) { // Check if the element exists
                new Chart(categoryCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Tişört', 'Pantolon', 'Ayakkabı', 'Gömlek', 'Çanta', 'Aksesuar'],
                        datasets: [{
                            data: [35, 25, 15, 12, 8, 5],
                            backgroundColor: [
                                '#3498db',
                                '#2ecc71',
                                '#e74c3c',
                                '#f39c12',
                                '#9b59b6',
                                '#1abc9c'
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
            }

            // İstatistiklerin animasyonu
            function animateCounter(elementId, targetValue) {
                const element = document.getElementById(elementId);
                if (!element) return; // Öğenin varlığını kontrol et
                const duration = 1500;
                let startTime = null;
                const startValue = 0;

                function step(timestamp) {
                    if (!startTime) startTime = timestamp;
                    const progress = Math.min((timestamp - startTime) / duration, 1);
                    const currentValue = Math.floor(progress * (targetValue - startValue) + startValue);

                    element.innerText = currentValue.toLocaleString();

                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                }

                window.requestAnimationFrame(step);
            }

            // Sayaçları başlat
            animateCounter('totalProducts', 1425);
            animateCounter('monthlyTransactions', 368);
            animateCounter('lowStock', 7);
            animateCounter('pendingOrders', 12);
        });
    </script>

<?php
echo $OUTPUT->footer();
?>