<?php
require_once(__DIR__ . '/../../../config.php');
global $DB, $PAGE, $OUTPUT, $USER,$CFG;
require_once($CFG->libdir . '/moodlelib.php');
require_login();


// Sadece depo ID'si zorunlu olsun
$depoid = required_param('depoid', PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT); // Ürün ID'si isteğe bağlı

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri_tablosu.php', ['depoid' => $depoid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketleri');
$PAGE->set_heading('Stok Hareketleri');
$PAGE->set_pagelayout('admin');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin && $is_depo_user) {
    $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
    if (!$user_depo || $user_depo != $depoid) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('Erişim izniniz yok.', 'error');
        echo $OUTPUT->footer();
        exit;
    }
}

// Depo bilgilerini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

// SQL sorgusunu hazırla
$params = ['depoid' => $depoid];
$where = "h.depoid = :depoid";

// Eğer ürün ID'si belirtilmişse, sorguya ekle
if ($urunid) {
    $where .= " AND h.urunid = :urunid";
    $params['urunid'] = $urunid;
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid], '*');
}

$sql = "SELECT h.*, u.name as urun_adi, d.name as depo_adi,
               usr.firstname, usr.lastname
        FROM {block_depo_yonetimi_hareketler} h
        JOIN {block_depo_yonetimi_urunler} u ON h.urunid = u.id
        JOIN {block_depo_yonetimi_depolar} d ON h.depoid = d.id
        JOIN {user} usr ON h.kullanici_id = usr.id
        WHERE $where
        ORDER BY h.islem_tarihi DESC";

$hareketler = $DB->get_records_sql($sql, $params);

echo $OUTPUT->header();

// Başlık - Depo adı ve varsa ürün adı
if (isset($urun)) {
    echo $OUTPUT->heading($depo->name . ' - ' . $urun->name . ' Stok Hareketleri');
} else {
    echo $OUTPUT->heading($depo->name . ' - Stok Hareketleri');
}

// Geri dönüş butonu
echo '<div class="mb-3">
        <a href="' . new moodle_url('/blocks/depo_yonetimi/depo_detay.php', ['id' => $depoid]) . '" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Depoya Dön
        </a>
      </div>';

// Stok hareketleri tablosu
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

// Kayıt yoksa bilgi mesajı göster
if (empty($hareketler)) {
    echo '<div class="alert alert-info">Bu depoya ait stok hareketi bulunmamaktadır.</div>';
}

echo $OUTPUT->footer();
?>