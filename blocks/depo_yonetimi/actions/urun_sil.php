<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

try {
    // Gelen parametreleri al
    $depoid = required_param('depoid', PARAM_INT);
    $urunid = required_param('urunid', PARAM_INT);

    // Sayfa ayarları
    $PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_sil.php', ['depoid' => $depoid, 'urunid' => $urunid]));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title('Ürün Sil');
    $PAGE->set_heading('Ürün Sil');

    // Yetki kontrolü
    $context = context_system::instance();
    $is_admin = has_capability('block/depo_yonetimi:viewall', $context);
    $is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

    if (!$is_admin) {
        if (!$is_depo_user)
        {
            throw new moodle_exception('Erişim izniniz yok.');
        }

    }

    // Ürün var mı kontrol et
    $urun = $DB->get_record('block_depo_yonetimi_urunler', ['id' => $urunid, 'depoid' => $depoid]);
    if (!$urun) {
        throw new moodle_exception('Ürün bulunamadı.');
    }

    // Transaction başlat
    $transaction = $DB->start_delegated_transaction();
    try {
        // Ürünü veritabanından tamamen sil
        $sonuc = $DB->delete_records('block_depo_yonetimi_urunler', ['id' => $urunid]);

        if (!$sonuc) {
            throw new moodle_exception('Ürün silinirken bir hata oluştu.');
        }

        // Transaction'ı onayla
        $transaction->allow_commit();

        redirect(
            new moodle_url('/my', ['depo' => $depoid]),
            'Ürün başarıyla silindi.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Exception $e) {
        // Hata durumunda transaction'ı geri al
        $transaction->rollback($e);
        throw $e;
    }
} catch (Exception $e) {
    redirect(
        new moodle_url('/my', ['depo' => $depoid]),
        'Hata: ' . $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}