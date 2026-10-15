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
    $warna          = trim($_POST['warna'] ?? '');
    if ($warna !== '' && !preg_match('/^#[a-fA-F0-9]{3,8}$/', $warna)) {
        $warna = '';
    }
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
        deleteUploadedFile($file_sertifikat, 'pemeringkatan');
        deleteUploadedFile($file_sertifikat, 'akreditasi');
        $file_sertifikat = '';
    }

    // Handle File Upload (Support Cropped Base64 & Direct File Upload)
    $cropped_cert = trim($_POST['cropped_sertifikat_data'] ?? '');
    if (!empty($cropped_cert) && preg_match('/^data:image\/(\w+);base64,/', $cropped_cert)) {
        $raw = base64_decode(substr($cropped_cert, strpos($cropped_cert, ',') + 1));
        if ($raw !== false) {
            $upload_dir = __DIR__ . '/../uploads/pemeringkatan/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($judul ?: 'sertifikat'));
            $filename = 'sertifikat_' . $clean_name . '_' . time() . '.webp';
            if (file_put_contents($upload_dir . $filename, $raw)) {
                if ($file_sertifikat && $file_sertifikat !== $filename) {
                    deleteUploadedFile($file_sertifikat, 'pemeringkatan');
                }
                $file_sertifikat = $filename;
            } else {
                $errors[] = 'Gagal menyimpan gambar sertifikat hasil crop ke server.';
            }
        }
    } elseif (!empty($_FILES['file_sertifikat']['name'])) {
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
                if ($file_sertifikat && $file_sertifikat !== $filename) {
                    deleteUploadedFile($file_sertifikat, 'pemeringkatan');
                }
                $file_sertifikat = $filename;
            } else {
                $errors[] = 'Gagal menyimpan berkas yang diunggah ke server.';
            }
        }
    }

    if (empty($errors)) {
        $db_warna = ($warna !== '') ? $warna : null;
        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE pemeringkatan 
                SET judul = ?, lembaga = ?, kategori = ?, peringkat = ?, peringkat_dari = ?, 
                    badge_teks = ?, warna = ?, deskripsi = ?, link_url = ?, file_sertifikat = ?, tahun = ?, 
                    urutan = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $judul, $lembaga, $kategori, $peringkat, $peringkat_dari,
                $badge_teks, $db_warna, $deskripsi, $link_url, $file_sertifikat, $tahun,
                $urutan, $is_active, $id
            ]);
            $_SESSION['flash'] = 'Data pemeringkatan berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO pemeringkatan 
                (judul, lembaga, kategori, peringkat, peringkat_dari, badge_teks, warna, deskripsi, link_url, file_sertifikat, tahun, urutan, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $judul, $lembaga, $kategori, $peringkat, $peringkat_dari,
                $badge_teks, $db_warna, $deskripsi, $link_url, $file_sertifikat, $tahun,
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

                <!-- Angka Peringkat, Lingkup, Badge, dan Warna -->
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Peringkat Utama</label>
                    <input type="text" name="peringkat" class="form-control fw-bold" placeholder="Contoh: #1, Top 100, #65" value="<?= htmlspecialchars($_POST['peringkat'] ?? ($item['peringkat'] ?? '')) ?>">
                    <small class="text-muted" style="font-size:0.75rem;">Angka tebal utama kartu.</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Lingkup Pembanding</label>
                    <input type="text" name="peringkat_dari" class="form-control" placeholder="Contoh: of 14 Kampus Semarang, of 562" value="<?= htmlspecialchars($_POST['peringkat_dari'] ?? ($item['peringkat_dari'] ?? '')) ?>">
                    <small class="text-muted" style="font-size:0.75rem;">Teks sub-peringkat pendamping.</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Badge / Label Singkat</label>
                    <input type="text" name="badge_teks" class="form-control" placeholder="Contoh: PTS Terbaik Semarang" value="<?= htmlspecialchars($_POST['badge_teks'] ?? ($item['badge_teks'] ?? '')) ?>">
                    <small class="text-muted" style="font-size:0.75rem;">Label sorotan kecil.</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Warna Aksen Kartu (Opsional)</label>
                    <?php $item_warna = $_POST['warna'] ?? ($item['warna'] ?? ''); ?>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color p-1" id="warnaPicker" value="<?= $item_warna ?: '#1E3A8A' ?>" style="width:44px;max-width:44px;height:38px;border-radius:8px 0 0 8px;cursor:pointer;" oninput="document.getElementById('warnaInput').value = this.value;">
                        <input type="text" name="warna" id="warnaInput" class="form-control" placeholder="#HEX (default segmen)" value="<?= htmlspecialchars($item_warna) ?>" maxlength="10" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('warnaPicker').value = this.value;">
                        <button type="button" class="btn btn-outline-secondary btn-sm" title="Gunakan Default Segmen" onclick="document.getElementById('warnaInput').value='';">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:0.72rem;">Kosongkan jika ingin mengikuti warna segmen.</small>
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
                    $is_img_cert = in_array(strtolower(pathinfo($item['file_sertifikat'], PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp']);
                    ?>
                    <div class="p-2 mb-2 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <i class="bi bi-file-earmark-check-fill text-success fs-4"></i>
                            <div class="text-truncate">
                                <div class="fw-bold small text-truncate"><?= e($item['file_sertifikat']) ?></div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="<?= $f_url ?>" target="_blank" class="text-primary text-decoration-none" style="font-size:0.75rem;">
                                        <i class="bi bi-eye me-1"></i> Buka Berkas
                                    </a>
                                    <?php if ($is_img_cert): ?>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:0.7rem;" onclick="editExistingCert('<?= $f_url ?>')">
                                        <i class="bi bi-crop text-warning me-1"></i>Edit / Crop
                                    </button>
                                    <?php endif; ?>
                                </div>
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
                    <div class="d-flex gap-2 align-items-center">
                        <input type="file" name="file_sertifikat" id="inputCertFile" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <button type="button" class="btn btn-outline-primary d-none align-items-center gap-1 fw-semibold" id="btnEditCertImg" style="white-space:nowrap;padding:0.55rem 0.9rem;border-radius:8px;">
                            <i class="bi bi-crop text-warning"></i> Sesuaikan / Edit Gambar
                        </button>
                    </div>
                    <input type="hidden" name="cropped_sertifikat_data" id="croppedSertifikatData">
                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Maksimal 10MB (PDF, JPG, PNG, WebP). Berkas gambar dapat disesuaikan framingnya menggunakan editor crop interaktif.</small>
                    <div id="liveCertPreview" class="mt-2 d-none">
                        <div class="p-2 bg-light rounded-3 border d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <img id="liveCertImg" src="" alt="Preview" style="height:60px;border-radius:6px;object-fit:cover;border:1px solid #CBD5E1;">
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success mb-1" id="cropCertBadge">
                                        <i class="bi bi-check-circle-fill me-1"></i> Telah Disesuaikan &amp; Di-crop
                                    </span>
                                    <div class="small text-muted" style="font-size:0.75rem;">Gambar siap disimpan ke server dalam format WebP HD.</div>
                                </div>
                            </div>
                        </div>
                    </div>
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

<!-- Modal Cropper Universal Pemeringkatan -->
<div class="modal fade" id="modalCropCert" tabindex="-1" aria-labelledby="modalCropCertLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropCertLabel">Editor Gambar Sertifikat: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing berkas bukti atau sertifikat pemeringkatan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgCert" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHCert">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVCert">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftCert">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightCert">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInCert">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutCert">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropCert">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-cert fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-cert fw-semibold" data-ratio="1.777778">16:9 Banner</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-cert fw-semibold" data-ratio="1">1:1 Persegi</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-cert fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropCert" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cropperCert = null;
    let scaleXCert = 1;
    let scaleYCert = 1;
    let currentRatioCert = 4 / 3;

    const modalCropCertEl  = document.getElementById('modalCropCert');
    if (!modalCropCertEl) return;
    const bsModalCert      = new bootstrap.Modal(modalCropCertEl);
    const sourceImgCert    = document.getElementById('cropperSourceImgCert');
    const fileInputCert    = document.getElementById('inputCertFile');
    const btnEditCert      = document.getElementById('btnEditCertImg');
    const liveCertWrap     = document.getElementById('liveCertPreview');
    const liveCertImg      = document.getElementById('liveCertImg');
    const hiddenCropCert   = document.getElementById('croppedSertifikatData');
    const applyBtnCert     = document.getElementById('btnApplyCropCert');

    function initCropperCert() {
        if (cropperCert) cropperCert.destroy();
        scaleXCert = 1;
        scaleYCert = 1;
        cropperCert = new Cropper(sourceImgCert, {
            aspectRatio: currentRatioCert,
            viewMode: 1,
            autoCropArea: 0.95,
            responsive: true,
            guides: true,
            checkCrossOrigin: false
        });
    }

    modalCropCertEl.addEventListener('shown.bs.modal', function() { initCropperCert(); });
    modalCropCertEl.addEventListener('hidden.bs.modal', function() {
        if (cropperCert) {
            cropperCert.destroy();
            cropperCert = null;
        }
    });

    // Controls
    document.getElementById('btnRotateLeftCert')?.addEventListener('click', function() { if (cropperCert) cropperCert.rotate(-90); });
    document.getElementById('btnRotateRightCert')?.addEventListener('click', function() { if (cropperCert) cropperCert.rotate(90); });
    document.getElementById('btnFlipHCert')?.addEventListener('click', function() { if (cropperCert) { scaleXCert = -scaleXCert; cropperCert.scaleX(scaleXCert); } });
    document.getElementById('btnFlipVCert')?.addEventListener('click', function() { if (cropperCert) { scaleYCert = -scaleYCert; cropperCert.scaleY(scaleYCert); } });
    document.getElementById('btnZoomInCert')?.addEventListener('click', function() { if (cropperCert) cropperCert.zoom(0.1); });
    document.getElementById('btnZoomOutCert')?.addEventListener('click', function() { if (cropperCert) cropperCert.zoom(-0.1); });
    document.getElementById('btnResetCropCert')?.addEventListener('click', function() { if (cropperCert) { cropperCert.reset(); scaleXCert = 1; scaleYCert = 1; } });

    document.querySelectorAll('.ratio-btn-cert').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn-cert').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            const val = parseFloat(this.getAttribute('data-ratio'));
            currentRatioCert = isNaN(val) ? NaN : val;
            if (cropperCert) cropperCert.setAspectRatio(currentRatioCert);
        });
    });

    fileInputCert?.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name);
            if (isImage) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    sourceImgCert.src = e.target.result;
                    liveCertImg.src = e.target.result;
                    liveCertWrap.classList.remove('d-none');
                    btnEditCert.classList.remove('d-none');
                    btnEditCert.classList.add('d-inline-flex');
                    hiddenCropCert.value = '';
                    bsModalCert.show();
                };
                reader.readAsDataURL(file);
            } else {
                btnEditCert.classList.add('d-none');
                liveCertWrap.classList.add('d-none');
            }
        }
    });

    btnEditCert?.addEventListener('click', function() {
        if (sourceImgCert.src) {
            bsModalCert.show();
        }
    });

    window.editExistingCert = function(imgUrl) {
        sourceImgCert.src = imgUrl;
        bsModalCert.show();
    };

    applyBtnCert?.addEventListener('click', function() {
        if (!cropperCert) return;
        const canvas = cropperCert.getCroppedCanvas({
            maxWidth: 1920,
            maxHeight: 1200,
            imageSmoothingQuality: 'high'
        });
        if (canvas) {
            const dataUrl = canvas.toDataURL('image/webp', 0.88);
            hiddenCropCert.value = dataUrl;
            liveCertImg.src = dataUrl;
            liveCertWrap.classList.remove('d-none');
            btnEditCert.classList.remove('d-none');
            btnEditCert.classList.add('d-inline-flex');
            if (fileInputCert) fileInputCert.value = '';
            bsModalCert.hide();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
