<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$is_edit = false;
$data = [];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM ami_siklus4_dokumentasi WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Dokumentasi Audit Lapangan (Siklus 4)' : 'Tambah Dokumentasi Audit Lapangan (Siklus 4)';

$periodes = $db->query("SELECT * FROM ami_periode WHERE is_active = 1 ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$default_periode = $_GET['periode'] ?? ($data['periode'] ?? ($periodes[0]['nama_periode'] ?? '2025/2026'));

// Ambil Master Fakultas & Prodi dari akreditasi_prodi
$fakultas_list = $db->query("SELECT DISTINCT fakultas FROM akreditasi_prodi ORDER BY fakultas ASC")->fetchAll(PDO::FETCH_COLUMN);
$prodi_rows = $db->query("SELECT DISTINCT fakultas, program_studi, strata FROM akreditasi_prodi ORDER BY fakultas ASC, program_studi ASC")->fetchAll(PDO::FETCH_ASSOC);

// Susun map JSON fakultas -> prodi
$fakultas_prodi_map = [];
foreach ($prodi_rows as $row) {
    $f = $row['fakultas'];
    if (!isset($fakultas_prodi_map[$f])) {
        $fakultas_prodi_map[$f] = [];
    }
    $fakultas_prodi_map[$f][] = $row['program_studi'] . ' (' . $row['strata'] . ')';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periode = trim($_POST['periode'] ?? '');
    $tingkat = trim($_POST['tingkat'] ?? 'fakultas'); // 'fakultas' or 'prodi'
    if (!in_array($tingkat, ['fakultas', 'prodi'])) $tingkat = 'fakultas';

    $fakultas = trim($_POST['fakultas'] ?? '');
    $prodi = ($tingkat === 'prodi') ? trim($_POST['prodi'] ?? '') : null;
    $judul = trim($_POST['judul'] ?? '');
    $narasi_berita_acara = '';

    if ($periode === '' || $fakultas === '' || $judul === '') {
        $error = 'Periode, Fakultas, dan Judul Dokumentasi wajib diisi.';
    } elseif ($tingkat === 'prodi' && empty($prodi)) {
        $error = 'Harap pilih Program Studi untuk dokumentasi tingkat Prodi.';
    } else {
        $upload_dir = __DIR__ . '/../uploads/ami/siklus4/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

        // 1. File Berita Acara
        $file_berita_acara = $data['file_berita_acara'] ?? null;
        if (!empty($_FILES['file_berita_acara']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_berita_acara']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
                $error = 'Format file Berita Acara harus PDF atau Word.';
            } else {
                $ba_name = 'ba_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_berita_acara']['tmp_name'], $upload_dir . $ba_name)) {
                    if ($is_edit && !empty($data['file_berita_acara'])) {
                        deleteUploadedFile($data['file_berita_acara']);
                    }
                    $file_berita_acara = 'ami/siklus4/' . $ba_name;
                }
            }
        }

        // 2. File Daftar Hadir
        $file_daftar_hadir = $data['file_daftar_hadir'] ?? null;
        if (!$error && !empty($_FILES['file_daftar_hadir']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_daftar_hadir']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                $error = 'Format file Daftar Hadir harus PDF, Word, atau Excel.';
            } else {
                $dh_name = 'dh_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_daftar_hadir']['tmp_name'], $upload_dir . $dh_name)) {
                    if ($is_edit && !empty($data['file_daftar_hadir'])) {
                        deleteUploadedFile($data['file_daftar_hadir']);
                    }
                    $file_daftar_hadir = 'ami/siklus4/' . $dh_name;
                }
            }
        }

        // 3. File Undangan
        $file_undangan = $data['file_undangan'] ?? null;
        if (!$error && !empty($_FILES['file_undangan']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_undangan']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
                $error = 'Format file Undangan harus PDF atau Word.';
            } else {
                $und_name = 'undangan_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_undangan']['tmp_name'], $upload_dir . $und_name)) {
                    if ($is_edit && !empty($data['file_undangan'])) {
                        deleteUploadedFile($data['file_undangan']);
                    }
                    $file_undangan = 'ami/siklus4/' . $und_name;
                }
            }
        }

        // 4. Foto-foto Dokumentasi Kegiatan (Multiple Upload)
        $existing_photos = [];
        if (!empty($data['foto_kegiatan'])) {
            $decoded = json_decode($data['foto_kegiatan'], true);
            if (is_array($decoded)) $existing_photos = $decoded;
        }

        // Cek jika ada foto yang dihapus melalui checkbox
        if (isset($_POST['delete_photos']) && is_array($_POST['delete_photos'])) {
            foreach ($_POST['delete_photos'] as $dp) {
                deleteUploadedFile($dp);
            }
            $existing_photos = array_values(array_diff($existing_photos, $_POST['delete_photos']));
        }

        // Cek jika ada foto hasil crop / edit (Base64)
        $has_cropped = false;
        if (!empty($_POST['cropped_photos']) && is_array($_POST['cropped_photos'])) {
            foreach ($_POST['cropped_photos'] as $c_data) {
                if (preg_match('/^data:image\/(\w+);base64,/', $c_data, $c_match)) {
                    $raw = base64_decode(substr($c_data, strpos($c_data, ',') + 1));
                    if ($raw !== false) {
                        $c_name = 'dok_crop_' . time() . '_' . uniqid() . '.webp';
                        if (file_put_contents($upload_dir . $c_name, $raw)) {
                            $existing_photos[] = 'ami/siklus4/' . $c_name;
                            $has_cropped = true;
                        }
                    }
                }
            }
        }

        // Cegah upload ganda: jika sudah ada foto hasil crop, jangan proses ulang $_FILES foto_kegiatan
        if (!$error && !$has_cropped && !empty($_FILES['foto_kegiatan']['name'][0])) {
            $count = count($_FILES['foto_kegiatan']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['foto_kegiatan']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['foto_kegiatan']['tmp_name'][$i];
                    $saved_webp = convertAndSaveWebP($tmp_name, $upload_dir, 'dok_', 85, 1920);
                    if ($saved_webp) {
                        $existing_photos[] = 'ami/siklus4/' . $saved_webp;
                    }
                }
            }
        }

        $foto_kegiatan_json = !empty($existing_photos) ? json_encode($existing_photos) : null;

        // Pengaturan Pembatasan Akses Masing-Masing Dokumen
        $res_undangan_is_restricted     = !empty($_POST['undangan_is_restricted']) ? 1 : 0;
        $res_undangan_max_pages         = (int)($_POST['undangan_max_preview_pages'] ?? 3);
        $res_undangan_allowed_domains   = parseSubmittedAllowedDomains('undangan');

        $res_daftar_hadir_is_restricted   = !empty($_POST['daftar_hadir_is_restricted']) ? 1 : 0;
        $res_daftar_hadir_max_pages       = (int)($_POST['daftar_hadir_max_preview_pages'] ?? 3);
        $res_daftar_hadir_allowed_domains = parseSubmittedAllowedDomains('daftar_hadir');

        $res_berita_acara_is_restricted   = !empty($_POST['berita_acara_is_restricted']) ? 1 : 0;
        $res_berita_acara_max_pages       = (int)($_POST['berita_acara_max_preview_pages'] ?? 3);
        $res_berita_acara_allowed_domains = parseSubmittedAllowedDomains('berita_acara');

        // Status restriction global (kompatibilitas)
        $is_restricted     = ($res_undangan_is_restricted || $res_daftar_hadir_is_restricted || $res_berita_acara_is_restricted) ? 1 : 0;
        $max_preview_pages = 3;
        $allowed_domains   = '@unika.ac.id,@student.unika.ac.id';

        if (!$error) {
            if ($is_edit) {
                $stmt = $db->prepare("
                    UPDATE ami_siklus4_dokumentasi 
                    SET periode = ?, tingkat = ?, fakultas = ?, prodi = ?, judul = ?, narasi_berita_acara = '', 
                        file_berita_acara = ?, file_daftar_hadir = ?, file_undangan = ?, foto_kegiatan = ?, 
                        is_restricted = ?, max_preview_pages = ?, allowed_domains = ?,
                        is_restricted_undangan = ?, max_preview_pages_undangan = ?, allowed_domains_undangan = ?,
                        is_restricted_daftar_hadir = ?, max_preview_pages_daftar_hadir = ?, allowed_domains_daftar_hadir = ?,
                        is_restricted_berita_acara = ?, max_preview_pages_berita_acara = ?, allowed_domains_berita_acara = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $periode, $tingkat, $fakultas, $prodi, $judul,
                    $file_berita_acara, $file_daftar_hadir, $file_undangan, $foto_kegiatan_json,
                    $is_restricted, $max_preview_pages, $allowed_domains,
                    $res_undangan_is_restricted, $res_undangan_max_pages, $res_undangan_allowed_domains,
                    $res_daftar_hadir_is_restricted, $res_daftar_hadir_max_pages, $res_daftar_hadir_allowed_domains,
                    $res_berita_acara_is_restricted, $res_berita_acara_max_pages, $res_berita_acara_allowed_domains,
                    $id
                ]);
                $_SESSION['flash'] = 'Dokumentasi audit lapangan berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO ami_siklus4_dokumentasi 
                    (periode, tingkat, fakultas, prodi, judul, narasi_berita_acara, file_berita_acara, file_daftar_hadir, file_undangan, foto_kegiatan, 
                     is_restricted, max_preview_pages, allowed_domains,
                     is_restricted_undangan, max_preview_pages_undangan, allowed_domains_undangan,
                     is_restricted_daftar_hadir, max_preview_pages_daftar_hadir, allowed_domains_daftar_hadir,
                     is_restricted_berita_acara, max_preview_pages_berita_acara, allowed_domains_berita_acara) 
                    VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $periode, $tingkat, $fakultas, $prodi, $judul,
                    $file_berita_acara, $file_daftar_hadir, $file_undangan, $foto_kegiatan_json,
                    $is_restricted, $max_preview_pages, $allowed_domains,
                    $res_undangan_is_restricted, $res_undangan_max_pages, $res_undangan_allowed_domains,
                    $res_daftar_hadir_is_restricted, $res_daftar_hadir_max_pages, $res_daftar_hadir_allowed_domains,
                    $res_berita_acara_is_restricted, $res_berita_acara_max_pages, $res_berita_acara_allowed_domains
                ]);
                $_SESSION['flash'] = 'Dokumentasi audit lapangan berhasil ditambahkan.';
            }
            redirect(SITE_URL . '/admin/ami-siklus-list.php?tab=siklus4&periode=' . urlencode($periode));
        }
    }
}

