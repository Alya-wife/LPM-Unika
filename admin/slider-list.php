<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Banner Slider Beranda';
$db = getDB();

// Handle Save Hero Text (Teks Tetap)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_hero_text') {
    $hero_welcome_text  = trim($_POST['hero_welcome_text'] ?? 'Selamat datang di');
    $hero_title         = trim($_POST['hero_title'] ?? 'Lembaga Penjaminan Mutu Unika');
    $hero_tagline       = trim($_POST['hero_tagline'] ?? 'GROW WITH QUALITY, SERVE WITH HEART');
    $hero_desc          = trim($_POST['hero_desc'] ?? '');
    $hero_btn_text      = trim($_POST['hero_btn_text'] ?? 'Akses Dokumen SPMI');
    $hero_btn_link      = trim($_POST['hero_btn_link'] ?? 'spmi.php');
    $hero_btn2_text     = trim($_POST['hero_btn2_text'] ?? 'Profil Lembaga');
    $hero_btn2_link     = trim($_POST['hero_btn2_link'] ?? 'profil.php');

    setPengaturan('hero_welcome_text', $hero_welcome_text);
    setPengaturan('hero_title', $hero_title);
    setPengaturan('hero_tagline', $hero_tagline);
    setPengaturan('hero_desc', $hero_desc);
    setPengaturan('hero_btn_text', $hero_btn_text);
    setPengaturan('hero_btn_link', $hero_btn_link);
    setPengaturan('hero_btn2_text', $hero_btn2_text);
    setPengaturan('hero_btn2_link', $hero_btn2_link);

    $_SESSION['flash'] = 'Teks tetap banner beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/slider-list.php');
}

// Handle Delete Slide
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $db->prepare("SELECT gambar FROM hero_slides WHERE id = ?");
    $row->execute([$id]);
    $item = $row->fetch();
    if ($item && $item['gambar'] && !filter_var($item['gambar'], FILTER_VALIDATE_URL)) {
        $path = __DIR__ . '/../uploads/slides/' . $item['gambar'];
        if (file_exists($path)) @unlink($path);
    }
    $db->prepare("DELETE FROM hero_slides WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Gambar slide berhasil dihapus.';
    redirect(SITE_URL . '/admin/slider-list.php');
}

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE hero_slides SET is_active = IF(is_active=1, 0, 1) WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status aktif slide berhasil diubah.';
    redirect(SITE_URL . '/admin/slider-list.php');
}

// Data Teks Tetap
$hero_welcome_text  = getPengaturan('hero_welcome_text', 'Selamat datang di');
$hero_title         = getPengaturan('hero_title', 'Lembaga Penjaminan Mutu Unika');
$hero_tagline       = getPengaturan('hero_tagline', 'GROW WITH QUALITY, SERVE WITH HEART');
$hero_desc          = getPengaturan('hero_desc', 'Mewujudkan tata kelola penjaminan mutu pendidikan tinggi yang unggul, terencana, dan berkelanjutan.');
$hero_btn_text      = getPengaturan('hero_btn_text', 'Akses Dokumen SPMI');
$hero_btn_link      = getPengaturan('hero_btn_link', 'spmi.php');
$hero_btn2_text     = getPengaturan('hero_btn2_text', 'Profil Lembaga');
$hero_btn2_link     = getPengaturan('hero_btn2_link', 'profil.php');

