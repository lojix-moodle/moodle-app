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
 * Dashboard library functions for Depo Yonetimi
 *
 * @package    block_depo_yonetimi
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Günlük özet verileri getirir
 *
 * @return array Günlük özet veriler
 */
function get_daily_summary() {
    global $DB;

    $today = strtotime(date('Y-m-d'));
    $tomorrow = $today + 86400; // 86400 = 24 saat (saniye cinsinden)

    // Günlük giriş sayısı
    $entries = $DB->count_records_select(
        'block_depo_yonetimi_hareketler',
        'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih < :bitis',
        ['tip' => 'giris', 'baslangic' => $today, 'bitis' => $tomorrow]
    );

    // Günlük çıkış sayısı
    $exits = $DB->count_records_select(
        'block_depo_yonetimi_hareketler',
        'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih < :bitis',
        ['tip' => 'cikis', 'baslangic' => $today, 'bitis' => $tomorrow]
    );

    // Günlük sipariş sayısı
    $orders = $DB->count_records_select(
        'block_depo_yonetimi_siparisler',
        'siparis_tarih >= :baslangic AND siparis_tarih < :bitis',
        ['baslangic' => $today, 'bitis' => $tomorrow]
    );

    return [
        'entries' => $entries,
        'exits' => $exits,
        'orders' => $orders
    ];
}

/**
 * Haftalık özet verileri getirir
 *
 * @return array Haftalık özet veriler
 */
function get_weekly_summary() {
    global $DB;

    $oneweekago = strtotime('-1 week');
    $today = time();

    // Haftalık giriş sayısı
    $entries = $DB->count_records_select(
        'block_depo_yonetimi_hareketler',
        'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis',
        ['tip' => 'giris', 'baslangic' => $oneweekago, 'bitis' => $today]
    );

    // Haftalık çıkış sayısı
    $exits = $DB->count_records_select(
        'block_depo_yonetimi_hareketler',
        'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis',
        ['tip' => 'cikis', 'baslangic' => $oneweekago, 'bitis' => $today]
    );

    // Haftalık sipariş sayısı
    $orders = $DB->count_records_select(
        'block_depo_yonetimi_siparisler',
        'siparis_tarih >= :baslangic AND siparis_tarih <= :bitis',
        ['baslangic' => $oneweekago, 'bitis' => $today]
    );

    // Haftalık stok hareketleri için veriler (grafik için)
    $daynames = [];
    $daydata = [];

    for ($i = 6; $i >= 0; $i--) {
        $daystart = strtotime("-$i days 00:00:00");
        $dayend = strtotime("-$i days 23:59:59");
        $dayname = date('D', $daystart);

        $dayentries = $DB->count_records_select(
            'block_depo_yonetimi_hareketler',
            'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis',
            ['tip' => 'giris', 'baslangic' => $daystart, 'bitis' => $dayend]
        );

        $dayexits = $DB->count_records_select(
            'block_depo_yonetimi_hareketler',
            'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis',
            ['tip' => 'cikis', 'baslangic' => $daystart, 'bitis' => $dayend]
        );

        $daynames[] = $dayname;
        $daydata[] = [
            'entries' => $dayentries,
            'exits' => $dayexits
        ];
    }

    return [
        'entries' => $entries,
        'exits' => $exits,
        'orders' => $orders,
        'chart_days' => $daynames,
        'chart_data' => $daydata
    ];
}

/**
 * Aylık özet verileri getirir
 *
 * @return array Aylık özet veriler
 */
function get_monthly_summary() {
    global $DB;

    $onemonthago = strtotime('-1 month');
    $today = time();

    // Aylık giriş sayısı
    $entries = $DB->count_records_select(
        'block_depo_yonetimi_hareketler',
        'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis',
        ['tip' => 'giris', 'baslangic' => $onemonthago, 'bitis' => $today]
    );

    // Aylık çıkış sayısı
    $exits = $DB->count_records_select(
        'block_depo_yonetimi_hareketler',
        'hareket_tipi = :tip AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis',
        ['tip' => 'cikis', 'baslangic' => $onemonthago, 'bitis' => $today]
    );

    // Aylık sipariş sayısı
    $orders = $DB->count_records_select(
        'block_depo_yonetimi_siparisler',
        'siparis_tarih >= :baslangic AND siparis_tarih <= :bitis',
        ['baslangic' => $onemonthago, 'bitis' => $today]
    );

    // Aylık stok değişimlerini hesapla
    $sql = "SELECT SUM(CASE WHEN hareket_tipi = 'giris' THEN miktar ELSE -miktar END) as net_change
            FROM {block_depo_yonetimi_hareketler}
            WHERE hareket_tarih >= :baslangic AND hareket_tarih <= :bitis";

    $net_change = $DB->get_field_sql($sql, ['baslangic' => $onemonthago, 'bitis' => $today]);

    return [
        'entries' => $entries,
        'exits' => $exits,
        'orders' => $orders,
        'net_change' => $net_change
    ];
}