$cur_tingkat = $data['tingkat'] ?? ($_POST['tingkat'] ?? 'fakultas');
$cur_fakultas = $data['fakultas'] ?? ($_POST['fakultas'] ?? '');
$cur_prodi = $data['prodi'] ?? ($_POST['prodi'] ?? '');
$current_photos = !empty($data['foto_kegiatan']) ? json_decode($data['foto_kegiatan'], true) : [];

// Pembatasan terpisah masing-masing dokumen
$res_undangan_is_restricted   = (int)($data['is_restricted_undangan'] ?? $data['is_restricted'] ?? 0);
$res_undangan_max_pages       = (int)($data['max_preview_pages_undangan'] ?? $data['max_preview_pages'] ?? 3);
$res_undangan_allowed_domains = $data['allowed_domains_undangan'] ?? $data['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

$res_daftar_hadir_is_restricted   = (int)($data['is_restricted_daftar_hadir'] ?? $data['is_restricted'] ?? 0);
$res_daftar_hadir_max_pages       = (int)($data['max_preview_pages_daftar_hadir'] ?? $data['max_preview_pages'] ?? 3);
$res_daftar_hadir_allowed_domains = $data['allowed_domains_daftar_hadir'] ?? $data['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

$res_berita_acara_is_restricted   = (int)($data['is_restricted_berita_acara'] ?? $data['is_restricted'] ?? 0);
$res_berita_acara_max_pages       = (int)($data['max_preview_pages_berita_acara'] ?? $data['max_preview_pages'] ?? 3);
$res_berita_acara_allowed_domains = $data['allowed_domains_berita_acara'] ?? $data['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
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
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><?= $admin_page_title ?></h4>
                    <p class="text-muted small mb-0">
                        Formulir terpadu dokumentasi audit lapangan: narasi berita acara, softfile BA, daftar hadir, dan foto kegiatan.
                    </p>
                </div>
                <span class="badge bg-navy px-3 py-2" style="font-size:0.85rem;">
                    <i class="bi bi-journal-check me-1"></i> Dokumentasi Terpadu
                </span>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- 1. Metadata & Lingkup Tingkatan -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Klasifikasi &amp; Tingkat Audit</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Periode AMI <span class="text-danger">*</span></label>
                                    <select name="periode" class="form-select" required>
                                        <?php foreach ($periodes as $p): ?>
                                        <option value="<?= e($p['nama_periode']) ?>" <?= $default_periode === $p['nama_periode'] ? 'selected' : '' ?>>
                                            <?= e($p['nama_periode']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Tingkatan Audit <span class="text-danger">*</span></label>
                                    <select name="tingkat" id="tingkatSelect" class="form-select" onchange="handleTingkatChange()" required>
                                        <option value="fakultas" <?= $cur_tingkat === 'fakultas' ? 'selected' : '' ?>>Tingkat Fakultas</option>
                                        <option value="prodi" <?= $cur_tingkat === 'prodi' ? 'selected' : '' ?>>Tingkat Program Studi</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Pilih Fakultas <span class="text-danger">*</span></label>
                                    <select name="fakultas" id="fakultasSelect" class="form-select" onchange="handleFakultasChange()" required>
                                        <option value="">-- Pilih Fakultas --</option>
                                        <?php foreach ($fakultas_list as $f): ?>
                                        <option value="<?= e($f) ?>" <?= $cur_fakultas === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12" id="prodiGroup" style="display: <?= $cur_tingkat === 'prodi' ? 'block' : 'none' ?>;">
                                    <label class="form-label fw-bold small">Pilih Program Studi <span class="text-danger">*</span></label>
                                    <select name="prodi" id="prodiSelect" class="form-select">
                                        <option value="">-- Pilih Program Studi --</option>
                                    </select>
                                    <div class="form-text">Daftar Program Studi otomatis menyesuaikan Fakultas yang dipilih di atas.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Judul Kegiatan -->
                    <div class="col-12">
                        <label class="form-label fw-bold small">Judul Dokumentasi Audit Lapangan <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Audit Lapangan Program Studi Sistem Informasi Semester Gasal 2025/2026" value="<?= e($data['judul'] ?? ($_POST['judul'] ?? '')) ?>" required>
                    </div>

                    <!-- 3. Berkas Dokumen (Undangan, Daftar Hadir, Berita Acara) dengan Button Pembatasan Sendiri-Sendiri -->
                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100 bg-white d-flex flex-column">
                            <label class="form-label fw-bold small d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-envelope-paper text-warning me-1"></i> Undangan</span>
                            </label>
                            <input type="file" name="file_undangan" class="form-control check-filesize" accept=".pdf,.doc,.docx" data-max-mb="20">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Maks 20 MB</span> PDF / DOC / DOCX
                            </div>

                            <?php if (!empty($data['file_undangan'])): ?>
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="small text-truncate" style="max-width:140px;"><?= basename($data['file_undangan']) ?></span>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_undangan']) ?>" target="_blank" class="btn btn-xs btn-outline-warning py-0 px-2" style="font-size:0.75rem;">
                                    Lihat File
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Button Pembatasan Khusus Undangan -->
                            <div class="mt-auto pt-3">
                                <button type="button" class="btn btn-sm w-100 d-flex align-items-center justify-content-between text-start" 
                                        data-bs-toggle="modal" data-bs-target="#modalResUndanganS4"
                                        style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:10px;padding:0.45rem 0.75rem;color:#92400E;min-height:52px;transition:all 0.2s;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 me-1">
                                        <i class="bi bi-shield-lock-fill text-warning fs-5 flex-shrink-0"></i>
                                        <div class="min-w-0" style="line-height:1.2;">
                                            <div class="fw-bold text-truncate" style="font-size:0.8rem;color:#78350F;">Proteksi Akses</div>
                                            <div class="text-truncate" style="font-size:0.72rem;color:#B45309;">Berkas Undangan</div>
                                        </div>
                                    </div>
                                    <span class="badge flex-shrink-0" id="badgeUndanganStatusS4" style="font-size:0.72rem;padding:0.4rem 0.6rem;border-radius:6px;white-space:nowrap;<?= $res_undangan_is_restricted ? 'background:#F59E0B;color:#fff;' : 'background:#E2E8F0;color:#475569;' ?>">
                                        <?= $res_undangan_is_restricted ? 'Dibatasi (' . $res_undangan_max_pages . ' Hal)' : 'Bebas / Publik' ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100 bg-white d-flex flex-column">
                            <label class="form-label fw-bold small d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-card-checklist text-success me-1"></i> Daftar Hadir</span>
                            </label>
                            <input type="file" name="file_daftar_hadir" class="form-control check-filesize" accept=".pdf,.doc,.docx,.xls,.xlsx" data-max-mb="20">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Maks 20 MB</span> PDF / Word / Excel
                            </div>

                            <?php if (!empty($data['file_daftar_hadir'])): ?>
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="small text-truncate" style="max-width:140px;"><?= basename($data['file_daftar_hadir']) ?></span>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_daftar_hadir']) ?>" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:0.75rem;">
                                    Lihat File
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Button Pembatasan Khusus Daftar Hadir -->
                            <div class="mt-auto pt-3">
                                <button type="button" class="btn btn-sm w-100 d-flex align-items-center justify-content-between text-start" 
                                        data-bs-toggle="modal" data-bs-target="#modalResDaftarHadirS4"
                                        style="background:#ECFDF5;border:1.5px solid #A7F3D0;border-radius:10px;padding:0.45rem 0.75rem;color:#065F46;min-height:52px;transition:all 0.2s;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 me-1">
                                        <i class="bi bi-shield-lock-fill text-success fs-5 flex-shrink-0"></i>
                                        <div class="min-w-0" style="line-height:1.2;">
                                            <div class="fw-bold text-truncate" style="font-size:0.8rem;color:#065F46;">Proteksi Akses</div>
                                            <div class="text-truncate" style="font-size:0.72rem;color:#047857;">Berkas Daftar Hadir</div>
                                        </div>
                                    </div>
                                    <span class="badge flex-shrink-0" id="badgeDaftarHadirStatusS4" style="font-size:0.72rem;padding:0.4rem 0.6rem;border-radius:6px;white-space:nowrap;<?= $res_daftar_hadir_is_restricted ? 'background:#10B981;color:#fff;' : 'background:#E2E8F0;color:#475569;' ?>">
                                        <?= $res_daftar_hadir_is_restricted ? 'Dibatasi (' . $res_daftar_hadir_max_pages . ' Hal)' : 'Bebas / Publik' ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100 bg-white d-flex flex-column">
                            <label class="form-label fw-bold small d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-file-earmark-check text-primary me-1"></i> Berita Acara</span>
                            </label>
                            <input type="file" name="file_berita_acara" class="form-control check-filesize" accept=".pdf,.doc,.docx" data-max-mb="20">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Maks 20 MB</span> PDF / DOC / DOCX
                            </div>

                            <?php if (!empty($data['file_berita_acara'])): ?>
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="small text-truncate" style="max-width:140px;"><?= basename($data['file_berita_acara']) ?></span>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_berita_acara']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:0.75rem;">
                                    Lihat File
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Button Pembatasan Khusus Berita Acara -->
                            <div class="mt-auto pt-3">
                                <button type="button" class="btn btn-sm w-100 d-flex align-items-center justify-content-between text-start" 
                                        data-bs-toggle="modal" data-bs-target="#modalResBeritaAcaraS4"
                                        style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:10px;padding:0.45rem 0.75rem;color:#1E40AF;min-height:52px;transition:all 0.2s;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 me-1">
                                        <i class="bi bi-shield-lock-fill text-primary fs-5 flex-shrink-0"></i>
                                        <div class="min-w-0" style="line-height:1.2;">
                                            <div class="fw-bold text-truncate" style="font-size:0.8rem;color:#1E40AF;">Proteksi Akses</div>
                                            <div class="text-truncate" style="font-size:0.72rem;color:#2563EB;">Berkas Berita Acara</div>
                                        </div>
                                    </div>
                                    <span class="badge flex-shrink-0" id="badgeBeritaAcaraStatusS4" style="font-size:0.72rem;padding:0.4rem 0.6rem;border-radius:6px;white-space:nowrap;<?= $res_berita_acara_is_restricted ? 'background:#3B82F6;color:#fff;' : 'background:#E2E8F0;color:#475569;' ?>">
                                        <?= $res_berita_acara_is_restricted ? 'Dibatasi (' . $res_berita_acara_max_pages . ' Hal)' : 'Bebas / Publik' ?>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Pengaturan Pembatasan Berkas Undangan -->
                    <div class="modal fade" id="modalResUndanganS4" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom py-3" style="background:#FFFBEB;">
                                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" style="font-size:1.05rem;">
                                        <i class="bi bi-shield-lock-fill text-warning fs-5"></i> Pengaturan Pembatasan: Undangan
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <?php
                                    $res_prefix          = 'undangan';
                                    $res_title           = 'Batasi / Proteksi Berkas Undangan';
                                    $res_is_restricted   = $res_undangan_is_restricted;
                                    $res_max_pages       = $res_undangan_max_pages;
                                    $res_allowed_domains = $res_undangan_allowed_domains;
                                    require __DIR__ . '/includes/file-restriction-field.php';
                                    ?>
                                </div>
                                <div class="modal-footer border-top py-2 bg-light">
                                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill" data-bs-dismiss="modal">
                                        <i class="bi bi-check2 me-1"></i> Selesai &amp; Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Pengaturan Pembatasan Berkas Daftar Hadir -->
                    <div class="modal fade" id="modalResDaftarHadirS4" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom py-3" style="background:#ECFDF5;">
                                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" style="font-size:1.05rem;">
                                        <i class="bi bi-shield-lock-fill text-success fs-5"></i> Pengaturan Pembatasan: Daftar Hadir
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <?php
                                    $res_prefix          = 'daftar_hadir';
                                    $res_title           = 'Batasi / Proteksi Berkas Daftar Hadir';
                                    $res_is_restricted   = $res_daftar_hadir_is_restricted;
                                    $res_max_pages       = $res_daftar_hadir_max_pages;
                                    $res_allowed_domains = $res_daftar_hadir_allowed_domains;
                                    require __DIR__ . '/includes/file-restriction-field.php';
                                    ?>
                                </div>
                                <div class="modal-footer border-top py-2 bg-light">
                                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill" data-bs-dismiss="modal">
                                        <i class="bi bi-check2 me-1"></i> Selesai &amp; Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Pengaturan Pembatasan Berkas Berita Acara -->
                    <div class="modal fade" id="modalResBeritaAcaraS4" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom py-3" style="background:#EFF6FF;">
                                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" style="font-size:1.05rem;">
                                        <i class="bi bi-shield-lock-fill text-primary fs-5"></i> Pengaturan Pembatasan: Berita Acara
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <?php
                                    $res_prefix          = 'berita_acara';
                                    $res_title           = 'Batasi / Proteksi Berkas Berita Acara';
                                    $res_is_restricted   = $res_berita_acara_is_restricted;
                                    $res_max_pages       = $res_berita_acara_max_pages;
                                    $res_allowed_domains = $res_berita_acara_allowed_domains;
                                    require __DIR__ . '/includes/file-restriction-field.php';
                                    ?>
                                </div>
                                <div class="modal-footer border-top py-2 bg-light">
                                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill" data-bs-dismiss="modal">
                                        <i class="bi bi-check2 me-1"></i> Selesai &amp; Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Upload Foto Dokumentasi (Multiple + Cropper Support) -->
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <label class="form-label fw-bold small mb-0">
                                    <i class="bi bi-images text-purple me-1"></i> Unggah Foto-Foto Kegiatan Audit Lapangan
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-none" id="btnOpenCropperS4" style="border-radius:8px;">
                                    <i class="bi bi-crop me-1"></i> Edit &amp; Crop Foto Pilihan
                                </button>
                            </div>
                            
                            <input type="file" name="foto_kegiatan[]" id="fotoKegiatanInput" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 10 MB per foto</span>
                                Mendukung format JPG, PNG, dan WebP. Anda dapat memilih beberapa foto sekaligus. Gambar otomatis dikonversi ke WebP kualitas HD terkompresi.
                            </div>

                            <!-- Kontainer Hasil Foto Tambahan dari Editor Crop -->
                            <div id="croppedPhotosContainer" class="mt-3 d-none">
                                <div class="small fw-bold text-success mb-2">
                                    <i class="bi bi-check-circle-fill me-1"></i> Foto Hasil Edit Siap Disimpan:
                                </div>
                                <div class="row g-2" id="croppedPhotosGrid"></div>
                            </div>

                            <?php if (!empty($current_photos)): ?>
                            <div class="mt-3">
                                <div class="small fw-bold text-muted mb-2">Foto Dokumentasi Tersimpan:</div>
                                <div class="row g-2">
                                    <?php foreach ($current_photos as $idx => $photo): ?>
                                    <div class="col-6 col-sm-4 col-md-3" id="photoItemWrapS4_<?= $idx ?>">
                                        <div class="card p-1 border position-relative h-100 shadow-sm" id="cardExistingS4_<?= $idx ?>">
                                            <div style="height:110px; border-radius:4px; overflow:hidden; background:#000;">
                                                <img src="<?= SITE_URL ?>/uploads/<?= e($photo) ?>" alt="Foto" id="imgThumbS4_<?= $idx ?>" style="height:100%;object-fit:cover;width:100%;">
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-2 px-1 gap-1 flex-wrap">
                                                <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold btn-crop-existing-s4 d-inline-flex align-items-center gap-1"
                                                        data-photo-url="<?= SITE_URL ?>/uploads/<?= e($photo) ?>"
                                                        data-photo-path="<?= e($photo) ?>"
                                                        data-idx="<?= $idx ?>"
                                                        title="Sesuaikan, putar, atau potong foto tersimpan ini"
                                                        style="font-size:0.75rem; border-radius:4px;">
                                                    <i class="bi bi-crop text-warning"></i> Sesuaikan
                                                </button>
                                                <div class="form-check m-0">
                                                    <input class="form-check-input check-del-s4" type="checkbox" name="delete_photos[]" value="<?= e($photo) ?>" id="delPhoto_<?= $idx ?>" data-idx="<?= $idx ?>">
                                                    <label class="form-check-label text-danger" for="delPhoto_<?= $idx ?>" style="font-size:0.75rem; cursor:pointer;">Hapus</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Dokumentasi
                    </button>
                    <a href="ami-siklus-list.php?tab=siklus4&periode=<?= urlencode($default_periode) ?>" class="btn btn-light px-3 py-2">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cropper S4 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<div class="modal fade" id="modalCropS4" tabindex="-1" aria-labelledby="modalCropS4Label" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropS4Label">Editor Gambar AMI: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing kartu foto dokumentasi audit lapangan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgS4" src="" alt="Source" crossorigin="anonymous" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHS4">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVS4">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftS4">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightS4">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInS4">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutS4">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropS4">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-s4 fw-semibold" data-ratio="1.777778">16:9 Card</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-s4 fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-s4 fw-semibold" data-ratio="1">1:1 Persegi</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-s4 fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropS4" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
var fakultasProdiMap = <?= json_encode($fakultas_prodi_map) ?>;
var selectedProdi = <?= json_encode($cur_prodi) ?>;

function handleTingkatChange() {
    var tingkat = document.getElementById('tingkatSelect').value;
    var prodiGroup = document.getElementById('prodiGroup');
    if (tingkat === 'prodi') {
        prodiGroup.style.display = 'block';
        handleFakultasChange();
    } else {
        prodiGroup.style.display = 'none';
    }
}

function handleFakultasChange() {
    var fSelect = document.getElementById('fakultasSelect');
    var pSelect = document.getElementById('prodiSelect');
    var selectedF = fSelect.value;
    
    pSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';
    
    if (selectedF && fakultasProdiMap[selectedF]) {
        fakultasProdiMap[selectedF].forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            if (item === selectedProdi) {
                opt.selected = true;
            }
            pSelect.appendChild(opt);
        });
    }
}

// Validasi ukuran file dokumen
document.querySelectorAll('.check-filesize').forEach(function(input) {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var maxMb = parseFloat(this.getAttribute('data-max-mb')) || 20;
            var sizeMb = this.files[0].size / (1024 * 1024);
            if (sizeMb > maxMb) {
                alert('Peringatan: Ukuran berkas (' + sizeMb.toFixed(2) + ' MB) melebihi batas maksimal ' + maxMb + ' MB. Silakan unggah dokumen yang lebih kecil.');
                this.value = '';
            }
        }
    });
});

