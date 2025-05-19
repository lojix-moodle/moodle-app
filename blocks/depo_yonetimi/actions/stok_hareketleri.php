<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
global $DB, $USER, $PAGE, $OUTPUT, $CFG;
require_login();


$depoid = optional_param('depoid', 0, PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20; // Sayfa başına hareket sayısı

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', [
    'depoid' => $depoid,
    'urunid' => $urunid,
    'page' => $page
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketleri');
$PAGE->set_heading('Stok Hareketleri');
$PAGE->set_pagelayout('admin');

// Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin && !$is_depo_user) {
    print_error('Erişim izniniz yok.');
}

if ($depoid) {
    // Depo bilgilerini al
    $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);

    // Yetkili olmayanlar sadece kendi depolarını görebilir
    if (!$is_admin) {
        $user_depo = $DB->get_field('block_depo_yonetimi_depolar', 'id', ['sorumluid' => $USER->id, 'id' => $depoid]);
        if (!$user_depo) {
            print_error('Bu depoya erişim izniniz yok.');
        }
    }
}

// Ürün bilgilerini al (eğer belirtildiyse)
$urun = null;
if ($urunid) {
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid], '*', MUST_EXIST);
    if ($urun->depoid != $depoid) {
        print_error('Bu ürün belirtilen depoya ait değil.');
    }
}

// Stok hareketlerini al
$params = [];
$sql_where = "";

if ($depoid) {
    $params['depoid'] = $depoid;
    $sql_where .= " AND l.depoid = :depoid";
}

if ($urunid) {
    $params['urunid'] = $urunid;
    $sql_where .= " AND l.urunid = :urunid";
}

// Hareket sayısını al (sayfalama için)
$count_sql = "SELECT COUNT(*)
              FROM {block_depo_yonetimi_stok_log} l
              WHERE 1=1 $sql_where";
$total_hareketler = $DB->count_records_sql($count_sql, $params);

// Sayfalama
$offset = $page * $perpage;

// Hareketleri al
$sql = "SELECT l.*, u.name AS urun_adi, d.name AS depo_adi, 
               usr.firstname, usr.lastname, usr.email
        FROM {block_depo_yonetimi_stok_log} l
        JOIN {block_depo_yonetimi_urunler} u ON l.urunid = u.id
        JOIN {block_depo_yonetimi_depolar} d ON l.depoid = d.id
        JOIN {user} usr ON l.islem_yapan = usr.id
        WHERE 1=1 $sql_where
        ORDER BY l.islem_tarihi DESC";

$hareketler = $DB->get_records_sql($sql, $params, $offset, $perpage);

// Renk ve boyut bilgilerini göstermek için yardımcı fonksiyon
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
    }
    return $value;
}

