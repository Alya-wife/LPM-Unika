<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$buletin = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM buletin WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $buletin = $stmt->fetch();
    if ($buletin) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Buletin JAMUS' : 'Upload Buletin JAMUS';
$error = '';

/**
 * Extract PDF title from metadata using pure PHP (no extension needed).
 * Reads /Title from the PDF info dictionary.
 */
function extractPdfTitle(string $pdfPath): string {
    $handle = @fopen($pdfPath, 'rb');
    if (!$handle) return '';
    // Read first 64KB – enough to get the info dict near the start
    $chunk = fread($handle, 65536);
    fclose($handle);

    // Try to find /Title in PDF metadata
    // Pattern: /Title (text) or /Title <hex>
    if (preg_match('/\/Title\s*\(([^)]+)\)/u', $chunk, $m)) {
        $title = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $m[1]);
        return trim($title);
    }
    // Try hex-encoded title
    if (preg_match('/\/Title\s*<([0-9A-Fa-f]+)>/u', $chunk, $m)) {
        $hex = $m[1];
        // Check BOM for UTF-16
        $bytes = pack('H*', $hex);
        if (substr($bytes, 0, 2) === "\xFE\xFF") {
            $title = mb_convert_encoding(substr($bytes, 2), 'UTF-8', 'UTF-16BE');
        } else {
            $title = $bytes;
        }
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $title));
    }
    return '';
}

/**
 * Save base64 cover image sent from client-side PDF.js canvas capture.
 */
