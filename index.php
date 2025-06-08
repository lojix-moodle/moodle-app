<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Depo Yönetimi ana sayfa dosyası.
 *
 * @package    block_depo_yonetimi
 * @copyright  2023 Moodle Depo Yönetimi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

$redirect = optional_param('redirect', 1, PARAM_BOOL);
$urlparams = array();
if ($redirect === 0) {
    $urlparams['redirect'] = 0;
}

$PAGE->set_url('/blocks/depo_yonetimi/index.php', $urlparams);
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Depo Yönetimi Sistemi');
$PAGE->set_heading('Depo Yönetimi Sistemi');

// Harici kaynaklar
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css'));
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js'), true);
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js'), true);

echo $OUTPUT->header();

// CSS stil tanımlamaları
echo '<style>
/* Modern tanıtım sayfası tasarımı */
.card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    margin-bottom: 20px;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* Hero bölümü */
.site-hero {
    background: linear-gradient(135deg, #2e7d32, #1b5e20);
    color: white;
    padding: 100px 0;
    margin-bottom: 60px;
    position: relative;
    overflow: hidden;
    border-radius: 0 0 30px 30px;
    text-align: center;
}

.site-hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.site-hero p {
    font-size: 1.5rem;
    max-width: 800px;
    margin: 0 auto 30px;
    opacity: 0.9;
}

/* Animasyonlu içerik kartları */
.feature-card {
    padding: 40px 30px;
    text-align: center;
    border-radius: 15px;
    margin-bottom: 40px;
    background: white;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.feature-card i {
    font-size: 3.5rem;
    margin-bottom: 25px;
    color: #2e7d32;
}

.feature-card h3 {
    font-size: 1.4rem;
    margin-bottom: 15px;
    font-weight: 600;
}

/* İstatistik grafikleri */
.chart-container {
    position: relative;
    height: 350px;
    width: 100%;
    margin: 30px 0;
}

/* İnfo bölümü */
.info-section {
    background: #f8f9fa;
    border-radius: 20px;
    padding: 60px 0;
    margin: 60px 0;
}

.info-section h2 {
    margin-bottom: 40px;
    font-weight: 700;
}

/* CTA bölümü */
.cta-section {
    background: linear-gradient(135deg, #388e3c, #2e7d32);
    color: white;
    padding: 80px 0;
    margin: 60px 0;
    border-radius: 20px;
    text-align: center;
}

.cta-section h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 30px;
}

.cta-section p {
    font-size: 1.2rem;
    max-width: 800px;
    margin: 0 auto 30px;
    opacity: 0.9;
}

.btn-cta {
    background: white;
    color: #2e7d32;
    font-size: 1.2rem;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-cta:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Sayaç bileşenleri */
.stats-section {
    padding: 60px 0;
}

.counter-container {
    padding: 30px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    transition: transform 0.3s;
    text-align: center;
    height: 100%;
}

.counter-container:hover {
    transform: translateY(-5px);
}

.counter-container i {
    color: #2e7d32;
    font-size: 3rem;
    margin-bottom: 20px;
}

.counter-container h2 {
    font-size: 3rem;
    font-weight: 700;
    margin: 15px 0;
    color: #333;
}

.counter-container p {
    font-size: 1.2rem;
    color: #666;
}

/* Proses kartları */
.process-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    position: relative;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.process-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.process-number {
    position: absolute;
    top: -15px;
    left: -15px;
    width: 50px;
    height: 50px;
    background: #2e7d32;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
}

/* Avantajlar bölümü */
.advantages-section {
    background: #f2f7f2;
    padding: 60px 0;
    border-radius: 20px;
    margin: 60px 0;
}

.advantage-item {
    display: flex;
    margin-bottom: 30px;
}

.advantage-icon {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    background: rgba(46,125,50,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    color: #2e7d32;
    font-size: 1.5rem;
}

/* Demo bölümü */
.demo-section {
    background-color: #e8f5e9;
    border-radius: 20px;
    padding: 60px 0;
    margin-bottom: 60px;
}

.mockup-image {
    max-width: 100%;
    border-radius: 10px;
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

/* Testimonial bölümü */
.testimonial-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
}

.testimonial-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 20px;
}

.testimonial-rating {
    color: #ffb400;
    margin-bottom: 15px;
}

/* Genel düzenlemeler */
.section-title {
    text-align: center;
    margin-bottom: 50px;
    font-size: 2.5rem;
    font-weight: 700;
}

.section-subtitle {
    text-align: center;
    margin-bottom: 50px;
    font-size: 1.2rem;
    color: #666;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .site-hero {
        padding: 60px 0;
    }

    .site-hero h1 {
        font-size: 2.5rem;
    }

    .site-hero p {
        font-size: 1.2rem;
    }

    .section-title {
        font-size: 2rem;
    }

    .counter-container h2 {
        font-size: 2.5rem;
    }

    .chart-container {
        height: 250px;
    }
}
</style>';

// JavaScript kodları
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // AOS animasyon kütüphanesini başlat
    if (typeof AOS !== "undefined") {
        AOS.init({
            duration: 800,
            easing: "ease-in-out",
            once: true,
            mirror: false
        });
    }

    // Sayaç animasyonları
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute("data-count"));
        const duration = 2000;
        const startTime = Date.now();
        const step = () => {
            const currentTime = Date.now();
            const progress = Math.min((currentTime - startTime) / duration, 1);
            counter.textContent = Math.floor(progress * target);
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                counter.textContent = target;
            }
        };
        requestAnimationFrame(step);
    });

    // Stok hareketleri grafiği
    if (typeof Chart !== "undefined") {
        const stockCtx = document.getElementById("stockOverviewChart");
        if (stockCtx) {
            new Chart(stockCtx, {
                type: "doughnut",
                data: {
                    labels: ["Elektronik", "Mobilya", "Gıda", "Giyim", "Diğer"],
                    datasets: [{
                        data: [35, 20, 15, 20, 10],
                        backgroundColor: ["#4caf50", "#8bc34a", "#cddc39", "#ffeb3b", "#ddd"],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            fontColor: "#333"
                        }
                    },
                    cutoutPercentage: 70,
                    animation: {
                        animateScale: true
                    }
                }
            });
        }
        
        const efficiencyCtx = document.getElementById("efficiencyChart");
        if (efficiencyCtx) {
            new Chart(efficiencyCtx, {
                type: "line",
                data: {
                    labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
                    datasets: [{
                        label: "Verimlilik Artışı (%)",
                        data: [5, 15, 20, 30, 40, 45],
                        borderColor: "#2e7d32",
                        backgroundColor: "rgba(46,125,50,0.1)",
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: "#2e7d32",
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value + "%";
                                }
                            },
                            gridLines: {
                                color: "rgba(0,0,0,0.05)",
                                zeroLineColor: "rgba(0,0,0,0.1)"
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            });
        }
    }
});
</script>';

