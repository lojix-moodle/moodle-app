<?php


require_once(__DIR__ . '/../../../config.php');

require_login(); // Kullanıcı giriş kontrolü
global $DB, $PAGE, $OUTPUT;

// Sayfa ayarlarını EN BAŞTA yapıyoruz
$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/urun_talep.php'));
$PAGE->set_context(context_system::instance()); // Context hatasını çözer
$PAGE->set_title('Ürün Talep Etme');
$PAGE->set_heading('Ürün Talebi');

// 1. Parametreleri al
$depoid = required_param('depoid', PARAM_INT);
$urunid = required_param('urunid', PARAM_INT);
$renk   = required_param('renk', PARAM_TEXT);
$beden   = required_param('beden', PARAM_TEXT);
$adet   = required_param('aciklama', PARAM_TEXT);

// 2. Yetki kontrolü
$context = context_system::instance();
$is_admin = has_capability('block/depo_yonetimi:viewall', $context);
$is_depo_user = has_capability('block/depo_yonetimi:viewown', $context);

if (!$is_admin) {
//    $user_depo = $DB->get_field('block_depo_yonetimi_kullanici_depo', 'depoid', ['userid' => $USER->id]);
//    if (!$user_depo || $user_depo != $depoid) {
    if (!$is_depo_user)
    {
        throw new moodle_exception('Erişim izniniz yok.');
    }
//    }
}

// 3. Depo var mı kontrol et
if (!$DB->record_exists('block_depo_yonetimi_depolar', ['id' => $depoid])) {
    throw new moodle_exception('invaliddepoid', 'block_depo_yonetimi');
}

$stok = new stdClass();
$stok->depoid = $depoid;
$stok->urunid = $urunid;
$stok->renk = $renk;
$stok->beden = $beden;
$stok->adet = $adet;
$stok->durum = 0;
$DB->insert_record('block_depo_yonetimi_talepler', $stok);
\core\notification::success('Talep başarıyla gönderildi.');
redirect(new moodle_url('/my', ['view' => '/blocks/depo_yonetimi/actions/talepler.php']));