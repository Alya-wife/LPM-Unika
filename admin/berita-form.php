<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

// Edit mode
$is_edit = false;
$berita  = [];
$gambar_tambahan = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM berita WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $berita = $stmt->fetch();
    if ($berita) {
        $is_edit = true;
        // Ambil gambar tambahan untuk slider
        $stmt_img = $db->prepare("SELECT * FROM berita_gambar WHERE berita_id = ? ORDER BY urutan ASC, id ASC");
        $stmt_img->execute([(int)$berita['id']]);
        $gambar_tambahan = $stmt_img->fetchAll();
    }
}

// Handle Delete Foto Slider Tunggal (yang sudah tersimpan di database)
if ($is_edit && isset($_GET['delete_img']) && is_numeric($_GET['delete_img'])) {
    $img_id = (int)$_GET['delete_img'];
    $stmt_del = $db->prepare("SELECT * FROM berita_gambar WHERE id = ? AND berita_id = ?");
    $stmt_del->execute([$img_id, (int)$berita['id']]);
    $img_to_del = $stmt_del->fetch();
    if ($img_to_del) {
        $path = __DIR__ . '/../uploads/berita/' . $img_to_del['gambar'];
        if (file_exists($path)) {
            @unlink($path);
        }
        $db->prepare("DELETE FROM berita_gambar WHERE id = ?")->execute([$img_id]);
        $_SESSION['flash'] = 'Foto slide berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/berita-form.php?id=' . $berita['id']);
}

// Handle Delete Foto Utama (yang sudah tersimpan di database)
if ($is_edit && isset($_GET['delete_cover']) && $_GET['delete_cover'] == '1') {
    if (!empty($berita['gambar'])) {
        $path = __DIR__ . '/../uploads/berita/' . $berita['gambar'];
        if (file_exists($path)) {
            @unlink($path);
        }
        // Jika ada foto di slider, jadikan foto slider pertama sebagai cover baru
        if (!empty($gambar_tambahan)) {
            $first_slide = $gambar_tambahan[0];
            $db->prepare("UPDATE berita SET gambar = ? WHERE id = ?")->execute([$first_slide['gambar'], (int)$berita['id']]);
            $db->prepare("DELETE FROM berita_gambar WHERE id = ?")->execute([$first_slide['id']]);
        } else {
            $db->prepare("UPDATE berita SET gambar = NULL WHERE id = ?")->execute([(int)$berita['id']]);
        }
        $_SESSION['flash'] = 'Foto utama berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/berita-form.php?id=' . $berita['id']);
}

