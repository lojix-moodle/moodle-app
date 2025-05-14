<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025051417) {
        $table = new xmldb_table('block_depo_yonetimi_depolar');

        // Eğer tablo varsa işlem yap
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('sorumluid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'name');

            // Eğer sütun yoksa ekle
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Sürüm numarasını güncelle
        upgrade_block_savepoint(true, 2025051417, 'depo_yonetimi');

    }


    return true;
}