$slides = $db->query("SELECT * FROM hero_slides ORDER BY urutan ASC, id ASC")->fetchAll();
$dynamic_pages = $db->query("SELECT slug, judul, kategori FROM pages ORDER BY kategori ASC, judul ASC")->fetchAll();
$flash  = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 style="font-size:1.35rem;font-weight:800;color:var(--navy);margin:0;">Kelola Banner Hero Beranda</h1>
        <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">
            Kelola teks tetap di atas banner serta gambar-gambar slide background yang berganti otomatis.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/" target="_blank" class="btn btn-outline-secondary btn-sm fw-semibold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat di Beranda
        </a>
        <a href="slider-form.php" class="btn-primary-lpm btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i> Tambah Gambar Slide
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i> <?= e($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- 1. Bagian Pengaturan Teks Tetap Banner -->
<div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div style="width:36px;height:36px;border-radius:8px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;color:#6D28D9;font-size:1.1rem;">
                <i class="bi bi-type-h1"></i>
            </div>
            <div>
                <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
                    Pengaturan Teks Banner Hero (Teks Tetap di Depan Slider)
                </h5>
                <small class="text-muted">
                    Teks ini tetap diam di layar dan tidak hilang saat gambar background di belakangnya berganti slide.
                </small>
            </div>
        </div>
        <span class="badge rounded-pill fw-semibold" style="background:#DCFCE7;color:#15803D;padding:6px 12px;font-size:0.75rem;">
            <i class="bi bi-check-circle-fill me-1"></i> Teks Beranda Aktif
        </span>
    </div>

    <form method="POST" class="p-4">
        <input type="hidden" name="action" value="save_hero_text">

        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Baris 1: Teks Pembuka / Welcome -->
                <div class="mb-3">
                    <label class="form-label small fw-bold" style="color:var(--navy);">
                        1. Teks Pembuka / Welcome Badge <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="hero_welcome_text" id="inputWelcome" class="form-control" 
                           value="<?= e($hero_welcome_text) ?>" placeholder="Contoh: Selamat datang di" required>
                    <div class="form-text">Tampil di baris pertama berupa label badge menyala di atas judul utama.</div>
                </div>

                <!-- Baris 2: Judul Utama -->
                <div class="mb-3">
                    <label class="form-label small fw-bold" style="color:var(--navy);">
                        2. Judul Utama Banner <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="hero_title" id="inputTitle" class="form-control fw-bold" 
                           value="<?= e($hero_title) ?>" placeholder="Contoh: Lembaga Penjaminan Mutu Unika" required style="font-size:1.05rem;">
                    <div class="form-text">Tampil sebagai heading utama berukuran besar di baris kedua.</div>
                </div>

                <!-- Baris 3: Slogan / Tagline Mutu -->
                <div class="mb-3">
                    <label class="form-label small fw-bold" style="color:var(--navy);">
                        3. Slogan / Tagline Mutu (Highlight Emas) <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="hero_tagline" id="inputTagline" class="form-control fw-bold text-uppercase" 
                           value="<?= e($hero_tagline) ?>" placeholder="Contoh: GROW WITH QUALITY, SERVE WITH HEART" required style="color:#B45309;letter-spacing:1px;">
                    <div class="form-text">Tampil di baris ketiga dengan bingkai emas elegan khas LPM UNIKA.</div>
                </div>

                <!-- Deskripsi Tambahan -->
                <div class="mb-3">
                    <label class="form-label small fw-bold" style="color:var(--navy);">
                        4. Kalimat Penjelasan Tambahan (Opsional)
                    </label>
                    <textarea name="hero_desc" id="inputDesc" class="form-control" rows="2" 
                              placeholder="Penjelasan ringkas mengenai komitmen mutu universitas..."><?= e($hero_desc) ?></textarea>
                </div>

                <!-- Tombol Aksi (CTA) -->
                <div class="row g-3">
                    <!-- Tombol Utama -->
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold" style="color:var(--navy);margin-bottom:0.25rem;">
                            Tombol Utama (Teks &amp; Tautan)
                        </label>
                        <input type="text" name="hero_btn_text" id="inputBtn1Text" class="form-control form-control-sm mb-2" 
                               value="<?= e($hero_btn_text) ?>" placeholder="Teks Tombol 1 (Contoh: Akses Dokumen SPMI)">
                        
                        <label class="form-label small text-muted mb-1" style="font-size:0.78rem;">Pilihan Halaman / Tautan:</label>
                        <select id="selectBtn1Link" class="form-select form-select-sm mb-2" style="font-size:0.83rem;">
                            <option value="">-- Pilih Halaman Website --</option>
                            <optgroup label="Halaman Utama Website">
                                <option value="spmi.php" data-default-text="Akses Dokumen SPMI">Sistem Penjaminan Mutu Internal (SPMI)</option>
                                <option value="profil.php" data-default-text="Profil LPM UNIKA">Profil LPM (Visi, Misi, Struktur)</option>
                                <option value="akreditasi.php" data-default-text="Status Akreditasi">Akreditasi Institusi &amp; Program Studi</option>
                                <option value="ami.php" data-default-text="Audit Mutu Internal">Audit Mutu Internal (AMI)</option>
                                <option value="berita.php" data-default-text="Berita &amp; Kegiatan">Berita &amp; Kegiatan LPM</option>
                                <option value="buletin.php" data-default-text="Baca Buletin JAMUS">Buletin JAMUS (Publikasi Mutu)</option>
                                <option value="penghargaan.php" data-default-text="Prestasi &amp; Penghargaan">Prestasi &amp; Penghargaan Mutu</option>
                                <option value="layanan.php" data-default-text="Layanan Kunjungan">Layanan &amp; Permohonan Kunjungan</option>
                                <option value="kalender-mutu.php" data-default-text="Kalender Mutu">Kalender Mutu Universitas</option>
                                <option value="faq.php" data-default-text="Tanya Jawab Mutu">Tanya Jawab (FAQ) Mutu</option>
                            </optgroup>
                            <?php if (!empty($dynamic_pages)): ?>
                            <optgroup label="Halaman Khusus (Materi Dinamis)">
                                <?php foreach ($dynamic_pages as $dp): ?>
                                <option value="page.php?slug=<?= e($dp['slug']) ?>" data-default-text="<?= e($dp['judul']) ?>"><?= e($dp['judul']) ?> (<?= e($dp['kategori']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                            <option value="custom">🔗 Ketik Tautan Manual / URL Eksternal (https://...)</option>
                        </select>

                        <div id="wrapBtn1Custom" style="display:none;">
                            <input type="text" name="hero_btn_link" id="inputBtn1Link" class="form-control form-control-sm" 
                                   value="<?= e($hero_btn_link) ?>" placeholder="Contoh: https://unika.ac.id atau spmi.php">
                            <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Ketik link halaman internal atau link website eksternal.</small>
                        </div>
                    </div>

                    <!-- Tombol Sekunder -->
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold" style="color:var(--navy);margin-bottom:0.25rem;">
                            Tombol Sekunder (Teks &amp; Tautan)
                        </label>
                        <input type="text" name="hero_btn2_text" id="inputBtn2Text" class="form-control form-control-sm mb-2" 
                               value="<?= e($hero_btn2_text) ?>" placeholder="Teks Tombol 2 (Contoh: Profil Lembaga)">
                        
                        <label class="form-label small text-muted mb-1" style="font-size:0.78rem;">Pilihan Halaman / Tautan:</label>
                        <select id="selectBtn2Link" class="form-select form-select-sm mb-2" style="font-size:0.83rem;">
                            <option value="">-- Tidak Menggunakan Tombol Sekunder --</option>
                            <optgroup label="Halaman Utama Website">
                                <option value="profil.php" data-default-text="Profil Lembaga">Profil LPM (Visi, Misi, Struktur)</option>
                                <option value="spmi.php" data-default-text="Akses Dokumen SPMI">Sistem Penjaminan Mutu Internal (SPMI)</option>
                                <option value="akreditasi.php" data-default-text="Status Akreditasi">Akreditasi Institusi &amp; Program Studi</option>
                                <option value="ami.php" data-default-text="Audit Mutu Internal">Audit Mutu Internal (AMI)</option>
                                <option value="berita.php" data-default-text="Berita &amp; Kegiatan">Berita &amp; Kegiatan LPM</option>
                                <option value="buletin.php" data-default-text="Baca Buletin JAMUS">Buletin JAMUS (Publikasi Mutu)</option>
                                <option value="penghargaan.php" data-default-text="Prestasi Mutu">Prestasi &amp; Penghargaan Mutu</option>
                                <option value="layanan.php" data-default-text="Layanan Kunjungan">Layanan &amp; Permohonan Kunjungan</option>
                                <option value="kalender-mutu.php" data-default-text="Kalender Mutu">Kalender Mutu Universitas</option>
                                <option value="faq.php" data-default-text="Tanya Jawab Mutu">Tanya Jawab (FAQ) Mutu</option>
                            </optgroup>
                            <?php if (!empty($dynamic_pages)): ?>
                            <optgroup label="Halaman Khusus (Materi Dinamis)">
                                <?php foreach ($dynamic_pages as $dp): ?>
                                <option value="page.php?slug=<?= e($dp['slug']) ?>" data-default-text="<?= e($dp['judul']) ?>"><?= e($dp['judul']) ?> (<?= e($dp['kategori']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                            <option value="custom">🔗 Ketik Tautan Manual / URL Eksternal (https://...)</option>
                        </select>

                        <div id="wrapBtn2Custom" style="display:none;">
                            <input type="text" name="hero_btn2_link" id="inputBtn2Link" class="form-control form-control-sm" 
                                   value="<?= e($hero_btn2_link) ?>" placeholder="Contoh: https://unika.ac.id atau profil.php">
                            <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Ketik link halaman internal atau link website eksternal.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Live Preview Tampilan Teks -->
            <div class="col-lg-5">
                <label class="form-label small fw-bold text-muted text-uppercase mb-2" style="letter-spacing:0.5px;">
                    <i class="bi bi-eye-fill me-1"></i> Preview Tampilan di Banner Beranda:
                </label>
                <div class="p-4 rounded-4 shadow-sm" style="background:linear-gradient(135deg, #0A192F 0%, #172554 100%);color:#fff;min-height:300px;display:flex;flex-direction:column;justify-content:center;border:1px solid rgba(255,255,255,0.1);">
                    <!-- Baris 1: Selamat datang di (Text Biasa) -->
                    <div class="fw-semibold mb-1" id="prevWelcome" style="font-family:var(--font-heading);font-size:1.05rem;color:rgba(255,255,255,0.92);">
                        <?= e($hero_welcome_text) ?>
                    </div>

                    <!-- Title -->
                    <h4 class="fw-bold mb-2 text-white" id="prevTitle" style="font-family:var(--font-heading);line-height:1.2;font-size:1.35rem;">
                        <?= e($hero_title) ?>
                    </h4>

                    <!-- Tagline Badge -->
                    <div class="mb-3">
                        <span class="badge rounded-pill fw-bold text-uppercase" id="prevTagline" style="background:rgba(217,119,6,0.25);border:1.5px solid rgba(245,158,11,0.6);color:#FEF08A;font-size:0.75rem;padding:6px 12px;letter-spacing:1px;">
                            <i class="bi bi-stars me-1 text-warning"></i>
                            <?= e($hero_tagline) ?>
                        </span>
                    </div>

                    <!-- Buttons Preview -->
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <span class="btn btn-sm px-3 fw-bold" id="prevBtn1" style="background:linear-gradient(135deg, #2563EB, #1D4ED8);color:#fff;border-radius:20px;font-size:0.75rem;<?= empty($hero_btn_text) ? 'display:none;' : '' ?>">
                            <?= e($hero_btn_text) ?> &rarr;
                        </span>
                        <span class="btn btn-sm px-3 fw-semibold" id="prevBtn2" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:20px;font-size:0.75rem;<?= empty($hero_btn2_text) ? 'display:none;' : '' ?>">
                            <?= e($hero_btn2_text) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-top mt-4 pt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4 fw-bold d-flex align-items-center gap-2" style="background:var(--navy);border:none;border-radius:8px;">
                <i class="bi bi-save"></i> Simpan Perubahan Teks Banner
            </button>
        </div>
    </form>
</div>

<!-- 2. Bagian Daftar Gambar Background Slide (Gambar yang Berubah-ubah) -->
<div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div style="width:36px;height:36px;border-radius:8px;background:#E0F2FE;display:flex;align-items:center;justify-content:center;color:#0369A1;font-size:1.1rem;">
                <i class="bi bi-images"></i>
            </div>
            <div>
                <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
                    Daftar Gambar Background Slide (<?= count($slides) ?> Foto)
                </h5>
                <small class="text-muted">
                    Gambar background yang berotasi otomatis setiap 5 detik di belakang teks tetap.
                </small>
            </div>
        </div>
        <a href="slider-form.php" class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-plus-lg"></i> Tambah Foto Slide
        </a>
    </div>

    <?php if (empty($slides)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-image fs-1 d-block mb-2 text-muted"></i>
        Belum ada gambar slide background. <a href="slider-form.php" class="text-primary fw-bold text-decoration-none">Tambah foto sekarang</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.88rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th width="70" class="text-center ps-3">Urutan</th>
                    <th width="160">Foto Background</th>
                    <th>Label Slide / Keterangan</th>
                    <th width="120" class="text-center">Status</th>
                    <th width="130" class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $s): ?>
                <?php
                $img_src = '';
                if (filter_var($s['gambar'], FILTER_VALIDATE_URL)) {
                    $img_src = $s['gambar'];
                } elseif ($s['gambar'] && file_exists(__DIR__ . '/../uploads/slides/' . $s['gambar'])) {
                    $img_src = SITE_URL . '/uploads/slides/' . $s['gambar'];
                }
                ?>
                <tr>
                    <td class="text-center ps-3">
                        <span class="badge bg-light text-dark border"><?= (int)$s['urutan'] ?></span>
                    </td>
                    <td>
                        <?php if ($img_src): ?>
                            <img src="<?= e($img_src) ?>" alt="" style="width:140px;height:75px;object-fit:cover;border-radius:8px;border:1px solid var(--border);box-shadow:0 2px 6px rgba(0,0,0,0.06);">
                        <?php else: ?>
                            <div style="width:140px;height:75px;background:var(--navy);border-radius:8px;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.4);font-size:0.75rem;">
                                No Image
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong style="color:var(--navy);font-size:0.95rem;"><?= e($s['judul']) ?></strong>
                        <?php if ($s['deskripsi']): ?>
                            <div class="text-muted small mt-1" style="max-width:400px;"><?= truncate($s['deskripsi'], 100) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="slider-list.php?toggle=<?= $s['id'] ?>" class="text-decoration-none" title="Klik untuk ubah status aktif">
                            <?php if ($s['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-eye me-1"></i> Tampil
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                    <i class="bi bi-eye-slash me-1"></i> Nonaktif
                                </span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="text-end pe-3">
                        <div class="d-inline-flex gap-1">
                            <a href="slider-form.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Slide">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="slider-list.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus Slide" onclick="return confirm('Hapus gambar slide ini?')">
                                <i class="bi bi-trash"></i>
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

<script>
// Live Preview inputs
const inWelcome = document.getElementById('inputWelcome');
const inTitle   = document.getElementById('inputTitle');
const inTagline = document.getElementById('inputTagline');

if (inWelcome) {
    inWelcome.addEventListener('input', function() {
        document.getElementById('prevWelcome').textContent = this.value || 'Selamat datang di';
    });
}
if (inTitle) {
    inTitle.addEventListener('input', function() {
        document.getElementById('prevTitle').textContent = this.value || 'Lembaga Penjaminan Mutu Unika';
    });
}
if (inTagline) {
    inTagline.addEventListener('input', function() {
        document.getElementById('prevTagline').innerHTML = '<i class="bi bi-stars me-1 text-warning"></i> ' + (this.value || 'GROW WITH QUALITY, SERVE WITH HEART');
    });
}

// 1. Sinkronisasi Tombol Utama
const selectBtn1 = document.getElementById('selectBtn1Link');
const inputBtn1  = document.getElementById('inputBtn1Link');
const wrapBtn1   = document.getElementById('wrapBtn1Custom');
const textBtn1   = document.getElementById('inputBtn1Text');
const prevBtn1   = document.getElementById('prevBtn1');

function syncBtn1() {
    const val = inputBtn1.value.trim();
    let matched = false;
    for (let opt of selectBtn1.options) {
        if (opt.value && opt.value === val) {
            selectBtn1.value = val;
            matched = true;
            break;
        }
    }
    if (!matched) {
        if (val === '') {
            selectBtn1.value = '';
            wrapBtn1.style.display = 'none';
        } else {
            selectBtn1.value = 'custom';
            wrapBtn1.style.display = 'block';
        }
    } else {
        wrapBtn1.style.display = 'none';
    }
}

if (selectBtn1 && inputBtn1) {
    selectBtn1.addEventListener('change', function() {
        if (this.value === 'custom') {
            wrapBtn1.style.display = 'block';
            inputBtn1.focus();
        } else {
            wrapBtn1.style.display = 'none';
            inputBtn1.value = this.value;
            // Rekomendasi teks jika input teks tombol masih kosong
            const selOpt = this.options[this.selectedIndex];
            const defaultText = selOpt ? selOpt.getAttribute('data-default-text') : '';
            if (defaultText && (!textBtn1.value || textBtn1.value.trim() === '')) {
                textBtn1.value = defaultText;
                if (prevBtn1) {
                    prevBtn1.textContent = defaultText + ' →';
                    prevBtn1.style.display = 'inline-block';
                }
            }
        }
    });

    inputBtn1.addEventListener('input', function() {
        syncBtn1();
    });
}

if (textBtn1 && prevBtn1) {
    textBtn1.addEventListener('input', function() {
        const val = this.value.trim();
        prevBtn1.textContent = (val || 'Akses Dokumen SPMI') + ' →';
        prevBtn1.style.display = val ? 'inline-block' : 'none';
    });
}

// 2. Sinkronisasi Tombol Sekunder
const selectBtn2 = document.getElementById('selectBtn2Link');
const inputBtn2  = document.getElementById('inputBtn2Link');
const wrapBtn2   = document.getElementById('wrapBtn2Custom');
const textBtn2   = document.getElementById('inputBtn2Text');
const prevBtn2   = document.getElementById('prevBtn2');

function syncBtn2() {
    const val = inputBtn2.value.trim();
    let matched = false;
    for (let opt of selectBtn2.options) {
        if (opt.value && opt.value === val) {
            selectBtn2.value = val;
            matched = true;
            break;
        }
    }
    if (!matched) {
        if (val === '') {
            selectBtn2.value = '';
            wrapBtn2.style.display = 'none';
        } else {
            selectBtn2.value = 'custom';
            wrapBtn2.style.display = 'block';
        }
    } else {
        wrapBtn2.style.display = 'none';
    }
}

if (selectBtn2 && inputBtn2) {
    selectBtn2.addEventListener('change', function() {
        if (this.value === 'custom') {
            wrapBtn2.style.display = 'block';
            inputBtn2.focus();
        } else {
            wrapBtn2.style.display = 'none';
            inputBtn2.value = this.value;
            // Rekomendasi teks jika input teks tombol masih kosong
            const selOpt = this.options[this.selectedIndex];
            const defaultText = selOpt ? selOpt.getAttribute('data-default-text') : '';
            if (defaultText && (!textBtn2.value || textBtn2.value.trim() === '')) {
                textBtn2.value = defaultText;
                if (prevBtn2) {
                    prevBtn2.textContent = defaultText;
                    prevBtn2.style.display = 'inline-block';
                }
            }
        }
    });

    inputBtn2.addEventListener('input', function() {
        syncBtn2();
    });
}

if (textBtn2 && prevBtn2) {
    textBtn2.addEventListener('input', function() {
        const val = this.value.trim();
        prevBtn2.textContent = val || 'Profil Lembaga';
        prevBtn2.style.display = val ? 'inline-block' : 'none';
    });
}

// Inisialisasi awal
syncBtn1();
syncBtn2();
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