$admin_page_title = $is_edit ? 'Edit Berita / Kegiatan' : 'Tambah Berita / Kegiatan';
$error   = '';
$flash   = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul   = trim($_POST['judul'] ?? '');
    $tipe    = trim($_POST['tipe'] ?? 'Berita');
    $konten  = trim($_POST['konten'] ?? '');
    $tanggal = $_POST['tanggal_publikasi'] ?? '';
    $id      = (int)($_POST['id'] ?? 0);

    $tampil_di_ami = isset($_POST['tampil_di_ami']) ? 1 : 0;

    if (!$judul || !$konten) {
        $error = 'Judul dan konten wajib diisi.';
    } else {
        $slug = makeSlug($judul);
        // Ensure unique slug
        $check = $db->prepare("SELECT id FROM berita WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }

        $upload_dir = __DIR__ . '/../uploads/berita/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $allowed = ['jpg','jpeg','png','gif','webp'];

        // 1. Handle Gambar Utama (Support Cropped Base64 & Direct Upload)
        $gambar_filename = $is_edit ? ($berita['gambar'] ?? null) : null;
        $cropped_cover = trim($_POST['cropped_cover_data'] ?? '');

        if (!empty($cropped_cover) && preg_match('/^data:image\/(\w+);base64,/', $cropped_cover)) {
            $raw_cover = base64_decode(substr($cropped_cover, strpos($cropped_cover, ',') + 1));
            if ($raw_cover !== false) {
                $c_name = 'cover_' . time() . '_' . uniqid() . '.webp';
                if (file_put_contents($upload_dir . $c_name, $raw_cover)) {
                    if ($is_edit && !empty($berita['gambar']) && file_exists($upload_dir . $berita['gambar'])) {
                        @unlink($upload_dir . $berita['gambar']);
                    }
                    $gambar_filename = $c_name;
                } else {
                    $error = 'Gagal menyimpan gambar cover hasil penyesuaian ke server.';
                }
            }
        } elseif (!empty($_FILES['gambar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format file gambar utama tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
                $error = 'Ukuran file gambar utama maksimal 5MB.';
            } else {
                $saved_name = convertAndSaveWebP($_FILES['gambar']['tmp_name'], $upload_dir, 'cover_');
                if ($saved_name) {
                    if ($is_edit && !empty($berita['gambar']) && file_exists($upload_dir . $berita['gambar'])) {
                        @unlink($upload_dir . $berita['gambar']);
                    }
                    $gambar_filename = $saved_name;
                } else {
                    $error = 'Gagal mengonversi gambar utama ke WebP.';
                }
            }
        }

        // 2. Handle Multi-Upload Slider Images (Support Cropped 1-per-1 & Direct Upload)
        $new_slider_files = [];

        // 2a. Foto slider hasil crop / edit (Base64)
        if (!empty($_POST['cropped_slider_photos']) && is_array($_POST['cropped_slider_photos'])) {
            foreach ($_POST['cropped_slider_photos'] as $c_slide) {
                if (!empty($c_slide) && preg_match('/^data:image\/(\w+);base64,/', $c_slide)) {
                    $raw_slide = base64_decode(substr($c_slide, strpos($c_slide, ',') + 1));
                    if ($raw_slide !== false) {
                        $s_name = 'slide_' . time() . '_' . uniqid() . '.webp';
                        if (file_put_contents($upload_dir . $s_name, $raw_slide)) {
                            $new_slider_files[] = $s_name;
                        }
                    }
                }
            }
        }

        // 2b. Foto slider asli yang tidak dicrop
        if (!$error && !empty($_FILES['gambar_slider']['name'][0])) {
            $total_files = count($_FILES['gambar_slider']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                if ($_FILES['gambar_slider']['error'][$i] === UPLOAD_ERR_OK) {
                    $orig_name = $_FILES['gambar_slider']['name'][$i];
                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    $size = $_FILES['gambar_slider']['size'][$i];
                    
                    if (!in_array($ext, $allowed)) {
                        $error = "File '{$orig_name}' memiliki format yang tidak didukung.";
                        break;
                    } elseif ($size > 5 * 1024 * 1024) {
                        $error = "File '{$orig_name}' melebihi batas ukuran 5MB.";
                        break;
                    } else {
                        $saved_slide = convertAndSaveWebP($_FILES['gambar_slider']['tmp_name'][$i], $upload_dir, 'slide_');
                        if ($saved_slide) {
                            $new_slider_files[] = $saved_slide;
                        }
                    }
                }
            }
        }

        // 2c. Update foto slider lama yang diedit via modal cropper
        if (!empty($_POST['cropped_existing_slide']) && is_array($_POST['cropped_existing_slide'])) {
            foreach ($_POST['cropped_existing_slide'] as $slide_id => $b64) {
                $slide_id = (int)$slide_id;
                if ($slide_id > 0 && !empty($b64) && preg_match('/^data:image\/(\w+);base64,/', $b64)) {
                    $raw = base64_decode(substr($b64, strpos($b64, ',') + 1));
                    if ($raw !== false) {
                        $new_fname = 'slide_' . time() . '_' . uniqid() . '.webp';
                        if (file_put_contents($upload_dir . $new_fname, $raw)) {
                            $old_img = $db->query("SELECT gambar FROM berita_gambar WHERE id = {$slide_id}")->fetchColumn();
                            if ($old_img && file_exists($upload_dir . $old_img)) @unlink($upload_dir . $old_img);
                            $db->prepare("UPDATE berita_gambar SET gambar = ? WHERE id = ?")->execute([$new_fname, $slide_id]);
                        }
                    }
                }
            }
        }

        // Jika gambar utama belum ada namun ada gambar slider yang diunggah, jadikan gambar slider pertama sebagai gambar utama
        if (!$error && empty($gambar_filename) && !empty($new_slider_files)) {
            $gambar_filename = array_shift($new_slider_files);
        }

        if (!$error) {
            if ($is_edit && $id) {
                $status = $berita['status'] ?? 'draft';
                $stmt = $db->prepare("UPDATE berita SET judul=?, tipe=?, slug=?, konten=?, gambar=?, tanggal_publikasi=?, tampil_di_ami=?, status=? WHERE id=?");
                $stmt->execute([$judul, $tipe, $slug, $konten, $gambar_filename, $tanggal ?: null, $tampil_di_ami, $status, $id]);
                $berita_id = $id;
                $_SESSION['flash'] = 'Perubahan berita/kegiatan berhasil disimpan.';
            } else {
                $status = 'draft';
                $stmt = $db->prepare("INSERT INTO berita (judul, tipe, slug, konten, gambar, tanggal_publikasi, tampil_di_ami, status) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$judul, $tipe, $slug, $konten, $gambar_filename, $tanggal ?: null, $tampil_di_ami, $status]);
                $berita_id = (int)$db->lastInsertId();
                $_SESSION['flash'] = 'Berita baru berhasil disimpan sebagai draft. Silakan klik "Terbitkan" untuk meninjau dan menerbitkannya.';
            }

            // Simpan gambar tambahan ke berita_gambar
            if (!empty($new_slider_files) && $berita_id) {
                $max_order = (int)$db->query("SELECT MAX(urutan) FROM berita_gambar WHERE berita_id = $berita_id")->fetchColumn();
                $stmt_img_ins = $db->prepare("INSERT INTO berita_gambar (berita_id, gambar, urutan) VALUES (?, ?, ?)");
                foreach ($new_slider_files as $slider_img) {
                    $max_order++;
                    $stmt_img_ins->execute([$berita_id, $slider_img, $max_order]);
                }
            }

            redirect(SITE_URL . '/admin/berita-list.php');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="berita-list.php" style="color:var(--text-muted);">Berita &amp; Kegiatan</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <?= e($flash) ?>
        </div>
        <?php endif; ?>

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
                <a href="berita-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data" id="form-berita">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $berita['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <!-- Judul -->
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="judul">
                                Judul Berita / Kegiatan <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" id="judul" name="judul" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;"
                                placeholder="Judul berita atau kegiatan..."
                                value="<?= e($is_edit ? $berita['judul'] : ($_POST['judul'] ?? '')) ?>" required>
                        </div>

                        <!-- Tipe / Kategori Publikasi -->
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="tipe">
                                    Tipe / Kategori Publikasi
                                </label>
                                <a href="kategori-berita.php" target="_blank" class="text-primary text-decoration-none" style="font-size:0.75rem;font-weight:600;">
                                    + Kelola Kategori
                                </a>
                            </div>
                            <select id="tipe" name="tipe" class="form-select" style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;">
                                <?php
                                $tipes_db = [];
                                try {
                                    $tipes_db = $db->query("SELECT nama_kategori FROM kategori_berita ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
                                } catch (Exception $e) {}
                                if (empty($tipes_db)) {
                                    $tipes_db = ['Berita', 'Kegiatan LPM', 'Artikel Mutu', 'Sosialisasi', 'Penghargaan'];
                                }
                                $cur_tipe = $is_edit ? ($berita['tipe'] ?? 'Berita') : ($_POST['tipe'] ?? 'Berita');
                                if (!in_array($cur_tipe, $tipes_db) && !empty($cur_tipe)) {
                                    $tipes_db[] = $cur_tipe;
                                }
                                foreach ($tipes_db as $t):
                                ?>
                                <option value="<?= e($t) ?>" <?= $cur_tipe === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tanggal Publikasi & Preview Tahun Akademik -->
                        <?php 
                        $val_tanggal = $is_edit ? ($berita['tanggal_publikasi'] ?? date('Y-m-d')) : ($_POST['tanggal_publikasi'] ?? date('Y-m-d'));
                        $current_ta  = getTahunAkademik($val_tanggal);
                        ?>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="tanggal">
                                Tanggal Publikasi / Pelaksanaan
                            </label>
                            <input type="date" id="tanggal" name="tanggal_publikasi" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;"
                                value="<?= e($val_tanggal) ?>"
                                onchange="updateTAPreview(this.value)">
                            <div class="mt-1 d-flex align-items-center gap-2" style="font-size:0.75rem;color:var(--text-muted);">
                                <span>Tahun Akademik:</span>
                                <span id="ta-preview" class="badge" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.75rem;padding:0.25rem 0.5rem;border-radius:4px;">
                                    <?= e($current_ta ?: '-') ?>
                                </span>
                                <span style="font-size:0.7rem;color:#888;">(1 Sept – 31 Agt)</span>
                            </div>
                        </div>

                        <!-- Gambar Utama -->
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="gambar">
                                Gambar Utama / Sampul (Cover)
                            </label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" id="gambar" name="gambar" class="form-control"
                                    style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.6rem 1rem;"
                                    accept="image/*">
                                <button type="button" class="btn btn-dark btn-sm d-none align-items-center gap-1" id="btnEditCover" style="white-space:nowrap;padding:0.6rem 0.9rem;border-radius:8px;">
                                    <i class="bi bi-crop text-warning"></i> Edit / Crop
                                </button>
                            </div>
                            <input type="hidden" name="cropped_cover_data" id="croppedCoverData">
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.25rem;">
                                Digunakan sebagai thumbnail di card dan slide cover. (Maks 5MB)
                            </div>
                            <!-- Live Preview Gambar Utama yang Baru Dipilih -->
                            <div id="coverLivePreview" style="display:none;margin-top:0.5rem;">
                                <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border">
                                    <img id="coverPreviewImg" src="" alt="Preview Cover" style="height:65px;border-radius:6px;border:1.5px solid var(--purple);object-fit:cover;">
                                    <div>
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">Preview Cover Baru</span>
                                        <div id="coverCroppedBadge" class="d-none small text-success fw-bold">
                                            <i class="bi bi-check-circle-fill me-1"></i>Telah Disesuaikan / Diedit
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Toggle Tampilkan di Halaman AMI (Audit Mutu Internal) -->
                        <div class="col-12">
                            <div class="card p-3" style="background:linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);border:1.5px solid #F59E0B;border-radius:12px;box-shadow:0 2px 6px rgba(245,158,11,0.08);">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width:42px;height:42px;border-radius:10px;background:#FDE68A;display:flex;align-items:center;justify-content:center;color:#D97706;flex-shrink:0;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="22" height="22">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div style="font-family:var(--font-heading);font-weight:700;font-size:0.92rem;color:#92400E;">
                                                Tampilkan Kegiatan di Halaman AMI (Audit Mutu Internal)
                                            </div>
                                            <div style="font-size:0.78rem;color:#78350F;line-height:1.4;">
                                                Aktifkan tombol switch ini bila kegiatan/berita ini ingin dimunculkan pada slider dokumentasi di bawah kalender AMI.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0 d-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" role="switch" id="tampil_di_ami" name="tampil_di_ami" value="1"
                                            <?= (!empty($berita['tampil_di_ami']) || (isset($_POST['tampil_di_ami']) && $_POST['tampil_di_ami'] == '1')) ? 'checked' : '' ?>
                                            style="width:3.2rem;height:1.7rem;cursor:pointer;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload Multi-Gambar untuk Slider Kegiatan (Bisa Satu per Satu & Bertahap & Diedit 1 per 1) -->
                        <div class="col-12" style="background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:12px;padding:1.4rem;margin-top:0.5rem;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <label class="form-label d-flex align-items-center gap-2 mb-0" style="font-family:var(--font-heading);font-size:0.88rem;font-weight:700;color:var(--navy);">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--purple)" width="20" height="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                        Foto Tambahan untuk Slider Kegiatan
                                    </label>
                                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                                        Pilih foto satu per satu atau sekaligus. Tiap foto memiliki tombol <strong>Edit / Crop</strong> untuk menyesuaikan framing satu per satu.
                                    </div>
                                </div>
                                
                                <!-- Tombol Tambah Foto -->
                                <button type="button" class="btn btn-sm d-flex align-items-center gap-1" id="btnPickSliderImages" 
                                        style="background:linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);color:#fff;font-weight:600;font-size:0.82rem;padding:0.45rem 1.1rem;border-radius:8px;box-shadow:0 3px 8px rgba(124,58,237,0.25);transition:all 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="16" height="16">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Tambah Foto Slide
                                </button>
                            </div>

                            <!-- Input picker hidden (untuk trigger dialog file) -->
                            <input type="file" id="sliderPickerInput" accept="image/*" multiple style="display:none;">
                            <!-- Input asli yang akan dikirim ke server untuk uncropped files -->
                            <input type="file" id="realSliderInput" name="gambar_slider[]" multiple style="display:none;">
                            <!-- Hidden inputs container for cropped photos -->
                            <div id="croppedSliderInputsContainer"></div>

                            <!-- Container Preview Foto yang Baru Dipilih (Staged) -->
                            <div id="stagedPhotosWrapper" style="display:none;margin-top:1rem;padding-top:1rem;border-top:1px solid #E2E8F0;">
                                <div style="font-size:0.75rem;font-weight:700;color:var(--purple);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;">
                                    <span>Foto Baru yang Akan Ditambahkan (<span id="stagedCount">0</span> foto):</span>
                                    <button type="button" class="btn btn-link text-danger p-0 text-decoration-none" style="font-size:0.72rem;" onclick="clearAllStagedPhotos()">
                                        Batal Semua
                                    </button>
                                </div>
                                <div id="stagedPhotosGrid" class="d-flex flex-wrap gap-3 align-items-start">
                                    <!-- Dynamic staged cards injected here -->
                                </div>
                            </div>
                            
                            <!-- Prompt jika belum ada foto baru yang dipilih -->
                            <div id="stagedEmptyPrompt" class="text-center py-3" style="color:#94A3B8;font-size:0.8rem;border:1px dashed #E2E8F0;border-radius:8px;margin-top:0.75rem;background:#fff;">
                                <i class="bi bi-images" style="font-size:1.4rem;display:block;margin-bottom:4px;color:#A78BFA;"></i>
                                Belum ada foto baru yang dipilih. Klik tombol <strong>"+ Tambah Foto Slide"</strong> di atas untuk memasukkan foto satu per satu atau sekaligus.
                            </div>
                        </div>

                        <!-- Galeri Foto Saat Ini (Jika Mode Edit) -->
                        <?php if ($is_edit && (!empty($berita['gambar']) || !empty($gambar_tambahan))): ?>
                        <div class="col-12" style="margin-top:0.75rem;">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:700;color:var(--navy);margin-bottom:0.75rem;">
                                Galeri Foto yang Sudah Tersimpan di Server:
                            </label>
                            <div class="d-flex flex-wrap gap-3 align-items-start">
                                <!-- Foto Utama -->
                                <?php if (!empty($berita['gambar'])): ?>
                                <div style="position:relative;width:140px;background:#fff;border:2px solid #8B5CF6;border-radius:8px;padding:6px;box-shadow:0 2px 4px rgba(0,0,0,0.06);">
                                    <span class="badge" style="position:absolute;top:-8px;left:8px;background:#8B5CF6;color:#fff;font-size:0.65rem;font-weight:700;padding:2px 6px;border-radius:4px;">Cover Utama</span>
                                    <img id="existingCoverImg" src="<?= SITE_URL ?>/uploads/berita/<?= e($berita['gambar']) ?>" alt="" style="width:100%;height:85px;object-fit:cover;border-radius:6px;display:block;">
                                    <div class="mt-2 d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary w-50 py-1" style="font-size:0.7rem;" onclick="editExistingCover('<?= SITE_URL ?>/uploads/berita/<?= e($berita['gambar']) ?>')">
                                            Edit
                                        </button>
                                        <a href="berita-form.php?id=<?= $berita['id'] ?>&delete_cover=1" class="btn btn-sm btn-outline-danger w-50 py-1" style="font-size:0.7rem;" onclick="return confirm('Hapus foto utama ini?')">
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Foto Tambahan Slider -->
                                <?php foreach ($gambar_tambahan as $idx => $gt): ?>
                                <div style="position:relative;width:140px;background:#fff;border:1.5px solid var(--border);border-radius:8px;padding:6px;box-shadow:0 2px 4px rgba(0,0,0,0.04);">
                                    <span class="badge bg-light text-dark" style="position:absolute;top:-8px;left:8px;border:1px solid #CBD5E1;font-size:0.65rem;font-weight:600;padding:2px 6px;border-radius:4px;">Slide #<?= $idx + 2 ?></span>
                                    <img id="existingSlideImg_<?= $gt['id'] ?>" src="<?= SITE_URL ?>/uploads/berita/<?= e($gt['gambar']) ?>" alt="" style="width:100%;height:85px;object-fit:cover;border-radius:6px;display:block;">
                                    <div class="mt-2 d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary w-50 py-1" style="font-size:0.7rem;" onclick="editExistingSlide(<?= $gt['id'] ?>, '<?= SITE_URL ?>/uploads/berita/<?= e($gt['gambar']) ?>')">
                                            Edit
                                        </button>
                                        <a href="berita-form.php?id=<?= $berita['id'] ?>&delete_img=<?= $gt['id'] ?>" class="btn btn-sm btn-outline-danger w-50 py-1" style="font-size:0.7rem;" onclick="return confirm('Hapus foto slide ini?')">
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Konten Berita -->
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="konten">
                                Konten / Isi Berita &amp; Kegiatan <span style="color:#C62828;">*</span>
                            </label>
                            <textarea id="konten" name="konten" class="form-control" rows="12"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.9rem;line-height:1.7;"
                                placeholder="Tulis rincian berita atau kegiatan di sini..."
                                required><?= e($is_edit ? $berita['konten'] : ($_POST['konten'] ?? '')) ?></textarea>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">
                                Tips: Gunakan paragraf yang jelas untuk keterbacaan optimal.
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <!-- Tombol Aksi: Hanya Batal dan Selesai -->
                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:0.75rem;">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <a href="berita-list.php" class="btn btn-outline-secondary px-4 py-2 fw-semibold" style="border-radius:8px;font-size:0.88rem;">
                                    Batal
                                </a>
                                <button type="submit" name="submit_selesai" value="1" class="btn btn-primary px-4 py-2 fw-bold shadow-sm" style="border-radius:8px;background:var(--navy);border:none;font-size:0.88rem;">
                                    <i class="bi bi-check2-circle me-1"></i> Selesai
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cropper Universal Berita (Cover & Multi-Slider) -->
<div class="modal fade" id="modalCropBerita" tabindex="-1" aria-labelledby="modalCropBeritaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropBeritaLabel">Editor Gambar: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small id="modalCropBeritaSubtitle" style="color:rgba(255,255,255,0.85);">Sesuaikan framing foto dokumentasi berita atau kegiatan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgBerita" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHBerita">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVBerita">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftBerita">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightBerita">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInBerita">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutBerita">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropBerita">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-berita fw-semibold" data-ratio="1.777778">16:9 Card</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-berita fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-berita fw-semibold" data-ratio="1">1:1 Persegi</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-berita fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropBerita" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function updateTAPreview(dateStr) {
    if (!dateStr) return;
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return;
    
    const year = d.getFullYear();
    const month = d.getMonth() + 1;
    let ta = '';
    if (month >= 9) {
        ta = year + '/' + (year + 1);
    } else {
        ta = (year - 1) + '/' + year;
    }
    const badge = document.getElementById('ta-preview');
    if (badge) {
        badge.innerText = ta;
    }
}

// -------------------------------------------------------------
// SISTEM EDITOR CROPPER UNIVERSAL BERITA (COVER & MULTI-SLIDER)
// -------------------------------------------------------------
let cropperInstance = null;
let scaleX = 1;
let scaleY = 1;
let currentRatio = 16 / 9;
let cropMode = null; // 'cover', 'existing_cover', 'slider_item', 'existing_slide'
let activeSliderItemId = null;
let activeExistingSlideId = null;

const cropModalEl          = document.getElementById('modalCropBerita');
const bsCropModal          = new bootstrap.Modal(cropModalEl);
const cropperSourceImg     = document.getElementById('cropperSourceImgBerita');
const modalSubtitle        = document.getElementById('modalCropBeritaSubtitle');
const btnApplyCrop         = document.getElementById('btnApplyCropBerita');

function initCropper() {
    if (cropperInstance) cropperInstance.destroy();
    scaleX = 1;
    scaleY = 1;
    cropperInstance = new Cropper(cropperSourceImg, {
        aspectRatio: currentRatio,
        viewMode: 1,
        autoCropArea: 0.95,
        responsive: true,
        guides: true,
        checkCrossOrigin: false
    });
}

cropModalEl.addEventListener('shown.bs.modal', function() { initCropper(); });
cropModalEl.addEventListener('hidden.bs.modal', function() {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
});

// Kontrol Toolbar Modal
document.getElementById('btnRotateLeftBerita')?.addEventListener('click', function() { if (cropperInstance) cropperInstance.rotate(-90); });
document.getElementById('btnRotateRightBerita')?.addEventListener('click', function() { if (cropperInstance) cropperInstance.rotate(90); });
document.getElementById('btnFlipHBerita')?.addEventListener('click', function() { if (cropperInstance) { scaleX = -scaleX; cropperInstance.scaleX(scaleX); } });
document.getElementById('btnFlipVBerita')?.addEventListener('click', function() { if (cropperInstance) { scaleY = -scaleY; cropperInstance.scaleY(scaleY); } });
document.getElementById('btnZoomInBerita')?.addEventListener('click', function() { if (cropperInstance) cropperInstance.zoom(0.1); });
document.getElementById('btnZoomOutBerita')?.addEventListener('click', function() { if (cropperInstance) cropperInstance.zoom(-0.1); });
document.getElementById('btnResetCropBerita')?.addEventListener('click', function() { if (cropperInstance) { cropperInstance.reset(); scaleX = 1; scaleY = 1; } });

document.querySelectorAll('.ratio-btn-berita').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.ratio-btn-berita').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        const val = parseFloat(this.getAttribute('data-ratio'));
        currentRatio = isNaN(val) ? NaN : val;
        if (cropperInstance) cropperInstance.setAspectRatio(currentRatio);
    });
});

// 1. Logika Cover / Gambar Utama
const coverInput         = document.getElementById('gambar');
const btnEditCover       = document.getElementById('btnEditCover');
const coverLivePreview   = document.getElementById('coverLivePreview');
const coverPreviewImg    = document.getElementById('coverPreviewImg');
const coverCroppedBadge  = document.getElementById('coverCroppedBadge');
const hiddenCoverData    = document.getElementById('croppedCoverData');

if (coverInput) {
    coverInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    coverPreviewImg.src = e.target.result;
                    coverLivePreview.style.display = 'block';
                    btnEditCover.classList.remove('d-none');
                    btnEditCover.classList.add('d-inline-flex');
                    hiddenCoverData.value = '';
                    if (coverCroppedBadge) coverCroppedBadge.classList.add('d-none');

                    // Langsung buka modal cropper untuk kenyamanan admin
                    cropMode = 'cover';
                    modalSubtitle.textContent = 'Sesuaikan framing gambar sampul (cover) berita/kegiatan.';
                    cropperSourceImg.src = e.target.result;
                    bsCropModal.show();
                };
                reader.readAsDataURL(file);
            }
        }
    });

    btnEditCover?.addEventListener('click', function() {
        if (coverPreviewImg.src) {
            cropMode = 'cover';
            modalSubtitle.textContent = 'Sesuaikan framing gambar sampul (cover) berita/kegiatan.';
            cropperSourceImg.src = coverPreviewImg.src;
            bsCropModal.show();
        }
    });
}

