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

        // CSS dosyasını dahil et
        $this->page_add_styles();

        return $this->content;
    }

    private function page_add_styles() {
        global $PAGE;
        $PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css'));
        $PAGE->requires->js_call_amd('core/bootstrap', 'init');
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
            return '<div class="alert alert-danger mt-4">Yetkiniz yok.</div>';
        }

        $depoid = optional_param('depo', null, PARAM_INT);
        $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

        if ($depoid) {
            if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depoid)) {
                $templatecontext = [
                    'urunler' => [],
                    'ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]),
                    'back_url' => $PAGE->url->out(false),
                    'kategori_ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'),
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
            } else {
                return '<div class="alert alert-warning mt-4">Bu depoya erişim izniniz yok.</div>';
            }
        } else {
            $html = '<div class="dashboard-fullpage">';
            $html .= '<div class="mb-4 d-flex gap-3 flex-wrap">';
            $html .= '<a href="' . new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php') . '" class="btn btn-dashboard shadow-sm rounded-pill px-4 py-2 d-flex align-items-center">
    <i class="fa fa-plus mr-2"></i> Depo Ekle
</a>';
            $html .= '<a href="' . new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php') . '" class="btn btn-dashboard shadow-sm rounded-pill px-4 py-2 d-flex align-items-center">
    <i class="fa fa-list mr-2"></i> Kategoriler
</a>';
            $html .= '</div>';

            $html .= '<div class="depo-row-modern">';
            /* ... depo kutuları burada ... */
            $html .= '</div>';
            $html .= '</div>';

            if ($yetki === 'admin') {
                foreach ($depolar as $depo) {
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);
                    $silurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id]);

                    $html .= '
                <div class="col-md-4 mb-4">
                    <div class="card shadow-lg h-100 depo-box-modern">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title text-primary font-weight-bold mb-3" style="font-size:1.5rem;">' . htmlspecialchars($depo->name) . '</h5>
                            <div class="mt-auto">
                                <a href="' . $url . '" class="btn btn-outline-primary btn-lg w-100 mb-2">
                                    <i class="fa fa-box"></i> Ürünleri Gör
                                </a>
                                <a href="' . $silurl . '" class="btn btn-danger btn-block w-100" onclick="return confirm(\'Bu depoyu silmek istediğinize emin misiniz?\');">
                                    <i class="fa fa-trash"></i> Depoyu Sil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';
                }
            } else {
                $kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;
                if ($kendi_depoid && isset($depolar[$kendi_depoid])) {
                    $depo = $depolar[$kendi_depoid];
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);
                    $html .= '
                <div class="col-md-4 mb-4">
                    <div class="card shadow-lg h-100 depo-box-modern">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h5 class="card-title text-primary font-weight-bold mb-3" style="font-size:1.5rem;">' . htmlspecialchars($depo->name) . '</h5>
                            <div class="mt-auto">
                                <a href="' . $url . '" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fa fa-box"></i> Ürünleri Gör
                                </a>
                            </div>
                        </div>
                    </div>
                </div>';
                } else {
                    $html .= '<div class="col-12"><div class="alert alert-info">Size atanmış bir depo yok.</div></div>';
                }
            }

            $html .= '</div>';
            return $html;
        }
    }}