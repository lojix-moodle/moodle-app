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
                                        // Her kategori için ürün sayısını sorgula
// Her kategori için ürün sayısını sorgula
                                        // Her kategori için ürün sayısını sorgula
                                        $urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['kategoriid' => $kategori->id]);                                       ?>
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
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', ['kategoriid' => $kategori->id, 'sesskey' => sesskey()]); ?>"
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
            var searchInput = document.getElementById('searchInput');
            var tableRows = document.querySelectorAll('#kategoriTable tbody tr');
            var sortButtons = document.querySelectorAll('.sort-option');
            var sortField = 'name';
            var sortDirection = 'asc';

            // Arama fonksiyonu
            searchInput.addEventListener('keyup', function() {
                var searchTerm = searchInput.value.toLowerCase();
                tableRows.forEach(function(row) {
                    var kategoriName = row.querySelector('.category-name').textContent.toLowerCase();
                    row.style.display = kategoriName.includes(searchTerm) ? '' : 'none';
                });
            });

            // Sıralama fonksiyonu
            sortButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var sortOption = this.getAttribute('data-sort');
                    var parts = sortOption.split('-');
                    sortField = parts[0];
                    sortDirection = parts[1];

                    sortTable();
                });
            });

            function sortTable() {
                var rows = Array.from(tableRows);
                rows.sort(function(a, b) {
                    var aValue = a.querySelector('.category-name').textContent.toLowerCase();
                    var bValue = b.querySelector('.category-name').textContent.toLowerCase();

                    if (sortField === 'date') {
                        aValue = parseInt(a.getAttribute('data-date')) || 0;
                        bValue = parseInt(b.getAttribute('data-date')) || 0;
                    }

                    if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
                    if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
                    return 0;
                });

                var tbody = document.querySelector('#kategoriTable tbody');
                tbody.innerHTML = '';
                rows.forEach(function(row) {
                    tbody.appendChild(row);
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Temel değişkenler
            var loadingOverlay = document.getElementById('loadingOverlay');
            var searchInput = document.getElementById('searchInput');
            var tableRows = document.querySelectorAll('#kategoriTable tbody tr');
            var allRows = Array.from(tableRows);
            var totalCountEl = document.getElementById('totalCount');
            var displayedCountEl = document.getElementById('displayedCount');
            var pageSizeSelect = document.getElementById('page-size');
            var paginationContainer = document.querySelector('.pagination');

            // Durum değişkenleri
            var currentPage = 1;
            var pageSize = 10;
            var sortField = 'name';
            var sortDirection = 'asc';

            // Modal değişkenleri
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            var deleteModalText = document.getElementById('deleteModalText');
            var sortDropdown = document.getElementById('sortDropdown');

            // Sayfa boyutunu başlangıçta ayarla
            pageSizeSelect.value = "10";

            // Loading overlay'i gizle
            window.addEventListener('load', function() {
                loadingOverlay.style.display = 'none';
            });

            // Silme butonlarına tıklama olayı - ÖNEMLİ: Burada alert eklenecek
            document.querySelectorAll('.delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var kategoriName = this.getAttribute('data-name');
                    var deleteUrl = this.href;

                    // Önce confirm ile uyarı göster
                    if (confirm('"' + kategoriName + '" kategorisini silmek istediğinizden emin misiniz?')) {
                        // Kullanıcı OK'e tıkladıysa, modal içinde daha detaylı onay
                        deleteModalText.textContent = '"' + kategoriName + '" kategorisini silmek üzeresiniz. Bu işlem geri alınamaz!';
                        confirmDeleteBtn.href = deleteUrl;
                        deleteModal.show();
                    }
                });
            });

            // Modal'da silme onayı
            confirmDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loadingOverlay.style.display = 'flex';
                setTimeout(function() {
                    window.location.href = confirmDeleteBtn.getAttribute('href');
                }, 100);
            });

            // Arama işlevi
            searchInput.addEventListener('keyup', function() {
                currentPage = 1;
                filterAndPaginate();
            });

            // Sıralama işlevi
            document.querySelectorAll('.sort-option').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var sortOption = this.getAttribute('data-sort');
                    var parts = sortOption.split('-');
                    sortField = parts[0];
                    sortDirection = parts[1];
                    sortDropdown.innerHTML = '<i class="fas fa-sort me-1"></i>' + this.textContent;
                    filterAndPaginate();
                });
            });

            // Sayfa boyutu değişimi
            pageSizeSelect.addEventListener('change', function() {
                var selectedValue = this.value;
                pageSize = selectedValue === 'all' ? allRows.length : parseInt(selectedValue);
                currentPage = 1;
                filterAndPaginate();
            });

            // Tablonun filtrelenmesi ve sayfalanması
            function filterAndPaginate() {
                // Önce tüm satırları gizle
                allRows.forEach(function(row) {
                    row.style.display = 'none';
                });

                // Arama filtresi uygula
                var searchTerm = searchInput.value.toLowerCase();
                var filteredRows = allRows.filter(function(row) {
                    var kategoriName = row.querySelector('.category-name').textContent.toLowerCase();
                    return kategoriName.includes(searchTerm);
                });

                // Sıralama uygula
                filteredRows.sort(function(a, b) {
                    var aValue, bValue;

                    if (sortField === 'name') {
                        aValue = a.querySelector('.category-name').textContent.toLowerCase();
                        bValue = b.querySelector('.category-name').textContent.toLowerCase();
                    } else if (sortField === 'date') {
                        aValue = parseInt(a.getAttribute('data-date')) || 0;
                        bValue = parseInt(b.getAttribute('data-date')) || 0;
                    }

                    if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
                    if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
                    return 0;
                });

                // Sayfalama hesapla
                var totalItems = filteredRows.length;
                var totalPages = Math.ceil(totalItems / pageSize);

                if (currentPage > totalPages && totalPages > 0) {
                    currentPage = totalPages;
                }

                // Sadece gerekli satırları göster
                var startIndex = (currentPage - 1) * pageSize;
                var endIndex = Math.min(startIndex + pageSize, totalItems);

                for (var i = startIndex; i < endIndex; i++) {
                    filteredRows[i].style.display = '';
                }

                // Sayfalama kontrollerini güncelle
                updatePagination(totalItems);

                // Sayaçları güncelle
                totalCountEl.textContent = allRows.length;
                displayedCountEl.textContent = totalItems;
            }

            // Sayfalama arayüzünü güncelleme
            function updatePagination(totalItems) {
                paginationContainer.innerHTML = '';

                if (pageSizeSelect.value === 'all' || pageSize >= totalItems) {
                    document.getElementById('pagination-container').style.display = 'none';
                    return;
                }

                document.getElementById('pagination-container').style.display = 'flex';

                var totalPages = Math.ceil(totalItems / pageSize);
                var startPage = Math.max(1, currentPage - 2);
                var endPage = Math.min(totalPages, startPage + 4);

                if (currentPage > 1) {
                    addPageItem(currentPage - 1, '«', 'Önceki');
                }

                for (var i = startPage; i <= endPage; i++) {
                    addPageItem(i, i, '', i === currentPage);
                }

                if (currentPage < totalPages) {
                    addPageItem(currentPage + 1, '»', 'Sonraki');
                }
            }

            // Sayfalama öğesi oluşturma
            function addPageItem(pageNum, text, ariaLabel, isActive) {
                var li = document.createElement('li');
                li.className = 'page-item' + (isActive ? ' active' : '');

                var a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.setAttribute('data-page', pageNum);
                if (ariaLabel) a.setAttribute('aria-label', ariaLabel);
                a.innerHTML = text;

                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentPage = parseInt(this.getAttribute('data-page'));
                    filterAndPaginate();
                });

                li.appendChild(a);
                paginationContainer.appendChild(li);
            }

            // Sayfa yüklendiğinde filtreleme ve sayfalamayı uygula
            setTimeout(function() {
                filterAndPaginate();
            }, 200);
        });
    </script>

<?php
echo $OUTPUT->footer();
?>