window.editExistingCover = function(imgUrl) {
    cropMode = 'existing_cover';
    modalSubtitle.textContent = 'Sesuaikan framing foto cover utama tersimpan.';
    cropperSourceImg.src = imgUrl;
    bsCropModal.show();
};

window.editExistingSlide = function(slideId, imgUrl) {
    cropMode = 'existing_slide';
    activeExistingSlideId = slideId;
    modalSubtitle.textContent = 'Sesuaikan framing foto slide tersimpan #' + slideId + '.';
    cropperSourceImg.src = imgUrl;
    bsCropModal.show();
};

// 2. Logika Multi-Foto Slider (Diedit Satu per Satu)
let sliderItems = []; // Array of { id, file, croppedData, previewUrl, name, size }

const pickerInput       = document.getElementById('sliderPickerInput');
const realInput         = document.getElementById('realSliderInput');
const btnPick           = document.getElementById('btnPickSliderImages');
const stagedGrid        = document.getElementById('stagedPhotosGrid');
const stagedWrap        = document.getElementById('stagedPhotosWrapper');
const stagedEmpty       = document.getElementById('stagedEmptyPrompt');
const stagedCount       = document.getElementById('stagedCount');
const croppedInputsWrap = document.getElementById('croppedSliderInputsContainer');

