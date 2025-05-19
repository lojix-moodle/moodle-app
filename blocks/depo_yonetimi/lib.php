<?php
/**
 * Stok hareketini kaydeder
 *
 * @param int $depoid Depo ID
 * @param int $urunid Ürün ID
 * @param string|null $color Renk (varsa)
 * @param string|null $size Boyut (varsa)
 * @param int $eski_miktar Eski stok miktarı
 * @param int $yeni_miktar Yeni stok miktarı
 * @param string $islem_turu İşlem türü (ekleme, azaltma, güncelleme)
 * @param string $aciklama İşlem açıklaması
 * @return bool İşlem başarılı mı
 */
function stok_hareketi_kaydet($depoid, $urunid, $color = null, $size = null, $eski_miktar, $yeni_miktar, $islem_turu, $aciklama = '') {
    global $DB, $USER;

    $stok_log = new stdClass();
    $stok_log->depoid = $depoid;
    $stok_log->urunid = $urunid;
    $stok_log->color = $color;
    $stok_log->size = $size;
    $stok_log->eski_miktar = $eski_miktar;
    $stok_log->yeni_miktar = $yeni_miktar;
    $stok_log->islem_turu = $islem_turu;
    $stok_log->aciklama = $aciklama;
    $stok_log->islem_yapan = $USER->id;
    $stok_log->islem_tarihi = time();

    return $DB->insert_record('block_depo_yonetimi_stok_log', $stok_log);
}