function saveCoverFromBase64(string $base64Data, string $coverDir): ?string {
    // Strip data URI prefix
    if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/is', $base64Data, $m)) {
        $ext  = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $data = base64_decode($m[2]);
        if ($data === false || strlen($data) < 100) return null;
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        $filename = 'cov_' . uniqid() . '.' . $ext;
        file_put_contents($coverDir . $filename, $data);
        return $filename;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul          = trim($_POST['judul'] ?? '');
    $edisi          = trim($_POST['edisi'] ?? '');
    $deskripsi      = trim($_POST['deskripsi'] ?? '');
    $tanggal_terbit = trim($_POST['tanggal_terbit'] ?? '') ?: null;
    $is_aktif       = isset($_POST['is_aktif']) ? 1 : 0;
    $cover_b64      = trim($_POST['cover_b64'] ?? '');
    $id             = (int)($_POST['id'] ?? 0);

    if (!$judul || !$edisi) {
        $error = 'Judul dan edisi buletin wajib diisi.';
    } else {
        $upload_dir = __DIR__ . '/../uploads/buletin/';
        $cover_dir  = __DIR__ . '/../uploads/buletin/covers/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!is_dir($cover_dir))  mkdir($cover_dir, 0755, true);

        $file_path  = $is_edit ? $buletin['file_path']  : '';
        $cover_path = $is_edit ? $buletin['cover_path'] : null;

        // ── Handle PDF upload ──────────────────────────────────────
        if (!empty($_FILES['file_pdf']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $error = 'File buletin harus dalam format PDF.';
            } elseif ($_FILES['file_pdf']['size'] > 30 * 1024 * 1024) {
                $error = 'Ukuran PDF maksimal 30MB.';
            } else {
                // Remove old PDF
                if ($is_edit && $buletin['file_path'] && file_exists($upload_dir . $buletin['file_path'])) {
                    unlink($upload_dir . $buletin['file_path']);
                }
                $file_path = 'bul_' . uniqid() . '.pdf';
                move_uploaded_file($_FILES['file_pdf']['tmp_name'], $upload_dir . $file_path);

                // Auto-extract title from PDF if judul not customized
                // (judul is already set from POST — user may have overridden the auto-filled value)
            }
        } elseif (!$is_edit) {
            $error = 'File PDF buletin wajib diupload.';
        }

        // ── Handle cover from PDF.js canvas capture ─────────────────
        if (!$error && $cover_b64 && str_starts_with($cover_b64, 'data:image')) {
            // Remove old cover if replacing
            if ($is_edit && $buletin['cover_path'] && file_exists($cover_dir . $buletin['cover_path'])) {
                unlink($cover_dir . $buletin['cover_path']);
            }
            $saved = saveCoverFromBase64($cover_b64, $cover_dir);
            if ($saved) $cover_path = $saved;
        }

        if (!$error) {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE buletin SET judul=?, edisi=?, deskripsi=?, file_path=?, cover_path=?, tanggal_terbit=?, is_aktif=? WHERE id=?");
                $stmt->execute([$judul, $edisi, $deskripsi ?: null, $file_path, $cover_path, $tanggal_terbit, $is_aktif, $id]);
                $_SESSION['flash'] = 'Buletin berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO buletin (judul, edisi, deskripsi, file_path, cover_path, tanggal_terbit, is_aktif) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$judul, $edisi, $deskripsi ?: null, $file_path, $cover_path, $tanggal_terbit, $is_aktif]);
                $_SESSION['flash'] = 'Buletin berhasil diupload.';
            }
            redirect(SITE_URL . '/admin/buletin-list.php');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Load PDF.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
<script>
    // Point PDF.js worker to CDN
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }
</script>

<div class="row justify-content-center">
    <div class="col-xl-8">

        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="buletin-list.php" style="color:var(--text-muted);text-decoration:none;">Buletin JAMUS</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <!-- Main Layout: Form + Live Preview side by side -->
        <div class="row g-4">

            <!-- LEFT: Form -->
            <div class="col-lg-7">
                <div class="card-lpm p-4 p-md-5" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-xl);">

                    <!-- Card Header -->
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3" style="border-bottom:1px solid var(--border);">
                        <div style="width:44px;height:44px;background:linear-gradient(135deg,#7B1FA2,#4A148C);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#fff" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                        <div>
                            <h5 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);margin:0;"><?= $admin_page_title ?></h5>
                            <div style="font-size:0.8rem;color:var(--text-muted);">Sampul & judul otomatis diambil dari PDF</div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="buletinForm">
                        <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?= $buletin['id'] ?>">
                        <?php endif; ?>

                        <!-- Hidden field: base64 cover from PDF.js -->
                        <input type="hidden" name="cover_b64" id="cover_b64">

                        <!-- ── PDF Upload (STEP 1 — shown first) ── -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.88rem;">
                                <?= $is_edit ? '📄 Ganti File PDF (opsional)' : '📄 Upload File PDF Buletin *' ?>
                            </label>
                            <?php if ($is_edit && $buletin['file_path']): ?>
                            <div class="mb-2 p-2" style="background:var(--bg-main);border-radius:6px;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                                <span style="color:var(--text-muted);">File saat ini:</span>
                                <a href="<?= SITE_URL ?>/uploads/buletin/<?= e($buletin['file_path']) ?>" target="_blank" style="color:var(--purple);font-weight:600;">
                                    <?= e($buletin['file_path']) ?>
                                </a>
                                <span style="color:var(--text-muted);">(biarkan kosong jika tidak ingin mengganti)</span>
                            </div>
                            <?php endif; ?>

                            <input type="file" name="file_pdf" id="filePdf" class="form-control" accept=".pdf"
                                   style="border:1.5px solid var(--border);padding:0.75rem 1rem;">
                            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.35rem;">
                                📌 Setelah memilih file, <strong>judul dan sampul akan otomatis terisi</strong> dari PDF.
                            </div>

                            <!-- PDF processing status indicator -->
                            <div id="pdfStatus" style="display:none;margin-top:0.6rem;padding:0.6rem 1rem;background:#EDE7F6;border-radius:8px;font-size:0.82rem;color:#4A148C;font-weight:600;display:none;align-items:center;gap:0.5rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" style="animation:spin 1s linear infinite;flex-shrink:0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span id="pdfStatusText">Memproses PDF...</span>
                            </div>
                            <style>@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }</style>
                        </div>

                        <!-- ── Judul (auto-filled) ── -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.88rem;">
                                Judul Buletin <span style="color:#e53935;">*</span>
                                <span id="judulAutoTag" style="display:none;background:#E8F5E9;color:#2E7D32;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;">✓ Otomatis dari PDF</span>
                            </label>
                            <input type="text" name="judul" id="judulInput" class="form-control" required
                                   placeholder="Judul akan otomatis terisi dari PDF, atau ketik manual..."
                                   value="<?= e($is_edit ? $buletin['judul'] : ($_POST['judul'] ?? '')) ?>"
                                   style="border:1.5px solid var(--border);padding:0.75rem 1rem;">
                        </div>

                        <!-- ── Edisi ── -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.88rem;">
                                Edisi <span style="color:#e53935;">*</span>
                            </label>
                            <input type="text" name="edisi" class="form-control" required
                                   placeholder="Edisi I / 2026 | Vol.3 No.2 / 2025"
                                   value="<?= e($is_edit ? $buletin['edisi'] : ($_POST['edisi'] ?? '')) ?>"
                                   style="border:1.5px solid var(--border);padding:0.75rem 1rem;">
                            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.35rem;">Misal: "Edisi I / 2026", "Vol.3 No.2 / 2025", "September 2026"</div>
                        </div>

                        <!-- ── Deskripsi ── -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.88rem;">Deskripsi Singkat</label>
                            <textarea name="deskripsi" class="form-control" rows="3"
                                      placeholder="Ringkasan topik utama edisi ini..."
                                      style="border:1.5px solid var(--border);padding:0.75rem 1rem;"><?= e($is_edit ? $buletin['deskripsi'] : ($_POST['deskripsi'] ?? '')) ?></textarea>
                        </div>

                        <!-- ── Tanggal Terbit ── -->
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.88rem;">Tanggal Terbit</label>
                            <input type="date" name="tanggal_terbit" class="form-control"
                                   value="<?= e($is_edit ? $buletin['tanggal_terbit'] : ($_POST['tanggal_terbit'] ?? '')) ?>"
                                   style="border:1.5px solid var(--border);padding:0.75rem 1rem;">
                        </div>

                        <!-- ── Status Aktif ── -->
                        <div class="mb-4 p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border:1px solid var(--border);">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_aktif" id="switchAktif"
                                       <?= ($is_edit ? $buletin['is_aktif'] : 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="switchAktif" style="font-weight:700;color:var(--navy);font-size:0.9rem;">
                                    Tampilkan di Knowledge Center
                                </label>
                            </div>
                            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.25rem;margin-left:2.75rem;">
                                Nonaktifkan untuk menyembunyikan tanpa menghapus data.
                            </div>
                        </div>

                        <!-- ── Submit ── -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn-hero-primary" style="border:none;padding:0.75rem 2rem;font-size:0.9rem;cursor:pointer;">
                                <?= $is_edit ? '💾 Simpan Perubahan' : '📤 Upload Buletin' ?>
                            </button>
                            <a href="buletin-list.php" class="btn-hero-secondary" style="padding:0.75rem 1.5rem;font-size:0.9rem;text-decoration:none;background:#fff;color:var(--navy);border-color:var(--border);">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIGHT: Live Cover Preview -->
            <div class="col-lg-5">
                <div class="card-lpm p-4" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-xl);position:sticky;top:90px;">
                    <h6 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);margin-bottom:0.25rem;">
                        Pratinjau Sampul
                    </h6>
                    <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:1.25rem;">Begini tampilan buletin di rak buku:</p>

                    <!-- Book preview (mimics bookshelf card) -->
                    <div class="d-flex justify-content-center" style="padding:1.5rem 1rem 2rem;background:#EEEFF4;border-radius:12px;position:relative;overflow:visible;">
                        <!-- Shelf line -->
                        <div style="position:absolute;bottom:0;left:-4px;right:-4px;height:18px;background:linear-gradient(180deg,#8B6F47,#4A3520);border-radius:0 0 8px 8px;box-shadow:0 4px 12px rgba(74,53,32,0.3);"></div>
                        
                        <!-- Book -->
                        <div style="width:130px;position:relative;">
                            <div id="coverPreviewWrap" style="
                                position:relative;border-radius:2px 8px 8px 2px;overflow:hidden;
                                aspect-ratio:3/4;
                                box-shadow:-3px 0 0 #bbb,-5px 0 0 #aaa, 4px 6px 20px rgba(0,0,0,0.3), 8px 12px 30px rgba(0,0,0,0.15);
                            ">
                                <!-- Spine shadow -->
                                <div style="position:absolute;top:0;left:0;width:12px;height:100%;background:rgba(0,0,0,0.22);z-index:2;"></div>
                                <!-- Shine -->
                                <div style="position:absolute;top:0;left:10%;width:28%;height:100%;background:linear-gradient(105deg,rgba(255,255,255,0.25) 0%,rgba(255,255,255,0) 80%);z-index:3;pointer-events:none;"></div>

                                <?php if ($is_edit && $buletin['cover_path']): ?>
                                <!-- Existing cover -->
                                <img id="coverPreviewImg"
                                     src="<?= SITE_URL ?>/uploads/buletin/covers/<?= e($buletin['cover_path']) ?>"
                                     alt="cover"
                                     style="width:100%;height:100%;object-fit:cover;display:block;">
                                <div id="coverPreviewPlaceholder" style="display:none;width:100%;height:100%;background:linear-gradient(160deg,#4A148C,#7B1FA2);display:none;align-items:center;justify-content:center;flex-direction:column;padding:10px;position:relative;overflow:hidden;">
                                    <div style="position:absolute;bottom:0;left:0;right:0;height:36%;background:repeating-linear-gradient(-45deg,rgba(206,147,216,0.3) 0,rgba(206,147,216,0.3) 2px,transparent 2px,transparent 12px);"></div>
                                    <span style="font-family:var(--font-heading);font-weight:900;color:#fff;font-size:1.1rem;letter-spacing:1px;z-index:1;text-shadow:0 2px 6px rgba(0,0,0,0.4);">JAMUS</span>
                                    <span id="prevEdisiText" style="background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.35);color:#fff;font-size:0.62rem;font-weight:700;padding:2px 8px;border-radius:20px;z-index:1;margin-top:6px;">Edisi</span>
                                </div>
                                <?php else: ?>
                                <!-- Placeholder -->
                                <img id="coverPreviewImg" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;">
                                <div id="coverPreviewPlaceholder" style="width:100%;height:100%;background:linear-gradient(160deg,#4A148C,#7B1FA2);display:flex;align-items:center;justify-content:center;flex-direction:column;padding:10px;position:relative;overflow:hidden;">
                                    <div style="position:absolute;bottom:0;left:0;right:0;height:36%;background:repeating-linear-gradient(-45deg,rgba(206,147,216,0.3) 0,rgba(206,147,216,0.3) 2px,transparent 2px,transparent 12px);"></div>
                                    <span style="font-family:var(--font-heading);font-weight:900;color:#fff;font-size:1.1rem;letter-spacing:1px;z-index:1;text-shadow:0 2px 6px rgba(0,0,0,0.4);">JAMUS</span>
                                    <span id="prevEdisiText" style="background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.35);color:#fff;font-size:0.62rem;font-weight:700;padding:2px 8px;border-radius:20px;z-index:1;margin-top:6px;">Edisi</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Title below book -->
                            <div id="prevTitle" style="margin-top:10px;font-family:var(--font-heading);font-weight:700;font-size:0.72rem;color:var(--navy);text-align:center;line-height:1.3;">
                                <?= $is_edit ? e($buletin['judul']) : 'Judul Buletin' ?>
                            </div>
                            <div id="prevEdisi" style="text-align:center;font-size:0.65rem;color:var(--purple);font-weight:700;margin-top:2px;">
                                <?= $is_edit ? e($buletin['edisi']) : 'Edisi' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Status info -->
                    <div id="coverInfo" class="mt-3" style="font-size:0.78rem;color:var(--text-muted);text-align:center;line-height:1.5;">
                        <?php if ($is_edit && $buletin['cover_path']): ?>
                        ✅ Sampul tersimpan. Upload PDF baru untuk memperbarui.
                        <?php else: ?>
                        ⬆️ Pilih file PDF untuk generate sampul otomatis.
                        <?php endif; ?>
                    </div>

                    <!-- Hidden canvas for PDF rendering -->
                    <canvas id="pdfCanvas" style="display:none;"></canvas>
                </div>
            </div>

        </div><!-- /row -->
    </div>
