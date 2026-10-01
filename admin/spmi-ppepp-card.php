<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Kartu Siklus PPEPP SPMI';
$current_admin = 'spmi-ppepp-card';
$db = getDB();

$flash = $_SESSION['flash'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['flash'], $_SESSION['error']);

// Tangani Reset ke Bawaan
if (isset($_GET['reset_default']) && $_GET['reset_default'] == '1') {
    require_once __DIR__ . '/../scripts/migrate_spmi_ppepp_stages.php';
    $_SESSION['flash'] = 'Seluruh isi 5 kartu siklus PPEPP berhasil direset ke pengaturan standar bawaan!';
    redirect(SITE_URL . '/admin/spmi-ppepp-card.php');
}

// Tangani Simpan Data Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahap_ke = (int)($_POST['tahap_ke'] ?? 1);
    if ($tahap_ke < 1 || $tahap_ke > 5) $tahap_ke = 1;

    $badge_teks       = trim($_POST['badge_teks'] ?? '');
    $judul            = trim($_POST['judul'] ?? '');
    $inisial          = trim($_POST['inisial'] ?? 'P');
    $label            = trim($_POST['label'] ?? '');
    $warna            = trim($_POST['warna'] ?? '#C0392B');
    $deskripsi        = trim($_POST['deskripsi'] ?? '');
    $prosedur_label   = trim($_POST['prosedur_label'] ?? 'PROSEDUR OPERASIONAL:');
    $langkah_prosedur = trim($_POST['langkah_prosedur'] ?? '');
    $dokumen_label    = trim($_POST['dokumen_label'] ?? 'DOKUMEN TERKAIT:');
    $dokumen_teks     = trim($_POST['dokumen_teks'] ?? '');
    $aktor_label      = trim($_POST['aktor_label'] ?? 'PENANGGUNG JAWAB:');
    $aktor_teks       = trim($_POST['aktor_teks'] ?? '');
    $tombol_teks      = trim($_POST['tombol_teks'] ?? 'Dokumen Terkait');
    $tombol_url       = trim($_POST['tombol_url'] ?? '#dokumen-spmi');
    $footer_teks      = trim($_POST['footer_teks'] ?? 'Standar Mutu UNIKA Soegijapranata');

    if (empty($judul)) {
        $_SESSION['error'] = 'Judul tahapan PPEPP wajib diisi.';
    } else {
        $stmt = $db->prepare("
            UPDATE spmi_ppepp_stages 
            SET inisial = ?, label = ?, badge_teks = ?, judul = ?, warna = ?, 
                deskripsi = ?, prosedur_label = ?, langkah_prosedur = ?, 
                dokumen_label = ?, dokumen_teks = ?, aktor_label = ?, aktor_teks = ?, 
                tombol_teks = ?, tombol_url = ?, footer_teks = ?
            WHERE tahap_ke = ?
        ");
        $stmt->execute([
            $inisial, $label, $badge_teks, $judul, $warna,
            $deskripsi, $prosedur_label, $langkah_prosedur,
            $dokumen_label, $dokumen_teks, $aktor_label, $aktor_teks,
            $tombol_teks, $tombol_url, $footer_teks,
            $tahap_ke
        ]);

        $_SESSION['flash'] = "Perubahan kartu Tahap {$tahap_ke} ({$label}) berhasil disimpan!";
    }
    redirect(SITE_URL . '/admin/spmi-ppepp-card.php?tab=' . $tahap_ke);
}

// Ambil seluruh data 5 tahapan dari database
$stages = [];
try {
    $rows = $db->query("SELECT * FROM spmi_ppepp_stages ORDER BY tahap_ke ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $stages[$r['tahap_ke']] = $r;
    }
} catch (Exception $e) {}

// Jika belum ada, jalankan seeder
if (empty($stages) || count($stages) < 5) {
    require_once __DIR__ . '/../scripts/migrate_spmi_ppepp_stages.php';
    $rows = $db->query("SELECT * FROM spmi_ppepp_stages ORDER BY tahap_ke ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $stages[$r['tahap_ke']] = $r;
    }
}

