<?php
require_once('../../../config.php');
global $DB, $PAGE, $OUTPUT, $CFG;
require_once($CFG->dirroot . '/blocks/depo_yonetimi/lib.php');

// Sayfa parametreleri
$depoid = optional_param('depoid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$rafid = optional_param('rafid', 0, PARAM_INT);
$bolumid = optional_param('bolumid', 0, PARAM_INT);

// Yetki kontrolü
require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/depo_yonetimi/actions/raf_yonetimi.php');
$PAGE->set_title('Raf Yönetimi');
$PAGE->set_heading('Raf Yönetimi');

// Bootstrap CSS ve JS ekle
$PAGE->requires->css(new moodle_url('/blocks/depo_yonetimi/styles.css'));
$PAGE->requires->js_call_amd('block_depo_yonetimi/raf_yonetimi', 'init');

// Depolar listesini al
$depolar = $DB->get_records('block_depo_yonetimi_depolar');

// Eğer depoid belirtilmişse ve mevcutsa depo bilgisini al
$depo = null;
if ($depoid > 0) {
    $depo = $DB->get_record('block_depo_yonetimi_depolar', array('id' => $depoid), '*', MUST_EXIST);
}

// Form işlemleri
if ($action == 'add_raf' && $depoid > 0) {
    $raf = new stdClass();
    $raf->depoid = $depoid;
    $raf->kod = required_param('raf_kod', PARAM_TEXT);
    $raf->aciklama = optional_param('raf_aciklama', '', PARAM_TEXT);
    $raf->timecreated = time();
    $raf->timemodified = time();

    $DB->insert_record('block_depo_yonetimi_raflar', $raf);
    redirect($PAGE->url . '?depoid=' . $depoid, 'Raf başarıyla eklendi.');
}

if ($action == 'add_bolum' && $rafid > 0) {
    $bolum = new stdClass();
    $bolum->rafid = $rafid;
    $bolum->kod = required_param('bolum_kod', PARAM_TEXT);
    $bolum->aciklama = optional_param('bolum_aciklama', '', PARAM_TEXT);
    $bolum->timecreated = time();
    $bolum->timemodified = time();

    $DB->insert_record('block_depo_yonetimi_bolumler', $bolum);
    $raf = $DB->get_record('block_depo_yonetimi_raflar', array('id' => $rafid));
    redirect($PAGE->url . '?depoid=' . $raf->depoid, 'Bölüm başarıyla eklendi.');
}

// Sayfa içeriği
echo $OUTPUT->header();
echo $OUTPUT->heading('Raf Yönetimi');
?>

    <div class="row mb-4">
        <div class="col-12">
            <form action="" method="get" class="form-inline">
                <div class="form-group">
                    <label for="depoid" class="mr-2">Depo Seçin:</label>
                    <select name="depoid" id="depoid" class="form-control" onchange="this.form.submit()">
                        <option value="0">Seçiniz</option>
                        <?php foreach ($depolar as $d): ?>
                            <option value="<?php echo $d->id; ?>" <?php echo $depoid == $d->id ? 'selected' : ''; ?>>
                                <?php echo $d->name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

<?php if ($depo): ?>
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Yeni Raf Ekle</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo $PAGE->url; ?>" method="post">
                        <input type="hidden" name="action" value="add_raf">
                        <input type="hidden" name="depoid" value="<?php echo $depoid; ?>">

                        <div class="form-group mb-3">
                            <label for="raf_kod">Raf Kodu:</label>
                            <input type="text" class="form-control" id="raf_kod" name="raf_kod" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="raf_aciklama">Açıklama:</label>
                            <textarea class="form-control" id="raf_aciklama" name="raf_aciklama" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Raf Ekle</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Raflar ve Bölümler</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="raflarAccordion">
                        <?php
                        $raflar = $DB->get_records('block_depo_yonetimi_raflar', array('depoid' => $depoid));
                        foreach ($raflar as $raf):
                            $bolumler = $DB->get_records('block_depo_yonetimi_bolumler', array('rafid' => $raf->id));
                            ?>
                            <div class="accordion-item mb-3 border">
                                <h2 class="accordion-header" id="heading<?php echo $raf->id; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse<?php echo $raf->id; ?>">
                                        <strong>Raf: <?php echo $raf->kod; ?></strong>
                                        <?php if (!empty($raf->aciklama)): ?>
                                            - <?php echo $raf->aciklama; ?>
                                        <?php endif; ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $raf->id; ?>" class="accordion-collapse collapse"
                                     aria-labelledby="heading<?php echo $raf->id; ?>">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-lg-5">
                                                <h6>Yeni Bölüm Ekle</h6>
                                                <form action="<?php echo $PAGE->url; ?>" method="post">
                                                    <input type="hidden" name="action" value="add_bolum">
                                                    <input type="hidden" name="rafid" value="<?php echo $raf->id; ?>">

                                                    <div class="form-group mb-2">
                                                        <label for="bolum_kod<?php echo $raf->id; ?>">Bölüm Kodu:</label>
                                                        <input type="text" class="form-control form-control-sm"
                                                               id="bolum_kod<?php echo $raf->id; ?>" name="bolum_kod" required>
                                                    </div>

                                                    <div class="form-group mb-2">
                                                        <label for="bolum_aciklama<?php echo $raf->id; ?>">Açıklama:</label>
                                                        <input type="text" class="form-control form-control-sm"
                                                               id="bolum_aciklama<?php echo $raf->id; ?>" name="bolum_aciklama">
                                                    </div>

                                                    <button type="submit" class="btn btn-sm btn-primary">Bölüm Ekle</button>
                                                </form>
                                            </div>

                                            <div class="col-lg-7">
                                                <h6>Mevcut Bölümler</h6>
                                                <?php if (count($bolumler) > 0): ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-striped">
                                                            <thead>
                                                            <tr>
                                                                <th>Bölüm Kodu</th>
                                                                <th>Açıklama</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <?php foreach ($bolumler as $bolum): ?>
                                                                <tr>
                                                                    <td><?php echo $bolum->kod; ?></td>
                                                                    <td><?php echo $bolum->aciklama; ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-info">Bu rafa henüz bölüm eklenmemiş.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($raflar) == 0): ?>
                            <div class="alert alert-info">Bu depoya henüz raf eklenmemiş.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Lütfen bir depo seçin.</div>
<?php endif; ?>

<?php
echo $OUTPUT->footer();
?>