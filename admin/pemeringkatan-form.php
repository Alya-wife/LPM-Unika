<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Form Pemeringkatan Kampus';
$current_admin = 'pemeringkatan-form';
$db = getDB();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$item = null;
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM pemeringkatan WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        $_SESSION['flash'] = 'Data pemeringkatan tidak ditemukan.';
        redirect(SITE_URL . '/admin/pemeringkatan-list.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul          = trim($_POST['judul'] ?? '');
    $lembaga        = trim($_POST['lembaga'] ?? '');
    $kategori       = trim($_POST['kategori'] ?? 'nasional');
    $peringkat      = trim($_POST['peringkat'] ?? '');
    $peringkat_dari = trim($_POST['peringkat_dari'] ?? '');
    $badge_teks     = trim($_POST['badge_teks'] ?? '');
    $deskripsi      = trim($_POST['deskripsi'] ?? '');
    $link_url       = trim($_POST['link_url'] ?? '');
    $tahun          = trim($_POST['tahun'] ?? date('Y'));
    $urutan         = (int)($_POST['urutan'] ?? 1);
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    if ($judul === '') {
        $errors[] = 'Judul pemeringkatan wajib diisi.';
    }
    if ($lembaga === '') {
        $errors[] = 'Nama lembaga/penyelenggara pemeringkatan wajib diisi.';
    }
    if (!in_array($kategori, ['lokal', 'nasional', 'internasional'])) {
        $kategori = 'nasional';
    }

    $file_sertifikat = $item['file_sertifikat'] ?? '';

    // Handle delete existing certificate
    if (!empty($_POST['hapus_sertifikat']) && $file_sertifikat !== '') {
        $f1 = __DIR__ . '/../uploads/pemeringkatan/' . $file_sertifikat;
        $f2 = __DIR__ . '/../uploads/akreditasi/' . $file_sertifikat;
        if (file_exists($f1)) @unlink($f1);
        if (file_exists($f2)) @unlink($f2);
        $file_sertifikat = '';
    }

    // Handle File Upload
    if (!empty($_FILES['file_sertifikat']['name'])) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['file_sertifikat']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format berkas sertifikat tidak valid. Gunakan PDF, JPG, PNG, atau WebP.';
        } elseif ($_FILES['file_sertifikat']['size'] > 10 * 1024 * 1024) {
            $errors[] = 'Ukuran berkas melebihi batas 10MB.';
        } else {
            $upload_dir = __DIR__ . '/../uploads/pemeringkatan/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(pathinfo($_FILES['file_sertifikat']['name'], PATHINFO_FILENAME)));
            $filename = 'sertifikat_' . $clean_name . '_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['file_sertifikat']['tmp_name'], $upload_dir . $filename)) {
                $file_sertifikat = $filename;
            } else {
                $errors[] = 'Gagal menyimpan berkas yang diunggah ke server.';
            }
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE pemeringkatan 
                SET judul = ?, lembaga = ?, kategori = ?, peringkat = ?, peringkat_dari = ?, 
                    badge_teks = ?, deskripsi = ?, link_url = ?, file_sertifikat = ?, tahun = ?, 
                    urutan = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $judul, $lembaga, $kategori, $peringkat, $peringkat_dari,
                $badge_teks, $deskripsi, $link_url, $file_sertifikat, $tahun,
                $urutan, $is_active, $id
            ]);
            $_SESSION['flash'] = 'Data pemeringkatan berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO pemeringkatan 
                (judul, lembaga, kategori, peringkat, peringkat_dari, badge_teks, deskripsi, link_url, file_sertifikat, tahun, urutan, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $judul, $lembaga, $kategori, $peringkat, $peringkat_dari,
                $badge_teks, $deskripsi, $link_url, $file_sertifikat, $tahun,
                $urutan, $is_active
            ]);
            $_SESSION['flash'] = 'Data pemeringkatan baru berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/pemeringkatan-list.php?kategori=' . urlencode($kategori));
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            <?= $id > 0 ? 'Sunting Data Pemeringkatan' : 'Tambah Pemeringkatan Baru' ?>
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Lengkapi informasi rekognisi, segmen (Lokal, Nasional, Internasional), angka peringkat, tautan sumber, dan berkas sertifikat.
        </p>
    </div>
    <div>
        <a href="<?= SITE_URL ?>/admin/pemeringkatan-list.php" class="btn btn-sm btn-outline-secondary fw-semibold" style="border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger rounded-3 mb-4 shadow-sm" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Terjadi kesalahan:</div>
    <ul class="mb-0 ps-3 small">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden mb-5">
    <div class="card-header bg-white py-3 px-4 border-bottom">
        <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">Form Data Pemeringkatan</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="row g-4 mb-4">
                <!-- Segmentasi Kategori -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Segmentasi Wilayah / Skala <span class="text-danger">*</span></label>
                    <?php $curr_kat = $_POST['kategori'] ?? ($item['kategori'] ?? 'nasional'); ?>
                    <div class="d-flex gap-3 mt-1">
                        <label class="form-check p-3 border rounded-3 flex-fill cursor-pointer <?= $curr_kat === 'lokal' ? 'border-primary bg-primary bg-opacity-10' : '' ?>" style="cursor:pointer;">
                            <input class="form-check-input me-2" type="radio" name="kategori" value="lokal" <?= $curr_kat === 'lokal' ? 'checked' : '' ?> required>
                            <span class="fw-bold text-primary"><i class="bi bi-geo-alt-fill me-1"></i> Lokal</span>
                            <small class="d-block text-muted" style="font-size:0.75rem;">Semarang &amp; Jawa Tengah</small>
                        </label>
                        <label class="form-check p-3 border rounded-3 flex-fill cursor-pointer <?= $curr_kat === 'nasional' ? 'border-warning bg-warning bg-opacity-10' : '' ?>" style="cursor:pointer;">
                            <input class="form-check-input me-2" type="radio" name="kategori" value="nasional" <?= $curr_kat === 'nasional' ? 'checked' : '' ?> required>
                            <span class="fw-bold text-dark"><i class="bi bi-flag-fill text-warning me-1"></i> Nasional</span>
                            <small class="d-block text-muted" style="font-size:0.75rem;">Tingkat Indonesia</small>
                        </label>
                        <label class="form-check p-3 border rounded-3 flex-fill cursor-pointer <?= $curr_kat === 'internasional' ? 'border-purple bg-purple bg-opacity-10' : '' ?>" style="cursor:pointer;">
                            <input class="form-check-input me-2" type="radio" name="kategori" value="internasional" <?= $curr_kat === 'internasional' ? 'checked' : '' ?> required>
                            <span class="fw-bold text-purple"><i class="bi bi-globe2 me-1"></i> Internasional</span>
                            <small class="d-block text-muted" style="font-size:0.75rem;">Global &amp; Dunia</small>
                        </label>
                    </div>
                </div>

                <!-- Lembaga Penyelenggara -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Lembaga / Badan Penilai <span class="text-danger">*</span></label>
                    <input type="text" name="lembaga" class="form-control" placeholder="Contoh: EduRank.org, uniRank, Marketeers, AD Scientific Index, UI GreenMetric" value="<?= htmlspecialchars($_POST['lembaga'] ?? ($item['lembaga'] ?? '')) ?>" required>
                    <small class="text-muted" style="font-size:0.75rem;">Nama institusi independen atau media yang merilis peringkat/penghargaan.</small>
                </div>

                <!-- Judul Pemeringkatan -->
                <div class="col-12">
                    <label class="form-label fw-bold small text-dark">Judul Capaian Pemeringkatan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: EduRank: PTS Nomor 1 di Kota Semarang" value="<?= htmlspecialchars($_POST['judul'] ?? ($item['judul'] ?? '')) ?>" required>
                </div>

                <!-- Angka Peringkat & Lingkup -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Peringkat / Capaian Utama</label>
                    <input type="text" name="peringkat" class="form-control fw-bold" placeholder="Contoh: #1, Top 100, #65, Awardee 2026" value="<?= htmlspecialchars($_POST['peringkat'] ?? ($item['peringkat'] ?? '')) ?>">
                    <small class="text-muted" style="font-size:0.75rem;">Ditampilkan sebagai angka tebal utama pada kartu.</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Lingkup / Pembanding (of ...)</label>
                    <input type="text" name="peringkat_dari" class="form-control" placeholder="Contoh: of 14 Kampus Semarang, of 562, World, 55 Kampus Jateng" value="<?= htmlspecialchars($_POST['peringkat_dari'] ?? ($item['peringkat_dari'] ?? '')) ?>">
                    <small class="text-muted" style="font-size:0.75rem;">Teks sub-peringkat pendamping.</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Badge / Label Teks Singkat</label>
                    <input type="text" name="badge_teks" class="form-control" placeholder="Contoh: PTS Terbaik di Semarang 2026" value="<?= htmlspecialchars($_POST['badge_teks'] ?? ($item['badge_teks'] ?? '')) ?>">
                </div>

                <!-- Deskripsi Lengkap -->
                <div class="col-12">
                    <label class="form-label fw-bold small text-dark">Deskripsi &amp; Ulasan Capaian</label>
                    <textarea name="deskripsi" rows="3" class="form-control" placeholder="Tuliskan ringkasan prestasi, indikator penilaian, atau rincian pencapaian..."><?= htmlspecialchars($_POST['deskripsi'] ?? ($item['deskripsi'] ?? '')) ?></textarea>
                </div>

                <!-- Tautan Sumber Berita / Rilis Resmi -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Tautan Sumber Berita / Direktori Resmi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-link-45deg"></i></span>
                        <input type="url" name="link_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($_POST['link_url'] ?? ($item['link_url'] ?? '')) ?>">
                    </div>
                    <small class="text-muted" style="font-size:0.75rem;">Tautan artikel berita (Espos, Suara Merdeka) atau direktori perankingan resmi.</small>
                </div>

                <!-- Berkas Sertifikat / PDF -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Berkas Sertifikat / Dokumen Bukti (PDF / Gambar)</label>
                    <?php if (!empty($item['file_sertifikat'])): ?>
                    <?php 
                    $f_url = SITE_URL . '/uploads/pemeringkatan/' . $item['file_sertifikat'];
                    if (!file_exists(__DIR__ . '/../uploads/pemeringkatan/' . $item['file_sertifikat'])) {
                        $f_url = SITE_URL . '/uploads/akreditasi/' . $item['file_sertifikat'];
                    }
                    ?>
                    <div class="p-2 mb-2 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <i class="bi bi-file-earmark-check-fill text-success fs-4"></i>
                            <div class="text-truncate">
                                <div class="fw-bold small text-truncate"><?= e($item['file_sertifikat']) ?></div>
                                <a href="<?= $f_url ?>" target="_blank" class="text-primary text-decoration-none" style="font-size:0.75rem;">
                                    <i class="bi bi-eye me-1"></i> Buka Berkas
                                </a>
                            </div>
                        </div>
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" name="hapus_sertifikat" value="1" id="hapusCert">
                            <label class="form-check-label text-danger small fw-semibold" for="hapusCert">
                                Hapus
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="file_sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <small class="text-muted" style="font-size:0.75rem;">Maksimal 10MB (PDF, JPG, PNG, WebP).</small>
                </div>

                <!-- Tahun & Urutan -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Tahun Capaian</label>
                    <input type="text" name="tahun" class="form-control" placeholder="2026" value="<?= htmlspecialchars($_POST['tahun'] ?? ($item['tahun'] ?? date('Y'))) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Urutan Tampil</label>
                    <input type="number" name="urutan" class="form-control" min="1" value="<?= htmlspecialchars($_POST['urutan'] ?? ($item['urutan'] ?? '1')) ?>">
                    <small class="text-muted" style="font-size:0.75rem;">Angka lebih kecil tampil lebih dulu pada segmen terkait.</small>
                </div>

                <div class="col-md-4 d-flex align-items-center pt-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" <?= (!isset($item) || $item['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold text-dark" for="isActive">
                            Publikasikan di Halaman Web
                        </label>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= SITE_URL ?>/admin/pemeringkatan-list.php" class="btn btn-outline-secondary px-4 fw-semibold" style="border-radius:10px;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary px-5 fw-bold" style="border-radius:10px;background:var(--navy);border:none;">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Data Pemeringkatan
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
