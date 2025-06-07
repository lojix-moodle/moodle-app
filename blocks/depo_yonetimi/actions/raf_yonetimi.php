<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$urunid = optional_param('urunid', 0, PARAM_INT);
$bolum = optional_param('bolum', '', PARAM_TEXT);
$raf = optional_param('raf', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Raf Yönetimi');
$PAGE->set_heading('Raf Yönetimi');

// CSS ve JS ekle
$PAGE->requires->css_theme_url('/blocks/depo_yonetimi/assets/css/styles.css');
$PAGE->requires->js_call_amd('block_depo_yonetimi/raf_yonetimi', 'init');

// Depo bilgisini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Ürün filtreleme parametreleri
$filter_raf = optional_param('filter_raf', '', PARAM_TEXT);
$filter_bolum = optional_param('filter_bolum', '', PARAM_TEXT);
$filter_kategori = optional_param('filter_kategori', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

// AJAX İşlemleri
if ($action === 'update' && confirm_sesskey()) {
    $response = new stdClass();

    try {
        $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);

        if (!$urun) {
            throw new Exception('Ürün bulunamadı.');
        }

        $urun->raf = $raf;
        $urun->bolum = $bolum;

        $DB->update_record('block_depo_yonetimi_urunler', $urun);

        $response->status = 'success';
        $response->message = 'Raf bilgisi güncellendi.';
    } catch (Exception $e) {
        $response->status = 'error';
        $response->message = $e->getMessage();
    }

    echo json_encode($response);
    die();
}

// Filtreleme ve arama için temel sorgu hazırlama
$params = ['depoid' => $depoid];
$sql_where = "depoid = :depoid";

if (!empty($filter_raf)) {
    $sql_where .= " AND raf = :raf";
    $params['raf'] = $filter_raf;
}

if (!empty($filter_bolum)) {
    $sql_where .= " AND bolum = :bolum";
    $params['bolum'] = $filter_bolum;
}

if (!empty($filter_kategori)) {
    $sql_where .= " AND kategoriid = :kategoriid";
    $params['kategoriid'] = $filter_kategori;
}

if (!empty($search)) {
    $sql_where .= " AND " . $DB->sql_like('name', ':search', false);
    $params['search'] = '%' . $search . '%';
}

// Ürünleri getir
$sql = "SELECT u.*, k.name as kategori_adi
        FROM {block_depo_yonetimi_urunler} u
        LEFT JOIN {block_depo_yonetimi_kategoriler} k ON u.kategoriid = k.id
        WHERE $sql_where
        ORDER BY u.name ASC";

$urunler = $DB->get_records_sql($sql, $params);

// Mevcut tüm rafları ve bölümleri al (filtreleme için)
$tum_raflar = $DB->get_records_sql(
    "SELECT DISTINCT raf FROM {block_depo_yonetimi_urunler}
     WHERE depoid = :depoid AND raf IS NOT NULL AND raf != ''",
    ['depoid' => $depoid]
);

$tum_bolumler = $DB->get_records_sql(
    "SELECT DISTINCT bolum FROM {block_depo_yonetimi_urunler}
     WHERE depoid = :depoid AND bolum IS NOT NULL AND bolum != ''",
    ['depoid' => $depoid]
);

// Kategorileri al
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler');

// Sayfa çıktısı başlat
echo $OUTPUT->header();
?>

<!-- Harici CSS Kütüphaneleri -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

<style>
    :root {
        --primary: #4361ee;
        --primary-light: rgba(67, 97, 238, 0.1);
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --info: #4895ef;
        --warning: #f72585;
        --danger: #f94144;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --transition-speed: 0.3s;
        --border-radius: 0.85rem;
        --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    /* Global Animations */
    @keyframes slideInUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(67, 97, 238, 0); }
        100% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0); }
    }

    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }

    body {
        background-color: #f7f9fc;
        color: #333;
    }

    /* Layout */
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .section-header {
        margin-bottom: 2rem;
        position: relative;
    }

    .section-header h2 {
        font-weight: 700;
        color: var(--dark);
        position: relative;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .section-header h2:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -10px;
        width: 50px;
        height: 3px;
        background: linear-gradient(to right, var(--primary), var(--info));
        transition: width 0.3s ease;
    }

    .section-header:hover h2:after {
        width: 100px;
    }

    /* Cards */
    .card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: transform var(--transition-speed), box-shadow var(--transition-speed);
        overflow: hidden;
        margin-bottom: 2rem;
        background-color: white;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        background: linear-gradient(120deg, var(--primary), var(--secondary));
        color: white;
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        border: none;
    }

    .card-header .title-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
    }

    /* Dashboard Overview */
    .overview-card {
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .overview-card .icon-background {
        position: absolute;
        top: -20px;
        right: -20px;
        font-size: 9rem;
        opacity: 0.05;
        transform: rotate(15deg);
    }

    .overview-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        color: var(--dark);
    }

    /* Badges and Labels */
    .badge-special {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        letter-spacing: 0.5px;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.5s ease;
    }

    .badge-blue {
        background: linear-gradient(to right, #4cc9f0, #4895ef);
        color: white;
    }

    .badge-purple {
        background: linear-gradient(to right, #8338ec, #3a0ca3);
        color: white;
    }

    .badge-pink {
        background: linear-gradient(to right, #f72585, #b5179e);
        color: white;
    }

    .badge-green {
        background: linear-gradient(to right, #80ed99, #57cc99);
        color: white;
    }

    /* Data Tables */
    .datatable-wrapper {
        background: #fff;
        padding: 1rem;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    #urun-table {
        width: 100% !important;
        border-collapse: separate;
        border-spacing: 0;
    }

    #urun-table thead th {
        background-color: rgba(67, 97, 238, 0.05);
        color: var(--dark);
        font-weight: 600;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    #urun-table tbody tr {
        transition: all 0.2s;
    }

    #urun-table tbody tr:hover {
        background-color: var(--primary-light);
        transform: scale(1.01);
    }

    #urun-table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }

    /* Buttons */
    .btn {
        border-radius: 50px;
        padding: 0.6rem 1.5rem;
        font-weight: 500;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
    }

    .btn::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: -100%;
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
        transition: all 0.6s;
    }

    .btn:hover::after {
        left: 100%;
    }

    .btn-primary {
        background: linear-gradient(to right, var(--primary), var(--secondary));
        border: none;
        box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
    }

    .btn-info {
        background: linear-gradient(to right, var(--info), var(--success));
        border: none;
        box-shadow: 0 4px 15px rgba(72, 149, 239, 0.4);
    }

    .btn-danger {
        background: linear-gradient(to right, var(--danger), var(--warning));
        border: none;
        color: white;
    }

    .btn-outline-primary {
        border: 2px solid var(--primary);
        color: var(--primary);
        background: transparent;
    }

    .btn-outline-primary:hover {
        background: var(--primary);
        color: white;
    }

    /* Floating Action Button */
    .floating-action-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(to right, var(--primary), var(--secondary));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 20px rgba(67, 97, 238, 0.4);
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 999;
        animation: pulse 2s infinite;
    }

    .floating-action-btn:hover {
        transform: scale(1.1) rotate(90deg);
    }

    /* Form Elements */
    .form-control, .form-select {
        border: 1px solid #e9ecef;
        padding: 0.8rem 1.2rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: none;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }

    /* Search Box Animation */
    .search-container {
        position: relative;
        transition: all 0.3s ease;
    }

    .search-container .form-control {
        padding-left: 45px;
        transition: all 0.3s ease;
    }

    .search-container .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
        transition: all 0.3s ease;
    }

    .search-container .form-control:focus {
        padding-left: 50px;
    }

    .search-container .form-control:focus + .search-icon {
        color: var(--primary);
        animation: pulse 1s;
    }

    /* Modal Animations */
    .modal-content {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2);
        animation: slideInUp 0.4s ease;
    }

    .modal-header {
        background: linear-gradient(120deg, var(--primary), var(--secondary));
        color: white;
        border-top-left-radius: var(--border-radius);
        border-top-right-radius: var(--border-radius);
        border: none;
    }

    /* Loading Animation */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        visibility: hidden;
        opacity: 0;
        transition: all 0.5s ease;
    }

    .loading-overlay.active {
        visibility: visible;
        opacity: 1;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid var(--primary-light);
        border-top: 5px solid var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Drag and Drop Interface */
    .drag-container {
        border: 2px dashed #ddd;
        border-radius: var(--border-radius);
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .drag-container:hover {
        border-color: var(--primary);
        background-color: var(--primary-light);
    }

    .drag-container i {
        font-size: 3rem;
        color: #ccc;
        transition: all 0.3s ease;
    }

    .drag-container:hover i {
        color: var(--primary);
        transform: translateY(-5px);
    }

    /* Stats Cards */
    .stat-card {
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        transition: all 0.3s;
        animation: float 5s ease-in-out infinite;
    }

    .stat-card.blue {
        background: linear-gradient(45deg, #4cc9f0, #4895ef);
        color: white;
    }

    .stat-card.purple {
        background: linear-gradient(45deg, #8338ec, #3a0ca3);
        color: white;
    }

    .stat-card.pink {
        background: linear-gradient(45deg, #f72585, #b5179e);
        color: white;
    }

    .stat-card .stat-icon {
        position: absolute;
        top: -20px;
        right: -20px;
        font-size: 8rem;
        opacity: 0.1;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    /* Custom animations */
    .hover-float:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
    }

    .glowing {
        box-shadow: 0 0 10px rgba(67, 97, 238, 0.5);
        animation: glow 2s infinite alternate;
    }

    @keyframes glow {
        from { box-shadow: 0 0 5px rgba(67, 97, 238, 0.5); }
        to { box-shadow: 0 0 20px rgba(67, 97, 238, 0.8); }
    }

    /* Status badges */
    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .status-dot.success { background-color: #4cc9f0; }
    .status-dot.warning { background-color: #f72585; }
    .status-dot.danger { background-color: #f94144; }

    /* Ripple effect */
    .ripple {
        position: relative;
        overflow: hidden;
    }

    .ripple::after {
        content: "";
        display: block;
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        pointer-events: none;
        background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
        background-repeat: no-repeat;
        background-position: 50%;
        transform: scale(10, 10);
        opacity: 0;
        transition: transform .5s, opacity 1s;
    }

    .ripple:active::after {
        transform: scale(0, 0);
        opacity: .3;
        transition: 0s;
    }

    /* Toast Notification */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1060;
    }

    .custom-toast {
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px;
        animation: slideInUp 0.3s;
    }

    .toast-header {
        padding: 10px 15px;
        color: white;
    }

    .toast-header.success { background: linear-gradient(to right, #80ed99, #57cc99); }
    .toast-header.error { background: linear-gradient(to right, #f72585, #b5179e); }
    .toast-header.info { background: linear-gradient(to right, #4cc9f0, #4895ef); }

    .toast-body {
        padding: 15px;
        font-size: 0.9rem;
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<!-- Toast Notifications Container -->
<div class="toast-container" id="toastContainer"></div>

<div class="container-fluid">
    <!-- Ana Başlık -->
    <div class="section-header mb-5 animate__animated animate__fadeInDown">
        <div class="d-flex align-items-center">
            <div class="title-icon bg-primary text-white rounded-circle p-3 me-3">
                <i class="fas fa-layer-group fa-2x"></i>
            </div>
            <div>
                <h1 class="mb-0"><?php echo htmlspecialchars($depo->name); ?></h1>
                <p class="text-muted mb-0">Raf ve Bölüm Yönetim Paneli</p>
            </div>
        </div>
    </div>

    <!-- İstatistikler -->
    <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
        <div class="col-md-4 mb-4">
            <div class="stat-card blue shadow h-100" data-tilt data-tilt-max="5" data-tilt-speed="400">
                <i class="fas fa-boxes stat-icon"></i>
                <h3>Toplam Ürün</h3>
                <div class="stat-value"><?php echo count($urunler); ?></div>
                <p class="mb-0">Bu depodaki toplam ürün sayısı</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card purple shadow h-100" data-tilt data-tilt-max="5" data-tilt-speed="400">
                <i class="fas fa-th-large stat-icon"></i>
                <h3>Bölümler</h3>
                <div class="stat-value"><?php echo count($tum_bolumler); ?></div>
                <p class="mb-0">Farklı bölüm sayısı</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card pink shadow h-100" data-tilt data-tilt-max="5" data-tilt-speed="400">
                <i class="fas fa-layer-group stat-icon"></i>
                <h3>Raflar</h3>
                <div class="stat-value"><?php echo count($tum_raflar); ?></div>
                <p class="mb-0">Farklı raf sayısı</p>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card-header d-flex align-items-center">
            <div class="title-icon">
                <i class="fas fa-filter"></i>
            </div>
            <span>Gelişmiş Filtreleme</span>
        </div>
        <div class="card-body">
            <form method="get" id="filterForm" class="row g-3">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">

                <div class="col-md-4">
                    <div class="search-container">
                        <input type="text" class="form-control" placeholder="Ürün ara..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="filter_bolum">
                        <option value="">Tüm Bölümler</option>
                        <?php foreach ($tum_bolumler as $bolum_item): ?>
                            <option value="<?php echo htmlspecialchars($bolum_item->bolum); ?>"
                                <?php echo $filter_bolum === $bolum_item->bolum ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bolum_item->bolum); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="filter_raf">
                        <option value="">Tüm Raflar</option>
                        <?php foreach ($tum_raflar as $raf_item): ?>
                            <option value="<?php echo htmlspecialchars($raf_item->raf); ?>"
                                <?php echo $filter_raf === $raf_item->raf ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($raf_item->raf); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="filter_kategori">
                        <option value="0">Tüm Kategoriler</option>
                        <?php foreach ($kategoriler as $kategori): ?>
                            <option value="<?php echo $kategori->id; ?>"
                                <?php echo $filter_kategori == $kategori->id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kategori->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary ripple">
                            <i class="fas fa-filter me-1"></i> Filtrele
                        </button>
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>"
                           class="btn btn-outline-primary ripple">
                            <i class="fas fa-times me-1"></i> Sıfırla
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Ana İçerik -->
    <div class="card" data-aos="fade-up" data-aos-delay="300">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="title-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <span>Ürün Konumları</span>
            </div>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-info me-2" id="refreshTable">
                    <i class="fas fa-sync-alt me-2"></i> Yenile
                </button>
                <button id="topluKaydet" class="btn btn-sm btn-success d-none">
                    <i class="fas fa-save me-2"></i> Değişiklikleri Kaydet
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($urunler)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Bu depoda henüz ürün bulunmuyor veya filtrelere uygun ürün yok.
                </div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-hover table-raf align-middle">
                        <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Ürün Adı</th>
                            <th width="15%">Kategori</th>
                            <th width="10%">Stok</th>
                            <th width="17%">Bölüm</th>
                            <th width="18%">Raf</th>
                            <th width="10%">İşlem</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($urunler as $index => $urun): ?>
                            <tr data-id="<?php echo $urun->id; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($urun->name); ?></div>
                                </td>
                                <td>
                                    <?php echo !empty($urun->kategori_adi) ? htmlspecialchars($urun->kategori_adi) : '-'; ?>
                                </td>
                                <td>
                            <span class="badge bg-<?php echo $urun->adet > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $urun->adet; ?> adet
                            </span>
                                </td>
                                <td class="bolum-cell">
                                    <?php if (!empty($urun->bolum)): ?>
                                        <span class="raf-badge bg-light text-dark border">
                                    <?php echo htmlspecialchars($urun->bolum); ?>
                                </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="raf-cell">
                                    <?php if (!empty($urun->raf)): ?>
                                        <span class="raf-badge bg-light text-dark border">
                                    <?php echo htmlspecialchars($urun->raf); ?>
                                </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-btn">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Butonlar -->
    <div class="d-flex justify-content-between mt-4 mb-4">
        <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Depoya Geri Dön
        </a>
        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]); ?>"
           class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Yeni Ürün Ekle
        </a>
    </div>
</div>

    <!-- Düzenleme Modalı -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Raf ve Bölüm Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="edit_urun_id" name="urunid">
                        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                        <div class="mb-3">
                            <label for="edit_urun_adi" class="form-label">Ürün Adı</label>
                            <input type="text" class="form-control" id="edit_urun_adi" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="edit_bolum" class="form-label">Bölüm</label>
                            <select class="form-select" id="edit_bolum" name="bolum">
                                <option value="">-- Bölüm Seçin --</option>
                                <option value="Tişört">Tişört</option>
                                <option value="Pantolon">Pantolon</option>
                                <option value="Ayakkabı">Ayakkabı</option>
                                <option value="Gömlek">Gömlek</option>
                                <option value="Elbise">Elbise</option>
                                <option value="Ceket">Ceket</option>
                                <option value="Aksesuar">Aksesuar</option>
                                <option value="Çanta">Çanta</option>
                                <option value="İç Giyim">İç Giyim</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_raf" class="form-label">Raf</label>
                            <select class="form-select" id="edit_raf" name="raf">
                                <option value="">-- Önce Bölüm Seçin --</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="save-changes">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert ve Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bölüm ve raf seçimleri için
            const editBolumSelect = document.getElementById("edit_bolum");
            const editRafSelect = document.getElementById("edit_raf");
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const editForm = document.getElementById('editForm');
            const saveChangesBtn = document.getElementById('save-changes');
            const refreshBtn = document.getElementById('refreshTable');

            // Yenile butonu tıklandığında
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    location.reload();
                });
            }

            // Düzenleme butonu tıklandığında
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const urunId = row.dataset.id;
                    const urunAdi = row.querySelector('td:nth-child(2) div').textContent.trim();
                    const bolum = row.querySelector('.bolum-cell').textContent.trim() === '-' ?
                        '' : row.querySelector('.bolum-cell').textContent.trim();
                    const raf = row.querySelector('.raf-cell').textContent.trim() === '-' ?
                        '' : row.querySelector('.raf-cell').textContent.trim();

                    // Modal alanlarını doldur
                    document.getElementById('edit_urun_id').value = urunId;
                    document.getElementById('edit_urun_adi').value = urunAdi;

                    // Bölüm seçimini ayarla
                    for(let i = 0; i < editBolumSelect.options.length; i++) {
                        if(editBolumSelect.options[i].text === bolum) {
                            editBolumSelect.selectedIndex = i;
                            break;
                        }
                    }

                    // Raf seçeneklerini güncelle ve seçimi ayarla
                    updateRaflar.call(editBolumSelect, raf);

                    // Modal'ı göster
                    editModal.show();
                });
            });

            // Bölüm değiştiğinde rafları güncelle
            if (editBolumSelect) {
                editBolumSelect.addEventListener("change", updateRaflar);
            }

            // Değişikliği kaydet butonu
            if (saveChangesBtn) {
                saveChangesBtn.addEventListener('click', function() {
                    const formData = new FormData(editForm);
                    formData.append('action', 'update');
                    formData.append('depoid', <?php echo $depoid; ?>);

                    // AJAX ile verileri gönder
                    fetch('<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Başarılı!',
                                    text: 'Raf bilgileri güncellendi.',
                                    confirmButtonColor: '#3e64ff'
                                }).then(() => {
                                    location.reload(); // Sayfayı yenile
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Hata!',
                                    text: data.message || 'Bir hata oluştu.',
                                    confirmButtonColor: '#3e64ff'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Hata:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Bağlantı Hatası!',
                                text: 'Sunucuyla bağlantı kurulamadı.',
                                confirmButtonColor: '#3e64ff'
                            });
                        });
                });
            }

            // Rafları güncelleme fonksiyonu
            function updateRaflar(selectedRaf) {
                const bolum = this.value;

                // Raf seçimini temizle
                editRafSelect.innerHTML = '<option value="">-- Raf Seçin --</option>';

                // Bölüme göre rafları ayarla
                if (bolum === "Tişört" || bolum === "Gömlek") {
                    addRafOption(editRafSelect, "A1 Rafı");
                    addRafOption(editRafSelect, "A2 Rafı");
                    addRafOption(editRafSelect, "A3 Rafı");
                } else if (bolum === "Pantolon") {
                    addRafOption(editRafSelect, "B1 Rafı");
                    addRafOption(editRafSelect, "B2 Rafı");
                    addRafOption(editRafSelect, "B3 Rafı");
                } else if (bolum === "Ayakkabı") {
                    addRafOption(editRafSelect, "C1 Rafı");
                    addRafOption(editRafSelect, "C2 Rafı");
                    addRafOption(editRafSelect, "C3 Rafı");
                    addRafOption(editRafSelect, "C4 Rafı");
                } else if (bolum === "Aksesuar" || bolum === "Çanta") {
                    addRafOption(editRafSelect, "D1 Rafı");
                    addRafOption(editRafSelect, "D2 Rafı");
                } else if (bolum) {
                    // Diğer tüm bölümler için
                    addRafOption(editRafSelect, "E1 Rafı");
                    addRafOption(editRafSelect, "E2 Rafı");
                    addRafOption(editRafSelect, "E3 Rafı");
                }

                // Eğer önceden seçilmiş bir raf varsa onu seç
                if (selectedRaf) {
                    for(let i = 0; i < editRafSelect.options.length; i++) {
                        if(editRafSelect.options[i].text === selectedRaf) {
                            editRafSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }

            // Raf seçeneği ekleme yardımcı fonksiyonu
            function addRafOption(select, value) {
                const option = document.createElement("option");
                option.value = value;
                option.text = value;
                select.appendChild(option);
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>