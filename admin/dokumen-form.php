<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$dokumen = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM dokumen WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $dokumen = $stmt->fetch();
    if ($dokumen) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Dokumen SPMI' : 'Upload Dokumen SPMI';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_dokumen = trim($_POST['nama_dokumen'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $id           = (int)($_POST['id'] ?? 0);
    $kategori_db  = $db->query("SELECT nama_kategori FROM kategori_dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $allowed_kat  = array_unique(array_merge(['Kebijakan', 'Manual', 'Standar', 'Formulir', 'SOP', 'Laporan AMI', 'Panduan', 'Regulasi', 'Instrumen', 'Download Center'], $kategori_db));

    if (!$nama_dokumen || !in_array($kategori, $allowed_kat)) {
        $error = 'Nama dokumen dan kategori wajib diisi dengan benar.';
    } else {
        $file_path = $is_edit ? $dokumen['file_path'] : null;

        if (!empty($_FILES['file_doc']['name'])) {
            $allowed_ext = ['pdf','doc','docx','xls','xlsx','ppt','pptx'];
            $ext         = strtolower(pathinfo($_FILES['file_doc']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) {
                $error = 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX.';
            } elseif ($_FILES['file_doc']['size'] > 20 * 1024 * 1024) {
                $error = 'Ukuran file maksimal 20MB.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/dokumen/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $file_path = uniqid('dok_') . '.' . $ext;
                move_uploaded_file($_FILES['file_doc']['tmp_name'], $upload_dir . $file_path);
            }
        } elseif (!$is_edit) {
            $error = 'File dokumen wajib diupload.';
        }

        if (!$error) {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE dokumen SET nama_dokumen=?, kategori=?, file_path=? WHERE id=?");
                $stmt->execute([$nama_dokumen, $kategori, $file_path, $id]);
                $_SESSION['flash'] = 'Dokumen berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO dokumen (nama_dokumen, kategori, file_path) VALUES (?,?,?)");
                $stmt->execute([$nama_dokumen, $kategori, $file_path]);
                $_SESSION['flash'] = 'Dokumen berhasil diupload.';
            }
            redirect(SITE_URL . '/admin/dokumen-list.php');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-7">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="dokumen-list.php" style="color:var(--text-muted);">Dokumen SPMI</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title"><?= $admin_page_title ?></div>
                <a href="dokumen-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">Kembali</a>
            </div>
            <div style="padding:1.5rem;">
                <form method="POST" enctype="multipart/form-data" id="form-dokumen">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $dokumen['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="nama_dokumen">
                                Nama Dokumen <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" id="nama_dokumen" name="nama_dokumen" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;"
                                placeholder="Contoh: Kebijakan Mutu SCU Tahun 2024"
                                value="<?= e($is_edit ? $dokumen['nama_dokumen'] : ($_POST['nama_dokumen'] ?? '')) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="kategori">
                                Kategori Dokumen <span style="color:#C62828;">*</span>
                            </label>
                            <select id="kategori" name="kategori" class="form-select"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;" required>
                                 <option value="">-- Pilih Kategori Dokumen --</option>
                                <?php 
                                $categories_select = $db->query("SELECT nama_kategori FROM kategori_dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
                                if (empty($categories_select)) {
                                    $categories_select = ['Kebijakan Mutu', 'Manual Mutu', 'Standar Mutu', 'Formulir Mutu', 'SOP', 'Laporan AMI', 'Regulasi', 'Panduan', 'Instrumen', 'Download Center'];
                                }
                                foreach ($categories_select as $val): ?>
                                <option value="<?= e($val) ?>" <?= ($is_edit ? $dokumen['kategori'] : ($_POST['kategori'] ?? '')) === $val ? 'selected' : '' ?>><?= e($val) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="file_doc">
                                File Dokumen <?= $is_edit ? '' : '<span style="color:#C62828;">*</span>' ?> (PDF/DOC/DOCX/XLS/PPT, maks. 20MB)
                            </label>
                            <input type="file" id="file_doc" name="file_doc" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.6rem 1rem;"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" <?= $is_edit ? '' : 'required' ?>>
                            <?php if ($is_edit && $dokumen['file_path']): ?>
                            <div style="margin-top:0.5rem;font-size:0.78rem;color:var(--text-muted);">
                                File saat ini: <strong><?= e($dokumen['file_path']) ?></strong>
                                <a href="<?= SITE_URL ?>/uploads/dokumen/<?= e($dokumen['file_path']) ?>" target="_blank" class="text-purple" style="margin-left:6px;">Lihat file</a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Upload area visual -->
                        <div class="col-12">
                            <div style="border:2px dashed var(--border);border-radius:var(--radius-md);padding:2rem;text-align:center;cursor:pointer;transition:var(--transition);" id="drop-zone">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-light)" width="36" height="36" style="display:block;margin:0 auto 0.75rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                <div style="font-size:0.875rem;color:var(--text-muted);">Drag & drop file ke sini, atau klik tombol "Browse" di atas</div>
                                <div style="font-size:0.78rem;color:var(--text-light);margin-top:0.25rem;" id="file-info">Mendukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX</div>
                            </div>
                        </div>

                        <div class="col-12" style="padding-top:0.5rem;border-top:1px solid var(--border);margin-top:0.5rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="dokumen-list.php" style="color:var(--text-muted);font-size:0.875rem;font-weight:500;">Batal</a>
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan' : 'Upload Dokumen' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show file name on selection
document.getElementById('file_doc').addEventListener('change', function() {
    const info = document.getElementById('file-info');
    if (this.files.length > 0) {
        info.textContent = '✓ ' + this.files[0].name + ' (' + (this.files[0].size / 1024).toFixed(1) + ' KB)';
        info.style.color = 'var(--purple)';
        document.getElementById('drop-zone').style.borderColor = 'var(--purple)';
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
