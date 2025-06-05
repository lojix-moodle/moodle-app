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
    $sql_where .= " AND sh.hareket_tipi = :hareket_tipi";
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
$sql_where = "ur.depoid = :depoid"; // sh.depoid yerine ur.depoid kullanılıyor

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
            <form method="get" action="" id="filterForm" class="mb-4">
                <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">
                <?php if ($urunid): ?>
                    <input type="hidden" name="urunid" value="<?php echo $urunid; ?>">
                <?php endif; ?>

                <div class="row align-items-end g-3">
                    <!-- Ürün Filtreleme (sadece tüm hareketler görüntüleniyorsa) -->
                    <?php if (!$urunid): ?>
                        <div class="col-md-3">
                            <label for="urun-filtre" class="form-label">Ürün</label>
                            <select id="urun-filtre" name="urunid" class="form-select">
                                <option value="">Tüm Ürünler</option>
                                <?php
                                $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid], 'name ASC');
                                foreach ($urunler as $u) {
                                    echo '<option value="' . $u->id . '">' . htmlspecialchars($u->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Hareket Tipi Filtreleme -->
                    <div class="col-md-3">
                        <label for="hareket-filtre" class="form-label">Hareket Tipi</label>
                        <select id="hareket-filtre" name="hareket_tipi" class="form-select">
                            <option value="">Tümü</option>
                            <option value="giris" <?php echo $hareket_tipi === 'giris' ? 'selected' : ''; ?>>Stok Girişi</option>
                            <option value="cikis" <?php echo $hareket_tipi === 'cikis' ? 'selected' : ''; ?>>Stok Çıkışı</option>
                        </select>
                    </div>

                    <!-- Tarih Filtreleme -->
                    <div class="col-md-2">
                        <label for="tarih-baslangic" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" id="tarih-baslangic" name="tarih_baslangic" class="form-control"
                               value="<?php echo $tarih_baslangic ? date('Y-m-d', $tarih_baslangic) : ''; ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="tarih-bitis" class="form-label">Bitiş Tarihi</label>
                        <input type="date" id="tarih-bitis" name="tarih_bitis" class="form-control"
                               value="<?php echo $tarih_bitis ? date('Y-m-d', $tarih_bitis) : ''; ?>">
                    </div>

                    <!-- Sıralama Seçimi -->
                    <div class="col-md-2">
                        <label for="sira-filtre" class="form-label">Sıralama</label>
                        <select id="sira-filtre" name="sira" class="form-select">
                            <option value="tarih_desc" <?php echo $sira === 'tarih_desc' ? 'selected' : ''; ?>>En Yeni</option>
                            <option value="tarih_asc" <?php echo $sira === 'tarih_asc' ? 'selected' : ''; ?>>En Eski</option>
                            <option value="miktar_desc" <?php echo $sira === 'miktar_desc' ? 'selected' : ''; ?>>Miktar (Büyük → Küçük)</option>
                            <option value="miktar_asc" <?php echo $sira === 'miktar_asc' ? 'selected' : ''; ?>>Miktar (Küçük → Büyük)</option>
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
                        <th>Hareket</th>
                        <th>Miktar</th>
                        <th>Varyasyon</th>
                        <th>Açıklama</th>
                        <th>İşlemi Yapan</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($hareketler)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-info-circle text-muted me-2"></i>
                                Belirtilen kriterlere uygun stok hareketi bulunamadı.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hareketler as $hareket): ?>
                            <tr class="<?php echo $hareket->hareket_tipi == 'giris' ? 'table-success bg-opacity-25' : 'table-danger bg-opacity-25'; ?>">
                                <td><?php echo date('d.m.Y H:i', $hareket->tarih); ?></td>
                                <td><?php echo htmlspecialchars($hareket->urun_adi); ?></td>
                                <td>
                                    <?php if ($hareket->hareket_tipi == 'giris'): ?>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success me-1">
                                                <i class="fas fa-arrow-up"></i>
                                            </span>
                                            <span>Stok Girişi</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-danger me-1">
                                                <i class="fas fa-arrow-down"></i>
                                            </span>
                                            <span>Stok Çıkışı</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $hareket->miktar; ?></strong> adet</td>
                                <td>
                                    <?php
                                    if (!empty($hareket->renk) || !empty($hareket->beden)) {
                                        $varyasyon_detay = [];
                                        if (!empty($hareket->renk)) {
                                            echo '<span class="badge me-1" style="background-color: '.getColorHex($hareket->renk).'">&nbsp;</span>';
                                            $varyasyon_detay[] = $hareket->renk;
                                        }
                                        if (!empty($hareket->beden)) $varyasyon_detay[] = $hareket->beden;
                                        echo implode(' / ', $varyasyon_detay);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo !empty($hareket->aciklama) ? htmlspecialchars($hareket->aciklama) : '-'; ?></td>
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
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                const baslangicInput = document.getElementById('tarih-baslangic');
                const bitisInput = document.getElementById('tarih-bitis');

                if (baslangicInput.value) {
                    const baslangicDate = new Date(baslangicInput.value);
                    baslangicInput.name = 'tarih_baslangic';
                    baslangicInput.value = Math.floor(baslangicDate.getTime() / 1000);
                }

                if (bitisInput.value) {
                    const bitisDate = new Date(bitisInput.value);
                    // Günün sonunu al (23:59:59)
                    bitisDate.setHours(23, 59, 59);
                    bitisInput.name = 'tarih_bitis';
                    bitisInput.value = Math.floor(bitisDate.getTime() / 1000);
                }
            });

            // Son 24 saat içindeki hareketleri vurgula
            const simdi = new Date();
            const yirmiDortSaatOnce = new Date(simdi.getTime() - (24 * 60 * 60 * 1000));
            const hareketSatirlar = document.querySelectorAll('tbody tr');

            hareketSatirlar.forEach(satir => {
                const tarihHucresi = satir.querySelector('td:first-child');
                if (tarihHucresi) {
                    const tarihStr = tarihHucresi.textContent;
                    const [tarihBolum, saatBolum] = tarihStr.split(' ');
                    const [gun, ay, yil] = tarihBolum.split('.');
                    const [saat, dakika] = saatBolum.split(':');

                    const hareketTarihi = new Date(`${yil}-${ay}-${gun}T${saat}:${dakika}:00`);

                    if (hareketTarihi > yirmiDortSaatOnce) {
                        satir.classList.add('recent-activity');
                        const hareketHucresi = satir.querySelector('td:nth-child(3) .badge');
                        if (hareketHucresi) {
                            hareketHucresi.classList.add('pulse');
                        }
                    }
                }
            });
        });

        // Renk kodlarını al
        /**
         * Renk adına göre hex kodunu döndürür
         *
         * @param string $colorName Renk adı
         * @return string Renk hex kodu
         */
        function getColorHex(colorName) {
            colorMap = [
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

            return colorMap[colorName] || '#6c757d';
        }
    </script>

    <style>
        .recent-activity {
            background-color: rgba(248, 249, 250, 0.5) !important;
        }

        .badge.pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.9;
            }
            100% {
                transform: scale(1);
                opacity: 1;
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