// Hero bölümü ekle
echo '<div class="site-hero" data-aos="fade-down">';
echo '<div class="container">';
echo '<h1 data-aos="fade-up" data-aos-delay="200">Depo Yönetimi Sistemi</h1>';
echo '<p data-aos="fade-up" data-aos-delay="400">Stok takibi, ürün yönetimi ve depo operasyonları için modern ve kullanıcı dostu bir platform.</p>';
echo '<div class="mt-4" data-aos="fade-up" data-aos-delay="600">';
echo '<a href="#features" class="btn btn-light btn-lg mx-2">Özellikler</a>';
echo '<a href="#demo" class="btn btn-outline-light btn-lg mx-2">Demo Görüntüle</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// İstatistik sayaçları
echo '<div class="container stats-section" id="stats">';
echo '<h2 class="section-title">Rakamlarla Sistemimiz</h2>';
echo '<p class="section-subtitle">Depo yönetimi sistemimiz işletmelerin stok ve envanter yönetimini kolaylaştırıyor</p>';
echo '<div class="row text-center">';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-box fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="1250">0</h2>';
echo '<p>Toplam Ürün</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-warehouse fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="8">0</h2>';
echo '<p>Depo Bölümü</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-users fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="85">0</h2>';
echo '<p>Aktif İşletme</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-chart-line fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="45">0</h2>';
echo '<p>Verimlilik Artışı (%)</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Özellikler bölümü
echo '<div class="container mt-5" id="features">';
echo '<h2 class="section-title">Sistem Özellikleri</h2>';
echo '<p class="section-subtitle">Modern ve kullanıcı dostu arayüzümüz ile depo yönetimini kolaylaştırıyoruz</p>';
echo '<div class="row">';
echo '<div class="col-lg-4 col-md-6">';
echo '<div class="feature-card">';
echo '<i class="fa fa-boxes"></i>';
echo '<h3>Stok Takibi</h3>';
echo '<p>Gelişmiş stok takip sistemi ile ürünlerinizin miktarını, yerini ve durumunu anlık olarak izleyin</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-lg-4 col-md-6">';
echo '<div class="feature-card">';
echo '<i class="fa fa-barcode"></i>';
echo '<h3>Barkod Sistemi</h3>';
echo '<p>Ürünleri hızlı tanımlamak için barkod sistemi ve otomatik etiket basım özellikleri</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-lg-4 col-md-6">';
echo '<div class="feature-card">';
echo '<i class="fa fa-chart-line"></i>';
echo '<h3>Raporlama</h3>';
echo '<p>Detaylı raporlar ve analizlerle stok hareketlerini ve ürün performansını takip edin</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="row mt-4">';
echo '<div class="col-lg-4 col-md-6">';
echo '<div class="feature-card">';
echo '<i class="fa fa-bell"></i>';
echo '<h3>Stok Alarmları</h3>';
echo '<p>Kritik stok seviyelerine ulaşan ürünler için otomatik bildirimler ve uyarılar</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-lg-4 col-md-6">';
echo '<div class="feature-card">';
echo '<i class="fa fa-mobile-alt"></i>';
echo '<h3>Mobil Erişim</h3>';
echo '<p>Mobil cihazlardan tam erişim ile her an her yerden deponuzu yönetin</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-lg-4 col-md-6">';
echo '<div class="feature-card">';
echo '<i class="fa fa-user-shield"></i>';
echo '<h3>Kullanıcı Yetkilendirme</h3>';
echo '<p>Farklı yetki seviyelerine sahip kullanıcılar oluşturarak güvenliği artırın</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Nasıl Çalışır Bölümü
echo '<div class="container mt-5">';
echo '<h2 class="section-title" data-aos="fade-up">Nasıl Çalışır?</h2>';
echo '<p class="section-subtitle" data-aos="fade-up">Depo yönetimi sistemimiz basit ve anlaşılır adımlarla işletmenize entegre olur</p>';
echo '<div class="row">';
echo '<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">';
echo '<div class="process-card">';
echo '<div class="process-number">1</div>';
echo '<h4>Ürün Tanımlama</h4>';
echo '<p>Depo envanterinizi tanımlayın ve her ürün için barkod, stok kodu, kategori ve diğer detayları girin.</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="process-card">';
echo '<div class="process-number">2</div>';
echo '<h4>Stok Yönetimi</h4>';
echo '<p>Ürün giriş çıkışlarını takip edin, minimum stok seviyelerini belirleyin ve otomatik uyarılar alın.</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">';
echo '<div class="process-card">';
echo '<div class="process-number">3</div>';
echo '<h4>Raporlama</h4>';
echo '<p>Detaylı analiz ve raporlarla stok performansınızı ölçün, darboğazları tespit edin ve stratejik kararlar alın.</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Grafik ve analiz bölümü
echo '<div class="container mt-5">';
echo '<h2 class="section-title">Veri Analizleri</h2>';
echo '<p class="section-subtitle">Grafiklerle verilerinizi görselleştirerek daha iyi kararlar alın</p>';
echo '<div class="row">';
echo '<div class="col-md-6 mb-4" data-aos="fade-right">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Stok Dağılımı</h4>';
echo '<div class="chart-container">';
echo '<canvas id="stockOverviewChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6 mb-4" data-aos="fade-left">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Verimlilik Artışı</h4>';
echo '<div class="chart-container">';
echo '<canvas id="efficiencyChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Avantajlar bölümü
echo '<div class="advantages-section" data-aos="fade-up">';
echo '<div class="container">';
echo '<h2 class="section-title">Neden Depo Yönetimi Sistemimizi Seçmelisiniz?</h2>';
echo '<div class="row mt-5">';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="100">';
echo '<div class="advantage-item">';
echo '<div class="advantage-icon"><i class="fas fa-rocket"></i></div>';
echo '<div>';
echo '<h4>Hızlı ve Kolay Kurulum</h4>';
echo '<p>Minimum teknik bilgi ile sistemi hızlıca kurun ve kullanmaya başlayın. Kurulum sürecinde size destek olacak ekibimiz hazır.</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="100">';
echo '<div class="advantage-item">';
echo '<div class="advantage-icon"><i class="fas fa-sync"></i></div>';
echo '<div>';
echo '<h4>Gerçek Zamanlı Güncelleme</h4>';
echo '<p>Tüm stok hareketleri gerçek zamanlı olarak güncellenir. Böylece her zaman en güncel verilere sahip olursunuz.</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="advantage-item">';
echo '<div class="advantage-icon"><i class="fas fa-mobile-alt"></i></div>';
echo '<div>';
echo '<h4>Tam Mobil Uyumluluk</h4>';
echo '<p>Mobil cihazlarla uyumlu tasarım sayesinde sahada, depo içinde veya dışarıda her yerden sisteme erişin.</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="advantage-item">';
echo '<div class="advantage-icon"><i class="fas fa-chart-pie"></i></div>';
echo '<div>';
echo '<h4>Detaylı Analizler</h4>';
echo '<p>Gelişmiş analiz araçlarıyla stoklarınızı daha iyi yönetin, demirbaş takibini kolaylaştırın ve trend analizi yapın.</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="300">';
echo '<div class="advantage-item">';
echo '<div class="advantage-icon"><i class="fas fa-shield-alt"></i></div>';
echo '<div>';
echo '<h4>Güvenli Erişim</h4>';
echo '<p>Rol tabanlı yetkilendirme sistemi sayesinde herkes sadece yetkili olduğu özelliklere erişebilir.</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="300">';
echo '<div class="advantage-item">';
echo '<div class="advantage-icon"><i class="fas fa-headset"></i></div>';
echo '<div>';
echo '<h4>7/24 Teknik Destek</h4>';
echo '<p>Uzman teknik destek ekibimiz sorunlarınızı çözmek için her zaman hazır. Telefonla, e-posta ile veya canlı destek hattı ile ulaşabilirsiniz.</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';

