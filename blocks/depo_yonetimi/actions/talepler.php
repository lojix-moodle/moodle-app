<?php
// Gerekli dosyaları dahil et
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

// Kullanıcı girişi kontrolü
require_login();
global $DB, $USER, $PAGE, $OUTPUT;

// Sayfa ayarları
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/talepler.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Ürün Talepleri');
$PAGE->set_heading('Ürün Talepleri Yönetimi');
$PAGE->set_pagelayout('standard');

// CSS ve JS ekle
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css'));
$PAGE->requires->js_call_amd('block_depo_yonetimi/talepler', 'init');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin) {
    if (!$is_depo_user)
    {
        throw new moodle_exception('Erişim izniniz yok.');
    }
}

// Filtre parametrelerini al
$filtre_depoid = optional_param('depoid', 0, PARAM_INT);
$filtre_durum = optional_param('durum', '', PARAM_TEXT);
$arama = optional_param('arama', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10; // Sayfa başına gösterilecek kayıt sayısı

// Depoları çek (filtre için)
$depolar = $DB->get_records('block_depo_yonetimi_depolar', null, 'name ASC');

// Durum filtreleme seçenekleri
$durum_secenekleri = [
    '' => 'Tümü',
    '0' => 'Beklemede',
    '1' => 'Onaylandı',
    '2' => 'Reddedildi'
];

// Filtre ve arama sorgusunu oluştur
$params = [];
$conditions = [];

if ($filtre_depoid > 0) {
    $conditions[] = 't.depoid = :depoid';
    $params['depoid'] = $filtre_depoid;
}

if ($filtre_durum !== '') {
    $conditions[] = 't.durum = :durum';
    $params['durum'] = $filtre_durum;
}

if (!empty($arama)) {
    $conditions[] = '(u.name LIKE :arama OR d.name LIKE :arama2)';
    $params['arama'] = '%' . $arama . '%';
    $params['arama2'] = '%' . $arama . '%';
}

$where = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

// Toplam kayıt sayısını al
$countsql = "SELECT COUNT(t.id) 
              FROM {block_depo_yonetimi_talepler} t
              LEFT JOIN {block_depo_yonetimi_urunler} u ON t.urunid = u.id
              LEFT JOIN {block_depo_yonetimi_depolar} d ON t.depoid = d.id
              $where";
$total = $DB->count_records_sql($countsql, $params);

// Talepleri çek
$sql = "SELECT t.*, u.name as urun_adi, d.name as depo_adi 
         FROM {block_depo_yonetimi_talepler} t
         LEFT JOIN {block_depo_yonetimi_urunler} u ON t.urunid = u.id
         LEFT JOIN {block_depo_yonetimi_depolar} d ON t.depoid = d.id
         $where
         ORDER BY t.id DESC";

$talep_records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$sorumlu_depo = $DB->get_record('block_depo_yonetimi_depolar', ['sorumluid' => $USER->id]);

$sorumluDepoUrunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $sorumlu_depo->id]);

// Durum işleme
$islem = optional_param('islem', '', PARAM_TEXT);
$talepid = optional_param('talepid', 0, PARAM_INT);

if ($islem && $talepid) {
    if ($islem === 'onayla' || $islem === 'reddet') {
        $sql = "SELECT t.*, u.name AS urun_adi
        FROM {block_depo_yonetimi_talepler} t
        LEFT JOIN {block_depo_yonetimi_urunler} u ON t.urunid = u.id
        WHERE t.id = :talepid
        LIMIT 1";

        $params = ['talepid' => $talepid];

        $talep = $DB->get_record_sql($sql, $params);
        if ($talep) {
            $talep->durum = ($islem === 'onayla') ? 1 : 2;

            if ($islem === 'onayla')
            {
                $requested_by_warehouse = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $talep->urunid]);
                $requested_by_warehouse_variants = json_decode($requested_by_warehouse->varyasyonlar, true);
                $requested_by_warehouse_variants[$talep->renk][$talep->beden] += $talep->adet;

                $responding_warehouse = $DB->get_record('block_depo_yonetimi_urunler', ['name' => $talep->urun_adi]);
                $responding_warehouse_variants = json_decode($responding_warehouse->varyasyonlar, true);
                $responding_warehouse_variants[$talep->renk][$talep->beden] -= $talep->adet;
            }

            $DB->update_record('block_depo_yonetimi_talepler', $talep);

            // Başarı mesajı
            $mesaj = ($islem === 'onayla') ? 'Talep başarıyla onaylandı.' : 'Talep reddedildi.';
            \core\notification::success($mesaj);
            redirect($PAGE->url);
        }
    }
}

