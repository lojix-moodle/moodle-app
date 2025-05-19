<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025051931) {
        $table = new xmldb_table('block_depo_yonetimi_urunler');

        $field3 = new xmldb_field('colors', XMLDB_TYPE_CHAR, '755', null, XMLDB_NOTNULL, null, '#000000');
        $field2 = new xmldb_field('sizes', XMLDB_TYPE_CHAR, '755', null, XMLDB_NOTNULL, null, '#000000');

        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }

        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025051931, 'depo_yonetimi');
    }


    return true;
}
