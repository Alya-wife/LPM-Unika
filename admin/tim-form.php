<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$person  = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM tim_lpm WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $person = $stmt->fetch();
    if ($person) $is_edit = true;
}

$default_kategori = $_GET['kategori'] ?? ($is_edit ? ($person['kategori'] ?? 'lpm') : 'lpm');
if (!in_array($default_kategori, ['lpm', 'gpm'])) {
    $default_kategori = 'lpm';
}

$admin_page_title = $is_edit ? 'Edit Personel Tim / GPM' : 'Tambah Personel Tim / GPM';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori       = trim($_POST['kategori'] ?? 'lpm');
    if (!in_array($kategori, ['lpm', 'gpm'])) $kategori = 'lpm';

    $nama           = trim($_POST['nama'] ?? '');
    $jabatan        = trim($_POST['jabatan'] ?? '');
    $bidang         = trim($_POST['bidang'] ?? '');
    $deskripsi      = trim($_POST['deskripsi'] ?? '');
    $tanggung_jawab = trim($_POST['tanggung_jawab'] ?? '');
    $level          = trim($_POST['level'] ?? 'koordinator');
    $urutan         = (int)($_POST['urutan'] ?? 1);
    $id             = (int)($_POST['id'] ?? 0);
    $cropped_data   = trim($_POST['cropped_foto_data'] ?? '');
    $hapus_foto     = !empty($_POST['hapus_foto']);

    if (!$nama || !$jabatan) {
        $error = 'Nama dan jabatan wajib diisi.';
    } else {
        $foto = $is_edit ? ($person['foto'] ?? '') : '';
        $dir = __DIR__ . '/../uploads/tim/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // 1. Cek jika admin memilih untuk MENGHAPUS FOTO tersimpan
        if ($hapus_foto) {
            if ($is_edit && !empty($person['foto'])) {
                $old_path = $dir . $person['foto'];
                if (file_exists($old_path)) @unlink($old_path);
            }
            $foto = '';
        }
        // 2. Prioritaskan jika ada data foto baru hasil crop (Base64)
        elseif (!empty($cropped_data)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $cropped_data, $type)) {
                $raw_data = substr($cropped_data, strpos($cropped_data, ',') + 1);
                $decoded = base64_decode($raw_data);
                if ($decoded === false) {
                    $error = 'Gagal memproses data gambar hasil crop.';
                } else {
                    $ext = strtolower($type[1]);
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $ext = 'jpg';
                    }
                    $filename = 'tim_' . uniqid() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                    if (file_put_contents($dir . $filename, $decoded)) {
                        if ($is_edit && !empty($person['foto'])) {
                            $old_path = $dir . $person['foto'];
                            if (file_exists($old_path)) @unlink($old_path);
                        }
                        $foto = $filename;
                    } else {
                        $error = 'Gagal menyimpan file foto hasil crop ke server.';
                    }
                }
            }
        }
        // 2. Jika tidak dicrop, cek apakah ada file upload biasa
        elseif (!empty($_FILES['foto']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 5MB.';
                $saved_foto = convertAndSaveWebP($_FILES['foto']['tmp_name'], $dir, 'tim_');
                if ($saved_foto) {
                    if ($is_edit && !empty($person['foto'])) {
                        $old_path = $dir . $person['foto'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                    $foto = $saved_foto;
                } else {
                    $error = 'Gagal mengonversi foto tim ke format WebP.';
                }
            }
        }

        if (!$error) {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE tim_lpm SET kategori=?, nama=?, jabatan=?, bidang=?, deskripsi=?, tanggung_jawab=?, foto=?, level=?, urutan=? WHERE id=?");
                $stmt->execute([$kategori, $nama, $jabatan, $bidang, $deskripsi, $tanggung_jawab, $foto, $level, $urutan, $id]);
                $_SESSION['flash'] = 'Data personel berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO tim_lpm (kategori, nama, jabatan, bidang, deskripsi, tanggung_jawab, foto, level, urutan) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$kategori, $nama, $jabatan, $bidang, $deskripsi, $tanggung_jawab, $foto, $level, $urutan]);
                $_SESSION['flash'] = 'Personel baru berhasil ditambahkan.';
            }
            redirect(SITE_URL . '/admin/tim-list.php?kat=' . $kategori);
        }
    }
}

