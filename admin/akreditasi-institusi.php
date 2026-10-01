<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Akreditasi Institusi';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $peringkat    = trim($_POST['peringkat'] ?? '');
    $sk           = trim($_POST['sk'] ?? '');
    $teks         = trim($_POST['teks'] ?? '');
    $masa_berlaku = trim($_POST['masa_berlaku'] ?? '');
    $card_badge   = trim($_POST['card_badge'] ?? 'STATUS RESMI');
    $card_title   = trim($_POST['card_title'] ?? 'TERAKREDITASI');
    $card_desc    = trim($_POST['card_desc'] ?? '');

    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_institusi_peringkat', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$peringkat, $peringkat]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_institusi_sk', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$sk, $sk]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_institusi_teks', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$teks, $teks]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_institusi_masa_berlaku', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$masa_berlaku, $masa_berlaku]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_card_badge', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$card_badge, $card_badge]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_card_title', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$card_title, $card_title]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_card_desc', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$card_desc, $card_desc]);

    $upload_dir = __DIR__ . '/../uploads/akreditasi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle File Upload for Sertifikat
    if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sertifikat']['tmp_name'];
        $name = $_FILES['file_sertifikat']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $new_name = saveOrConvertToPdf($tmp, $upload_dir, 'akred_institusi_cert_', $name);
            if ($new_name) {
                $old = getPengaturan('akred_institusi_file');
                if ($old && file_exists($upload_dir . $old) && strpos($old, 'akred_institusi_cert_') === 0) {
                    @unlink($upload_dir . $old);
                    $old_thumb = $upload_dir . pathinfo($old, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                    $old_thumb_jpg = $upload_dir . 'thumb_' . pathinfo($old, PATHINFO_FILENAME) . '.jpg';
                    if (file_exists($old_thumb_jpg)) @unlink($old_thumb_jpg);
                }
                $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_institusi_file', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$new_name, $new_name]);
                getOrGeneratePdfPreview($new_name, 'akreditasi');
            }
        }
    }

    // Handle File Upload for SK
    if (isset($_FILES['file_sk']) && $_FILES['file_sk']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sk']['tmp_name'];
        $name = $_FILES['file_sk']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $new_sk_name = saveOrConvertToPdf($tmp, $upload_dir, 'akred_institusi_sk_', $name);
            if ($new_sk_name) {
                $old_sk = getPengaturan('akred_institusi_sk_file');
                if ($old_sk && file_exists($upload_dir . $old_sk) && strpos($old_sk, 'akred_institusi_sk_') === 0) {
                    @unlink($upload_dir . $old_sk);
                    $old_thumb = $upload_dir . pathinfo($old_sk, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                }
                $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('akred_institusi_sk_file', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$new_sk_name, $new_sk_name]);
                getOrGeneratePdfPreview($new_sk_name, 'akreditasi');
            }
        }
    }

    $_SESSION['flash'] = 'Data Akreditasi Institusi berhasil diperbarui.';
    redirect(SITE_URL . '/admin/akreditasi-institusi.php');
}

