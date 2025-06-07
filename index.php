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
 * Depo Yönetim Sistemi - Profesyonel Ana Sayfa
 *
 * @package    block_depo_yonetimi
 * @copyright  2023 Depo Yönetim Sistemi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $CFG, $USER;

$PAGE->set_url('/blocks/depo_yonetimi/view.php');
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'block_depo_yonetimi'));
$PAGE->set_heading(get_string('pluginname', 'block_depo_yonetimi'));

// Modern kütüphaneleri ekle
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/glider-js@1.7.8/glider.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'), true);
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js'), true);
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/glider-js@1.7.8/glider.min.js'), true);
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'), true);

echo $OUTPUT->header();

// Özel CSS Stilleri
echo '<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --dark-color: #1b263b;
    --light-color: #f8f9fa;
    --success-color: #4cc9f0;
    --warning-color: #f8961e;
    --danger-color: #f94144;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    --transition: all 0.3s ease;
}

/* Ana Layout */
.depo-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Hero Bölümü */
.depo-hero {
    background: linear-gradient(135deg, var(--dark-color), var(--primary-color));
    color: white;
    padding: 80px 20px;
    border-radius: var(--border-radius);
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.depo-hero::before {
    content: "";
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.depo-hero::after {
    content: "";
    position: absolute;
    bottom: -30px;
    left: -30px;
    width: 150px;
    height: 150px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.depo-hero h1 {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 20px;
    position: relative;
    z-index: 2;
}

.depo-hero p {
    font-size: 1.2rem;
    max-width: 700px;
    margin: 0 auto 30px;
    opacity: 0.9;
    position: relative;
    z-index: 2;
}

/* Özellik Kartları */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.feature-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    border: 1px solid rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.feature-card i {
    font-size: 2.5rem;
    color: var(--accent-color);
    margin-bottom: 20px;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.feature-card h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: var(--dark-color);
}

.feature-card p {
    color: #6c757d;
    margin-bottom: 20px;
}

.feature-card .btn {
    border-radius: 50px;
    padding: 8px 20px;
    font-weight: 600;
}

/* İstatistikler */
.stats-section {
    background: var(--light-color);
    border-radius: var(--border-radius);
    padding: 50px 20px;
    margin: 50px 0;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
}

.stat-item:hover {
    transform: translateY(-5px);
}

.stat-item i {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 10px 0;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.stat-label {
    color: #6c757d;
    font-size: 1rem;
}

/* Hızlı Erişim Butonları */
.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
    margin: 40px 0;
}

.quick-action {
    display: inline-flex;
    align-items: center;
    padding: 12px 25px;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    color: white;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
}

.quick-action i {
    margin-right: 10px;
}

.quick-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
    color: white;
}

/* Grafik Bölümü */
.chart-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 40px;
    margin: 50px 0;
    box-shadow: var(--box-shadow);
}

.chart-container {
    position: relative;
    height: 400px;
    width: 100%;
}

/* Carousel */
.glider-contain {
    margin: 40px 0;
}

.glider-slide {
    padding: 15px;
}

.testimonial-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--box-shadow);
    text-align: center;
}

.testimonial-card img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 20px;
    border: 3px solid var(--light-color);
}

.testimonial-card p {
    font-style: italic;
    color: #6c757d;
    margin-bottom: 20px;
}

.testimonial-card h5 {
    font-weight: 700;
    margin-bottom: 5px;
}

.testimonial-card .position {
    color: var(--accent-color);
    font-size: 0.9rem;
}

