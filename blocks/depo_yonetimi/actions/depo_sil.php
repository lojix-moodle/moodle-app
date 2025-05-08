<?php
require_once(__DIR__ . '/../../../config.php');

require_login(); // Kullanıcı giriş kontrolü
global $DB, $PAGE, $OUTPUT;

// Sayfa ayarlarını EN BAŞTA yapıyoruz
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php'));
$PAGE->set_context(context_system::instance()); // Context hatasını çözer
$PAGE->set_title('Depo Silme');
$PAGE->set_heading('Depo Silme');

// 1. Parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// 2. Yetki kontrolü
require_capability('block/depo_yonetimi:viewall', context_system::instance());

// 3. Depo var mı kontrol et
if (!$DB->record_exists('block_depo_yonetimi_depolar', ['id' => $depoid])) {
    throw new moodle_exception('invaliddepoid', 'block_depo_yonetimi');
}

// 4. Onay ekranı göster
if (!$confirm) {
    $depo_adi = $DB->get_field('block_depo_yonetimi_depolar', 'name', ['id' => $depoid]);

    $yesurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', [
        'depoid' => $depoid,
        'confirm' => 1,
        'sesskey' => sesskey()
    ]);
    $nourl = new moodle_url('/my');
    $duzenleurl = new moodle_url('/blocks/depo_yonetimi/actions/depo_duzenle.php', [
        'depoid' => $depoid
    ]);

    echo $OUTPUT->header();

    // İlişkili ürünleri kontrol et
    $urun_sayisi = $DB->count_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

    if ($urun_sayisi > 0) {
        echo $OUTPUT->notification(
            "Bu depoda {$urun_sayisi} ürün bulunuyor. Depoyu silmek için önce ürünleri başka bir depoya taşımalı veya silmelisiniz.",
            'warning'
        );
    }

    echo html_writer::tag('h3', "'{$depo_adi}' deposunu silmek istediğinize emin misiniz?", ['class' => 'mb-4']);

    echo html_writer::start_div('d-flex flex-column gap-2');

    // Silme onayı butonları
    if ($urun_sayisi == 0) {
        echo html_writer::link($yesurl, 'Evet, Sil', ['class' => 'btn btn-danger mb-2']);
    } else {
        // Ürünleri silme seçeneği de ekle
        $sil_url = new moodle_url('/blocks/depo_yonetimi/actions/depo_sil.php', [
            'depoid' => $depoid,
            'confirm' => 1,
            'force_delete' => 1,
            'sesskey' => sesskey()
        ]);
        echo html_writer::link($sil_url, 'Evet, Depo ve İçindeki Tüm Ürünleri Sil', ['class' => 'btn btn-danger mb-2']);
    }

    echo html_writer::link($nourl, 'Hayır, Vazgeç', ['class' => 'btn btn-secondary mb-2']);

    echo html_writer::end_div();

    echo $OUTPUT->footer();
    exit;
}

// 5. Silme onayı alındıysa depo sil
require_sesskey();

try {
    // Transaction başlat
    $transaction = $DB->start_delegated_transaction();

    // Force delete seçeneği varsa önce ilişkili ürünleri sil
    $force_delete = optional_param('force_delete', 0, PARAM_BOOL);
    if ($force_delete) {
        // İlişkili ürünleri sil
        $DB->delete_records('block_depo_yonetimi_urunler', ['depoid' => $depoid]);

        // Başka ilişkili tablolar varsa onları da temizle
        // Örnek: $DB->delete_records('block_depo_yonetimi_stok_hareketleri', ['depoid' => $depoid]);
    }

    // Depoyu sil
    $DB->delete_records('block_depo_yonetimi_depolar', ['id' => $depoid]);

    // İşlemi onayla
    $transaction->allow_commit();

    redirect(
        new moodle_url('/my'),
        'Depo başarıyla silindi.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (Exception $e) {
    // Hata durumunda transaction'ı geri al
    if (isset($transaction)) {
        $transaction->rollback($e);
    }

    // Kullanıcıya anlaşılır hata mesajı göster
    redirect(
        new moodle_url('/my'),
        'Depo silinemedi: Bu depoya bağlı ürünler veya başka kayıtlar var. Önce onları silmelisiniz.',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}