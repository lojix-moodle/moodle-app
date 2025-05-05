// Depoları Listeleme
if ($yetki === 'admin') {
foreach ($depolar as $depo) {
$url = new moodle_url($PAGE->url, ['depo' => $depo->id]);
$duzenleurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depo->id]);
$silurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id]);

// Depo kartı başlatılıyor
$html .= '<div class="depo-card">';
    $html .= '<div class="depo-header">';
        $html .= "<div class='depo-name'>{$depo->name}</div>";
        $html .= "<a href='{$duzenleurl}' class='depo-edit-btn' title='Depoyu Düzenle'>✎</a>";
        $html .= '</div>'; // depo-header sonu

    // Depo kartı butonları
    $html .= '<div class="depo-buttons">';
        $html .= "<a href='{$silurl}' class='depo-delete-btn' onclick='return confirm(\"Bu depoyu silmek istediğinize emin misiniz?\");'>🗑</a>";
        $html .= "<a href='{$url}' class='depo-view-btn'>Ürünleri Gör</a>";

        // Satış Ekle butonu
        $html .= "<a href='" . new moodle_url('/blocks/depo_yonetimi/actions/satis_ekle.php', ['depoid' => $depo->id]) . "' class='btn btn-success btn-sm'>Satış Ekle</a>";

        $html .= '</div>'; // depo-buttons sonu
    $html .= '</div>'; // depo-card sonu
}
} else {
// Kullanıcının sadece kendi depo erişimi olduğu durum
$kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

if ($kendi_depoid && isset($depolar[$kendi_depoid])) {
$depo = $depolar[$kendi_depoid];
$url = new moodle_url($PAGE->url, ['depo' => $depo->id]);

$html .= '<div class="depo-card">';
    $html .= '<div class="depo-header">';
        $html .= "<div class='depo-name'>{$depo->name}</div>";
        $html .= '</div>'; // depo-header sonu

    $html .= '<div class="depo-buttons">';
        $html .= "<a href='{$url}' class='depo-view-btn'>Ürünleri Gör</a>";
        $html .= "<a href='" . new moodle_url('/blocks/depo_yonetimi/actions/satis_ekle.php', ['depoid' => $depo->id]) . "' class='btn btn-success btn-sm'>Satış Ekle</a>";
        $html .= '</div>'; // depo-buttons sonu

    $html .= '</div>'; // depo-card sonu
} else {
$html .= '<p>Size atanmış bir depo yok.</p>';
}
}
