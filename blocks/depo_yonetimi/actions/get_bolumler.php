<?php
require_once('../../../config.php');
global $DB, $PAGE, $OUTPUT;

$rafid = required_param('rafid', PARAM_INT);

// Yetki kontrolü
require_login();

// Bölümleri getir
$bolumler = $DB->get_records('block_depo_yonetimi_bolumler', ['rafid' => $rafid], 'kod ASC');

// JSON olarak dön
header('Content-Type: application/json');
echo json_encode(array_values($bolumler));