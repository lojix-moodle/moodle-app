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

    // Stok hareketleri tablosunu ekle - yeni sürüm numarası
    if ($oldversion < 2025052044) {
        // Stok hareketleri tablosu
        $table = new xmldb_table('block_depo_yonetimi_stok_hareketleri');

        // Alanlar
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('urunid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('renk', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('beden', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('miktar', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('aciklama', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('islemtipi', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tarih', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Anahtarlar
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Tablo yoksa oluştur
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
//
//        // Minimum stok seviyesi alanını ekle
//        $table = new xmldb_table('block_depo_yonetimi_urunler');
//        $field = new xmldb_field('min_stok_seviyesi', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
//
//        if (!$dbman->field_exists($table, $field)) {
//            $dbman->add_field($table, $field);
//        }

        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025052044, 'depo_yonetimi');
    }

    return true;
}