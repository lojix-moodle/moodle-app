<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

// $DB değişkenini global olarak tanımla
global $DB;

header('Content-Type: application/json');

$kategori_adi = optional_param('name', '', PARAM_TEXT);
$exclude_id = optional_param('exclude_id', 0, PARAM_INT);

if (empty($kategori_adi)) {
    echo json_encode(['error' => 'Kategori adı belirtilmedi.', 'exists' => false]);
    exit;
}

// Veritabanında aynı isimde kategori var mı kontrol et
if ($exclude_id > 0) {
    // Düzenleme sırasında kendi ID'si hariç kontrol et
    $mevcut_kategori = $DB->get_record_sql(
        "SELECT * FROM {block_depo_yonetimi_kategoriler} WHERE name = ? AND id != ?",
        [$kategori_adi, $exclude_id]
    );
} else {
    // Yeni ekleme durumunda tüm kayıtları kontrol et
    $mevcut_kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['name' => $kategori_adi]);
}

echo json_encode(['exists' => !empty($mevcut_kategori)]);