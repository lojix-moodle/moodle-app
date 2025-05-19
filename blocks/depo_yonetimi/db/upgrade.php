<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025051921) {
        $table = new xmldb_table('block_depo_yonetimi_urunler');

        $field = new xmldb_field('colors', XMLDB_TYPE_CHAR, '755', null, XMLDB_NOTNULL, null, '#000000');
        $field = new xmldb_field('sizes', XMLDB_TYPE_CHAR, '755', null, XMLDB_NOTNULL, null, '#000000');
        $field = new xmldb_field('varyasyonlar', XMLDB_TYPE_CHAR, '755', null, XMLDB_NOTNULL, null, '#000000');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025051921, 'depo_yonetimi');
    }


    return true;
}