/**
 * Stok seviyelerini getirir
 *
 * @return array Stok seviyeleri
 */
function get_stock_levels() {
    global $DB;

    // Tüm ürünleri getir
    $products = $DB->get_records('block_depo_yonetimi_urunler', null, 'urun_adi');

    $stocklevels = [];

    foreach ($products as $product) {
        // Ürünün stok durumunu al
        $sql = "SELECT 
                    SUM(CASE WHEN hareket_tipi = 'giris' THEN miktar ELSE 0 END) as total_in,
                    SUM(CASE WHEN hareket_tipi = 'cikis' THEN miktar ELSE 0 END) as total_out
                FROM {block_depo_yonetimi_hareketler}
                WHERE urun_id = :urun_id";

        $stock_data = $DB->get_record_sql($sql, ['urun_id' => $product->id]);

        $current_stock = ($stock_data->total_in ?? 0) - ($stock_data->total_out ?? 0);

        // Stok seviyesi kontrolü
        $stock_status = 'normal';
        if ($current_stock <= $product->kritik_stok) {
            $stock_status = 'critical';
        } else if ($current_stock <= $product->kritik_stok * 2) {
            $stock_status = 'warning';
        }

        $stocklevels[] = [
            'id' => $product->id,
            'name' => $product->urun_adi,
            'current_stock' => $current_stock,
            'critical_level' => $product->kritik_stok,
            'max_stock' => $product->max_stok,
            'stock_status' => $stock_status,
            'percentage' => ($current_stock / $product->max_stok) * 100
        ];
    }

    return $stocklevels;
}

/**
 * Bekleyen siparişleri getirir
 *
 * @return array Bekleyen siparişler
 */
function get_pending_orders() {
    global $DB;

    $sql = "SELECT s.*, u.firstname, u.lastname, p.urun_adi
            FROM {block_depo_yonetimi_siparisler} s
            JOIN {user} u ON s.user_id = u.id
            JOIN {block_depo_yonetimi_urunler} p ON s.urun_id = p.id
            WHERE s.siparis_durumu = 'beklemede'
            ORDER BY s.siparis_tarih DESC
            LIMIT 10";

    $orders = $DB->get_records_sql($sql);

    $result = [];
    foreach ($orders as $order) {
        $result[] = [
            'id' => $order->id,
            'product_name' => $order->urun_adi,
            'quantity' => $order->miktar,
            'order_date' => date('d.m.Y H:i', $order->siparis_tarih),
            'requested_by' => $order->firstname . ' ' . $order->lastname
        ];
    }

    return $result;
}

/**
 * Kritik stok uyarılarını getirir
 *
 * @return array Kritik stok uyarıları
 */
function get_critical_stock_alerts() {
    global $DB;

    // Önce tüm stok seviyelerini al
    $stock_levels = get_stock_levels();

    // Kritik seviyede olanları filtrele
    $critical_alerts = array_filter($stock_levels, function($item) {
        return $item['stock_status'] == 'critical' || $item['stock_status'] == 'warning';
    });

    return array_values($critical_alerts); // array_values ile indeksleri sıfırla
}

/**
 * KPI verilerini getirir
 *
 * @return array KPI verileri
 */
