<?php

class block_depo_yonetimi extends block_base {

    public function init() {
        // Plugin başlığı
        $this->title = get_string('pluginname', 'block_depo_yonetimi');
    }

    public function get_content() {
        // İçerik zaten varsa onu döndür
        if ($this->content !== null) {
            return $this->content;
        }

        // Yeni içerik oluştur
        $this->content = new stdClass();
        $this->content->text = $this->generate_boxes_html();
        $this->content->footer = '';

        // CSS ve JS dosyalarını dahil et
        $this->page_add_assets();

        return $this->content;
    }

    private function page_add_assets() {
        global $PAGE;
        // Stil dosyasını dahil et
        $PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css'));

        // Font Awesome'ı dahil et (eğer tema tarafından sağlanmıyorsa)
        $PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'));

        // Bootstrap ve JS dosyalarını dahil et
        $PAGE->requires->js_call_amd('core/bootstrap', 'init');
        $PAGE->requires->js(new moodle_url('/blocks/depo_yonetimi/js/script.js'));
    }

    private function generate_boxes_html() {
        global $PAGE, $USER, $OUTPUT, $DB;

        $depolar = $DB->get_records('block_depo_yonetimi_depolar');

        if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
            $yetki = 'admin';
        } elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
            $yetki = 'depoyetkilisi';
        } else {
            return $this->render_access_denied('Yetkiniz yok.');
        }

        $depoid = optional_param('depo', null, PARAM_INT);

        // Depo detayı görüntüleniyorsa
        if ($depoid) {
            if ($yetki === 'admin' OR $yetki === 'depoyetkilisi') {
                return $this->render_depo_detay($depoid);
            } else {
                return $this->render_access_denied('Bu depoya erişim izniniz yok.');
            }
        }
        // Ana sayfa görüntüleniyorsa
        else {
            return $this->render_depolar_dashboard($depolar, $yetki);
        }
    }

    /**
     * Erişim reddedildiğinde gösterilecek mesaj
     */
    private function render_access_denied($message) {
        return '
            <div class="depo-access-denied">
                <div class="card border-danger shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                        <h4 class="text-danger">' . get_string('access_denied', 'block_depo_yonetimi', 'Erişim Reddedildi') . '</h4>
                        <p class="lead">' . $message . '</p>
                    </div>
                </div>
            </div>';
    }

    /**
     * Depo detay sayfasını render et
     */
    private function render_depo_detay($depoid) {
        global $DB, $OUTPUT, $PAGE;

        $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
        if (!$depo) {
            return $this->render_access_denied('Depo bulunamadı.');
        }

        $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

        // Kategorilere göre ürünleri grupla
        $kategorilere_gore_urunler = [];
        foreach ($urunler as $urun) {
            $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $urun->kategoriid]);
            $kategori_adi = $kategori ? $kategori->name : 'Kategorisiz';

            if (!isset($kategorilere_gore_urunler[$kategori_adi])) {
                $kategorilere_gore_urunler[$kategori_adi] = [];
            }

            $kategorilere_gore_urunler[$kategori_adi][] = [
                'id' => $urun->id,
                'name' => $urun->name,
                'adet' => $urun->adet,

                'duzenle_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', [
                    'depoid' => $depoid,
                    'urunid' => $urun->id
                ]))->out(false),
                'sil_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', [
                    'depoid' => $depoid,
                    'urunid' => $urun->id
                ]))->out(false)
            ];
        }

        $kategoriler = array_keys($kategorilere_gore_urunler);

        // Tüm kategorileri al (filtreleme için)
        $tum_kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler', null, 'name ASC');

        $html = '
            <div class="depo-dashboard">
                <div class="depo-header mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h2 class="mb-0">
                                <i class="fas fa-warehouse text-primary"></i>
                                ' . htmlspecialchars($depo->name) . '
                            </h2>
                            <p class="text-muted">
                                <i class="fas fa-box-open mr-1"></i> ' . count($urunler) . ' Ürün
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="' . $PAGE->url->out(false) . '" class="btn btn-outline-secondary rounded-pill">
                                <i class="fas fa-arrow-left"></i> Geri Dön
                            </a>
                            <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]) . '" class="btn btn-primary rounded-pill">
                                <i class="fas fa-plus"></i> Yeni Ürün Ekle
                            </a>

                           <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php', ['depoid' => $depo->id]) . '"
