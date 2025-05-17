<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $USER;

try {
    // Parametreyi güvenli bir şekilde al
    $kategoriid = optional_param('kategoriid', 0, PARAM_INT);

    if (!$kategoriid) {
        throw new moodle_exception('Kategori ID parametresi gerekli.');
    }

    // Veritabanı bağlantısını kontrol et
    if (!$DB) {
        throw new moodle_exception('Veritabanı bağlantısı kurulamadı.');
    }

    // Kategoriyi kontrol et
    $sql = "SELECT * FROM {block_depo_yonetimi_kategoriler} WHERE id = :kategoriid";
    $params = ['kategoriid' => $kategoriid];
    $kategori = $DB->get_record_sql($sql, $params);

    if (!$kategori) {
        throw new moodle_exception('Kategori bulunamadı.');
    }

    // Bağlı ürünleri kontrol et
    $sql_urunler = "SELECT COUNT(*) as toplam 
                    FROM {block_depo_yonetimi_urunler} 
                    WHERE kategoriid = :kategoriid 
                    AND deleted = 0";

    $bagli_urunler = $DB->get_field_sql($sql_urunler, ['kategoriid' => $kategoriid]);

    if ($bagli_urunler > 0) {
        redirect(
            new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
            'Bu kategoriye bağlı ' . $bagli_urunler . ' ürün var.',
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    // Kategoriyi sil
    $sonuc = $DB->delete_records('block_depo_yonetimi_kategoriler', ['id' => $kategoriid]);
    if (!$sonuc) {
        throw new moodle_exception('Kategori silinirken bir hata oluştu.');
    }

    redirect(
        new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
        'Kategori başarıyla silindi.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (dml_exception $e) {
    redirect(
        new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
        'Veritabanı hatası: ' . $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
} catch (Exception $e) {
    redirect(
        new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'),
        'Bir hata oluştu: ' . $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}