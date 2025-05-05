<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
global $PAGE, $DB, $USER, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depolar.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title("Depolar");
$PAGE->set_heading("Depolar");
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css', ['v' => time()]));

$depolar = $DB->get_records('block_depo_yonetimi_depolar');
$kullanici_depo_eslesme = [2 => 3, 5 => 1];

if (has_capability('block/depo_yonetimi:viewall', context_system::instance())) {
    $yetki = 'admin';
} elseif (has_capability('block/depo_yonetimi:viewown', context_system::instance())) {
    $yetki = 'depoyetkilisi';
} else {
    echo $OUTPUT->header();
    echo "<p>Yetkiniz yok.</p>";
    echo $OUTPUT->footer();
    exit;
}

$depoid = optional_param('depo', null, PARAM_INT);
$urunler = $DB->get_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

echo $OUTPUT->header();

if ($depoid) {
    if ($yetki === 'admin' || (isset($kullanici_depo_eslesme[$USER->id]) && $kullanici_depo_eslesme[$USER->id] == $depoid)) {
        $templatecontext = [
            'urunler' => [],
            'ekle_url' => new moodle_url('/blocks/depo_yonetimi/actions/urun_ekle.php', ['depoid' => $depoid]),
            'back_url' => new moodle_url('/blocks/depo_yonetimi/actions/depolar.php'),
        ];

        foreach ($urunler as $index => $urun) {
            $templatecontext['urunler'][] = [
                'name' => $urun->name,
                'adet' => $urun->adet,
                'duzenle_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_duzenle.php', [
                    'depoid' => $depoid,
                    'urunid' => $urun->id
                ]))->out(false),
                'sil_url' => (new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', [
                    'depoid' => $depoid,
                    'urunid' => $urun->id
                ]))->out(false),
            ];
        }

        echo $OUTPUT->render_from_template('block_depo_yonetimi/urun_tablo', $templatecontext);
    } else {
        echo '<p>Bu depoya erişim izniniz yok.</p>';
    }
} else {
    echo '<div class="depo-ekle-container">';
    echo '<a href="' . new moodle_url('/blocks/depo_yonetimi/actions/depo_ekle.php') . '" class="btn btn-primary btn-sm">+ Depo Ekle</a>';
    echo '</div>';

    echo '<div class="depo-container">';

    if ($yetki === 'admin') {
        foreach ($depolar as $depo) {
            $url = new moodle_url('/blocks/depo_yonetimi/actions/depolar.php', ['depo' => $depo->id]);
            $duzenleurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', ['depoid' => $depo->id]);
            $silurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', ['depoid' => $depo->id]);

            echo '<div class="depo-card">';
            echo '<div class="depo-header">';
            echo "<div class='depo-name'>{$depo->name}</div>";
            echo "<a href='{$duzenleurl}' class='depo-edit-btn' title='Depoyu Düzenle'>✎</a>";
            echo '</div>';
            echo '<div class="depo-buttons">';
            echo "<a href='{$silurl}' class='depo-delete-btn' onclick='return confirm(\"Bu depoyu silmek istediğinize emin misiniz?\");'>🗑</a>";
            echo "<a href='{$url}' class='depo-view-btn'>Ürünleri Gör</a>";
            echo '</div>';
            echo '</div>';
        }
    } else {
        $kendi_depoid = $kullanici_depo_eslesme[$USER->id] ?? null;

        if ($kendi_depoid && isset($depolar[$kendi_depoid])) {
            $depo = $depolar[$kendi_depoid];
            $url = new moodle_url('/blocks/depo_yonetimi/actions/depolar.php', ['depo' => $depo->id]);

            echo '<div class="depo-card">';
            echo '<div class="depo-header">';
            echo "<div class='depo-name'>{$depo->name}</div>";
            echo '</div>';
            echo '<div class="depo-buttons">';
            echo "<a href='{$url}' class='depo-view-btn'>Ürünleri Gör</a>";
            echo '</div>';
            echo '</div>';
        } else {
            echo '<p>Size atanmış bir depo yok.</p>';
        }
    }

    echo '</div>';
}

echo $OUTPUT->footer();
