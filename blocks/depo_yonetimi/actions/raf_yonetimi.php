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

// CSS dosyası doğru şekilde yükleniyor
$PAGE->requires->css('/blocks/depo_yonetimi/assets/css/styles.css');

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

<!-- Modern CSS Kütüphaneleri -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">

<style>
    :root {
        --primary: #2563eb;
        --primary-light: #dbeafe;
        --primary-dark: #1e40af;
        --secondary: #475569;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --info: #06b6d4;
        --light: #f8fafc;
        --dark: #1e293b;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --border-radius: 0.5rem;
        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        color: var(--dark);
    }

    /* Modern Card Styles */
    .card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        overflow: hidden;
    }

    .card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-3px);
    }

    .card-header {
        background-color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .app-header {
        background-image: linear-gradient(135deg, var(--primary-dark), var(--primary));
        padding: 2.5rem 0;
        margin-bottom: 2rem;
        color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
    }

    /* Buttons */
    .btn {
        border-radius: 0.375rem;
        font-weight: 500;
        padding: 0.625rem 1rem;
        transition: var(--transition);
    }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .btn-primary:hover, .btn-primary:focus {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .btn-outline-primary {
        color: var(--primary);
        border-color: var(--primary);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary);
        color: white;
    }

    /* Stats Cards */
    .stat-card {
        position: relative;
        overflow: hidden;
        padding: 1rem;
        border-radius: var(--border-radius);
        transition: var(--transition);
        box-shadow: var(--shadow-md);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .stat-card .stat-icon {
        position: absolute;
        right: -15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 5rem;
        opacity: 0.1;
    }

    .stat-card .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
    }

    .stat-card .stat-label {
        text-transform: uppercase;
        font-size: 0.875rem;
        font-weight: 600;
        opacity: 0.8;
        margin-bottom: 0.75rem;
    }

    /* Table Styles */
    .table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .table th {
        background-color: var(--gray-100);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }

    .table-hover tbody tr {
        transition: var(--transition);
    }

    .table-hover tbody tr:hover {
        background-color: var(--primary-light);
    }

    /* Badge */
    .badge {
        padding: 0.35em 0.65em;
        font-weight: 600;
        border-radius: 0.25rem;
    }

    /* Location Tags */
    .location-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 0.75rem;
        background-color: var(--gray-100);
        border-radius: var(--border-radius);
        font-weight: 500;
        color: var(--secondary);
        border: 1px solid var(--gray-300);
        transition: var(--transition);
    }

    .location-tag i {
        margin-right: 0.5rem;
        color: var(--primary);
    }

    /* Forms */
    .form-control, .form-select {
        border-radius: var(--border-radius);
        padding: 0.625rem 0.75rem;
        border-color: var(--gray-300);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.1);
    }

    .input-group-text {
        background-color: var(--gray-100);
        border-color: var(--gray-300);
    }

    /* Modal */
    .modal-content {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        border-bottom: 1px solid var(--gray-200);
        background-color: var(--gray-100);
    }

    .modal-footer {
        border-top: 1px solid var(--gray-200);
    }

    /* Animasyonlar */
    .fade-in {
        animation: fadeIn 0.5s ease;
    }

    /* Tablo ve kart için modern stiller */
    #urunlerTable {
        font-size: 14px;
    }

    #urunlerTable thead th {
        letter-spacing: 0.5px;
        font-size: 12px;
        border-bottom: none;
    }

    #urunlerTable tbody tr {
        transition: all 0.2s;
    }

    #urunlerTable tbody tr:hover {
        background-color: rgba(37, 99, 235, 0.05);
    }

    .badge {
        font-weight: 500;
        border-radius: 50px;
    }

    /* Bootstrap 5.3 için subtle renklerin desteği */
    .bg-success-subtle {
        background-color: rgba(16, 185, 129, 0.1);
    }

    .bg-warning-subtle {
        background-color: rgba(245, 158, 11, 0.1);
    }

    .bg-danger-subtle {
        background-color: rgba(239, 68, 68, 0.1);
    }

    .bg-primary-subtle {
        background-color: rgba(37, 99, 235, 0.1);
    }

    .bg-info-subtle {
        background-color: rgba(6, 182, 212, 0.1);
    }

    .fs-7 {
        font-size: 0.85rem;
    }

    .edit-btn:hover {
        background-color: rgba(37, 99, 235, 0.1);
        box-shadow: 0 3px 5px rgba(37, 99, 235, 0.1);
        transform: translateY(-2px);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="container-fluid py-4">
    <!-- Ana Başlık -->
    <div class="app-header mb-5 fade-in animate__animated animate__fadeIn">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="d-flex justify-content-center align-items-center bg-white rounded-circle p-3" style="width: 70px; height: 70px">
                        <i class="bx bx-layer text-primary" style="font-size: 36px"></i>
                    </div>
                </div>
                <div class="col">
                    <h1 class="display-6 fw-bold mb-0"><?php echo htmlspecialchars($depo->name); ?></h1>
                    <p class="lead mb-0 opacity-75">Raf ve Bölüm Yönetim Paneli</p>
                </div>
                <div class="col-auto">
                    <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-light">
                        <i class="bx bx-arrow-back me-2"></i>Depoya Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistikler -->
    <div class="row mb-4">
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2">Toplam Ürün</h6>
                            <h2 class="mb-0 fw-bold"><?php echo count($urunler); ?></h2>
                            <small>Bu depodaki toplam ürün sayısı</small>
                        </div>
                        <div>
                            <i class="fas fa-box-open fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2">Bölümler</h6>
                            <h2 class="mb-0 fw-bold"><?php echo count($tum_bolumler); ?></h2>
                            <small>Farklı bölüm sayısı</small>
                        </div>
                        <div>
                            <i class="fas fa-th-large fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-2">Raflar</h6>
                            <h2 class="mb-0 fw-bold"><?php echo count($tum_raflar); ?></h2>
                            <small>Farklı raf sayısı</small>
                        </div>
                        <div>
                            <i class="fas fa-layer-group fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card mb-5 animate__animated animate__fadeIn" style="animation-delay: 0.2s">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="bx bx-filter-alt text-primary me-2"></i>
                <h5 class="mb-0">Gelişmiş Filtreleme</h5>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bx bx-chevron-down"></i>
            </button>
        </div>
        <div class="card-body collapse show" id="filterCollapse">
            <form method="get" id="filterForm" class="row g-3">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">

                <div class="col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Ürün ara..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <div class="col-md-6 col-lg-2">
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

                <div class="col-md-6 col-lg-2">
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

                <div class="col-md-6 col-lg-2">
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

                <div class="col-md-6 col-lg-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bx bx-filter me-1"></i> Filtrele
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Ana İçerik -->
    <div class="card shadow-sm rounded-3 border-0 animate__animated animate__fadeIn" style="animation-delay: 0.3s">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                        <i class="bx bx-package text-primary" style="font-size: 24px"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Ürün Konumları</h5>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-light me-2" id="refreshTable" title="Tabloyu Yenile">
                        <i class="bx bx-refresh me-1"></i> Yenile
                    </button>
                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]); ?>"
                       class="btn btn-sm btn-primary">
                        <i class="bx bx-plus me-1"></i> Yeni Ürün Ekle
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($urunler)): ?>
                <div class="p-5 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/6598/6598519.png" alt="Boş Depo" style="max-width: 120px; opacity: 0.3">
                    <h4 class="mt-3">Bu depoda henüz ürün bulunmuyor</h4>
                    <p class="text-muted">veya filtrelere uygun ürün yok</p>
                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]); ?>"
                       class="btn btn-primary mt-3">
                        <i class="bx bx-plus me-2"></i> Yeni Ürün Ekle
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle" id="urunlerTable">
                        <thead class="bg-light">
                        <tr>
                            <th class="py-3 text-uppercase text-secondary fw-semibold fs-7 px-4" width="30%">Ürün Adı</th>
                            <th class="py-3 text-uppercase text-secondary fw-semibold fs-7" width="15%">Kategori</th>
                            <th class="py-3 text-uppercase text-secondary fw-semibold fs-7" width="10%">Stok</th>
                            <th class="py-3 text-uppercase text-secondary fw-semibold fs-7" width="20%">Bölüm</th>
                            <th class="py-3 text-uppercase text-secondary fw-semibold fs-7" width="20%">Raf</th>
                            <th class="py-3 text-uppercase text-secondary fw-semibold fs-7 text-end pe-4" width="5%">İşlem</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($urunler as $index => $urun): ?>
                            <tr class="border-bottom" data-id="<?php echo $urun->id; ?>">
                                <td class="ps-4 py-3">
                                    <div class="fw-medium"><?php echo htmlspecialchars($urun->name); ?></div>
                                    <?php if (!empty($urun->barkod)): ?>
                                        <div class="small text-muted">
                                            <i class="bx bx-barcode-reader me-1"></i>
                                            <?php echo htmlspecialchars($urun->barkod); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <?php if (!empty($urun->kategori_adi)): ?>
                                        <div class="badge rounded-pill bg-light text-dark border px-3 py-2 fw-normal">
                                            <i class="bx bx-category text-primary me-1"></i>
                                            <?php echo htmlspecialchars($urun->kategori_adi); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Belirtilmemiş</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <?php if ($urun->adet > 10): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2 fw-medium">
                                        <i class="bx bx-check me-1"></i><?php echo $urun->adet; ?> adet
                                    </span>
                                    <?php elseif ($urun->adet > 0): ?>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2 fw-medium">
                                        <i class="bx bx-error me-1"></i><?php echo $urun->adet; ?> adet
                                    </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2 fw-medium">
                                        <i class="bx bx-x me-1"></i><?php echo $urun->adet; ?> adet
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="bolum-cell py-3">
                                    <?php if (!empty($urun->bolum)): ?>
                                        <div class="badge rounded-pill bg-primary-subtle text-primary border px-3 py-2 fw-normal">
                                            <i class="bx bx-cabinet me-1"></i>
                                            <?php echo htmlspecialchars($urun->bolum); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Belirtilmemiş</span>
                                    <?php endif; ?>
                                </td>
                                <td class="raf-cell py-3">
                                    <?php if (!empty($urun->raf)): ?>
                                        <div class="badge rounded-pill bg-info-subtle text-info border px-3 py-2 fw-normal">
                                            <i class="bx bx-server me-1"></i>
                                            <?php echo htmlspecialchars($urun->raf); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Belirtilmemiş</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4 py-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-pill px-3 edit-btn">
                                        <i class="bx bx-edit me-1"></i> Düzenle
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
</div>

