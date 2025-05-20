<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025052051) {
        $table = new xmldb_table('block_depo_yonetimi_talepler');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('depoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('urunid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('renk', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('beden', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('adet', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('durum', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, );

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025052051, 'depo_yonetimi');
    }

    return true;
}