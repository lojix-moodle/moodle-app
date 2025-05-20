<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025051947) {
        $table = new xmldb_table('block_depo_yonetimi_talepler');

        $table->add_field('renk', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('beden', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('durum', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 0);

        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025051947, 'depo_yonetimi');
    }

    return true;
}