function getColorHex($colorName) {
    $colorMap = [
        'kirmizi' => '#dc3545',
        'mavi' => '#0d6efd',
        'siyah' => '#212529',
        'beyaz' => '#f8f9fa',
        'yesil' => '#198754',
        'sari' => '#ffc107',
        'turuncu' => '#fd7e14',
        'mor' => '#6f42c1',
        'pembe' => '#d63384',
        'gri' => '#6c757d',
        'bej' => '#E4DAD2',
        'lacivert' => '#11098A',
        'kahverengi' => '#8B4513',
        'haki' => '#8A9A5B',
        'vizon' => '#A89F91',
        'bordo' => '#800000'
    ];

    return $colorMap[$colorName] || '#6c757d';
}

echo $OUTPUT->header();
?>

    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fa fa-list-alt mr-2"></i> Ürün Talepleri</h4>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/index.php'); ?>" class="btn btn-light btn-sm">
                    <i class="fa fa-arrow-left"></i> Geri Dön
                </a>
            </div>

            <div class="card-body">
                <!-- Filtre bölümü -->
                <div class="filters mb-4">
                    <form method="get" action="" class="form-inline">
                        <div class="row g-3 align-items-end w-100">
                            <div class="col-md-3">
                                <label for="depoid" class="form-label">Depo</label>
                                <select name="depoid" id="depoid" class="form-select">
                                    <option value="0">Tüm Depolar</option>
                                    <?php foreach ($depolar as $depo): ?>
                                        <option value="<?php echo $depo->id; ?>" <?php echo ($filtre_depoid == $depo->id) ? 'selected' : ''; ?>>
                                            <?php echo format_string($depo->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="durum" class="form-label">Durum</label>
                                <select name="durum" id="durum" class="form-select">
                                    <?php foreach ($durum_secenekleri as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo ($filtre_durum === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="arama" class="form-label">Arama</label>
                                <div class="input-group">
                                    <input type="text" id="arama" name="arama" class="form-control"
                                           value="<?php echo s($arama); ?>" placeholder="Ürün adı veya depo adı ile ara...">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fa fa-search"></i> Ara
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa fa-filter"></i> Filtrele
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tablo -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Ürün</th>
                            <th>Depo</th>
                            <th>Renk / Beden</th>
                            <th>Talep</th>
                            <th>Aktarabilirsin</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($talep_records) > 0): ?>
                            <?php foreach ($talep_records as $talep): ?>
                                <tr>
                                    <td><?php echo $talep->id; ?></td>
                                    <td><?php echo format_string($talep->urun_adi); ?></td>
                                    <td><?php echo format_string($talep->depo_adi); ?></td>
                                    <td>
                                        <span class="color-badge me-1" style="background-color: <?php echo getColorHex($talep->renk); ?>">&nbsp;</span>
                                        <?php echo format_string($talep->renk); ?> / <?php echo format_string($talep->beden); ?>
                                    </td>
                                    <td><?php echo $talep->adet; ?></td>
                                    <td>
                                        <?php
                                        foreach ($sorumluDepoUrunler as $item)
                                        {
                                            if ($item->name === $talep->urun_adi)
                                            {
                                                $renk = $talep->renk;
                                                $beden = $talep->beden;
                                                $varyasyonlar = json_decode($item->varyasyonlar, true);
                                                if (isset($varyasyonlar[$renk])) {
                                                    if (isset($varyasyonlar[$renk][$beden])) {
                                                        $adet = (int) $varyasyonlar[$renk][$beden];
                                                        if ($adet > $talep->adet) {
                                                            echo "Evet";
                                                        } else {
                                                            echo "Hayır";
                                                        }
                                                    } else {
                                                        echo "Hayır (Beden yok)";
                                                    }
                                                } else {
                                                    echo "Hayır (Renk yok)";
                                                }
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $durum_text = '';
                                        $durum_class = '';

                                        switch($talep->durum) {
                                            case '0':
                                                $durum_text = 'Beklemede';
                                                $durum_class = 'badge bg-warning';
                                                break;
                                            case '1':
                                                $durum_text = 'Onaylandı';
                                                $durum_class = 'badge bg-success';
                                                break;
                                            case '2':
                                                $durum_text = 'Reddedildi';
                                                $durum_class = 'badge bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo $durum_class; ?>"><?php echo $durum_text; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($talep->durum == '0'): ?>
                                            <div class="btn-group">
                                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/talepler.php',
                                                    ['islem' => 'onayla', 'talepid' => $talep->id]); ?>"
                                                   class="btn btn-success btn-sm"
                                                   onclick="return confirm('Bu talebi onaylamak istediğinize emin misiniz?');">
                                                    <i class="fa fa-check"></i> Onayla
                                                </a>
                                                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/talepler.php',
                                                    ['islem' => 'reddet', 'talepid' => $talep->id]); ?>"
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Bu talebi reddetmek istediğinize emin misiniz?');">
                                                    <i class="fa fa-times"></i> Reddet
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">İşlem yapılamaz</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Kayıt bulunamadı.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <div class="mt-3">
                    <?php
                    echo $OUTPUT->paging_bar($total, $page, $perpage, $PAGE->url);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .color-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid rgba(0,0,0,0.2);
            vertical-align: middle;
        }
    </style>

<?php
echo $OUTPUT->footer();