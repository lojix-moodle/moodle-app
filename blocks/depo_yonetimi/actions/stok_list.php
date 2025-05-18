<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stoklar');
$PAGE->set_heading('Stoklar');

// Yetkilendirme kontrolleri
if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
    !has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
    redirect(new moodle_url('/my'), 'Bu sayfayı görüntüleme yetkiniz bulunmuyor.', null, \core\output\notification::NOTIFY_ERROR);
}

// Admin mi yoksa depo yetkilisi mi?
$is_admin = has_capability('block/depo_yonetimi:viewall', context_system::instance());

// Paginasyon için değişkenler
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20; // Sayfa başına varyasyon sayısı

// Filtreleme için parametreler
$kategori_id = optional_param('kategori', 0, PARAM_INT);
$depo_id = optional_param('depo', 0, PARAM_INT);
$arama = optional_param('arama', '', PARAM_TEXT);

// Sorgu koşullarını hazırla
$conditions = array('is_parent' => 0); // Sadece varyasyonları getir
$params = array();

if ($depo_id) {
    $conditions['depoid'] = $depo_id;
}

if ($kategori_id) {
    $conditions['kategoriid'] = $kategori_id;
}

// Arama filtresini ekle
if ($arama) {
    $like_param = $DB->sql_like('name', ':name');
    $where_sql = "$like_param";
    $params['name'] = '%' . $DB->sql_like_escape($arama) . '%';
} else {
    $where_sql = '1=1';
}

// Sadece yetkilisi olduğu depolar için sorgu (admin değilse)
if (!$is_admin) {
    $user_depos = $DB->get_records('block_depo_yonetimi_depolar', ['sorumluid' => $USER->id], '', 'id');
    if (empty($user_depos)) {
        $conditions['depoid'] = -1; // Hiçbir kayıt gelmemesi için geçersiz ID
    } else {
        $depo_ids = array_keys($user_depos);
        list($depo_sql, $depo_params) = $DB->get_in_or_equal($depo_ids);
        $where_sql .= " AND depoid $depo_sql";
        $params = array_merge($params, $depo_params);
    }
}

// SQL koşullarını oluştur
$conditions_sql = [];
foreach ($conditions as $field => $value) {
    $conditions_sql[] = "$field = :$field";
    $params[$field] = $value;
}

// Toplam kayıt sayısını al
$count_sql = "SELECT COUNT(*) FROM {block_depo_yonetimi_urunler} 
              WHERE " . implode(' AND ', $conditions_sql) . " AND $where_sql";
$total_records = $DB->count_records_sql($count_sql, $params);

// Varyasyonları al (sayfalama ile)
$sql = "SELECT u.* FROM {block_depo_yonetimi_urunler} u
        WHERE " . implode(' AND ', $conditions_sql) . " AND $where_sql
        ORDER BY u.timemodified DESC";
$varyasyonlar = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Sayfalama nesnesi
$baseurl = new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', [
    'kategori' => $kategori_id,
    'depo' => $depo_id,
    'arama' => $arama
]);
$pagingbar = new paging_bar($total_records, $page, $perpage, $baseurl);

// Kategorileri al
$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler', null, 'name ASC');

// Depoları al
if ($is_admin) {
    $depolar = $DB->get_records('block_depo_yonetimi_depolar', null, 'name ASC');
} else {
    $depolar = $DB->get_records('block_depo_yonetimi_depolar', ['sorumluid' => $USER->id], 'name ASC');
}