$active_tab = isset($_GET['tab']) && is_numeric($_GET['tab']) ? (int)$_GET['tab'] : 1;
if ($active_tab < 1 || $active_tab > 5) $active_tab = 1;
$cur = $stages[$active_tab] ?? [];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Kartu Siklus PPEPP SPMI
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kustomisasi seluruh konten, teks, label, prosedur operasional, penanggung jawab, tombol, serta warna kartu pada setiap tahap siklus SPMI.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= SITE_URL ?>/spmi.php#siklus-ppepp" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau di Web Publik
        </a>
        <a href="<?= SITE_URL ?>/admin/spmi-portal-setting.php" class="btn btn-sm btn-outline-secondary fw-semibold" style="border-radius:8px;">
            <i class="bi bi-gear me-1"></i> Chart &amp; Portal SPMI
        </a>
        <a href="<?= SITE_URL ?>/admin/spmi-ppepp-card.php?reset_default=1" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" onclick="return confirm('Apakah Anda yakin ingin mereset seluruh kartu siklus PPEPP ke format teks default standar?');" title="Kembalikan seluruh teks ke standar bawaan">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset ke Default
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Tab Navigasi 5 Tahap PPEPP -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-body p-3">
        <div class="nav nav-pills nav-fill gap-2" id="ppeppTabs" role="tablist">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <?php 
                $st = $stages[$i] ?? [];
                $is_active = ($active_tab === $i);
                $st_color = $st['warna'] ?? '#C0392B';
            ?>
            <a href="<?= SITE_URL ?>/admin/spmi-ppepp-card.php?tab=<?= $i ?>" class="nav-link p-2 px-3 text-start d-flex align-items-center gap-2 rounded-3 text-decoration-none <?= $is_active ? 'active shadow-sm' : 'bg-light text-dark' ?>" style="<?= $is_active ? "background:{$st_color} !important;color:#fff !important;" : 'border:1px solid #E2E8F0;' ?>">
                <span class="badge rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:28px;height:28px;font-size:0.85rem;<?= $is_active ? 'background:#fff;color:' . $st_color : 'background:' . $st_color . ';color:#fff' ?>">
                    <?= htmlspecialchars($st['inisial'] ?? 'P') ?>
                </span>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold text-truncate" style="font-size:0.88rem;">Tahap <?= $i ?>: <?= htmlspecialchars($st['label'] ?? '') ?></div>
                    <small class="d-block text-truncate" style="font-size:0.72rem;opacity:<?= $is_active ? '0.9' : '0.6' ?>;"><?= htmlspecialchars($st['judul'] ?? '') ?></small>
                </div>
            </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Layout Dua Kolom: Form Edit di Kiri, Live Preview di Kanan -->
