// blocks/depo_yonetimi/db/services.php
$services = array(
'block_depo_yonetimi_services' => array(
'functions' => array(
'block_depo_yonetimi_get_yerlesim',
'block_depo_yonetimi_get_raf_urunleri',
'block_depo_yonetimi_save_yerlesim',
),
'restrictedusers' => 0,
'enabled' => 1,
)
);

$functions = array(
'block_depo_yonetimi_get_yerlesim' => array(
'classname'     => 'block_depo_yonetimi_external',
'methodname'    => 'get_yerlesim',
'classpath'     => 'blocks/depo_yonetimi/external.php',
'description'   => 'Depo yerleşim planını getirir',
'type'          => 'read',
'capabilities'  => 'block/depo_yonetimi:view',
'ajax'          => true,
),
'block_depo_yonetimi_get_raf_urunleri' => array(
'classname'     => 'block_depo_yonetimi_external',
'methodname'    => 'get_raf_urunleri',
'classpath'     => 'blocks/depo_yonetimi/external.php',
'description'   => 'Raftaki ürünleri getirir',
'type'          => 'read',
'capabilities'  => 'block/depo_yonetimi:view',
'ajax'          => true,
),
'block_depo_yonetimi_save_yerlesim' => array(
'classname'     => 'block_depo_yonetimi_external',
'methodname'    => 'save_yerlesim',
'classpath'     => 'blocks/depo_yonetimi/external.php',
'description'   => 'Yerleşim planını kaydeder',
'type'          => 'write',
'capabilities'  => 'block/depo_yonetimi:yonet',
'ajax'          => true,
),
);