if (btnPick && pickerInput) {
    btnPick.addEventListener('click', function() {
        pickerInput.value = '';
        pickerInput.click();
    });

    pickerInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (file.type.startsWith('image/')) {
                    const itemId = 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                    sliderItems.push({
                        id: itemId,
                        file: file,
                        croppedData: null,
                        previewUrl: URL.createObjectURL(file),
                        name: file.name,
                        size: file.size
                    });
                }
            }
            renderStagedPhotos();
        }
    });
}

function renderStagedPhotos() {
    const count = sliderItems.length;
    if (stagedCount) stagedCount.textContent = count;

    if (count > 0) {
        stagedWrap.style.display = 'block';
        stagedEmpty.style.display = 'none';
    } else {
        stagedWrap.style.display = 'none';
        stagedEmpty.style.display = 'block';
    }

    stagedGrid.innerHTML = '';
    croppedInputsWrap.innerHTML = '';

    const dt = new DataTransfer();

    sliderItems.forEach((item, index) => {
        // Jika belum dicrop, file dikirim lewat realInput
        if (!item.croppedData && item.file) {
            dt.items.add(item.file);
        } else if (item.croppedData) {
            // Jika sudah dicrop, kirim lewat hidden input array cropped_slider_photos[]
            const hInput = document.createElement('input');
            hInput.type = 'hidden';
            hInput.name = 'cropped_slider_photos[]';
            hInput.value = item.croppedData;
            croppedInputsWrap.appendChild(hInput);
        }

        const card = document.createElement('div');
        card.className = 'card p-2 border position-relative shadow-sm';
        card.style.cssText = 'width:145px;background:#fff;border-radius:10px;transition:all 0.2s;';

        const sizeKb = Math.round(item.size / 1024);
        const fileName = item.name.length > 13 ? item.name.substring(0, 10) + '...' : item.name;

        card.innerHTML = `
            <div style="width:100%;height:95px;border-radius:6px;overflow:hidden;background:#F1F5F9;position:relative;">
                <img src="${item.previewUrl}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;display:block;">
                ${item.croppedData ? '<span class="badge bg-success position-absolute top-0 start-0 m-1" style="font-size:0.65rem;box-shadow:0 1px 3px rgba(0,0,0,0.2);"><i class="bi bi-check2"></i> Diedit</span>' : ''}
            </div>
            <div style="font-size:0.72rem;font-weight:600;color:var(--navy);margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${item.name}">
                ${fileName}
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;">
                <span style="font-size:0.65rem;color:#64748B;">${sizeKb} KB</span>
                <span class="badge bg-light text-dark border" style="font-size:0.65rem;">#${index + 1}</span>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top gap-1">
                <button type="button" class="btn btn-xs btn-outline-primary flex-fill py-1 fw-bold d-flex align-items-center justify-content-center gap-1" style="font-size:0.72rem;border-radius:6px;" onclick="openCropSliderItem('${item.id}')">
                    <i class="bi bi-crop text-warning"></i> Edit
                </button>
                <button type="button" class="btn btn-xs btn-outline-danger p-0 d-flex align-items-center justify-content-center" style="width:26px;height:26px;border-radius:6px;font-size:0.85rem;" title="Hapus foto ini" onclick="removeSliderItem('${item.id}')">
                    &times;
                </button>
            </div>
        `;
        stagedGrid.appendChild(card);
    });

    if (realInput) {
        realInput.files = dt.files;
    }
}