function get_kpi_data() {
    global $DB;

    // Toplam stok değeri
    $sql = "SELECT SUM(u.birim_fiyat * 
            (
                (SELECT SUM(CASE WHEN h.hareket_tipi = 'giris' THEN h.miktar ELSE 0 END) 
                FROM {block_depo_yonetimi_hareketler} h 
                WHERE h.urun_id = u.id) - 
                (SELECT COALESCE(SUM(CASE WHEN h.hareket_tipi = 'cikis' THEN h.miktar ELSE 0 END), 0) 
                FROM {block_depo_yonetimi_hareketler} h 
                WHERE h.urun_id = u.id)
            )) as total_value
            FROM {block_depo_yonetimi_urunler} u";

    $total_stock_value = $DB->get_field_sql($sql);

    // Son 30 gündeki toplam çıkış değeri
    $onemonthago = strtotime('-1 month');
    $today = time();

    $sql = "SELECT SUM(u.birim_fiyat * h.miktar) as total_exit_value
            FROM {block_depo_yonetimi_hareketler} h
            JOIN {block_depo_yonetimi_urunler} u ON h.urun_id = u.id
            WHERE h.hareket_tipi = 'cikis'
            AND h.hareket_tarih >= :baslangic AND h.hareket_tarih <= :bitis";

    $monthly_exit_value = $DB->get_field_sql($sql, ['baslangic' => $onemonthago, 'bitis' => $today]);

    // Stok devir hızı (son 30 günlük çıkış miktarı / ortalama stok miktarı)
    $sql = "SELECT AVG(
                (SELECT SUM(CASE WHEN h.hareket_tipi = 'giris' THEN h.miktar ELSE 0 END) - 
                        SUM(CASE WHEN h.hareket_tipi = 'cikis' THEN h.miktar ELSE 0 END)
                FROM {block_depo_yonetimi_hareketler} h
                WHERE h.urun_id = u.id)
            ) as avg_stock
            FROM {block_depo_yonetimi_urunler} u";

    $avg_stock = $DB->get_field_sql($sql);

    $sql = "SELECT SUM(miktar) as total_exit_quantity
            FROM {block_depo_yonetimi_hareketler}
            WHERE hareket_tipi = 'cikis'
            AND hareket_tarih >= :baslangic AND hareket_tarih <= :bitis";

    $monthly_exit_quantity = $DB->get_field_sql($sql, ['baslangic' => $onemonthago, 'bitis' => $today]);

    $stock_turnover = 0;
    if ($avg_stock > 0) {
        $stock_turnover = $monthly_exit_quantity / $avg_stock;
    }

    // Sipariş tamamlanma oranı (son 30 gün)
    $total_orders = $DB->count_records_select(
        'block_depo_yonetimi_siparisler',
        'siparis_tarih >= :baslangic AND siparis_tarih <= :bitis',
        ['baslangic' => $onemonthago, 'bitis' => $today]
    );

    $completed_orders = $DB->count_records_select(
        'block_depo_yonetimi_siparisler',
        'siparis_tarih >= :baslangic AND siparis_tarih <= :bitis AND siparis_durumu = :durum',
        ['baslangic' => $onemonthago, 'bitis' => $today, 'durum' => 'tamamlandi']
    );

    $order_completion_rate = 0;
    if ($total_orders > 0) {
        $order_completion_rate = ($completed_orders / $total_orders) * 100;
    }

    return [
        'total_stock_value' => number_format($total_stock_value, 2, ',', '.'),
        'monthly_exit_value' => number_format($monthly_exit_value, 2, ',', '.'),
        'stock_turnover' => number_format($stock_turnover, 2),
        'order_completion_rate' => number_format($order_completion_rate, 1)
    ];
}

/**
 * Son hareketleri/işlemleri getirir
 *
 * @return array Son hareketler/işlemler
 */
function get_recent_activities() {
    global $DB;

    // Stok hareketleri ve siparişleri birleştir
    $sql_movements = "SELECT 
                        h.id, 
                        u.urun_adi as activity_name,
                        CASE 
                            WHEN h.hareket_tipi = 'giris' THEN 'Stok Girişi'
                            ELSE 'Stok Çıkışı'
                        END as activity_type,
                        h.miktar as quantity,
                        h.hareket_tarih as activity_date,
                        CONCAT(usr.firstname, ' ', usr.lastname) as user_name
                    FROM {block_depo_yonetimi_hareketler} h
                    JOIN {block_depo_yonetimi_urunler} u ON h.urun_id = u.id
                    JOIN {user} usr ON h.user_id = usr.id
                    ORDER BY h.hareket_tarih DESC
                    LIMIT 15";

    $movements = $DB->get_records_sql($sql_movements);

    $activities = [];
    foreach ($movements as $move) {
        $activities[] = [
            'id' => $move->id,
            'name' => $move->activity_name,
            'type' => $move->activity_type,
            'quantity' => $move->quantity,
            'date' => date('d.m.Y H:i', $move->activity_date),
            'user' => $move->user_name
        ];
    }

    return $activities;
}