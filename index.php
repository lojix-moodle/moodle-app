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
 * Moodle ana sayfa dosyası.
 *
 * @package    core
 * @copyright  2023 Moodle Öğrenme Platformu
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
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_secondary_active_tab('coursehome');

// Modern tasarım için ek CSS ve JavaScript kütüphanelerini ekle
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css'));
$PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js'), true);
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
/* Modern ana sayfa tasarımı */
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
    background: linear-gradient(135deg, #0073e6, #5a23c8);
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
    color: #0073e6;
}

/* Modern kurs listesi */
.course-list-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin: 40px 0;
}

.course-item {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.course-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.course-item .course-image {
    height: 160px;
    background-size: cover;
    background-position: center;
    background-color: #f5f5f5;
}

.course-item .course-content {
    padding: 20px;
}

.course-item h3 {
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
    color: #0073e6;
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

    .course-list-container {
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

    // Katılım grafiği
    const ctxEngagement = document.getElementById("engagementChart");
    if (ctxEngagement) {
        new Chart(ctxEngagement, {
            type: "line",
            data: {
                labels: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran"],
                datasets: [
                    {
                        label: "Aktif Öğrenciler",
                        data: [65, 78, 82, 91, 103, 120],
                        borderColor: "#3498db",
                        backgroundColor: "rgba(52, 152, 219, 0.1)",
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: "Tamamlanan Aktiviteler",
                        data: [42, 55, 60, 75, 82, 90],
                        borderColor: "#2ecc71",
                        backgroundColor: "rgba(46, 204, 113, 0.1)",
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

    // Kurs kategorileri grafiği
    const ctxCategories = document.getElementById("categoriesChart");
    if (ctxCategories) {
        new Chart(ctxCategories, {
            type: "doughnut",
            data: {
                labels: ["Bilgisayar Bilimleri", "Dil Eğitimi", "Matematik", "Fen Bilimleri", "Sosyal Bilimler"],
                datasets: [{
                    data: [35, 25, 15, 15, 10],
                    backgroundColor: [
                        "#3498db",
                        "#2ecc71",
                        "#f1c40f",
                        "#e74c3c",
                        "#9b59b6"
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
});
</script>';

// Hero bölümü ekle
echo '<div class="site-hero" data-aos="fade-down">';
echo '<div class="container">';
echo '<h1 data-aos="fade-up" data-aos-delay="200">' . $SITE->fullname . '</h1>';
echo '<p data-aos="fade-up" data-aos-delay="400">Modern, interaktif ve kullanıcı dostu bir öğrenme platformuna hoş geldiniz.</p>';
echo '<div class="mt-4" data-aos="fade-up" data-aos-delay="600">';
echo '<a href="' . new moodle_url('/course/index.php') . '" class="btn btn-light btn-lg mr-3">Kursları Keşfet</a>';
echo '<a href="#features" class="btn btn-outline-light btn-lg">Özellikler</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// İstatistik sayaçları
echo '<div class="container mt-5" id="stats">';
echo '<div class="row text-center" data-aos="fade-up">';
echo '<div class="col-md-4 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-users fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="' . $DB->count_records('user', array('deleted' => 0)) . '">0</h2>';
echo '<p>Öğrenci</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-book fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="' . $DB->count_records('course', array('visible' => 1)) . '">0</h2>';
echo '<p>Kurs</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4 mb-4">';
echo '<div class="counter-container">';
echo '<i class="fa fa-certificate fa-3x mb-3 text-primary"></i>';
echo '<h2 class="counter" data-count="5000">0</h2>';
echo '<p>Başarı Sertifikası</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Özellikler bölümü
echo '<div class="container mt-5" id="features">';
echo '<h2 class="text-center mb-5" data-aos="fade-up">Platform Özellikleri</h2>';
echo '<div class="row">';
echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="feature-card">';
echo '<i class="fa fa-laptop"></i>';
echo '<h3>İnteraktif Dersler</h3>';
echo '<p>Video dersler, sınavlar ve interaktif öğrenme materyalleri ile zengin içerik</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="400">';
echo '<div class="feature-card">';
echo '<i class="fa fa-comments"></i>';
echo '<h3>Canlı Tartışmalar</h3>';
echo '<p>Eğitmenler ve diğer öğrencilerle anlık iletişim kurma imkanı</p>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="600">';
echo '<div class="feature-card">';
echo '<i class="fa fa-mobile-alt"></i>';
echo '<h3>Mobil Erişim</h3>';
echo '<p>Tüm cihazlardan erişim ile her zaman ve her yerde öğrenme fırsatı</p>';
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
echo '<h4 class="card-title">Öğrenci Katılımı</h4>';
echo '<div class="chart-container">';
echo '<canvas id="engagementChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6" data-aos="fade-up" data-aos-delay="200">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h4 class="card-title">Kurs Kategorileri</h4>';
echo '<div class="chart-container">';
echo '<canvas id="categoriesChart"></canvas>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Print Section or custom info.
echo '<div class="container frontpage-block">';
if (!empty($CFG->customfrontpageinclude)) {
    include($CFG->customfrontpageinclude);
} else if ($siteformatoptions['numsections'] > 0) {
    echo $courserenderer->frontpage_section1();
}
echo '</div>';

// Duyurular bölümü
echo '<div class="container frontpage-block">';
echo '<div class="announcements-section" data-aos="fade-up">';
echo '<h2 class="mb-4">Duyurular ve Etkinlikler</h2>';
echo '<div class="row">';

// Son eklenen duyurular veya güncellemeler burada listelenebilir
$announcements = array(
    array(
        'title' => 'Yeni Kurslar Eklendi',
        'date' => '23 Haziran 2023',
        'content' => 'Bilişim teknolojileri ve yabancı dil kategorilerinde yeni kurslar platformumuza eklenmiştir.'
    ),
    array(
        'title' => 'Platform Güncellemesi',
        'date' => '20 Haziran 2023',
        'content' => 'Platformumuz yeni özellikler ve geliştirilmiş arayüz ile güncellendi. Daha iyi bir deneyim için keşfetmeye başlayın.'
    ),
    array(
        'title' => 'Yaz Dönemi Kayıtları Açıldı',
        'date' => '15 Haziran 2023',
        'content' => 'Yaz dönemi kurslarına kayıtlar başlamıştır. 30 Haziran\'a kadar erken kayıt indirimi mevcuttur.'
    ),
    array(
        'title' => 'Canlı Webinar: Modern Öğrenme Teknikleri',
        'date' => '10 Haziran 2023',
        'content' => '25 Haziran saat 14:00\'te "Modern Öğrenme Teknikleri" konulu webinarımıza katılabilirsiniz.'
    )
);

foreach ($announcements as $announcement) {
    echo '<div class="col-md-6 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">' . $announcement['title'] . '</h5>';
    echo '<h6 class="card-subtitle mb-2 text-muted">' . $announcement['date'] . '</h6>';
    echo '<p class="card-text">' . $announcement['content'] . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';

// Ana kurs listesi için özel stil
echo '<div class="container frontpage-block">';
echo '<h2 class="text-center mb-4" data-aos="fade-up">Öne Çıkan Kurslar</h2>';
echo '<div class="course-list-container">';

// Standart kurs listesi ekleniyor, bu kısım Moodle'ın kendi kurs listesini getirir
echo $courserenderer->frontpage();

echo '</div>';
echo '</div>';

if ($editing && has_capability('moodle/course:create', context_system::instance())) {
    echo $courserenderer->add_new_course_button();
}

echo $OUTPUT->footer();
?>