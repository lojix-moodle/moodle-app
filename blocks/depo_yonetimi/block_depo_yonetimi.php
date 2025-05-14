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

        // CSS dosyasını dahil et
        $PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css'));

        // JavaScript dosyalarını dahil et
        $PAGE->requires->js_call_amd('core/bootstrap', 'init');
        $PAGE->requires->js(new moodle_url('/blocks/depo_yonetimi/js/fontawesome.min.js'), true);
        $PAGE->requires->js(new moodle_url('/blocks/depo_yonetimi/js/custom.js'));
    }

    private function generate_boxes_html() {
        global $PAGE, $USER, $OUTPUT, $DB;

        $depolar = $DB->get_records('block_depo_yonetimi_depolar');
        $kullanici_depo_eslesme = [
            2 => 3,
            5 => 1,
        ];

        // Yetki kontrolü
        if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
            $yetki = 'admin';
        } elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
            $yetki = 'depoyetkilisi';
        } else {
            return $this->render_access_denied();
        }

        $depoid = optional_param('depo', null, PARAM_INT);

        // Depo seçilmişse ürünleri göster
        if ($depoid) {
            return $this->render_warehouse_products($depoid, $yetki, $kullanici_depo_eslesme);
        } else {
            return $this->render_warehouse_dashboard($depolar, $yetki, $kullanici_depo_eslesme);
        }
    }

    private function render_access_denied() {
        return '
        <div class="depo-access-denied">
            <div class="card border-danger shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-danger">Erişim Engellendi</h4>
                    <p class="mb-0">Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
                </div>
            </div>
        </div>';
    }

    private function render_warehouse_products($depoid, $yetki, $kullanici_depo_eslesme) {
        global $USER, $DB, $OUTPUT, $PAGE;

        // Yetki kontrolü
        if ($yetki !== 'admin' && (!isset($kullanici_depo_eslesme[$USER->id]) || $kullanici_depo_eslesme[$USER->id] != $depoid)) {
            return '
            <div class="alert alert-warning border-0 shadow-sm">
                <div class="d-flex">
                    <div class="mr-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Erişim Engellendi</h5>
                        <p class="mb-0">Bu depoya erişim izniniz bulunmamaktadır.</p>
                    </div>
                </div>
            </div>';
        }

        $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
        $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

        $templatecontext = [
            'depo_name' => $depo->name,
            'urunler' => [],
            'ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]),
            'back_url' => $PAGE->url->out(false),
            'kategori_ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'),
            'is_admin' => ($yetki === 'admin'),
        ];

        foreach ($urunler as $urun) {
            $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $urun->kategoriid]);
            $templatecontext['urunler'][] = [
                'kategori_name' => $kategori->name,
                'name' => $urun->name,
                'adet' => $urun->adet,
                'duzenle_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', [
                    'depoid' => $depoid,
                    'urunid' => $urun->id
                ]))->out(false),
                'sil_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', [
                    'depoid' => $depoid,
                    'urunid' => $urun->id
                ]))->out(false),
            ];
        }

        return $OUTPUT->render_from_template('block_depo_yonetimi/urun_tablo', $templatecontext);
    }

    private function render_warehouse_dashboard($depolar, $yetki, $kullanici_depo_eslesme) {
        global $USER, $PAGE;

        $html = '<div class="depo-dashboard">';

        // Üst menü bölümü
        $html .= $this->render_dashboard_header($yetki);

        // Ana içerik bölümü
        $html .= '<div class="depo-content mt-4">';

        // Depo kartları
        if ($yetki === 'admin') {
            $html .= $this->render_admin_warehouse_cards($depolar);
        } else {
            $html .= $this->render_user_warehouse_cards($depolar, $kullanici_depo_eslesme);
        }

        $html .= '</div>'; // .depo-content
        $html .= '</div>'; // .depo-dashboard

        return $html;
    }

    private function render_dashboard_header($yetki) {
        $html = '<div class="depo-header">';
        $html .= '<div class="depo-toolbar d-flex flex-wrap gap-2 mb-4">';

        // Admin için ekstra butonlar
        if ($yetki === 'admin') {
            $html .= '<a href="' . new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php') . '" 
                class="btn btn-primary btn-sm rounded-pill px-3 py-2 d-flex align-items-center">
                <i class="fas fa-plus-circle mr-2"></i> Depo Ekle
            </a>';

            $html .= '<a href="' . new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php') . '" 
                class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 d-flex align-items-center">
                <i class="fas fa-tags mr-2"></i> Kategoriler
            </a>';

            $html .= '<a href="' . new moodle_url('/blocks/depo_yonetimi/reports/index.php') . '" 
                class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 d-flex align-items-center">
                <i class="fas fa-chart-bar mr-2"></i> Raporlar
            </a>';
        }

        $html .= '</div>'; // .depo-toolbar

        // Dashboard başlık bölümü
        $html .= '<div class="depo-dashboard-header mb-4">
            <h3 class="mb-2"><i class="fas fa-warehouse text-primary mr-2"></i> Depo Yönetimi</h3>
            <p class="text-muted">Depo stok takibi ve envanter yönetimi</p>
        </div>';

        $html .= '</div>'; // .depo-header

        return $html;
    }

    private function render_admin_warehouse_cards($depolar) {
        global $PAGE;

        if (empty($depolar)) {
            return '<div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex">
                    <div class="mr-3">
                        <i class="fas fa-info-circle text-info" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Depo Bulunamadı</h5>
                        <p class="mb-0">Henüz tanımlanmış depo bulunmamaktadır. Yeni bir depo ekleyebilirsiniz.</p>
                    </div>
                </div>
            </div>';
        }

        $html = '<div class="depo-cards">';
        $html .= '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3">';

        foreach ($depolar as $depo) {
            $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);
            $silurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id]);
            $duzenleurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depo->id]);

            // Depodaki toplam ürün sayısını al
            global $DB;
            $urun_sayisi_sql = "SELECT COUNT(*) FROM {block_depo_yonetimi_urunler} WHERE depoid = :depoid";
            $urun_sayisi = $DB->count_records_sql($urun_sayisi_sql, ['depoid' => $depo->id]);

            $html .= '
            <div class="col mb-4">
                <div class="card h-100 depo-card shadow-sm border-0">
                    <div class="position-relative">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge badge-pill badge-light text-muted">
                                    <i class="fas fa-cubes mr-1"></i> ' . $urun_sayisi . ' ürün
                                </span>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-light rounded-circle" type="button" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="' . $duzenleurl . '">
                                            <i class="fas fa-edit mr-2 text-primary"></i> Düzenle
                                        </a>
                                        <a class="dropdown-item text-danger" href="' . $silurl . '" 
                                            onclick="return confirm(\'Bu depoyu silmek istediğinize emin misiniz?\');">
                                            <i class="fas fa-trash mr-2"></i> Sil
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="depo-icon-wrapper mb-3">
                                <i class="fas fa-warehouse depo-icon"></i>
                            </div>
                            <h4 class="card-title">' . htmlspecialchars($depo->name) . '</h4>
                        </div>
                        <div class="mt-auto">
                            <a href="' . $url . '" class="btn btn-primary btn-block">
                                <i class="fas fa-box-open mr-2"></i> Ürünleri Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>';
        }

        $html .= '</div>'; // .row
        $html .= '</div>'; // .depo-cards

        return $html;
    }

    private function render_user_warehouse_cards($depolar, $kullanici_depo_eslesme) {
        global $USER, $PAGE;

        $kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

        if (!$kendi_depoid || !isset($depolar[$kendi_depoid])) {
            return '<div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex">
                    <div class="mr-3">
                        <i class="fas fa-info-circle text-info" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Depo Atanmamış</h5>
                        <p class="mb-0">Size atanmış bir depo bulunmamaktadır. Yönetici ile iletişime geçebilirsiniz.</p>
                    </div>
                </div>
            </div>';
        }

        $depo = $depolar[$kendi_depoid];
        $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);

        // Depodaki toplam ürün sayısını al
        global $DB;
        $urun_sayisi_sql = "SELECT COUNT(*) FROM {block_depo_yonetimi_urunler} WHERE depoid = :depoid";
        $urun_sayisi = $DB->count_records_sql($urun_sayisi_sql, ['depoid' => $depo->id]);

        $html = '
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card depo-card shadow-sm border-0">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <span class="badge badge-pill badge-light text-muted">
                            <i class="fas fa-cubes mr-1"></i> ' . $urun_sayisi . ' ürün
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <div class="depo-icon-wrapper mb-3">
                                <i class="fas fa-warehouse depo-icon"></i>
                            </div>
                            <h4 class="card-title">' . htmlspecialchars($depo->name) . '</h4>
                            <p class="card-text text-muted">Size atanmış depo</p>
                        </div>
                        <div class="mt-auto">
                            <a href="' . $url . '" class="btn btn-primary btn-block">
                                <i class="fas fa-box-open mr-2"></i> Ürünleri Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return $html;
    }
}