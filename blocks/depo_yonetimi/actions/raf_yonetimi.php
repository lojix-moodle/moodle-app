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

// Raflara göre gruplandırılmış ürünleri al
$sql = "SELECT u.*, k.name as kategori_adi, u.raf, u.bolum
        FROM {block_depo_yonetimi_urunler} u
        LEFT JOIN {block_depo_yonetimi_kategoriler} k ON u.kategoriid = k.id
        WHERE u.depoid = :depoid
        ORDER BY u.raf, u.bolum, u.name ASC";

$urunler = $DB->get_records_sql($sql, ['depoid' => $depoid]);

// Bölümleri grupla
$bolum_listesi = [
    "Tişört", "Pantolon", "Ayakkabı", "Gömlek", "Elbise",
    "Ceket", "Aksesuar", "Çanta", "İç Giyim"
];

// Rafları tanımla
$raflar = [
    "A1 Rafı" => ["renk" => "#3498db", "yukseklik" => "200px"],
    "A2 Rafı" => ["renk" => "#2ecc71", "yukseklik" => "200px"],
    "A3 Rafı" => ["renk" => "#9b59b6", "yukseklik" => "200px"],
    "B1 Rafı" => ["renk" => "#e74c3c", "yukseklik" => "200px"],
    "B2 Rafı" => ["renk" => "#f39c12", "yukseklik" => "200px"],
    "B3 Rafı" => ["renk" => "#1abc9c", "yukseklik" => "200px"],
    "C1 Rafı" => ["renk" => "#d35400", "yukseklik" => "200px"],
    "C2 Rafı" => ["renk" => "#27ae60", "yukseklik" => "180px"],
    "C3 Rafı" => ["renk" => "#8e44ad", "yukseklik" => "180px"],
    "C4 Rafı" => ["renk" => "#c0392b", "yukseklik" => "180px"],
    "D1 Rafı" => ["renk" => "#16a085", "yukseklik" => "150px"],
    "D2 Rafı" => ["renk" => "#2980b9", "yukseklik" => "150px"],
    "E1 Rafı" => ["renk" => "#f1c40f", "yukseklik" => "150px"],
    "E2 Rafı" => ["renk" => "#e67e22", "yukseklik" => "150px"],
    "E3 Rafı" => ["renk" => "#34495e", "yukseklik" => "150px"],
];

// Ürünleri raflara dağıt
$raf_urunleri = [];
foreach($urunler as $urun) {
    $raf_key = !empty($urun->raf) ? $urun->raf : "Atanmamış";
    if(!isset($raf_urunleri[$raf_key])) {
        $raf_urunleri[$raf_key] = [];
    }
    $raf_urunleri[$raf_key][] = $urun;
}

// Sayfa çıktısı başlat
echo $OUTPUT->header();
?>

