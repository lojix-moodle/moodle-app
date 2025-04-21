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
    }

    private function generate_boxes_html() {
        global $PAGE, $USER;

        // 1. Depo listesi
        $depolar = [
            ['id' => 1, 'name' => 'Ankara'],
            ['id' => 2, 'name' => 'Bursa'],
            ['id' => 3, 'name' => 'Bartın'],
            ['id' => 4, 'name' => 'İstanbul'],
            ['id' => 5, 'name' => 'İzmir'],
            ['id' => 6, 'name' => 'Diyarbakır'],
        ];

        // 2. Kullanıcı-depo eşleşmeleri (ileride veritabanı bağlantısı yapılabilir)
        $kullanici_depo_eslesme = [
            2 => 3, // Kullanıcı ID 2 => Bartın
            5 => 1, // Kullanıcı ID 5 => Ankara
        ];

        // 3. Yetki kontrolü
        $context = context_block::instance($this->instance->id);

        if (has_capability('block/depo_yonetimi:viewall', $context)) {
            $yetki = 'admin';
        } elseif (has_capability('block/depo_yonetimi:viewown', $context)) {
            $yetki = 'depoyetkilisi';
        } else {
            return '<p>Yetkiniz yok.</p>';
        }

        // 4. URL'den depo ID al
        $depoid = optional_param('depo', null, PARAM_INT);

        // 5. HTML başlat
        $html = '<div class="depo-container" style="display: flex; flex-wrap: wrap;">';

        // 6. Ürün listesi
        $urunler = [
            1 => [ ['name' => 'Laptop', 'adet' => 5], ['name' => 'Mouse', 'adet' => 10] ],
            2 => [ ['name' => 'Keyboard', 'adet' => 7], ['name' => 'Monitor', 'adet' => 3] ],
            3 => [ ['name' => 'Printer', 'adet' => 4], ['name' => 'Scanner', 'adet' => 2] ],
            4 => [ ['name' => 'Webcam', 'adet' => 6], ['name' => 'Speakers', 'adet' => 8] ],
            5 => [ ['name' => 'USB Cable', 'adet' => 15], ['name' => 'Hard Drive', 'adet' => 5] ],
            6 => [ ['name' => 'Router', 'adet' => 3], ['name' => 'Ethernet Cable', 'adet' => 12] ],
        ];

        // 7. Depo yetkilisi ise sadece kendi deposunu görür
        if ($yetki == 'depoyetkilisi') {
            $kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

            if ($depoid) {
                if ($depoid == $kendi_depoid) {
                    foreach ($urunler[$depoid] as $urun) {
                        $html .= '<div class="urun-box">';
                        $html .= "<strong>{$urun['name']}</strong><br>";
                        $html .= "<span>Adet: {$urun['adet']}</span>";
                        $html .= '</div>';
                    }
                    $html .= '<br><a href="' . $PAGE->url->out(false) . '" class="back-link">← Depolara dön</a>';
                } else {
                    $html .= '<p>Bu depoya erişim izniniz yok.</p>';
                }
            } elseif ($kendi_depoid) {
                $depo = $depolar[$kendi_depoid - 1];
                $url = new moodle_url($PAGE->url, ['depo' => $depo['id']]);
                $html .= '<div class="depo-box">';
                $html .= "<strong>{$depo['name']}</strong><br>";
                $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a>";
                $html .= '</div>';
            } else {
                $html .= '<p>Size atanmış bir depo yok.</p>';
            }

        } else {
            // Admin tüm depoları görür
            if ($depoid) {
                foreach ($urunler[$depoid] as $urun) {
                    $html .= '<div class="urun-box">';
                    $html .= "<strong>{$urun['name']}</strong><br>";
                    $html .= "<span>Adet: {$urun['adet']}</span>";
                    $html .= '</div>';
                }
                $html .= '<br><a href="' . $PAGE->url->out(false) . '" class="back-link">← Depolara dön</a>';
            } else {
                foreach ($depolar as $depo) {
                    $url = new moodle_url($PAGE->url, ['depo' => $depo['id']]);
                    $html .= '<div class="depo-box">';
                    $html .= "<strong>{$depo['name']}</strong><br>";
                    $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a>";
                    $html .= '</div>';
                }
            }
        }

        $html .= '</div>'; // .depo-container kapanışı
        return $html;
    }
}
