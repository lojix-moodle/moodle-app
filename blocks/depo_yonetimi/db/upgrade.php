<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

//    if ($oldversion < 2025042204) {
//        $table = new xmldb_table('block_depo_yonetimi_urunler');
//
//        $field = new xmldb_field('kategoriid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
//
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }
//
//        // Güncelleme noktasını kaydet
//        upgrade_block_savepoint(true, 2025051401, 'depo_yonetimi');
//    }


    return true;
}
