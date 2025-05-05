<?php

class block_depo_yonetimi extends block_base {
    public function init() {
        // Blok başlığı
        $this->title = get_string('pluginname', 'block_depo_yonetimi');
    }

    public function get_content() {
        // Eğer içerik zaten var ise, onu geri döndür
        if ($this->content !== null) {
            return $this->content;
        }

        // İçeriği oluştur
        $this->content = new stdClass();

        // CSS dosyasını dahil et
        $this->page->requires->css(new moodle_url('/blocks/depo_yonetimi/homestyle.css', ['v' => time()]));
        $this->page->requires->js_call_amd('core/bootstrap', 'init');

        // Blok içeriğini HTML olarak oluştur
        $this->content->text = '
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/depolar.php" class="box box-depo">Depolar</a>
                </div>
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/urunler.php" class="box box-urun">Ürünler</a>
                </div>
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/satislar.php" class="box box-satis">Satışlar</a>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/stok.php" class="box box-stok">Stok</a>
                </div>
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/talep.php" class="box box-talep">Talep</a>
                </div>
                <div class="col-md-4">
                    <!-- boş kutu -->
                </div>
            </div>
        </div>';

        // Footer kısmı kaldırıldı

        // İçeriği döndür
        return $this->content;
    }
}
