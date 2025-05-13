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
 * Depo Yönetimi - Özet Verileri ve Grafikler
 *
 * @package    local_depo_yonetimi
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/depo_yonetimi/db/access.php');
require_once($CFG->dirroot.'/local/depo_yonetimi/classes/form/urun_form.php');

// Sayfa başlığı ve başlık ayarları
$PAGE->set_url(new moodle_url('/local/depo_yonetimi/depo_ozet.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_depo_yonetimi') . ': ' . get_string('summary', 'local_depo_yonetimi'));
$PAGE->set_heading(get_string('pluginname', 'local_depo_yonetimi') . ': ' . get_string('summary', 'local_depo_yonetimi'));
$PAGE->set_pagelayout('admin');

// Yetki kontrolü
require_login();
require_capability('local/depo_yonetimi:view', context_system::instance());

// CSS ve JS dosyalarını ekleyin
$PAGE->requires->css('/local/depo_yonetimi/styles.css');
$PAGE->requires->js_call_amd('local/depo_yonetimi/charts', 'init');

// Gerekli Chart.js kütüphanesini ekleyin (CDN üzerinden)
$PAGE->requires->js(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js'));

// Veritabanından özet verileri al
$DB = $GLOBALS['DB'];

// 1. Günlük veriler (son 7 gün)
$daily_data = [];
$daily_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = strtotime("-$i days");
    $formatted_date = date('Y-m-d', $date);
    $sql_date = date('Y-m-d', $date);

    // Son 7 günün her biri için o gün eklenen ürün sayısını al
    $sql = "SELECT COUNT(*) FROM {local_depo_yonetimi_urunler} 
            WHERE DATE(timemodified) = ?";
    $count = $DB->count_records_sql($sql, [$sql_date]);

    $daily_data[] = $count;
    $daily_labels[] = date('d.m.Y', $date);
}

// 2. Haftalık veriler (son 4 hafta)
$weekly_data = [];
$weekly_labels = [];
for ($i = 3; $i >= 0; $i--) {
    $start_date = strtotime("-" . ($i * 7 + 6) . " days");
    $end_date = strtotime("-" . ($i * 7) . " days");

    $sql = "SELECT COUNT(*) FROM {local_depo_yonetimi_urunler} 
            WHERE timemodified BETWEEN ? AND ?";
    $count = $DB->count_records_sql($sql, [$start_date, $end_date]);

    $weekly_data[] = $count;
    $weekly_labels[] = date('d.m.Y', $start_date) . ' - ' . date('d.m.Y', $end_date);
}

// 3. Aylık veriler (son 6 ay)
$monthly_data = [];
$monthly_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $start_date = strtotime("first day of -$i month");
    $end_date = strtotime("last day of -$i month 23:59:59");

    $sql = "SELECT COUNT(*) FROM {local_depo_yonetimi_urunler} 
            WHERE timemodified BETWEEN ? AND ?";
    $count = $DB->count_records_sql($sql, [$start_date, $end_date]);

    $monthly_data[] = $count;
    $monthly_labels[] = date('F Y', $start_date);
}

// 4. Stok seviyeleri - En düşük 5 ürün
$low_stock_sql = "SELECT name, stock_quantity 
                  FROM {local_depo_yonetimi_urunler} 
                  ORDER BY stock_quantity ASC
                  LIMIT 5";
$low_stock_products = $DB->get_records_sql($low_stock_sql);

$low_stock_names = [];
$low_stock_quantities = [];
foreach ($low_stock_products as $product) {
    $low_stock_names[] = $product->name;
    $low_stock_quantities[] = $product->stock_quantity;
}

// 5. Stok seviyeleri - Kategoriye göre
$category_sql = "SELECT category, SUM(stock_quantity) as total_stock
                FROM {local_depo_yonetimi_urunler}
                GROUP BY category
                ORDER BY total_stock DESC";
$category_stocks = $DB->get_records_sql($category_sql);

$category_names = [];
$category_quantities = [];
foreach ($category_stocks as $category) {
    $category_names[] = $category->category;
    $category_quantities[] = $category->total_stock;
}

// Sayfayı göster
echo $OUTPUT->header();

// Sayfanın üst kısmı: başlık ve açıklama
echo '<div class="depo-summary-container">';
echo '<h2>' . get_string('summary_title', 'local_depo_yonetimi', 'Depo Yönetimi Özet Verileri') . '</h2>';
echo '<p>' . get_string('summary_description', 'local_depo_yonetimi', 'Bu sayfada depo yönetimi ile ilgili günlük, haftalık ve aylık özet veriler ile stok seviyesi grafikleri yer almaktadır.') . '</p>';

// Ana dashboard bölümü: Grafikleri içerir
echo '<div class="depo-summary-dashboard">';

// 1. Satır: Günlük ve haftalık veriler
echo '<div class="depo-summary-row">';

// Günlük veriler grafiği
echo '<div class="depo-summary-chart">';
echo '<h3>' . get_string('daily_summary', 'local_depo_yonetimi', 'Günlük Özet (Son 7 Gün)') . '</h3>';
echo '<div class="chart-container">';
echo '<canvas id="dailyChart"></canvas>';
echo '</div>';
echo '</div>';

