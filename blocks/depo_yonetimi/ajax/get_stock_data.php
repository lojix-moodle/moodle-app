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
 * AJAX endpoint stok verilerini almak için
 *
 * @package    block_depo_yonetimi
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
require_once('../dashboard_lib.php');

// Yetki kontrolü
require_login();
$context = context_system::instance();
require_capability('block/depo_yonetimi:view', $context);

// CORS header'ları ekle
header('Content-Type: application/json');

// Stok seviyelerini al
$stockLevels = get_stock_levels();

// JSON olarak döndür
echo json_encode([
    'success' => true,
    'data' => $stockLevels
]);