// Demo bölümü
echo '<div class="demo-section mt-5" id="demo" data-aos="fade-up">';
echo '<div class="container">';
echo '<h2 class="section-title">Demo Görüntüleri</h2>';
echo '<p class="section-subtitle">Sistemimizin kullanıcı dostu arayüzünü ve güçlü özelliklerini keşfedin</p>';

echo '<div class="row mt-4">';
echo '<div class="col-md-6 mb-4" data-aos="fade-right" data-aos-delay="100">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/dashboard-demo.jpg" alt="Dashboard Demo" class="mockup-image">';
echo '<h4 class="mt-3 text-center">Dashboard Görünümü</h4>';
echo '</div>';

echo '<div class="col-md-6 mb-4" data-aos="fade-left" data-aos-delay="100">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/inventory-demo.jpg" alt="Inventory Demo" class="mockup-image">';
echo '<h4 class="mt-3 text-center">Stok Yönetimi Ekranı</h4>';
echo '</div>';
echo '</div>';

echo '<div class="row mt-4">';
echo '<div class="col-md-6 mb-4" data-aos="fade-right" data-aos-delay="200">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/reports-demo.jpg" alt="Reports Demo" class="mockup-image">';
echo '<h4 class="mt-3 text-center">Raporlama ve Analiz</h4>';
echo '</div>';

