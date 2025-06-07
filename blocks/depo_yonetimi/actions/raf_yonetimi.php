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

    <style>
        :root {
            --primary: #3e64ff;
            --primary-light: rgba(62, 100, 255, 0.1);
            --info: #17a2b8;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --secondary: #6c757d;
            --light: #f8f9fa;
            --dark: #343a40;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.10);
            --border-radius: 0.5rem;
        }

        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 1.5rem;
        }

        .card-header {
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
            background: linear-gradient(to right, var(--info), #20c9d6);
            color: white;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .table-raf {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-raf thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }

        .table-raf tbody tr {
            transition: all 0.2s;
        }

        .table-raf tbody tr:hover {
            background-color: var(--primary-light);
        }

        .raf-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .edit-row {
            background-color: rgba(255, 255, 224, 0.5);
        }

        .filter-section {
            padding: 1.2rem;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .depo-info {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            background-color: #fff;
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--info);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .depo-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(to right, var(--info), #20c9d6);
            color: white;
            margin-right: 1rem;
            font-size: 1.4rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>

    <div class="container-fluid">
        <!-- Depo Bilgisi -->
        <div class="depo-info">
            <div class="depo-icon">
                <i class="fas fa-warehouse"></i>
            </div>
            <div>
                <h4 class="mb-0"><?php echo htmlspecialchars($depo->name); ?></h4>
                <p class="text-muted mb-0">Raf ve Bölüm Yönetimi</p>
            </div>
        </div>

        <!-- Filtre Bölümü -->
        <div class="card">
            <div class="card-header bg-light text-dark">
                <i class="fas fa-filter me-2"></i> Filtreler
            </div>
            <div class="card-body filter-section">
                <form method="get" id="filterForm" class="row g-3">
                    <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">

                    <!-- Arama -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="Ürün ara..." name="search"
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>

                    <!-- Bölüm Filtresi -->
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

                    <!-- Raf Filtresi -->
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

                    <!-- Kategori Filtresi -->
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Filtrele
                            </button>
                            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depoid]); ?>"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Temizle
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ürün Listesi -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-boxes me-2"></i> Raf ve Bölüm Düzenleme
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-2"><?php echo count($urunler); ?> Ürün</span>
                        <button id="topluKaydet" class="btn btn-sm btn-success d-none">
                            <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
                        </button>
                    </div>
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