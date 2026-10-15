<?php
/**
 * Komponen Input Pengaturan Proteksi / Pembatasan Dokumen (File Restriction)
 * 
 * Parameter yang diharapkan:
 * - $res_is_restricted (int/bool): 0 atau 1
 * - $res_max_pages (int): batas halaman pratinjau (default: 3)
 * - $res_allowed_domains (string): domain akun yang diizinkan (default: '@unika.ac.id,@student.unika.ac.id')
 * - $res_prefix (string): opsional prefix untuk name input jika ada
 */

$field_restricted = ($res_prefix ?? '') ? ($res_prefix . '_is_restricted') : 'is_restricted';
$field_pages      = ($res_prefix ?? '') ? ($res_prefix . '_max_preview_pages') : 'max_preview_pages';
$field_domains    = ($res_prefix ?? '') ? ($res_prefix . '_allowed_domains') : 'allowed_domains';
$field_custom     = ($res_prefix ?? '') ? ($res_prefix . '_custom_domains') : 'custom_domains';

$val_restricted = !empty($res_is_restricted) ? 1 : 0;
$val_pages      = isset($res_max_pages) ? (int)$res_max_pages : 3;
$val_domains_str = $res_allowed_domains ?? '@unika.ac.id,@student.unika.ac.id';
$val_domains_arr = array_map('trim', explode(',', strtolower($val_domains_str)));

$has_unika_staff   = in_array('@unika.ac.id', $val_domains_arr, true);
$has_unika_student = in_array('@student.unika.ac.id', $val_domains_arr, true);

// Domain custom di luar unika & student
$custom_domains = [];
foreach ($val_domains_arr as $d) {
    if ($d && $d !== '@unika.ac.id' && $d !== '@student.unika.ac.id') {
        $custom_domains[] = $d;
    }
}
$val_custom = implode(', ', $custom_domains);
$uid = 'res_' . uniqid();
?>