echo '<div class="col-md-6 mb-4" data-aos="fade-left" data-aos-delay="200">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/mobile-demo.jpg" alt="Mobile Demo" class="mockup-image">';
echo '<h4 class="mt-3 text-center">Mobil Arayüz</h4>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

// Referanslar bölümü
echo '<div class="container mt-5">';
echo '<h2 class="section-title" data-aos="fade-up">Müşteri Görüşleri</h2>';
echo '<p class="section-subtitle" data-aos="fade-up">Sistemimizi kullanan müşterilerimizin yorumları</p>';

echo '<div class="row">';
echo '<div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">';
echo '<div class="testimonial-card">';
echo '<div class="testimonial-avatar">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/avatar-1.jpg" alt="Avatar 1" class="img-fluid">';
echo '</div>';
echo '<div class="testimonial-rating">';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '</div>';
echo '<p>"Depo Yönetimi Sistemi ile stok takibi artık çok daha kolay. Stok hareketlerimizi anlık izleyebiliyor ve raporlayabiliyoruz. Kesinlikle tavsiye ederim."</p>';
echo '<h5>Ahmet Yılmaz</h5>';
echo '<span>Lojistik Müdürü, ABC Elektronik</span>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="testimonial-card">';
echo '<div class="testimonial-avatar">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/avatar-2.jpg" alt="Avatar 2" class="img-fluid">';
echo '</div>';
echo '<div class="testimonial-rating">';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star-half-alt"></i>';
echo '</div>';
echo '<p>"Sistemi kullanmaya başladıktan sonra stok kayıplarımız %30 azaldı. Kullanıcı dostu arayüzü sayesinde personelimiz hızlıca adapte oldu."</p>';
echo '<h5>Ayşe Kaya</h5>';
echo '<span>Genel Müdür, XYZ Market Zinciri</span>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">';
echo '<div class="testimonial-card">';
echo '<div class="testimonial-avatar">';
echo '<img src="' . $CFG->wwwroot . '/blocks/depo_yonetimi/assets/images/avatar-3.jpg" alt="Avatar 3" class="img-fluid">';
echo '</div>';
echo '<div class="testimonial-rating">';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '<i class="fas fa-star"></i>';
echo '</div>';
echo '<p>"Raporlama özellikleri gerçekten etkileyici. Artık stok yönetimi ile ilgili stratejik kararlar almak çok daha kolay. Teknik destek ekibi de her zaman yardımcı oluyor."</p>';
echo '<h5>Mehmet Demir</h5>';
echo '<span>Depo Sorumlusu, İnşaat A.Ş.</span>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// CTA bölümü
echo '<div class="container mt-5">';
echo '<div class="cta-section" data-aos="fade-up">';
echo '<h2>Depo Yönetimini Dijitalleştirmeye Hazır Mısınız?</h2>';
echo '<p>Hemen giriş yapın veya daha fazla bilgi için bizimle iletişime geçin</p>';
echo '<a href="' . $CFG->wwwroot . '/login/index.php" class="btn btn-cta mr-3">Giriş Yap</a>';
echo '<a href="' . $CFG->wwwroot . '/contact.php" class="btn btn-outline-light">İletişime Geç</a>';
echo '</div>';
echo '</div>';

