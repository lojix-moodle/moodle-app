<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $USER;

try {
    $kategoriid = required_param('kategoriid', PARAM_INT);

    // Kategori var mı kontrol et
    $kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['id' => $kategoriid]);
    if (!$kategori) {
        throw new moodle_exception('Kategori bulunamadı.');
    }

    // Yetki kontrolü
    $kullanici_depo_eslesme = [
        2 => 3,
        5 => 1,
    ];

    if (!has_capability('block/depo_yonetimi:viewall', context_system::instance()) &&
        (!isset($kullanici_depo_eslesme[$USER->id]) || $kullanici_depo_eslesme[$USER->id] != $kategori->depoid)) {
        throw new moodle_exception('Bu depoya erişim izniniz yok.');
    }

    // Ürünleri kontrol ederken aktif ürünleri filtreleyelim
    $sql = "SELECT COUNT(*) 
            FROM {block_depo_yonetimi_urunler} 
            WHERE kategoriid = :kategoriid 
            AND deleted = 0";

    $bagli_urunler = $DB->count_records_sql($sql, ['kategoriid' => $kategoriid]);

    if ($bagli_urunler > 0) {
        redirect(
            new moodle_url('/my', ['view' => 'kategoriler']),
            'Bu kategoriye bağlı ' . $bagli_urunler . ' ürün var. Önce bu ürünlerin kategorisini değiştirin veya silin.',
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    } else {
        // Kategoriyi sil
        if ($DB->delete_records('block_depo_yonetimi_kategoriler', ['id' => $kategoriid])) {
            redirect(
                new moodle_url('/my', ['view' => 'kategoriler']),
                'Kategori başarıyla silindi.',
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            throw new moodle_exception('Kategori silinirken bir hata oluştu.');
        }
    }
} catch (Exception $e) {
    redirect(
        new moodle_url('/my', ['view' => 'kategoriler']),
        'Bir hata oluştu: ' . $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}