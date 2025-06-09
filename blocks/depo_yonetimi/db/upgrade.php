<?php

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025060616) {
        $table = new xmldb_table('block_depo_yonetimi_talepler');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('depoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('urunid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('renk', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('beden', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('adet', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('durum', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, );

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }



        // DÜZELTME: Doğru tabloyu hedefle
        $urunler_table = new xmldb_table('block_depo_yonetimi_urunler');

        $field_raf = new xmldb_field('raf', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'varyasyonlar');
        if (!$dbman->field_exists($urunler_table, $field_raf)) {
            $dbman->add_field($urunler_table, $field_raf);
        }

        $field_bolum = new xmldb_field('bolum', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'raf');
        if (!$dbman->field_exists($urunler_table, $field_bolum)) {
            $dbman->add_field($urunler_table, $field_bolum);
        }


        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025060616, 'depo_yonetimi');
    }

    function xmldb_block_depo_yonetimi_upgrade($oldversion) {
        global $DB;
        $dbman = $DB->get_manager();

        if ($oldversion < 2025060616) { // Versiyon numarasını uygun şekilde değiştirin
            $table = new xmldb_table('block_depo_yonetimi_urunler');
            $field = new xmldb_field('barkod', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'name');

            // Alan yoksa ekle
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            upgrade_block_savepoint(true, 2025060616, 'depo_yonetimi');
        }

        return true;
    }



    return true;
}