// Extra CSS untuk Cropper.js
$extra_css = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<style>
.crop-container {
    max-height: 480px;
    background: #0F172A;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 10px;
}
.crop-container img {
    max-width: 100%;
    max-height: 460px;
    display: block;
}
.cropper-view-box,
.cropper-face {
    border-radius: 0;
}
.guide-card-preview {
    width: 100%;
    max-width: 320px;
    border-radius: 12px;
    overflow: hidden;
    border: 1.5px solid #E2E8F0;
    box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    background: #ffffff;
}
.guide-card-preview .preview-photo-wrap {
    width: 100%;
    height: 220px;
    background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 50%, #4A148C 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.guide-card-preview .preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
}
</style>
';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="tim-list.php?kat=<?= e($default_kategori) ?>" style="color:var(--text-muted);">Struktur Tim &amp; GPM</a>
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
                <a href="tim-list.php?kat=<?= e($default_kategori) ?>" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" id="formPersonel" enctype="multipart/form-data">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $person['id'] ?>">
                    <?php endif; ?>

                    <!-- Input tersembunyi untuk menyimpan gambar Base64 yang sudah di-crop -->
                    <input type="hidden" name="cropped_foto_data" id="croppedFotoData" value="">

                    <div class="row g-3">
                        
                        <!-- Kategori Personel Selection -->
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:700;color:var(--navy);">
                                Kategori Kelompok Personel <span style="color:#C62828;">*</span>
                            </label>
                            <?php
                            $curr_kat = $is_edit ? $person['kategori'] : $default_kategori;
                            ?>
                            <select name="kategori" class="form-select" style="border:2px solid var(--purple);padding:0.75rem 1rem;font-weight:700;color:var(--navy);" required>
                                <option value="lpm" <?= $curr_kat === 'lpm' ? 'selected' : '' ?>>1. Personel LPM UNIKA Soegijapranata (Tim Utama LPM)</option>
                                <option value="gpm" <?= $curr_kat === 'gpm' ? 'selected' : '' ?>>2. Gugus Penjaminan Mutu (GPM) Fakultas</option>
                            </select>
                            <small class="text-muted">Pilih apakah personel ini termasuk Tim LPM Pusat atau Gugus Penjaminan Mutu (GPM) Fakultas.</small>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Lengkap &amp; Gelar <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="nama" id="inputNama" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Dr. Bernardinus Harnadi, ST., MT." value="<?= e($is_edit ? $person['nama'] : ($_POST['nama'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Urutan Tampil
                            </label>
                            <input type="number" name="urutan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" min="1" value="<?= (int)($is_edit ? $person['urutan'] : ($_POST['urutan'] ?? 1)) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jabatan / Peran Personel <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="jabatan" id="inputJabatan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Ketua GPM Fakultas Ilmu Komputer" value="<?= e($is_edit ? $person['jabatan'] : ($_POST['jabatan'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Level Struktur (Warna Badge)
                            </label>
                            <select name="level" id="inputLevel" class="form-select" style="border:1.5px solid var(--border);padding:0.7rem 1rem;">
                                <option value="pimpinan" <?= ($is_edit && $person['level'] === 'pimpinan') ? 'selected' : '' ?>>Pimpinan (Badge Gelap)</option>
                                <option value="sekretaris" <?= ($is_edit && $person['level'] === 'sekretaris') ? 'selected' : '' ?>>Sekretaris (Badge Biru)</option>
                                <option value="koordinator" <?= (!$is_edit || $person['level'] === 'koordinator') ? 'selected' : '' ?>>Koordinator / Ketua GPM (Badge Ungu)</option>
                                <option value="staf" <?= ($is_edit && $person['level'] === 'staf') ? 'selected' : '' ?>>Staf / Anggota (Badge Soft)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Bidang Tugas / Unit Kerja
                            </label>
                            <input type="text" name="bidang" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Penjaminan Mutu Akademik FIK" value="<?= e($is_edit ? $person['bidang'] : ($_POST['bidang'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Uraian Ringkas Peran
                            </label>
                            <textarea name="deskripsi" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Jelaskan peran singkat personel ini..."><?= e($is_edit ? ($person['deskripsi'] ?? '') : ($_POST['deskripsi'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Detail Tanggung Jawab Personel (Akan Muncul Saat Kartu Diklik)
                            </label>
                            <textarea name="tanggung_jawab" class="form-control" rows="4" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Tuliskan butir-butir tanggung jawab per baris..."><?= e($is_edit ? ($person['tanggung_jawab'] ?? '') : ($_POST['tanggung_jawab'] ?? '')) ?></textarea>
                            <small class="text-muted">Setiap baris baru akan otomatis ditampilkan sebagai poin tugas di modal detail.</small>
                        </div>

                        <!-- ==============================================
                             PANDUAN & FITUR CROP FOTO PERSONEL
                        ============================================== -->
                        <div class="col-12">
                            <div class="p-4 rounded-4" style="background:#F8FAFC;border:1.5px solid #E2E8F0;">
                                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:36px;height:36px;border-radius:8px;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-person-bounding-box" style="font-size:1.2rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold" style="color:var(--navy);">Pengaturan Foto Profil &amp; Panduan Tampilan</h6>
                                            <small class="text-muted">Dilengkapi panduan framing website dan pemotong gambar (Crop &amp; Flip)</small>
                                        </div>
                                    </div>
                                    <span class="badge" style="background:#EDE7F6;color:#4A148C;font-size:0.75rem;padding:0.4rem 0.8rem;border-radius:20px;">
                                        Rasio Tampilan Website: 4:3 (Landscape)
                                    </span>
                                </div>

                                <!-- Arahan Bagian Mana yang Ditampilkan -->
                                <div class="alert alert-info border-0 d-flex gap-3 mb-4 rounded-3" style="background:#EFF6FF;color:#1E3A8A;border-left:4px solid #1D4ED8 !important;">
                                    <i class="bi bi-info-circle-fill mt-1" style="font-size:1.25rem;color:#1D4ED8;flex-shrink:0;"></i>
                                    <div style="font-size:0.85rem;line-height:1.6;">
                                        <strong>Bagian Mana yang Ditampilkan di Website?</strong>
                                        <ul class="mb-0 ps-3 mt-1">
                                            <li>Kartu profil website LPM UNIKA menampilkan foto dengan <strong>rasio 4:3</strong> (tinggi kartu <strong>250px</strong> dengan posisi <em>fokus atas / top-center</em>).</li>
                                            <li><strong>Bagian yang paling baik ditampilkan:</strong> Dari <strong>puncak kepala/wajah hingga batas dada (pasfoto / setengah badan)</strong>.</li>
                                            <li>Pastikan wajah berada di <strong>tengah atau sepertiga atas</strong> bidang crop agar tidak terpotong saat ditampilkan di kartu.</li>
                                            <li>Gunakan fitur <strong>Crop &amp; Flip Horizontal</strong> di bawah ini untuk memotong dan membalik arah pandang wajah sesuai keinginan Anda.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="row g-4 align-items-start">
                                    <!-- Input File & Tombol Aksi Crop -->
                                    <div class="col-md-7">
                                        <label class="form-label fw-bold" style="color:var(--navy);font-size:0.9rem;">
                                            Pilih Berkas Foto Personel
                                        </label>
                                        <div class="input-group mb-2">
                                            <input type="file" id="fotoFileInput" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        </div>
                                        <small class="text-muted d-block mb-3">
                                            Format didukung: JPG, PNG, WEBP (Maksimal 5MB). Setelah memilih file, modal crop otomatis muncul.
                                        </small>

                                        <!-- Opsi Hapus Foto yang Sudah Tersimpan -->
                                        <?php 
                                        $cur_foto_url = ($is_edit && !empty($person['foto'])) ? getTimFotoUrl($person['foto']) : '';
                                        if ($cur_foto_url): 
                                        ?>
                                        <div class="p-3 rounded-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" id="existingPhotoCard" style="background:#FFF5F5;border:1.5px solid #FED7D7;">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= htmlspecialchars($cur_foto_url) ?>" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;border:1px solid #CBD5E1;">
                                                <div>
                                                    <div class="fw-bold text-danger" style="font-size:0.85rem;">Foto Personel Tersimpan</div>
                                                    <small class="text-muted"><?= e($person['foto']) ?></small>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" name="hapus_foto" id="checkHapusFoto" value="1" style="cursor:pointer;width:2.2em;height:1.2em;">
                                                <label class="form-check-label fw-bold text-danger ms-1" for="checkHapusFoto" style="cursor:pointer;font-size:0.85rem;">
                                                    <i class="bi bi-trash3 me-1"></i> Hapus Foto
                                                </label>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Tombol Buka Modal Crop Manual jika foto sudah dipilih -->
                                        <div class="d-flex gap-2 flex-wrap mb-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 py-2 d-none align-items-center gap-2" id="btnOpenCropperModal" style="border-radius:8px;">
                                                <i class="bi bi-crop"></i> Atur &amp; Crop Foto Ini
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-3 py-2 d-none align-items-center gap-1" id="btnResetFoto" style="border-radius:8px;">
                                                <i class="bi bi-arrow-counterclockwise"></i> Reset Foto
                                            </button>
                                        </div>

                                        <!-- Status Badge Hasil Crop -->
                                        <div id="cropStatusBox" class="p-3 rounded-3 d-none mb-3" style="background:#ECFDF5;border:1px solid #A7F3D0;">
                                            <div class="d-flex align-items-center gap-2 text-success fw-bold" style="font-size:0.85rem;">
                                                <i class="bi bi-check-circle-fill"></i> Foto Telah Di-Crop Sesuai Ukuran Kartu Website!
                                            </div>
                                            <div class="small text-muted mt-1">
                                                Foto siap disimpan. Jangan lupa klik tombol <strong>"<?= $is_edit ? 'Simpan Perubahan' : 'Tambahkan Personel' ?>"</strong> di bawah untuk menyimpan ke database.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live Preview Kartu Website -->
                                    <div class="col-md-5 d-flex flex-column align-items-center">
                                        <span class="text-muted small fw-bold mb-2 text-center">
                                            <i class="bi bi-eye me-1"></i> Simulasi Tampilan Kartu di Website
                                        </span>
                                        <div class="guide-card-preview">
                                            <div class="preview-photo-wrap">
                                                <?php
                                                $initial_foto = ($is_edit && $person['foto'] && file_exists(__DIR__ . '/../uploads/tim/' . $person['foto'])) 
                                                    ? SITE_URL . '/uploads/tim/' . e($person['foto']) 
                                                    : '';
                                                ?>
                                                <img src="<?= $initial_foto ?>" alt="Preview" id="liveCardImg" class="preview-img <?= empty($initial_foto) ? 'd-none' : '' ?>">
                                                <div id="liveCardPlaceholder" class="<?= !empty($initial_foto) ? 'd-none' : '' ?> text-center p-3 text-white-50">
                                                    <i class="bi bi-person-circle" style="font-size:3.5rem;color:rgba(255,255,255,0.7);"></i>
                                                    <div style="font-size:0.75rem;margin-top:0.25rem;">Foto Personel Tampil Di Sini</div>
                                                </div>
                                            </div>
                                            <div style="padding:1rem 1.25rem;">
                                                <div class="fw-bold" id="liveCardName" style="color:var(--navy);font-size:0.95rem;line-height:1.3;margin-bottom:0.25rem;">
                                                    <?= e($is_edit && $person['nama'] ? $person['nama'] : 'Nama Personel') ?>
                                                </div>
                                                <div class="badge bg-secondary" id="liveCardRole" style="font-size:0.75rem;padding:0.35rem 0.6rem;">
                                                    <?= e($is_edit && $person['jabatan'] ? $person['jabatan'] : 'Jabatan Personel') ?>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted mt-2 text-center" style="font-size:0.75rem;">
                                            Pratinjau langsung kartu profil website
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" style="padding-top:1.5rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="tim-list.php?kat=<?= e($default_kategori) ?>" style="color:var(--text-muted);font-size:0.875rem;text-decoration:none;">Batal</a>
                                <button type="submit" class="btn-submit" style="padding:0.75rem 2rem;font-size:0.95rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan' : 'Tambahkan Personel' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================
     MODAL CROPPER.JS DENGAN FITUR FLIP HORIZONTAL
============================================== -->
<div class="modal fade" id="modalCropFoto" tabindex="-1" aria-labelledby="modalCropFotoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, var(--navy), #1E3A8A);padding:1.25rem 1.5rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.3rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropFotoLabel">Crop &amp; Atur Posisi Foto Personel</h5>
                        <small style="color:rgba(255,255,255,0.8);">Sesuaikan rasio tampilan, zoom, rotasi, dan balik posisi (horizontal flip)</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F8FAFC;">
                
                <!-- Cropper Workspace -->
                <div class="crop-container mb-3 shadow-inner">
                    <img id="cropperImageSource" src="" alt="Source Image">
                </div>

                <!-- Toolbar Kontrol: Flip Horizontal, Zoom, Rotate, Ratio -->
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        
                        <!-- 1. Horizontal Flip (Kebutuhan Utama User) -->
                        <div class="col-md-4 col-sm-6">
                            <button type="button" class="btn btn-dark w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2" id="btnFlipHorizontal" title="Balik foto secara cermin horizontal (kiri-kanan)">
                                <i class="bi bi-symmetry-vertical" style="font-size:1.1rem;color:#FFD54F;"></i>
                                <span>Flip Horizontal (⇄)</span>
                            </button>
                        </div>

                        <!-- 2. Zoom In & Out -->
                        <div class="col-md-4 col-sm-6 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomIn" title="Perbesar (Zoom In)">
                                <i class="bi bi-zoom-in"></i> Zoom +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOut" title="Perkecil (Zoom Out)">
                                <i class="bi bi-zoom-out"></i> Zoom -
                            </button>
                        </div>

                        <!-- 3. Rotate Kiri & Kanan & Reset -->
                        <div class="col-md-4 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill py-2" id="btnRotateLeft" title="Putar 90 Derajat ke Kiri">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill py-2" id="btnRotateRight" title="Putar 90 Derajat ke Kanan">
                                <i class="bi bi-arrow-clockwise"></i> ↻
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill py-2" id="btnResetCrop" title="Kembalikan ke Posisi Semula">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Pilihan Rasio Aspek (Hanya 4:3 Tampilan Website & 1:1 Persegi) -->
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-semibold me-1">Rasio Crop:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn" data-ratio="1.3333">
                                <i class="bi bi-aspect-ratio me-1"></i> 4:3 (Sesuai Tampilan Website)
                            </button>
                            <button type="button" class="btn btn-outline-primary ratio-btn" data-ratio="1">
                                <i class="bi bi-square me-1"></i> 1:1 (Persegi)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.5rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:8px;">
                    Batal
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCrop" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg" style="font-size:1.1rem;"></i>
                    Terapkan Hasil Crop
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script Cropper.js & Logika Crop & Flip -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput         = document.getElementById('fotoFileInput');
    const btnOpenModal      = document.getElementById('btnOpenCropperModal');
    const btnResetFoto      = document.getElementById('btnResetFoto');
    const cropModalEl       = document.getElementById('modalCropFoto');
    const cropImageSource   = document.getElementById('cropperImageSource');
    const btnApplyCrop      = document.getElementById('btnApplyCrop');
    const hiddenCroppedData = document.getElementById('croppedFotoData');
    const liveCardImg       = document.getElementById('liveCardImg');
    const liveCardHolder    = document.getElementById('liveCardPlaceholder');
    const cropStatusBox     = document.getElementById('cropStatusBox');

    // Live preview input synchronization
    const inputNama = document.getElementById('inputNama');
    const inputJabatan = document.getElementById('inputJabatan');
    const liveCardName = document.getElementById('liveCardName');
    const liveCardRole = document.getElementById('liveCardRole');

    if (inputNama && liveCardName) {
        inputNama.addEventListener('input', function() {
            liveCardName.textContent = this.value.trim() || 'Nama Personel';
        });
    }
    if (inputJabatan && liveCardRole) {
        inputJabatan.addEventListener('input', function() {
            liveCardRole.textContent = this.value.trim() || 'Jabatan Personel';
        });
    }

    let cropperInstance = null;
    let currentScaleX   = 1;
    let activeRatio     = 4 / 3; // default ratio 4:3 for website card
    const bsCropModal   = new bootstrap.Modal(cropModalEl);

    // Opsi Hapus Foto Tersimpan
    const checkHapusFoto = document.getElementById('checkHapusFoto');
    if (checkHapusFoto) {
        checkHapusFoto.addEventListener('change', function() {
            if (this.checked) {
                // Tampilkan avatar default di Live Preview Card
                liveCardImg.classList.add('d-none');
                liveCardHolder.classList.remove('d-none');
                // Reset input file baru jika ada
                fileInput.value = '';
                hiddenCroppedData.value = '';
                btnOpenModal.classList.add('d-none');
                cropStatusBox.classList.add('d-none');
            } else {
                // Kembalikan foto lama jika tidak dicentang
                <?php if ($is_edit && !empty($person['foto']) && file_exists(__DIR__ . '/../uploads/tim/' . $person['foto'])): ?>
                    liveCardImg.src = '<?= SITE_URL ?>/uploads/tim/<?= e($person['foto']) ?>';
                    liveCardImg.classList.remove('d-none');
                    liveCardHolder.classList.add('d-none');
                <?php endif; ?>
            }
        });
    }

    // Saat admin memilih file gambar dari komputernya
    fileInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            // Batalkan centang hapus foto jika memilih foto baru
            if (checkHapusFoto) {
                checkHapusFoto.checked = false;
            }
            const file = files[0];
            if (!file.type.match(/^image\//)) {
                alert('Silakan pilih file gambar yang valid (JPG, PNG, atau WEBP).');
                fileInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(evt) {
                cropImageSource.src = evt.target.result;
                btnOpenModal.classList.remove('d-none');
                btnOpenModal.classList.add('d-inline-flex');
                btnResetFoto.classList.remove('d-none');
                btnResetFoto.classList.add('d-inline-flex');
                
                // Langsung buka modal cropper secara otomatis untuk memudahkan admin
                bsCropModal.show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Inisialisasi Cropper.js saat modal ditampilkan
    cropModalEl.addEventListener('shown.bs.modal', function() {
        if (cropperInstance) {
            cropperInstance.destroy();
        }
        currentScaleX = 1;

        cropperInstance = new Cropper(cropImageSource, {
            aspectRatio: activeRatio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    });

    // Hancurkan cropper saat modal ditutup agar resource bersih
    cropModalEl.addEventListener('hidden.bs.modal', function() {
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
    });

    // Tombol Buka Modal Crop Manual
    btnOpenModal.addEventListener('click', function() {
        if (cropImageSource.src) {
            bsCropModal.show();
        }
    });

    // 1. FITUR FLIP HORIZONTAL (Kiri-Kanan)
    const btnFlipH = document.getElementById('btnFlipHorizontal');
    btnFlipH.addEventListener('click', function() {
        if (!cropperInstance) return;
        currentScaleX = currentScaleX === 1 ? -1 : 1;
        cropperInstance.scaleX(currentScaleX);
    });

    // 2. Zoom In & Out
    document.getElementById('btnZoomIn').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.zoom(0.1);
    });
    document.getElementById('btnZoomOut').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.zoom(-0.1);
    });

    // 3. Rotate Kiri & Kanan
    document.getElementById('btnRotateLeft').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.rotate(-90);
    });
    document.getElementById('btnRotateRight').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.rotate(90);
    });

    // 4. Reset
    document.getElementById('btnResetCrop').addEventListener('click', function() {
        if (cropperInstance) {
            currentScaleX = 1;
            cropperInstance.reset();
        }
    });

    // 5. Ubah Rasio Aspek
    document.querySelectorAll('.ratio-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const ratioVal = parseFloat(this.getAttribute('data-ratio'));
            activeRatio = ratioVal;
            if (cropperInstance) {
                cropperInstance.setAspectRatio(ratioVal);
            }
        });
    });

    // 6. Tombol TERAPKAN HASIL CROP
    btnApplyCrop.addEventListener('click', function() {
        if (!cropperInstance) return;

        // Ambil canvas hasil crop dengan resolusi tinggi (lebar standar kartu 700px, 4:3)
        const canvas = cropperInstance.getCroppedCanvas({
            width: 700,
            height: activeRatio && !isNaN(activeRatio) ? Math.round(700 / activeRatio) : 525,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        if (canvas) {
            const base64Image = canvas.toDataURL('image/jpeg', 0.92);

            // Simpan ke input hidden untuk dikirim via form POST ke PHP
            hiddenCroppedData.value = base64Image;

            // Tampilkan hasil crop langsung pada Live Preview Card
            liveCardImg.src = base64Image;
            liveCardImg.classList.remove('d-none');
            liveCardHolder.classList.add('d-none');

            // Tampilkan pesan status berhasil crop
            cropStatusBox.classList.remove('d-none');
            btnOpenModal.innerHTML = '<i class="bi bi-crop"></i> Ubah / Crop Ulang Foto';

            // Tutup modal
            bsCropModal.hide();
        }
    });

    // Tombol Reset Foto
    btnResetFoto.addEventListener('click', function() {
        if (confirm('Apakah Anda ingin membatalkan pemilihan foto baru ini?')) {
            fileInput.value = '';
            hiddenCroppedData.value = '';
            cropImageSource.src = '';
            btnOpenModal.classList.add('d-none');
            btnResetFoto.classList.add('d-none');
            cropStatusBox.classList.add('d-none');

            <?php if ($is_edit && $person['foto'] && file_exists(__DIR__ . '/../uploads/tim/' . $person['foto'])): ?>
                liveCardImg.src = '<?= SITE_URL ?>/uploads/tim/<?= e($person['foto']) ?>';
                liveCardImg.classList.remove('d-none');
                liveCardHolder.classList.add('d-none');
            <?php else: ?>
                liveCardImg.src = '';
                liveCardImg.classList.add('d-none');
                liveCardHolder.classList.remove('d-none');
            <?php endif; ?>
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
