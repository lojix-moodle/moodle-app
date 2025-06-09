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

    /* Yeni başlık tasarımı için CSS */
    .dashboard-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        padding: 2rem 0;
        color: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 30%;
        height: 100%;
        background: url('https://cdn.pixabay.com/photo/2018/03/10/12/00/teamwork-3213924_1280.png') no-repeat;
        background-size: cover;
        background-position: left center;
        opacity: 0.1;
    }

    .header-content {
        position: relative;
        z-index: 2;
    }

    .header-title {
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .header-title i {
        background: rgba(255, 255, 255, 0.2);
        padding: 12px;
        border-radius: 12px;
        margin-right: 15px;
        font-size: 1.8rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .header-subtitle {
        font-size: 1rem;
        opacity: 0.8;
        max-width: 650px;
        margin-top: 8px;
        margin-left: 55px;
    }

    .dashboard-metrics {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 15px;
        display: flex;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .metric-item {
        flex: 1;
        min-width: 150px;
        padding: 10px 15px;
        text-align: center;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .metric-item:last-child {
        border-right: none;
    }

    .metric-title {
        font-size: 0.8rem;
        opacity: 0.7;
        margin-bottom: 5px;
    }

    .metric-value {
        font-size: 1.5rem;
        font-weight: 600;
    }

    /* Hızlı erişim paneli için CSS */
    .quick-access-panel {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
        position: relative;
    }

    .quick-access-tabs {
        display: flex;
        border-bottom: 1px solid #eee;
        padding: 0 15px;
    }

    .quick-access-tab {
        padding: 15px 20px;
        font-weight: 500;
        color: #777;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .quick-access-tab.active {
        color: #2a5298;
        border-bottom-color: #2a5298;
    }

    .quick-access-tab i {
        margin-right: 8px;
    }

    .quick-access-content {
        padding: 20px;
    }

    .search-items {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .search-item {
        flex: 1;
        min-width: 200px;
    }

    .search-item label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: #555;
    }

    .search-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
        gap: 10px;
    }

    .btn-search {
        background: #2a5298;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-reset {
        background: #f1f2f6;
        color: #444;
        border: none;
        padding: 10px 25px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-search:hover {
        background: #1e3c72;
        transform: translateY(-2px);
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

    /* Kurumsal Bilgilendirme Paneli için CSS */
    .enterprise-info-panel {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
        margin-bottom: 2.5rem;
        overflow: hidden;
        border: 1px solid #eaecef;
    }

    .enterprise-info-header {
        background: linear-gradient(135deg, #192e59 0%, #2d5286 100%);
        color: white;
        padding: 18px 25px;
        font-weight: 600;
    }

    .enterprise-info-header h4 {
        margin: 0;
        font-size: 1.3rem;
        letter-spacing: 0.3px;
    }

    .enterprise-info-content {
        padding: 30px;
    }

    .enterprise-subtitle {
        color: #192e59;
        font-weight: 600;
        font-size: 1.25rem;
        margin-bottom: 15px;
        letter-spacing: -0.3px;
    }

    .enterprise-text {
        color: #505c75;
        line-height: 1.7;
        font-size: 1rem;
        margin-bottom: 25px;
    }

    /* Özellikler bölümü tasarımı */
    .enterprise-features {
        margin-top: 25px;
    }

    .feature-row {
        display: flex;
        gap: 25px;
        margin-bottom: 20px;
    }

    .feature-box {
        flex: 1;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
        border-radius: 8px;
        background-color: #f8fafc;
        transition: all 0.2s ease;
    }

    .feature-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #192e59 0%, #2d5286 100%);
        border-radius: 8px;
        color: white;
        font-size: 1rem;
    }

    .feature-details {
        flex: 1;
    }

    .feature-details h6 {
        margin: 0 0 5px;
        color: #192e59;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .feature-details p {
        margin: 0;
        color: #505c75;
        font-size: 0.85rem;
        line-height: 1.5;
    }

    /* Sağ taraf - Metrikler */
    .enterprise-metrics {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 20px;
    }

    /* Sertifikalar */
    .enterprise-certification {
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }

    .cert-badges {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 15px;
    }

    .cert-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 12px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        min-width: 100px;
    }

    .cert-badge i {
        font-size: 1.5rem;
        margin-bottom: 5px;
    }

    .cert-badge.iso i {
        color: #192e59;
    }

    .cert-badge.gdpr i {
        color: #2d5286;
    }

    .cert-badge span {
        font-size: 0.8rem;
        font-weight: 600;
        color: #505c75;
    }

    .cert-description {
        font-size: 0.85rem;
        color: #505c75;
    }

    /* Performans çemberi */
    .enterprise-performance {
        display: flex;
        justify-content: center;
        padding: 15px 0;
    }

    .perf-circle {
        text-align: center;
    }

    .circular-chart {
        display: block;
        max-width: 150px;
        max-height: 150px;
        margin: 0 auto;
    }

    .circle-bg {
        fill: none;
        stroke: #eaeef5;
        stroke-width: 2.8;
    }

    .circle {
        fill: none;
        stroke-width: 2.8;
        stroke-linecap: round;
        stroke: #2d5286;
        animation: progress 1.5s ease-out forwards;
    }

    @keyframes progress {
        0% { stroke-dasharray: 0 100; }
    }

    .percentage {
        fill: #2d5286;
        font-size: 0.5em;
        text-anchor: middle;
        font-weight: bold;
    }

    .perf-label {
        font-size: 0.9rem;
        color: #505c75;
        margin-top: 10px;
    }

    /* Versiyon bilgileri */
    .enterprise-version {
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 20px;
    }

    .version-details {
        text-align: center;
    }

    .version-badge {
        display: inline-block;
        background: linear-gradient(135deg, #192e59 0%, #2d5286 100%);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 15px;
        margin-bottom: 8px;
    }

    .version-number {
        font-size: 1.25rem;
        font-weight: 700;
        color: #192e59;
        margin-bottom: 8px;
    }

    .version-info {
        font-size: 0.8rem;
        color: #505c75;
        margin-bottom: 10px;
    }

    .support-info {
        font-size: 0.85rem;
        color: #2d5286;
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .feature-row {
            flex-direction: column;
            gap: 15px;
        }

        .enterprise-metrics {
            margin-top: 30px;
        }
    }


    /* Yeni Profesyonel Kartlar için CSS */
    .pro-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.25s ease;
    }

    .pro-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transform: translateY(-5px);
    }

    .pro-card-header {
        padding: 20px 25px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .header-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #192e59 0%, #2d5286 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.4rem;
        box-shadow: 0 4px 10px rgba(45, 82, 134, 0.3);
    }

    .header-content h4 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #192e59;
    }

    .header-content span {
        font-size: 0.9rem;
        color: #505c75;
        display: block;
        margin-top: 3px;
    }

    .pro-card-body {
        padding: 25px;
    }

    .pro-card-footer {
        padding: 15px 25px;
        background-color: #f8fafc;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        font-size: 0.85rem;
        color: #505c75;
    }

    /* Doküman Bölümü */
    .document-section {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .doc-item {
        display: flex;
        padding: 15px;
        background-color: #f8fafc;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .doc-item:hover {
        background-color: #f1f5f9;
    }

    .doc-icon {
        width: 45px;
        height: 45px;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.2rem;
    }

    .doc-icon i.fa-file-pdf {
        color: #e74c3c;
    }

    .doc-icon i.fa-file-alt {
        color: #3498db;
    }

    .doc-icon i.fa-file-powerpoint {
        color: #e67e22;
    }

    .doc-content h5 {
        margin: 0 0 5px;
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .doc-content p {
        margin: 0 0 8px;
        font-size: 0.85rem;
        color: #505c75;
        line-height: 1.5;
    }

    .doc-meta {
        display: flex;
        gap: 15px;
    }

    .doc-meta span {
        font-size: 0.75rem;
        color: #7f8c8d;
        display: flex;
        align-items: center;
    }

    .doc-meta span i {
        margin-right: 5px;
        font-size: 0.8rem;
    }

    /* Analitik Metrikler */
    .kpi-metrics {
        padding: 5px;
    }

    .analytics-widget {
        background-color: #fff;
        border-radius: 10px;
        padding: 15px;
        display: flex;
        align-items: center;
        position: relative;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        height: 100px;
    }

    .analytics-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: linear-gradient(135deg, #192e59 0%, #2d5286 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        margin-right: 15px;
    }

    .analytics-content h3 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .analytics-content span {
        font-size: 0.8rem;
        color: #7f8c8d;
    }

    .trend {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 15px;
        display: flex;
        align-items: center;
    }

    .trend-up {
        background-color: rgba(39, 174, 96, 0.1);
        color: #27ae60;
    }

    .trend-down {
        background-color: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
    }

    /* Trend Grafiği */
    .industry-trends .chart-container {
        height: 210px;
    }

    .trend-highlights {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .trend-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .trend-name {
        flex: 1;
        font-size: 0.9rem;
        color: #505c75;
    }

    .trend-progress {
        width: 100px;
    }

    .trend-value {
        font-weight: 600;
        color: #2c3e50;
    }

    /* Entegrasyonlar */
    .integration-systems {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .integration-category h5 {
        font-size: 0.9rem;
        color: #7f8c8d;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .integration-platforms {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .integration-item {
        display: flex;
        align-items: center;
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 12px;
        width: calc(50% - 10px);
        transition: all 0.2s ease;
    }

    .integration-item:hover {
        background-color: #f1f5f9;
    }

    .integration-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 12px;
        font-size: 1.1rem;
    }

    .integration-icon.erp {
        background: linear-gradient(135deg, #4834d4 0%, #686de0 100%);
    }

    .integration-icon.crm {
        background: linear-gradient(135deg, #eb3b5a 0%, #fc5c65 100%);
    }

    .integration-icon.accounting {
        background: linear-gradient(135deg, #20bf6b 0%, #26de81 100%);
    }

    .integration-icon.ecommerce {
        background: linear-gradient(135deg, #0fb9b1 0%, #2bcbba 100%);
    }

    .integration-icon.marketplace {
        background: linear-gradient(135deg, #f7b731 0%, #fed330 100%);
    }

    .integration-details h6 {
        margin: 0 0 3px;
        font-size: 0.95rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .integration-status {
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .integration-status.connected {
        color: #20bf6b;
    }

    .integration-status.pending {
        color: #f7b731;
    }

    @media (max-width: 992px) {
        .integration-item {
            width: 100%;
        }
    }

    /* Performans Göstergeleri CSS */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    }

    .performance-dashboard .card-header {
        padding: 1rem 1.5rem;
    }

    .performance-metrics {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 10px;
    }

    .metric-box {
        background-color: #f8fafc;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .metric-title {
        font-size: 0.9rem;
        color: #505c75;
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .metric-title i {
        margin-right: 8px;
        width: 18px;
    }

    .metric-value {
        font-size: 1.8rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
    }

    .trend {
        font-size: 0.9rem;
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .trend.up {
        background-color: rgba(39, 174, 96, 0.1);
        color: #27ae60;
    }

    .trend.down {
        background-color: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
    }

    /* Teknoloji Vitrini CSS */
    .tech-showcase .card-header {
        background: linear-gradient(135deg, #192e59 0%, #2d5286 100%);
        color: white;
        padding: 1rem 1.5rem;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
    }

    .tech-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
        gap: 1px;
        background-color: #edf2f7;
    }

    .tech-item {
        background-color: white;
        padding: 20px;
        display: flex;
        align-items: flex-start;
        gap: 20px;
        transition: all 0.3s ease;
    }

    .tech-item:hover {
        background-color: #f8fafc;
    }

    .tech-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: linear-gradient(135deg, #2d5286 0%, #4481d6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.6rem;
        box-shadow: 0 4px 10px rgba(45, 82, 134, 0.3);
    }

    .tech-content {
        flex: 1;
    }

    .tech-content h5 {
        color: #192e59;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .tech-content p {
        color: #505c75;
        font-size: 0.9rem;
        margin-bottom: 12px;
        line-height: 1.5;
    }

    .tech-info {
        display: flex;
        gap: 15px;
        font-size: 0.8rem;
    }

    .implementation {
        color: #192e59;
        font-weight: 600;
    }

    .roi {
        color: #27ae60;
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .tech-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Animasyonlu Banner CSS */
    .animated-banner {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        padding: 4rem 0;
        margin-bottom: 2.5rem;
        position: relative;
        overflow: hidden;
    }

    .banner-content {
        position: relative;
        z-index: 10;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .animated-text-container {
        max-width: 600px;
    }

    .text-animation {
        height: 60px;
        overflow: hidden;
        margin-bottom: 5px;
    }

    .text-animation .text-line {
        font-size: 3.5rem;
        font-weight: 800;
        color: white;
        display: block;
        height: 100%;
        animation: text-slide 8s infinite;
        letter-spacing: -1px;
        text-shadow: 2px 2px 0 rgba(0,0,0,0.1);
    }

    @keyframes text-slide {
        0% { transform: translateY(0); }
        25% { transform: translateY(-100%); }
        50% { transform: translateY(-200%); }
        75% { transform: translateY(-300%); }
        100% { transform: translateY(0); }
    }

    .banner-subtitle {
        font-size: 1.8rem;
        font-weight: 400;
        opacity: 0;
        animation: fade-in 1s forwards 0.5s;
    }

    .banner-action {
        margin-top: 20px;
        opacity: 0;
        animation: fade-in 1s forwards 1s;
    }

    .action-button {
        background: rgba(255,255,255,0.2);
        color: white;
        border: 2px solid rgba(255,255,255,0.4);
        padding: 12px 30px;
        border-radius: 30px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .action-button:hover {
        background: white;
        color: #1e3c72;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .banner-graphic {
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }

    .circle-animation {
        position: absolute;
        border-radius: 50%;
        opacity: 0.1;
        background: white;
    }

    .c1 {
        width: 300px;
        height: 300px;
        right: -50px;
        top: -100px;
        animation: pulse 8s infinite alternate;
    }

    .c2 {
        width: 200px;
        height: 200px;
        right: 100px;
        bottom: -50px;
        animation: pulse 6s infinite alternate-reverse;
    }

    .c3 {
        width: 150px;
        height: 150px;
        right: 250px;
        top: 50px;
        animation: pulse 10s infinite alternate;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.1; }
        50% { transform: scale(1.2); opacity: 0.2; }
        100% { transform: scale(1); opacity: 0.1; }
    }

    .icon-float {
        position: absolute;
        color: rgba(255,255,255,0.3);
        font-size: 2rem;
    }

    .icon-float:nth-child(4) {
        right: 150px;
        top: 50px;
        font-size: 3rem;
        animation: float 6s infinite ease-in-out;
    }

    .icon-float:nth-child(5) {
        right: 300px;
        bottom: 70px;
        font-size: 2.5rem;
        animation: float 8s infinite ease-in-out reverse;
    }

    .icon-float:nth-child(6) {
        right: 100px;
        top: 150px;
        font-size: 2rem;
        animation: float 10s infinite ease-in-out;
    }

    @keyframes float {
        0% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(10deg); }
        100% { transform: translateY(0) rotate(0deg); }
    }

    @keyframes fade-in {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .banner-content {
            flex-direction: column;
            align-items: flex-start;
        }

        .text-animation .text-line {
            font-size: 2.5rem;
        }

        .banner-subtitle {
            font-size: 1.4rem;
        }

        .c1, .c2, .c3 {
            display: none;
        }
    }


</style>

    <!-- Animasyonlu Banner Bölümü -->
    <div class="animated-banner">
        <div class="container">
            <div class="banner-content">
                <div class="animated-text-container">
                    <div class="text-animation">
                        <span class="text-line">Verimli</span>
                        <span class="text-line">Akıllı</span>
                        <span class="text-line">Entegre</span>
                        <span class="text-line">Güvenilir</span>
                    </div>
                    <h2 class="banner-subtitle">Depo Yönetimi Çözümü</h2>
                </div>

                <div class="banner-action">
                    <a href="#" class="action-button">
                        <span>Keşfet</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <div class="banner-graphic">
                <div class="circle-animation c1"></div>
                <div class="circle-animation c2"></div>
                <div class="circle-animation c3"></div>
                <i class="fas fa-box-open icon-float"></i>
                <i class="fas fa-barcode icon-float"></i>
                <i class="fas fa-chart-line icon-float"></i>
            </div>
        </div>
    </div>


    <!-- Sistem Hakkında Bilgilendirme Paneli -->
    <div class="container">
        <div class="enterprise-info-panel">
            <div class="enterprise-info-header">
                <h4><i class="fas fa-shield-alt me-2"></i>Kurumsal Depo Yönetim Platformu</h4>
            </div>
            <div class="enterprise-info-content">
                <div class="row align-items-center">
                    <div class="col-lg-8 enterprise-description">
                        <h5 class="enterprise-subtitle">İşletmeniz İçin Özelleştirilmiş Entegre Çözüm</h5>
                        <p class="enterprise-text">
                            Depo Yönetim Platformumuz, kurumsal düzeyde envanter optimizasyonu ve tedarik zinciri
                            yönetimi sunan kapsamlı bir çözümdür. ISO 27001 sertifikalı altyapımız ile verileriniz
                            güvenle korunurken, yapay zeka destekli analitik araçlarımız ile stok ve
                            operasyonel verimliliğinizi maksimize edebilirsiniz.
                        </p>

                        <div class="enterprise-features">
                            <div class="feature-row">
                                <div class="feature-box">
                                    <div class="feature-icon"><i class="fas fa-sync-alt"></i></div>
                                    <div class="feature-details">
                                        <h6>Gerçek Zamanlı İzleme</h6>
                                        <p>Anlık stok bildirimleri ve otomatik raporlama sistemleri</p>
                                    </div>
                                </div>
                                <div class="feature-box">
                                    <div class="feature-icon"><i class="fas fa-cubes"></i></div>
                                    <div class="feature-details">
                                        <h6>Akıllı Envanter Yönetimi</h6>
                                        <p>Tahmine dayalı stok planlaması ve optimizasyon algoritmaları</p>
                                    </div>
                                </div>
                            </div>
                            <div class="feature-row">
                                <div class="feature-box">
                                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                                    <div class="feature-details">
                                        <h6>Gelişmiş Analitik</h6>
                                        <p>Öngörüsel analiz ve özelleştirilebilir KPI takip sistemleri</p>
                                    </div>
                                </div>
                                <div class="feature-box">
                                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                                    <div class="feature-details">
                                        <h6>Kurumsal Güvenlik</h6>
                                        <p>Çok katmanlı erişim kontrolü ve veri şifreleme protokolleri</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="enterprise-metrics">
                            <div class="enterprise-certification">
                                <div class="cert-badges">
                                    <div class="cert-badge iso">
                                        <i class="fas fa-certificate"></i>
                                        <span>ISO 27001</span>
                                    </div>
                                    <div class="cert-badge gdpr">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>KVKK Uyumlu</span>
                                    </div>
                                </div>
                                <div class="cert-description">
                                    Uluslararası güvenlik standartlarına uygun altyapı
                                </div>
                            </div>

                            <div class="enterprise-performance">
                                <div class="perf-circle">
                                    <svg viewBox="0 0 36 36" class="circular-chart">
                                        <path class="circle-bg" d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <path class="circle" stroke-dasharray="98, 100" d="M18 2.0845
                                        a 15.9155 15.9155 0 0 1 0 31.831
                                        a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <text x="18" y="21" class="percentage">98%</text>
                                    </svg>
                                    <div class="perf-label">Operasyonel Verimlilik</div>
                                </div>
                            </div>

                            <div class="enterprise-version">
                                <div class="version-details">
                                    <div class="version-badge">Enterprise Edition</div>
                                    <div class="version-number">v5.2.3 LTS</div>
                                    <div class="version-info">
                                        <span><i class="fas fa-clock"></i> Son Güncelleme: 15 Haziran 2023</span>
                                    </div>
                                    <div class="support-info">
                                        <i class="fas fa-headset"></i> 7/24 Kurumsal Destek
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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



        <!-- Ana İçerik Bölümü -->
        <div class="container mt-5">
            <div class="row">
                <!-- Sol Taraf - Rehber ve Dokümanlar -->
                <div class="col-lg-6">
                    <!-- Depo Yönetimi Rehberi -->
                    <div class="pro-card mb-4">
                        <div class="pro-card-header">
                            <div class="header-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="header-content">
                                <h4>Depo Yönetimi Rehberi</h4>
                                <span>Profesyonel operasyon yönetimi teknikleri</span>
                            </div>
                        </div>
                        <div class="pro-card-body">
                            <div class="document-section">
                                <div class="doc-item">
                                    <div class="doc-icon"><i class="fas fa-file-pdf"></i></div>
                                    <div class="doc-content">
                                        <h5>Stok Optimizasyon Stratejileri</h5>
                                        <p>Envanter maliyetlerinizi düşürürken stok devir hızını artırmanın yolları</p>
                                        <div class="doc-meta">
                                            <span><i class="fas fa-calendar-alt"></i> 15.03.2023</span>
                                            <span><i class="fas fa-download"></i> 1.2 MB</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="doc-item">
                                    <div class="doc-icon"><i class="fas fa-file-alt"></i></div>
                                    <div class="doc-content">
                                        <h5>ABC Analizi Uygulama Kılavuzu</h5>
                                        <p>Pareto prensibi ile stok önceliklendirme ve kaynakları verimli kullanma teknikleri</p>
                                        <div class="doc-meta">
                                            <span><i class="fas fa-calendar-alt"></i> 28.05.2023</span>
                                            <span><i class="fas fa-download"></i> 875 KB</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="doc-item">
                                    <div class="doc-icon"><i class="fas fa-file-powerpoint"></i></div>
                                    <div class="doc-content">
                                        <h5>JIT (Tam Zamanında) Tedarik Modeli</h5>
                                        <p>Minimum stok ile çalışma ve tedarik zinciri optimizasyonu metodolojisi</p>
                                        <div class="doc-meta">
                                            <span><i class="fas fa-calendar-alt"></i> 10.06.2023</span>
                                            <span><i class="fas fa-download"></i> 2.4 MB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pro-card-footer">
                            <div class="resource-count"><i class="fas fa-layer-group"></i> Toplam 14 doküman</div>
                        </div>
                    </div>

                    <!-- Veri Analizi ve Raporlama -->
                    <div class="pro-card mb-4">
                        <div class="pro-card-header">
                            <div class="header-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="header-content">
                                <h4>Veri Analizi ve Raporlama</h4>
                                <span>İş zekası araçları ve KPI takip sistemi</span>
                            </div>
                        </div>
                        <div class="pro-card-body">
                            <div class="kpi-metrics">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="analytics-widget">
                                            <div class="analytics-icon"><i class="fas fa-sync"></i></div>
                                            <div class="analytics-content">
                                                <h3>5.8</h3>
                                                <span>Stok Devir Hızı</span>
                                            </div>
                                            <div class="trend trend-up">+0.7</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="analytics-widget">
                                            <div class="analytics-icon"><i class="fas fa-clock"></i></div>
                                            <div class="analytics-content">
                                                <h3>1.2</h3>
                                                <span>Ortalama Bekleme (gün)</span>
                                            </div>
                                            <div class="trend trend-down">-0.3</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="analytics-widget">
                                            <div class="analytics-icon"><i class="fas fa-bullseye"></i></div>
                                            <div class="analytics-content">
                                                <h3>96.8%</h3>
                                                <span>Sipariş Karşılama Oranı</span>
                                            </div>
                                            <div class="trend trend-up">+1.2%</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="analytics-widget">
                                            <div class="analytics-icon"><i class="fas fa-boxes"></i></div>
                                            <div class="analytics-content">
                                                <h3>99.5%</h3>
                                                <span>Envanter Doğruluğu</span>
                                            </div>
                                            <div class="trend trend-up">+0.2%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pro-card-footer">
                            <div class="resource-count"><i class="fas fa-chart-bar"></i> 7 adet özel rapor mevcut</div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Taraf - Trendler ve Entegrasyonlar -->
                <div class="col-lg-6">
                    <!-- Sektörel Trendler ve İstatistikler -->
                    <div class="pro-card mb-4">
                        <div class="pro-card-header">
                            <div class="header-icon">
                                <i class="fas fa-industry"></i>
                            </div>
                            <div class="header-content">
                                <h4>Sektörel Trendler ve İstatistikler</h4>
                                <span>Tedarik zinciri ve lojistik sektörü iç görüler</span>
                            </div>
                        </div>
                        <div class="pro-card-body">
                            <div class="industry-trends">
                                <div class="chart-container">
                                    <canvas id="trendChart"></canvas>
                                </div>
                                <div class="trend-highlights mt-3">
                                    <div class="trend-item">
                                        <div class="trend-name">Otomatik Depolama Sistemleri</div>
                                        <div class="trend-progress">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: 68%"></div>
                                            </div>
                                        </div>
                                        <div class="trend-value">68%</div>
                                    </div>
                                    <div class="trend-item">
                                        <div class="trend-name">Blockchain Teknolojisi</div>
                                        <div class="trend-progress">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: 42%"></div>
                                            </div>
                                        </div>
                                        <div class="trend-value">42%</div>
                                    </div>
                                    <div class="trend-item">
                                        <div class="trend-name">Yapay Zeka & Makine Öğrenmesi</div>
                                        <div class="trend-progress">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 87%"></div>
                                            </div>
                                        </div>
                                        <div class="trend-value">87%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pro-card-footer">
                            <div class="trend-source">Kaynak: Tedarik Zinciri Yönetimi Derneği (2023 Q2 Raporu)</div>
                        </div>
                    </div>

                    <!-- Sistem Entegrasyonları -->
                    <div class="pro-card">
                        <div class="pro-card-header">
                            <div class="header-icon">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <div class="header-content">
                                <h4>Sistem Entegrasyonları</h4>
                                <span>Bağlantılı veri ekosistemi</span>
                            </div>
                        </div>
                        <div class="pro-card-body">
                            <div class="integration-systems">
                                <div class="integration-category">
                                    <h5>Kurumsal Sistemler</h5>
                                    <div class="integration-platforms">
                                        <div class="integration-item">
                                            <div class="integration-icon erp">
                                                <i class="fas fa-cogs"></i>
                                            </div>
                                            <div class="integration-details">
                                                <h6>ERP</h6>
                                                <div class="integration-status connected">
                                                    <i class="fas fa-check-circle"></i> Bağlı
                                                </div>
                                            </div>
                                        </div>
                                        <div class="integration-item">
                                            <div class="integration-icon crm">
                                                <i class="fas fa-user-friends"></i>
                                            </div>
                                            <div class="integration-details">
                                                <h6>CRM</h6>
                                                <div class="integration-status connected">
                                                    <i class="fas fa-check-circle"></i> Bağlı
                                                </div>
                                            </div>
                                        </div>
                                        <div class="integration-item">
                                            <div class="integration-icon accounting">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                            <div class="integration-details">
                                                <h6>Muhasebe</h6>
                                                <div class="integration-status connected">
                                                    <i class="fas fa-check-circle"></i> Bağlı
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="integration-category">
                                    <h5>E-Ticaret & Pazaryerleri</h5>
                                    <div class="integration-platforms">
                                        <div class="integration-item">
                                            <div class="integration-icon ecommerce">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                            <div class="integration-details">
                                                <h6>E-Ticaret</h6>
                                                <div class="integration-status connected">
                                                    <i class="fas fa-check-circle"></i> Bağlı (3)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="integration-item">
                                            <div class="integration-icon marketplace">
                                                <i class="fas fa-store"></i>
                                            </div>
                                            <div class="integration-details">
                                                <h6>Pazaryeri</h6>
                                                <div class="integration-status connected">
                                                    <i class="fas fa-check-circle"></i> Bağlı (4)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pro-card-footer">
                            <div class="integration-count"><i class="fas fa-plug"></i> 9 aktif entegrasyon bağlantısı</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ek JavaScript - Trend Grafiği için -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Sektörel Trendler Grafiği
                const trendCtx = document.getElementById('trendChart').getContext('2d');
                const trendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: ['2018', '2019', '2020', '2021', '2022', '2023'],
                        datasets: [
                            {
                                label: 'Otomasyon Kullanımı',
                                data: [24, 38, 45, 56, 65, 78],
                                borderColor: '#2d5286',
                                backgroundColor: 'rgba(45, 82, 134, 0.05)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Bulut Tabanlı Sistemler',
                                data: [15, 29, 40, 59, 68, 82],
                                borderColor: '#27ae60',
                                backgroundColor: 'rgba(39, 174, 96, 0.05)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    drawBorder: false,
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
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
            });
        </script>

    <!-- Tedarik Zinciri Performans Göstergeleri -->
    <div class="card mb-4 performance-dashboard">
        <div class="card-header d-flex justify-content-between align-items-center bg-gradient-primary text-white">
            <span><i class="fas fa-tachometer-alt me-2"></i>Tedarik Zinciri Performans Göstergeleri</span>
            <div>
                <button class="btn btn-sm btn-light" id="quarterly-view">Çeyrek Dönem</button>
                <button class="btn btn-sm btn-outline-light ms-2" id="yearly-view">Yıllık</button>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-lg-8">
                    <div class="chart-container" style="height: 320px;">
                        <canvas id="supplyChainChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="performance-metrics">
                        <div class="metric-box">
                            <div class="metric-title">
                                <i class="fas fa-dollar-sign"></i> Stok Taşıma Maliyeti
                            </div>
                            <div class="metric-value">
                                13.8%
                                <span class="trend down"><i class="fas fa-arrow-down"></i> 2.4%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 76%"></div>
                            </div>
                        </div>

                        <div class="metric-box">
                            <div class="metric-title">
                                <i class="fas fa-truck"></i> Zamanında Teslimat
                            </div>
                            <div class="metric-value">
                                94.5%
                                <span class="trend up"><i class="fas fa-arrow-up"></i> 1.7%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 94%"></div>
                            </div>
                        </div>

                        <div class="metric-box">
                            <div class="metric-title">
                                <i class="fas fa-check-double"></i> Sipariş Doğruluk Oranı
                            </div>
                            <div class="metric-value">
                                99.3%
                                <span class="trend up"><i class="fas fa-arrow-up"></i> 0.5%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 99%"></div>
                            </div>
                        </div>

                        <div class="metric-box">
                            <div class="metric-title">
                                <i class="fas fa-exchange-alt"></i> Stok Devir Hızı
                            </div>
                            <div class="metric-value">
                                6.2
                                <span class="trend up"><i class="fas fa-arrow-up"></i> 0.8</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 82%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
            <span><i class="fas fa-sync-alt"></i> Son güncelleme: 25.07.2023</span>
            <a href="#" class="link-primary">Detaylı Performans Raporu <i class="fas fa-chevron-right ms-1"></i></a>
        </div>
    </div>

    <!-- Akıllı Depo Yönetim Teknolojileri -->
    <div class="card tech-showcase">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-microchip me-2"></i>Akıllı Depo Yönetim Teknolojileri</span>
            <div class="badge bg-gradient-info text-white">Endüstri 4.0</div>
        </div>
        <div class="card-body p-0">
            <div class="tech-grid">
                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="tech-content">
                        <h5>Otonom Robot Sistemleri</h5>
                        <p>Lojistik süreçlerde %67'ye varan verimlilik artışı sağlayan akıllı robotik çözümler</p>
                        <div class="tech-info">
                            <span class="implementation">Uygulama Seviyesi: 75%</span>
                            <span class="roi">ROI: 24 ay</span>
                        </div>
                    </div>
                </div>

                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="tech-content">
                        <h5>IoT Tabanlı Stok Yönetimi</h5>
                        <p>Gerçek zamanlı stok takibi ve %99.8 envanter doğruluğuna ulaşan entegre sensör sistemleri</p>
                        <div class="tech-info">
                            <span class="implementation">Uygulama Seviyesi: 88%</span>
                            <span class="roi">ROI: 18 ay</span>
                        </div>
                    </div>
                </div>

                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="tech-content">
                        <h5>Yapay Zeka & Öngörücü Analitik</h5>
                        <p>Talep tahmin doğruluğunda %32 artış sağlayan makine öğrenimi algoritmaları</p>
                        <div class="tech-info">
                            <span class="implementation">Uygulama Seviyesi: 62%</span>
                            <span class="roi">ROI: 36 ay</span>
                        </div>
                    </div>
                </div>

                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-vr-cardboard"></i>
                    </div>
                    <div class="tech-content">
                        <h5>AR/VR Destekli Operasyonlar</h5>
                        <p>Eğitim süresini %45 kısaltan ve sipariş toplama hızını %30 artıran artırılmış gerçeklik sistemleri</p>
                        <div class="tech-info">
                            <span class="implementation">Uygulama Seviyesi: 45%</span>
                            <span class="roi">ROI: 30 ay</span>
                        </div>
                    </div>
                </div>

                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-digital-tachograph"></i>
                    </div>
                    <div class="tech-content">
                        <h5>Dijital İkiz Teknolojisi</h5>
                        <p>Depo tasarımı ve süreç optimizasyonunda %28 iyileştirme sağlayan simülasyon sistemi</p>
                        <div class="tech-info">
                            <span class="implementation">Uygulama Seviyesi: 39%</span>
                            <span class="roi">ROI: 42 ay</span>
                        </div>
                    </div>
                </div>

                <div class="tech-item">
                    <div class="tech-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="tech-content">
                        <h5>Blockchain Tabanlı Tedarik Zinciri</h5>
                        <p>İzlenebilirlik ve şeffaflıkta %100 artış, tedarik sürecinde %22 maliyet azaltımı</p>
                        <div class="tech-info">
                            <span class="implementation">Uygulama Seviyesi: 28%</span>
                            <span class="roi">ROI: 48 ay</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light text-center">
            <a href="#" class="btn btn-outline-primary btn-sm">Teknoloji Adaptasyon Planını Görüntüle</a>
        </div>
    </div>
    </div>
</div>

<!-- JavaScript Kodu -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tedarik Zinciri Performans Grafiği
        const supplyChainCtx = document.getElementById('supplyChainChart').getContext('2d');

        const quarterlyData = {
            labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz'],
            datasets: [
                {
                    label: 'Stok Devir Hızı',
                    data: [5.2, 5.4, 5.7, 5.9, 6.1, 6.0, 6.2],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Zamanında Teslimat (%)',
                    data: [91.2, 92.0, 92.8, 93.5, 94.0, 94.3, 94.5],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'percentage'
                },
                {
                    label: 'Taşıma Maliyeti (%)',
                    data: [16.8, 16.2, 15.9, 15.1, 14.5, 14.0, 13.8],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'percentage'
                }
            ]
        };

        const yearlyData = {
            labels: ['2018', '2019', '2020', '2021', '2022', '2023'],
            datasets: [
                {
                    label: 'Stok Devir Hızı',
                    data: [4.1, 4.4, 4.8, 5.3, 5.7, 6.2],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Zamanında Teslimat (%)',
                    data: [86.5, 88.3, 89.7, 91.2, 93.0, 94.5],
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'percentage'
                },
                {
                    label: 'Taşıma Maliyeti (%)',
                    data: [21.2, 19.8, 18.2, 17.1, 15.6, 13.8],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'percentage'
                }
            ]
        };

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: 4,
                    max: 7,
                    title: {
                        display: true,
                        text: 'Stok Devir Hızı'
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                percentage: {
                    beginAtZero: false,
                    position: 'right',
                    min: 0,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Yüzde (%)'
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        };

        let supplyChainChart = new Chart(supplyChainCtx, {
            type: 'line',
            data: quarterlyData,
            options: chartOptions
        });

        // Çeyrek dönem ve yıllık görünüm butonları
        document.getElementById('quarterly-view').addEventListener('click', function() {
            this.classList.add('btn-light');
            this.classList.remove('btn-outline-light');
            document.getElementById('yearly-view').classList.add('btn-outline-light');
            document.getElementById('yearly-view').classList.remove('btn-light');

            supplyChainChart.data = quarterlyData;
            supplyChainChart.update();
        });

        document.getElementById('yearly-view').addEventListener('click', function() {
            this.classList.add('btn-light');
            this.classList.remove('btn-outline-light');
            document.getElementById('quarterly-view').classList.add('btn-outline-light');
            document.getElementById('quarterly-view').classList.remove('btn-light');

            supplyChainChart.data = yearlyData;
            supplyChainChart.update();
        });
    });
</script>

<?php
echo $OUTPUT->footer();
?>