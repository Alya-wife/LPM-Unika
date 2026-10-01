<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Form Akreditasi Prodi';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = [
    'fakultas' => '', 'program_studi' => '', 'strata' => 'S1',
    'peringkat' => '', 'lembaga' => '', 'no_sk' => '',
    'file_sk' => '', 'masa_berlaku' => date('Y-m-d'), 'file_sertifikat' => ''
];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM akreditasi_prodi WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $p = $row;
    else $id = 0;
}

$lembaga_options = $db->query("SELECT kode, nama FROM lembaga_akreditasi ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fakultas = trim($_POST['fakultas'] ?? '');
    $program_studi = trim($_POST['program_studi'] ?? '');
    $strata = trim($_POST['strata'] ?? 'S1');
    $peringkat = trim($_POST['peringkat'] ?? '');
    
    // Handle Lembaga (from dropdown or custom input)
    $lembaga_select = trim($_POST['lembaga_select'] ?? '');
    $lembaga_custom = trim($_POST['lembaga_custom'] ?? '');
    if ($lembaga_select === '__OTHER__') {
        $lembaga = $lembaga_custom;
    } else {
        $lembaga = $lembaga_select ?: $lembaga_custom;
    }
    if (empty($lembaga)) {
        $lembaga = trim($_POST['lembaga'] ?? '');
    }

    $no_sk = trim($_POST['no_sk'] ?? '');
    $masa_berlaku = trim($_POST['masa_berlaku'] ?? date('Y-m-d'));
    
    $file_sk = $p['file_sk'] ?? '';
    $file_sertifikat = $p['file_sertifikat'] ?? '';
    $upload_dir = __DIR__ . '/../uploads/akreditasi_prodi/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    // Handle File SK
    if (isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sk']['tmp_name'];
        $name = $_FILES['file_sk']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $new_sk_name = saveOrConvertToPdf($tmp, $upload_dir, 'sk_', $name);
            if ($new_sk_name) {
                if ($file_sk && file_exists($upload_dir . $file_sk)) {
                    @unlink($upload_dir . $file_sk);
                    $old_thumb = $upload_dir . pathinfo($file_sk, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                }
                $file_sk = $new_sk_name;
            }
        }
    }

    // Handle File Sertifikat
    if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sertifikat']['tmp_name'];
        $name = $_FILES['file_sertifikat']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $new_name = saveOrConvertToPdf($tmp, $upload_dir, 'prodi_', $name);
            if ($new_name) {
                if ($file_sertifikat && file_exists($upload_dir . $file_sertifikat)) {
                    @unlink($upload_dir . $file_sertifikat);
                    $old_thumb = $upload_dir . pathinfo($file_sertifikat, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                }
                $file_sertifikat = $new_name;
            }
        }
    }
    
    if ($id > 0) {
        $db->prepare("UPDATE akreditasi_prodi SET fakultas=?, program_studi=?, strata=?, peringkat=?, lembaga=?, no_sk=?, file_sk=?, masa_berlaku=?, file_sertifikat=? WHERE id=?")
           ->execute([$fakultas, $program_studi, $strata, $peringkat, $lembaga, $no_sk, $file_sk, $masa_berlaku, $file_sertifikat, $id]);
        $_SESSION['flash'] = 'Data berhasil diupdate.';
    } else {
        $db->prepare("INSERT INTO akreditasi_prodi (fakultas, program_studi, strata, peringkat, lembaga, no_sk, file_sk, masa_berlaku, file_sertifikat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
           ->execute([$fakultas, $program_studi, $strata, $peringkat, $lembaga, $no_sk, $file_sk, $masa_berlaku, $file_sertifikat]);
        $_SESSION['flash'] = 'Data berhasil ditambahkan.';
    }
    redirect(SITE_URL . '/admin/akreditasi-prodi-list.php');
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title"><?= $id > 0 ? 'Edit' : 'Tambah' ?> Akreditasi Prodi</div>
        <a href="akreditasi-prodi-list.php" class="btn-outline">Kembali</a>
    </div>

    <form method="post" enctype="multipart/form-data" class="lpm-form" style="padding:1.5rem;">
        
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Fakultas <span class="text-danger">*</span></label>
                <input type="text" name="fakultas" class="form-control" value="<?= e($p['fakultas']) ?>" required placeholder="Contoh: Fakultas Ilmu Komputer">
            </div>
            <div class="col-md-4 mt-4 mt-md-0">
                <label class="form-label fw-bold">Program Studi <span class="text-danger">*</span></label>
                <input type="text" name="program_studi" class="form-control" value="<?= e($p['program_studi']) ?>" required placeholder="Contoh: Sistem Informasi">
            </div>
            <div class="col-md-2 mt-4 mt-md-0">
                <label class="form-label fw-bold">Strata <span class="text-danger">*</span></label>
                <select name="strata" class="form-control" required>
                    <option value="D3" <?= $p['strata'] === 'D3' ? 'selected' : '' ?>>D3</option>
                    <option value="D4" <?= $p['strata'] === 'D4' ? 'selected' : '' ?>>D4</option>
                    <option value="S1" <?= $p['strata'] === 'S1' ? 'selected' : '' ?>>S1</option>
                    <option value="S2" <?= $p['strata'] === 'S2' ? 'selected' : '' ?>>S2</option>
                    <option value="S3" <?= $p['strata'] === 'S3' ? 'selected' : '' ?>>S3</option>
                    <option value="Profesi" <?= $p['strata'] === 'Profesi' ? 'selected' : '' ?>>Profesi</option>
                </select>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Peringkat <span class="text-danger">*</span></label>
                <input type="text" name="peringkat" class="form-control" value="<?= e($p['peringkat']) ?>" required placeholder="Contoh: Unggul / Baik Sekali">
            </div>
            <div class="col-md-8 mt-4 mt-md-0">
                <label class="form-label fw-bold">Lembaga Akreditasi <span class="text-danger">*</span></label>
                <?php 
                $is_custom_lembaga = true;
                $clean_current_lem = strtolower(str_replace([' ', '-', '_'], '', $p['lembaga']));
                foreach ($lembaga_options as $lo) {
                    if (strtolower(str_replace([' ', '-', '_'], '', $lo['kode'])) === $clean_current_lem) {
                        $is_custom_lembaga = false;
                        break;
                    }
                }
                if (empty($p['lembaga'])) $is_custom_lembaga = false;
                ?>
                <select name="lembaga_select" id="lembaga_select" class="form-select form-control" onchange="handleLembagaSelectChange(this)" required>
                    <option value="">-- Pilih Lembaga Akreditasi --</option>
                    <?php foreach ($lembaga_options as $lo): 
                        $is_sel = (strtolower(str_replace([' ', '-', '_'], '', $lo['kode'])) === $clean_current_lem);
                    ?>
                        <option value="<?= e($lo['kode']) ?>" <?= $is_sel ? 'selected' : '' ?>>
                            <?= e($lo['kode']) ?> - <?= e($lo['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__OTHER__" <?= ($is_custom_lembaga && !empty($p['lembaga'])) ? 'selected' : '' ?>>
                        + Lembaga Lainnya / Akreditasi Internasional
                    </option>
                </select>

                <div id="wrapCustomLembaga" class="mt-2" style="<?= ($is_custom_lembaga && !empty($p['lembaga'])) ? 'display:block;' : 'display:none;' ?>">
                    <input type="text" name="lembaga_custom" id="lembaga_custom" class="form-control" value="<?= ($is_custom_lembaga && !empty($p['lembaga'])) ? e($p['lembaga']) : '' ?>" placeholder="Ketik nama lembaga lainnya (contoh: AQAS, ASIIN, dsb)">
                    <div class="form-text" style="font-size:0.75rem;">Gunakan opsi ini hanya jika lembaga akreditasi belum tercantum pada daftar resmi di atas.</div>
                </div>
                <input type="hidden" name="lembaga" id="lembaga_final" value="<?= e($p['lembaga']) ?>">

                <script>
                function handleLembagaSelectChange(selectEl) {
                    var wrap = document.getElementById('wrapCustomLembaga');
                    var customInput = document.getElementById('lembaga_custom');
                    var finalInput = document.getElementById('lembaga_final');
                    if (selectEl.value === '__OTHER__') {
                        wrap.style.display = 'block';
                        customInput.required = true;
                        customInput.focus();
                        finalInput.value = customInput.value;
                    } else {
                        wrap.style.display = 'none';
                        customInput.required = false;
                        finalInput.value = selectEl.value;
                    }
                }
                document.getElementById('lembaga_custom')?.addEventListener('input', function() {
                    document.getElementById('lembaga_final').value = this.value;
                });
                </script>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label fw-bold">Nomor SK <span class="text-danger">*</span></label>
                <input type="text" name="no_sk" class="form-control" value="<?= e($p['no_sk']) ?>" required placeholder="Contoh: 111/SK/LAM-INFOKOM/Akred/S/I/2025">
            </div>
            <div class="col-md-4 mt-4 mt-md-0">
                <label class="form-label fw-bold">Berlaku Sampai <span class="text-danger">*</span></label>
                <input type="date" name="masa_berlaku" class="form-control" value="<?= e($p['masa_berlaku']) ?>" required>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">File Surat Keputusan (SK) <span class="badge bg-light text-primary border ms-1" style="font-size:0.75rem;">Otomatis PDF</span></label>
                <input type="file" name="file_sk" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="text-muted d-block mt-1">PDF atau Gambar (JPG, PNG, WebP). Gambar otomatis diubah menjadi dokumen PDF.</small>
                <?php if (!empty($p['file_sk'])): ?>
                <div class="mt-2 text-muted small">
                    File SK saat ini: <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= e($p['file_sk']) ?>" target="_blank" class="fw-bold text-primary"><?= e($p['file_sk']) ?></a>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 mt-3 mt-md-0">
                <label class="form-label fw-bold">File Sertifikat <span class="badge bg-light text-primary border ms-1" style="font-size:0.75rem;">Otomatis PDF</span></label>
                <input type="file" name="file_sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="text-muted d-block mt-1">PDF atau Gambar (JPG, PNG, WebP). Gambar otomatis diubah menjadi dokumen PDF.</small>
                <?php if (!empty($p['file_sertifikat'])): ?>
                <div class="mt-2 text-muted small">
                    File Sertifikat saat ini: <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= e($p['file_sertifikat']) ?>" target="_blank" class="fw-bold text-success"><?= e($p['file_sertifikat']) ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="border-top pt-4 text-end">
            <button type="submit" class="btn-save px-4 py-2">Simpan Data</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
