<?php

class block_depo_yonetimi extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_depo_yonetimi');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = $this->generate_boxes_html();
        $this->content->footer = '';

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
            return '<p>Yetkiniz yok.</p>';
        }

        $depoid = optional_param('depo', null, PARAM_INT);

        if ($depoid) {
            $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

            if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depoid)) {
                $templatecontext = [
                    'urunler' => [],
                    'ekle_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]))->out(false),
                    'back_url' => (new moodle_url('/blocks/depo_yonetimi/index.php'))->out(false),
                ];

                foreach ($urunler as $urun) {
                    $templatecontext['urunler'][] = [
                        'name' => $urun->name,
                        'adet' => $urun->adet,
                        'duzenle_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', [
                            'depoid' => $depoid,
                            'urunid' => $urun->id
                        ]))->out(false),
                        'sil_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', [
                            'depoid' => $depoid,
                            'urunid' => $urun->id,
                            'sesskey' => sesskey(),
                        ]))->out(false),
                    ];
                }

                return $OUTPUT->render_from_template('block_depo_yonetimi/urun_tablo', $templatecontext);
            } else {
                return '<p>Bu depoya erişim izniniz yok.</p>';
            }
        } else {
            $html = '';

            if ($yetki === 'admin') {
                $html .= '<div class="depo-ekle-container">';
                $html .= '<a href="' . (new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php'))->out(false) . '" class="btn btn-primary btn-sm">+ Depo Ekle</a>';
                $html .= '</div>';
            }

            // 4'lü grid sistemi burada
            $html .= '<div class="depo-grid">';

            if ($yetki === 'admin') {
                foreach ($depolar as $depo) {
                    $html .= $this->render_depo_box($depo, true);
                }
            } else {
                $kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

                if ($kendi_depoid && isset($depolar[$kendi_depoid])) {
                    $html .= $this->render_depo_box($depolar[$kendi_depoid], false);
                } else {
                    $html .= '<p>Size atanmış bir depo bulunamadı.</p>';
                }
            }

            $html .= '</div>'; // depo-grid kapatma
            return $html;
        }
    }

    private function render_depo_box($depo, $isadmin = false) {
        $depo_url = new moodle_url('/blocks/depo_yonetimi/index.php', ['depo' => $depo->id]);
        $duzenle_url = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depo->id]);
        $sil_url = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id, 'sesskey' => sesskey()]);

        $html = '<div class="depo-box">';
        $html .= '<h3>' . format_string($depo->name) . '</h3>';

        if ($isadmin) {
            $html .= '<div class="depo-icons">';
            $html .= '<a href="' . $duzenle_url . '" title="Düzenle" class="btn btn-outline-secondary btn-sm"><i class="fas fa-edit"></i></a> ';
            $html .= '<a href="' . $sil_url . '" title="Sil" class="btn btn-outline-danger btn-sm" onclick="return confirm(\'Bu depoyu silmek istediğinize emin misiniz?\');"><i class="fas fa-trash"></i></a>';
            $html .= '</div>';
        }

        $html .= '<a href="' . $depo_url . '" class="btn btn-primary btn-block mt-2">Ürünleri Gör</a>';
        $html .= '</div>';

        return $html;
    }
}
