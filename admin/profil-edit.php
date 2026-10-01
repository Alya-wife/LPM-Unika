<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Profil & Visi Misi';
$db = getDB();

$flash = '';
$error = '';

if (!function_exists('cleanProfileItem')) {
    function cleanProfileItem($item) {
        return trim(preg_replace('/^(\d+[\.\)]|\-|\•|\*)\s*/u', '', trim($item)));
    }
}

function parsePostItems($key_array, $key_raw) {
    $items = [];
    if (isset($_POST[$key_array]) && is_array($_POST[$key_array])) {
        foreach ($_POST[$key_array] as $line) {
            $cleaned = cleanProfileItem($line);
            if ($cleaned !== '') {
                $items[] = $cleaned;
            }
        }
    } elseif (!empty($_POST[$key_raw])) {
        $lines = explode("\n", $_POST[$key_raw]);
        foreach ($lines as $line) {
            $cleaned = cleanProfileItem($line);
            if ($cleaned !== '') {
                $items[] = $cleaned;
            }
        }
    }
    return implode("\n", $items);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul_tentang = trim($_POST['profil_tentang_judul'] ?? '');
    $teks1         = trim($_POST['profil_tentang_teks1'] ?? '');
    $teks2         = trim($_POST['profil_tentang_teks2'] ?? '');
    $visi          = trim($_POST['profil_visi'] ?? '');
    $misi          = parsePostItems('profil_misi_items', 'profil_misi');
    $tujuan        = parsePostItems('profil_tujuan_items', 'profil_tujuan');
    $tugas         = trim($_POST['profil_tugas'] ?? '');
    $fungsi        = parsePostItems('profil_fungsi_items', 'profil_fungsi');
    $stat_akr      = trim($_POST['stat_akreditasi'] ?? 'A');
    $stat_prodi    = trim($_POST['stat_prodi'] ?? '27');
    $stat_tahun    = trim($_POST['stat_tahun'] ?? '40');

    if (!$judul_tentang || !$visi || !$misi) {
        $error = 'Judul tentang, visi, dan butir misi wajib diisi minimal 1 butir.';
    } else {
        setPengaturan('profil_tentang_judul', $judul_tentang);
        setPengaturan('profil_tentang_teks1', $teks1);
        setPengaturan('profil_tentang_teks2', $teks2);
        setPengaturan('profil_visi', $visi);
        setPengaturan('profil_misi', $misi);
        setPengaturan('profil_tujuan', $tujuan);
        setPengaturan('profil_tugas', $tugas);
        setPengaturan('profil_fungsi', $fungsi);
        setPengaturan('stat_akreditasi', $stat_akr);
        setPengaturan('stat_prodi', $stat_prodi);
        setPengaturan('stat_tahun', $stat_tahun);

        $_SESSION['flash'] = 'Konten Profil, Visi, Misi, Tujuan, serta Tugas & Fungsi berhasil diperbarui.';
        redirect(SITE_URL . '/admin/profil-edit.php');
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$judul_tentang = getPengaturan('profil_tentang_judul', 'Lembaga Penjaminan Mutu UNIKA');
$teks1         = getPengaturan('profil_tentang_teks1', '');
$teks2         = getPengaturan('profil_tentang_teks2', '');
$visi          = getPengaturan('profil_visi', '');
$misi          = getPengaturan('profil_misi', '');
$tujuan        = getPengaturan('profil_tujuan', '');
$tugas         = getPengaturan('profil_tugas', '');
$fungsi        = getPengaturan('profil_fungsi', '');
$stat_akr      = getPengaturan('stat_akreditasi', 'Unggul');
$stat_prodi    = getPengaturan('stat_prodi', '');
$stat_tahun    = getPengaturan('stat_tahun', '40');
$actual_prodi_count = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi")->fetchColumn();

$misi_items   = array_values(array_filter(array_map('cleanProfileItem', explode("\n", $misi))));
$tujuan_items = array_values(array_filter(array_map('cleanProfileItem', explode("\n", $tujuan))));
$fungsi_items = array_values(array_filter(array_map('cleanProfileItem', explode("\n", $fungsi))));

if (empty($misi_items))   $misi_items   = [''];
if (empty($tujuan_items)) $tujuan_items = [''];
if (empty($fungsi_items)) $fungsi_items = [''];

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
.item-builder-container {
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    border-radius: 10px;
    padding: 1.25rem;
}
.item-row {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.65rem;
    background: #FFFFFF;
    padding: 0.45rem 0.65rem;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.item-row:focus-within {
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.item-badge {
    width: 28px;
    height: 28px;
    min-width: 28px;
    background: var(--navy);
    color: #FFFFFF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}
.item-input {
    border: 1px solid transparent !important;
    background: transparent !important;
    padding: 0.4rem 0.5rem !important;
    font-size: 0.9rem;
    flex-grow: 1;
}
.item-input:focus {
    outline: none !important;
    box-shadow: none !important;
}
.btn-remove-row {
    width: 30px;
    height: 30px;
    border: none;
    background: #FEE2E2;
    color: #DC2626;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    transition: background 0.15s;
}
.btn-remove-row:hover {
    background: #FCA5A5;
    color: #991B1B;
}
.btn-add-item {
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px dashed #93C5FD;
    padding: 0.45rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: background 0.15s;
}
.btn-add-item:hover {
    background: #DBEAFE;
}
.btn-bulk-toggle {
    background: none;
    border: none;
    color: #64748B;
    font-size: 0.8rem;
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
}
.btn-bulk-toggle:hover {
    color: var(--navy);
}
.bulk-paste-box {
    display: none;
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
}
</style>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Kelola Profil, Visi &amp; Misi</span>
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
                <div class="admin-table-title">Pengaturan Halaman Profil (Tentang Kami, Visi &amp; Misi, Tupoksi)</div>
                <a href="<?= SITE_URL ?>/profil.php" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;border-radius:6px;">
                    Lihat Halaman Profil &rarr;
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST">
                    <!-- Bagian 1: Tentang Kami -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        1. Bagian "Tentang LPM"
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Judul Bagian Tentang <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="profil_tentang_judul" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($judul_tentang) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Paragraf Pertama (Penjelasan Umum)
                            </label>
                            <textarea name="profil_tentang_teks1" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;"><?= e($teks1) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Paragraf Kedua (Peran Katalisator Budaya Mutu)
                            </label>
                            <textarea name="profil_tentang_teks2" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;"><?= e($teks2) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Akreditasi Institusi
                            </label>
                            <input type="text" name="stat_akreditasi" class="form-control" style="border:1.5px solid var(--border);padding:0.6rem 1rem;" value="<?= e($stat_akr) ?>" placeholder="A / Unggul">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jumlah Program Studi
                            </label>
                            <input type="text" name="stat_prodi" class="form-control" style="border:1.5px solid var(--border);padding:0.6rem 1rem;" value="<?= e($stat_prodi) ?>" placeholder="Auto (<?= $actual_prodi_count ?>)">
                            <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_prodi_count ?>)</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Tahun Pengalaman
                            </label>
                            <input type="text" name="stat_tahun" class="form-control" style="border:1.5px solid var(--border);padding:0.6rem 1rem;" value="<?= e($stat_tahun) ?>" placeholder="Contoh: 40+">
                        </div>
                    </div>

                    <!-- Bagian 2: Visi, Misi & Tujuan -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-top:2.5rem;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        2. Bagian "Visi, Misi &amp; Tujuan"
                    </h5>
                    
                    <!-- Visi -->
                    <div class="mb-4">
                        <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                            Rumusan Visi LPM <span style="color:#C62828;">*</span>
                        </label>
                        <textarea name="profil_visi" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;" placeholder="Visi LPM UNIKA..." required><?= e($visi) ?></textarea>
                        <small class="text-muted">Pernyataan visi utama penjaminan mutu lembaga.</small>
                    </div>

                    <!-- Misi Item Builder -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <label class="form-label mb-0" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                    Butir-Butir Misi LPM <span style="color:#C62828;">*</span>
                                </label>
                                <span class="badge ms-2" style="background:#E0E7FF;color:#3730A3;font-size:0.75rem;padding:0.2rem 0.55rem;border-radius:6px;" id="misi-count-badge"><?= count($misi_items) ?> Butir</span>
                            </div>
                            <button type="button" class="btn-bulk-toggle" onclick="toggleBulkBox('bulk-misi')">
                                📋 Salin/Tempel Banyak Baris Sekaligus
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">
                            Setiap baris di bawah akan otomatis diberi nomor urut rapi (1, 2, dst). Tidak perlu mengetik angka manual di awal kalimat.
                        </small>

                        <div id="bulk-misi" class="bulk-paste-box mb-3">
                            <label style="font-size:0.8rem;font-weight:600;color:#334155;">Tempel Daftar Misi dari Dokumen (Otomatis dipisah per baris):</label>
                            <textarea id="bulk-misi-text" class="form-control mb-2" rows="4" placeholder="1. Misi pertama&#10;2. Misi kedua&#10;3. Misi ketiga..."></textarea>
                            <button type="button" class="btn btn-sm btn-primary" style="font-size:0.8rem;" onclick="applyBulkItems('misi')">
                                Masukkan ke Daftar Butir
                            </button>
                        </div>

                        <div class="item-builder-container" id="misi-container">
                            <div class="items-list" id="misi-list">
                                <?php foreach ($misi_items as $idx => $item_val): ?>
                                <div class="item-row">
                                    <span class="item-badge"><?= $idx + 1 ?></span>
                                    <input type="text" name="profil_misi_items[]" class="form-control item-input" value="<?= e($item_val) ?>" placeholder="Tuliskan butir misi di sini..." required>
                                    <button type="button" class="btn-remove-row" title="Hapus butir ini" onclick="removeRow(this, 'misi')">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-add-item mt-2" onclick="addRow('misi')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Butir Misi
                            </button>
                        </div>
                    </div>

                    <!-- Tujuan Item Builder -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <label class="form-label mb-0" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                    Butir-Butir Tujuan LPM
                                </label>
                                <span class="badge ms-2" style="background:#E0E7FF;color:#3730A3;font-size:0.75rem;padding:0.2rem 0.55rem;border-radius:6px;" id="tujuan-count-badge"><?= count($tujuan_items) ?> Butir</span>
                            </div>
                            <button type="button" class="btn-bulk-toggle" onclick="toggleBulkBox('bulk-tujuan')">
                                📋 Salin/Tempel Banyak Baris Sekaligus
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">
                            Target capaian operasional penjaminan mutu. Otomatis bernomor urut di halaman profil.
                        </small>

                        <div id="bulk-tujuan" class="bulk-paste-box mb-3">
                            <label style="font-size:0.8rem;font-weight:600;color:#334155;">Tempel Daftar Tujuan dari Dokumen (Otomatis dipisah per baris):</label>
                            <textarea id="bulk-tujuan-text" class="form-control mb-2" rows="4" placeholder="1. Tujuan pertama&#10;2. Tujuan kedua..."></textarea>
                            <button type="button" class="btn btn-sm btn-primary" style="font-size:0.8rem;" onclick="applyBulkItems('tujuan')">
                                Masukkan ke Daftar Butir
                            </button>
                        </div>

                        <div class="item-builder-container" id="tujuan-container">
                            <div class="items-list" id="tujuan-list">
                                <?php foreach ($tujuan_items as $idx => $item_val): ?>
                                <div class="item-row">
                                    <span class="item-badge"><?= $idx + 1 ?></span>
                                    <input type="text" name="profil_tujuan_items[]" class="form-control item-input" value="<?= e($item_val) ?>" placeholder="Tuliskan butir tujuan di sini...">
                                    <button type="button" class="btn-remove-row" title="Hapus butir ini" onclick="removeRow(this, 'tujuan')">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-add-item mt-2" onclick="addRow('tujuan')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Butir Tujuan
                            </button>
                        </div>
                    </div>

                    <!-- Bagian 3: Tugas & Fungsi -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-top:2.5rem;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        3. Bagian "Tugas dan Fungsi"
                    </h5>
                    
                    <!-- Tugas Utama -->
                    <div class="mb-4">
                        <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                            Tugas Pokok &amp; Mandat Utama LPM (Berdasarkan SK / Peraturan Universitas)
                        </label>
                        <textarea name="profil_tugas" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Sesuai Peraturan Universitas Katolik Soegijapranata..."><?= e($tugas) ?></textarea>
                        <small class="text-muted">Paragraf mandat tugas LPM yang tampil pada kotak utama bernuansa ungu di halaman profil.</small>
                    </div>

                    <!-- Fungsi Item Builder -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <label class="form-label mb-0" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                    Butir-Butir Fungsi Penyelenggaraan LPM
                                </label>
                                <span class="badge ms-2" style="background:#E0E7FF;color:#3730A3;font-size:0.75rem;padding:0.2rem 0.55rem;border-radius:6px;" id="fungsi-count-badge"><?= count($fungsi_items) ?> Butir</span>
                            </div>
                            <button type="button" class="btn-bulk-toggle" onclick="toggleBulkBox('bulk-fungsi')">
                                📋 Salin/Tempel Banyak Baris Sekaligus
                            </button>
                        </div>
                        <small class="text-muted d-block mb-2">
                            Setiap butir di bawah akan otomatis tampil sebagai kartu fungsi bernomor di halaman profil.
                        </small>

                        <div id="bulk-fungsi" class="bulk-paste-box mb-3">
                            <label style="font-size:0.8rem;font-weight:600;color:#334155;">Tempel Daftar Fungsi dari Dokumen (Otomatis dipisah per baris):</label>
                            <textarea id="bulk-fungsi-text" class="form-control mb-2" rows="4" placeholder="1. Menyelenggarakan evaluasi...&#10;2. Mengembangkan instrumen..."></textarea>
                            <button type="button" class="btn btn-sm btn-primary" style="font-size:0.8rem;" onclick="applyBulkItems('fungsi')">
                                Masukkan ke Daftar Butir
                            </button>
                        </div>

                        <div class="item-builder-container" id="fungsi-container">
                            <div class="items-list" id="fungsi-list">
                                <?php foreach ($fungsi_items as $idx => $item_val): ?>
                                <div class="item-row">
                                    <span class="item-badge"><?= $idx + 1 ?></span>
                                    <input type="text" name="profil_fungsi_items[]" class="form-control item-input" value="<?= e($item_val) ?>" placeholder="Tuliskan butir fungsi di sini...">
                                    <button type="button" class="btn-remove-row" title="Hapus butir ini" onclick="removeRow(this, 'fungsi')">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-add-item mt-2" onclick="addRow('fungsi')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah Butir Fungsi
                            </button>
                        </div>
                    </div>

                    <div class="col-12" style="padding-top:1.5rem;border-top:1px solid var(--border);margin-top:1.5rem;">
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn-submit">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Simpan Perubahan Profil &amp; Tupoksi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function reindexItems(type) {
    const list = document.getElementById(type + '-list');
    const rows = list.querySelectorAll('.item-row');
    rows.forEach((row, i) => {
        const badge = row.querySelector('.item-badge');
        if (badge) badge.textContent = (i + 1);
    });
    const badgeCount = document.getElementById(type + '-count-badge');
    if (badgeCount) badgeCount.textContent = rows.length + ' Butir';
}

function addRow(type, textVal = '') {
    const list = document.getElementById(type + '-list');
    const row = document.createElement('div');
    row.className = 'item-row';
    const isRequired = (type === 'misi' && list.children.length === 0) ? 'required' : '';
    
    row.innerHTML = `
        <span class="item-badge">0</span>
        <input type="text" name="profil_${type}_items[]" class="form-control item-input" value="${escapeHtml(textVal)}" placeholder="Tuliskan butir ${type} di sini..." ${isRequired}>
        <button type="button" class="btn-remove-row" title="Hapus butir ini" onclick="removeRow(this, '${type}')">&times;</button>
    `;
    list.appendChild(row);
    reindexItems(type);
    if (!textVal) {
        const input = row.querySelector('.item-input');
        if (input) input.focus();
    }
}

function removeRow(btn, type) {
    const list = document.getElementById(type + '-list');
    const rows = list.querySelectorAll('.item-row');
    if (rows.length <= 1) {
        // Jangan hapus baris terakhir, cukup kosongkan nilainya
        const input = rows[0].querySelector('.item-input');
        if (input) input.value = '';
        return;
    }
    const row = btn.closest('.item-row');
    if (row) {
        row.remove();
        reindexItems(type);
    }
}

function toggleBulkBox(id) {
    const box = document.getElementById(id);
    if (box) {
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';
    }
}

function applyBulkItems(type) {
    const textarea = document.getElementById('bulk-' + type + '-text');
    if (!textarea) return;
    const lines = textarea.value.split('\n');
    const cleanLines = [];
    
    lines.forEach(line => {
        let clean = line.trim();
        // Bersihkan numbering seperti '1. ', '1) ', '• ', '- ', '* '
        clean = clean.replace(/^(\d+[\.\)]|\-|\•|\*)\s*/, '').trim();
        if (clean !== '') {
            cleanLines.push(clean);
        }
    });

    if (cleanLines.length === 0) {
        alert('Teks kosong atau tidak ada baris yang valid.');
        return;
    }

    const list = document.getElementById(type + '-list');
    // Jika hanya ada 1 baris dan kosong, kita bersihkan dulu
    const currentRows = list.querySelectorAll('.item-row');
    if (currentRows.length === 1) {
        const firstInput = currentRows[0].querySelector('.item-input');
        if (!firstInput.value.trim()) {
            list.innerHTML = '';
        }
    }

    cleanLines.forEach(text => {
        addRow(type, text);
    });

    textarea.value = '';
    toggleBulkBox('bulk-' + type);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;')
               .replace(/</g, '&lt;')
               .replace(/>/g, '&gt;')
               .replace(/"/g, '&quot;');
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
