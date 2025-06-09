<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Depo Yönetimi blok yükseltme kodları
 *
 * @package    block_depo_yonetimi
 * @copyright  2024 Depo Yönetimi Geliştirme Ekibi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_depo_yonetimi_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025060561) {
        // Talep tablosu oluşturma
        $table = new xmldb_table('block_depo_yonetimi_talepler');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('depoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('urunid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('renk', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('beden', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('adet', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('durum', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null); // Virgülü kaldırdım

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ürünler tablosuna raf ve bölüm alanları ekleme
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
        upgrade_block_savepoint(true, 2025060561, 'depo_yonetimi');
    }

    if ($oldversion < 2025060917) {
        // Ürünler tablosuna barkod alanı ekle
        $urunler_table = new xmldb_table('block_depo_yonetimi_urunler');

        $field_barkod = new xmldb_field('barkod', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'bolum');
        if (!$dbman->field_exists($urunler_table, $field_barkod)) {
            $dbman->add_field($urunler_table, $field_barkod);
        }



        // Güncelleme noktasını kaydet
        upgrade_block_savepoint(true, 2025060917, 'depo_yonetimi');
    }

    return true;
}