class="btn btn-info">
<i class="fas fa-th-list me-2"></i> Raf Yönetimi
</a>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" id="urunArama" class="form-control border-start-0" placeholder="Ürün ara...">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <select id="kategoriFiltre" class="form-select">
                                    <option value="">Tüm Kategoriler</option>';




        foreach ($tum_kategoriler as $kategori) {
            $html .= '<option value="' . htmlspecialchars($kategori->name) . '">' . htmlspecialchars($kategori->name) . '</option>';
        }

        $html .= '
                                </select>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php') . '" class="btn btn-outline-primary">
                                    <i class="fas fa-folder-plus"></i> Kategori Ekle
                                </a>

                                 <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/talepler.php') . '" class="btn btn-outline-secondary">
                                    <i class="fas fa-clipboard-list"></i> Talepler
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover urun-tablosu mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Ürün Adı</th>
                                        <th>Barkod</th>
                                        <th>Kategori</th>
                                        <th>Raf</th>
                                        <th>Bölüm</th>
                                        <th class="text-center">Adet</th>
                                        <th class="text-end pe-4">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>';

        if (empty($urunler)) {
            $html .= '
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-box-open text-muted mb-3" style="font-size: 3rem;"></i>
                                                <h4>Henüz ürün bulunmuyor</h4>
                                                <p class="text-muted">Bu depoya ürün eklemek için "Yeni Ürün Ekle" butonunu kullanabilirsiniz.</p>
                                            </div>
                                        </td>
                                    </tr>';
        } else {
            foreach ($urunler as $urun) {
                $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $urun->kategoriid]);
                $kategori_adi = $kategori ? $kategori->name : 'Kategorisiz';

                $html .= '
                                    <tr data-kategori="' . htmlspecialchars($kategori_adi) . '" data-id="' . $urun->id . '">
                                        <!-- Ürün Adı -->
                                        <td class="ps-4 align-middle">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box text-primary me-2"></i>
                                                <strong>' . htmlspecialchars($urun->name) . '</strong>
                                            </div>
                                        </td>
                                        <!-- Barkod -->
                                        <!-- Barkod -->
<td class="align-middle">
    ' . (!empty($urun->barkod) ?
                        '<div class="d-flex align-items-center">
        <i class="fas fa-barcode text-primary me-2"></i>
        <div class="barcode-display p-2 border rounded bg-light">
            <strong class="text-dark">' . htmlspecialchars($urun->barkod) . '</strong>
           <!-- Barkod tara ikonu -->
<span class="barcode-scan-icon ms-2" id="scan-barcode-btn" data-bs-toggle="tooltip" title="Barkodu Tara">
    <i class="fas fa-qrcode text-success"></i>
</span>
<div id="barcode-scanner" style="display:none;"></div>
    </div>' :

                        '<span class="text-muted fst-italic"><i class="fas fa-exclamation-circle me-1"></i>Barkod yok</span>') . '
</td>
                                        <!-- Kategori -->
                                      <td class="align-middle">
    <span class="badge rounded-pill px-3 py-2 fw-medium"
          style="background: linear-gradient(90deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; border: 1px solid #a5b4fc;">
        <i class="fas fa-tag me-1"></i>
        ' . htmlspecialchars($kategori_adi) . '
    </span>
