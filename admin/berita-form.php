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

        // 1. Handle Gambar Utama
        $gambar_filename = $is_edit ? ($berita['gambar'] ?? null) : null;
        if (!empty($_FILES['gambar']['name'])) {
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

        // 2. Handle Multi-Upload Slider Images
        $new_slider_files = [];
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
                            <input type="file" id="gambar" name="gambar" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.6rem 1rem;"
                                accept="image/*">
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.25rem;">
                                Digunakan sebagai thumbnail di card dan slide cover. (Maks 5MB)
                            </div>
                            <!-- Live Preview Gambar Utama yang Baru Dipilih -->
                            <div id="coverLivePreview" style="display:none;margin-top:0.5rem;">
                                <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">Preview Cover Baru:</span>
                                <div>
                                    <img id="coverPreviewImg" src="" alt="" style="height:60px;border-radius:6px;border:1.5px solid var(--purple);object-fit:cover;">
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

                        <!-- Upload Multi-Gambar untuk Slider Kegiatan (Bisa Satu per Satu & Bertahap) -->
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
                                        Bisa memilih <strong>satu per satu secara bertahap</strong> (pilih foto A, lalu klik tambah lagi untuk foto B, dst).
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
                            <!-- Input asli yang akan dikirim ke server -->
                            <input type="file" id="realSliderInput" name="gambar_slider[]" multiple style="display:none;">

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
                                <div style="position:relative;width:130px;background:#fff;border:2px solid #8B5CF6;border-radius:8px;padding:6px;box-shadow:0 2px 4px rgba(0,0,0,0.06);">
                                    <span class="badge" style="position:absolute;top:-8px;left:8px;background:#8B5CF6;color:#fff;font-size:0.65rem;font-weight:700;padding:2px 6px;border-radius:4px;">Cover Utama</span>
                                    <img src="<?= SITE_URL ?>/uploads/berita/<?= e($berita['gambar']) ?>" alt="" style="width:100%;height:85px;object-fit:cover;border-radius:6px;display:block;">
                                    <div class="mt-2 text-center">
                                        <a href="berita-form.php?id=<?= $berita['id'] ?>&delete_cover=1" class="btn btn-sm btn-outline-danger w-100" style="font-size:0.7rem;padding:2px 4px;" onclick="return confirm('Hapus foto utama ini?')">
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Foto Tambahan Slider -->
                                <?php foreach ($gambar_tambahan as $idx => $gt): ?>
                                <div style="position:relative;width:130px;background:#fff;border:1.5px solid var(--border);border-radius:8px;padding:6px;box-shadow:0 2px 4px rgba(0,0,0,0.04);">
                                    <span class="badge bg-light text-dark" style="position:absolute;top:-8px;left:8px;border:1px solid #CBD5E1;font-size:0.65rem;font-weight:600;padding:2px 6px;border-radius:4px;">Slide #<?= $idx + 2 ?></span>
                                    <img src="<?= SITE_URL ?>/uploads/berita/<?= e($gt['gambar']) ?>" alt="" style="width:100%;height:85px;object-fit:cover;border-radius:6px;display:block;">
                                    <div class="mt-2 text-center">
                                        <a href="berita-form.php?id=<?= $berita['id'] ?>&delete_img=<?= $gt['id'] ?>" class="btn btn-sm btn-outline-danger w-100" style="font-size:0.7rem;padding:2px 4px;" onclick="return confirm('Hapus foto slide ini?')">
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

<script>
function updateTAPreview(dateStr) {
    if (!dateStr) return;
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return;
    
    const year = d.getFullYear();
    const month = d.getMonth() + 1; // 1-12
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

// Live Preview Cover Image
const coverInput = document.getElementById('gambar');
const coverLivePreview = document.getElementById('coverLivePreview');
const coverPreviewImg = document.getElementById('coverPreviewImg');
if (coverInput) {
    coverInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            coverPreviewImg.src = URL.createObjectURL(this.files[0]);
            coverLivePreview.style.display = 'block';
        } else {
            coverLivePreview.style.display = 'none';
        }
    });
}

// Staging Multi-Foto Slider (Bisa Satu per Satu & Bertahap)
const stagedFiles = new DataTransfer();
const pickerInput = document.getElementById('sliderPickerInput');
const realInput   = document.getElementById('realSliderInput');
const btnPick     = document.getElementById('btnPickSliderImages');
const stagedGrid  = document.getElementById('stagedPhotosGrid');
const stagedWrap  = document.getElementById('stagedPhotosWrapper');
const stagedEmpty = document.getElementById('stagedEmptyPrompt');
const stagedCount = document.getElementById('stagedCount');

if (btnPick && pickerInput) {
    btnPick.addEventListener('click', function() {
        pickerInput.value = ''; // reset agar file yang sama bisa dipilih ulang jika diinginkan
        pickerInput.click();
    });

    pickerInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (file.type.startsWith('image/')) {
                    stagedFiles.items.add(file);
                }
            }
            renderStagedPhotos();
        }
    });
}

function renderStagedPhotos() {
    if (!realInput) return;
    realInput.files = stagedFiles.files;
    const count = stagedFiles.files.length;
    
    if (stagedCount) stagedCount.textContent = count;
    
    if (count > 0) {
        stagedWrap.style.display = 'block';
        stagedEmpty.style.display = 'none';
    } else {
        stagedWrap.style.display = 'none';
        stagedEmpty.style.display = 'block';
    }
    
    stagedGrid.innerHTML = '';
    
    for (let i = 0; i < stagedFiles.files.length; i++) {
        const file = stagedFiles.files[i];
        const card = document.createElement('div');
        card.style.cssText = 'position:relative;width:125px;background:#fff;border:1.5px solid #CBD5E1;border-radius:8px;padding:6px;box-shadow:0 2px 5px rgba(0,0,0,0.06);transition:all 0.2s;';
        
        const imgUrl = URL.createObjectURL(file);
        const sizeKb = Math.round(file.size / 1024);
        const fileName = file.name.length > 14 ? file.name.substring(0, 11) + '...' : file.name;
        
        card.innerHTML = `
            <div style="width:100%;height:80px;border-radius:5px;overflow:hidden;background:#F1F5F9;">
                <img src="${imgUrl}" alt="${file.name}" style="width:100%;height:100%;object-fit:cover;display:block;">
            </div>
            <div style="font-size:0.72rem;font-weight:600;color:var(--navy);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${file.name}">
                ${fileName}
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:2px;">
                <span style="font-size:0.65rem;color:#64748B;">${sizeKb} KB</span>
                <button type="button" class="btn btn-sm btn-outline-danger p-0 d-flex align-items-center justify-content-center" 
                        style="width:22px;height:22px;border-radius:50%;font-size:0.75rem;line-height:1;" 
                        title="Batal pilih foto ini" 
                        onclick="removeStagedPhoto(${i})">
                    &times;
                </button>
            </div>
        `;
        stagedGrid.appendChild(card);
    }
}

window.removeStagedPhoto = function(index) {
    const newDt = new DataTransfer();
    for (let i = 0; i < stagedFiles.files.length; i++) {
        if (i !== index) {
            newDt.items.add(stagedFiles.files[i]);
        }
    }
    stagedFiles.items.clear();
    for (let i = 0; i < newDt.files.length; i++) {
        stagedFiles.items.add(newDt.files[i]);
    }
    renderStagedPhotos();
};

window.clearAllStagedPhotos = function() {
    stagedFiles.items.clear();
    renderStagedPhotos();
};
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
