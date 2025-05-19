<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

// $DB değişkenini global olarak tanımla
global $DB;

header('Content-Type: application/json');

$kategori_adi = optional_param('name', '', PARAM_TEXT);

if (empty($kategori_adi)) {
    echo json_encode(['error' => 'Kategori adı belirtilmedi.', 'exists' => false]);
    exit;
}

// Veritabanında aynı isimde kategori var mı kontrol et
$mevcut_kategori = $DB->get_record('block_depo_yonetimi_kategoriler', ['name' => $kategori_adi]);

echo json_encode(['exists' => !empty($mevcut_kategori)]);