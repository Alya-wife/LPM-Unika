<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Form Hasil SPMI Kemendikti Saintek';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$d = [
    'judul'        => '',
    'deskripsi'    => '',
    'file_pdf'     => '',
    'tahun'        => date('Y'),
    'urutan'       => 1,
    'is_published' => 1
];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM spmi_kemendikti WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $d = $row;
    else $id = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul        = trim($_POST['judul'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $tahun        = trim($_POST['tahun'] ?? date('Y'));
    $urutan       = (int)($_POST['urutan'] ?? 1);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    $file_pdf = $d['file_pdf'];
    
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/spmi_kemendikti/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $tmp = $_FILES['file_pdf']['tmp_name'];
        $name = $_FILES['file_pdf']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        if ($ext === 'pdf') {
            $new_name = 'spmi_kemendikti_' . time() . '_' . rand(100, 999) . '.pdf';
            if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
                if ($file_pdf && file_exists($upload_dir . $file_pdf)) {
                    unlink($upload_dir . $file_pdf);
                }
                $file_pdf = $new_name;
            }
        }
    }
    
    if ($id > 0) {
        $db->prepare("UPDATE spmi_kemendikti SET judul=?, deskripsi=?, file_pdf=?, tahun=?, urutan=?, is_published=? WHERE id=?")
           ->execute([$judul, $deskripsi, $file_pdf, $tahun, $urutan, $is_published, $id]);
        $_SESSION['flash'] = 'Dokumen Hasil SPMI Kemendikti berhasil diperbarui.';
    } else {
        $db->prepare("INSERT INTO spmi_kemendikti (judul, deskripsi, file_pdf, tahun, urutan, is_published) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$judul, $deskripsi, $file_pdf, $tahun, $urutan, $is_published]);
        $_SESSION['flash'] = 'Dokumen Hasil SPMI Kemendikti baru berhasil ditambahkan.';
    }
    redirect(SITE_URL . '/admin/spmi-kemendikti-list.php');
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title"><?= $id > 0 ? 'Edit Dokumen Hasil SPMI Kemendikti' : 'Tambah Dokumen Hasil SPMI Kemendikti Baru' ?></div>
        <a href="spmi-kemendikti-list.php" class="btn-outline">Kembali</a>
    </div>

    <form method="post" enctype="multipart/form-data" class="lpm-form" style="padding:1.5rem;">
        
        <div class="mb-4">
            <label class="form-label fw-bold">Judul Dokumen / Laporan SPMI Kemendikti Saintek <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control" value="<?= e($d['judul']) ?>" required placeholder="Contoh: Laporan Hasil Penjaminan Mutu Internal Kemendikti Saintek 2025">
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Tahun Terbit / Pelaporan <span class="text-danger">*</span></label>
                <input type="text" name="tahun" class="form-control" value="<?= e($d['tahun']) ?>" required placeholder="Contoh: 2025">
            </div>
            <div class="col-md-6 mt-4 mt-md-0">
                <label class="form-label fw-bold">Nomor Urutan Tampil <span class="text-danger">*</span></label>
                <input type="number" name="urutan" class="form-control" value="<?= (int)$d['urutan'] ?>" required min="1">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Ringkasan / Catatan Deskripsi (Opsional)</label>
            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tuliskan keterangan singkat terkait dokumen ini..."><?= e($d['deskripsi']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Upload File Dokumen PDF <?= $id == 0 ? '<span class="text-danger">*</span>' : '' ?></label>
            <input type="file" name="file_pdf" class="form-control" accept=".pdf" <?= $id == 0 && empty($d['file_pdf']) ? 'required' : '' ?>>
            <div class="form-text mt-1">
                <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 30 MB</span> Format berkas: PDF
            </div>
            <?php if ($d['file_pdf']): ?>
            <div class="mt-2 text-muted small">
                File PDF saat ini: <a href="<?= SITE_URL ?>/uploads/spmi_kemendikti/<?= e($d['file_pdf']) ?>" target="_blank" class="fw-bold text-primary">Lihat File PDF</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= $d['is_published'] ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="is_published">Publikasikan (Tampilkan di halaman SPMI publik)</label>
            </div>
        </div>
        
        <div class="border-top pt-4 text-end">
            <button type="submit" class="btn-save px-4 py-2">Simpan Dokumen</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