<div class="row g-4 mb-5">
    
    <!-- Kolom Kiri: Form Sunting Lengkap -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">
                        <i class="bi bi-pencil-square me-1 text-primary"></i> Formulir Pengaturan Tahap <?= $active_tab ?> (<?= htmlspecialchars($cur['label'] ?? '') ?>)
                    </h5>
                    <small class="text-muted">Perubahan akan langsung terpasang pada card interaktif siklus PPEPP.</small>
                </div>
                <span class="badge px-3 py-2 rounded-pill fw-bold" style="background:<?= htmlspecialchars($cur['warna'] ?? '#C0392B') ?>;color:#fff;">
                    Tahap <?= $active_tab ?> / 5
                </span>
            </div>

            <div class="card-body p-4">
                <form method="POST" id="ppeppCardForm">
                    <input type="hidden" name="tahap_ke" value="<?= $active_tab ?>">

                    <!-- Bagian 1: Header & Identitas -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <div class="fw-bold small text-uppercase text-dark mb-3" style="letter-spacing:0.5px;">
                            <i class="bi bi-tag-fill me-1 text-primary"></i> 1. Identitas &amp; Badge Tahap
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-dark">Inisial Huruf <span class="text-danger">*</span></label>
                                <input type="text" name="inisial" id="inputInisial" class="form-control text-center fw-bold fs-5" value="<?= htmlspecialchars($cur['inisial'] ?? 'P') ?>" maxlength="3" required oninput="updateLivePreview();">
                                <small class="text-muted" style="font-size:0.72rem;">Contoh: P atau E</small>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-bold small text-dark">Label Singkat <span class="text-danger">*</span></label>
                                <input type="text" name="label" id="inputLabel" class="form-control" value="<?= htmlspecialchars($cur['label'] ?? '') ?>" placeholder="Penetapan, Pelaksanaan, dll" required oninput="updateLivePreview();">
                                <small class="text-muted" style="font-size:0.72rem;">Label pada lingkaran diagram.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-dark">Warna Aksen Card <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color p-1" id="inputColorPicker" value="<?= htmlspecialchars($cur['warna'] ?? '#C0392B') ?>" style="width:44px;max-width:44px;height:38px;border-radius:8px 0 0 8px;cursor:pointer;" oninput="document.getElementById('inputWarna').value = this.value; updateLivePreview();">
                                    <input type="text" name="warna" id="inputWarna" class="form-control text-uppercase" value="<?= htmlspecialchars($cur['warna'] ?? '#C0392B') ?>" maxlength="10" required oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){ document.getElementById('inputColorPicker').value = this.value; updateLivePreview(); }">
                                </div>
                                <small class="text-muted" style="font-size:0.72rem;">Warna border, badge, &amp; aksen.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Teks Badge Header Card <span class="text-danger">*</span></label>
                                <input type="text" name="badge_teks" id="inputBadgeTeks" class="form-control" value="<?= htmlspecialchars($cur['badge_teks'] ?? 'TAHAP ' . $active_tab . ' DARI 5 • SIKLUS SPMI') ?>" required oninput="updateLivePreview();">
                                <small class="text-muted" style="font-size:0.72rem;">Teks pill di bagian atas card (contoh: <code>TAHAP <?= $active_tab ?> DARI 5 • SIKLUS SPMI</code>).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Judul & Deskripsi Utama -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <div class="fw-bold small text-uppercase text-dark mb-3" style="letter-spacing:0.5px;">
                            <i class="bi bi-fonts me-1 text-primary"></i> 2. Judul &amp; Uraian Utama
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Judul Tahapan Card <span class="text-danger">*</span></label>
                                <input type="text" name="judul" id="inputJudul" class="form-control fw-bold" value="<?= htmlspecialchars($cur['judul'] ?? '') ?>" placeholder="Penetapan Standar Dikti (P)" required oninput="updateLivePreview();">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Deskripsi Penjelasan Tahap <span class="text-danger">*</span></label>
                                <textarea name="deskripsi" id="inputDeskripsi" rows="3" class="form-control" placeholder="Tuliskan uraian tahapan mutu..." required oninput="updateLivePreview();"><?= htmlspecialchars($cur['deskripsi'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 3: Prosedur Operasional -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <div class="fw-bold small text-uppercase text-dark mb-3" style="letter-spacing:0.5px;">
                            <i class="bi bi-list-check me-1 text-primary"></i> 3. Prosedur Operasional (Poin-Poin)
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Label Judul Sub-bagian Prosedur</label>
                                <input type="text" name="prosedur_label" id="inputProsedurLabel" class="form-control" value="<?= htmlspecialchars($cur['prosedur_label'] ?? 'PROSEDUR OPERASIONAL:') ?>" oninput="updateLivePreview();">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Butir-Butir Prosedur Operasional</label>
                                <textarea name="langkah_prosedur" id="inputLangkahProsedur" rows="5" class="form-control" placeholder="Tuliskan satu langkah/prosedur per baris..." oninput="updateLivePreview();"><?= htmlspecialchars($cur['langkah_prosedur'] ?? '') ?></textarea>
                                <small class="text-muted" style="font-size:0.75rem;">
                                    <i class="bi bi-info-circle me-1"></i> <strong>Tips:</strong> Tekan <strong>Enter</strong> untuk membuat butir baru. Setiap baris otomatis menjadi satu poin bullet.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 4: Dokumen & Penanggung Jawab -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <div class="fw-bold small text-uppercase text-dark mb-3" style="letter-spacing:0.5px;">
                            <i class="bi bi-file-earmark-ruled me-1 text-primary"></i> 4. Dokumen &amp; Penanggung Jawab
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Label Dokumen Terkait</label>
                                <input type="text" name="dokumen_label" id="inputDokumenLabel" class="form-control" value="<?= htmlspecialchars($cur['dokumen_label'] ?? 'DOKUMEN TERKAIT:') ?>" oninput="updateLivePreview();">
                                
                                <label class="form-label fw-bold small text-dark mt-2">Daftar Dokumen Terkait</label>
                                <textarea name="dokumen_teks" id="inputDokumenTeks" rows="3" class="form-control" placeholder="Kebijakan SPMI, Manual, ..." oninput="updateLivePreview();"><?= htmlspecialchars($cur['dokumen_teks'] ?? '') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Label Penanggung Jawab</label>
                                <input type="text" name="aktor_label" id="inputAktorLabel" class="form-control" value="<?= htmlspecialchars($cur['aktor_label'] ?? 'PENANGGUNG JAWAB:') ?>" oninput="updateLivePreview();">

                                <label class="form-label fw-bold small text-dark mt-2">Pihak Penanggung Jawab</label>
                                <textarea name="aktor_teks" id="inputAktorTeks" rows="3" class="form-control" placeholder="Rektor, Dekan, LPM, ..." oninput="updateLivePreview();"><?= htmlspecialchars($cur['aktor_teks'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 5: Tombol Aksi & Footer -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <div class="fw-bold small text-uppercase text-dark mb-3" style="letter-spacing:0.5px;">
                            <i class="bi bi-box-arrow-in-up-right me-1 text-primary"></i> 5. Tombol Aksi &amp; Keterangan Footer
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Teks Tombol Aksi</label>
                                <input type="text" name="tombol_teks" id="inputTombolTeks" class="form-control" value="<?= htmlspecialchars($cur['tombol_teks'] ?? 'Dokumen Terkait') ?>" oninput="updateLivePreview();">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Tautan / URL Tombol Aksi</label>
                                <input type="text" name="tombol_url" id="inputTombolUrl" class="form-control" value="<?= htmlspecialchars($cur['tombol_url'] ?? '#dokumen-spmi') ?>" placeholder="#dokumen-spmi atau https://...">
                                <small class="text-muted" style="font-size:0.72rem;">Gunakan <code>#dokumen-spmi</code> untuk scroll ke daftar dokumen.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-dark">Teks Keterangan Samping Footer</label>
                                <input type="text" name="footer_teks" id="inputFooterTeks" class="form-control" value="<?= htmlspecialchars($cur['footer_teks'] ?? 'Standar Mutu UNIKA Soegijapranata') ?>" oninput="updateLivePreview();">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= SITE_URL ?>/admin/spmi-ppepp-card.php?tab=<?= $active_tab ?>" class="btn btn-outline-secondary px-3" style="border-radius:10px;">
                            Batal / Ulangi
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" style="border-radius:10px;background:var(--navy);border:none;">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Tahap <?= $active_tab ?>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Live Interactive Preview Card -->
    <div class="col-lg-5">
        <div class="sticky-top" style="top:90px;z-index:10;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold small text-uppercase text-muted" style="letter-spacing:0.5px;">
                    <i class="bi bi-eye-fill me-1 text-primary"></i> Pratinjau Tampilan Card Langsung:
                </span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1" style="font-size:0.72rem;">
                    Live Interactive Preview
                </span>
            </div>

            <!-- Card Visual Representation (Mirrors Front-end Exactly) -->
            <div id="previewCard" style="background:#ffffff;border:1px solid #E2E8F0;border-left:5px solid <?= htmlspecialchars($cur['warna'] ?? '#C0392B') ?>;border-radius:16px;padding:1.4rem 1.5rem;box-shadow:0 10px 30px rgba(10,25,47,0.08);transition:all 0.2s ease;">
                
                <!-- Header Badge & Prev/Next Placeholder -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2" style="border-bottom:1px solid #F1F5F9;">
                    <span id="prevBadge" style="font-size:0.72rem;font-weight:800;letter-spacing:0.5px;color:<?= htmlspecialchars($cur['warna'] ?? '#C0392B') ?>;background:rgba(192, 57, 43, 0.08);padding:0.3rem 0.75rem;border-radius:20px;text-transform:uppercase;">
                        <?= htmlspecialchars($cur['badge_teks'] ?? 'TAHAP ' . $active_tab . ' DARI 5 • SIKLUS SPMI') ?>
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.72rem;border-radius:6px;" disabled>
                            &larr; Prev
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.72rem;border-radius:6px;" disabled>
                            Next &rarr;
                        </button>
                    </div>
                </div>

                <!-- Judul -->
                <h3 id="prevJudul" style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.2rem;margin-bottom:0.5rem;line-height:1.35;">
                    <?= htmlspecialchars($cur['judul'] ?? '') ?>
                </h3>

                <!-- Deskripsi -->
                <p id="prevDeskripsi" style="font-size:0.86rem;color:#334155;line-height:1.6;margin-bottom:0.85rem;">
                    <?= nl2br(htmlspecialchars($cur['deskripsi'] ?? '')) ?>
                </p>

                <!-- Prosedur Operasional -->
                <div class="mb-3">
                    <div id="prevProsedurLabel" style="font-family:var(--font-heading);font-weight:700;font-size:0.78rem;color:var(--navy);margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.5px;">
                        <i class="bi bi-check2-circle me-1" id="prevCheckIcon" style="color:<?= htmlspecialchars($cur['warna'] ?? '#C0392B') ?>;"></i> <?= htmlspecialchars($cur['prosedur_label'] ?? 'PROSEDUR OPERASIONAL:') ?>
                    </div>
                    <ul id="prevLangkahList" style="margin:0;padding-left:1.2rem;font-size:0.82rem;color:#64748B;line-height:1.55;">
                        <?php 
                        $init_steps = array_filter(array_map('trim', explode("\n", $cur['langkah_prosedur'] ?? '')));
                        foreach ($init_steps as $step): 
                        ?>
                        <li class="mb-1"><?= htmlspecialchars($step) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Dokumen & Penanggung Jawab -->
                <div class="row g-2 pt-2 mb-2" style="border-top:1px dashed #E2E8F0;">
                    <div class="col-7">
                        <small id="prevDokumenLabel" class="text-muted d-block" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                            <?= htmlspecialchars($cur['dokumen_label'] ?? 'DOKUMEN TERKAIT:') ?>
                        </small>
                        <span id="prevDokumenTeks" style="font-size:0.78rem;color:var(--navy);font-weight:600;line-height:1.4;display:block;">
                            <?= nl2br(htmlspecialchars($cur['dokumen_teks'] ?? '')) ?>
                        </span>
                    </div>
                    <div class="col-5">
                        <small id="prevAktorLabel" class="text-muted d-block" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                            <?= htmlspecialchars($cur['aktor_label'] ?? 'PENANGGUNG JAWAB:') ?>
                        </small>
                        <span id="prevAktorTeks" style="font-size:0.78rem;color:var(--navy);font-weight:600;line-height:1.4;display:block;">
                            <?= nl2br(htmlspecialchars($cur['aktor_teks'] ?? '')) ?>
                        </span>
                    </div>
                </div>

                <!-- Tombol Dokumen & Footer -->
                <div class="mt-2 pt-2 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-top:1px solid #F1F5F9;">
                    <span class="btn btn-sm btn-outline-primary fw-bold px-3 py-1" style="font-size:0.76rem;border-radius:6px;pointer-events:none;">
                        <i class="bi bi-file-earmark-text me-1"></i> <span id="prevTombolTeks"><?= htmlspecialchars($cur['tombol_teks'] ?? 'Dokumen Terkait') ?></span>
                    </span>
                    <span id="prevFooterTeks" style="font-size:0.72rem;color:#94A3B8;">
                        <?= htmlspecialchars($cur['footer_teks'] ?? 'Standar Mutu UNIKA Soegijapranata') ?>
                    </span>
                </div>

            </div>

            <!-- Petunjuk Singkat -->
            <div class="mt-3 p-3 bg-white rounded-3 border small text-muted shadow-sm">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                Setiap bagian yang Anda ketik pada formulir di sebelah kiri akan langsung diperbarui secara *real-time* pada kartu pratinjau di atas.
            </div>

        </div>
    </div>

</div>

<script>
function updateLivePreview() {
    var warna = document.getElementById('inputWarna').value || '#C0392B';
    var badgeTeks = document.getElementById('inputBadgeTeks').value || 'TAHAP <?= $active_tab ?> DARI 5 • SIKLUS SPMI';
    var judul = document.getElementById('inputJudul').value || 'Judul Tahap';
    var deskripsi = document.getElementById('inputDeskripsi').value || '';
    var prosedurLabel = document.getElementById('inputProsedurLabel').value || 'PROSEDUR OPERASIONAL:';
    var langkahText = document.getElementById('inputLangkahProsedur').value || '';
    var dokumenLabel = document.getElementById('inputDokumenLabel').value || 'DOKUMEN TERKAIT:';
    var dokumenTeks = document.getElementById('inputDokumenTeks').value || '';
    var aktorLabel = document.getElementById('inputAktorLabel').value || 'PENANGGUNG JAWAB:';
    var aktorTeks = document.getElementById('inputAktorTeks').value || '';
    var tombolTeks = document.getElementById('inputTombolTeks').value || 'Dokumen Terkait';
    var footerTeks = document.getElementById('inputFooterTeks').value || 'Standar Mutu UNIKA Soegijapranata';

    // Update Card Border & Accent Color
    var card = document.getElementById('previewCard');
    if (card) {
        card.style.borderLeftColor = warna;
    }

    // Badge Header
    var badge = document.getElementById('prevBadge');
    if (badge) {
        badge.textContent = badgeTeks;
        badge.style.color = warna;
        badge.style.background = 'rgba(' + hexToRgb(warna) + ', 0.08)';
    }

    // Judul & Deskripsi
    var elJudul = document.getElementById('prevJudul');
    if (elJudul) elJudul.textContent = judul;

    var elDesc = document.getElementById('prevDeskripsi');
    if (elDesc) elDesc.innerHTML = escapeHtml(deskripsi).replace(/\n/g, '<br>');

    // Prosedur
    var elProsLabel = document.getElementById('prevProsedurLabel');
    var elCheckIcon = document.getElementById('prevCheckIcon');
    if (elProsLabel) {
        elProsLabel.innerHTML = '<i class="bi bi-check2-circle me-1" style="color:' + warna + ';"></i> ' + escapeHtml(prosedurLabel);
    }

    var listEl = document.getElementById('prevLangkahList');
    if (listEl) {
        listEl.innerHTML = '';
        var lines = langkahText.split('\n');
        lines.forEach(function(ln) {
            var trimmed = ln.trim();
            if (trimmed !== '') {
                var li = document.createElement('li');
                li.className = 'mb-1';
                li.textContent = trimmed;
                listEl.appendChild(li);
            }
        });
    }

    // Dokumen & Aktor
    var elDokLabel = document.getElementById('prevDokumenLabel');
    if (elDokLabel) elDokLabel.textContent = dokumenLabel;

    var elDokTeks = document.getElementById('prevDokumenTeks');
    if (elDokTeks) elDokTeks.innerHTML = escapeHtml(dokumenTeks).replace(/\n/g, '<br>');

    var elAktorLabel = document.getElementById('prevAktorLabel');
    if (elAktorLabel) elAktorLabel.textContent = aktorLabel;

    var elAktorTeks = document.getElementById('prevAktorTeks');
    if (elAktorTeks) elAktorTeks.innerHTML = escapeHtml(aktorTeks).replace(/\n/g, '<br>');

    // Tombol & Footer
    var elBtnTeks = document.getElementById('prevTombolTeks');
    if (elBtnTeks) elBtnTeks.textContent = tombolTeks;

    var elFootTeks = document.getElementById('prevFooterTeks');
    if (elFootTeks) elFootTeks.textContent = footerTeks;
}

function hexToRgb(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    var num = parseInt(hex, 16);
    if (isNaN(num)) return '192, 57, 43';
    var r = (num >> 16) & 255;
    var g = (num >> 8) & 255;
    var b = num & 255;
    return r + ', ' + g + ', ' + b;
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