/* Responsive Düzen */
@media (max-width: 768px) {
    .depo-hero {
        padding: 60px 20px;
    }
    
    .depo-hero h1 {
        font-size: 2.2rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 576px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .quick-action {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
}
</style>';

// Hero Bölümü
echo '<div class="depo-container">';
echo '<div class="depo-hero" data-aos="fade-down">';
echo '<h1>Depo Yönetim Sisteminiz</h1>';
echo '<p>Profesyonel depo yönetimi çözümü ile stoklarınızı kolayca takip edin, raporlar oluşturun ve verimliliğinizi artırın</p>';
echo '<div class="quick-actions">';
echo '<a href="'.$CFG->wwwroot.'/blocks/depo_yonetimi/dashboard.php" class="quick-action"><i class="fas fa-tachometer-alt"></i> Kontrol Paneli</a>';
echo '<a href="'.$CFG->wwwroot.'/blocks/depo_yonetimi/products.php" class="quick-action"><i class="fas fa-boxes"></i> Ürünler</a>';
echo '<a href="'.$CFG->wwwroot.'/blocks/depo_yonetimi/reports.php" class="quick-action"><i class="fas fa-chart-pie"></i> Raporlar</a>';
echo '</div>';
echo '</div>';

// Özellikler Bölümü
echo '<h2 class="text-center mb-4" data-aos="fade-up">Sistem Özellikleri</h2>';
echo '<div class="feature-grid">';

$features = [
    [
        'icon' => 'fas fa-box-open',
        'title' => 'Ürün Yönetimi',
        'desc' => 'Tüm ürünlerinizi detaylı şekilde kaydedin, kategorilere ayırın ve kolayca yönetin',
        'link' => 'products.php',
        'btn' => 'Ürünleri Görüntüle'
    ],
    [
        'icon' => 'fas fa-warehouse',
        'title' => 'Depo Yönetimi',
        'desc' => 'Birden fazla deponuzu tek sistemde yönetin, raf ve bölüm bilgilerini kaydedin',
        'link' => 'warehouses.php',
        'btn' => 'Depoları Görüntüle'
    ],
    [
        'icon' => 'fas fa-exchange-alt',
        'title' => 'Stok Hareketleri',
        'desc' => 'Giriş ve çıkış işlemlerinizi kaydedin, stok hareket geçmişini takip edin',
        'link' => 'movements.php',
        'btn' => 'Hareketleri Görüntüle'
    ],
    [
        'icon' => 'fas fa-chart-line',
        'title' => 'Detaylı Raporlar',
        'desc' => 'Özelleştirilebilir raporlar oluşturun ve stok analizleri yapın',
        'link' => 'reports.php',
        'btn' => 'Raporları Görüntüle'
    ],
    [
        'icon' => 'fas fa-barcode',
        'title' => 'Barkod Sistemi',
        'desc' => 'Ürünleriniz için barkod oluşturun ve kolayca stok takibi yapın',
        'link' => 'barcodes.php',
        'btn' => 'Barkod Oluştur'
    ],
    [
        'icon' => 'fas fa-bell',
        'title' => 'Stok Uyarıları',
        'desc' => 'Kritik stok seviyeleri için otomatik uyarılar alın',
        'link' => 'alerts.php',
        'btn' => 'Uyarıları Görüntüle'
    ]
];

foreach ($features as $index => $feature) {
    echo '<div class="feature-card" data-aos="fade-up" data-aos-delay="'.($index*100).'">';
    echo '<i class="'.$feature['icon'].'"></i>';
    echo '<h3>'.$feature['title'].'</h3>';
    echo '<p>'.$feature['desc'].'</p>';
    echo '<a href="'.$CFG->wwwroot.'/blocks/depo_yonetimi/'.$feature['link'].'" class="btn btn-outline-primary">'.$feature['btn'].'</a>';
    echo '</div>';
}

echo '</div>';

// İstatistikler Bölümü
echo '<div class="stats-section" data-aos="fade-up">';
echo '<h2 class="text-center mb-5">Sistem İstatistikleri</h2>';
echo '<div class="stats-container">';

$stats = [
    ['icon' => 'fas fa-users', 'number' => $DB->count_records('user'), 'label' => 'Kullanıcı'],
    ['icon' => 'fas fa-warehouse', 'number' => $DB->count_records('block_depo_yonetimi_depolar'), 'label' => 'Depo'],
    ['icon' => 'fas fa-boxes', 'number' => $DB->count_records('block_depo_yonetimi_urunler'), 'label' => 'Ürün'],
    ['icon' => 'fas fa-exchange-alt', 'number' => $DB->count_records('block_depo_yonetimi_hareketler'), 'label' => 'Hareket']
];

foreach ($stats as $stat) {
    echo '<div class="stat-item">';
    echo '<i class="'.$stat['icon'].'"></i>';
    echo '<div class="stat-number">'.$stat['number'].'</div>';
    echo '<div class="stat-label">'.$stat['label'].'</div>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Grafik Bölümü
echo '<div class="chart-section" data-aos="fade-up">';
echo '<h2 class="text-center mb-4">Stok Hareket Analizi</h2>';
echo '<div class="chart-container">';
echo '<canvas id="stockChart"></canvas>';
echo '</div>';
echo '</div>';

// Referanslar Bölümü
echo '<h2 class="text-center my-5" data-aos="fade-up">Müşteri Görüşleri</h2>';
echo '<div class="glider-contain" data-aos="fade-up">';
echo '<div class="glider">';

$testimonials = [
    [
        'name' => 'Ahmet Yılmaz',
        'position' => 'Depo Müdürü - ABC Şirketi',
        'comment' => 'Bu sistem sayesinde depo operasyonlarımız %40 daha verimli hale geldi. Kullanımı çok kolay ve raporlama özellikleri mükemmel.',
        'image' => 'https://randomuser.me/api/portraits/men/32.jpg'
    ],
    [
        'name' => 'Ayşe Kaya',
        'position' => 'Lojistik Sorumlusu - XYZ A.Ş.',
        'comment' => 'Stok takibindeki karmaşadan kurtulduk. Artık gerçek zamanlı stok bilgisine anında ulaşabiliyoruz.',
        'image' => 'https://randomuser.me/api/portraits/women/44.jpg'
    ],
    [
        'name' => 'Mehmet Demir',
        'position' => 'Operasyon Müdürü - DEF Ltd.',
        'comment' => 'Birden fazla depomuzu tek sistemde yönetebilmek bizim için büyük kolaylık oldu. Ekip olarak çok memnunuz.',
        'image' => 'https://randomuser.me/api/portraits/men/75.jpg'
    ]
];

foreach ($testimonials as $testimonial) {
    echo '<div class="glider-slide">';
    echo '<div class="testimonial-card">';
    echo '<img src="'.$testimonial['image'].'" alt="'.$testimonial['name'].'">';
    echo '<p>"'.$testimonial['comment'].'"</p>';
    echo '<h5>'.$testimonial['name'].'</h5>';
    echo '<div class="position">'.$testimonial['position'].'</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '<button aria-label="Previous" class="glider-prev"><i class="fas fa-chevron-left"></i></button>';
echo '<button aria-label="Next" class="glider-next"><i class="fas fa-chevron-right"></i></button>';
echo '<div role="tablist" class="dots"></div>';
echo '</div>';

// Son Çağrı Bölümü
echo '<div class="text-center my-5" data-aos="fade-up">';
echo '<h2 class="mb-4">Depo Yönetim Sisteminizi Bugün Kullanmaya Başlayın</h2>';
echo '<p class="mb-4">Profesyonel çözümümüzle depo operasyonlarınızı optimize edin ve verimliliğinizi artırın</p>';
echo '<div class="quick-actions">';
echo '<a href="'.$CFG->wwwroot.'/blocks/depo_yonetimi/dashboard.php" class="quick-action"><i class="fas fa-rocket"></i> Hemen Başla</a>';
echo '<a href="'.$CFG->wwwroot.'/blocks/depo_yonetimi/demo.php" class="quick-action" style="background: var(--dark-color);"><i class="fas fa-play-circle"></i> Demo İzle</a>';
echo '</div>';
echo '</div>';

echo '</div>'; // Container kapatma

// JavaScript Kodları
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Animasyonları başlat
    AOS.init({
        duration: 800,
        easing: "ease-in-out",
        once: true
    });
    
    // Carousel başlatma
    new Glider(document.querySelector(".glider"), {
        slidesToShow: 1,
        slidesToScroll: 1,
        draggable: true,
        dots: ".dots",
        arrows: {
            prev: ".glider-prev",
            next: ".glider-next"
        },
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 1
                }
            }
        ]
    });
    
    // Grafik oluşturma
    const ctx = document.getElementById("stockChart").getContext("2d");
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
            datasets: [
                {
                    label: "Stok Girişi",
                    data: [65, 59, 80, 81, 56, 72],
                    backgroundColor: "rgba(67, 97, 238, 0.7)",
                    borderColor: "rgba(67, 97, 238, 1)",
                    borderWidth: 1
                },
                {
                    label: "Stok Çıkışı",
                    data: [28, 48, 40, 19, 86, 27],
                    backgroundColor: "rgba(72, 149, 239, 0.7)",
                    borderColor: "rgba(72, 149, 239, 1)",
                    borderWidth: 1
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
                    position: "top"
                },
                tooltip: {
                    mode: "index",
                    intersect: false
                }
            }
        }
    });
});
</script>';

echo $OUTPUT->footer();