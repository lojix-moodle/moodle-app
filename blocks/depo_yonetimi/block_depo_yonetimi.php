<?php

class block_depo_yonetimi extends block_base {

    public function init() {
        // Plugin başlığı
        $this->title = get_string('pluginname', 'block_home');
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
        $PAGE->requires->css(new moodle_url('/blocks/home/homestyle.css', ['v' => time()]));
        $PAGE->requires->js_call_amd('core/bootstrap', 'init');
    }

    private function generate_boxes_html() {
        // 5 kutunun HTML'ini oluşturuyoruz
        $html = '
        <div class="container">
            <div class="row">
                <div class="col">
                    <div class="box depo">Depolar</div>
                </div>
                <div class="col">
                    <div class="box urun">Ürünler</div>
                </div>
                <div class="col">
                    <div class="box satis">Satışlar</div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="box stok">Stok</div>
                </div>
                <div class="col">
                    <div class="box talep">Talep</div>
                </div>
                <div class="col">
                    <!-- Boş kutu, 3. satırda yer alması için -->
                </div>
            </div>
        </div>';

        return $html;
    }
}
