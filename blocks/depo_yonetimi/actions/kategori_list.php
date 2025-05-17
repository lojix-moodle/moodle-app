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

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
        }

        .badge-counter {
            background-color: #6c757d;
            color: white;
            font-size: 0.7rem;
            padding: 0.15rem 0.4rem;
            border-radius: 1rem;
            margin-left: 0.25rem;
        }

        .pagination {
            margin-bottom: 0;
        }

        .page-item .page-link {
            color: #0f6cbf;
        }

        .page-item.active .page-link {
            background-color: #0f6cbf;
            border-color: #0f6cbf;
        }

        .animate-fade {
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .dropdown-item {
            cursor: pointer;
        }
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
                                    <div class="input-group search-box">
                                    <span class="input-group-text" id="search-addon">
                                        <i class="fas fa-search"></i>
                                    </span>
                                        <input type="text" class="form-control" id="searchInput" placeholder="Kategori ara..." aria-label="Ara" aria-describedby="search-addon">
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-sort me-1"></i>İsim (A-Z)
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
                                        <th scope="col">Kategori Adı</th>
                                        <th scope="col">Ürün Sayısı</th>
                                        <th scope="col">Oluşturulma Tarihi</th>
                                        <th scope="col" class="text-end">İşlemler</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $urun_sayilari = []; // Gerçek projede bu değeri veritabanından alabilirsiniz
                                    foreach ($kategoriler as $kategori):
                                        $urun_sayisi = isset($urun_sayilari[$kategori->id]) ? $urun_sayilari[$kategori->id] : 0;
                                        $date_timestamp = isset($kategori->timecreated) ? $kategori->timecreated : 0;
                                        $date_formatted = $date_timestamp ? userdate($date_timestamp, get_string('strftimedatetimeshort', 'core_langconfig')) : '';
                                        ?>
                                        <tr data-date="<?php echo $date_timestamp; ?>">
                                            <td>
                                                <span class="category-name"><?php echo $kategori->name; ?></span>
                                            </td>
                                            <td>
                                        <span class="badge bg-<?php echo $urun_sayisi > 0 ? 'primary' : 'secondary'; ?> rounded-pill">
                                            <?php echo $urun_sayisi; ?> ürün
                                        </span>
                                            </td>
                                            <td><?php echo $date_formatted; ?></td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_edit.php', ['id' => $kategori->id]); ?>" class="btn btn-outline-primary btn-sm edit-btn" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', ['id' => $kategori->id, 'sesskey' => sesskey()]); ?>"
                                                       class="btn btn-outline-danger btn-sm delete-btn"
                                                       data-id="<?php echo $kategori->id; ?>"
                                                       data-name="<?php echo $kategori->name; ?>"
                                                       title="Sil">
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
                                        <option value="10" selected>10 öğe</option>
                                        <option value="25">25 öğe</option>
                                        <option value="50">50 öğe</option>
                                        <option value="100">100 öğe</option>
                                        <option value="all">Tümü</option>
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

            // Silme butonu işlemleri
            var deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var kategoriId = this.getAttribute('data-id');
                    var kategoriName = this.getAttribute('data-name');

                    deleteModalText.textContent = '"' + kategoriName + '" kategorisini silmek istediğinizden emin misiniz?';
                    confirmDeleteBtn.href = this.href;

                    deleteModal.show();
                });
            });

            // Modal ile silme onayı
            confirmDeleteBtn.addEventListener('click', function() {
                loadingOverlay.style.display = 'flex';
            });

            // Sıralama işlemi
            var sortButtons = document.querySelectorAll('.sort-option');
            sortButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var sortOption = this.getAttribute('data-sort');
                    var parts = sortOption.split('-');
                    sortField = parts[0];
                    sortDirection = parts[1];

                    // Sıralama başlığını güncelle
                    sortDropdown.innerHTML = '<i class="fas fa-sort me-1"></i>' + this.textContent;

                    sortTable();
                    updateTable();
                });
            });

            // Tablo sıralama fonksiyonu - DÜZELTİLMİŞ
            function sortTable() {
                console.log("Sıralama: " + sortField + " " + sortDirection);
                var rows = Array.from(tableRows);

                rows.sort(function(a, b) {
                    var aValue, bValue;

                    if (sortField === 'name') {
                        // İsim sıralaması
                        aValue = a.querySelector('.category-name').textContent.toLowerCase().trim();
                        bValue = b.querySelector('.category-name').textContent.toLowerCase().trim();
                    } else if (sortField === 'date') {
                        // Tarih sıralaması (data-date özniteliği kullanarak)
                        aValue = parseInt(a.getAttribute('data-date')) || 0;
                        bValue = parseInt(b.getAttribute('data-date')) || 0;
                    }

                    // Karşılaştırma için tam bir karşılaştırma operatörü kullanıyoruz
                    var comparison = 0;
                    if (aValue > bValue) {
                        comparison = 1;
                    } else if (aValue < bValue) {
                        comparison = -1;
                    }

                    // Sıralama yönüne göre sonucu tersine çeviriyoruz
                    return sortDirection === 'asc' ? comparison : -comparison;
                });

                // Sıralanmış satırları tabloya yerleştir
                var tbody = document.querySelector('#kategoriTable tbody');
                // Önce tüm satırları kaldır
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }
                // Sonra sıralanmış satırları ekle
                rows.forEach(function(row) {
                    tbody.appendChild(row);
                });
            }

            // Arama fonksiyonu
            searchInput.addEventListener('keyup', function() {
                currentPage = 1;
                updateTable();
            });

            // Sayfa boyutu değişim işlemi
            pageSizeSelect.addEventListener('change', function() {
                var selectedValue = this.value;
                if (selectedValue === 'all') {
                    pageSize = tableRows.length;
                } else {
                    pageSize = parseInt(selectedValue);
                }
                currentPage = 1;
                updateTable();
            });

            // Tablo güncelleme fonksiyonu
            function updateTable() {
                var searchTerm = searchInput.value.toLowerCase();
                var visibleRows = [];
                var startIndex, endIndex;

                // Önce arama kriterlerine göre görünür satırları belirle
                tableRows.forEach(function(row) {
                    var kategoriName = row.querySelector('.category-name').textContent.toLowerCase();
                    if (kategoriName.indexOf(searchTerm) > -1) {
                        row.dataset.visible = 'true';
                        visibleRows.push(row);
                    } else {
                        row.dataset.visible = 'false';
                    }
                });

                // Sayfalama sınırlarını belirle
                if (pageSize === tableRows.length || pageSize >= visibleRows.length) {
                    startIndex = 0;
                    endIndex = visibleRows.length;
                } else {
                    startIndex = (currentPage - 1) * pageSize;
                    endIndex = Math.min(startIndex + pageSize, visibleRows.length);
                }

                // Tüm satırları gizle
                tableRows.forEach(function(row) {
                    row.style.display = 'none';
                });

                // Geçerli sayfa için satırları göster
                for (var i = startIndex; i < endIndex; i++) {
                    if (i < visibleRows.length) {
                        visibleRows[i].style.display = '';
                    }
                }

                // Sayfalama oluştur
                createPagination(visibleRows.length);

                // Sayaçları güncelle
                totalCountEl.textContent = tableRows.length;
                displayedCountEl.textContent = visibleRows.length;
            }

            // Sayfalama oluşturma fonksiyonu
            function createPagination(totalItems) {
                if (pageSizeSelect.value === 'all' || pageSize >= totalItems) {
                    document.getElementById('pagination-container').style.display = 'none';
                    return;
                }

                document.getElementById('pagination-container').style.display = 'flex';
                paginationContainer.innerHTML = '';

                var totalPages = Math.ceil(totalItems / pageSize);
                var startPage = Math.max(1, currentPage - 2);
                var endPage = Math.min(totalPages, startPage + 4);

                // Önceki sayfa butonu
                if (currentPage > 1) {
                    paginationContainer.innerHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Önceki">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                `;
                }

                // Sayfa numaraları
                for (var i = startPage; i <= endPage; i++) {
                    paginationContainer.innerHTML += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
                }

                // Sonraki sayfa butonu
                if (currentPage < totalPages) {
                    paginationContainer.innerHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Sonraki">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                `;
                }

                // Sayfa butonlarına olay dinleyicisi ekle
                var pageLinks = document.querySelectorAll('.page-link');
                pageLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = parseInt(this.getAttribute('data-page'));
                        updateTable();
                    });
                });
            }

            // İlk yükleme
            updateTable();
        });
    </script>

<?php
echo $OUTPUT->footer();
?>