<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$jenis = trim($_GET['jenis'] ?? 'prosedur'); // 'prosedur' or 'jadwal'
if (!in_array($jenis, ['prosedur', 'jadwal'])) $jenis = 'prosedur';

$id = (int)($_GET['id'] ?? 0);
$is_edit = false;
$data = [];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM ami_siklus4_dokumen WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        $is_edit = true;
        $jenis = $data['jenis'];
    }
}

$label_jenis = ($jenis === 'prosedur') ? 'Prosedur Sistem Mutu Audit Lapangan' : 'Jadwal Audit Lapangan';
$admin_page_title = $is_edit ? "Edit Dokumen {$label_jenis}" : "Upload Dokumen {$label_jenis}";

$periodes = $db->query("SELECT * FROM ami_periode WHERE is_active = 1 ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$default_periode = $_GET['periode'] ?? ($data['periode'] ?? ($periodes[0]['nama_periode'] ?? '2025/2026'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periode = trim($_POST['periode'] ?? '');
    $judul = trim($_POST['judul'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 1);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jenis = trim($_POST['jenis'] ?? $jenis);

    if ($periode === '' || $judul === '') {
        $error = 'Periode dan Judul Dokumen wajib diisi.';
    } else {
        $file_dokumen = $data['file_dokumen'] ?? '';
        $file_size = $data['file_size'] ?? '';

        if (!empty($_FILES['file_dokumen']['name'])) {
            $orig_name = $_FILES['file_dokumen']['name'];
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

            if (!in_array($ext, $allowed)) {
                $error = 'Format file tidak didukung. Harap upload dokumen PDF, Word, atau Excel.';
            } elseif ($_FILES['file_dokumen']['size'] > 30 * 1024 * 1024) {
                $error = 'Ukuran file dokumen maksimal 30MB.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/ami/siklus4/';
                if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

                $new_name = $jenis . '_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $upload_dir . $new_name)) {
                    $file_dokumen = 'ami/siklus4/' . $new_name;
                    $bytes = $_FILES['file_dokumen']['size'];
                    if ($bytes >= 1048576) {
                        $file_size = number_format($bytes / 1048576, 2) . ' MB';
                    } else {
                        $file_size = number_format($bytes / 1024, 1) . ' KB';
                    }
                } else {
                    $error = 'Gagal menyimpan file dokumen.';
                }
            }
        } elseif (!$is_edit) {
            $error = 'File dokumen wajib diunggah.';
        }

        if (!$error) {
            if ($is_edit) {
                $stmt = $db->prepare("UPDATE ami_siklus4_dokumen SET periode = ?, jenis = ?, judul = ?, file_dokumen = ?, file_size = ?, keterangan = ?, urutan = ? WHERE id = ?");
                $stmt->execute([$periode, $jenis, $judul, $file_dokumen, $file_size, $keterangan, $urutan, $id]);
                $_SESSION['flash'] = "Dokumen {$label_jenis} berhasil diperbarui.";
            } else {
                $stmt = $db->prepare("INSERT INTO ami_siklus4_dokumen (periode, jenis, judul, file_dokumen, file_size, keterangan, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$periode, $jenis, $judul, $file_dokumen, $file_size, $keterangan, $urutan]);
                $_SESSION['flash'] = "Dokumen {$label_jenis} berhasil ditambahkan.";
            }
            redirect(SITE_URL . '/admin/ami-siklus-list.php?tab=siklus4&periode=' . urlencode($periode));
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="ami-siklus-list.php?tab=siklus4&periode=<?= urlencode($default_periode) ?>" style="color:var(--text-muted);">Siklus 4 (Audit Lapangan)</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><?= $admin_page_title ?></h4>
                    <p class="text-muted small mb-0">
                        <?= ($jenis === 'prosedur') ? 'Upload dokumen Prosedur Sistem Mutu Audit Lapangan. Judul otomatis terisi dari nama file dan dapat diedit.' : 'Upload dokumen Jadwal Pelaksanaan Audit Lapangan AMI.' ?>
                    </p>
                </div>
                <span class="badge bg-purple px-3 py-2" style="font-size:0.85rem;">
                    <i class="bi bi-file-earmark-check me-1"></i> Siklus 4
                </span>
            </div>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="jenis" value="<?= e($jenis) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Periode AMI <span class="text-danger">*</span></label>
                        <select name="periode" class="form-select" required>
                            <?php foreach ($periodes as $p): ?>
                            <option value="<?= e($p['nama_periode']) ?>" <?= $default_periode === $p['nama_periode'] ? 'selected' : '' ?>>
                                <?= e($p['nama_periode']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Urutan Tampil</label>
                        <input type="number" name="urutan" class="form-control" value="<?= e($data['urutan'] ?? 1) ?>" min="1">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Pilih Dokumen Softfile <?= $is_edit ? '<span class="text-muted fw-normal">(Biarkan kosong jika tidak diganti)</span>' : '<span class="text-danger">*</span>' ?></label>
                        <div class="input-group">
                            <input type="file" name="file_dokumen" id="fileDokumen" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx" <?= $is_edit ? '' : 'required' ?> onchange="handleFileSelected(this)">
                        </div>
                        <div class="form-text text-primary">
                            <i class="bi bi-magic me-1"></i><strong>Fitur Pintar:</strong> Saat file dipilih, judul dokumen di bawah akan otomatis terisi dari nama file asli (tanpa ekstensi), namun Anda tetap dapat mengubahnya secara bebas.
                        </div>

                        <?php if (!empty($data['file_dokumen'])): ?>
                        <div class="mt-3 p-3 border rounded bg-light d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                <div>
                                    <div class="fw-bold small"><?= basename($data['file_dokumen']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;">Ukuran: <?= e($data['file_size'] ?? '-') ?></div>
                                </div>
                            </div>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_dokumen']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Unduh File Saat Ini
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Judul Dokumen <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="inputJudul" class="form-control" placeholder="Contoh: Prosedur Operasional Standar Audit Lapangan AMI" value="<?= e($data['judul'] ?? ($_POST['judul'] ?? '')) ?>" required>
                        <div class="form-text">Nama atau judul resmi dokumen yang akan ditampilkan kepada pembaca.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Keterangan Dokumen (Opsional)</label>
                        <textarea name="keterangan" rows="3" class="form-control" placeholder="Catatan atau deskripsi ringkas tentang isi dokumen..."><?= e($data['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Dokumen
                    </button>
                    <a href="ami-siklus-list.php?tab=siklus4&periode=<?= urlencode($default_periode) ?>" class="btn btn-light px-3 py-2">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        var filename = input.files[0].name;
        // Buang ekstensi
        var nameWithoutExt = filename.replace(/\.[^/.]+$/, "");
        // Rapikan dash/underscore menjadi spasi
        var cleanTitle = nameWithoutExt.replace(/[_-]+/g, " ").trim();
        // Huruf kapital awal kata
        cleanTitle = cleanTitle.replace(/\b\w/g, function(l){ return l.toUpperCase(); });
        
        var judulInput = document.getElementById('inputJudul');
        if (judulInput) {
            judulInput.value = cleanTitle;
            judulInput.classList.add('border-primary');
            setTimeout(function() {
                judulInput.classList.remove('border-primary');
            }, 1000);
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
