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

        // 1. Depo listesi (veritabanından dinamik olarak çekiliyor)
        $depolar = $DB->get_records('block_depo_yonetimi_depolar');

        // 2. Kullanıcı-depo eşleşmeleri (sabit kalabilir ya da dinamik yapılabilir)
        $kullanici_depo_eslesme = [
            2 => 3,
            5 => 1,
        ];

        // 3. Yetki kontrolü
        if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
            $yetki = 'admin';
        } elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
            $yetki = 'depoyetkilisi';
        } else {
            return '<p>Yetkiniz yok.</p>';
        }

        // 4. URL'den depo ID al
        $depoid = optional_param('depo', null, PARAM_INT);

        // 5. Ürün listesi (şimdilik sabit, istersen bunu da veritabanından çekebiliriz)
        $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

        if ($depoid) {
            if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depoid)) {
                $templatecontext = [
                    'urunler' => [],
                    'ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]),
                    'back_url' => $PAGE->url->out(false),
                    'kategori_ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'),
                ];

                foreach ($urunler as $index => $urun) {

                    $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $urun['kategoriid']]);

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
                return '<p>Bu depoya erişim izniniz yok.</p>';
            }
        }  else {
            $html = '<div class="depo-ekle-container">';
            $html .= '<a href="' . new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php') . '" class="btn btn-primary btn-sm">+ Depo Ekle</a>';
            $html .= '</div>'; // depo-ekle-container kapatma

            $html .= '<div class="depo-listesi">'; // Yeni depo listesi container başlangıç

            // Depoları Listeleme
            if ($yetki === 'admin') {
                foreach ($depolar as $depo) {
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);
                    $silurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id]);

                    $html .= '<div class="depo-box">';
                    $html .= "<strong>{$depo->name}</strong><br>";
                    $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a><br><br>";
                    $html .= "<a href='{$silurl}' class='btn btn-danger' onclick='return confirm(\"Bu depoyu silmek istediğinize emin misiniz?\");'>Depoyu Sil</a>";
                    $html .= '</div>';
                }
            } else {
                $kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

                if ($kendi_depoid && isset($depolar[$kendi_depoid])) {
                    $depo = $depolar[$kendi_depoid];
                    $url = new moodle_url($PAGE->url, ['depo' => $depo->id]);
                    $html .= '<div class="depo-box">';
                    $html .= "<strong>{$depo->name}</strong><br>";
                    $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a>";
                    $html .= '</div>';
                } else {
                    $html .= '<p>Size atanmış bir depo yok.</p>';
                }
            }

            $html .= '</div>';
            return $html;
        }
    }
}