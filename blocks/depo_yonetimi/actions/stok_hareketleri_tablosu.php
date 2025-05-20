<?php
// Temel ayarlar ve kimlik doğrulama
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT, $USER;

$depoid = required_param('depoid', PARAM_INT);
$urunid = optional_param('urunid', 0, PARAM_INT); // Opsiyonel - belirli bir ürün için filtreleme

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/stok_hareketleri_tablosu.php', ['depoid' => $depoid]));
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

// Depo bilgisini al
$depo = $DB->get_record('block_depo_yonetimi_depolar', ['id' => $depoid]);
if (!$depo) {
    print_error('Depo bulunamadı.');
}

// Stok hareketlerini al - son 30 günün hareketleri
$son_30_gun = time() - (30 * 24 * 60 * 60);

$sql_params = ['depoid' => $depoid];
$where_clause = "sh.urunid IN (SELECT id FROM {block_depo_yonetimi_urunler} WHERE depoid = :depoid)";

if ($urunid) {
    $where_clause .= " AND sh.urunid = :urunid";
    $sql_params['urunid'] = $urunid;
}

$where_clause .= " AND sh.tarih >= :son_30_gun";
$sql_params['son_30_gun'] = $son_30_gun;

$sql = "SELECT sh.*, u.name as urun_adi, usr.firstname, usr.lastname 
        FROM {block_depo_yonetimi_stok_hareketleri} sh
        JOIN {block_depo_yonetimi_urunler} u ON sh.urunid = u.id
        JOIN {user} usr ON sh.userid = usr.id
        WHERE $where_clause
        ORDER BY sh.tarih DESC";

$stok_hareketleri = $DB->get_records_sql($sql, $sql_params);

// Her ürün için stok hareketlerini grupla
$urunlere_gore_hareketler = [];
$toplam_giris = 0;
$toplam_cikis = 0;

foreach ($stok_hareketleri as $hareket) {
    $urun_adi = $hareket->urun_adi;

    if (!isset($urunlere_gore_hareketler[$hareket->urunid])) {
        $urunlere_gore_hareketler[$hareket->urunid] = [
            'urun_adi' => $urun_adi,
            'hareketler' => [],
            'toplam_giris' => 0,
            'toplam_cikis' => 0
        ];
    }

    $urunlere_gore_hareketler[$hareket->urunid]['hareketler'][] = $hareket;

    if ($hareket->miktar > 0) {
        $urunlere_gore_hareketler[$hareket->urunid]['toplam_giris'] += $hareket->miktar;
        $toplam_giris += $hareket->miktar;
    } else {
        $urunlere_gore_hareketler[$hareket->urunid]['toplam_cikis'] += abs($hareket->miktar);
        $toplam_cikis += abs($hareket->miktar);
    }
}

// Renk ve boyut için görsel gösterimler
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
            'xxxl' => 'XXXL'
        ];
        return isset($sizes[$value]) ? $sizes[$value] : $value;
    }
    return $value;
}

