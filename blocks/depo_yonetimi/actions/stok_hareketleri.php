<?php
require_once('../../../config.php');
global $DB, $PAGE, $OUTPUT, $USER, $CFG;

require_once($CFG->dirroot.'/blocks/depo_yonetimi/lib.php');

$depoid = required_param('depoid', PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT);

// Sayfa ayarları
$PAGE->set_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $urunid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Stok Hareketleri');
$PAGE->set_heading('Stok Hareketleri');
$PAGE->navbar->add('Depo Yönetimi', new moodle_url('/blocks/depo_yonetimi/view.php'));
$PAGE->navbar->add('Stok Hareketleri');

// Depo bilgilerini kontrol et
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
if (!$depo) {
    redirect(new moodle_url('/blocks/depo_yonetimi/view.php'), 'Depo bulunamadı.', null, \core\output\notification::NOTIFY_ERROR);
}

// Ürün seçildiyse bilgilerini al
$urun = null;
$baslik = 'Tüm Stok Hareketleri';
if ($urunid) {
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if ($urun) {
        $baslik = htmlspecialchars($urun->name) . ' - Stok Hareketleri';
    } else {
        $urunid = 0; // Ürün bulunamadıysa filtreleme yapma
    }
}

// Filtreleme ve sıralama
$tarih_baslangic = optional_param('tarih_baslangic', 0, PARAM_INT);
$tarih_bitis = optional_param('tarih_bitis', 0, PARAM_INT);
$hareket_tipi = optional_param('hareket_tipi', '', PARAM_ALPHA);
$sira = optional_param('sira', 'tarih_desc', PARAM_TEXT);

// SQL sorgusu oluştur
$params = array('depoid' => $depoid);
$sql_where = "sh.depoid = :depoid";

if ($urunid) {
    $sql_where .= " AND sh.urunid = :urunid";
    $params['urunid'] = $urunid;
}

if ($hareket_tipi) {
    $sql_where .= " AND sh.islemtipi = :hareket_tipi";
    $params['hareket_tipi'] = $hareket_tipi;
}

// Tarih filtreleme
if ($tarih_baslangic) {
    $sql_where .= " AND sh.tarih >= :tarih_baslangic";
    $params['tarih_baslangic'] = $tarih_baslangic;
}

if ($tarih_bitis) {
    $sql_where .= " AND sh.tarih <= :tarih_bitis";
    $params['tarih_bitis'] = $tarih_bitis;
}

// Sıralama
switch ($sira) {
    case 'tarih_asc':
        $sort = "sh.tarih ASC";
        break;
    case 'tarih_desc':
        $sort = "sh.tarih DESC";
        break;
    case 'miktar_asc':
        $sort = "sh.miktar ASC";
        break;
    case 'miktar_desc':
        $sort = "sh.miktar DESC";
        break;
    default:
        $sort = "sh.tarih DESC";
}

// Stok hareketleri verilerini al
$sql = "SELECT sh.*, sh.islemtipi as hareket_tipi, u.firstname, u.lastname, ur.name as urun_adi
        FROM {block_depo_yonetimi_stok_hareketleri} sh
        JOIN {user} u ON u.id = sh.userid
        JOIN {block_depo_yonetimi_urunler} ur ON ur.id = sh.urunid
        WHERE $sql_where
        ORDER BY $sort";

$hareketler = $DB->get_records_sql($sql, $params);

