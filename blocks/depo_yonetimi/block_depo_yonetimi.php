<?php

class block_depo_yonetimi extends block_base {

    public function init() {
        // Plugin başlığını ayarlıyoruz.
        $this->title = get_string('pluginname', 'block_depo_yonetimi');
    }

    public function get_content() {
        // İçeriğin zaten varsa, o içeriği geri döndürüyoruz.
        if ($this->content !== null) {
            return $this->content;
        }

        // Yeni içeriği oluşturuyoruz.
        $this->content = new stdClass();
        $this->content->text = $this->generate_boxes_html();  // Depo kutucuklarını oluştur.
        $this->content->footer = '';  // Footer boş bırakıldı, ilerleyen zamanlarda ekleme yapılabilir.

        // CSS dosyasını yüklüyoruz.
        $this->page_add_styles();

        return $this->content;
    }

    private function page_add_styles() {
        global $PAGE;

        // CSS dosyasını sayfaya dahil etme
        $PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css'));
    }

    private function generate_boxes_html() {
        global $PAGE, $OUTPUT,$USER;

        // Kullanıcının yetkilerini kontrol et
        if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
            $yetki = 'admin';
        } elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
            $yetki = 'depoyetkilisi';
        } else {
            return '<p>Yetkiniz yok.</p>';
        }

        if ($yetki == 'depo_yetkilisi') {
            $depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

            if ($depoid && isset($depolar[$depoid - 1])) {
                $depo = $depolar[$depoid - 1];
                $url = new moodle_url($PAGE->url, ['depo' => $depo['id']]);
                $html .= '<div class="depo-box">';
                $html .= "<strong>{$depo['name']}</strong><br>";
                $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a>";
                $html .= '</div>';
            } else {
                $html .= '<p>Size atanmış bir depo yok.</p>';
            }
        } else {
            // Adminse tüm depoları göster
            foreach ($depolar as $depo) {
                $url = new moodle_url($PAGE->url, ['depo' => $depo['id']]);
                $html .= '<div class="depo-box">';
                $html .= "<strong>{$depo['name']}</strong><br>";
                $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a>";
                $html .= '</div>';
            }
        }


        // Depo verileri (gerçek projede burası veritabanından alınacak)
        $depolar = [
            ['id' => 1, 'name' => 'Ankara'],
            ['id' => 2, 'name' => 'Bursa'],
            ['id' => 3, 'name' => 'Bartın'],
            ['id' => 4, 'name' => 'İstanbul'],
            ['id' => 5, 'name' => 'İzmir'],
            ['id' => 6, 'name' => 'Diyarbakır'],
        ];

        // URL'den depo parametresi alınır.
        $depoid = optional_param('depo', null, PARAM_INT);

        // HTML içeriğini oluşturuyoruz.
        $html = '<div class="depo-container" style="display: flex; flex-wrap: wrap;">';

        if ($depoid) {
            // Bir depoya tıklanmışsa, ürünleri listele.
            $urunler = [
                1 => [ ['name' => 'Laptop', 'adet' => 5], ['name' => 'Mouse', 'adet' => 10] ],
                2 => [ ['name' => 'Keyboard', 'adet' => 7], ['name' => 'Monitor', 'adet' => 3] ],
                3 => [ ['name' => 'Printer', 'adet' => 4], ['name' => 'Scanner', 'adet' => 2] ],
                4 => [ ['name' => 'Webcam', 'adet' => 6], ['name' => 'Speakers', 'adet' => 8] ],
                5 => [ ['name' => 'USB Cable', 'adet' => 15], ['name' => 'Hard Drive', 'adet' => 5] ],
                6 => [ ['name' => 'Router', 'adet' => 3], ['name' => 'Ethernet Cable', 'adet' => 12] ],
            ];

            // Ürünleri listeleme.
            foreach ($urunler[$depoid] as $urun) {
                $html .= '<div class="urun-box">';
                $html .= "<strong>{$urun['name']}</strong><br>";
                $html .= "<span>Adet: {$urun['adet']}</span>";
                $html .= '</div>';
            }

            // Depolara geri dön linki.
            $html .= '<br><a href="' . $PAGE->url->out(false) . '" class="back-link">← Depolara dön</a>';

        } else {
            // Depoları listeleme.
            foreach ($depolar as $depo) {
                $url = new moodle_url($PAGE->url, ['depo' => $depo['id']]);
                $html .= '<div class="depo-box">';
                $html .= "<strong>{$depo['name']}</strong><br>";
                $html .= "<a href='{$url}' class='depo-btn'>Ürünleri Gör</a>";
                $html .= '</div>';
            }
        }

        $html .= '</div>';  // Depo container'ı kapatma.
        return $html;
    }

}