// SSS Bölümü
echo '<div class="container mt-5">';
echo '<h2 class="section-title" data-aos="fade-up">Sıkça Sorulan Sorular</h2>';
echo '<div class="accordion" id="faqAccordion" data-aos="fade-up" data-aos-delay="200">';

echo '<div class="card mb-3">';
echo '<div class="card-header" id="faqHeading1">';
echo '<h5 class="mb-0">';
echo '<button class="btn btn-link text-dark" data-toggle="collapse" data-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">';
echo 'Sistemi nasıl kurabilirim?';
echo '</button>';
echo '</h5>';
echo '</div>';
echo '<div id="faqCollapse1" class="collapse show" aria-labelledby="faqHeading1" data-parent="#faqAccordion">';
echo '<div class="card-body">';
echo 'Sistemimiz bulut tabanlı olduğu için kurulum gerektirmez. Abonelik planınızı seçtikten sonra size özel hesap oluşturulur ve hemen kullanmaya başlayabilirsiniz. Yerinde kurulum isteyen müşterilerimiz için teknik ekibimiz destek sağlamaktadır.';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="card mb-3">';
echo '<div class="card-header" id="faqHeading2">';
echo '<h5 class="mb-0">';
echo '<button class="btn btn-link text-dark collapsed" data-toggle="collapse" data-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">';
echo 'Mevcut sistemimle entegre edebilir miyim?';
echo '</button>';
echo '</h5>';
echo '</div>';
echo '<div id="faqCollapse2" class="collapse" aria-labelledby="faqHeading2" data-parent="#faqAccordion">';
echo '<div class="card-body">';
echo 'Evet, sistemimiz API desteği sayesinde muhasebe yazılımları, e-ticaret platformları ve ERP sistemleri ile entegre çalışabilir. Entegrasyon için teknik ekibimiz size özel çözümler sunmaktadır.';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="card mb-3">';
echo '<div class="card-header" id="faqHeading3">';
echo '<h5 class="mb-0">';
echo '<button class="btn btn-link text-dark collapsed" data-toggle="collapse" data-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">';
echo 'Personelim sistemi kullanmayı nasıl öğrenecek?';
echo '</button>';
echo '</h5>';
echo '</div>';
echo '<div id="faqCollapse3" class="collapse" aria-labelledby="faqHeading3" data-parent="#faqAccordion">';
echo '<div class="card-body">';
echo 'Kullanıcı dostu arayüzümüz sayesinde personel eğitimi oldukça kolaydır. Ayrıca tüm müşterilerimize ücretsiz eğitim ve kapsamlı dokümantasyon sağlıyoruz. İsteğe bağlı olarak yerinde eğitim hizmeti de verilmektedir.';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';

