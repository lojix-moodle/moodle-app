<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/depo_yonetimi/locallib.php');

$depoid = required_param('depoid', PARAM_INT);

// Sayfa başlığı ve URL ayarları
$PAGE->set_url('/blocks/depo_yonetimi/actions/stok_hareketleri_tablosu.php', ['depoid' => $depoid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title("Stok Hareketleri");
$PAGE->set_heading("Stok Hareketleri");

// Depo bilgisini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// Stok hareketlerini sorgula
$sql = "SELECT h.*, u.name as urun_adi, d.name as depo_adi, 
               usr.firstname, usr.lastname
        FROM {block_depo_yonetimi_hareketler} h
        JOIN {block_depo_yonetimi_urunler} u ON h.urunid = u.id
        JOIN {block_depo_yonetimi_depolar} d ON h.depoid = d.id
        JOIN {user} usr ON h.kullanici_id = usr.id
        WHERE h.depoid = :depoid
        ORDER BY h.islem_tarihi DESC";

$hareketler = $DB->get_records_sql($sql, ['depoid' => $depoid]);

// Çıktı için hazırlık
echo $OUTPUT->header();
echo $OUTPUT->heading($depo->name . ' - Stok Hareketleri');

// Geri dönüş butonu
echo '<div class="mb-3">
        <a href="' . new moodle_url('/blocks/depo_yonetimi/depo_detay.php', ['id' => $depoid]) . '" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Depoya Dön
        </a>
      </div>';

// Hareketler tablosu
echo '<div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Ürün</th>
                    <th>Hareket</th>
                    <th>Miktar</th>
                    <th>Açıklama</th>
                    <th>İşlem Yapan</th>
                </tr>
            </thead>
            <tbody>';

foreach ($hareketler as $hareket) {
    $hareket_sinif = ($hareket->hareket_tipi == 'Giriş') ? 'text-success' : 'text-danger';
    $hareket_icon = ($hareket->hareket_tipi == 'Giriş') ? 'fa-arrow-down' : 'fa-arrow-up';

    echo '<tr>
            <td>' . userdate($hareket->islem_tarihi) . '</td>
            <td>' . $hareket->urun_adi . '</td>
            <td><span class="' . $hareket_sinif . '"><i class="fas ' . $hareket_icon . ' mr-1"></i>' . $hareket->hareket_tipi . '</span></td>
            <td>' . $hareket->miktar . '</td>
            <td>' . $hareket->aciklama . '</td>
            <td>' . $hareket->firstname . ' ' . $hareket->lastname . '</td>
          </tr>';
}

echo '</tbody></table></div>';

// Henüz kayıt yoksa bilgi mesajı göster
if (empty($hareketler)) {
    echo '<div class="alert alert-info">Bu depoya ait stok hareketi bulunmamaktadır.</div>';
}

echo $OUTPUT->footer();