// Cropper untuk foto kegiatan Siklus 4
document.addEventListener('DOMContentLoaded', function() {
    handleFakultasChange();

    var fotoInput        = document.getElementById('fotoKegiatanInput');
    var btnOpenCropper   = document.getElementById('btnOpenCropperS4');
    var modalEl          = document.getElementById('modalCropS4');
    var cropperSourceImg = document.getElementById('cropperSourceImgS4');
    var btnApplyCrop     = document.getElementById('btnApplyCropS4');
    var containerGrid    = document.getElementById('croppedPhotosGrid');
    var containerWrap    = document.getElementById('croppedPhotosContainer');

    if (!fotoInput || !modalEl) return;

    var bsModal = new bootstrap.Modal(modalEl);
    var cropper = null;
    var scaleX = 1;
    var scaleY = 1;
    var currentRatio = 16 / 9;

    var currentTargetExistingIdx  = null;
    var currentTargetExistingPath = null;

    function initCropper() {
        if (cropper) cropper.destroy();
        scaleX = 1;
        scaleY = 1;
        cropper = new Cropper(cropperSourceImg, {
            aspectRatio: currentRatio,
            viewMode: 2,
            autoCropArea: 0.95,
            responsive: true,
            guides: true
        });
    }

    fotoInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
            currentTargetExistingIdx = null;
            currentTargetExistingPath = null;
            var file = this.files[0];
            if (file.type.match(/^image\//)) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                    cropperSourceImg.src = evt.target.result;
                    if (btnOpenCropper) {
                        btnOpenCropper.classList.remove('d-none');
                        btnOpenCropper.innerHTML = '<i class="bi bi-crop me-1"></i> Sesuaikan Foto Baru Dipilih';
                    }
                    bsModal.show();
                };
                reader.readAsDataURL(file);
            }
        }
    });

    btnOpenCropper?.addEventListener('click', function() {
        if (cropperSourceImg.src) bsModal.show();
    });

    // Sesuaikan Foto yang Sudah Terupload / Tersimpan
    document.querySelectorAll('.btn-crop-existing-s4').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.getAttribute('data-photo-url');
            var path = this.getAttribute('data-photo-path');
            var idx = this.getAttribute('data-idx');
            currentTargetExistingIdx = idx;
            currentTargetExistingPath = path;

            cropperSourceImg.src = url;
            if (btnOpenCropper) {
                btnOpenCropper.classList.remove('d-none');
                btnOpenCropper.innerHTML = '<i class="bi bi-crop me-1"></i> Sesuaikan Foto Terpilih';
            }
            bsModal.show();
        });
    });

    // Visual feedback saat checkbox hapus dicentang manual
    document.querySelectorAll('.check-del-s4').forEach(function(chk) {
        chk.addEventListener('change', function() {
            var card = document.getElementById('cardExistingS4_' + this.dataset.idx);
            if (card) {
                if (this.checked) {
                    card.style.opacity = '0.45';
                    card.style.borderColor = '#EF4444';
                } else {
                    card.style.opacity = '1';
                    card.style.borderColor = '#CBD5E1';
                }
            }
        });
    });

    modalEl.addEventListener('shown.bs.modal', function() { initCropper(); });
    modalEl.addEventListener('hidden.bs.modal', function() { if (cropper) { cropper.destroy(); cropper = null; } });

    document.getElementById('btnRotateLeftS4')?.addEventListener('click', function() { if (cropper) cropper.rotate(-90); });
    document.getElementById('btnRotateRightS4')?.addEventListener('click', function() { if (cropper) cropper.rotate(90); });
    document.getElementById('btnFlipHS4')?.addEventListener('click', function() { if (cropper) { scaleX = -scaleX; cropper.scaleX(scaleX); } });
    document.getElementById('btnFlipVS4')?.addEventListener('click', function() { if (cropper) { scaleY = -scaleY; cropper.scaleY(scaleY); } });
    document.getElementById('btnZoomInS4')?.addEventListener('click', function() { if (cropper) cropper.zoom(0.1); });
    document.getElementById('btnZoomOutS4')?.addEventListener('click', function() { if (cropper) cropper.zoom(-0.1); });
    document.getElementById('btnResetCropS4')?.addEventListener('click', function() { if (cropper) { cropper.reset(); scaleX = 1; scaleY = 1; } });

    document.querySelectorAll('.ratio-btn-s4').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn-s4').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var val = parseFloat(this.getAttribute('data-ratio'));
            currentRatio = isNaN(val) ? NaN : val;
            if (cropper) cropper.setAspectRatio(currentRatio);
        });
    });

    btnApplyCrop?.addEventListener('click', function() {
        if (!cropper) return;
        var canvas = cropper.getCroppedCanvas({ maxWidth: 1920, maxHeight: 1200, imageSmoothingQuality: 'high' });
        if (canvas) {
            var dataUrl = canvas.toDataURL('image/webp', 0.88);
            var col = document.createElement('div');
            col.className = 'col-6 col-sm-4 col-md-3';

            var linkedIdx = currentTargetExistingIdx;
            var existingNote = '';

            if (linkedIdx !== null) {
                existingNote = '<div class="px-1 py-1 bg-warning-subtle text-dark border-top small text-truncate" style="font-size:0.68rem;font-weight:600;"><i class="bi bi-arrow-repeat me-1 text-warning"></i>Menggantikan foto tersimpan</div>';

                var chkDel = document.getElementById('delPhoto_' + linkedIdx);
                var cardOld = document.getElementById('cardExistingS4_' + linkedIdx);
                if (chkDel) chkDel.checked = true;
                if (cardOld) {
                    cardOld.style.opacity = '0.45';
                    cardOld.style.borderColor = '#EF4444';
                }
            }

            col.innerHTML = '<div class="card p-1 border position-relative shadow-sm">' +
                '<img src="' + dataUrl + '" style="height:100px;object-fit:cover;border-radius:4px;">' +
                existingNote +
                '<input type="hidden" name="cropped_photos[]" value="' + dataUrl + '">' +
                '<button type="button" class="btn btn-xs btn-danger position-absolute top-0 end-0 m-1 p-1" style="line-height:1;border-radius:50%;" title="Batalkan foto hasil crop ini">&times;</button>' +
                '</div>';

            var btnDel = col.querySelector('button');
            btnDel.addEventListener('click', function() {
                if (linkedIdx !== null) {
                    var chkDel = document.getElementById('delPhoto_' + linkedIdx);
                    var cardOld = document.getElementById('cardExistingS4_' + linkedIdx);
                    if (chkDel) chkDel.checked = false;
                    if (cardOld) {
                        cardOld.style.opacity = '1';
                        cardOld.style.borderColor = '#CBD5E1';
                    }
                }
                col.remove();
                if (containerGrid.children.length === 0) {
                    containerWrap.classList.add('d-none');
                }
            });

            containerGrid.appendChild(col);
            containerWrap.classList.remove('d-none');

            // Kosongkan file input asli jika berasal dari pemilihan file baru
            if (linkedIdx === null) {
                fotoInput.value = '';
                if (btnOpenCropper) btnOpenCropper.classList.add('d-none');
            }

            bsModal.hide();
        }
    });

    // Sinkronisasi badge status pembatasan per berkas Siklus 4
    var docPrefixesS4 = [
        { prefix: 'undangan', badgeId: 'badgeUndanganStatusS4', color: '#F59E0B' },
        { prefix: 'daftar_hadir', badgeId: 'badgeDaftarHadirStatusS4', color: '#10B981' },
        { prefix: 'berita_acara', badgeId: 'badgeBeritaAcaraStatusS4', color: '#3B82F6' }
    ];

    docPrefixesS4.forEach(function(item) {
        var toggle = document.querySelector('input[name="' + item.prefix + '_is_restricted"]');
        var pagesInput = document.querySelector('input[name="' + item.prefix + '_max_preview_pages"]');
        var badge = document.getElementById(item.badgeId);

        function updateBadge() {
            if (!badge || !toggle) return;
            if (toggle.checked) {
                var pages = pagesInput ? pagesInput.value : '3';
                badge.innerText = 'Dibatasi (' + pages + ' Hal)';
                badge.style.background = item.color;
                badge.style.color = '#ffffff';
            } else {
                badge.innerText = 'Publik / Bebas';
                badge.style.background = '#E2E8F0';
                badge.style.color = '#475569';
            }
        }

        if (toggle) toggle.addEventListener('change', updateBadge);
        if (pagesInput) pagesInput.addEventListener('input', updateBadge);
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