// Footer bilgileri
echo '<div class="container mt-5 mb-5">';
echo '<hr>';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<p>&copy; ' . date("Y") . ' Depo Yönetimi Sistemi. Tüm hakları saklıdır.</p>';
echo '</div>';
echo '<div class="col-md-6 text-right">';
echo '<p>Versiyon 1.0.5 | <a href="' . $CFG->wwwroot . '/privacy.php">Gizlilik Politikası</a> | <a href="' . $CFG->wwwroot . '/terms.php">Kullanım Koşulları</a></p>';
echo '</div>';
echo '</div>';
echo '</div>';

// AOS ve diğer JavaScript kütüphaneleri
echo '<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>';
echo '<script>
// AOS başlat
document.addEventListener("DOMContentLoaded", function() {
    AOS.init({
        duration: 800,
        easing: "ease-in-out",
        once: true
    });
    
    // Grafikleri oluştur
    if (typeof Chart !== "undefined") {
        // Stok Dağılımı Grafiği
        var stockCtx = document.getElementById("stockOverviewChart");
        if (stockCtx) {
            var stockChart = new Chart(stockCtx, {
                type: "pie",
                data: {
                    labels: ["Giyim", "Elektronik", "Gıda", "Mobilya", "Kozmetik"],
                    datasets: [{
                        data: [35, 25, 20, 15, 5],
                        backgroundColor: ["#4CAF50", "#8BC34A", "#FFC107", "#FF9800", "#F44336"],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: "right",
                        labels: {
                            fontSize: 14,
                            padding: 20
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var total = dataset.data.reduce(function(previousValue, currentValue) {
                                    return previousValue + currentValue;
                                });
                                var currentValue = dataset.data[tooltipItem.index];
                                var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                                return data.labels[tooltipItem.index] + ": " + percentage + "%";
                            }
                        }
                    }
                }
            });
        }
        
        // Verimlilik Artışı Grafiği
        var efficiencyCtx = document.getElementById("efficiencyChart");
        if (efficiencyCtx) {
            var efficiencyChart = new Chart(efficiencyCtx, {
                type: "line",
                data: {
                    labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
                    datasets: [{
                        label: "Verimlilik Artışı (%)",
                        data: [10, 15, 25, 35, 40, 45],
                        backgroundColor: "rgba(76, 175, 80, 0.1)",
                        borderColor: "#4CAF50",
                        borderWidth: 3,
                        pointBackgroundColor: "#4CAF50",
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {return value + "%"}
                            },
                            gridLines: {
                                drawBorder: false
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    },
                    legend: {
                        display: false
                    }
                }
            });
        }
    }
    
    // Sayaç animasyonları
    const counters = document.querySelectorAll(".counter");
    const speed = 200;
    
    counters.forEach(counter => {
        const animate = () => {
            const value = +counter.getAttribute("data-count");
            const data = +counter.innerText;
            
            const time = value / speed;
            
            if (data < value) {
                counter.innerText = Math.ceil(data + time);
                setTimeout(animate, 1);
            } else {
                counter.innerText = value;
            }
        }
        
        animate();
    });
});
</script>';

echo $OUTPUT->footer();
?>