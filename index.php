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
 * Depo Yönetim Sistemi ana sayfası.
 *
 * @package    block_depo_yonetimi
 * @copyright  2023 onwards Depo Yönetim Sistemi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!file_exists('./config.php')) {
    header('Location: install.php');
    die;
}
global $DB, $PAGE, $OUTPUT , $CFG, $SITE , $USER, $SESSION, $COURSE, $CFG, $THEME;

require_once('config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

redirect_if_major_upgrade_required();

// Redirect logged-in users to homepage if required.
$redirect = optional_param('redirect', 1, PARAM_BOOL);

$urlparams = array();
if (!empty($CFG->defaulthomepage) &&
    ($CFG->defaulthomepage == HOMEPAGE_MY || $CFG->defaulthomepage == HOMEPAGE_MYCOURSES) &&
    $redirect === 0
) {
    $urlparams['redirect'] = 0;
}
$PAGE->set_url('/', $urlparams);
$PAGE->set_pagelayout('frontpage');
$PAGE->add_body_class('limitedwidth');
$PAGE->set_other_editing_capability('moodle/course:update');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_other_editing_capability('moodle/course:activityvisibility');

// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);

require_course_login($SITE);

$hasmaintenanceaccess = has_capability('moodle/site:maintenanceaccess', context_system::instance());

// If the site is currently under maintenance, then print a message.
if (!empty($CFG->maintenance_enabled) and !$hasmaintenanceaccess) {
    print_maintenance_message();
}

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
}

// If site registration needs updating, redirect.
\core\hub\registration::registration_reminder('/index.php');

$homepage = get_home_page();
if ($homepage != HOMEPAGE_SITE) {
    // Burada mevcut yönlendirme kodları yer alıyor
}

// Trigger event.
course_view(context_course::instance(SITEID));

$PAGE->set_pagetype('site-index');
$PAGE->set_docs_path('');
$editing = $PAGE->user_is_editing();
$PAGE->set_title('Depo Yönetim Sistemi');
$PAGE->set_heading('Depo Yönetim Sistemi');
$PAGE->set_secondary_active_tab('depoyonetimi');

// Modern tasarım için ek CSS ve JavaScript kütüphanelerini ekle
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css'));
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js'), true);
// JsBarcode kütüphanesi
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'), true);
// Chart.js kütüphanesi
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js'), true);

$siteformatoptions = course_get_format($SITE)->get_format_options();
$modinfo = get_fast_modinfo($SITE);
$modnamesused = $modinfo->get_used_module_names();

// The home page can have activities in the block aside. We should
// initialize the course editor before the page structure is rendered.
include_course_ajax($SITE, $modnamesused);

$courserenderer = $PAGE->get_renderer('core', 'course');

if ($hassiteconfig) {
    $editurl = new moodle_url('/course/view.php', ['id' => SITEID, 'sesskey' => sesskey()]);
    $editbutton = $OUTPUT->edit_button($editurl);
    $PAGE->set_button($editbutton);
}

echo $OUTPUT->header();

// CSS stil tanımlamaları doğrudan HTML içerisine ekleniyor
echo '<style>
/* Depo yönetim sistemi ana sayfa tasarımı */
body.path-site .card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    margin-bottom: 20px;
}

body.path-site .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* Hero bölümü */
.site-hero {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
    border-radius: 0 0 30px 30px;
}

.site-hero::before {
    content: "";
    position: absolute;
    top: -50px;
    right: -50px;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
}

.site-hero::after {
    content: "";
    position: absolute;
    bottom: -70px;
    left: -70px;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
}

