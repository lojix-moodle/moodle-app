<?php
require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('../lib.php');

// Sayfa başlığı ve yerleşimi ayarla
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urunler.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('urunler', 'block_depo_yonetimi'));
$PAGE->set_heading(get_string('urunler', 'block_depo_yonetimi'));
$PAGE->set_pagelayout('admin');

// Bootstrap ve kendi CSS'imizi ekleyelim
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/style.css', ['v' => time()]));
$PAGE->requires->js_call_amd('core/bootstrap', 'init');

// Kullanıcının giriş yapmış olduğunu kontrol et
require_login();

// Parametreleri al
$sube_id = optional_param('sube_id', 0, PARAM_INT);
$depo_id = optional_param('depo', 0, PARAM_INT); // depolar.php'den gelen depo parametresi

// Kullanıcı yetki kontrolü
$kullanici_depo_eslesme = [2 => 3, 5 => 1]; // Bu dizi, kullanıcıların yetkili olduğu depoları tanımlar

if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
    $yetki = 'admin';
} elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
    $yetki = 'depoyetkilisi';
} else {
    $yetki = 'normal';
}

// Şube bilgisini al
if ($sube_id > 0) {
    $user_branch_id = $sube_id;
} else {
    // Kullanıcının şubesini al
    if (isset($kullanici_depo_eslesme[$USER->id])) {
        $user_branch_id = $kullanici_depo_eslesme[$USER->id];
    } else {
        $user_branch_id = get_user_branch_id($USER->id);
    }
}

// Ürün ekleme işlemi
if (isset($_POST['submit'])) {
    $urun_adi = required_param('urun_adi', PARAM_TEXT);
    $miktar = required_param('miktar', PARAM_INT);
    $depo_id = required_param('depo_id', PARAM_INT);

    // Kullanıcının seçtiği deponun kendi şubesine ait olup olmadığını kontrol et
    if ($yetki === 'admin' || is_user_authorized_for_depot($USER->id, $depo_id)) {
        // Ürünü veritabanına ekle
        $record = new stdClass();
        $record->urun_adi = $urun_adi;
        $record->miktar = $miktar;
        $record->depo_id = $depo_id;
        $record->sube_id = $user_branch_id;
        $record->ekleyen_id = $USER->id;
        $record->ekleme_tarihi = time();

        $DB->insert_record('block_depo_yonetimi_urunler', $record);

        // Başarılı mesajı
        \core\notification::success(get_string('urun_eklendi', 'block_depo_yonetimi'));
    } else {
        // Yetki hatası
        \core\notification::error(get_string('yetki_hatasi', 'block_depo_yonetimi'));
    }
}

// Sayfa içeriği
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('urunler', 'block_depo_yonetimi'));

// Ürün listesi ve ekleme formu
?>
<div class="container">
    <?php if ($depo_id > 0): ?>
        <!-- Sadece belirli bir depo için ürünleri göster -->
        <div class="row mb-4">
            <div class="col-12">
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/depolar.php'); ?>" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Depolara Dön
                </a>
                <?php if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depo_id)): ?>
                    <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#urunEkleModal">
                        <i class="fa fa-plus"></i> Ürün Ekle
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <?php
                        $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depo_id]);
                        echo $depo ? "Depo: " . $depo->name : "Depo Bilgisi";
                        ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ürün Adı</th>
                                <th>Miktar</th>
                                <th>Eklenme Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Seçilen depo için ürünleri getir
                            $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depo_id]);

                            if ($urunler) {
                                foreach ($urunler as $urun) {
                                    echo '<tr>';
                                    echo '<td>'.$urun->id.'</td>';
                                    echo '<td>'.$urun->name.'</td>';
                                    echo '<td>'.$urun->adet.'</td>';
                                    echo '<td>'.userdate($urun->ekleme_tarihi ?? time(), get_string('strftimedatetime', 'core_langconfig')).'</td>';
                                    echo '<td>';

                                    if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depo_id)) {
                                        echo '<a href="urun_duzenle.php?depoid='.$depo_id.'&urunid='.$urun->id.'" class="btn btn-sm btn-info"><i class="fa fa-edit"></i></a> ';
                                        echo '<a href="urun_sil.php?depoid='.$depo_id.'&urunid='.$urun->id.'" class="btn btn-sm btn-danger" onclick="return confirm(\'Bu ürünü silmek istediğinizden emin misiniz?\');"><i class="fa fa-trash"></i></a>';
                                    }

                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">Bu depoda henüz ürün bulunmamaktadır.</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tüm ürünleri göster (şube bazlı filtreleme ile) -->
        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#urunEkleModal">
                    <i class="fa fa-plus"></i> Ürün Ekle
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ürün Adı</th>
                                <th>Miktar</th>
                                <th>Depo</th>
                                <th>Şube</th>
                                <th>Eklenme Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Kullanıcının yetkisine göre görüntülenecek ürünleri belirle
                            if ($yetki === 'admin') {
                                // Admin tüm ürünleri görebilir
                                $sql = "SELECT u.*, d.name as depo_adi, s.name as sube_adi 
                                            FROM {block_depo_yonetimi_urunler} u
                                            LEFT JOIN {block_depo_yonetimi_depolar} d ON u.depoid = d.id
                                            LEFT JOIN {block_depo_yonetimi_subeler} s ON d.subeid = s.id
                                            ORDER BY u.id DESC";
                                $params = [];
                            } else {
                                // Normal kullanıcı sadece kendi şubesindeki depolardaki ürünleri görebilir
                                $sql = "SELECT u.*, d.name as depo_adi, s.name as sube_adi 
                                            FROM {block_depo_yonetimi_urunler} u
                                            JOIN {block_depo_yonetimi_depolar} d ON u.depoid = d.id
                                            JOIN {block_depo_yonetimi_subeler} s ON d.subeid = s.id
                                            WHERE d.subeid = :sube_id
                                            ORDER BY u.id DESC";
                                $params = ['sube_id' => $user_branch_id];
                            }

                            $urunler = $DB->get_records_sql($sql, $params);

                            if ($urunler) {
                                foreach ($urunler as $urun) {
                                    echo '<tr>';
                                    echo '<td>'.$urun->id.'</td>';
                                    echo '<td>'.$urun->name.'</td>';
                                    echo '<td>'.$urun->adet.'</td>';
                                    echo '<td>'.$urun->depo_adi.'</td>';
                                    echo '<td>'.$urun->sube_adi.'</td>';
                                    echo '<td>'.userdate($urun->ekleme_tarihi ?? time(), get_string('strftimedatetime', 'core_langconfig')).'</td>';
                                    echo '<td>';

                                    // Kullanıcının yetkisi varsa düzenleme/silme butonlarını göster
                                    if ($yetki === 'admin' || ($yetki === 'depoyetkilisi' && isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $urun->depoid)) {
                                        echo '<a href="urun_duzenle.php?depoid='.$urun->depoid.'&urunid='.$urun->id.'" class="btn btn-sm btn-info"><i class="fa fa-edit"></i></a> ';
                                        echo '<a href="urun_sil.php?depoid='.$urun->depoid.'&urunid='.$urun->id.'" class="btn btn-sm btn-danger" onclick="return confirm(\'Bu ürünü silmek istediğinizden emin misiniz?\');"><i class="fa fa-trash"></i></a>';
                                    }

                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center">Henüz ürün bulunmamaktadır.</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Ürün Ekleme Modal -->
<div class="modal fade" id="urunEkleModal" tabindex="-1" role="dialog" aria-labelledby="urunEkleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="urunEkleModalLabel">Yeni Ürün Ekle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>