// Sayfayı render et
echo $OUTPUT->header();
?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h4 class="mb-0">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    <?php echo $baslik; ?>
                </h4>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/view.php', ['depo' => $depoid]); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Depoya Dön
                </a>
            </div>
        </div>

        <div class="card-body">
            <?php if ($urunid && $urun): ?>
                <!-- Ürün Özet Bilgileri -->
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3"><?php echo htmlspecialchars($urun->name); ?></h5>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-muted me-2">Mevcut Stok:</span>
                                    <span class="badge <?php echo $urun->adet > $urun->min_stok_seviyesi ? 'bg-success' : 'bg-warning'; ?> px-3 py-2">
                                    <i class="fas <?php echo $urun->adet > $urun->min_stok_seviyesi ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-1"></i>
                                    <strong><?php echo $urun->adet; ?></strong> adet
                                </span>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="btn-group">
                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_ekle.php', ['depoid' => $depoid, 'urunid' => $urunid]); ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-1"></i> Stok Hareketi Ekle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="get" action="" id="filterForm" class="mb-4">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                <?php if ($urunid): ?>
                    <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
                <?php endif; ?>

                <div class="row align-items-end g-3">
                    <!-- Ürün Filtreleme (sadece tüm hareketler görüntüleniyorsa) -->
                    <?php if (!$urunid): ?>
                        <div class="col-md-3">
                            <label for="urun_filtre" class="form-label">Ürün</label>
                            <select name="urunid" id="urun_filtre" class="form-select">
                                <option value="">Tüm Ürünler</option>
                                <?php
                                $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid], 'name ASC');
                                foreach ($urunler as $urun_item) {
                                    echo '<option value="'.$urun_item->id.'"'.($urunid == $urun_item->id ? ' selected' : '').'>'
                                        .htmlspecialchars($urun_item->name).'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Hareket Tipi Filtreleme -->
                    <div class="col-md-3">
                        <label for="hareket_tipi" class="form-label">Hareket Tipi</label>
                        <select name="hareket_tipi" id="hareket_tipi" class="form-select">
                            <option value="">Tüm Hareketler</option>
                            <option value="giris" <?php echo $hareket_tipi == 'giris' ? 'selected' : ''; ?>>Stok Girişi</option>
                            <option value="cikis" <?php echo $hareket_tipi == 'cikis' ? 'selected' : ''; ?>>Stok Çıkışı</option>
                        </select>
                    </div>

                    <!-- Tarih Filtreleme -->
                    <div class="col-md-2">
                        <label for="tarih_baslangic" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="tarih_baslangic_str" id="tarih_baslangic" class="form-control"
                               value="<?php echo $tarih_baslangic ? date('Y-m-d', $tarih_baslangic) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="tarih_bitis" class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="tarih_bitis_str" id="tarih_bitis" class="form-control"
                               value="<?php echo $tarih_bitis ? date('Y-m-d', $tarih_bitis) : ''; ?>">
                    </div>

                    <!-- Sıralama Seçimi -->
                    <div class="col-md-2">
                        <label for="sira" class="form-label">Sıralama</label>
                        <select name="sira" id="sira" class="form-select">
                            <option value="tarih_desc" <?php echo $sira == 'tarih_desc' ? 'selected' : ''; ?>>Tarih (Yeni-Eski)</option>
                            <option value="tarih_asc" <?php echo $sira == 'tarih_asc' ? 'selected' : ''; ?>>Tarih (Eski-Yeni)</option>
                            <option value="miktar_desc" <?php echo $sira == 'miktar_desc' ? 'selected' : ''; ?>>Miktar (Çok-Az)</option>
                            <option value="miktar_asc" <?php echo $sira == 'miktar_asc' ? 'selected' : ''; ?>>Miktar (Az-Çok)</option>
                        </select>
                    </div>

                    <!-- Filtre butonu -->
                    <div class="col-md-2 d-flex">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrele
                        </button>
                    </div>
                </div>
            </form>

            <!-- Hareket tablosu -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Ürün</th>
                        <th>İşlem</th>
                        <th>Miktar</th>
                        <th>Varyasyon</th>
                        <th>İşlemi Yapan</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($hareketler)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-info-circle text-muted me-1"></i>
                                Bu kriterlere uygun stok hareketi bulunamadı.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hareketler as $hareket): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', $hareket->tarih); ?></td>
                                <td>
                                    <?php if (!$urunid): ?>
                                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri.php', ['depoid' => $depoid, 'urunid' => $hareket->urunid]); ?>">
                                            <?php echo htmlspecialchars($hareket->urun_adi); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($hareket->urun_adi); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hareket->hareket_tipi == 'giris'): ?>
                                        <span class="badge bg-success">
                                        <i class="fas fa-arrow-up me-1"></i> Giriş
                                    </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                        <i class="fas fa-arrow-down me-1"></i> Çıkış
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $hareket->miktar; ?> adet</td>
                                <td>
                                    <?php
                                    if (!empty($hareket->renk) || !empty($hareket->beden)) {
                                        $varyasyon_detay = [];

                                        // Renk JSON formatında gelebilir (["siyah"] gibi)
                                        if (!empty($hareket->renk)) {
                                            $renk = $hareket->renk;
                                            if (strpos($renk, '[') === 0) {
                                                // JSON formatındaysa parse et
                                                $renk = trim(str_replace(['"', "'", '[', ']'], '', $renk));
                                            }
                                            echo '<span class="badge me-1" style="background-color: '.getColorHex($renk).'">&nbsp;</span>';
                                            $varyasyon_detay[] = $renk;
                                        }

                                        // Beden JSON formatında gelebilir
                                        if (!empty($hareket->beden)) {
                                            $beden = $hareket->beden;
                                            if (strpos($beden, '[') === 0) {
                                                $beden = trim(str_replace(['"', "'", '[', ']'], '', $beden));
                                            }
                                            $varyasyon_detay[] = $beden;
                                        }

                                        echo implode(' / ', $varyasyon_detay);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo fullname($hareket); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Tarih filtreleri değiştiğinde Unix timestamp'e çevirme
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            const tarihBaslangic = document.getElementById('tarih_baslangic');
            const tarihBitis = document.getElementById('tarih_bitis');

            // Form gönderildiğinde tarih değerlerini Unix timestamp'e çevir
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Tarih başlangıç ve bitiş değerlerini timestamp'e çevir
                const tarihBaslangicVal = tarihBaslangic.value ? new Date(tarihBaslangic.value).getTime() / 1000 : 0;
                const tarihBitisVal = tarihBitis.value ? new Date(tarihBitis.value + 'T23:59:59').getTime() / 1000 : 0;

                // Gizli input'ları oluştur
                if (tarihBaslangicVal) {
                    const hiddenStart = document.createElement('input');
                    hiddenStart.type = 'hidden';
                    hiddenStart.name = 'tarih_baslangic';
                    hiddenStart.value = tarihBaslangicVal;
                    form.appendChild(hiddenStart);
                }

                if (tarihBitisVal) {
                    const hiddenEnd = document.createElement('input');
                    hiddenEnd.type = 'hidden';
                    hiddenEnd.name = 'tarih_bitis';
                    hiddenEnd.value = tarihBitisVal;
                    form.appendChild(hiddenEnd);
                }

                // Formu gönder
                form.submit();
            });
        });

        // Renk kodlarını al
        function getColorHex(colorName) {
            const colorMap = {
                'kirmizi': '#dc3545',
                'mavi': '#0d6efd',
                'siyah': '#212529',
                'beyaz': '#f8f9fa',
                'yesil': '#198754',
                'sari': '#ffc107',
                'turuncu': '#fd7e14',
                'mor': '#6f42c1',
                'pembe': '#d63384',
                'gri': '#6c757d',
                'bej': '#E4DAD2',
                'lacivert': '#11098A',
                'kahverengi': '#8B4513',
                'haki': '#8A9A5B',
                'vizon': '#A89F91',
                'bordo': '#800000'
            };

            return colorMap[colorName] || '#6c757d';
        }
    </script>

    <style>
        .recent-activity {
            border-left: 3px solid #0d6efd;
            padding-left: 15px;
        }

        .badge.pulse {
            position: relative;
        }

        .badge.pulse::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: inherit;
            border-radius: inherit;
            animation: pulse 1.5s infinite;
            z-index: -1;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.3);
                opacity: 0.3;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
        }
    </style>

<?php
echo $OUTPUT->footer();

// Tarih parametrelerini çevirme yardımcı fonksiyonu
function convert_date_param($date_str) {
    if (empty($date_str)) {
        return 0;
    }

    $date = DateTime::createFromFormat('Y-m-d', $date_str);
    if ($date) {
        return $date->getTimestamp();
    }
    return 0;
}
?>