<style>
    :root {
        --primary: #3e64ff;
        --info: #17a2b8;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --light: #f8f9fa;
        --dark: #343a40;
        --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        --border-radius: 8px;
    }

    .warehouse-container {
        background-color: #f5f5f5;
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }

    .shelf-header {
        background: linear-gradient(to right, #34495e, #2c3e50);
        color: white;
        padding: 15px;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .shelf-system {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .shelf {
        background-color: #8B4513;
        background: linear-gradient(to bottom, #8B4513, #A0522D);
        padding: 10px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        position: relative;
    }

    .shelf-name {
        background-color: #6d3200;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        position: absolute;
        top: -10px;
        left: 10px;
        font-size: 0.9rem;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .shelf-divider {
        height: 12px;
        background-color: #6d3200;
        border-radius: 2px 2px 0 0;
        margin: 5px 0;
    }

    .items-container {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: var(--border-radius);
        padding: 5px;
        min-height: 180px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-content: flex-start;
    }

    .product-item {
        padding: 8px;
        border-radius: 6px;
        width: calc(50% - 4px);
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
        position: relative;
        min-height: 75px;
        color: black;
    }

    .product-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        z-index: 10;
    }

    .product-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: var(--danger);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .product-category {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 3px;
    }

    .unassigned-items {
        background: linear-gradient(to right, #c0392b, #e74c3c);
        padding: 15px;
        border-radius: var(--border-radius);
        margin-top: 20px;
        margin-bottom: 20px;
        color: white;
        box-shadow: var(--shadow);
    }

    .unassigned-container {
        background-color: #f5f5f5;
        border-radius: var(--border-radius);
        padding: 15px;
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .unassigned-product {
        background-color: white;
        padding: 8px 12px;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        width: calc(33% - 10px);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .unassigned-product:hover {
        background-color: var(--light);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .shelf-section {
        margin-bottom: 40px;
    }

    .shelf-section-title {
        background: linear-gradient(to right, var(--info), #20c9d6);
        color: white;
        padding: 10px 15px;
        border-radius: var(--border-radius);
        margin-bottom: 15px;
        font-weight: bold;
    }

    .action-buttons {
        display: flex;
        justify-content: space-between;
        margin: 20px 0;
    }

    @media (max-width: 768px) {
        .shelf-system {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }

        .product-item {
            width: 100%;
        }

        .unassigned-product {
            width: calc(50% - 10px);
        }
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

    /* Renk kodları */
    .bg-tisort { background-color: #3498db; }
    .bg-pantolon { background-color: #2ecc71; }
    .bg-ayakkabi { background-color: #e74c3c; }
    .bg-gomlek { background-color: #f39c12; }
    .bg-elbise { background-color: #9b59b6; }
    .bg-ceket { background-color: #1abc9c; }
    .bg-aksesuar { background-color: #d35400; }
    .bg-canta { background-color: #27ae60; }
    .bg-icgiyim { background-color: #8e44ad; }
    .bg-diger { background-color: #95a5a6; }
</style>

<div class="container-fluid">
    <!-- Depo Bilgisi -->
    <div class="depo-info">
        <div class="depo-icon">
            <i class="fas fa-warehouse"></i>
        </div>
        <div>
            <h4 class="mb-0"><?php echo htmlspecialchars($depo->name); ?></h4>
            <p class="text-muted mb-0">3D Raf Sistemi</p>
        </div>
    </div>

    <!-- Raf Sistemi Ana Bölümü -->
    <div class="warehouse-container">
        <div class="shelf-header">
            <h4 class="mb-0"><i class="fas fa-th-large me-2"></i> Depo Raf Sistemi</h4>
            <span class="badge bg-light text-dark"><?php echo count($urunler); ?> Ürün</span>
        </div>

        <?php
        // Bölümlere göre rafları gruplandır
        foreach($bolum_listesi as $bolum_adi) {
            $bolum_raf_sayisi = 0;
            $bolum_urun_sayisi = 0;

            // Bu bölüm için raf sayımı yap
            foreach($raf_urunleri as $raf_adi => $raf_uruns) {
                foreach($raf_uruns as $raf_urun) {
                    if($raf_urun->bolum === $bolum_adi) {
                        $bolum_raf_sayisi++;
                        $bolum_urun_sayisi++;
                        break;
                    }
                }
            }

            // Bu bölümde ürün yoksa gösterme
            if($bolum_urun_sayisi === 0) continue;
            ?>
            <div class="shelf-section">
                <div class="shelf-section-title">
                    <i class="fas fa-box me-2"></i> <?php echo htmlspecialchars($bolum_adi); ?> Bölümü
                </div>

                <div class="shelf-system">
                    <?php
                    // Bu bölüm için rafları göster
                    $bolum_temiz = strtolower(str_replace(['ı','İ','ş','Ş','ç','Ç','ö','Ö','ü','Ü','ğ','Ğ',' '], ['i','i','s','s','c','c','o','o','u','u','g','g',''], $bolum_adi));
                    $raf_bg_class = "bg-{$bolum_temiz}";
                    if(!in_array($bolum_temiz, ['tisort', 'pantolon', 'ayakkabi', 'gomlek', 'elbise', 'ceket', 'aksesuar', 'canta', 'icgiyim'])) {
                        $raf_bg_class = "bg-diger";
                    }

                    foreach($raflar as $raf_adi => $raf_info) {
                        $has_products = false;

                        // Bu raf için ürünleri kontrol et
                        if(isset($raf_urunleri[$raf_adi])) {
                            foreach($raf_urunleri[$raf_adi] as $rurun) {
                                if($rurun->bolum === $bolum_adi) {
                                    $has_products = true;
                                    break;
                                }
                            }
                        }

                        // Bu rafta bu bölümün ürünleri yoksa gösterme
                        if(!$has_products) continue;
                        ?>
                        <div class="shelf">
                            <span class="shelf-name"><?php echo $raf_adi; ?></span>
                            <div class="shelf-divider"></div>
                            <div class="items-container" style="min-height: <?php echo $raf_info['yukseklik']; ?>">
                                <?php
                                // Bu raftaki ve bölümdeki ürünleri göster
                                if(isset($raf_urunleri[$raf_adi])) {
                                    foreach($raf_urunleri[$raf_adi] as $urun) {
                                        if($urun->bolum !== $bolum_adi) continue;

                                        $urun_renk = '';
                                        switch($bolum_adi) {
                                            case 'Tişört': $urun_renk = '#3498db'; break;
                                            case 'Pantolon': $urun_renk = '#2ecc71'; break;
                                            case 'Ayakkabı': $urun_renk = '#e74c3c'; break;
                                            case 'Gömlek': $urun_renk = '#f39c12'; break;
                                            case 'Elbise': $urun_renk = '#9b59b6'; break;
                                            case 'Ceket': $urun_renk = '#1abc9c'; break;
                                            case 'Aksesuar': $urun_renk = '#d35400'; break;
                                            case 'Çanta': $urun_renk = '#27ae60'; break;
                                            case 'İç Giyim': $urun_renk = '#8e44ad'; break;
                                            default: $urun_renk = '#95a5a6';
                                        }
                                        ?>
                                        <div class="product-item"
                                             style="background-color: <?php echo $urun_renk; ?>20; border: 2px solid <?php echo $urun_renk; ?>;"
                                             data-id="<?php echo $urun->id; ?>"
                                             data-name="<?php echo htmlspecialchars($urun->name); ?>"
                                             data-raf="<?php echo htmlspecialchars($urun->raf); ?>"
                                             data-bolum="<?php echo htmlspecialchars($urun->bolum); ?>">
                                            <div class="product-count"><?php echo $urun->adet; ?></div>
                                            <strong><?php echo htmlspecialchars($urun->name); ?></strong>
                                            <span class="product-category"><?php echo htmlspecialchars($urun->kategori_adi); ?></span>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <!-- Atanmamış Ürünler Bölümü -->
        <?php if(isset($raf_urunleri['Atanmamış']) && !empty($raf_urunleri['Atanmamış'])): ?>
            <div class="unassigned-items">
                <h5><i class="fas fa-dolly fa-fw me-2"></i> Rafa Atanmamış Ürünler</h5>
                <div class="unassigned-container">
                    <?php foreach($raf_urunleri['Atanmamış'] as $urun): ?>
                        <div class="unassigned-product"
                             data-id="<?php echo $urun->id; ?>"
                             data-name="<?php echo htmlspecialchars($urun->name); ?>">
                            <strong><?php echo htmlspecialchars($urun->name); ?></strong>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted"><?php echo htmlspecialchars($urun->kategori_adi); ?></small>
                                <span class="badge bg-<?php echo $urun->adet > 0 ? 'success' : 'danger'; ?>"><?php echo $urun->adet; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Butonlar -->
    <div class="d-flex justify-content-between mt-4 mb-4">
        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/block_depo_yonetimi.php', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Depoya Geri Dön
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
                            <?php foreach($bolum_listesi as $bolum_item): ?>
                                <option value="<?php echo htmlspecialchars($bolum_item); ?>"><?php echo htmlspecialchars($bolum_item); ?></option>
                            <?php endforeach; ?>
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

        // Raf tanımları (her bölüm için olası raflar)
        const rafTanimlari = {
            "Tişört": ["A1 Rafı", "A2 Rafı", "A3 Rafı"],
            "Gömlek": ["A1 Rafı", "A2 Rafı", "A3 Rafı"],
            "Pantolon": ["B1 Rafı", "B2 Rafı", "B3 Rafı"],
            "Ayakkabı": ["C1 Rafı", "C2 Rafı", "C3 Rafı", "C4 Rafı"],
            "Aksesuar": ["D1 Rafı", "D2 Rafı"],
            "Çanta": ["D1 Rafı", "D2 Rafı"],
            "default": ["E1 Rafı", "E2 Rafı", "E3 Rafı"]
        };

        // Tüm ürün öğelerine tıklama olayı ekle
        document.querySelectorAll('.product-item, .unassigned-product').forEach(item => {
            item.addEventListener('click', function() {
                const urunId = this.dataset.id;
                const urunAdi = this.dataset.name;
                const bolum = this.dataset.bolum || '';
                const raf = this.dataset.raf || '';

                // Modal alanlarını doldur
                document.getElementById('edit_urun_id').value = urunId;
                document.getElementById('edit_urun_adi').value = urunAdi;

                // Bölüm seçimini ayarla
                editBolumSelect.value = bolum;

                // Raf seçeneklerini güncelle
                updateRafOptions(bolum, raf);

                // Modal'ı göster
                editModal.show();
            });
        });

        // Bölüm değiştiğinde rafları güncelle
        editBolumSelect.addEventListener('change', function() {
            updateRafOptions(this.value);
        });

        // Değişikliği kaydet butonu
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
                            text: 'Raf ve bölüm bilgileri güncellendi.',
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

        // Rafları güncelleme fonksiyonu
        function updateRafOptions(bolum, selectedRaf = '') {
            // Raf seçimini temizle
            editRafSelect.innerHTML = '<option value="">-- Raf Seçin --</option>';

            // Bölüme göre rafları ayarla
            let raflar = [];

            if (bolum in rafTanimlari) {
                raflar = rafTanimlari[bolum];
            } else if (bolum) {
                // Diğer tüm bölümler için varsayılan rafları kullan
                raflar = ["E1 Rafı", "E2 Rafı", "E3 Rafı"];
            }

            // Rafları dropdown'a ekle
            raflar.forEach(raf => {
                const option = document.createElement("option");
                option.value = raf;
                option.text = raf;
                editRafSelect.appendChild(option);
            });

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
    }
    });
</script>

<?php
echo $OUTPUT->footer();
?>