.site-hero h1 {
    font-size: 2.8rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.site-hero p {
    font-size: 1.3rem;
    max-width: 700px;
    opacity: 0.9;
}

/* Kontrol paneli kartları */
.dashboard-card {
    padding: 25px;
    text-align: center;
    border-radius: 15px;
    margin-bottom: 30px;
    background: white;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.dashboard-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.dashboard-card i {
    font-size: 3rem;
    margin-bottom: 20px;
    color: #3498db;
}

.dashboard-card .badge-warning {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 0.8rem;
}

/* Ürün listesi */
.product-list-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.product-item {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    background: white;
}

.product-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.product-item .product-image {
    height: 160px;
    background-size: cover;
    background-position: center;
    background-color: #f5f5f5;
    position: relative;
}

.product-item .product-stock {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.product-item .in-stock {
    background: #2ecc71;
    color: white;
}

.product-item .low-stock {
    background: #f39c12;
    color: white;
}

.product-item .out-of-stock {
    background: #e74c3c;
    color: white;
}

.product-item .product-content {
    padding: 20px;
}

.product-item h3 {
    margin-top: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.product-item .product-location {
    display: flex;
    align-items: center;
    margin-top: 10px;
    color: #7f8c8d;
}

.product-item .product-location i {
    margin-right: 5px;
}

/* İstatistik sayaçları */
.counter-container {
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.counter-container:hover {
    transform: translateY(-5px);
}

.counter-container i {
    color: #3498db;
}

.counter-container h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 10px 0;
    color: #333;
}

/* Stok uyarıları bölümü */
.alerts-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 30px;
    margin: 40px 0;
}

.alert-item {
    border-left: 4px solid;
    padding: 10px 15px;
    margin-bottom: 15px;
    background: white;
    border-radius: 0 8px 8px 0;
}

.alert-item.danger {
    border-left-color: #e74c3c;
}

.alert-item.warning {
    border-left-color: #f39c12;
}

.alert-item.info {
    border-left-color: #3498db;
}

.alert-item h5 {
    margin-top: 0;
    font-weight: 600;
}

.alert-item p {
    margin-bottom: 0;
    color: #7f8c8d;
}

/* Genel düzenlemeler */
.frontpage-block {
    margin-bottom: 40px;
}

.action-buttons {
    margin-top: 20px;
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
    margin-right: 10px;
    margin-bottom: 10px;
}

.action-button i {
    margin-right: 8px;
}

.action-button.primary {
    background: #3498db;
    color: white;
}

.action-button.secondary {
    background: white;
    color: #3498db;
    border: 2px solid #3498db;
}

.action-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-decoration: none;
}

.barcode-preview {
    display: inline-block;
    margin: 15px 0;
    padding: 15px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* Grafik ve istatistik bölümleri */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
    margin: 20px 0;
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .site-hero {
        padding: 40px 0;
    }

    .site-hero h1 {
        font-size: 2rem;
    }

    .product-list-container {
        grid-template-columns: 1fr;
    }

    .chart-container {
        height: 250px;
    }
}
</style>';

// JavaScript kodları doğrudan HTML içerisine ekleniyor
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // AOS animasyon kütüphanesini başlat
    AOS.init({
        duration: 800,
        easing: "ease-in-out",
        once: true
    });

    // Sayaç animasyonları
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute("data-count"));
        const duration = 2000;
        const step = Math.ceil(target / (duration / 16));
        let current = 0;

        const updateCounter = () => {
            current += step;
            if (current < target) {
                counter.textContent = current;
                setTimeout(updateCounter, 16);
            } else {
                counter.textContent = target;
            }
        };

        const observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting) {
                updateCounter();
                observer.disconnect();
            }
        });

        observer.observe(counter);
    });

    // Kategori dağılımı grafiği
    const ctxCategory = document.getElementById("categoryChart");
    if (ctxCategory) {
        new Chart(ctxCategory, {
            type: "pie",
            data: {
                labels: ["Tişört", "Pantolon", "Ayakkabı", "Aksesuar", "Çanta", "Diğer"],
                datasets: [{
                    data: [25, 18, 15, 12, 10, 20],
                    backgroundColor: [
                        "#3498db",
                        "#2ecc71",
                        "#e74c3c",
                        "#f39c12",
                        "#9b59b6",
                        "#34495e"
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                }
            }
        });
    }

    // Aylık stok hareketleri grafiği
    const ctxStock = document.getElementById("stockChart");
    if (ctxStock) {
        new Chart(ctxStock, {
            type: "line",
            data: {
                labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
                datasets: [
                    {
                        label: "Giriş",
                        data: [65, 78, 52, 91, 43, 87],
                        borderColor: "#2ecc71",
                        backgroundColor: "rgba(46, 204, 113, 0.1)",
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: "Çıkış",
                        data: [42, 55, 40, 75, 32, 69],
                        borderColor: "#e74c3c",
                        backgroundColor: "rgba(231, 76, 60, 0.1)",
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
    }

    // Örnek barkod oluştur
    const barcodeElement = document.getElementById("sample-barcode");
    if (barcodeElement && typeof JsBarcode !== "undefined") {
        JsBarcode(barcodeElement, "123456789012", {
            format: "CODE128",
            lineColor: "#000",
            width: 1.5,
            height: 40,
            displayValue: true,
            fontSize: 12
        });
    }
});
</script>';

// Hero bölümü ekle
echo '<div class="site-hero" data-aos="fade-down">';
echo '<div class="container">';
echo '<h1 data-aos="fade-up" data-aos-delay="200">Depo Yönetim Sistemi</h1>';
echo '<p data-aos="fade-up" data-aos-delay="400">Tüm ürünlerinizi, stok durumlarını ve depo hareketlerinizi tek bir yerden yönetin.</p>';
echo '<div class="mt-4" data-aos="fade-up" data-aos-delay="600">';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_ekle.php" class="action-button primary"><i class="fa fa-plus"></i> Yeni Ürün Ekle</a>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/reports.php" class="action-button secondary"><i class="fa fa-chart-bar"></i> Raporlar</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// İstatistik sayaçları
echo '<div class="container mt-5" id="stats">';
echo '<div class="row text-center" data-aos="fade-up">';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-boxes fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="1256">0</h2>';
echo '<p>Toplam Ürün</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-warehouse fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="24">0</h2>';
echo '<p>Raf Sayısı</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-tags fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="42">0</h2>';
echo '<p>Farklı Kategori</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-3 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-exclamation-triangle fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="18">0</h2>';
echo '<p>Kritik Ürün</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Kontrol paneli kartları
echo '<div class="container mt-5" id="dashboard">';
echo '<h2 class="text-center mb-5" data-aos="fade-up">Hızlı Erişim</h2>';
echo '<div class="row">';
echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="dashboard-card">';
echo '<i class="fa fa-box"></i>';
echo '<h3>Ürün Yönetimi</h3>';
echo '<p>Ürün ekleme, düzenleme ve stok takibi yapın</p>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php" class="btn btn-primary mt-3">Ürünlere Git</a>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="400">';
echo '<div class="dashboard-card">';
echo '<i class="fa fa-exchange-alt"></i>';
echo '<h3>Stok Hareketleri</h3>';
echo '<p>Giriş ve çıkış işlemlerini yönetin</p>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/movements.php" class="btn btn-primary mt-3">Hareketlere Git</a>';
echo '<span class="badge badge-warning">3 Yeni</span>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="600">';
echo '<div class="dashboard-card">';
echo '<i class="fa fa-barcode"></i>';
echo '<h3>Barkod İşlemleri</h3>';
echo '<p>Barkod oluşturma ve okuma işlemleri</p>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/barcodes.php" class="btn btn-primary mt-3">Barkodar Git</a>';
echo '<div class="barcode-preview mt-3">';
echo '<svg id="sample-barcode"></svg>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Grafik ve analiz bölümü
echo '<div class="container mt-5">';
echo '<div class="row">';
echo '<div class="col-md-6" data-aos="fade-up">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Kategori Dağılımı</h4>';
echo '<div class="chart-container">';
echo '<canvas id="categoryChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Stok Hareketleri</h4>';
echo '<div class="chart-container">';
echo '<canvas id="stockChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Stok Uyarıları bölümü
echo '<div class="container frontpage-block">';
echo '<div class="alerts-section" data-aos="fade-up">';
echo '<h2 class="mb-4">Stok Uyarıları</h2>';
echo '<div class="row">';

// Stok uyarıları listesi
$alerts = array(
    array(
        'type' => 'danger',
        'title' => 'Kritik Stok Seviyesi',
        'content' => 'Tişört kategorisinde 5 ürün kritik stok seviyesinin altına düştü.'
    ),
    array(
        'type' => 'warning',
        'title' => 'Stok Azalıyor',
        'content' => 'Ayakkabı bölümünde 8 ürünün stok seviyesi minimum değere yaklaşıyor.'
    ),
    array(
        'type' => 'info',
        'title' => 'Yeni Ürün Girişi',
        'content' => 'Aksesuar kategorisine 12 yeni ürün girişi yapıldı.'
    )
);

foreach ($alerts as $alert) {
    echo '<div class="col-md-12 mb-3">';
    echo '<div class="alert-item ' . $alert['type'] . '">';
    echo '<h5>' . $alert['title'] . '</h5>';
    echo '<p>' . $alert['content'] . '</p>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';

// Düşük stoklu ürünler bölümü
echo '<div class="container frontpage-block">';
echo '<h2 class="text-center mb-4" data-aos="fade-up">Kritik Stok Ürünleri</h2>';
echo '<div class="product-list-container">';

// Örnek ürün kartları
$products = array(
    array(
        'id' => 1,
        'name' => 'Siyah Tişört L Beden',
        'image' => 'https://via.placeholder.com/300x200?text=Tişört',
        'stock' => 2,
        'status' => 'out-of-stock',
        'location' => 'A1 Rafı',
        'category' => 'Tişört'
    ),
    array(
        'id' => 2,
        'name' => 'Spor Ayakkabı 42 Numara',
        'image' => 'https://via.placeholder.com/300x200?text=Ayakkabı',
        'stock' => 5,
        'status' => 'low-stock',
        'location' => 'C2 Rafı',
        'category' => 'Ayakkabı'
    ),
    array(
        'id' => 3,
        'name' => 'Deri Kemer',
        'image' => 'https://via.placeholder.com/300x200?text=Kemer',
        'stock' => 3,
        'status' => 'low-stock',
        'location' => 'D1 Rafı',
        'category' => 'Aksesuar'
    ),
    array(
        'id' => 4,
        'name' => 'Kot Pantolon 34 Beden',
        'image' => 'https://via.placeholder.com/300x200?text=Pantolon',
        'stock' => 4,
        'status' => 'low-stock',
        'location' => 'B2 Rafı',
        'category' => 'Pantolon'
    )
);

foreach ($products as $product) {
    echo '<div class="product-item" data-aos="fade-up">';
    echo '<div class="product-image" style="background-image: url(\'' . $product['image'] . '\')">';

    // Stok durumu etiketi
    $stockText = $product['stock'] . ' adet';
    echo '<span class="product-stock ' . $product['status'] . '">' . $stockText . '</span>';

    echo '</div>';
    echo '<div class="product-content">';
    echo '<h3>' . $product['name'] . '</h3>';
    echo '<div class="product-location">';
    echo '<i class="fa fa-map-marker-alt"></i> ' . $product['location'] . ' (' . $product['category'] . ')';
    echo '</div>';
    echo '<div class="d-flex justify-content-between align-items-center mt-3">';
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_duzenle.php?id=' . $product['id'] . '" class="btn btn-sm btn-primary">Düzenle</a>';
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_hareketi.php?id=' . $product['id'] . '"" class="btn btn-sm btn-secondary">Stok Hareketi</a>';

    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '<div class="text-center">';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?filter=critical" class="btn btn-warning">Tüm Kritik Ürünleri Görüntüle</a>';
echo '</div>';
echo '</div>';

// Son işlemler bölümü
echo '<div class="container frontpage-block">';
echo '<div class="row">';
echo '<div class="col-md-6" data-aos="fade-up">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Son Eklenen Ürünler</h4>';
echo '<div class="list-group list-group-flush">';

// Son eklenen 5 ürün
$lastProducts = array(
    array('id' => 112, 'name' => 'Beyaz Gömlek L Beden', 'date' => '22.06.2023', 'category' => 'Gömlek'),
    array('id' => 111, 'name' => 'Spor Çanta', 'date' => '20.06.2023', 'category' => 'Çanta'),
    array('id' => 110, 'name' => 'Kadın Bot 38 Numara', 'date' => '19.06.2023', 'category' => 'Ayakkabı'),
    array('id' => 109, 'name' => 'Deri Cüzdan', 'date' => '18.06.2023', 'category' => 'Aksesuar'),
    array('id' => 108, 'name' => 'Slim Fit Jean 32 Beden', 'date' => '17.06.2023', 'category' => 'Pantolon')
);

foreach ($lastProducts as $product) {
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_detay.php?id=' . $product['id'] . '" class="list-group-item list-group-item-action">';
    echo '<div class="d-flex w-100 justify-content-between">';
    echo '<h5 class="mb-1">' . $product['name'] . '</h5>';
    echo '<small class="text-muted">' . $product['date'] . '</small>';
    echo '</div>';
    echo '<small class="text-muted">' . $product['category'] . '</small>';
    echo '</a>';
}

echo '</div>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?sort=newest" class="btn btn-outline-primary mt-3">Tümünü Görüntüle</a>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Son Stok Hareketleri</h4>';
echo '<div class="list-group list-group-flush">';

// Son stok hareketleri
$lastMovements = array(
    array('id' => 245, 'product' => 'Siyah Tişört L Beden', 'type' => 'Giriş', 'quantity' => '+10', 'date' => '23.06.2023', 'class' => 'success'),
    array('id' => 244, 'product' => 'Kot Pantolon 34 Beden', 'type' => 'Çıkış', 'quantity' => '-3', 'date' => '22.06.2023', 'class' => 'danger'),
    array('id' => 243, 'product' => 'Deri Kemer', 'type' => 'Giriş', 'quantity' => '+15', 'date' => '21.06.2023', 'class' => 'success'),
    array('id' => 242, 'product' => 'Spor Ayakkabı 42 Numara', 'type' => 'Çıkış', 'quantity' => '-2', 'date' => '20.06.2023', 'class' => 'danger'),
    array('id' => 241, 'product' => 'Beyaz Gömlek L Beden', 'type' => 'Giriş', 'quantity' => '+20', 'date' => '19.06.2023', 'class' => 'success')
);

foreach ($lastMovements as $movement) {
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/hareket_detay.php?id=' . $movement['id'] . '" class="list-group-item list-group-item-action">';
    echo '<div class="d-flex w-100 justify-content-between">';
    echo '<h5 class="mb-1">' . $movement['product'] . '</h5>';
    echo '<span class="badge badge-' . $movement['class'] . '">' . $movement['quantity'] . '</span>';
    echo '</div>';
    echo '<small>' . $movement['type'] . ' - ' . $movement['date'] . '</small>';
    echo '</a>';
}

echo '</div>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/movements.php" class="btn btn-outline-primary mt-3">Tümünü Görüntüle</a>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Hızlı işlemler bölümü
echo '<div class="container frontpage-block text-center" data-aos="fade-up">';
echo '<h2 class="mb-4">Hızlı İşlemler</h2>';
echo '<div class="row justify-content-center">';
echo '<div class="col-md-8">';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_ekle.php" class="action-button primary"><i class="fa fa-box-open"></i> Yeni Ürün</a>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_giris.php" class="action-button primary"><i class="fa fa-arrow-down"></i> Stok Girişi</a>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_cikis.php" class="action-button primary"><i class="fa fa-arrow-up"></i> Stok Çıkışı</a>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/reports.php" class="action-button secondary"><i class="fa fa-chart-pie"></i> Raporlar</a>';
echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/settings.php" class="action-button secondary"><i class="fa fa-cog"></i> Ayarlar</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// AOS için Javascript başlatma
echo '<script>
    
    AOS.init();
</script>';

echo $OUTPUT->footer();
?>