window.openCropSliderItem = function(itemId) {
    const item = sliderItems.find(i => i.id === itemId);
    if (!item) return;

    cropMode = 'slider_item';
    activeSliderItemId = itemId;
    modalSubtitle.textContent = 'Sesuaikan framing foto slide: "' + item.name + '".';
    cropperSourceImg.src = item.previewUrl;
    bsCropModal.show();
};

window.removeSliderItem = function(itemId) {
    sliderItems = sliderItems.filter(i => i.id !== itemId);
    renderStagedPhotos();
};

window.clearAllStagedPhotos = function() {
    sliderItems = [];
    renderStagedPhotos();
};

// 3. Tombol Terapkan Penyesuaian Hasil Cropper
btnApplyCrop?.addEventListener('click', function() {
    if (!cropperInstance) return;

    const canvas = cropperInstance.getCroppedCanvas({
        maxWidth: 1920,
        maxHeight: 1200,
        imageSmoothingQuality: 'high'
    });

    if (canvas) {
        const dataUrl = canvas.toDataURL('image/webp', 0.88);

        if (cropMode === 'cover') {
            hiddenCoverData.value = dataUrl;
            coverPreviewImg.src = dataUrl;
            coverLivePreview.style.display = 'block';
            if (coverCroppedBadge) coverCroppedBadge.classList.remove('d-none');
        } else if (cropMode === 'existing_cover') {
            hiddenCoverData.value = dataUrl;
            const existingCover = document.getElementById('existingCoverImg');
            if (existingCover) existingCover.src = dataUrl;
        } else if (cropMode === 'slider_item') {
            const item = sliderItems.find(i => i.id === activeSliderItemId);
            if (item) {
                item.croppedData = dataUrl;
                item.previewUrl = dataUrl;
                item.file = null; // Agar tidak dikirim ganda sebagai raw file
                renderStagedPhotos();
            }
        } else if (cropMode === 'existing_slide') {
            const existingSlide = document.getElementById('existingSlideImg_' + activeExistingSlideId);
            if (existingSlide) existingSlide.src = dataUrl;
            
            // Tambahkan hidden input untuk menyimpan perubahan slide tersimpan
            let existInput = document.getElementById('input_existing_slide_' + activeExistingSlideId);
            if (!existInput) {
                existInput = document.createElement('input');
                existInput.type = 'hidden';
                existInput.id = 'input_existing_slide_' + activeExistingSlideId;
                existInput.name = 'cropped_existing_slide[' + activeExistingSlideId + ']';
                document.querySelector('form').appendChild(existInput);
            }
            existInput.value = dataUrl;
        }

        bsCropModal.hide();
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