<!-- Düzenleme Modalı -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bx bx-edit me-2"></i>Raf ve Bölüm Düzenle
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_urun_id" name="urunid">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                    <div class="mb-4">
                        <label for="edit_urun_adi" class="form-label text-muted small text-uppercase fw-semibold">Ürün Adı</label>
                        <input type="text" class="form-control form-control-lg" id="edit_urun_adi" disabled>
                    </div>

                    <div class="mb-4">
                        <label for="edit_bolum" class="form-label text-muted small text-uppercase fw-semibold">Bölüm</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="bx bx-cabinet text-primary"></i></span>
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
                    </div>

                    <div class="mb-4">
                        <label for="edit_raf" class="form-label text-muted small text-uppercase fw-semibold">Raf</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="bx bx-server text-primary"></i></span>
                            <select class="form-select" id="edit_raf" name="raf">
                                <option value="">-- Önce Bölüm Seçin --</option>
                            </select>
                        </div>
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

    <!-- Bootstrap Modal ve SweetAlert için gerekli JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bootstrap Modal nesnesini oluşturma
            let editModal;
            if (typeof bootstrap !== 'undefined') {
                editModal = new bootstrap.Modal(document.getElementById('editModal'));
            } else {
                console.error('Bootstrap kütüphanesi yüklenemedi.');
            }

            const editBolumSelect = document.getElementById("edit_bolum");
            const editRafSelect = document.getElementById("edit_raf");
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
                    const bolumCell = row.querySelector('.bolum-cell');
                    const rafCell = row.querySelector('.raf-cell');

                    // Bölüm ve raf değerlerini al
                    let bolum = '-';
                    let raf = '-';

                    if (bolumCell) {
                        const bolumSpan = bolumCell.querySelector('span.raf-badge');
                        bolum = bolumSpan ? bolumSpan.textContent.trim() : '';
                    }

                    if (rafCell) {
                        const rafSpan = rafCell.querySelector('span.raf-badge');
                        raf = rafSpan ? rafSpan.textContent.trim() : '';
                    }

                    if (bolum === '-') bolum = '';
                    if (raf === '-') raf = '';

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
                    if (editModal) {
                        editModal.show();
                    } else {
                        alert('Modal açılamadı. Sayfayı yenileyin veya tarayıcınızı güncelleyin.');
                    }
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
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Başarılı!',
                                        text: 'Raf bilgileri güncellendi.',
                                        confirmButtonColor: '#3e64ff'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    alert('Başarılı! Raf bilgileri güncellendi.');
                                    location.reload();
                                }
                            } else {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Hata!',
                                        text: data.message || 'Bir hata oluştu.',
                                        confirmButtonColor: '#3e64ff'
                                    });
                                } else {
                                    alert('Hata: ' + (data.message || 'Bir hata oluştu.'));
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Hata:', error);
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Bağlantı Hatası!',
                                    text: 'Sunucuyla bağlantı kurulamadı.',
                                    confirmButtonColor: '#3e64ff'
                                });
                            } else {
                                alert('Bağlantı Hatası! Sunucuyla bağlantı kurulamadı.');
                            }
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