// Haftalık veriler grafiği
echo '<div class="depo-summary-chart">';
echo '<h3>' . get_string('weekly_summary', 'local_depo_yonetimi', 'Haftalık Özet (Son 4 Hafta)') . '</h3>';
echo '<div class="chart-container">';
echo '<canvas id="weeklyChart"></canvas>';
echo '</div>';
echo '</div>';

echo '</div>'; // .depo-summary-row son

// 2. Satır: Aylık veriler ve düşük stok
echo '<div class="depo-summary-row">';

// Aylık veriler grafiği
echo '<div class="depo-summary-chart">';
echo '<h3>' . get_string('monthly_summary', 'local_depo_yonetimi', 'Aylık Özet (Son 6 Ay)') . '</h3>';
echo '<div class="chart-container">';
echo '<canvas id="monthlyChart"></canvas>';
echo '</div>';
echo '</div>';

// Düşük stok seviyeleri grafiği
echo '<div class="depo-summary-chart">';
echo '<h3>' . get_string('low_stock', 'local_depo_yonetimi', 'En Düşük Stok Seviyeleri') . '</h3>';
echo '<div class="chart-container">';
echo '<canvas id="lowStockChart"></canvas>';
echo '</div>';
echo '</div>';

echo '</div>'; // .depo-summary-row son

// 3. Satır: Kategoriye göre stok seviyeleri
echo '<div class="depo-summary-row">';

// Kategoriye göre stok seviyeleri grafiği
echo '<div class="depo-summary-chart full-width">';
echo '<h3>' . get_string('category_stock', 'local_depo_yonetimi', 'Kategoriye Göre Stok Seviyeleri') . '</h3>';
echo '<div class="chart-container">';
echo '<canvas id="categoryStockChart"></canvas>';
echo '</div>';
echo '</div>';

echo '</div>'; // .depo-summary-row son

echo '</div>'; // .depo-summary-dashboard son

// JS kodu ile grafikleri oluştur
echo '<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    // Renk paleti
    const colors = [
        "rgba(54, 162, 235, 0.7)",   // Mavi
        "rgba(255, 99, 132, 0.7)",   // Kırmızı
        "rgba(75, 192, 192, 0.7)",   // Yeşil
        "rgba(255, 206, 86, 0.7)",   // Sarı
        "rgba(153, 102, 255, 0.7)",  // Mor
        "rgba(255, 159, 64, 0.7)"    // Turuncu
    ];
    
    // 1. Günlük veriler grafiği
    const dailyCtx = document.getElementById("dailyChart").getContext("2d");
    const dailyChart = new Chart(dailyCtx, {
        type: "bar",
        data: {
            labels: ' . json_encode($daily_labels) . ',
            datasets: [{
                label: "Günlük Eklenen Ürün Sayısı",
                data: ' . json_encode($daily_data) . ',
                backgroundColor: colors[0],
                borderColor: colors[0].replace("0.7", "1"),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // 2. Haftalık veriler grafiği
    const weeklyCtx = document.getElementById("weeklyChart").getContext("2d");
    const weeklyChart = new Chart(weeklyCtx, {
        type: "bar",
        data: {
            labels: ' . json_encode($weekly_labels) . ',
            datasets: [{
                label: "Haftalık Eklenen Ürün Sayısı",
                data: ' . json_encode($weekly_data) . ',
                backgroundColor: colors[1],
                borderColor: colors[1].replace("0.7", "1"),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // 3. Aylık veriler grafiği
    const monthlyCtx = document.getElementById("monthlyChart").getContext("2d");
    const monthlyChart = new Chart(monthlyCtx, {
        type: "line",
        data: {
            labels: ' . json_encode($monthly_labels) . ',
            datasets: [{
                label: "Aylık Eklenen Ürün Sayısı",
                data: ' . json_encode($monthly_data) . ',
                backgroundColor: "rgba(0, 0, 0, 0)",
                borderColor: colors[2],
                borderWidth: 2,
                tension: 0.3,
                fill: false,
                pointBackgroundColor: colors[2]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // 4. Düşük stok seviyeleri grafiği
    const lowStockCtx = document.getElementById("lowStockChart").getContext("2d");
    const lowStockChart = new Chart(lowStockCtx, {
        type: "horizontalBar",
        type: "bar",
        data: {
            labels: ' . json_encode($low_stock_names) . ',
            datasets: [{
                label: "Stok Miktarı",
                data: ' . json_encode($low_stock_quantities) . ',
                backgroundColor: colors[3],
                borderColor: colors[3].replace("0.7", "1"),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // 5. Kategoriye göre stok seviyeleri grafiği
    const categoryStockCtx = document.getElementById("categoryStockChart").getContext("2d");
    const categoryStockChart = new Chart(categoryStockCtx, {
        type: "pie",
        data: {
            labels: ' . json_encode($category_names) . ',
            datasets: [{
                label: "Toplam Stok Miktarı",
                data: ' . json_encode($category_quantities) . ',
                backgroundColor: colors,
                borderColor: colors.map(color => color.replace("0.7", "1")),
                borderWidth: 1
            }]
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
});
</script>';

echo '</div>'; // .depo-summary-container son

// Diğer sayfalara erişim bağlantıları
echo '<div class="depo-nav-links">';
echo '<a href="' . new moodle_url('/local/depo_yonetimi/index.php') . '" class="btn btn-secondary">' . get_string('back_to_main', 'local_depo_yonetimi', 'Ana Sayfaya Dön') . '</a>';
echo '</div>';

echo $OUTPUT->footer();