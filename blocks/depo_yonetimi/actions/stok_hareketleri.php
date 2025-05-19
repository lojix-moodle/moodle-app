<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER ,$CFG;

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
        $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
        if (!$user_depo || $user_depo != $depoid) {
            print_error('Bu depoya erişim izniniz yok.');
        }
    }
}

// Ürün bilgilerini al (eğer belirtildiyse)
$urun = null;
if ($urunid) {
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid], '*');
    if (!$urun) {
        print_error('Ürün bulunamadı.');
    }

    if ($depoid && $urun->depoid != $depoid) {
        print_error('Bu ürün belirtilen depoya ait değil.');
    }

    // Depoid belirlenmemişse, ürünün depo ID'sini kullan
    if (!$depoid) {
        $depoid = $urun->depoid;
        $depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid], '*', MUST_EXIST);
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
               usr.firstname, usr.lastname
        FROM {block_depo_yonetimi_stok_log} l
        JOIN {block_depo_yonetimi_urunler} u ON l.urunid = u.id
        JOIN {block_depo_yonetimi_depolar} d ON l.depoid = d.id
        JOIN {user} usr ON l.islem_yapan = usr.id
        WHERE 1=1 $sql_where
        ORDER BY l.islem_tarihi DESC";

$hareketler = $DB->get_records_sql($sql, $params, $offset, $perpage);

// Renk ve boyut bilgilerini göstermek için yardımcı fonksiyon
function get_hareket_string_from_value($value, $type) {
    if ($type == 'color') {
        $colors = [
            'siyah' => 'Siyah',
            'beyaz' => 'Beyaz',
            'kirmizi' => 'Kırmızı',
            'mavi' => 'Mavi',
            'yesil' => 'Yeşil',
            'sari' => 'Sarı',
            'turuncu' => 'Turuncu',
            'mor' => 'Mor',
            'pembe' => 'Pembe',
            'gri' => 'Gri',
            'kahverengi' => 'Kahverengi',
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
            'xxl' => 'XXL'
        ];
        return isset($sizes[$value]) ? $sizes[$value] : $value;
    }
    return $value;
}

