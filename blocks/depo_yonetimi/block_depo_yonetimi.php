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

        // Kullanıcı yetkisini kontrol et
        global $USER;
        $kullanici_depo_eslesme = [2 => 3, 5 => 1]; // Bu dizi, kullanıcıların yetkili olduğu depoları tanımlar

        // Yetki kontrolü için kullanıcının hangi şubede olduğu bilgisini ekleyin
        $sube_id = 0; // Varsayılan şube ID'si

        if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
            $yetki = 'admin';
        } elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
            $yetki = 'depoyetkilisi';
            // Kullanıcı depo yetkilisi ise, hangi şubede olduğunu belirle
            if (isset($kullanici_depo_eslesme[$USER->id])) {
                $sube_id = $kullanici_depo_eslesme[$USER->id];
            }
        } else {
            $yetki = 'normal';
        }

        // Blok içeriğini HTML olarak oluştur
        $this->content->text = '
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/depolar.php" class="box box-depo">Depolar</a>
                </div>
                <div class="col-md-4">
                    <a href="/blocks/depo_yonetimi/actions/urunler.php?sube_id=' . $sube_id . '" class="box box-urun">Ürünler</a>
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

        // İçeriği döndür
        return $this->content;
    }
}