echo $OUTPUT->header();
?>

    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exchange-alt text-white me-2"></i>
                    <h5 class="mb-0"><?php echo htmlspecialchars($depo->name); ?> - Stok Hareketleri</h5>
                </div>
                <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/index.php', ['depo' => $depoid]); ?>" class="btn btn-sm btn-outline-white">
                    <i class="fas fa-arrow-left me-1"></i> Geri Dön
                </a>
            </div>
            <div class="card-body">
                <!-- Özet Bilgiler -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <h6 class="card-title text-muted mb-2">Toplam Stok Girişi</h6>
                                <div class="d-flex justify-content-center align-items-center">
                                    <i class="fas fa-arrow-circle-up text-success me-2" style="font-size: 1.5rem;"></i>
                                    <h4 class="mb-0 text-success"><?php echo $toplam_giris; ?> adet</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <h6 class="card-title text-muted mb-2">Toplam Stok Çıkışı</h6>
                                <div class="d-flex justify-content-center align-items-center">
                                    <i class="fas fa-arrow-circle-down text-danger me-2" style="font-size: 1.5rem;"></i>
                                    <h4 class="mb-0 text-danger"><?php echo $toplam_cikis; ?> adet</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body text-center">
                                <h6 class="card-title text-muted mb-2">Hareket Sayısı</h6>
                                <div class="d-flex justify-content-center align-items-center">
                                    <i class="fas fa-exchange-alt text-primary me-2" style="font-size: 1.5rem;"></i>
                                    <h4 class="mb-0"><?php echo count($stok_hareketleri); ?> işlem</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtre Seçenekleri -->
                <div class="mb-4">
                    <form id="filtreForm" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="baslangicTarihi" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="baslangicTarihi">
                        </div>
                        <div class="col-md-3">
                            <label for="bitisTarihi" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="bitisTarihi">
                        </div>
                        <div class="col-md-4">
                            <label for="urunFiltre" class="form-label">Ürün</label>
                            <select class="form-select" id="urunFiltre">
                                <option value="">Tüm Ürünler</option>
                                <?php
                                $urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid], 'name ASC');
                                foreach ($urunler as $urun) {
                                    $selected = ($urun->id == $urunid) ? 'selected' : '';
                                    echo "<option value=\"{$urun->id}\" $selected>" . htmlspecialchars($urun->name) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                        </div>
                    </form>
                </div>

                <!-- Stok Hareketleri Tablosu -->
                <div class="table-responsive">
                    <table class="table table-hover" id="stokHareketleriTablosu">
                        <thead>
                        <tr>
                            <th>Varyasyon</th>
                            <th>Stok Miktarı</th>
                            <th>Eklenen Stok Miktarı</th>
                            <th>Düşen Stok Miktarı</th>
                            <th>İşlem Tarihi</th>
                            <th>Kullanıcı</th>
                            <th>Açıklama</th>
                        </tr>
                        </thead>
                        <tbody id="stokHareketleriGovdesi">
                        <?php if (empty($stok_hareketleri)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-info-circle text-info mb-3" style="font-size: 2rem;"></i>
                                    <p class="mb-0">Son 30 güne ait stok hareketi bulunamadı.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stok_hareketleri as $hareket): ?>
                                <tr data-urunid="<?php echo $hareket->urunid; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($hareket->renk)): ?>
                                                <span class="badge me-2" style="background-color: <?php echo get_color_hex($hareket->renk); ?>; color: <?php echo get_contrast_color($hareket->renk); ?>;">&nbsp;&nbsp;&nbsp;</span>
                                            <?php endif; ?>
                                            <span>
                                                <?php
                                                echo htmlspecialchars($hareket->urun_adi);
                                                if (!empty($hareket->renk) && !empty($hareket->beden)) {
                                                    echo ' - ' . get_string_from_value($hareket->renk, 'color') . ' / ' . get_string_from_value($hareket->beden, 'size');
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="stok-miktar" data-baslangic-miktar="<?php echo $hareket->miktar; ?>">
                                        <input type="number" class="form-control form-control-sm guncel-miktar" value="<?php echo $hareket->miktar; ?>" min="0">
                                    </td>
                                    <td class="eklenen-miktar text-success"></td>
                                    <td class="dusen-miktar text-danger"></td>
                                    <td><?php echo date('d.m.Y H:i', $hareket->tarih); ?></td>
                                    <td><?php echo htmlspecialchars($hareket->firstname . ' ' . $hareket->lastname); ?></td>
                                    <td><?php echo htmlspecialchars($hareket->aciklama); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Stok miktarı değişikliklerini izle
            const stokMiktarlari = document.querySelectorAll('.guncel-miktar');

            stokMiktarlari.forEach(function(input) {
                const row = input.closest('tr');
                const baslangicMiktar = parseInt(row.querySelector('.stok-miktar').dataset.baslangicMiktar);
                const eklenenMiktarHucresi = row.querySelector('.eklenen-miktar');
                const dusenMiktarHucresi = row.querySelector('.dusen-miktar');

                // İlk yükleme için hesapla
                hesaplaFark(input, baslangicMiktar, eklenenMiktarHucresi, dusenMiktarHucresi);

                // Değişiklikleri izle
                input.addEventListener('input', function() {
                    hesaplaFark(input, baslangicMiktar, eklenenMiktarHucresi, dusenMiktarHucresi);
                });
            });

            // Stok farkını hesaplama fonksiyonu
            function hesaplaFark(input, baslangicMiktar, eklenenHucre, dusenHucre) {
                const guncelMiktar = parseInt(input.value) || 0;
                const fark = guncelMiktar - baslangicMiktar;

                // Miktar artmış
                if (fark > 0) {
                    eklenenHucre.innerHTML = `<span class="badge bg-success"><i class="fas fa-arrow-up me-1"></i> ${fark}</span>`;
                    dusenHucre.innerHTML = '';
                }
                // Miktar azalmış
                else if (fark < 0) {
                    eklenenHucre.innerHTML = '';
                    dusenHucre.innerHTML = `<span class="badge bg-danger"><i class="fas fa-arrow-down me-1"></i> ${Math.abs(fark)}</span>`;
                }
                // Değişiklik yok
                else {
                    eklenenHucre.innerHTML = '';
                    dusenHucre.innerHTML = '';
                }
            }

            // Filtreleme formu
            const filtreForm = document.getElementById('filtreForm');
            const urunFiltre = document.getElementById('urunFiltre');

            filtreForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // URL parametrelerini oluştur
                const params = new URLSearchParams();
                params.append('depoid', '<?php echo $depoid; ?>');

                if (urunFiltre.value) {
                    params.append('urunid', urunFiltre.value);
                }

                const baslangicTarihi = document.getElementById('baslangicTarihi').value;
                const bitisTarihi = document.getElementById('bitisTarihi').value;

                if (baslangicTarihi) params.append('baslangic', baslangicTarihi);
                if (bitisTarihi) params.append('bitis', bitisTarihi);

                // Sayfayı yeniden yükle
                window.location.href = '?' + params.toString();
            });
        });

        // Renk kodlarını al
        function get_color_hex(colorName) {
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

        // Kontrast rengi hesapla
        function get_contrast_color(colorName) {
            const lightColors = ['beyaz', 'sari', 'acik-mavi', 'acik-yesil', 'acik-pembe', 'bej'];
            return lightColors.includes(colorName) ? '#212529' : '#ffffff';
        }
    </script>

<?php
echo $OUTPUT->footer();
?>