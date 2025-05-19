<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB;

header('Content-Type: application/json');

// Depo ID parametresini al
$depoid = optional_param('depoid', 0, PARAM_INT);

// Sorgu koşulunu oluştur
$where = [];
$params = [];

if ($depoid) {
    $where[] = "depoid = :depoid";
    $params['depoid'] = $depoid;
}

// SQL sorgusunu oluştur
$sql_where = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT id, name FROM {block_depo_yonetimi_urunler} $sql_where ORDER BY name ASC";

// Ürünleri al
$urunler = $DB->get_records_sql($sql, $params);

// Sonucu JSON olarak döndür
$result = [];
foreach ($urunler as $urun) {
    $result[] = [
        'id' => $urun->id,
        'name' => $urun->name
    ];
}

echo json_encode($result);