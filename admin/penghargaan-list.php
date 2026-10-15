<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Penghargaan';
$db = getDB();

// Handle Simpan / Tambah / Edit Item Capaian Mutu Multi-Item (Beranda)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_capaian_item') {
    $item_id   = (int)($_POST['item_id'] ?? 0);
    $badge     = trim($_POST['badge'] ?? 'Akreditasi Institusi');
    $peringkat = trim($_POST['peringkat'] ?? 'UNGGUL');
    $judul     = trim($_POST['judul'] ?? "Capaian Mutu\nUniversitas Katolik Soegijapranata");
    $nomor_sk  = trim($_POST['nomor_sk'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $btn_text  = trim($_POST['btn_text'] ?? 'Lihat Akreditasi Program Studi');
    $btn_link  = trim($_POST['btn_link'] ?? 'akreditasi-prodi.php');
    $urutan    = (int)($_POST['urutan'] ?? 1);

    $items = getCapaianMutuItems();
    $upload_dir = __DIR__ . '/../uploads/akreditasi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $existing_index = -1;
    $current_item = null;
    if ($item_id > 0) {
        foreach ($items as $idx => $it) {
            if ((int)($it['id'] ?? 0) === $item_id) {
                $existing_index = $idx;
                $current_item = $it;
                break;
            }
        }
    }

    $new_file = $current_item['file_sertifikat'] ?? '';

    // Cek hasil penyesuaian gambar cropper (base64)
    $cropped_b64 = $_POST['cropped_image_data'] ?? '';
    if (!empty($cropped_b64) && strpos($cropped_b64, 'data:image') === 0) {
        $b64_parts = explode(',', $cropped_b64);
        if (count($b64_parts) === 2) {
            $img_data = base64_decode($b64_parts[1]);
            $temp_img = $upload_dir . 'temp_crop_' . uniqid() . '.webp';
            if (file_put_contents($temp_img, $img_data)) {
                $uploaded_pdf = saveOrConvertToPdf($temp_img, $upload_dir, 'akred_cert_', 'sertifikat.webp');
                @unlink($temp_img);
                if ($uploaded_pdf) {
                    if ($new_file && $new_file !== $uploaded_pdf && strpos($new_file, '2023_Sertifikat') === false) {
                        deleteUploadedFile($new_file, 'akreditasi');
                    }
                    $new_file = $uploaded_pdf;
                    getOrGeneratePdfPreview($new_file, 'akreditasi');
                }
            }
        }
    } elseif (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sertifikat']['tmp_name'];
        $name = $_FILES['file_sertifikat']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $uploaded_pdf = saveOrConvertToPdf($tmp, $upload_dir, 'akred_cert_', $name);
            if ($uploaded_pdf) {
                if ($new_file && $new_file !== $uploaded_pdf && strpos($new_file, '2023_Sertifikat') === false) {
                    deleteUploadedFile($new_file, 'akreditasi');
                }
                $new_file = $uploaded_pdf;
                getOrGeneratePdfPreview($new_file, 'akreditasi');
            }
        }
    }

    if ($existing_index >= 0) {
        $items[$existing_index] = [
            'id'              => $item_id,
            'badge'           => $badge,
            'judul'           => $judul,
            'deskripsi'       => $deskripsi,
            'peringkat'       => $peringkat,
            'nomor_sk'        => $nomor_sk,
            'file_sertifikat' => $new_file,
            'btn_text'        => $btn_text,
            'btn_link'        => $btn_link,
            'urutan'          => $urutan
        ];
    } else {
        $max_id = 0;
        foreach ($items as $it) {
            $max_id = max($max_id, (int)($it['id'] ?? 0));
        }
        $new_id = $max_id + 1;
        $items[] = [
            'id'              => $new_id,
            'badge'           => $badge,
            'judul'           => $judul,
            'deskripsi'       => $deskripsi,
            'peringkat'       => $peringkat,
            'nomor_sk'        => $nomor_sk,
            'file_sertifikat' => $new_file,
            'btn_text'        => $btn_text,
            'btn_link'        => $btn_link,
            'urutan'          => $urutan ?: (count($items) + 1)
        ];
    }

    // Urutkan item berdasarkan urutan lalu id
    usort($items, function($a, $b) {
        $ua = (int)($a['urutan'] ?? 1);
        $ub = (int)($b['urutan'] ?? 1);
        if ($ua === $ub) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        }
        return $ua <=> $ub;
    });

    saveCapaianMutuItems($items);
    $_SESSION['flash'] = 'Data Sertifikat Capaian Mutu berhasil disimpan.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle Hapus Item Capaian Mutu
