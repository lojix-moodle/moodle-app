<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $USER;

try {
    // Kategori ID'sini al
    $kategoriid = required_param('kategoriid', PARAM_INT);

    // Basit sorgu ile kategoriyi kontrol et
    $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $kategoriid]);
    if (!$kategori) {
        throw new moodle_exception('Kategori bulunamadı.');
    }

    // Bağlı ürünleri basit sorgu ile kontrol et
    $bagli_urunler = $DB->count_records('block_depo_yonetimi_urunler', [
        'kategoriid' => $kategoriid,
        'deleted' => 0
    ]);

    if ($bagli_urunler > 0) {
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
            'Bu kategoriye bağlı ' . $bagli_urunler . ' ürün var.',
            null,
            \core\output\notification::NOTIFY_ERROR
        );
        exit;
    }

    // Kategoriyi sil
    if ($DB->delete_records('block_depo_yonetimi_kategoriler', ['id' => $kategoriid])) {
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
            'Kategori başarıyla silindi.',
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        throw new moodle_exception('Silme işlemi başarısız oldu.');
    }

} catch (moodle_exception $e) {
    redirect(
        new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}