</td>
                                        <!-- Raf -->
                                        <td class="raf-cell align-middle">
                                            ' . (!empty($urun->raf) ?
                        '<div class="badge rounded-pill bg-info-subtle text-info border px-3 py-2 fw-normal">
                                                <i class="bx bx-server me-1"></i>' . htmlspecialchars($urun->raf) . '
                                            </div>' :
                        '<span class="text-muted fst-italic">Belirtilmemiş</span>') . '
                                        </td>
                                        <!-- Bölüm -->
                                        <td class="bolum-cell align-middle">
                                            ' . (!empty($urun->bolum) ?
                        '<div class="badge rounded-pill bg-primary-subtle text-primary border px-3 py-2 fw-normal">
                                                <i class="bx bx-cabinet me-1"></i>' . htmlspecialchars($urun->bolum) . '
                                            </div>' :
                        '<span class="text-muted fst-italic">Belirtilmemiş</span>') . '
                                        </td>
                                        <!-- Adet -->
                                        <td class="text-center align-middle">
                                            <span class="badge bg-' . ($urun->adet > 10 ? 'success' : ($urun->adet > 3 ? 'warning' : 'danger')) . ' rounded-pill px-3 py-2 fw-medium">
                                                <i class="bx bx-' . ($urun->adet > 10 ? 'check' : ($urun->adet > 0 ? 'error' : 'x')) . ' me-1"></i>' . $urun->adet . ' adet
                                            </span>
                                        </td>
                                        <!-- İşlemler -->
                                        <td class="text-end pe-4 align-middle">
                                            <div class="btn-group">
                                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', [
                        'depoid' => $depoid,
                        'urunid' => $urun->id
                    ]) . '" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Stok Listesi">
                                                    <i class="fas fa-cube"></i>
                                                </a>
                                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', [
                        'depoid' => $depoid,
                        'urunid' => $urun->id
                    ]) . '" class="btn btn-sm btn-outline-secondary stok-hareket-btn" data-bs-toggle="tooltip" title="Stok Hareketleri">
                                                    <i class="fas fa-cubes text-warning stok-hareket-icon"></i>
                                                </a>
                                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', [
                        'depoid' => $depoid,
                        'urunid' => $urun->id
                    ]) . '" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ürün Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', [
                        'depoid' => $depoid,
                        'urunid' => $urun->id
                    ]) . '" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Ürün Sil" onclick="return confirm(\'Bu ürünü silmek istediğinize emin misiniz?\');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>';
            }
        }



        $html .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .stok-hareket-btn:hover {
                    background-color: #fd7e14 !important;
                    border-color: #fd7e14 !important;
                }
                .stok-hareket-btn:hover .stok-hareket-icon {
                    color: white !important;
                }
            </style>

            <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Arama fonksiyonu
                const searchInput = document.getElementById("urunArama");
                const kategoriSelect = document.getElementById("kategoriFiltre");
                const urunSatirlar = document.querySelectorAll("table.urun-tablosu tbody tr");

                function filterTable() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const kategoriFiltre = kategoriSelect.value.toLowerCase();

                    urunSatirlar.forEach(satir => {
                        const urunAdi = satir.querySelector("td:first-child strong").textContent.toLowerCase();
                        const kategori = satir.getAttribute("data-kategori").toLowerCase();

                        const searchMatch = urunAdi.includes(searchTerm);
                        const kategoriMatch = kategoriFiltre === "" || kategori === kategoriFiltre;

                        satir.style.display = (searchMatch && kategoriMatch) ? "" : "none";
                    });
                }

                searchInput.addEventListener("input", filterTable);
                kategoriSelect.addEventListener("change", filterTable);

                // Tooltip\'leri başlatmak için
                if (typeof bootstrap !== \'undefined\') {
                    const tooltipTriggerList = document.querySelectorAll(\'[data-bs-toggle="tooltip"]\');
                    [...tooltipTriggerList].forEach(el => new bootstrap.Tooltip(el));
                }
            });
            </script>';

        return $html;
    }

    /**
     * Depo dashboard sayfasını render et
     */
    private function render_depolar_dashboard($depolar, $yetki) {
        global $PAGE, $USER, $DB;

        $html = '
        <div class="depo-dashboard">
            <div class="dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-warehouse text-primary me-2"></i>
                            Depo Yönetimi
                        </h2>
                        <p class="text-muted">
                            ' . ($yetki === 'admin' ? 'Tüm depoları yönetebilirsiniz.' : 'Size atanmış depoları görebilirsiniz.') . '
                        </p>
                    </div>';

        if ($yetki === 'admin') {
            $html .= '
                    <div class="dashboard-actions d-flex gap-2 flex-wrap">
                        <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php') . '"
                           class="btn btn-primary rounded-pill d-flex align-items-center">
                            <i class="fas fa-plus me-2"></i> Depo Ekle
                        </a>
                        <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php') . '"
                           class="btn btn-outline-primary rounded-pill d-flex align-items-center">
                            <i class="fas fa-tags me-2"></i> Kategoriler
                        </a>

                    </div>';
        }

        $html .= '
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var scanBtn = document.getElementById("scan-barcode-btn");
    var scannerDiv = document.getElementById("barcode-scanner");
    var urunArama = document.getElementById("urunArama");
    var html5QrCode;
    if (scanBtn && scannerDiv && urunArama) {
        scanBtn.addEventListener("click", function() {
            scannerDiv.style.display = "block";
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("barcode-scanner");
            }
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: 250 },
                function(decodedText) {
                    urunArama.value = decodedText;
                    html5QrCode.stop();
                    scannerDiv.style.display = "none";
                },
                function(errorMessage) {
                    // Hata mesajı gösterme (isteğe bağlı)
                }
            );
        });
    }
});
</script>
';


        $html .= '
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">';

        // Admin için tüm depoları göster
        if ($yetki === 'admin') {
            if (empty($depolar)) {
                $html .= '
                <div class="col-12">
                    <div class="card border-dashed h-100 bg-light">
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center p-5">
                            <i class="fas fa-warehouse text-muted mb-3" style="font-size: 3rem;"></i>
                            <h4>Henüz depo bulunmuyor</h4>
                            <p class="text-muted">İlk deponuzu eklemek için "Depo Ekle" butonunu kullanabilirsiniz.</p>
                            <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php') . '"
                               class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i> Depo Ekle
                            </a>
                        </div>
                    </div>
                </div>';
            } else {
                foreach ($depolar as $depo) {
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);

                    // Depodaki ürün sayısını bul
                    $urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['depoid' => $depo->id]);

                    // Depo sorumlusunu bul
                    $sorumlu_html = '';
                    if (!empty($depo->sorumluid)) {
                        $sorumlu = $DB->get_record('user', ['id' => $depo->sorumluid]);
                        if ($sorumlu) {
                            $sorumlu_html = '<div class="d-flex align-items-center mt-2">
                                <div class="avatar-xs me-2 bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-primary" style="font-size: 0.6rem;"></i>
                                </div>
                                <span class="text-muted small">' . fullname($sorumlu) . '</span>
                            </div>';
                        }
                    }

                    $html .= '
                    <div class="col">
                        <a href="' . $url->out() . '" class="text-decoration-none">
                            <div class="card h-100 shadow-sm hover-shadow">
                                <div class="card-body">
                                    <h5 class="card-title d-flex align-items-center">
                                        <i class="fas fa-warehouse text-primary me-2"></i>
                                        ' . htmlspecialchars($depo->name) . '
                                    </h5>
                                    <div class="d-flex justify-content-between mt-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-box text-success" style="font-size: 0.6rem;"></i>
                                            </div>
                                            <span class="text-muted">' . $urun_sayisi . ' Ürün</span>
                                        </div>
                                    </div>
                                    ' . $sorumlu_html . '
                                </div>
                                <div class="card-footer bg-transparent border-0 text-end pt-0">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="fas fa-arrow-right me-1"></i> Detaylar
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>';
                }
            }
        }
        // Depo yetkilisi için
        else {
            $yetkili_depolar = $DB->get_records('block_depo_yonetimi_depolar', ['sorumluid' => $USER->id]);

            if (empty($yetkili_depolar)) {
                $html .= '
                <div class="col-12">
                    <div class="card border-dashed h-100 bg-light">
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center p-5">
                            <i class="fas fa-warehouse text-muted mb-3" style="font-size: 3rem;"></i>
                            <h4>Size atanmış bir depo bulunmuyor</h4>
                            <p class="text-muted">Henüz size atanmış bir depo bulunmamaktadır.</p>
                        </div>
                    </div>
                </div>';
            } else {
                foreach ($yetkili_depolar as $depo) {
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);

                    // Depodaki ürün sayısını bul
                    $urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['depoid' => $depo->id]);

                    $html .= '
                    <div class="col">
                        <a href="' . $url->out() . '" class="text-decoration-none">
                            <div class="card h-100 shadow-sm hover-shadow">
                                <div class="card-body">
                                    <h5 class="card-title d-flex align-items-center">
                                        <i class="fas fa-warehouse text-primary me-2"></i>
                                        ' . htmlspecialchars($depo->name) . '
                                    </h5>
                                    <div class="d-flex justify-content-between mt-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs bg-success-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-box text-success" style="font-size: 0.6rem;"></i>
                                            </div>
                                            <span class="text-muted">' . $urun_sayisi . ' Ürün</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-end pt-0">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="fas fa-arrow-right me-1"></i> Detaylar
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>';
                }
            }
        }

        $html .= '
            </div>
        </div>
        
        <style>
        .border-dashed {
            border-style: dashed !important;
        }
        .hover-shadow:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            transition: all 0.3s ease;
        }
        .avatar-xs {
            width: 22px;
            height: 22px;
        }
        </style>';

        return $html;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_multiple() {
        return false;
    }
}