if (isset($_GET['delete_capaian']) && is_numeric($_GET['delete_capaian'])) {
    $del_id = (int)$_GET['delete_capaian'];
    $items = getCapaianMutuItems();
    if (count($items) <= 1) {
        $_SESSION['flash_error'] = 'Minimal harus ada 1 sertifikat capaian mutu untuk ditampilkan di Beranda.';
    } else {
        $filtered = [];
        foreach ($items as $it) {
            if ((int)($it['id'] ?? 0) === $del_id) {
                if (!empty($it['file_sertifikat']) && strpos($it['file_sertifikat'], '2023_Sertifikat') === false) {
                    deleteUploadedFile($it['file_sertifikat'], 'akreditasi');
                }
            } else {
                $filtered[] = $it;
            }
        }
        saveCapaianMutuItems($filtered);
        $_SESSION['flash'] = 'Sertifikat capaian mutu berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Fallback legacy update capaian mutu & akreditasi institusi update (Beranda)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_capaian_mutu') {
    $akred_badge     = trim($_POST['akred_institusi_badge'] ?? 'Akreditasi Institusi');
    $akred_judul     = trim($_POST['akred_institusi_judul'] ?? "Capaian Mutu\nUniversitas Katolik Soegijapranata");
    $akred_peringkat = trim($_POST['akred_institusi_peringkat'] ?? 'UNGGUL');
    $akred_sk        = trim($_POST['akred_institusi_sk'] ?? '');
    $akred_teks      = trim($_POST['akred_institusi_teks'] ?? '');

    $upload_dir = __DIR__ . '/../uploads/akreditasi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $items = getCapaianMutuItems();
    $current_file = $items[0]['file_sertifikat'] ?? getPengaturan('akred_institusi_file', '');

    if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sertifikat']['tmp_name'];
        $name = $_FILES['file_sertifikat']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $new_name = saveOrConvertToPdf($tmp, $upload_dir, 'akred_institusi_cert_', $name);
            if ($new_name) {
                if ($current_file && $current_file !== $new_name && strpos($current_file, '2023_Sertifikat') === false) {
                    deleteUploadedFile($current_file, 'akreditasi');
                }
                $current_file = $new_name;
                getOrGeneratePdfPreview($new_name, 'akreditasi');
            }
        }
    }

    if (!empty($items)) {
        $items[0]['badge']           = $akred_badge;
        $items[0]['judul']           = $akred_judul;
        $items[0]['peringkat']       = $akred_peringkat;
        $items[0]['nomor_sk']        = $akred_sk;
        $items[0]['deskripsi']       = $akred_teks;
        $items[0]['file_sertifikat'] = $current_file;
    } else {
        $items[] = [
            'id'              => 1,
            'badge'           => $akred_badge,
            'judul'           => $akred_judul,
            'peringkat'       => $akred_peringkat,
            'nomor_sk'        => $akred_sk,
            'deskripsi'       => $akred_teks,
            'file_sertifikat' => $current_file,
            'btn_text'        => 'Lihat Akreditasi Program Studi',
            'btn_link'        => 'akreditasi-prodi.php',
            'urutan'          => 1
        ];
    }

    saveCapaianMutuItems($items);
    $_SESSION['flash'] = 'Data Capaian Mutu & Sertifikat Akreditasi Beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle statistik capaian update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stats') {
    $stat_tahun      = trim($_POST['stat_tahun'] ?? '40');
    $stat_prodi      = trim($_POST['stat_prodi'] ?? '');
    $stat_akreditasi = trim($_POST['stat_akreditasi'] ?? '');
    $stat_dokumen    = trim($_POST['stat_dokumen'] ?? '');

    setPengaturan('stat_tahun', $stat_tahun);
    setPengaturan('stat_prodi', $stat_prodi);
    setPengaturan('stat_akreditasi_persen', $stat_akreditasi);
    setPengaturan('stat_dokumen', $stat_dokumen);

    $_SESSION['flash'] = 'Statistik capaian beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle setting layout update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_layout') {
    $layout = $_POST['layout_penghargaan'] === 'grid' ? 'grid' : 'slider';
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('layout_penghargaan', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$layout, $layout]);
    $_SESSION['flash'] = 'Layout beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $db->prepare("SELECT file_path FROM penghargaan WHERE id = ?");
    $row->execute([$id]);
    $row = $row->fetch();
    if ($row && !empty($row['file_path'])) {
        deleteUploadedFile($row['file_path'], 'penghargaan');
    }
    $db->prepare("DELETE FROM penghargaan WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Penghargaan berhasil dihapus.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

$peng_list = $db->query("SELECT * FROM penghargaan ORDER BY tahun DESC, id DESC")->fetchAll();
$current_layout = getPengaturan('layout_penghargaan', 'slider');

$actual_doc_count     = (int)$db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
$actual_prodi_count   = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi")->fetchColumn();
$count_terakreditasi  = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi WHERE peringkat IS NOT NULL AND TRIM(peringkat) != '' AND LOWER(peringkat) NOT LIKE '%belum%'")->fetchColumn();
if ($actual_prodi_count > 0) {
    $actual_akred_pct = ($count_terakreditasi >= $actual_prodi_count) ? 100 : round(($count_terakreditasi / $actual_prodi_count) * 100);
} else {
    $actual_akred_pct = 100;
}

$stat_tahun           = getPengaturan('stat_tahun', '40');
$stat_prodi           = getPengaturan('stat_prodi', '');
$stat_akreditasi      = getPengaturan('stat_akreditasi_persen', '');
if ($stat_akreditasi === '') {
    $old_akr = getPengaturan('stat_akreditasi', '');
    if (is_numeric($old_akr)) {
        $stat_akreditasi = $old_akr;
    }
}
$stat_dokumen         = getPengaturan('stat_dokumen', '');

// Data Capaian Mutu & Akreditasi Beranda (Multi-Item)
$capaian_items = getCapaianMutuItems();

// Daftar Halaman Sistem & Dinamis untuk Dropdown Tautan Capaian
$available_pages = [];
try {
    $available_pages = $db->query("SELECT id, judul, slug FROM pages WHERE status = 'publish' ORDER BY judul ASC")->fetchAll();
} catch (Exception $e) {}

$system_link_options = [
    'Halaman Akreditasi & Capaian' => [
        'akreditasi-institusi.php' => 'Akreditasi Institusi',
        'akreditasi-prodi.php'     => 'Akreditasi Program Studi',
        'lembaga-akreditasi.php'   => 'Lembaga Akreditasi (BAN-PT & LAM)',
        'pemeringkatan.php'        => 'Pemeringkatan & Prestasi'
    ],
    'Sistem Penjaminan Mutu & Audit' => [
        'spmi.php'                 => 'Sistem Penjaminan Mutu Internal (SPMI)',
        'spmi.php?view=dokumen'    => 'Dokumen Kebijakan & Manual SPMI',
        'ami.php'                  => 'Audit Mutu Internal (AMI)',
        'siklus-ami.php'           => 'Siklus & Alur AMI'
    ],
    'Profil, Layanan & Informasi' => [
        'profil.php'               => 'Profil Lembaga Penjaminan Mutu',
        'profil.php?view=visi-misi'=> 'Visi, Misi & Tujuan LPM',
        'profil.php?view=struktur' => 'Struktur Organisasi LPM',
        'berita.php'               => 'Berita & Pengumuman',
        'galeri.php'               => 'Galeri Kegiatan',
        'layanan.php'              => 'Layanan Mutu LPM',
        'pelatihan-eksternal.php'  => 'Pelatihan & Bimbingan Teknis',
        'survei-kepuasan.php'      => 'Survei Kepuasan Stakeholder',
        'kalender-mutu.php'        => 'Kalender Agenda Mutu',
        'glosarium.php'            => 'Glosarium Istilah Mutu',
        'faq.php'                  => 'Tanya Jawab (FAQ)',
        'kontak.php'               => 'Kontak & Aduan'
    ]
];

if (!empty($available_pages)) {
    $page_opts = [];
    foreach ($available_pages as $ap) {
        $page_opts['page.php?slug=' . $ap['slug']] = 'Halaman: ' . $ap['judul'];
    }
    $system_link_options['Halaman Dinamis (Pages Web)'] = $page_opts;
}

$flash = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert-lpm alert-danger mb-4" style="background:#FEF2F2;border:1px solid #FCA5A5;color:#991B1B;">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 8.25h.008v.008H12v-.008Z" />
    </svg>
    <?= e($flash_error) ?>
</div>
<?php endif; ?>

<!-- Kelola Capaian Mutu & Sertifikat Akreditasi Beranda (Multi-Item) -->
<div class="admin-table-wrap mb-4" style="padding: 1.5rem 1.75rem; border: 1.5px solid #E2E8F0; background: #FFFFFF; border-radius: 12px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3" style="border-bottom: 1px solid #F1F5F9;">
        <div>
            <h5 class="mb-1" style="font-weight: 700; color: var(--navy); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--purple)" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                </svg>
                Capaian Mutu &amp; Sertifikat Akreditasi (Beranda)
                <span class="badge bg-purple-subtle text-purple border border-purple-subtle ms-1" style="font-size:0.75rem; font-weight:700;">
                    <?= count($capaian_items) ?> Sertifikat
                </span>
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Kelola daftar sertifikat capaian mutu yang tampil di Beranda. Bila terdapat lebih dari 1 sertifikat, di Beranda kartu sertifikat akan bergeser otomatis (slide carousel) dan teks di sisi kirinya berganti selaras dengan sertifikat yang aktif.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" id="btnTambahCapaian" style="font-size: 0.82rem; border-radius: 6px; background:var(--purple); border-color:var(--purple); font-weight:600;">
                <i class="bi bi-plus-lg"></i> Tambah Sertifikat Capaian
            </button>
            <a href="<?= SITE_URL ?>/admin/akreditasi-institusi.php" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem; border-radius: 6px;">
                <i class="bi bi-gear-fill me-1"></i> Detail Akreditasi Institusi
            </a>
            <a href="<?= SITE_URL ?>/index.php#akreditasi" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem; border-radius: 6px;">
                Lihat di Beranda &rarr;
            </a>
        </div>
    </div>

    <!-- Alert status slide -->
    <?php if (count($capaian_items) > 1): ?>
        <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2" style="border-radius:8px; font-size:0.83rem; background:#EFF6FF; border-color:#BFDBFE; color:#1E40AF;">
            <i class="bi bi-arrow-repeat fs-5 text-primary"></i>
            <div>
                <strong>Auto-Slide Beranda Aktif (<?= count($capaian_items) ?> Sertifikat):</strong> Berkas sertifikat di sisi kanan bergeser otomatis setiap 5 detik, dan seluruh informasi teks di sisi kiri (Badge, Judul, Peringkat, SK, Deskripsi, Tombol) berganti otomatis mengikuti sertifikat aktif.
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary py-2 px-3 small mb-3 d-flex align-items-center gap-2" style="border-radius:8px; font-size:0.83rem; background:#F8FAFC; border-color:#E2E8F0; color:#475569;">
            <i class="bi bi-info-circle fs-5 text-secondary"></i>
            <div>
                <strong>Mode Tampilan Tunggal (1 Sertifikat):</strong> Saat ini hanya ada 1 sertifikat capaian. Klik <strong>Tambah Sertifikat Capaian</strong> di atas bila ingin menambahkan sertifikat lain agar fitur rotasi carousel otomatis aktif di Beranda.
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabel Daftar Sertifikat Capaian Mutu -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC; border-bottom:1.5px solid #E2E8F0;">
                <tr>
                    <th width="45" class="text-center">#</th>
                    <th width="110">Sertifikat</th>
                    <th>Informasi Capaian (Teks Beranda)</th>
                    <th width="190">Tombol Aksi</th>
                    <th width="120" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($capaian_items as $c_idx => $c_item): 
                    $c_file = $c_item['file_sertifikat'] ?? '';
                    $c_prev = $c_file ? getOrGeneratePdfPreview($c_file, 'akreditasi') : '';
                ?>
                <tr>
                    <td class="text-center fw-bold text-muted"><?= $c_item['urutan'] ?? ($c_idx + 1) ?></td>
                    <td>
                        <?php if ($c_file && file_exists(__DIR__ . '/../uploads/akreditasi/' . $c_file)): ?>
                            <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= e($c_file) ?>" target="_blank" title="Buka berkas sertifikat">
                                <div style="width:90px;height:65px;border-radius:8px;overflow:hidden;border:1px solid #CBD5E1;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.06);position:relative;">
                                    <?php if ($c_prev && file_exists(__DIR__ . '/../uploads/akreditasi/' . $c_prev)): ?>
                                        <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($c_prev) ?>" alt="Sertifikat" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-3"></i>
                                    <?php endif; ?>
                                    <span style="position:absolute;bottom:2px;right:2px;background:rgba(15,23,42,0.85);color:#fff;font-size:0.55rem;font-weight:700;padding:1px 3px;border-radius:3px;">
                                        <?= strtoupper(pathinfo($c_file, PATHINFO_EXTENSION)) ?>
                                    </span>
                                </div>
                            </a>
                        <?php else: ?>
                            <div style="width:90px;height:65px;border-radius:8px;background:#F1F5F9;border:1px dashed #CBD5E1;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:0.7rem;text-align:center;padding:4px;">
                                Tanpa Berkas
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle" style="font-size:0.72rem;font-weight:600;">
                                <?= e($c_item['badge'] ?? 'Akreditasi Institusi') ?>
                            </span>
                            <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:0.72rem;font-weight:800;">
                                <?= e($c_item['peringkat'] ?? 'UNGGUL') ?>
                            </span>
                        </div>
                        <div class="fw-bold text-navy" style="font-size:0.92rem;line-height:1.35;"><?= nl2br(e($c_item['judul'] ?? '')) ?></div>
                        <?php if (!empty($c_item['nomor_sk'])): ?>
                            <div class="text-muted small mt-1"><i class="bi bi-file-text me-1 text-primary"></i><?= e($c_item['nomor_sk']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c_item['deskripsi'])): ?>
                            <div class="text-muted small mt-1 text-truncate" style="max-width:480px;" title="<?= e($c_item['deskripsi']) ?>">
                                <?= e(mb_strimwidth($c_item['deskripsi'], 0, 110, '...')) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold small text-navy mb-1"><?= e($c_item['btn_text'] ?? 'Lihat Akreditasi Program Studi') ?></div>
                        <span class="badge bg-light text-muted border text-truncate d-inline-block" style="max-width:180px;font-size:0.72rem;">
                            <?= e($c_item['btn_link'] ?? 'akreditasi-prodi.php') ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-capaian" 
                                    data-id="<?= (int)($c_item['id'] ?? 0) ?>"
                                    data-badge="<?= e($c_item['badge'] ?? '') ?>"
                                    data-peringkat="<?= e($c_item['peringkat'] ?? '') ?>"
                                    data-judul="<?= e($c_item['judul'] ?? '') ?>"
                                    data-sk="<?= e($c_item['nomor_sk'] ?? '') ?>"
                                    data-deskripsi="<?= e($c_item['deskripsi'] ?? '') ?>"
                                    data-btn-text="<?= e($c_item['btn_text'] ?? 'Lihat Akreditasi Program Studi') ?>"
                                    data-btn-link="<?= e($c_item['btn_link'] ?? 'akreditasi-prodi.php') ?>"
                                    data-urutan="<?= (int)($c_item['urutan'] ?? ($c_idx + 1)) ?>"
                                    data-file="<?= e($c_file) ?>"
                                    data-preview="<?= e($c_prev) ?>"
                                    title="Edit Sertifikat Ini" style="border-radius:6px;padding:4px 8px;">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <?php if (count($capaian_items) > 1): ?>
                                <a href="<?= SITE_URL ?>/admin/penghargaan-list.php?delete_capaian=<?= (int)($c_item['id'] ?? 0) ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus sertifikat capaian ini dari Beranda?')" 
                                   title="Hapus Sertifikat Ini" style="border-radius:6px;padding:4px 8px;">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-light border text-muted" disabled title="Minimal harus ada 1 sertifikat" style="border-radius:6px;padding:4px 8px;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Form Kelola Statistik Capaian Beranda -->
