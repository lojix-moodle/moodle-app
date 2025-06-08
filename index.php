<?php
// Önceki PHP kodları aynı kalacak, sadece tasarım kısmını değiştiriyoruz

echo $OUTPUT->header();

// Modern ve profesyonel CSS
echo '<style>
/* Temel stil ayarları */
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #4cc9f0;
    --danger-color: #f72585;
    --warning-color: #f8961e;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --gray-color: #6c757d;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

body.path-site {
    font-family: "Poppins", sans-serif;
    color: var(--dark-color);
    background-color: #f5f7fa;
    line-height: 1.6;
}

/* Container ayarları */
.container {
    max-width: 1200px;
    padding-left: 20px;
    padding-right: 20px;
}

/* Kart stilleri */
.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    overflow: hidden;
    background: white;
    margin-bottom: 25px;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.card-header {
    background-color: var(--primary-color);
    color: white;
    font-weight: 600;
    padding: 15px 20px;
    border-bottom: none;
}

.card-body {
    padding: 25px;
}

/* Buton stilleri */
.btn {
    border-radius: 50px;
    padding: 10px 25px;
    font-weight: 500;
    transition: var(--transition);
    border: none;
}

.btn-primary {
    background-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
}

.btn-outline-primary {
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    background: transparent;
}

.btn-outline-primary:hover {
    background-color: var(--primary-color);
    color: white;
}

/* Hero bölümü */
.hero-section {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 100px 0;
    position: relative;
    overflow: hidden;
    margin-bottom: 50px;
    border-radius: 0 0 30px 30px;
}

.hero-section::before {
    content: "";
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.hero-section::after {
    content: "";
    position: absolute;
    bottom: -150px;
    left: -150px;
    width: 500px;
    height: 500px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    margin-bottom: 30px;
    max-width: 700px;
}

/* Özellikler bölümü */
.features-section {
    padding: 80px 0;
}

.section-title {
    text-align: center;
    margin-bottom: 60px;
    position: relative;
}

.section-title h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 15px;
}

.section-title p {
    color: var(--gray-color);
    max-width: 700px;
    margin: 0 auto;
}

.feature-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    text-align: center;
    height: 100%;
    transition: var(--transition);
}

.feature-card i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 20px;
    background: rgba(67, 97, 238, 0.1);
    width: 80px;
    height: 80px;
    line-height: 80px;
    border-radius: 50%;
    display: inline-block;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.feature-card p {
    color: var(--gray-color);
}

/* İstatistikler */
.stats-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
}

.stat-card {
    text-align: center;
    padding: 30px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.stat-card i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.stat-card .count {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin: 10px 0;
}

.stat-card p {
    color: var(--gray-color);
    margin: 0;
}

/* Grafik bölümü */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Kritik stok uyarısı */
.alert-critical {
    background-color: #fff5f5;
    border-left: 4px solid var(--danger-color);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
}

.alert-critical h3 {
    color: var(--danger-color);
    margin-top: 0;
}

/* Ürün listesi */
.product-item {
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
    margin-bottom: 20px;
}

.product-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.product-image {
    height: 180px;
    background-size: cover;
    background-position: center;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image i {
    font-size: 3rem;
    color: var(--primary-color);
    opacity: 0.7;
}

.product-content {
    padding: 20px;
    background: white;
}

.product-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    color: var(--gray-color);
    font-size: 0.9rem;
}

/* Tablo stilleri */
.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table thead th {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 15px;
}

.table tbody tr {
    transition: var(--transition);
}

.table tbody tr:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

.table tbody td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

/* Hızlı aksiyon butonları */
.action-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin: 40px 0;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    padding: 15px 25px;
    border-radius: 50px;
    font-weight: 500;
    transition: var(--transition);
    text-decoration: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.action-btn i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.action-btn.primary {
    background-color: var(--primary-color);
    color: white;
}

.action-btn.primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3);
}