$peringkat    = getPengaturan('akred_institusi_peringkat', 'UNGGUL');
$sk           = getPengaturan('akred_institusi_sk', 'Nomor SK: -');
$teks         = getPengaturan('akred_institusi_teks', 'Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).');
$file         = getPengaturan('akred_institusi_file', '2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf');
$file_sk_inst = getPengaturan('akred_institusi_sk_file', '2023_SK_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf');
$masa_berlaku = getPengaturan('akred_institusi_masa_berlaku', '2028-12-31');
$card_badge   = getPengaturan('akred_card_badge', 'STATUS RESMI');
$card_title   = getPengaturan('akred_card_title', 'TERAKREDITASI');
$card_desc    = getPengaturan('akred_card_desc', 'Berdasarkan Keputusan Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT) untuk Institusi Universitas Katolik Soegijapranata.');

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Pengaturan Akreditasi Institusi</div>
    </div>

    <?php if ($flash): ?>
    <div class="alert-lpm alert-success m-3">
        <?= e($flash) ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="lpm-form" style="padding:1.5rem;">
        
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Peringkat Akreditasi Institusi</label>
                <input type="text" name="peringkat" class="form-control" value="<?= e($peringkat) ?>" placeholder="Contoh: UNGGUL">
                <div class="form-text">Nilai peringkat akreditasi utama (misal: UNGGUL).</div>
            </div>
            <div class="col-md-6 mt-3 mt-md-0">
                <label class="form-label fw-bold">Badge Card Status</label>
                <input type="text" name="card_badge" class="form-control" value="<?= e($card_badge) ?>" placeholder="Contoh: STATUS RESMI">
                <div class="form-text">Label pada badge atas kartu status akreditasi.</div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">Judul Card Status Akreditasi</label>
                <input type="text" name="card_title" class="form-control" value="<?= e($card_title) ?>" placeholder="Contoh: TERAKREDITASI">
                <div class="form-text">Judul utama pada kartu status akreditasi.</div>
            </div>
            <div class="col-md-6 mt-3 mt-md-0">
                <label class="form-label fw-bold">Nomor SK &amp; Lembaga</label>
                <input type="text" name="sk" class="form-control" value="<?= e($sk) ?>" placeholder="Contoh: Nomor SK: 123/BAN-PT/Akred/PT/2023">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Keterangan / Deskripsi Card Status</label>
            <textarea name="card_desc" class="form-control" rows="2" placeholder="Teks keterangan pada kartu status akreditasi"><?= e($card_desc) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Masa Berlaku Akreditasi Institusi</label>
            <input type="date" name="masa_berlaku" class="form-control" value="<?= e($masa_berlaku) ?>">
            <div class="form-text">Peringatan otomatis persiapan re-akreditasi akan aktif 2 tahun (730 hari) sebelum tanggal ini.</div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Deskripsi Pendek Halaman/Beranda</label>
            <textarea name="teks" class="form-control" rows="3"><?= e($teks) ?></textarea>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold">File Sertifikat Akreditasi Institusi <span class="badge bg-light text-primary border ms-1" style="font-size:0.75rem;">Otomatis PDF</span></label>
                <input type="file" name="file_sertifikat" id="file_sertifikat_input" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="text-muted d-block mt-1">Format: PDF atau Gambar (JPG, PNG, WebP). Jika gambar, otomatis diubah menjadi dokumen PDF.</small>
                
                <div id="preview_sertifikat_box" class="mt-2 p-2 bg-light rounded d-none" style="border: 2px dashed var(--purple);">
                    <small class="fw-bold text-navy d-block mb-1">Pratinjau Berkas Baru:</small>
                    <div id="preview_sertifikat_content"></div>
                </div>

                <?php if ($file): ?>
                <?php $cert_preview = getOrGeneratePdfPreview($file, 'akreditasi'); ?>
                <div class="mt-3 p-3 bg-light rounded d-flex align-items-center gap-3 border">
                    <?php if ($cert_preview): ?>
                        <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= e($file) ?>" target="_blank">
                            <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($cert_preview) ?>" alt="Sertifikat" class="img-thumbnail" style="max-height:120px;max-width:180px;object-fit:contain;">
                        </a>
                    <?php endif; ?>
                    <div>
                        <div class="fw-bold text-navy small mb-1">Sertifikat Tersimpan:</div>
                        <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= e($file) ?>" target="_blank" class="fw-bold text-success small d-block mb-1">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka File PDF (<?= e($file) ?>)
                        </a>
                        <small class="text-muted">Pratinjau foto sertifikat otomatis aktif di beranda.</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6 mt-3 mt-md-0">
                <label class="form-label fw-bold">File Surat Keputusan (SK) Institusi <span class="badge bg-light text-primary border ms-1" style="font-size:0.75rem;">Otomatis PDF</span></label>
                <input type="file" name="file_sk" id="file_sk_input" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="text-muted d-block mt-1">Format: PDF atau Gambar (JPG, PNG, WebP). Jika gambar, otomatis diubah menjadi dokumen PDF.</small>
                
                <div id="preview_sk_box" class="mt-2 p-2 bg-light rounded d-none" style="border: 2px dashed var(--purple);">
                    <small class="fw-bold text-navy d-block mb-1">Pratinjau Berkas Baru:</small>
                    <div id="preview_sk_content"></div>
                </div>

                <?php if ($file_sk_inst): ?>
                <?php $sk_preview = getOrGeneratePdfPreview($file_sk_inst, 'akreditasi'); ?>
                <div class="mt-3 p-3 bg-light rounded d-flex align-items-center gap-3 border">
                    <?php if ($sk_preview): ?>
                        <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= e($file_sk_inst) ?>" target="_blank">
                            <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($sk_preview) ?>" alt="SK" class="img-thumbnail" style="max-height:120px;max-width:180px;object-fit:contain;">
                        </a>
                    <?php endif; ?>
                    <div>
                        <div class="fw-bold text-navy small mb-1">SK Tersimpan:</div>
                        <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= e($file_sk_inst) ?>" target="_blank" class="fw-bold text-primary small d-block mb-1">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka File (<?= e($file_sk_inst) ?>)
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="border-top pt-4">
            <button type="submit" class="btn-save px-4 py-2">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<!-- PDF.js library for instant client-side rendering of PDF pages -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

function attachPdfLivePreview(inputId, boxId, contentId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        var box = document.getElementById(boxId);
        var content = document.getElementById(contentId);
        content.innerHTML = '';
        box.classList.remove('d-none');
        
        if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
            content.innerHTML = '<div class="text-muted small py-2"><div class="spinner-border spinner-border-sm text-purple me-1"></div> Merender pratinjau halaman PDF...</div>';
            var fileReader = new FileReader();
            fileReader.onload = function() {
                var typedarray = new Uint8Array(this.result);
                pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                    pdf.getPage(1).then(function(page) {
                        var viewport = page.getViewport({scale: 1.0});
                        var canvas = document.createElement('canvas');
                        canvas.className = 'img-thumbnail shadow-sm';
                        canvas.style.maxHeight = '140px';
                        canvas.style.maxWidth = '200px';
                        canvas.style.objectFit = 'contain';
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        page.render({canvasContext: context, viewport: viewport}).promise.then(function() {
                            content.innerHTML = '';
                            var wrap = document.createElement('div');
                            wrap.className = 'd-flex align-items-center gap-2';
                            wrap.appendChild(canvas);
                            var info = document.createElement('div');
                            info.innerHTML = '<span class="badge bg-danger mb-1"><i class="bi bi-file-earmark-pdf"></i> PDF Siap</span><div class="small text-muted fw-bold">' + file.name + '</div>';
                            wrap.appendChild(info);
                            content.appendChild(wrap);
                        });
                    });
                }).catch(function(err) {
                    content.innerHTML = '<span class="text-danger small">Gagal merender preview: ' + err.message + '</span>';
                });
            };
            fileReader.readAsArrayBuffer(file);
        } else if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                content.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="' + evt.target.result + '" class="img-thumbnail" style="max-height:140px;max-width:200px;"><div class="small text-muted"><span class="badge bg-success mb-1">Gambar</span><div>' + file.name + '</div></div></div>';
            };
            reader.readAsDataURL(file);
        }
    });
}

attachPdfLivePreview('file_sertifikat_input', 'preview_sertifikat_box', 'preview_sertifikat_content');
attachPdfLivePreview('file_sk_input', 'preview_sk_box', 'preview_sk_content');
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