echo $OUTPUT->header();
?>

    <style>
        /* Stil kodları buraya eklenebilir */
        .stock-badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 20px;
        }
        .stock-badge.high {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .stock-badge.medium {
            background-color: #fff3cd;
            color: #664d03;
        }
        .stock-badge.low {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Header bölümü -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h2 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-boxes text-primary me-3"></i>
                    Stok Yönetimi
                </h2>
                <p class="text-muted">Tüm ürün varyasyonlarını ve stok durumlarını görüntüleyin</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-outline-secondary rounded-pill">
                    <i class="fas fa-arrow-left me-2"></i>Ana Sayfaya Dön
                </a>
            </div>
        </div>

        <!-- Filtre kartı -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="<?php echo $PAGE->url; ?>" class="row g-3">
                    <!-- Arama kutusu -->
                    <div class="col-lg-4">
                        <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                            <input type="text" name="arama" class="form-control border-start-0"
                                   placeholder="Ürün ara..." value="<?php echo $arama; ?>">
                        </div>
                    </div>

                    <!-- Kategori seçimi -->
                    <div class="col-lg-3">
                        <select name="kategori" class="form-select">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach($kategoriler as $kategori): ?>
                                <option value="<?php echo $kategori->id; ?>"
                                    <?php echo ($kategori_id == $kategori->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kategori->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Depo seçimi -->
                    <div class="col-lg-3">
                        <select name="depo" class="form-select">
                            <option value="">Tüm Depolar</option>
                            <?php foreach($depolar as $depo): ?>
                                <option value="<?php echo $depo->id; ?>"
                                    <?php echo ($depo_id == $depo->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($depo->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Arama butonu -->
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stok tablosu -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($varyasyonlar)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Varyasyon Bulunamadı</h4>
                        <p class="text-muted">Seçilen filtrelere göre herhangi bir varyasyon bulunmamaktadır.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th class="ps-4">Ürün Adı</th>
                                <th>Kategori</th>
                                <th>Depo</th>
                                <th>Renk</th>
                                <th>Boyut</th>
                                <th class="text-center">Stok Miktarı</th>
                                <th class="text-end pe-4">İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($varyasyonlar as $varyasyon):
                                // Ana ürünü al
                                $ana_urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $varyasyon->parent_id]);

                                // Kategori bilgisi
                                $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $varyasyon->kategoriid]);
                                $kategori_adi = $kategori ? $kategori->name : 'Kategorisiz';

                                // Depo bilgisi
                                $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $varyasyon->depoid]);
                                $depo_adi = $depo ? $depo->name : 'Bilinmiyor';

                                // Stok durumuna göre sınıf belirleme
                                $stok_class = '';
                                if ($varyasyon->adet > 10) {
                                    $stok_class = 'high';
                                } else if ($varyasyon->adet > 3) {
                                    $stok_class = 'medium';
                                } else {
                                    $stok_class = 'low';
                                }
                                ?>
                                <tr>
                                    <td class="ps-4 align-middle">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-box text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($varyasyon->name); ?></strong>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                <span class="badge bg-light text-dark border">
                                    <?php echo htmlspecialchars($kategori_adi); ?>
                                </span>
                                    </td>
                                    <td class="align-middle">
                                <span class="badge bg-light text-dark border">
                                    <?php echo htmlspecialchars($depo_adi); ?>
                                </span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if (!empty($varyasyon->color)): ?>
                                            <div class="d-flex align-items-center">
                                    <span class="badge rounded-circle me-2" style="background-color: <?php
                                    echo get_color_hex($varyasyon->color);
                                    ?>; width: 15px; height: 15px;"></span>
                                                <?php echo get_string_from_value($varyasyon->color, 'color'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php if (!empty($varyasyon->size)): ?>
                                            <?php echo get_string_from_value($varyasyon->size, 'size'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                <span class="stock-badge <?php echo $stok_class; ?>">
                                    <?php echo $varyasyon->adet; ?> adet
                                </span>
                                    </td>
                                    <td class="text-end pe-4 align-middle">
                                        <div class="btn-group">
                                            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_duzenle.php', [
                                                'depoid' => $varyasyon->depoid,
                                                'urunid' => $varyasyon->id
                                            ]); ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <div class="p-3">
                        <?php echo $OUTPUT->render($pagingbar); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
// Renk kodlarını alma yardımcı fonksiyonu
function get_color_hex($colorName) {
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

    return isset($colorMap[$colorName]) ? $colorMap[$colorName] : '#6c757d';
}

// Renk ve boyut metin gösterimini alma yardımcı fonksiyonu
function get_string_from_value($value, $type) {
    if ($type == 'color') {
        $colors = [
            'kirmizi' => 'Kırmızı',
            'mavi' => 'Mavi',
            'siyah' => 'Siyah',
            'beyaz' => 'Beyaz',
            'yesil' => 'Yeşil',
            'sari' => 'Sarı',
            'turuncu' => 'Turuncu',
            'mor' => 'Mor',
            'pembe' => 'Pembe',
            'gri' => 'Gri',
            'bej' => 'Bej',
            'lacivert' => 'Lacivert',
            'kahverengi' => 'Kahverengi',
            'haki' => 'Haki',
            'vizon' => 'Vizon',
            'bordo' => 'Bordo'
        ];
        return isset($colors[$value]) ? $colors[$value] : $value;
    } else if ($type == 'size') {
        $sizes = [
            '17' => '17',
            '18' => '18',
            '19' => '19',
            '20' => '20',
            '21' => '21',
            '22' => '22',
            '23' => '23',
            '24' => '24',
            '25' => '25',
            '26' => '26',
            '27' => '27',
            '28' => '28',
            '29' => '29',
            '30' => '30',
            '31' => '31',
            '32' => '32',
            '33' => '33',
            '34' => '34',
            '35' => '35',
            '36' => '36',
            '37' => '37',
            '38' => '38',
            '39' => '39',
            '40' => '40',
            '41' => '41',
            '42' => '42',
            '43' => '43',
            '44' => '44',
            '45' => '45',
            // Diğer boyutlar burada eklenebilir
        ];
        return isset($sizes[$value]) ? $sizes[$value] : $value;
    }
    return $value;
}

echo $OUTPUT->footer();
?>