<div class="admin-table-wrap mb-4" style="padding: 1.5rem 1.75rem; border: 1.5px solid #E2E8F0; background: #FFFFFF; border-radius: 12px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3" style="border-bottom: 1px solid #F1F5F9;">
        <div>
            <h5 class="mb-1" style="font-weight: 700; color: var(--navy); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--gold)" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                Statistik Capaian Beranda
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Sesuaikan 4 angka counter capaian yang tampil mencolok di Stats Bar halaman depan (Beranda).
            </p>
        </div>
        <a href="<?= SITE_URL ?>/index.php#capaian" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem; border-radius: 6px;">
            Lihat di Beranda &rarr;
        </a>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="update_stats">
        <div class="row g-3">
            <!-- 1. Tahun Berdiri -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Tahun Berdiri (+)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_tahun" class="form-control" value="<?= e($stat_tahun) ?>" required style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">+</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Label: Tahun Berdiri</small>
            </div>

            <!-- 2. Program Studi -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Program Studi (+)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_prodi" class="form-control" placeholder="Auto (<?= $actual_prodi_count ?>)" value="<?= e($stat_prodi) ?>" style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">+</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_prodi_count ?>)</small>
            </div>

            <!-- 3. Prodi Terakreditasi -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Prodi Terakreditasi (%)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_akreditasi" class="form-control" placeholder="Auto (<?= $actual_akred_pct ?>%)" value="<?= e($stat_akreditasi) ?>" min="0" max="100" style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">%</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_akred_pct ?>%)</small>
            </div>

            <!-- 4. Dokumen Mutu -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Dokumen Mutu (+)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_dokumen" class="form-control" placeholder="Auto (<?= $actual_doc_count ?>)" value="<?= e($stat_dokumen) ?>" style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">+</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_doc_count ?>)</small>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3 pt-2" style="border-top: 1px dashed #E2E8F0;">
            <button type="submit" class="btn-save" style="margin-top: 0; padding: 0.55rem 1.4rem; font-size: 0.875rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Simpan Statistik Capaian
            </button>
        </div>
    </form>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Penghargaan & Prestasi (<?= count($peng_list) ?>)</div>
        <a href="penghargaan-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Penghargaan
        </a>
    </div>

    <?php if (empty($peng_list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada data penghargaan. <a href="penghargaan-form.php" class="text-purple font-weight-600">Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="40" class="text-center">#</th>
                    <th width="90">Sampul</th>
                    <th>Judul Penghargaan</th>
                    <th width="220">Instansi / Lembaga</th>
                    <th width="90" class="text-center">Tahun</th>
                    <th width="130">Berkas Asli</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($peng_list as $i => $d): 
                    $p_preview = getOrGeneratePdfPreview($d['file_path'] ?? '', 'penghargaan');
                ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;text-align:center;"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($p_preview && file_exists(__DIR__ . '/../uploads/penghargaan/' . $p_preview)): ?>
                            <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($d['file_path']) ?>" target="_blank" title="Buka berkas penuh">
                                <div style="width:72px;height:52px;border-radius:8px;overflow:hidden;border:1px solid #CBD5E1;background:#ffffff;box-shadow:0 2px 6px rgba(0,0,0,0.06);position:relative;display:flex;align-items:center;justify-content:center;">
                                    <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p_preview) ?>" alt="Sampul" style="width:100%;height:100%;object-fit:cover;">
                                    <?php if ($d['is_pdf']): ?>
                                        <span style="position:absolute;bottom:2px;right:2px;background:rgba(220,38,38,0.92);color:#fff;font-size:0.58rem;font-weight:800;padding:1px 4px;border-radius:3px;line-height:1.1;">PDF</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php else: ?>
                            <div style="width:72px;height:52px;border-radius:8px;background:#F1F5F9;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:0.68rem;text-align:center;">
                                No Sampul
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--navy);font-size:0.92rem;line-height:1.4;"><?= e($d['judul']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;color:var(--text-muted);"><?= e($d['instansi'] ?: '-') ?></div>
                    </td>
                    <td class="text-center">
                        <span style="background-color: var(--purple, #6A1B9A); color: #FFFFFF; padding: 0.25rem 0.65rem; border-radius: 12px; font-weight: 700; font-size: 0.8rem; display: inline-block; letter-spacing: 0.5px;">
                            <?= e($d['tahun']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($d['file_path'] && file_exists(__DIR__ . '/../uploads/penghargaan/' . $d['file_path'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($d['file_path']) ?>" target="_blank" class="text-decoration-none d-inline-flex align-items-center gap-1">
                                <?php if ($d['is_pdf']): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size:0.75rem;">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Buka PDF
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.75rem;">
                                        <i class="bi bi-file-earmark-image-fill me-1"></i> Buka Gambar
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span style="font-size:0.78rem;color:var(--text-light);">File tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="penghargaan-form.php?id=<?= $d['id'] ?>" class="btn-action btn-edit" style="text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit
                            </a>
                            <a href="penghargaan-list.php?delete=<?= $d['id'] ?>" class="btn-action btn-delete" style="text-decoration:none;" onclick="return confirm('Yakin hapus penghargaan ini?')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah / Edit Sertifikat Capaian Mutu -->
<div class="modal fade" id="modalCapaianItem" tabindex="-1" aria-labelledby="modalCapaianItemLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0; padding:1.25rem 1.75rem;">
                <h5 class="modal-title fw-bold text-navy" id="modalCapaianItemLabel" style="font-size:1.15rem;">
                    <i class="bi bi-award-fill text-warning me-2"></i>
                    <span>Tambah Sertifikat Capaian Mutu</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formCapaianItem">
                <input type="hidden" name="action" value="save_capaian_item">
                <input type="hidden" name="item_id" id="capaian_item_id" value="0">
                <input type="hidden" name="cropped_image_data" id="capaian_cropped_data" value="">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Badge Kategori</label>
                            <input type="text" name="badge" id="capaian_badge" class="form-control" placeholder="Contoh: Akreditasi Institusi" value="Akreditasi Institusi" style="border-radius:8px;">
                        </div>
                        <div class="col-md-4 col-8">
                            <label class="form-label fw-semibold text-secondary small mb-1">Peringkat / Predikat <span class="text-danger">*</span></label>
                            <input type="text" name="peringkat" id="capaian_peringkat" class="form-control fw-bold" placeholder="Contoh: UNGGUL" value="UNGGUL" required style="border-radius:8px; color:var(--purple);">
                        </div>
                        <div class="col-md-2 col-4">
                            <label class="form-label fw-semibold text-secondary small mb-1">Urutan</label>
                            <input type="number" name="urutan" id="capaian_urutan" class="form-control text-center" min="1" value="1" style="border-radius:8px;">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small mb-1">Judul Bagian Capaian Mutu <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="capaian_judul" class="form-control" placeholder="Contoh: Capaian Mutu Universitas Katolik Soegijapranata" value="Capaian Mutu Universitas Katolik Soegijapranata" required style="border-radius:8px;">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small mb-1">Nomor SK / Sertifikat</label>
                            <input type="text" name="nomor_sk" id="capaian_sk" class="form-control" placeholder="Contoh: No. 263/SK/BAN-PT/Ak.KP/PT/IV/2023" style="border-radius:8px;">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small mb-1">Teks Komitmen / Deskripsi Capaian Mutu</label>
                            <textarea name="deskripsi" id="capaian_deskripsi" rows="3" class="form-control" placeholder="Tuliskan komitmen mutu atau ringkasan capaian akreditasi..." style="border-radius:8px;">Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Teks Tombol Aksi</label>
                            <input type="text" name="btn_text" id="capaian_btn_text" class="form-control" placeholder="Lihat Akreditasi Program Studi" value="Lihat Akreditasi Program Studi" style="border-radius:8px;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Tautan / Link Tombol</label>
                            <select id="capaian_btn_link_select" class="form-select" style="border-radius:8px;">
                                <?php foreach ($system_link_options as $group_label => $opts): ?>
                                    <optgroup label="<?= e($group_label) ?>">
                                        <?php foreach ($opts as $val => $lbl): ?>
                                            <option value="<?= e($val) ?>"><?= e($lbl) ?> (<?= e($val) ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                                <optgroup label="Tautan Khusus">
                                    <option value="custom">-- Ketik Tautan Manual / URL Kustom... --</option>
                                </optgroup>
                            </select>
                            <div id="capaian_custom_link_wrap" class="mt-2 d-none">
                                <input type="text" name="btn_link" id="capaian_btn_link" class="form-control" placeholder="Contoh: akreditasi-prodi.php atau https://..." value="akreditasi-prodi.php" style="border-radius:8px;">
                                <small class="text-muted" style="font-size:0.75rem;">Ketik URL internal atau tautan eksternal yang dituju.</small>
                            </div>
                        </div>

                        <!-- Berkas Sertifikat & Cropper Section -->
                        <div class="col-12 mt-3 pt-3 border-top">
                            <label class="form-label fw-semibold text-navy small mb-1 d-flex align-items-center justify-content-between">
                                <span>Berkas Sertifikat (.PDF / Gambar)</span>
                                <span class="badge bg-primary-subtle text-primary border" style="font-size:0.7rem;">Otomatis PDF</span>
                            </label>

                            <!-- Preview Berkas Saat Ini -->
                            <div id="capaian_current_file_wrap" class="d-none mb-3 p-2 bg-light border rounded-3 align-items-center gap-3">
                                <div id="capaian_current_thumb" style="width:56px;height:56px;border-radius:6px;overflow:hidden;background:#fff;border:1px solid #CBD5E1;flex-shrink:0;display:flex;align-items:center;justify-content:center;"></div>
                                <div style="min-width:0;flex-grow:1;">
                                    <div id="capaian_current_filename" class="text-truncate fw-semibold text-navy small mb-1"></div>
                                    <a id="capaian_current_link" href="#" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:0.75rem;border-radius:4px;">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Berkas Asli
                                    </a>
                                </div>
                            </div>

                            <input type="file" name="file_sertifikat" id="capaian_file_input" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" style="border-radius:8px;">
                            <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap">
                                <small class="text-muted" style="font-size:0.75rem;">Mendukung berkas .pdf, .webp, .jpg, .png (Maks. 20 MB). Format gambar otomatis diubah ke dokumen PDF resolusi tinggi.</small>
                            </div>

                            <!-- Image Preview & Crop Button Trigger (untuk gambar) -->
                            <div id="capaian_image_crop_area" class="d-none mt-3 p-3 bg-light rounded-3 border">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div style="width:120px;height:85px;border-radius:8px;overflow:hidden;border:1px solid #CBD5E1;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.06);">
                                        <img id="capaian_preview_crop_img" src="" alt="Preview Sertifikat" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-navy small mb-1">Penyesuaian Visual Sertifikat</div>
                                        <p class="text-muted small mb-2" style="font-size:0.78rem;">Anda dapat memotong (crop), memutar (rotate), atau membalik (flip) gambar sertifikat sebelum disimpan.</p>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center gap-1" id="btnOpenCapaianCropper" style="border-radius:6px;">
                                            <i class="bi bi-crop text-warning"></i> Sesuaikan Gambar (Crop &amp; Rotate)
                                        </button>
                                        <span id="capaian_crop_status_badge" class="badge bg-success-subtle text-success border border-success ms-2 d-none" style="font-size:0.75rem;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Telah disesuaikan
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between" style="background:#F8FAFC; border-top:1px solid #E2E8F0; padding:1rem 1.75rem;">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" style="background:var(--purple); border-color:var(--purple); border-radius:8px;">
                        <i class="bi bi-check-lg fs-5"></i> Simpan Sertifikat Capaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cropper Penyesuaian Gambar Sertifikat -->
<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0; padding:1.25rem 1.75rem;">
                <h5 class="modal-title fw-bold text-navy" id="cropperModalLabel" style="font-size:1.15rem;">
                    <i class="bi bi-crop text-primary me-2"></i> Sesuaikan Gambar Sertifikat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background:#F1F5F9;">
                <div style="width:100%; height:380px; background:#0F172A; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                    <img id="cropperTargetImg" src="" alt="Crop Target" style="max-width:100%; max-height:100%;">
                </div>

                <!-- Kontrol Toolbar -->
                <div class="p-3 bg-white rounded-3 border mt-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3 col-6 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-2" id="btnFlipH" title="Balik Horizontal">
                                <i class="bi bi-arrows-expand-vertical"></i> ⇆ Flip H
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-2" id="btnFlipV" title="Balik Vertikal">
                                <i class="bi bi-arrows-collapse-vertical"></i> ⇅ Flip V
                            </button>
                        </div>
                        <div class="col-md-3 col-6 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-2" id="btnRotateLeft" title="Putar Kiri 90°">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-2" id="btnRotateRight" title="Putar Kanan 90°">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-md-3 col-6 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-2" id="btnZoomIn" title="Perbesar">
                                <i class="bi bi-zoom-in"></i> Zoom +
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-2" id="btnZoomOut" title="Perkecil">
                                <i class="bi bi-zoom-out"></i> Zoom -
                            </button>
                        </div>
                        <div class="col-md-3 col-6">
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 fw-bold py-2" id="btnResetCrop" title="Reset Penyesuaian">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Rasio Pilihan -->
                    <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top flex-wrap">
                        <span class="text-muted small fw-bold text-uppercase" style="font-size:0.75rem; letter-spacing:0.5px;">Rasio Aspek:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn fw-semibold" data-ratio="1.414213">A4 Landscape (1.41:1)</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="1.333333">4:3 Sertifikat</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="1.777778">16:9 Landscape</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="NaN">Bebas (Free)</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#F8FAFC; border-top:1px solid #E2E8F0; padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" id="btnCancelCrop" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCrop" style="background:var(--navy); border-color:var(--navy); border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalCapaianEl = document.getElementById('modalCapaianItem');
    const bsModalCapaian = modalCapaianEl ? new bootstrap.Modal(modalCapaianEl) : null;

    const modalCropperEl = document.getElementById('cropperModal');
    const bsModalCropper = modalCropperEl ? new bootstrap.Modal(modalCropperEl) : null;

    const btnTambah = document.getElementById('btnTambahCapaian');
    const modalTitle = document.getElementById('modalCapaianItemLabel');
    const formCapaian = document.getElementById('formCapaianItem');

    const inpId = document.getElementById('capaian_item_id');
    const inpBadge = document.getElementById('capaian_badge');
    const inpPeringkat = document.getElementById('capaian_peringkat');
    const inpUrutan = document.getElementById('capaian_urutan');
    const inpJudul = document.getElementById('capaian_judul');
    const inpSK = document.getElementById('capaian_sk');
    const inpDeskripsi = document.getElementById('capaian_deskripsi');
    const inpBtnText = document.getElementById('capaian_btn_text');
    const inpBtnLink = document.getElementById('capaian_btn_link');
    const inpCropped = document.getElementById('capaian_cropped_data');

    const fileInput = document.getElementById('capaian_file_input');
    const curFileWrap = document.getElementById('capaian_current_file_wrap');
    const curThumb = document.getElementById('capaian_current_thumb');
    const curFilename = document.getElementById('capaian_current_filename');
    const curLink = document.getElementById('capaian_current_link');

    const cropArea = document.getElementById('capaian_image_crop_area');
    const previewCropImg = document.getElementById('capaian_preview_crop_img');
    const btnOpenCropper = document.getElementById('btnOpenCapaianCropper');
    const cropStatusBadge = document.getElementById('capaian_crop_status_badge');
    const cropperTargetImg = document.getElementById('cropperTargetImg');

    let cropper = null;
    let currentScaleX = 1;
    let currentScaleY = 1;
    let activeRatio = 1.414213;

    // Dropdown Tautan Halaman Sync
    const selBtnLink = document.getElementById('capaian_btn_link_select');
    const wrapCustomLink = document.getElementById('capaian_custom_link_wrap');

    function syncBtnLinkDropdown(val) {
        val = (val || '').trim();
        if (!selBtnLink || !inpBtnLink) return;

        inpBtnLink.value = val;
        let matched = false;

        for (let i = 0; i < selBtnLink.options.length; i++) {
            if (selBtnLink.options[i].value === val) {
                selBtnLink.selectedIndex = i;
                matched = true;
                break;
            }
        }

        if (matched) {
            if (wrapCustomLink) wrapCustomLink.classList.add('d-none');
        } else {
            selBtnLink.value = 'custom';
            if (wrapCustomLink) wrapCustomLink.classList.remove('d-none');
        }
    }

    if (selBtnLink) {
        selBtnLink.addEventListener('change', function() {
            if (this.value === 'custom') {
                if (wrapCustomLink) wrapCustomLink.classList.remove('d-none');
                if (inpBtnLink) {
                    inpBtnLink.focus();
                }
            } else {
                if (wrapCustomLink) wrapCustomLink.classList.add('d-none');
                if (inpBtnLink) inpBtnLink.value = this.value;
            }
        });
    }

    // Tombol Tambah Sertifikat Baru
    if (btnTambah) {
        btnTambah.addEventListener('click', function() {
            modalTitle.innerHTML = '<i class="bi bi-award-fill text-warning me-2"></i><span>Tambah Sertifikat Capaian Mutu</span>';
            formCapaian.reset();
            inpId.value = '0';
            inpCropped.value = '';
            inpBadge.value = 'Akreditasi Institusi';
            inpPeringkat.value = 'UNGGUL';
            inpJudul.value = 'Capaian Mutu Universitas Katolik Soegijapranata';
            inpDeskripsi.value = 'Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).';
            inpBtnText.value = 'Lihat Akreditasi Program Studi';
            syncBtnLinkDropdown('akreditasi-prodi.php');
            inpUrutan.value = '<?= count($capaian_items) + 1 ?>';

            curFileWrap.classList.add('d-none');
            cropArea.classList.add('d-none');
            cropStatusBadge.classList.add('d-none');
            bsModalCapaian.show();
        });
    }

    // Tombol Edit Sertifikat
    document.querySelectorAll('.btn-edit-capaian').forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i><span>Edit Sertifikat Capaian Mutu</span>';
            inpId.value = this.dataset.id || '0';
            inpBadge.value = this.dataset.badge || '';
            inpPeringkat.value = this.dataset.peringkat || '';
            inpJudul.value = this.dataset.judul || '';
            inpSK.value = this.dataset.sk || '';
            inpDeskripsi.value = this.dataset.deskripsi || '';
            inpBtnText.value = this.dataset.btnText || 'Lihat Akreditasi Program Studi';
            syncBtnLinkDropdown(this.dataset.btnLink || 'akreditasi-prodi.php');
            inpUrutan.value = this.dataset.urutan || '1';
            inpCropped.value = '';
            fileInput.value = '';
            cropStatusBadge.classList.add('d-none');

            const fName = this.dataset.file || '';
            const fPrev = this.dataset.preview || '';

            if (fName) {
                curFileWrap.classList.remove('d-none');
                curFilename.textContent = fName;
                curLink.href = '<?= SITE_URL ?>/uploads/akreditasi/' + fName;
                if (fPrev) {
                    curThumb.innerHTML = '<img src="<?= SITE_URL ?>/uploads/akreditasi/' + fPrev + '" style="width:100%;height:100%;object-fit:cover;">';
                    previewCropImg.src = '<?= SITE_URL ?>/uploads/akreditasi/' + fPrev;
                    cropArea.classList.remove('d-none');
                } else {
                    curThumb.innerHTML = '<i class="bi bi-file-earmark-pdf-fill text-danger fs-3"></i>';
                    cropArea.classList.add('d-none');
                }
            } else {
                curFileWrap.classList.add('d-none');
                cropArea.classList.add('d-none');
            }

            bsModalCapaian.show();
        });
    });

    // Handle pemilihan berkas
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const ext = file.name.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        previewCropImg.src = evt.target.result;
                        cropperTargetImg.src = evt.target.result;
                        cropArea.classList.remove('d-none');
                        cropStatusBadge.classList.add('d-none');
                        inpCropped.value = '';

                        // Tampilkan cropper langsung untuk memudahkan user
                        bsModalCapaian.hide();
                        setTimeout(() => bsModalCropper.show(), 350);
                    };
                    reader.readAsDataURL(file);
                } else {
                    cropArea.classList.add('d-none');
                    inpCropped.value = '';
                }
            }
        });
    }

    // Tombol buka cropper manual
    if (btnOpenCropper) {
        btnOpenCropper.addEventListener('click', function() {
            if (previewCropImg.src) {
                cropperTargetImg.src = previewCropImg.src;
                bsModalCapaian.hide();
                setTimeout(() => bsModalCropper.show(), 350);
            }
        });
    }

    // Inisialisasi cropper saat modal cropper tampil
    if (modalCropperEl) {
        modalCropperEl.addEventListener('shown.bs.modal', function() {
            if (cropper) cropper.destroy();
            currentScaleX = 1;
            currentScaleY = 1;
            cropper = new Cropper(cropperTargetImg, {
                aspectRatio: activeRatio,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.95,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                checkCrossOrigin: false,
            });
        });

        modalCropperEl.addEventListener('hidden.bs.modal', function() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        });
    }

    // Batal Crop -> buka kembali modal capaian item
    const btnCancelCrop = document.getElementById('btnCancelCrop');
    if (btnCancelCrop) {
        btnCancelCrop.addEventListener('click', function() {
            bsModalCropper.hide();
            setTimeout(() => bsModalCapaian.show(), 350);
        });
    }

    // Flip & Rotate
    const btnFlipH = document.getElementById('btnFlipH');
    if (btnFlipH) btnFlipH.addEventListener('click', () => { if (!cropper) return; currentScaleX = -currentScaleX; cropper.scaleX(currentScaleX); });

    const btnFlipV = document.getElementById('btnFlipV');
    if (btnFlipV) btnFlipV.addEventListener('click', () => { if (!cropper) return; currentScaleY = -currentScaleY; cropper.scaleY(currentScaleY); });

    const btnRotL = document.getElementById('btnRotateLeft');
    if (btnRotL) btnRotL.addEventListener('click', () => { if (cropper) cropper.rotate(-90); });

    const btnRotR = document.getElementById('btnRotateRight');
    if (btnRotR) btnRotR.addEventListener('click', () => { if (cropper) cropper.rotate(90); });

    const btnZIn = document.getElementById('btnZoomIn');
    if (btnZIn) btnZIn.addEventListener('click', () => { if (cropper) cropper.zoom(0.1); });

    const btnZOut = document.getElementById('btnZoomOut');
    if (btnZOut) btnZOut.addEventListener('click', () => { if (cropper) cropper.zoom(-0.1); });

    const btnReset = document.getElementById('btnResetCrop');
    if (btnReset) btnReset.addEventListener('click', () => { if (!cropper) return; cropper.reset(); currentScaleX = 1; currentScaleY = 1; });

    // Rasio Pilihan
    document.querySelectorAll('.ratio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const r = parseFloat(this.dataset.ratio);
            activeRatio = isNaN(r) ? NaN : r;
            if (cropper) cropper.setAspectRatio(activeRatio);
        });
    });

    // Terapkan Crop
    const btnApplyCrop = document.getElementById('btnApplyCrop');
    if (btnApplyCrop) {
        btnApplyCrop.addEventListener('click', function() {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({
                maxWidth: 2400,
                maxHeight: 2400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });
            if (canvas) {
                const b64 = canvas.toDataURL('image/webp', 0.92);
                inpCropped.value = b64;
                previewCropImg.src = b64;
                cropStatusBadge.classList.remove('d-none');
            }
            bsModalCropper.hide();
            setTimeout(() => bsModalCapaian.show(), 350);
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
