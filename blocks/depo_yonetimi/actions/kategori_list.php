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
        /* Genel stil ve sayfa ortalama için */
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0; /* isteğe bağlı arka plan */
        }

        .container {
            width: 90%;
            max-width: 1200px;
        }

        /* diğer stil ayarları burada kaldı... (kendi kodunuzdaki stil) */
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
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center; align-items: center;
        }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0f6cbf;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); }
        }
        /* diğer stiller... (kendi kodunuzdaki stil) */
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-folder me-2"></i>
                                <h5 class="mb-0">Kategoriler</h5>
                            </div>
                            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'); ?>"
                               class="btn btn-light btn-sm">
                                <i class="fas fa-plus-circle me-2"></i>Yeni Kategori
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($kategoriler)): ?>
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="input-group search-box" style="max-width:300px;">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" id="searchInput" class="form-control" placeholder="Kategori ara...">
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-sort me-1"></i>Sırala
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                            <li><a class="dropdown-item sort-option" href="#" data-sort="name-asc">İsim (A-Z)</a></li>
                                            <li><a class="dropdown-item sort-option" href="#" data-sort="name-desc">İsim (Z-A)</a></li>
                                            <li><a class="dropdown-item sort-option" href="#" data-sort="date-asc">Oluşturma (Eski-Yeni)</a></li>
                                            <li><a class="dropdown-item sort-option" href="#" data-sort="date-desc">Oluşturma (Yeni-Eski)</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="kategoriTable">
                                    <thead>
                                    <tr>
                                        <th scope="col" class="border-0">
                                            <i class="fas fa-tag me-2"></i>Kategori Adı
                                        </th>
                                        <th scope="col" class="border-0 text-center">Ürün Sayısı</th>
                                        <th scope="col" class="border-0 text-center">Oluşturulma Tarihi</th>
                                        <th scope="col" class="border-0 text-end">İşlemler</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($kategoriler as $kategori):
                                        $urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['kategoriid' => $kategori->id]);
                                        ?>
                                        <tr data-id="<?php echo $kategori->id; ?>" data-name="<?php echo htmlspecialchars($kategori->name); ?>" data-date="<?php echo $kategori->timecreated ?? 0; ?>">
                                            <td class="align-middle">
                                                <span class="category-name"><?php echo htmlspecialchars($kategori->name); ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge bg-<?php echo $urun_sayisi > 0 ? 'primary' : 'secondary'; ?> rounded-pill"><?php echo $urun_sayisi; ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php
                                                if (!empty($kategori->timecreated)) {
                                                    echo date('d.m.Y H:i', $kategori->timecreated);
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['id' => $kategori->id]); ?>"
                                                       class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', ['id' => $kategori->id, 'sesskey' => sesskey()]); ?>"
                                                       class="btn btn-sm btn-outline-danger delete-btn"
                                                       title="Sil"
                                                       data-id="<?php echo $kategori->id; ?>"
                                                       data-name="<?php echo htmlspecialchars($kategori->name); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3" id="pagination-container">
                                <div>
                                    <select id="page-size" class="form-select form-select-sm" style="width: auto;">
                                        <option value="10">10 / sayfa</option>
                                        <option value="25">25 / sayfa</option>
                                        <option value="50">50 / sayfa</option>
                                        <option value="100">100 / sayfa</option>
                                        <option value="all">Tümünü Göster</option>
                                    </select>
                                </div>
                                <nav aria-label="Sayfalama">
                                    <ul class="pagination pagination-sm"></ul>
                                </nav>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted animate-fade">
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
                        <small class="text-muted">Toplam kategori: <span id="totalCount"><?php echo count($kategoriler); ?></span> / <span id="displayedCount"><?php echo count($kategoriler); ?></span></small>
                        <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Geri
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Silme Onay Modalı -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Kategori Silme Onayı</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <p><i class="fas fa-exclamation-triangle text-warning me-2"></i> <span id="deleteModalText">Bu kategoriyi silmek istediğinizden emin misiniz?</span></p>
                    <p class="text-muted small">Bu işlem geri alınamaz ve kategoriye bağlı ürünler artık bir kategoriye ait olmayacaktır.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Evet, Sil</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Değişkenler
            var loadingOverlay = document.getElementById('loadingOverlay');
            var searchInput = document.getElementById('searchInput');
            var tableRows = document.querySelectorAll('#kategoriTable tbody tr');
            var totalCountEl = document.getElementById('totalCount');
            var displayedCountEl = document.getElementById('displayedCount');
            var pageSizeSelect = document.getElementById('page-size');
            var paginationContainer = document.querySelector('.pagination');
            var currentPage = 1;
            var pageSize = 10;
            var sortField = 'name';
            var sortDirection = 'asc';
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            var deleteModalText = document.getElementById('deleteModalText');
            var sortDropdown = document.getElementById('sortDropdown');

            // Sıralama ayarları
            var currentSort = { field: 'name', direction: 'asc' };

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
            document.querySelectorAll('.btn-group .btn').forEach(function(btn) {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });

            // Silme işlemleri
            document.querySelectorAll('.delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var kategoriId = this.getAttribute('data-id');
                    var kategoriName = this.getAttribute('data-name');

                    deleteModalText.textContent = '"' + kategoriName + '" kategorisini silmek istediğinizden emin misiniz?';
                    confirmDeleteBtn.href = this.href;

                    deleteModal.show();
                });
            });
            confirmDeleteBtn.addEventListener('click', function() {
                loadingOverlay.style.display = 'flex';
            });

            // Sıralama seçenekleri olayları
            document.querySelectorAll('.sort-option').forEach(function(option) {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    var sortVal = this.getAttribute('data-sort'); // örn: 'name-asc'
                    var parts = sortVal.split('-');
                    currentSort.field = parts[0]; // 'name' veya 'date'
                    currentSort.direction = parts[1]; // 'asc' veya 'desc'

                    // Dropdown başlığını güncelle
                    var dropdownBtn = document.querySelector('#sortDropdown');
                    dropdownBtn.innerHTML = '<i class="fas fa-sort me-1"></i>' + this.textContent;

                    // Sıralama yap
                    sortTable();
                    updateTable();
                });
            });

            // Page size değişimi
            document.getElementById('page-size').addEventListener('change', function() {
                var val = this.value;
                if (val === 'all') {
                    pageSize = tableRows.length;
                } else {
                    pageSize = parseInt(val);
                }
                currentPage = 1;
                updateTable();
            });

            // Arama
            document.getElementById('searchInput').addEventListener('keyup', function() {
                currentPage = 1;
                updateTable();
            });

            // Tabloyu güncelleyen fonksiyon
            function updateTable() {
                var searchTerm = document.getElementById('searchInput').value.toLowerCase();
                var visibleRows = [];

                // Filtrele
                tableRows.forEach(function(row) {
                    var name = row.querySelector('.category-name').textContent.toLowerCase();
                    if (name.indexOf(searchTerm) > -1) {
                        row.dataset.visible = 'true';
                        visibleRows.push(row);
                    } else {
                        row.dataset.visible = 'false';
                    }
                });

                // Sayfalama sınırları
                var totalItems = visibleRows.length;
                var startIdx = 0;
                var endIdx = totalItems;

                if (pageSize !== 'all' && pageSize < totalItems) {
                    startIdx = (currentPage - 1) * pageSize;
                    endIdx = Math.min(startIdx + pageSize, totalItems);
                }

                // Tüm satırları gizle
                tableRows.forEach(function(row) {
                    row.style.display = 'none';
                });

                // Geçerli sayfa satırlarını göster
                for (var i = startIdx; i < endIdx; i++) {
                    visibleRows[i].style.display = '';
                }

                // Güncelleme
                document.getElementById('totalCount').textContent = tableRows.length;
                document.getElementById('displayedCount').textContent = totalItems;

                // Sayfalama
                createPagination(totalItems);
            }

            // Sayfalama
            function createPagination(totalItems) {
                var container = document.getElementById('pagination-container');
                var ul = container.querySelector('.pagination');
                ul.innerHTML = '';

                if (pageSize === 'all' || totalItems <= pageSize) {
                    container.style.display = 'none';
                    return;
                } else {
                    container.style.display = 'flex';
                }

                var totalPages = Math.ceil(totalItems / pageSize);
                if (totalPages <= 1) {
                    container.style.display = 'none';
                    return;
                }

                // önceki
                if (currentPage > 1) {
                    ul.innerHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}"><span aria-hidden="true">«</span></a></li>`;
                }

                // sayfa numaraları
                var startPage = Math.max(1, currentPage - 2);
                var endPage = Math.min(totalPages, startPage + 4);

                for (var i = startPage; i <= endPage; i++) {
                    ul.innerHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }

                // sonraki
                if (currentPage < totalPages) {
                    ul.innerHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}"><span aria-hidden="true">»</span></a></li>`;
                }

                // olaylar
                ul.querySelectorAll('.page-link').forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = parseInt(this.getAttribute('data-page'));
                        updateTable();
                    });
                });
            }

            // Sıralama fonksiyonu
            function sortTable() {
                var rowsArray = Array.from(tableRows);

                rowsArray.sort(function(a, b) {
                    var aValue, bValue;

                    if (currentSort.field === 'name') {
                        aValue = a.querySelector('.category-name').textContent.toLowerCase();
                        bValue = b.querySelector('.category-name').textContent.toLowerCase();
                    } else if (currentSort.field === 'date') {
                        aValue = parseInt(a.getAttribute('data-date')) || 0;
                        bValue = parseInt(b.getAttribute('data-date')) || 0;
                    }

                    if (aValue > bValue) return currentSort.direction === 'asc' ? 1 : -1;
                    if (aValue < bValue) return currentSort.direction === 'asc' ? -1 : 1;
                    return 0;
                });

                // Yeniden sırala ve tabloya ekle
                var tbody = document.querySelector('#kategoriTable tbody');
                tbody.innerHTML = '';
                rowsArray.forEach(function(row) {
                    tbody.appendChild(row);
                });
            }

            // ilk yüklemede
            updateTable();
        });
    </script>

<?php
echo $OUTPUT->footer();
?>