echo $OUTPUT->header();
?>

    <style>
        /* CSS kısmını ekleyin - stok_list.php'den kopyalayabilirsiniz */
        .stock-action-tag {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            font-weight: 500;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .stock-action-tag.ekleme {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .stock-action-tag.azaltma {
            background-color: #f8d7da;
            color: #842029;
        }

        .stock-action-tag.guncelleme {
            background-color: #e2e3e5;
            color: #41464b;
        }

        .stock-difference {
            font-weight: bold;
        }

        .stock-difference.increase {
            color: #198754;
        }

        .stock-difference.decrease {
            color: #dc3545;
        }
    </style>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p class="mt-3 mb-0">İşleminiz Yapılıyor...</p>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Başlık ve Filtreleme -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-history text-primary me-2"></i>
                    <h5 class="mb-0">
                        <?php if ($urunid): ?>
                            <?php echo htmlspecialchars($urun->name); ?> Ürünü Stok Hareketleri
                        <?php elseif ($depoid): ?>
                            <?php echo htmlspecialchars($depo->name); ?> Deposu Stok Hareketleri
                        <?php else: ?>
                            Tüm Stok Hareketleri
                        <?php endif; ?>
                    </h5>
                </div>

                <?php if ($depoid): ?>
                    <a href="<?php echo new moodle_url('/my/index.php', ['depo' => $depoid]); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Depoya Dön
                    </a>
                <?php else: ?>
                    <a href="<?php echo new moodle_url('/my/index.php'); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Ana Sayfaya Dön
                    </a>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <?php if (!$depoid): ?>
                        <div class="col-md-4">
                            <label for="depoid" class="form-label">Depo</label>
                            <select name="depoid" id="depoid" class="form-select">
                                <option value="">Tüm Depolar</option>
                                <?php
                                $depolar = $DB->get_records('block_depo_yonetimi_depolar', null, 'name ASC');
                                foreach ($depolar as $d) {
                                    $selected = ($d->id == $depoid) ? 'selected' : '';
                                    echo "<option value=\"{$d->id}\" {$selected}>{$d->name}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                    <?php endif; ?>

                    <?php if (!$urunid): ?>
                        <div class="col-md-4">
                            <label for="urunid" class="form-label">Ürün</label>
                            <select name="urunid" id="urunid" class="form-select">
                                <option value="">Tüm Ürünler</option>
                                <?php
                                $where = $depoid ? ['depoid' => $depoid] : [];
                                $urunler = $DB->get_records('block_depo_yonetimi_urunler', $where, 'name ASC');
                                foreach ($urunler as $u) {
                                    $selected = ($u->id == $urunid) ? 'selected' : '';
                                    echo "<option value=\"{$u->id}\" {$selected}>{$u->name}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
                    <?php endif; ?>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filtrele
                        </button>
                        <?php if ($depoid || $urunid): ?>
                            <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php'); ?>" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Filtreleri Temizle
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stok Hareketleri Tablosu -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($hareketler)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Seçilen kriterlere uygun stok hareketi bulunamadı.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                            <tr>
                                <th>Tarih/Saat</th>
                                <th>Depo</th>
                                <th>Ürün</th>
                                <th>Varyasyon</th>
                                <th>İşlem</th>
                                <th>Değişim</th>
                                <th>İşlemi Yapan</th>
                                <th>Açıklama</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($hareketler as $hareket): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', $hareket->islem_tarihi); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo htmlspecialchars($hareket->depo_adi); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($hareket->urun_adi); ?></td>
                                    <td>
                                        <?php if ($hareket->color || $hareket->size): ?>
                                            <?php if ($hareket->color): ?>
                                                <span class="badge bg-secondary">
                                                    <?php echo get_string_from_value($hareket->color, 'color'); ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($hareket->size): ?>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo $hareket->size; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="stock-action-tag <?php echo $hareket->islem_turu; ?>">
                                            <?php
                                            if ($hareket->islem_turu == 'ekleme') echo 'Ekleme';
                                            elseif ($hareket->islem_turu == 'azaltma') echo 'Azaltma';
                                            else echo 'Güncelleme';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $fark = $hareket->yeni_miktar - $hareket->eski_miktar;
                                        $fark_class = $fark > 0 ? 'increase' : ($fark < 0 ? 'decrease' : '');
                                        $fark_isaret = $fark > 0 ? '+' : '';
                                        ?>
                                        <span class="stock-difference <?php echo $fark_class; ?>">
                                            <?php echo $hareket->eski_miktar; ?> → <?php echo $hareket->yeni_miktar; ?>
                                            (<?php echo $fark_isaret . $fark; ?>)
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo fullname($hareket); ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($hareket->aciklama) ? htmlspecialchars($hareket->aciklama) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <?php
                    $baseurl = new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', [
                        'depoid' => $depoid,
                        'urunid' => $urunid
                    ]);
                    echo $OUTPUT->paging_bar($total_hareketler, $page, $perpage, $baseurl);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // AJAX filtresi için
        document.addEventListener('DOMContentLoaded', function() {
            const depoSelect = document.getElementById('depoid');
            const urunSelect = document.getElementById('urunid');

            if (depoSelect) {
                depoSelect.addEventListener('change', function() {
                    const depoid = this.value;

                    if (depoid) {
                        // Depo seçildiğinde ürünleri filtrele
                        fetch('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/get_urunler.php?depoid=' + depoid)
                            .then(response => response.json())
                            .then(data => {
                                // Ürün seçeneğini temizle
                                urunSelect.innerHTML = '<option value="">Tüm Ürünler</option>';

                                // Yeni ürünleri ekle
                                data.forEach(urun => {
                                    const option = document.createElement('option');
                                    option.value = urun.id;
                                    option.textContent = urun.name;
                                    urunSelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error('Ürünler yüklenirken hata:', error));
                    } else {
                        // Depo seçilmediğinde tüm ürünleri göster
                        fetch('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/get_urunler.php')
                            .then(response => response.json())
                            .then(data => {
                                // Ürün seçeneğini temizle
                                urunSelect.innerHTML = '<option value="">Tüm Ürünler</option>';

                                // Tüm ürünleri ekle
                                data.forEach(urun => {
                                    const option = document.createElement('option');
                                    option.value = urun.id;
                                    option.textContent = urun.name;
                                    urunSelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error('Ürünler yüklenirken hata:', error));
                    }
                });
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>