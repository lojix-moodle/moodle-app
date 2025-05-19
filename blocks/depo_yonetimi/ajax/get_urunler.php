<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB;

header('Content-Type: application/json');

// Depo ID parametresini al
$depoid = optional_param('depoid', 0, PARAM_INT);

// Sorgu koşulunu oluştur
$where = [];
if ($depoid) {
    $where['depoid'] = $depoid;
}

// Ürünleri al
$urunler = $DB->get_records('block_depo_yonetimi_urunler', $where, 'name ASC');

// Sonucu JSON olarak döndür
$result = [];
foreach ($urunler as $urun) {
    $result[] = [
        'id' => $urun->id,
        'name' => $urun->name
    ];
}

echo json_encode($result);