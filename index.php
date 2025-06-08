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

    if (!file_exists('./config.php')) {
        header('Location: install.php');
        die;
    }

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
    $PAGE->set_title('Depo Yönetimi Sistemi');
    $PAGE->set_heading('Depo Yönetimi Sistemi');
    $PAGE->set_secondary_active_tab('depoyonetimi');

    // Modern tasarım için ek CSS ve JavaScript kütüphanelerini ekle
    $PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css'));
    $PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'));
    $PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js'), true);
    $PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/chart.js'), true);

    $siteformatoptions = course_get_format($SITE)->get_format_options();
    $modinfo = get_fast_modinfo($SITE);
    $modnamesused = $modinfo->get_used_module_names();

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
    /* Modern depo yönetimi tasarımı */
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
        background: linear-gradient(135deg, #2e7d32, #1b5e20);
        color: white;
        padding: 80px 0;
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
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .site-hero p {
        font-size: 1.3rem;
        max-width: 700px;
        opacity: 0.9;
    }
    
    /* Animasyonlu içerik kartları */
    .feature-card {
        padding: 30px;
        text-align: center;
        border-radius: 15px;
        margin-bottom: 30px;
        background: white;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    
    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    
    .feature-card i {
        font-size: 3rem;
        margin-bottom: 20px;
        color: #2e7d32;
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
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-item .product-image i {
        font-size: 4rem;
        color: #2e7d32;
    }
    
    .product-item .product-content {
        padding: 20px;
    }
    
    .product-item h3 {
        margin-top: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    /* Sayaç bileşenleri */
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
        color: #2e7d32;
    }
    
    .counter-container h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 10px 0;
        color: #333;
    }
    
    /* Duyuru bölümü */
    .announcements-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 30px;
        margin: 40px 0;
    }
    
    /* İstatistik grafikleri */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin: 20px 0;
    }
    
    /* Kritik stok uyarısı */
    .critical-stock {
        background-color: #ffebee;
        border-left: 4px solid #f44336;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .critical-stock h3 {
        color: #d32f2f;
        margin-top: 0;
    }
    
    /* Hızlı erişim butonları */
    .action-button {
        display: inline-block;
        margin: 10px;
        padding: 15px 25px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s;
    }
    
    .action-button.primary {
        background: #2e7d32;
        color: white;
    }
    
    .action-button.secondary {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .action-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        text-decoration: none;
    }
    
    .action-button i {
        margin-right: 10px;
    }
    
    /* Genel düzenlemeler */
    .frontpage-block {
        margin-bottom: 40px;
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
    
    /* Stok durumu göstergeleri */
    .stock-indicator {
        display: inline-block;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        margin-right: 5px;
    }
    
    .stock-high {
        background-color: #4caf50;
    }
    
    .stock-medium {
        background-color: #ff9800;
    }
    
    .stock-low {
        background-color: #f44336;
    }
    
    /* Barkod tarayıcı bölümü */
    .barcode-scanner-container {
        background: #f5f5f5;
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .barcode-scanner-container i {
        font-size: 3rem;
        color: #2e7d32;
        margin-bottom: 15px;
    }
    
    .barcode-input {
        width: 100%;
        max-width: 300px;
        padding: 10px 15px;
        border: 2px solid #ddd;
        border-radius: 30px;
        margin: 15px auto;
        font-size: 1.1rem;
        text-align: center;
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
    
        // Hero bölümü için parallax efekti
        const hero = document.querySelector(".site-hero");
        if (hero) {
            window.addEventListener("scroll", function() {
                const scrolled = window.scrollY;
                hero.style.backgroundPosition = "center " + (scrolled * 0.4) + "px";
            });
        }
    
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
                    counter.innerText = current;
                    setTimeout(updateCounter, 16);
                } else {
                    counter.innerText = target;
                }
            };
    
            const observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting) {
                    updateCounter();
                    observer.unobserve(counter);
                }
            });
    
            observer.observe(counter);
        });
    
        // Stok hareketleri grafiği
        const ctxMovement = document.getElementById("stockMovementChart");
        if (ctxMovement) {
            new Chart(ctxMovement, {
                type: "line",
                data: {
                    labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
                    datasets: [
                        {
                            label: "Stok Girişi",
                            data: [120, 190, 150, 210, 180, 250],
                            borderColor: "#4caf50",
                            backgroundColor: "rgba(76, 175, 80, 0.1)",
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: "Stok Çıkışı",
                            data: [90, 110, 130, 140, 160, 200],
                            borderColor: "#f44336",
                            backgroundColor: "rgba(244, 67, 54, 0.1)",
                            tension: 0.3,
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
    
        // Ürün kategorileri grafiği
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
                                "#4caf50",
                                "#2196f3",
                                "#ff9800",
                                "#9c27b0",
                                "#607d8b"
                            ]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "right"
                        }
                    }
                }
            });
        }
        
        // Barkod tarayıcı simülasyonu
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

    // Hero bölümü ekle
    echo '<div class="site-hero" data-aos="fade-down">';
    echo '<div class="container">';
    echo '<h1 data-aos="fade-up" data-aos-delay="200">Depo Yönetimi Sistemi</h1>';
    echo '<p data-aos="fade-up" data-aos-delay="400">Stok takibi, ürün yönetimi ve depo operasyonları için modern ve kullanıcı dostu bir platform.</p>';
    echo '<div class="mt-4" data-aos="fade-up" data-aos-delay="600">';
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php" class="btn btn-light btn-lg mr-3">Ürünleri Görüntüle</a>';
    echo '<a href="#features" class="btn btn-outline-light btn-lg">Özellikler</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Barkod tarayıcı alanı
    echo '<div class="container">';
    echo '<div class="barcode-scanner-container" data-aos="fade-up">';
    echo '<i class="fas fa-barcode"></i>';
    echo '<h3>Hızlı Ürün Arama</h3>';
    echo '<p>Barkod numarasını girin veya tarayıcı ile okutun</p>';
    echo '<input type="text" id="barcodeInput" class="barcode-input" placeholder="Barkod numarası" autofocus>';
    echo '</div>';
    echo '</div>';

    // İstatistik sayaçları
    echo '<div class="container mt-5" id="stats">';
    echo '<div class="row text-center" data-aos="fade-up">';
    echo '<div class="col-md-4 mb-4">';
    echo '<div class="counter-container">';
    echo '<i class="fa fa-box fa-3x mb-3 text-primary"></i>';
    echo '<h2 class="counter" data-count="1250">0</h2>';
    echo '<p>Toplam Ürün</p>';
    echo '</div>';
    echo '</div>';
    echo '<div class="col-md-4 mb-4">';
    echo '<div class="counter-container">';
    echo '<i class="fa fa-warehouse fa-3x mb-3 text-primary"></i>';
    echo '<h2 class="counter" data-count="8">0</h2>';
    echo '<p>Depo Bölümü</p>';
    echo '</div>';
    echo '</div>';
    echo '<div class="col-md-4 mb-4">';
    echo '<div class="counter-container">';
    echo '<i class="fa fa-exchange-alt fa-3x mb-3 text-primary"></i>';
    echo '<h2 class="counter" data-count="3540">0</h2>';
    echo '<p>Aylık İşlem</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Kritik stok uyarısı
    echo '<div class="container mt-4">';
    echo '<div class="critical-stock" data-aos="fade-up">';
    echo '<h3><i class="fas fa-exclamation-triangle"></i> Kritik Stok Uyarısı</h3>';
    echo '<p>5 ürünün stok seviyesi kritik eşiğin altında. Lütfen kontrol edin ve gerekli sipariş işlemlerini başlatın.</p>';
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?filter=critical" class="btn btn-danger">Kritik Ürünleri Görüntüle</a>';
    echo '</div>';
    echo '</div>';

    // Özellikler bölümü
    echo '<div class="container mt-5" id="features">';
    echo '<h2 class="text-center mb-5" data-aos="fade-up">Sistem Özellikleri</h2>';
    echo '<div class="row">';
    echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="200">';
    echo '<div class="feature-card">';
    echo '<i class="fa fa-boxes"></i>';
    echo '<h3>Stok Takibi</h3>';
    echo '<p>Gelişmiş stok takip sistemi ile ürünlerinizin miktarını, yerini ve durumunu anlık olarak izleyin</p>';
    echo '</div>';
    echo '</div>';
    echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="400">';
    echo '<div class="feature-card">';
    echo '<i class="fa fa-barcode"></i>';
    echo '<h3>Barkod Sistemi</h3>';
    echo '<p>Ürünleri hızlı tanımlamak için barkod sistemi ve otomatik etiket basım özellikleri</p>';
    echo '</div>';
    echo '</div>';
    echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="600">';
    echo '<div class="feature-card">';
    echo '<i class="fa fa-chart-line"></i>';
    echo '<h3>Raporlama</h3>';
    echo '<p>Detaylı raporlar ve analizlerle stok hareketlerini ve ürün performansını takip edin</p>';
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
    echo '<h4 class="card-title">Stok Hareketleri</h4>';
    echo '<div class="chart-container">';
    echo '<canvas id="stockMovementChart"></canvas>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="200">';
    echo '<div class="card">';
    echo '<div class="card-body">';
    echo '<h4 class="card-title">Ürün Kategorileri</h4>';
    echo '<div class="chart-container">';
    echo '<canvas id="productCategoriesChart"></canvas>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Son işlemler bölümü
    echo '<div class="container mt-5">';
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

    // Kritik stoklu ürünler listesi
    echo '<div class="container mt-5">';
    echo '<h2 class="mb-4" data-aos="fade-up">Kritik Stoklu Ürünler</h2>';
    echo '<div class="row">';

    // Örnek kritik stoklu ürünler
    $criticalProducts = array(
        array('id' => 105, 'name' => 'Beyaz Tişört M Beden', 'stock' => 3, 'min' => 10, 'category' => 'Tişört'),
        array('id' => 87, 'name' => 'Siyah Spor Ayakkabı 40 Numara', 'stock' => 2, 'min' => 5, 'category' => 'Ayakkabı'),
        array('id' => 94, 'name' => 'Kot Pantolon 30 Beden', 'stock' => 4, 'min' => 8, 'category' => 'Pantolon')
    );

    foreach ($criticalProducts as $product) {
        echo '<div class="col-md-4 mb-3" data-aos="fade-up" data-aos-delay="200">';
        echo '<div class="card h-100">';
        echo '<div class="card-body">';
        echo '<h5 class="card-title">' . $product['name'] . '</h5>';
        echo '<div class="d-flex justify-content-between align-items-center">';
        echo '<span class="badge badge-danger">Stok: ' . $product['stock'] . '</span>';
        echo '<span>Min.: ' . $product['min'] . '</span>';
        echo '</div>';
        echo '<p class="card-text mt-2"><small class="text-muted">' . $product['category'] . '</small></p>';
        echo '<div class="d-flex justify-content-between mt-3">';
        echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/urun_detay.php?id=' . $product['id'] . '" class="btn btn-sm btn-info">Detay</a>';
        echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/actions/stok_giris.php?id=' . $product['id'] . '" class="btn btn-sm btn-secondary">Stok Hareketi</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '<div class="text-center">';
    echo '<a href="' . $CFG->wwwroot . '/blocks/depo_yonetimi/products.php?filter=critical" class="btn btn-warning mt-3">Tüm Kritik Ürünleri Görüntüle</a>';
    echo '</div>';
    echo '</div>';

    // Yaklaşan ürün giriş/çıkışları
    echo '<div class="container mt-5">';
    echo '<h2 class="mb-4" data-aos="fade-up">Yaklaşan İşlemler</h2>';
    echo '<div class="card" data-aos="fade-up" data-aos-delay="200">';
    echo '<div class="card-body">';
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>İşlem</th>';
    echo '<th>Ürün</th>';
    echo '<th>Miktar</th>';
    echo '<th>Tarih</th>';
    echo '<th>Durum</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    // Örnek yaklaşan işlemler
    $upcomingTasks = array(
        array('id' => 1, 'type' => 'Giriş', 'product' => 'Erkek Tişört Paketi', 'quantity' => 50, 'date' => '25.06.2023', 'status' => 'Bekliyor'),
        array('id' => 2, 'type' => 'Çıkış', 'product' => 'Kadın Ayakkabı Siparişi', 'quantity' => 15, 'date' => '26.06.2023', 'status' => 'Hazırlanıyor'),
        array('id' => 3, 'type' => 'Giriş', 'product' => 'Aksesuar Koleksiyonu', 'quantity' => 30, 'date' => '28.06.2023', 'status' => 'Bekliyor')
    );

    foreach ($upcomingTasks as $task) {
        $statusClass = $task['status'] === 'Bekliyor' ? 'warning' : 'info';
        $typeClass = $task['type'] === 'Giriş' ? 'success' : 'danger';

        echo '<tr>';
        echo '<td><span class="badge badge-' . $typeClass . '">' . $task['type'] . '</span></td>';
        echo '<td>' . $task['product'] . '</td>';
        echo '<td>' . $task['quantity'] . '</td>';
        echo '<td>' . $task['date'] . '</td>';
        echo '<td><span class="badge badge-' . $statusClass . '">' . $task['status'] . '</span></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // AOS için Javascript başlatma ve Moodle sayfa sonu
    echo '<script>
       
        AOS.init();
    </script>';

    echo $OUTPUT->footer();
    ?>