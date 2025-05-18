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
        $kullanici_depo_eslesme = [
            2 => 3,
            5 => 1,
        ];

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
            if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depoid)) {
                return $this->render_depo_detay($depoid);
            } else {
                return $this->render_access_denied('Bu depoya erişim izniniz yok.');
            }
        }
        // Ana sayfa görüntüleniyorsa
        else {
            return $this->render_depolar_dashboard($depolar, $yetki, $kullanici_depo_eslesme);
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
                'stok_miktari' => $urun->stok_miktari, // Bu satırı ekleyin

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
                                        <th>Kategori</th>
                                        <th class="text-center">Adet</th>
                                        <th class="text-center">Stok Miktarı</th>
                                        <th class="text-end pe-4">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>';

        if (empty($urunler)) {
            $html .= '
                                    <tr>
                                    <td colspan="5" class="text-center py-5">
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
                                    <tr data-kategori="' . htmlspecialchars($kategori_adi) . '">
                                        <td class="ps-4 align-middle">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-box text-primary me-2"></i>
                                                <strong>' . htmlspecialchars($urun->name) . '</strong>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge bg-light text-dark border">' . htmlspecialchars($kategori_adi) . '</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge bg-' . ($urun->adet > 10 ? 'success' : ($urun->adet > 3 ? 'warning' : 'danger')) . ' rounded-pill px-3 py-2">' .
                    $urun->adet . ' adet
                                            </span>                                   
                                        </td>
                                         <td class="text-center align-middle">
                                    <span class="badge bg-info rounded-pill px-3 py-2">' .
                                        $urun->stok_miktari . ' adet
                                    </span>
                                </td>
                                        
                                        <td class="text-end pe-4 align-middle">
                                            <div class="btn-group">
                                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', [
                        'depoid' => $depoid,
                        'urunid' => $urun->id
                    ]) . '" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="' . new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', [
                        'depoid' => $depoid,
                        'urunid' => $urun->id
                    ]) . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Bu ürünü silmek istediğinize emin misiniz?\');">
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
            });
            </script>';

        return $html;
    }

    /**
     * Depo dashboard sayfasını render et
     */
    private function render_depolar_dashboard($depolar, $yetki, $kullanici_depo_eslesme) {
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
                    $duzenleUrl = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depo->id]);
                    $silUrl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id]);

                    // Depo sorumlusu bilgisini al
                    // Doğru kod:
                    $sorumlu_ismi = 'Atanmamış';
                    if (!empty($depo->sorumluid)) {
                        $sorumlu = $DB->get_record('user', ['id' => $depo->sorumluid]);
                        if ($sorumlu) {
                            $sorumlu_ismi = fullname($sorumlu); // Moodle'un fullname fonksiyonunu kullan
                        }
                    }

                    $html .= '
                <div class="col">
                    <div class="card depo-card h-100 shadow-sm border-0">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="depo-icon-container bg-primary bg-opacity-10 rounded-circle p-3">
                                    <i class="fas fa-warehouse text-primary"></i>
                                </div>
                                <div class="d-flex">
                                    <a href="' . $duzenleUrl . '" class="btn btn-sm btn-outline-info me-2" title="Depoyu Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="' . $silUrl . '" class="btn btn-sm btn-outline-danger" title="Depoyu Sil" onclick="return confirm(\'Bu depoyu silmek istediğinize emin misiniz?\');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h3 class="card-title h5 mb-3">' . htmlspecialchars($depo->name) . '</h3>
                            <div class="depo-info mb-3">
                                <div class="d-flex align-items-center text-muted mb-2">
                                    <i class="fas fa-user me-2"></i>
                                    <span>Sorumlu: ' . htmlspecialchars($sorumlu_ismi) . '</span>
                                </div>
                            </div>
                            <a href="' . $url . '" class="btn btn-outline-primary mt-auto">
                                <i class="fas fa-boxes me-2"></i>Ürünleri Görüntüle
                            </a>
                        </div>
                    </div>
                </div>';
                }
            }
        }
        // Normal kullanıcılar için sadece kendi depolarını göster
        else {
            // Kullanıcının sorumlu olduğu depoları bul
            $kendi_depolari = $DB->get_records('block_depo_yonetimi_depolar', ['sorumluid' => $USER->id]);

            if (!empty($kendi_depolari)) {
                foreach ($kendi_depolari as $depo) {
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);

                    // Depo sorumlusu bilgisini al
                    $sorumlu_ismi = 'Siz'; // Yetkili kendi deposunu görüyorsa varsayılan olarak "Siz" yazılabilir.
                    $sorumlu = $DB->get_record('user', ['id' => $depo->sorumluid]);
                    if ($sorumlu && $sorumlu->id != $USER->id) {
                        $sorumlu_ismi = $sorumlu->fullname;
                    }

                    $html .= '
                <div class="col">
                    <div class="card depo-card h-100 shadow-sm border-0">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <div class="depo-icon-container bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-warehouse text-primary"></i>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h3 class="card-title h5 mb-3">' . htmlspecialchars($depo->name) . '</h3>
                            <div class="depo-info mb-3">
                                <div class="d-flex align-items-center text-muted mb-2">
                                    <i class="fas fa-user me-2"></i>
                                    <span>Sorumlu: ' . htmlspecialchars($sorumlu_ismi) . '</span>
                                </div>
                            </div>
                            <a href="' . $url . '" class="btn btn-outline-primary mt-auto">
                                <i class="fas fa-boxes me-2"></i>Ürünleri Görüntüle
                            </a>
                        </div>
                    </div>
                </div>';
                }
            } else {
                $html .= '
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-info-circle text-info mb-3" style="font-size: 3rem;"></i>
                        <h4>Size atanmış bir depo yok</h4>
                        <p class="text-muted">Sistem yöneticinizle iletişime geçerek depo sorumluluğu talep edebilirsiniz.</p>
                    </div>
                </div>
            </div>';
            }
        }

        $html .= '
        </div>
    </div>';

        return $html;
    }
}