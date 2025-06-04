<?php
require_once('../../../config.php');
global $DB, $PAGE, $OUTPUT;


$depoid = required_param('depoid', PARAM_INT);

// Yetki kontrolü
require_login();

// Rafları getir
$raflar = $DB->get_records('block_depo_yonetimi_raflar', ['depoid' => $depoid], 'kod ASC');

// JSON olarak dön
header('Content-Type: application/json');
echo json_encode(array_values($raflar));