echo $OUTPUT->header();
?>

    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner-container {
            text-align: center;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

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

        .color-sample {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }

        .siyah { background-color: #000; }
        .beyaz { background-color: #fff; border: 1px solid #ddd; }
        .kirmizi { background-color: #dc3545; }
        .mavi { background-color: #0d6efd; }
        .yesil { background-color: #198754; }
        .sari { background-color: #ffc107; }
        .turuncu { background-color: #fd7e14; }
        .mor { background-color: #6f42c1; }
        .pembe { background-color: #d63384; }
        .gri { background-color: #6c757d; }
        .kahverengi { background-color: #8b4513; }
        .bordo { background-color: #800000; }
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
                    <h3 class="mb-0">
                        <i class="fas fa-history text-primary me-2"></i>
                        Stok Hareketleri
                    </h3>
                </div>

                <div>
                    <?php if ($depoid && $urunid): ?>
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_list.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-box"></i> Stok Sayfasına Dön
                        </a>
                    <?php elseif ($depoid): ?>
                        <a href="<?php echo new moodle_url('/my', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-warehouse"></i> Depoya Dön
                        </a>
                    <?php else: ?>
                        <a href="<?php echo new moodle_url('/my'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-home"></i> Ana Sayfaya Dön
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="depoid" class="form-label">Depo</label>
                        <select name="depoid" id="depoid" class="form-select">
                            <option value="">Tüm Depolar</option>
                            <?php
                            $tum_depolar = $DB->get_records('block_depo_yonetimi_depolar', null, 'name ASC');
                            foreach ($tum_depolar as $d) {
                                $selected = ($depoid == $d->id) ? 'selected' : '';
                                echo "<option value='{$d->id}' $selected>{$d->name}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="urunid" class="form-label">Ürün</label>
                        <select name="urunid" id="urunid" class="form-select">
                            <option value="">Tüm Ürünler</option>
                            <?php
                            if ($depoid) {
                                $depo_urunleri = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid], 'name ASC');
                                foreach ($depo_urunleri as $u) {
                                    $selected = ($urunid == $u->id) ? 'selected' : '';
                                    echo "<option value='{$u->id}' $selected>{$u->name}</option>";
                                }
                            } elseif ($urunid) {
                                echo "<option value='{$urun->id}' selected>{$urun->name}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stok Hareketleri Tablosu -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($hareketler)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Kayıtlı stok hareketi bulunamadı.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Depo</th>
                                <th>Ürün</th>
                                <th>Varyasyon</th>
                                <th>İşlem</th>
                                <th>Miktar Değişimi</th>
                                <th>İşlemi Yapan</th>
                                <th>Açıklama</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($hareketler as $hareket): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', $hareket->islem_tarihi); ?></td>
                                    <td><?php echo $hareket->depo_adi; ?></td>
                                    <td><?php echo $hareket->urun_adi; ?></td>
                                    <td>
                                        <?php if ($hareket->color && $hareket->size): ?>
                                            <span class="color-sample <?php echo $hareket->color; ?>"></span>
                                            <?php echo get_hareket_string_from_value($hareket->color, 'color') . ' / ' .
                                                get_hareket_string_from_value($hareket->size, 'size'); ?>
                                        <?php elseif ($hareket->color): ?>
                                            <span class="color-sample <?php echo $hareket->color; ?>"></span>
                                            <?php echo get_hareket_string_from_value($hareket->color, 'color'); ?>
                                        <?php elseif ($hareket->size): ?>
                                            <?php echo get_hareket_string_from_value($hareket->size, 'size'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="stock-action-tag <?php echo $hareket->islem_turu; ?>">
                                            <?php
                                            $islem_metni = '';
                                            switch ($hareket->islem_turu) {
                                                case 'ekleme':
                                                    $islem_metni = 'Ekleme';
                                                    break;
                                                case 'azaltma':
                                                    $islem_metni = 'Azaltma';
                                                    break;
                                                case 'guncelleme':
                                                    $islem_metni = 'Güncelleme';
                                                    break;
                                                default:
                                                    $islem_metni = ucfirst($hareket->islem_turu);
                                            }
                                            echo $islem_metni;
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $fark = $hareket->yeni_miktar - $hareket->eski_miktar;
                                        $class = ($fark > 0) ? 'increase' : (($fark < 0) ? 'decrease' : '');
                                        $isaret = ($fark > 0) ? '+' : '';
                                        echo "<span class='stock-difference {$class}'>{$hareket->eski_miktar} → {$hareket->yeni_miktar} ({$isaret}{$fark})</span>";
                                        ?>
                                    </td>
                                    <td><?php echo $hareket->firstname . ' ' . $hareket->lastname; ?></td>
                                    <td><?php echo $hareket->aciklama ?: '<span class="text-muted">-</span>'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <?php
                    if ($total_hareketler > $perpage) {
                        echo $OUTPUT->paging_bar($total_hareketler, $page, $perpage, $PAGE->url);
                    }
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const depoSelect = document.getElementById('depoid');
            const urunSelect = document.getElementById('urunid');

            if (depoSelect) {
                depoSelect.addEventListener('change', function() {
                    // Depo değiştiğinde ürün listesini güncelle
                    const depoid = this.value;
                    urunSelect.innerHTML = '<option value="">Tüm Ürünler</option>';

                    if (depoid) {
                        // AJAX isteği ile ürünleri getir
                        fetch('<?php echo $CFG->wwwroot; ?>/blocks/depo_yonetimi/ajax/get_urunler.php?depoid=' + depoid)
                            .then(response => response.json())
                            .then(data => {
                                data.forEach(urun => {
                                    const option = document.createElement('option');
                                    option.value = urun.id;
                                    option.textContent = urun.name;
                                    urunSelect.appendChild(option);
                                });
                            });
                    }
                });
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>