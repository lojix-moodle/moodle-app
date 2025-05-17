<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Listesi');
$PAGE->set_heading('Kategori Listesi');

$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler', null, 'name ASC');

echo $OUTPUT->header();
?>

    <style>
        .form-control, .form-select {
            border-color: #dee2e6 !important;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0f6cbf;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .card {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .btn {
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }

        .search-box {
            max-width: 300px;
        }

        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-folder me-2"></i>
                            <h5 class="mb-0">Kategoriler</h5>
                        </div>
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'); ?>"
                           class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Yeni Kategori
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($kategoriler)): ?>
                            <div class="p-3">
                                <div class="input-group search-box mb-3">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="searchInput" class="form-control" placeholder="Kategori ara...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="kategoriTable">
                                    <thead>
                                    <tr>
                                        <th scope="col" class="border-0">
                                            <i class="fas fa-tag me-2"></i>Kategori Adı
                                        </th>
                                        <th scope="col" class="border-0 text-end">İşlemler</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <tr>
                                            <td class="align-middle">
                                                <?php echo htmlspecialchars($kategori->name); ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['kategoriid' => $kategori->id]); ?>"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', ['kategoriid' => $kategori->id]); ?>"
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Sil"
                                                       onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                                <p class="mb-1">Henüz kategori bulunmamaktadır.</p>
                                <p class="mb-3">Yeni kategori eklemek için yukarıdaki butonu kullanabilirsiniz.</p>
                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'); ?>"
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus-circle me-2"></i>Yeni Kategori Ekle
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center bg-light">
                        <small class="text-muted">Toplam kategori: <span id="totalCount"><?php echo count($kategoriler); ?></span></small>
                        <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Geri
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loadingOverlay = document.getElementById('loadingOverlay');
            var searchInput = document.getElementById('searchInput');
            var tableRows = document.querySelectorAll('#kategoriTable tbody tr');
            var totalCountEl = document.getElementById('totalCount');

            // Sayfa yüklendiğinde loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none';
            });

            // Tablo satırlarına hover efekti
            tableRows.forEach(function(row) {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });

            // İşlem butonlarına hover efekti
            var buttons = document.querySelectorAll('.btn-group .btn');
            buttons.forEach(function(btn) {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });

            // Arama fonksiyonu
            searchInput.addEventListener('keyup', function() {
                var searchTerm = this.value.toLowerCase();
                var visibleCount = 0;

                tableRows.forEach(function(row) {
                    var kategoriName = row.querySelector('td:first-child').textContent.toLowerCase();
                    if (kategoriName.indexOf(searchTerm) > -1) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                totalCountEl.textContent = visibleCount;
            });

            // Silme işlemi için onay kutusu
            var deleteButtons = document.querySelectorAll('a[title="Sil"]');
            deleteButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')) {
                        e.preventDefault();
                    } else {
                        loadingOverlay.style.display = 'flex';
                    }
                });
            });
        });
    </script>

<?php
echo $OUTPUT->footer();
?>