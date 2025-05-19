<?php
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php'); // clean_array() fonksiyonu için
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stoklar');
$PAGE->set_heading('Stoklar');

// Yetkilendirme kontrolleri
if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
    !has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
    throw new moodle_exception('accessdenied', 'admin');
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
    $conditions[] = $DB->sql_like('u.name', ':name', false); // 'u.name' olarak düzeltildi
    $params['name'] = '%' . $DB->sql_like_escape($arama) . '%';
}

// Sadece yetkilisi olduğu depolar için sorgu (admin değilse)
if (!$is_admin) {
    $depolar = $DB->get_fieldset_select('block_depo_yonetimi_depolar', 'id', 'sorumluid = :userid', ['userid' => $USER->id]);

    // Düzeltme: Eğer $depolar boş değilse, clean_array() ile temizlenmelidir
    if (!empty($depolar)) {
        $depolar = clean_array($depolar, PARAM_INT);
    }

    $conditions['depoid'] = $depolar;
    if (empty($conditions['depoid'])) {
        $conditions['depoid'] = 0; // Hiçbir depo yoksa, hiçbir sonuç getirme
    }
}

// SQL koşullarını oluştur
$conditions_sql = [];
foreach ($conditions as $field => $value) {
    if (is_array($value)) {
        if (!empty($value)) {
            list($in_sql, $in_params) = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED, 'param');
            $conditions_sql[] = "u.$field $in_sql";
            $params = array_merge($params, $in_params);
        }
    } else if (is_string($field) && strpos($field, '.') === false) {
        $paramname = 'param' . count($params);
        $conditions_sql[] = "u.$field = :$paramname";
        $params[$paramname] = $value;
    } else {
        $conditions_sql[] = $field;
    }
}

$where = implode(' AND ', $conditions_sql);

// Toplam kayıt sayısını al
$count_sql = "SELECT COUNT(*) FROM {block_depo_yonetimi_urunler} u WHERE $where";
$total_records = $DB->count_records_sql($count_sql, $params);

// Varyasyonları al (sayfalama ile)
$sql = "SELECT u.* FROM {block_depo_yonetimi_urunler} u WHERE $where ORDER BY u.name ASC";
$varyasyonlar = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Sayfalama nesnesi
$baseurl = new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', [
    'kategori' => $kategori_id,
    'depo' => $depo_id,
    'arama' => $arama
]);

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
        .stock-table th, .stock-table td {
            vertical-align: middle;
        }
        .stock-low {
            background-color: #fef1f1;
            color: #dc3545;
        }
        .stock-medium {
            background-color: #fff8e6;
            color: #ffc107;
        }
        .stock-good {
            background-color: #edfdf5;
            color: #198754;
        }
        .stock-tag {
            display: inline-block;
            padding: 0.25em 0.8em;
            font-size: 0.85em;
            font-weight: 700;
            border-radius: 0.25rem;
            text-align: center;
            white-space: nowrap;
        }
        .filters {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-boxes me-2 text-primary"></i>Stok Durumu
                <small class="text-muted d-block mt-1">Tüm ürün varyasyonlarının stok bilgileri</small>
            </h2>

            <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Ana Sayfaya Dön
            </a>
        </div>

        <!-- Filtre Bölümü -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="get" action="<?php echo $PAGE->url; ?>" class="row g-3">
                    <div class="col-md-4">
                        <label for="depo" class="form-label">Depo:</label>
                        <select name="depo" id="depo" class="form-select">
                            <option value="0">Tüm Depolar</option>
                            <?php foreach ($depolar as $depo): ?>
                                <option value="<?php echo $depo->id; ?>" <?php echo $depo_id == $depo->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($depo->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kategori" class="form-label">Kategori:</label>
                        <select name="kategori" id="kategori" class="form-select">
                            <option value="0">Tüm Kategoriler</option>
                            <?php foreach ($kategoriler as $kategori): ?>
                                <option value="<?php echo $kategori->id; ?>" <?php echo $kategori_id == $kategori->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kategori->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="arama" class="form-label">Ürün Ara:</label>
                        <div class="input-group">
                            <input type="text" name="arama" id="arama" class="form-control" placeholder="Ürün adı..." value="<?php echo htmlspecialchars($arama); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Ara
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        // Varyasyon verileri hazırla
        $template_data = new stdClass();
        $template_data->varyasyonlar = [];

        foreach ($varyasyonlar as $varyasyon) {
            $item = new stdClass();

            // Ana ürünü bul
            $parent = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $varyasyon->parent_id]);
            $item->parent_name = $parent ? $parent->name : '';

            // Kategori bilgisi
            $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $varyasyon->kategoriid]);
            $item->kategori_adi = $kategori ? $kategori->name : 'Kategorisiz';

            // Depo bilgisi
            $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $varyasyon->depoid]);
            $item->depo_adi = $depo ? $depo->name : 'Bilinmeyen Depo';

            // Varyasyon bilgileri
            $item->id = $varyasyon->id;
            $item->name = $varyasyon->name;

            // Renk ve boyut
            $color_text = !empty($varyasyon->color) ? get_string_from_value($varyasyon->color, 'color') : '';
            $size_text = !empty($varyasyon->size) ? get_string_from_value($varyasyon->size, 'size') : '';
            $item->color = $color_text;
            $item->size = $size_text;

            // Stok durumu
            $item->adet = $varyasyon->adet;
            if ($varyasyon->adet > 10) {
                $item->stok_class = 'stock-good';
                $item->stok_durum = 'İyi';
            } else if ($varyasyon->adet > 3) {
                $item->stok_class = 'stock-medium';
                $item->stok_durum = 'Orta';
            } else {
                $item->stok_class = 'stock-low';
                $item->stok_durum = 'Kritik';
            }

            // URL'ler
            $item->duzenle_url = (new moodle_url('/blocks/depo_yonetimi/actions/stok_duzenle.php', [
                'depoid' => $varyasyon->depoid,
                'urunid' => $varyasyon->id
            ]))->out(false);

            $template_data->varyasyonlar[] = $item;
        }

        // Sayfalama için
        $template_data->has_pagination = $total_records > $perpage;
        if ($template_data->has_pagination) {
            $pagingbar = new paging_bar($total_records, $page, $perpage, $baseurl);
            $template_data->pagination_html = $OUTPUT->render($pagingbar);
        }

        // Şablonu render et
        echo $OUTPUT->render_from_template('block_depo_yonetimi/stok_tablo', $template_data);
        ?>
    </div>

<?php
// Renk ve boyutlar için etiket fonksiyonu
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
            'xs' => 'XS',
            's' => 'S',
            'm' => 'M',
            'l' => 'L',
            'xl' => 'XL',
            'xxl' => 'XXL',
            'xxxl' => 'XXXL',
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
            '45' => '45'
        ];
        return isset($sizes[$value]) ? $sizes[$value] : $value;
    }
    return $value;
}

echo $OUTPUT->footer();
?>
