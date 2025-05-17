<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../../../config.php');
require_login();
global $DB, $PAGE, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/depo_yonetimi/actions/kategori_list.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Kategori Listesi');
$PAGE->set_heading('Kategori Listesi');

$kategoriler = $DB->get_records('block_depo_yonetimi_kategoriler', null, 'name ASC');

echo $OUTPUT->header();
?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-folder me-2"></i>
                            <h5 class="mb-0">Kategoriler</h5>
                        </div>
                        <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_ekle.php'); ?>"
                           class="btn btn-light btn-sm">
                            <i class="fas fa-plus-circle me-2"></i>Yeni Kategori
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($kategoriler)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="border-0">
                                            <i class="fas fa-tag me-2"></i>Kategori Adı
                                        </th>
                                        <th scope="col" class="border-0 text-end">İşlemler</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($kategoriler as $kategori): ?>
                                        <tr>
                                            <td class="align-middle">
                                                <?php echo htmlspecialchars($kategori->name); ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_duzenle.php', ['kategoriid' => $kategori->id]); ?>"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo new moodle_url('/blocks/depo_yonetimi/actions/kategori_sil.php', ['kategoriid' => $kategori->id]); ?>"
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Sil"
                                                       onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <p class="mb-0">Henüz kategori bulunmamaktadır.</p>
                                <p class="mb-0">Yeni kategori eklemek için yukarıdaki butonu kullanabilirsiniz.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DataTable özelliklerini ekleyebilirsiniz
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                $('.table').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
                    },
                    pageLength: 10,
                    ordering: true,
                    responsive: true
                });
            }
        });
    </script>

<?php
echo $OUTPUT->footer();
?>