</div>

<script>
(function () {
    const fileInput   = document.getElementById('filePdf');
    const coverB64    = document.getElementById('cover_b64');
    const judulInput  = document.getElementById('judulInput');
    const judulTag    = document.getElementById('judulAutoTag');
    const pdfStatus   = document.getElementById('pdfStatus');
    const pdfStatusT  = document.getElementById('pdfStatusText');
    const canvas      = document.getElementById('pdfCanvas');
    const previewImg  = document.getElementById('coverPreviewImg');
    const placeholder = document.getElementById('coverPreviewPlaceholder');
    const coverInfo   = document.getElementById('coverInfo');
    const prevTitle   = document.getElementById('prevTitle');
    const prevEdisi   = document.getElementById('prevEdisi');
    const prevEdisiT  = document.getElementById('prevEdisiText');

    // Update live preview from form fields
    document.querySelector('[name="judul"]').addEventListener('input', function () {
        prevTitle.textContent = this.value || 'Judul Buletin';
    });
    document.querySelector('[name="edisi"]').addEventListener('input', function () {
        prevEdisi.textContent = this.value || 'Edisi';
        if (prevEdisiT) prevEdisiT.textContent = this.value || 'Edisi';
    });

    if (!fileInput) return;

    fileInput.addEventListener('change', async function () {
        const file = this.files[0];
        if (!file || file.type !== 'application/pdf') return;

        // Show processing status
        pdfStatus.style.display = 'flex';
        pdfStatusT.textContent = 'Membaca PDF...';
        coverInfo.textContent  = '⏳ Memproses sampul dari halaman pertama PDF...';

        try {
            const arrayBuffer = await file.arrayBuffer();

            // ── Try to extract title from PDF metadata (PDF.js) ──────
            pdfStatusT.textContent = 'Membaca metadata...';
            let pdfTitle = '';
            try {
                const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer.slice(0) });
                const pdf = await loadingTask.promise;

                // Get metadata
                const meta = await pdf.getMetadata().catch(() => null);
                if (meta && meta.info && meta.info.Title && meta.info.Title.trim()) {
                    pdfTitle = meta.info.Title.trim();
                }

                // ── Render page 1 to canvas → generate cover ────────
                pdfStatusT.textContent = 'Merender halaman pertama...';
                const page = await pdf.getPage(1);

                // Render at 2x scale for quality
                const scale    = 2.0;
                const viewport = page.getViewport({ scale });

                canvas.width  = viewport.width;
                canvas.height = viewport.height;

                const ctx = canvas.getContext('2d');
                await page.render({ canvasContext: ctx, viewport }).promise;

                // Crop to portrait 3:4 ratio from top of page
                const targetW = Math.min(canvas.width, canvas.height * 0.75);
                const targetH = targetW / 0.75;

                const outCanvas = document.createElement('canvas');
                outCanvas.width  = 600;  // fixed output width
                outCanvas.height = 800;  // fixed output height (3:4)
                const outCtx = outCanvas.getContext('2d');

                // White background
                outCtx.fillStyle = '#ffffff';
                outCtx.fillRect(0, 0, 600, 800);

                // Draw PDF page scaled to fit
                outCtx.drawImage(canvas, 0, 0, targetW, Math.min(targetH, canvas.height), 0, 0, 600, 800);

                // Export as JPEG (smaller file size)
                const dataUrl = outCanvas.toDataURL('image/jpeg', 0.88);
                coverB64.value = dataUrl;

                // Update preview
                previewImg.src = dataUrl;
                previewImg.style.display = 'block';
                placeholder.style.display = 'none';

                pdfStatus.style.display = 'none';
                coverInfo.innerHTML = '✅ <strong>Sampul berhasil digenerate</strong> dari halaman pertama PDF.';
                coverInfo.style.color = '#2E7D32';

            } catch (pdfErr) {
                console.warn('PDF.js error:', pdfErr);
                pdfStatus.style.display = 'none';
                coverInfo.textContent = '⚠️ Tidak bisa membaca PDF. Sampul placeholder akan digunakan.';
            }

            // ── Fill title if found and field is empty/unchanged ──
            if (pdfTitle) {
                const currentVal = judulInput.value.trim();
                // Auto-fill if empty OR if user hasn't changed from a previous auto-fill
                if (!currentVal || judulInput.dataset.autoFilled === 'true') {
                    judulInput.value = pdfTitle;
                    judulInput.dataset.autoFilled = 'true';
                    prevTitle.textContent = pdfTitle;
                    judulTag.style.display = 'inline';
                }
            } else {
                // No metadata title — hint user
                if (!judulInput.value.trim()) {
                    judulInput.placeholder = 'Judul tidak ditemukan di metadata PDF, silakan isi manual.';
                }
            }

        } catch (err) {
            console.error('Error processing PDF:', err);
            pdfStatus.style.display = 'none';
            coverInfo.textContent = '❌ Gagal memproses PDF. Pastikan file valid.';
            coverInfo.style.color = '#c62828';
        }
    });

    // Clear auto-filled flag if user manually edits judul
    judulInput.addEventListener('input', function () {
        judulInput.dataset.autoFilled = 'false';
        judulTag.style.display = 'none';
    });

})();
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
