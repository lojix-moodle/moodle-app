<?php
require_once('config.php');
require_once($CFG->libdir.'/filelib.php');

// Sayfa başlığı ve meta etiketler
$PAGE->set_title('Depo Yönetimi Sistemi');
$PAGE->set_heading('Depo Yönetimi Sistemi');
$PAGE->set_pagelayout('frontpage');

// CSS ve JavaScript kütüphanelerini ekle
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
            border-radius: 10px 10px 0 0 !important;
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
        }

        .quick-action {
            padding: 1.2rem;
            text-align: center;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 1rem;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .quick-action:hover {
            transform: scale(1.05);
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
        .quick-action.btn {
            display: block;
            border: none;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            padding: 1.2rem;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .quick-action.btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .quick-action.btn:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }


        @media (max-width: 768px) {
            .status-card {
                margin-bottom: 1rem;
            }

            .quick-actions-container .col-md-3 {
                width: 50%;
            }
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
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
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Filtre
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Son 7 Gün</a></li>
                                <li><a class="dropdown-item" href="#">Son 30 Gün</a></li>
                                <li><a class="dropdown-item" href="#">Son 90 Gün</a></li>
                            </ul>
                        </div>
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
                        <button class="btn btn-sm btn-outline-secondary">Detay</button>
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

            <!-- İlk Kart: Yaklaşan Siparişler -->
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

            <!-- Yeni Kart: Ürün Hareket Analizi -->
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

    <!-- JavaScript Kodu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Barkod işlemleri
            const barcodeInput = document.getElementById('barcodeInput');
            const searchBtn = document.getElementById('searchBtn');
            const generateBtn = document.getElementById('generateBarcode');
            const barcodeResult = document.getElementById('barcode-result');
            const barcodeSvg = document.getElementById('barcodeSvg');

            // Barkod arama butonu
            searchBtn.addEventListener('click', function() {
                const code = barcodeInput.value.trim();
                if (code) {
                    window.location.href = 'actions/barkod_ara.php?code=' + code;
                }
            });

            // Enter tuşu ile arama
            barcodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const code = barcodeInput.value.trim();
                    if (code) {
                        window.location.href = 'actions/barkod_ara.php?code=' + code;
                    }
                }
            });

            // Barkod oluşturma
            generateBtn.addEventListener('click', function() {
                const code = barcodeInput.value.trim();
                if (code) {
                    try {
                        barcodeResult.style.display = 'block';
                        JsBarcode(barcodeSvg, code, {
                            format: "CODE128",
                            lineColor: "#000",
                            width: 2,
                            height: 50,
                            displayValue: true
                        });
                    } catch (e) {
                        alert("Geçersiz barkod değeri!");
                    }
                } else {
                    // Random barkod oluştur
                    let randomBarcode = '';
                    for (let i = 0; i < 12; i++) {
                        randomBarcode += Math.floor(Math.random() * 10);
                    }
                    barcodeInput.value = randomBarcode;
                    barcodeResult.style.display = 'block';
                    JsBarcode(barcodeSvg, randomBarcode, {
                        format: "CODE128",
                        lineColor: "#000",
                        width: 2,
                        height: 50,
                        displayValue: true
                    });
                }
            });

            // Stok hareketleri grafiği
            const stockCtx = document.getElementById('stockChart').getContext('2d');
            const stockChart = new Chart(stockCtx, {
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
                    }
                }
            });

            // Kategori dağılımı grafiği
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
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

            // İstatistiklerin animasyonu
            function animateCounter(elementId, targetValue) {
                const element = document.getElementById(elementId);
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