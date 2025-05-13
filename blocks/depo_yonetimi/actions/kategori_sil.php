<?php
require_once('../../config.php');
require_login();

global $DB;

// Kategori ID'sini al
$id = required_param('id', PARAM_INT);

// Kategoriyi kontrol et
$kategori = $DB->get_record('depo_yonetimi_kategoriler', ['id' => $id], '*', MUST_EXIST);

// Silme işlemi
$DB->delete_records('depo_yonetimi_kategoriler', ['id' => $id]);

// Geri yönlendir
redirect(new moodle_url('/blocks/depo_yonetimi/actions/kategori_listesi.php'), 'Kategori başarıyla silindi.', 2);
