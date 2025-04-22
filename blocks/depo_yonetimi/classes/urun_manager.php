<?php

namespace block_depo_yonetimi;

class urun_manager {
    private static $data = [];

    public static function load_data() {
        if (empty(self::$data)) {
            self::$data = [
                1 => [['id' => 1, 'name' => 'Laptop', 'adet' => 5]],
                2 => [['id' => 2, 'name' => 'Keyboard', 'adet' => 7]],
                3 => [['id' => 3, 'name' => 'Printer', 'adet' => 4]],
            ];
        }
        return self::$data;
    }

    public static function get_products($depoid) {
        self::load_data();
        return self::$data[$depoid] ?? [];
    }

    public static function add_product($depoid, $product) {
        $product['id'] = rand(1000, 9999);
        self::$data[$depoid][] = $product;
    }

    public static function update_product($depoid, $product) {
        foreach (self::$data[$depoid] as &$item) {
            if ($item['id'] == $product['id']) {
                $item = $product;
                break;
            }
        }
    }

    public static function delete_product($depoid, $productid) {
        self::$data[$depoid] = array_filter(self::$data[$depoid], fn($item) => $item['id'] != $productid);
    }

    public static function get_product($depoid, $id) {
        foreach (self::$data[$depoid] ?? [] as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }
}