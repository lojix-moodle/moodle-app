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
 * Dashboard for Depo Yonetimi
 *
 * @package    block_depo_yonetimi
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/depo_yonetimi/lib.php');
require_once($CFG->dirroot . '/blocks/depo_yonetimi/dashboard_lib.php');

// Sayfa ayarları
$PAGE->set_url('/blocks/depo_yonetimi/dashboard.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('dashboard', 'block_depo_yonetimi'));
$PAGE->set_heading(get_string('dashboard', 'block_depo_yonetimi'));
$PAGE->set_pagelayout('standard');

// CSS ve JavaScript dosyalarını ekle
$PAGE->requires->css('/blocks/depo_yonetimi/templates/dashboard.css');
$PAGE->requires->js_call_amd('block_depo_yonetimi/dashboard_charts', 'init');

// Yetkiyi kontrol et
require_login();
$context = context_system::instance();
require_capability('block/depo_yonetimi:view', $context);

// Verileri al
$daily_summary = get_daily_summary();
$weekly_summary = get_weekly_summary();
$monthly_summary = get_monthly_summary();
$stock_levels = get_stock_levels();
$pending_orders = get_pending_orders();
$critical_stock_alerts = get_critical_stock_alerts();
$kpi_data = get_kpi_data();
$recent_activities = get_recent_activities();

// Debug bilgisi (geliştirme sırasında yardımcı olur)
// debugging('Stock levels: ' . print_r($stock_levels, true), DEBUG_DEVELOPER);

// Şablona verileri gönder
$templatecontext = [
    'daily_summary' => $daily_summary,
    'weekly_summary' => $weekly_summary,
    'monthly_summary' => $monthly_summary,
    'stock_levels' => $stock_levels,
    'pending_orders' => $pending_orders,
    'critical_stock_alerts' => $critical_stock_alerts,
    'kpi_data' => $kpi_data,
    'recent_activities' => $recent_activities
];

// Sayfayı göster
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_depo_yonetimi/dashboard', $templatecontext);
echo $OUTPUT->footer();