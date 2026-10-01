<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Form Jadwal Pelaksanaan AMI';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$d = [
    'tahun_akademik' => '2026/2027',
    'bulan_tahun'    => '',
    'urutan'         => 1,
    'kegiatan'       => '',
    'is_active'      => 1
];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM kalender_ami WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $d = $row;
    else $id = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_select       = trim($_POST['tahun_akademik_select'] ?? '');
    $p_custom       = trim($_POST['tahun_akademik_custom'] ?? '');
    $tahun_akademik = ($p_select === 'NEW' || (!empty($p_custom) && $p_select === 'NEW')) ? $p_custom : ($p_select ?: trim($_POST['tahun_akademik'] ?? '2026/2027'));
    $bulan_tahun    = trim($_POST['bulan_tahun'] ?? '');
    $urutan         = (int)($_POST['urutan'] ?? 1);
    $kegiatan       = trim($_POST['kegiatan'] ?? '');
    $is_active      = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id > 0) {
        $db->prepare("UPDATE kalender_ami SET tahun_akademik=?, bulan_tahun=?, urutan=?, kegiatan=?, is_active=? WHERE id=?")
           ->execute([$tahun_akademik, $bulan_tahun, $urutan, $kegiatan, $is_active, $id]);
        $_SESSION['flash'] = 'Jadwal kegiatan AMI berhasil diperbarui.';
    } else {
        $db->prepare("INSERT INTO kalender_ami (tahun_akademik, bulan_tahun, urutan, kegiatan, is_active) VALUES (?, ?, ?, ?, ?)")
           ->execute([$tahun_akademik, $bulan_tahun, $urutan, $kegiatan, $is_active]);
        $_SESSION['flash'] = 'Jadwal kegiatan AMI baru berhasil ditambahkan.';
    }
    redirect(SITE_URL . '/admin/kalender-ami-list.php');
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title"><?= $id > 0 ? 'Edit Jadwal Pelaksanaan AMI' : 'Tambah Jadwal Pelaksanaan AMI Baru' ?></div>
        <a href="kalender-ami-list.php" class="btn-outline">Kembali</a>
    </div>

    <form method="post" class="lpm-form" style="padding:1.5rem;">
        
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Tahun Akademik <span class="text-danger">*</span></label>
                <?php
                $cur_ami = $id > 0 ? ($d['tahun_akademik'] ?? '2026/2027') : ($_POST['tahun_akademik_select'] ?? $_POST['tahun_akademik'] ?? '2026/2027');
                $existing_ami = $db->query("SELECT DISTINCT tahun_akademik FROM kalender_ami WHERE tahun_akademik IS NOT NULL AND tahun_akademik != '' ORDER BY tahun_akademik DESC")->fetchAll(PDO::FETCH_COLUMN);
                $default_ami = ['2024/2025', '2025/2026', '2026/2027', '2027/2028', '2028/2029'];
                $all_ami_options = array_unique(array_merge($existing_ami, $default_ami));
                rsort($all_ami_options);
                $is_custom_ami = (!in_array($cur_ami, $all_ami_options) && $cur_ami !== '');
                ?>
                <select name="tahun_akademik_select" id="ami_tahun_select" class="form-select mb-2" onchange="toggleCustomPeriode(this, 'ami_tahun_custom')">
                    <?php foreach ($all_ami_options as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= ($cur_ami === $opt && !$is_custom_ami) ? 'selected' : '' ?>>Periode <?= e($opt) ?></option>
                    <?php endforeach; ?>
                    <option value="NEW" <?= $is_custom_ami ? 'selected' : '' ?>>➕ Tambah Periode Baru...</option>
                </select>
                
                <input type="text" name="tahun_akademik_custom" id="ami_tahun_custom" class="form-control"
                       placeholder="Ketik periode baru (contoh: 2028/2029)"
                       value="<?= e($is_custom_ami ? $cur_ami : '') ?>"
                       style="border:1.5px solid var(--purple);display:<?= $is_custom_ami ? 'block' : 'none' ?>;">
            </div>
            <div class="col-md-5 mt-4 mt-md-0">
                <label class="form-label fw-bold">Tanggal / Waktu Pelaksanaan <span class="text-danger">*</span></label>
                <input type="text" name="bulan_tahun" class="form-control" value="<?= e($d['bulan_tahun']) ?>" required placeholder="Contoh: 1 - 15 Oktober 2026 / Oktober 2026">
            </div>
            <div class="col-md-3 mt-4 mt-md-0">
                <label class="form-label fw-bold">Nomor Urutan Tampil <span class="text-danger">*</span></label>
                <input type="number" name="urutan" class="form-control" value="<?= (int)$d['urutan'] ?>" required min="1">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Keterangan Kegiatan Pelaksanaan <span class="text-danger">*</span></label>
            <textarea name="kegiatan" class="form-control" rows="4" required placeholder="Tuliskan rincian kegiatan AMI (pisahkan tiap poin dengan baris baru)..."><?= e($d['kegiatan']) ?></textarea>
            <div class="form-text small text-muted mt-1">Gunakan baris baru (Enter) untuk membuat beberapa poin kegiatan.</div>
        </div>

        <div class="mb-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $d['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="is_active">Aktifkan Jadwal (Tampilkan di halaman publik)</label>
            </div>
        </div>
        
        <div class="border-top pt-4 text-end">
            <button type="submit" class="btn-save px-4 py-2">Simpan Jadwal AMI</button>
        </div>
    </form>
<script>
function toggleCustomPeriode(selectEl, customInputId) {
    var customInput = document.getElementById(customInputId);
    if (!customInput) return;
    if (selectEl.value === 'NEW') {
        customInput.style.display = 'block';
        customInput.focus();
        customInput.required = true;
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
