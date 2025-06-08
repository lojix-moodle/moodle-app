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

$PAGE->set_url('/index.php', $urlparams);
$PAGE->set_pagelayout('frontpage');
$PAGE->set_title('Depo Yönetimi Sistemi');
$PAGE->set_heading('Depo Yönetimi Sistemi');

// Harici kaynaklar
$PAGE->requires->css('/blocks/depo_yonetimi/styles/custom.css');
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'));

echo $OUTPUT->header();

// CSS stil tanımlamaları doğrudan HTML içerisine ekleniyor
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
        font-size: 2.2rem;
    }

    .site-hero p {
        font-size: 1.1rem;
    }

    .section-title {
        font-size: 2rem;
    }

    .counter-container h2 {
        font-size: 2.2rem;
    }

    .chart-container {
        height: 280px;
    }
}
</style>';

// Hero bölümü ekle
echo '<div class="site-hero">';
echo '<div class="container">';
echo '<h1>Depo Yönetimi Sistemi</h1>';
echo '<p>İşletmenizin stok yönetimini dijitalleştirin, verimliliği artırın</p>';
echo '<div class="mt-4">';
echo '<a href="' . $CFG->wwwroot . '/login/index.php" class="btn btn-light btn-lg">Giriş Yap</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// İstatistik sayaçları
echo '<div class="container stats-section" id="stats">';
echo '<h2 class="section-title">Rakamlarla Sistemimiz</h2>';
echo '<p class="section-subtitle">Depo yönetimi sistemimiz işletmelerin stok ve envanter yönetimini kolaylaştırıyor</p>';
echo '<div class="row text-center">';
echo '<div class="col-md-4 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-users fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="1250">1250</h2>';
echo '<p>Aktif Kullanıcı</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-building fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="85">85</h2>';
echo '<p>İşletme</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-chart-line fa-3x mb-3"></i>';
echo '<h2 class="counter" data-count="45">45</h2>';
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

// Grafik ve analiz bölümü
echo '<div class="container mt-5">';
echo '<h2 class="section-title">Veri Analizleri</h2>';
echo '<p class="section-subtitle">Grafiklerle verilerinizi görselleştirerek daha iyi kararlar alın</p>';
echo '<div class="row">';
echo '<div class="col-md-6 mb-4">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title text-center mb-4">Stok Hareketleri</h4>';
echo '<div class="chart-container">';
echo '<canvas id="stockMovementChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6 mb-4">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title text-center mb-4">Ürün Kategorileri</h4>';
echo '<div class="chart-container">';
echo '<canvas id="productCategoriesChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Bilgi bölümü
echo '<div class="info-section">';
echo '<div class="container">';
echo '<h2 class="section-title">Neden Depo Yönetimi Sistemi?</h2>';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<div class="mb-4">';
echo '<h4><i class="fa fa-check-circle text-success mr-2"></i> Zaman Tasarrufu</h4>';
echo '<p>Manuel işlemleri otomatikleştirerek personellerinizin zamanını daha verimli kullanın. Sistem, stok sayımları, raporlama ve sipariş işlemleri gibi zaman alan süreçleri minimuma indirir.</p>';
echo '</div>';
echo '<div class="mb-4">';
echo '<h4><i class="fa fa-check-circle text-success mr-2"></i> Maliyet Azaltma</h4>';
echo '<p>Stok fazlalıklarını önleyerek depolama maliyetlerinizi azaltın. Aynı zamanda stok eksikliklerini önceden tespit ederek üretim kesintilerini ve acil sipariş maliyetlerini engelleyin.</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<div class="mb-4">';
echo '<h4><i class="fa fa-check-circle text-success mr-2"></i> Doğru Veriler</h4>';
echo '<p>İnsan hatasını en aza indirerek stok verilerinizin doğruluğunu artırın. Gerçek zamanlı takip ile envanter durumunuza dair her zaman güncel bilgiye sahip olun.</p>';
echo '</div>';
echo '<div class="mb-4">';
echo '<h4><i class="fa fa-check-circle text-success mr-2"></i> Stratejik Kararlar</h4>';
echo '<p>Detaylı analiz ve raporlarla verilere dayalı stratejik kararlar alın. Hangi ürünlerin daha hızlı tükendiğini, hangi mevsimlerde hangi ürünlere talep olduğunu analiz edin.</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// CTA bölümü
echo '<div class="container">';
echo '<div class="cta-section">';
echo '<h2>Depo Yönetimini Dijitalleştirmeye Hazır Mısınız?</h2>';
echo '<p>Hemen giriş yapın veya daha fazla bilgi için bizimle iletişime geçin</p>';
echo '<a href="' . $CFG->wwwroot . '/login/index.php" class="btn btn-cta mr-3">Giriş Yap</a>';
echo '<a href="' . $CFG->wwwroot . '/contact.php" class="btn btn-outline-light">İletişime Geç</a>';
echo '</div>';
echo '</div>';

// Chart.js kütüphanesini ekle
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// Grafikleri oluşturan script
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Stok hareketleri grafiği
    const movementCtx = document.getElementById("stockMovementChart");
    if (movementCtx) {
        new Chart(movementCtx, {
            type: "line",
            data: {
                labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
                datasets: [{
                    label: "Giriş",
                    data: [65, 59, 80, 81, 56, 85],
                    borderColor: "#2e7d32",
                    backgroundColor: "rgba(46, 125, 50, 0.1)",
                    tension: 0.3
                }, {
                    label: "Çıkış",
                    data: [28, 48, 40, 19, 64, 27],
                    borderColor: "#f44336",
                    backgroundColor: "rgba(244, 67, 54, 0.1)",
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Ürün kategorileri grafiği
    const categoriesCtx = document.getElementById("productCategoriesChart");
    if (categoriesCtx) {
        new Chart(categoriesCtx, {
            type: "doughnut",
            data: {
                labels: ["Giyim", "Ayakkabı", "Aksesuar", "Çanta", "Elektronik"],
                datasets: [{
                    data: [35, 25, 15, 10, 15],
                    backgroundColor: [
                        "#2e7d32",
                        "#388e3c",
                        "#43a047",
                        "#4caf50",
                        "#66bb6a"
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>';

echo $OUTPUT->footer();
?>