<div class="card border-0 shadow-sm rounded-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0 !important;overflow:hidden;">
    <div class="card-body p-3">
        <!-- Switch Baris Utama: Mau di-hide / dibatasi? -->
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(217, 119, 6, 0.12);color:#d97706;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <label class="form-check-label fw-bold mb-0 text-dark" for="<?= $uid ?>_toggle" style="cursor:pointer;font-size:0.92rem;">
                        <?= htmlspecialchars(($res_title ?? '') ?: 'Batasi / Proteksi Akses File Ini (Hide Berkas Penuh)') ?>
                    </label>
                    <div class="text-muted small" style="font-size:0.78rem;line-height:1.35;">
                        Aktifkan untuk membatasi jumlah halaman yang dapat dilihat publik dan mewajibkan login akun Google resmi untuk membuka dokumen lengkap.
                    </div>
                </div>
            </div>
            <div class="form-check form-switch m-0 flex-shrink-0">
                <input class="form-check-input" type="checkbox" role="switch" id="<?= $uid ?>_toggle" name="<?= $field_restricted ?>" value="1" <?= $val_restricted ? 'checked' : '' ?> onchange="toggleFileRestrictionPanel('<?= $uid ?>')" style="width:2.75rem;height:1.45rem;cursor:pointer;">
            </div>
        </div>

        <!-- Panel Konfigurasi Lanjutan (Tampil jika switch aktif) -->
        <div id="<?= $uid ?>_panel" style="display:<?= $val_restricted ? 'block' : 'none' ?>;margin-top:1.15rem;padding-top:1.15rem;border-top:1px dashed #cbd5e1;">
            <div class="row g-3">
                <!-- 1. Batas Halaman Pratinjau -->
                <div class="col-md-5 col-lg-4">
                    <label class="form-label fw-bold small text-dark mb-1" for="<?= $uid ?>_pages">
                        <i class="bi bi-file-earmark-ruled me-1 text-primary"></i> Batas Pratinjau Gratis
                    </label>
                    <div class="input-group">
                        <input type="number" min="0" max="100" class="form-control text-center fw-bold" id="<?= $uid ?>_pages" name="<?= $field_pages ?>" value="<?= $val_pages ?>" placeholder="Contoh: 3" style="max-width:90px;">
                        <span class="input-group-text bg-white text-muted small">Halaman</span>
                    </div>
                    <div class="form-text" style="font-size:0.75rem;line-height:1.4;">
                        Halaman 1 s/d batas ini dapat dilihat gratis. Halaman berikutnya terkunci dan wajib login. (Isi <strong>0</strong> untuk kunci total).
                    </div>
                </div>

                <!-- 2. Akun Resmi Kampus yang Diizinkan Membuka -->
                <div class="col-md-7 col-lg-8">
                    <label class="form-label fw-bold small text-dark mb-1">
                        <i class="bi bi-shield-check me-1 text-success"></i> Akun / Domain yang Diizinkan Membuka
                    </label>
                    
                    <div class="d-flex flex-wrap gap-2 mb-1">
                        <!-- Checkbox Unika Staff/Dosen -->
                        <label class="form-check m-0 p-2 px-3 rounded-2 border bg-white d-flex align-items-center gap-2 cursor-pointer shadow-none" style="cursor:pointer;" for="<?= $uid ?>_dom_unika">
                            <input class="form-check-input m-0 flex-shrink-0" type="checkbox" id="<?= $uid ?>_dom_unika" name="<?= $field_domains ?>_arr[]" value="@unika.ac.id" <?= $has_unika_staff || !$val_restricted ? 'checked' : '' ?>>
                            <span class="badge bg-primary text-white" style="font-size:0.75rem;">@unika.ac.id</span>
                            <span class="small fw-semibold text-dark">Dosen / Staf</span>
                        </label>

                        <!-- Checkbox Unika Student -->
                        <label class="form-check m-0 p-2 px-3 rounded-2 border bg-white d-flex align-items-center gap-2 cursor-pointer shadow-none" style="cursor:pointer;" for="<?= $uid ?>_dom_student">
                            <input class="form-check-input m-0 flex-shrink-0" type="checkbox" id="<?= $uid ?>_dom_student" name="<?= $field_domains ?>_arr[]" value="@student.unika.ac.id" <?= $has_unika_student || !$val_restricted ? 'checked' : '' ?>>
                            <span class="badge bg-success text-white" style="font-size:0.75rem;">@student.unika.ac.id</span>
                            <span class="small fw-semibold text-dark">Mahasiswa</span>
                        </label>
                    </div>
                    <div class="form-text" style="font-size:0.75rem;">
                        Pilih jenis akun civitas UNIKA yang diizinkan mengakses berkas lengkap.
                    </div>
                </div>

                <!-- 3. Domain Kustom Tambahan (Full Width agar tidak pernah terpotong) -->
                <div class="col-12 pt-2 border-top">
                    <label class="form-label fw-semibold small text-dark mb-1">
                        <i class="bi bi-globe me-1 text-secondary"></i> Domain Tambahan Lainnya (Opsional)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-at"></i></span>
                        <input type="text" class="form-control" name="<?= $field_custom ?>" value="<?= e($val_custom) ?>" placeholder="Contoh: @gmail.com, @kemdikbud.go.id, @mitra.ac.id (pisahkan dengan koma jika lebih dari satu)">
                    </div>
                    <div class="form-text" style="font-size:0.75rem;">
                        Tambahkan domain email lain jika dokumen ini boleh dibuka oleh pihak mitra atau instansi eksternal. Kosongkan jika hanya untuk civitas UNIKA.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof toggleFileRestrictionPanel !== 'function') {
    function toggleFileRestrictionPanel(uid) {
        var toggle = document.getElementById(uid + '_toggle');
        var panel  = document.getElementById(uid + '_panel');
        if (toggle && panel) {
            panel.style.display = toggle.checked ? 'block' : 'none';
        }
    }
}
</script>