.action-btn.secondary {
    background-color: white;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.action-btn.secondary:hover {
    background-color: var(--primary-color);
    color: white;
}

/* Responsive ayarlar */
@media (max-width: 992px) {
    .hero-title {
        font-size: 2.8rem;
    }
    
    .section-title h2 {
        font-size: 2rem;
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 80px 0;
    }
    
    .hero-title {
        font-size: 2.2rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .section-title h2 {
        font-size: 1.8rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}

/* Animasyonlar */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate {
    animation: fadeInUp 0.8s ease forwards;
}

.delay-1 {
    animation-delay: 0.2s;
}

.delay-2 {
    animation-delay: 0.4s;
}

.delay-3 {
    animation-delay: 0.6s;
}
</style>';

// JavaScript kodları
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Sayaç animasyonları
    const counters = document.querySelectorAll(".counter");
    const speed = 200;
    
    counters.forEach(counter => {
        const target = +counter.getAttribute("data-count");
        const count = +counter.innerText;
        const increment = target / speed;
        
        if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(updateCounter, 1, counter, target, increment);
        } else {
            counter.innerText = target;
        }
    });
    
    function updateCounter(counter, target, increment) {
        const count = +counter.innerText;
        if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(updateCounter, 1, counter, target, increment);
        } else {
            counter.innerText = target;
        }
    }
    
    // Grafikler
    const ctxMovement = document.getElementById("stockMovementChart");
    if (ctxMovement) {
        new Chart(ctxMovement, {
            type: "line",
            data: {
                labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz"],
                datasets: [
                    {
                        label: "Stok Girişi",
                        data: [120, 190, 150, 210, 180, 250, 220],
                        borderColor: "#4cc9f0",
                        backgroundColor: "rgba(76, 201, 240, 0.1)",
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    },
                    {
                        label: "Stok Çıkışı",
                        data: [90, 110, 130, 140, 160, 200, 180],
                        borderColor: "#f72585",
                        backgroundColor: "rgba(247, 37, 133, 0.1)",
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "top",
                    },
                    tooltip: {
                        mode: "index",
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    const ctxCategories = document.getElementById("productCategoriesChart");
    if (ctxCategories) {
        new Chart(ctxCategories, {
            type: "doughnut",
            data: {
                labels: ["Giyim", "Ayakkabı", "Aksesuar", "Çanta", "Diğer"],
                datasets: [
                    {
                        data: [45, 25, 15, 10, 5],
                        backgroundColor: [
                            "#4361ee",
                            "#3f37c9",
                            "#4895ef",
                            "#4cc9f0",
                            "#560bad"
                        ],
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "right",
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || "";
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (%${percentage})`;
                            }
                        }
                    }
                },
                cutout: "70%"
            }
        });
    }
    
    // Gözlemci animasyonları
    const animateElements = document.querySelectorAll(".animate");
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = 1;
                entry.target.style.transform = "translateY(0)";
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    animateElements.forEach(element => {
        observer.observe(element);
    });
    
    // Barkod tarayıcı
    const barcodeInput = document.getElementById("barcodeInput");
    if (barcodeInput) {
        barcodeInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                const barcodeValue = this.value.trim();
                if (barcodeValue) {
                    window.location.href = "' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/barkod_ara.php?code=" + barcodeValue;
                }
            }
        });
    }
});
</script>';

// Hero Bölümü
echo '<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title animate">Depo Yönetimi Sistemi</h1>
            <p class="hero-subtitle animate delay-1">Stoklarınızı profesyonel şekilde yönetin, süreçlerinizi optimize edin ve verimliliğinizi artırın</p>
            <div class="animate delay-2">
                <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php" class="btn btn-light btn-lg mr-3">Ürünleri Görüntüle</a>
                <a href="#features" class="btn btn-outline-light btn-lg">Özellikleri Keşfet</a>
            </div>
        </div>
    </div>
</section>';

// Hızlı Arama Bölümü
echo '<div class="container">
    <div class="card animate delay-1">
        <div class="card-body text-center py-4">
            <div class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <i class="fas fa-barcode fa-3x text-primary"></i>
                    <h4 class="mt-2">Hızlı Ürün Arama</h4>
                </div>
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" id="barcodeInput" class="form-control form-control-lg" placeholder="Barkod numarasını girin veya tarayıcı ile okutun...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" onclick="window.location.href=\'' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/barkod_ara.php?code=\' + document.getElementById(\'barcodeInput\').value">
                                <i class="fas fa-search"></i> Ara
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// İstatistikler Bölümü
echo '<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 animate">
                <div class="stat-card">
                    <i class="fas fa-boxes"></i>
                    <div class="count counter" data-count="1250">0</div>
                    <p>Toplam Ürün</p>
                </div>
            </div>
            <div class="col-md-4 animate delay-1">
                <div class="stat-card">
                    <i class="fas fa-warehouse"></i>
                    <div class="count counter" data-count="8">0</div>
                    <p>Depo Bölümü</p>
                </div>
            </div>
            <div class="col-md-4 animate delay-2">
                <div class="stat-card">
                    <i class="fas fa-exchange-alt"></i>
                    <div class="count counter" data-count="3540">0</div>
                    <p>Aylık İşlem</p>
                </div>
            </div>
        </div>
    </div>
</section>';

// Kritik Stok Uyarısı
echo '<div class="container">
    <div class="alert-critical animate">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><i class="fas fa-exclamation-triangle mr-2"></i> Kritik Stok Uyarısı</h3>
                <p class="mb-0">5 ürünün stok seviyesi kritik eşiğin altında. Lütfen kontrol edin ve gerekli sipariş işlemlerini başlatın.</p>
            </div>
            <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?filter=critical" class="btn btn-danger">Kritik Ürünleri Görüntüle</a>
        </div>
    </div>
</div>';

// Özellikler Bölümü
echo '<section class="features-section" id="features">
    <div class="container">
        <div class="section-title animate">
            <h2>Sistem Özellikleri</h2>
            <p>Depo yönetimi sistemimizin güçlü özellikleri ile iş süreçlerinizi kolaylaştırın</p>
        </div>
        <div class="row">
            <div class="col-md-4 animate">
                <div class="feature-card">
                    <i class="fas fa-boxes"></i>
                    <h3>Stok Takibi</h3>
                    <p>Gerçek zamanlı stok takibi ile ürünlerinizin durumunu anlık olarak izleyin</p>
                </div>
            </div>
            <div class="col-md-4 animate delay-1">
                <div class="feature-card">
                    <i class="fas fa-barcode"></i>
                    <h3>Barkod Sistemi</h3>
                    <p>Barkod okuyucu desteği ile hızlı ve hatasız stok giriş/çıkış işlemleri</p>
                </div>
            </div>
            <div class="col-md-4 animate delay-2">
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Raporlama</h3>
                    <p>Detaylı raporlar ve analizler ile karar alma süreçlerinizi destekleyin</p>
                </div>
            </div>
        </div>
    </div>
</section>';

// Grafikler Bölümü
echo '<div class="container">
    <div class="row">
        <div class="col-md-6 animate">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Stok Hareketleri</h4>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="stockMovementChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 animate delay-1">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Ürün Kategorileri</h4>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="productCategoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// Son Eklenenler ve Hareketler
echo '<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 animate">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Son Eklenen Ürünler</h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">';

// Son eklenen ürünler listesi
$lastProducts = array(
    array('id' => 112, 'name' => 'Beyaz Gömlek L Beden', 'date' => '22.06.2023', 'category' => 'Gömlek'),
    array('id' => 111, 'name' => 'Spor Çanta', 'date' => '20.06.2023', 'category' => 'Çanta'),
    array('id' => 110, 'name' => 'Kadın Bot 38 Numara', 'date' => '19.06.2023', 'category' => 'Ayakkabı'),
    array('id' => 109, 'name' => 'Deri Cüzdan', 'date' => '18.06.2023', 'category' => 'Aksesuar'),
    array('id' => 108, 'name' => 'Slim Fit Jean 32 Beden', 'date' => '17.06.2023', 'category' => 'Pantolon')
);

foreach ($lastProducts as $product) {
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_detay.php?id=' . $product['id'] . '" class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">' . $product['name'] . '</h5>
                <small class="text-muted">' . $product['date'] . '</small>
            </div>
            <small class="text-muted">' . $product['category'] . '</small>
        </a>';
}

echo '</div>
                    <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?sort=newest" class="btn btn-outline-primary mt-3">Tümünü Görüntüle</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 animate delay-1">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Son Stok Hareketleri</h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">';

// Son stok hareketleri
$lastMovements = array(
    array('id' => 245, 'product' => 'Siyah Tişört L Beden', 'type' => 'Giriş', 'quantity' => '+10', 'date' => '23.06.2023', 'class' => 'success'),
    array('id' => 244, 'product' => 'Kot Pantolon 34 Beden', 'type' => 'Çıkış', 'quantity' => '-3', 'date' => '22.06.2023', 'class' => 'danger'),
    array('id' => 243, 'product' => 'Deri Kemer', 'type' => 'Giriş', 'quantity' => '+15', 'date' => '21.06.2023', 'class' => 'success'),
    array('id' => 242, 'product' => 'Spor Ayakkabı 42 Numara', 'type' => 'Çıkış', 'quantity' => '-2', 'date' => '20.06.2023', 'class' => 'danger'),
    array('id' => 241, 'product' => 'Beyaz Gömlek L Beden', 'type' => 'Giriş', 'quantity' => '+20', 'date' => '19.06.2023', 'class' => 'success')
);

foreach ($lastMovements as $movement) {
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/hareket_detay.php?id=' . $movement['id'] . '" class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">' . $movement['product'] . '</h5>
                <span class="badge badge-' . $movement['class'] . '">' . $movement['quantity'] . '</span>
            </div>
            <small>' . $movement['type'] . ' - ' . $movement['date'] . '</small>
        </a>';
}

echo '</div>
                    <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/movements.php" class="btn btn-outline-primary mt-3">Tümünü Görüntüle</a>
                </div>
            </div>
        </div>
    </div>
</div>';

// Hızlı Aksiyon Butonları
echo '<div class="container text-center my-5">
    <h2 class="mb-4 animate">Hızlı İşlemler</h2>
    <div class="action-buttons">
        <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_ekle.php" class="action-btn primary animate">
            <i class="fas fa-box-open"></i> Yeni Ürün Ekle
        </a>
        <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_giris.php" class="action-btn primary animate delay-1">
            <i class="fas fa-arrow-down"></i> Stok Girişi Yap
        </a>
        <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_cikis.php" class="action-btn primary animate delay-2">
            <i class="fas fa-arrow-up"></i> Stok Çıkışı Yap
        </a>
        <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/reports.php" class="action-btn secondary animate">
            <i class="fas fa-chart-pie"></i> Raporları Görüntüle
        </a>
    </div>
</div>';

// Kritik Stoklu Ürünler
echo '<div class="container my-5">
    <div class="card animate">
        <div class="card-header">
            <h4 class="mb-0">Kritik Stoklu Ürünler</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ürün Adı</th>
                            <th>Kategori</th>
                            <th>Mevcut Stok</th>
                            <th>Min. Stok</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>';

// Kritik stoklu ürünler
$criticalProducts = array(
    array('id' => 105, 'name' => 'Beyaz Tişört M Beden', 'stock' => 3, 'min' => 10, 'category' => 'Tişört'),
    array('id' => 87, 'name' => 'Siyah Spor Ayakkabı 40 Numara', 'stock' => 2, 'min' => 5, 'category' => 'Ayakkabı'),
    array('id' => 94, 'name' => 'Kot Pantolon 30 Beden', 'stock' => 4, 'min' => 8, 'category' => 'Pantolon'),
    array('id' => 76, 'name' => 'Deri Cüzdan', 'stock' => 1, 'min' => 5, 'category' => 'Aksesuar'),
    array('id' => 112, 'name' => 'Spor Çanta', 'stock' => 2, 'min' => 6, 'category' => 'Çanta')
);

foreach ($criticalProducts as $product) {
    echo '<tr>
            <td>' . $product['name'] . '</td>
            <td>' . $product['category'] . '</td>
            <td><span class="badge badge-danger">' . $product['stock'] . '</span></td>
            <td>' . $product['min'] . '</td>
            <td>
                <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_detay.php?id=' . $product['id'] . '" class="btn btn-sm btn-info mr-2">Detay</a>
                <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_giris.php?id=' . $product['id'] . '" class="btn btn-sm btn-primary">Stok Ekle</a>
            </td>
        </tr>';
}

echo '</tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?filter=critical" class="btn btn-warning">Tüm Kritik Ürünleri Görüntüle</a>
            </div>
        </div>
    </div>
</div>';

// Yaklaşan İşlemler
echo '<div class="container my-5">
    <div class="card animate">
        <div class="card-header">
            <h4 class="mb-0">Yaklaşan İşlemler</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>İşlem</th>
                            <th>Ürün</th>
                            <th>Miktar</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>';

// Yaklaşan işlemler
$upcomingTasks = array(
    array('id' => 1, 'type' => 'Giriş', 'product' => 'Erkek Tişört Paketi', 'quantity' => 50, 'date' => '25.06.2023', 'status' => 'Bekliyor'),
    array('id' => 2, 'type' => 'Çıkış', 'product' => 'Kadın Ayakkabı Siparişi', 'quantity' => 15, 'date' => '26.06.2023', 'status' => 'Hazırlanıyor'),
    array('id' => 3, 'type' => 'Giriş', 'product' => 'Aksesuar Koleksiyonu', 'quantity' => 30, 'date' => '28.06.2023', 'status' => 'Bekliyor'),
    array('id' => 4, 'type' => 'Çıkış', 'product' => 'Mağaza Sevkiyatı', 'quantity' => 25, 'date' => '29.06.2023', 'status' => 'Planlandı'),
    array('id' => 5, 'type' => 'Giriş', 'product' => 'Yaz Koleksiyonu', 'quantity' => 120, 'date' => '30.06.2023', 'status' => 'Bekliyor')
);

foreach ($upcomingTasks as $task) {
    $statusClass = $task['status'] === 'Bekliyor' ? 'warning' :
        ($task['status'] === 'Hazırlanıyor' ? 'info' : 'primary');
    $typeClass = $task['type'] === 'Giriş' ? 'success' : 'danger';

    echo '<tr>
            <td><span class="badge badge-' . $typeClass . '">' . $task['type'] . '</span></td>
            <td>' . $task['product'] . '</td>
            <td>' . $task['quantity'] . '</td>
            <td>' . $task['date'] . '</td>
            <td><span class="badge badge-' . $statusClass . '">' . $task['status'] . '</span></td>
        </tr>';
}

echo '</tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

echo $OUTPUT->footer();
?>