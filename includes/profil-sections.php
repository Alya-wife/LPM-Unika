<?php
/**
 * Modular Profil Sections Renderer
 * Digunakan bersama oleh profil.php (halaman publik) dan admin/builder-preview.php (visual builder)
 */
require_once __DIR__ . '/../config/database.php';

function getProfilData() {
    static $data = null;
    if ($data !== null) return $data;

    $db = getDB();
    $data = [];

    $data['tentang_judul'] = getPengaturan('profil_tentang_judul', 'Lembaga Penjaminan Mutu UNIKA');
    $data['tentang_teks1'] = getPengaturan('profil_tentang_teks1', 'Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata adalah unit kerja yang bertanggung jawab dalam merancang, melaksanakan, memantau, dan mengevaluasi Sistem Penjaminan Mutu Internal (SPMI) di lingkungan universitas.');
    $data['tentang_teks2'] = getPengaturan('profil_tentang_teks2', 'LPM berperan sebagai katalisator budaya mutu yang mendorong seluruh unit kerja untuk senantiasa meningkatkan kualitas layanan pendidikan, penelitian, dan pengabdian kepada masyarakat sesuai dengan standar nasional pendidikan tinggi (SN-Dikti) dan regulasi BAN-PT.');

    $data['stat_akr']   = getPengaturan('stat_akreditasi', 'Unggul');
    $stat_prodi_custom  = getPengaturan('stat_prodi', '');
    if ($stat_prodi_custom !== '' && is_numeric($stat_prodi_custom)) {
        $data['stat_prodi'] = (int)$stat_prodi_custom;
    } else {
        $data['stat_prodi'] = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi")->fetchColumn();
    }
    $data['stat_tahun'] = getPengaturan('stat_tahun', '40');

    $data['visi'] = getPengaturan('profil_visi', 'Terwujudnya budaya mutu melalui sistem penjaminan mutu yang mendukung pencapaian visi Universitas Katolik Soegijapranata.');

    if (!function_exists('cleanProfileItem')) {
        function cleanProfileItem($item) {
            return trim(preg_replace('/^(\d+[\.\)]|\-|\•|\*)\s*/u', '', trim($item)));
        }
    }

    $misi_raw    = getPengaturan('profil_misi', '');
    $data['misi_list']   = array_values(array_filter(array_map('cleanProfileItem', explode("\n", $misi_raw))));
    $tujuan_raw  = getPengaturan('profil_tujuan', '');
    $data['tujuan_list'] = array_values(array_filter(array_map('cleanProfileItem', explode("\n", $tujuan_raw))));

    $data['tugas_raw']   = getPengaturan('profil_tugas', 'Sesuai Peraturan Universitas Katolik Soegijapranata No. 01/E.2/Per-UKS/XI/2019 tentang Organisasi dan Tata Kelola Universitas Katolik Soegijapranata, LPM bertugas merencanakan, melaksanakan, mengevaluasi dan mengembangkan sistem penjaminan mutu.');
    $fungsi_raw  = getPengaturan('profil_fungsi', '');
    $data['fungsi_list'] = array_values(array_filter(array_map('cleanProfileItem', explode("\n", $fungsi_raw))));

    $data['tim_lpm_list'] = $db->query("SELECT * FROM tim_lpm WHERE kategori = 'lpm' ORDER BY urutan ASC, id ASC")->fetchAll();
    $data['tim_gpm_list'] = $db->query("SELECT * FROM tim_lpm WHERE kategori = 'gpm' ORDER BY urutan ASC, id ASC")->fetchAll();
    $data['st_map']       = getStrukturLpmMap($data['tim_lpm_list']);

    return $data;
}

function renderProfilSection($type, $block = [], $is_builder = false) {
    $data = getProfilData();
    $bg   = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc   = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    switch ($type) {
        case 'profil_tentang':
            ?>
            <section class="py-5 py-md-6" style="<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <h2 class="section-title mb-4" style="font-family:var(--font-heading);font-size:clamp(1.5rem, 2.8vw, 2.1rem);font-weight:800;color:var(--navy);line-height:1.35;">
                                <?= htmlspecialchars($block['title'] ?? $data['tentang_judul']) ?>
                            </h2>

                            <?php if (!empty($data['tentang_teks1'])): ?>
                            <div class="mb-4" style="color:var(--text-main);font-size:1.025rem;line-height:1.85;">
                                <?php 
                                $paragraphs1 = preg_split('/\r\n\r\n|\n\n/', trim($data['tentang_teks1']));
                                foreach ($paragraphs1 as $p): 
                                    $p_clean = trim($p);
                                    if ($p_clean === '') continue;
                                ?>
                                <p style="margin-bottom:1.15rem;text-align:justify;">
                                    <?= nl2br(htmlspecialchars($p_clean)) ?>
                                </p>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($data['tentang_teks2'])): ?>
                            <div class="p-4 p-md-5 rounded-4 mb-2" style="background:#ffffff;border:1px solid var(--border);border-left:5px solid var(--navy);box-shadow:0 4px 24px rgba(10,25,47,0.04);">
                                <div style="color:var(--text-main);font-size:0.985rem;line-height:1.85;">
                                    <?php 
                                    $paragraphs2 = preg_split('/\r\n\r\n|\n\n/', trim($data['tentang_teks2']));
                                    foreach ($paragraphs2 as $idx => $p): 
                                        $p_clean = trim($p);
                                        if ($p_clean === '') continue;
                                        $is_last = ($idx === count($paragraphs2) - 1);
                                    ?>
                                    <p class="<?= $is_last ? 'mb-0' : 'mb-3' ?>" style="text-align:justify;">
                                        <?= nl2br(htmlspecialchars($p_clean)) ?>
                                    </p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'profil_visi_misi':
            ?>
            <section class="py-5 py-md-6" style="background:var(--bg-white);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Arah & Komitmen') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Visi & Misi LPM UNIKA') ?></h2>
                    </div>

                    <!-- 1. Visi & Misi (2 Kolom Berdampingan Sejajar) -->
                    <div class="row g-4 mb-4 align-items-stretch">
                        <!-- Kolom Kiri: Visi -->
                        <div class="col-lg-6">
                            <div style="background:linear-gradient(135deg, var(--navy), #132D54);border-radius:var(--radius-lg);padding:2.5rem 2rem;box-shadow:0 8px 30px rgba(10,25,47,0.12);height:100%;display:flex;flex-direction:column;justify-content:center;text-align:center;">
                                <div style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:rgba(255,255,255,0.12);border-radius:50%;margin:0 auto 1.25rem;color:#FFD54F;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" width="28" height="28">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </div>
                                <h3 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:800;color:#fff;margin-bottom:1rem;letter-spacing:0.5px;">
                                    VISI LPM
                                </h3>
                                <p style="color:#FFFFFF;font-size:1.125rem;line-height:1.85;margin:0;font-style:italic;font-weight:500;">
                                    "<?= htmlspecialchars($data['visi']) ?>"
                                </p>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Misi -->
                        <div class="col-lg-6">
                            <div class="card-lpm p-4 h-100" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid var(--purple);border-radius:var(--radius-md);box-shadow:0 4px 20px rgba(10,25,47,0.04);">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div style="width:46px;height:46px;background:rgba(106,27,154,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--purple);flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 style="font-family:var(--font-heading);font-size:1.25rem;font-weight:800;color:var(--navy);margin:0;">
                                            MISI LPM
                                        </h4>
                                        <span style="font-size:0.75rem;color:var(--text-muted);">Langkah aksi strategis penjaminan mutu</span>
                                    </div>
                                </div>
                                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.85rem;">
                                    <?php foreach ($data['misi_list'] as $i => $m): ?>
                                    <li style="display:flex;align-items:flex-start;gap:0.85rem;">
                                        <span style="width:28px;height:28px;min-width:28px;background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:0.78rem;font-weight:800;color:#fff;margin-top:2px;">
                                            <?= $i+1 ?>
                                        </span>
                                        <span style="font-size:0.9rem;color:var(--text-main);line-height:1.65;">
                                            <?= htmlspecialchars($m) ?>
                                        </span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Tujuan LPM (Di Bawah Visi & Misi) -->
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card-lpm p-4 p-md-5" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid #1565C0;border-radius:var(--radius-md);box-shadow:0 4px 20px rgba(10,25,47,0.04);">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div style="width:46px;height:46px;background:rgba(21,101,192,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#1565C0;flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 style="font-family:var(--font-heading);font-size:1.25rem;font-weight:800;color:var(--navy);margin:0;">
                                            TUJUAN LPM
                                        </h4>
                                        <span style="font-size:0.75rem;color:var(--text-muted);">Target capaian penjaminan mutu berkelanjutan</span>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <?php foreach ($data['tujuan_list'] as $i => $t): ?>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-3 h-100 d-flex align-items-start gap-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                                            <span style="width:28px;height:28px;min-width:28px;background:linear-gradient(135deg, #1E88E5, #1565C0);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:0.78rem;font-weight:800;color:#fff;margin-top:2px;">
                                                <?= $i+1 ?>
                                            </span>
                                            <span style="font-size:0.9rem;color:var(--text-main);line-height:1.65;">
                                                <?= htmlspecialchars($t) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'profil_tugas_fungsi':
            ?>
            <section class="py-5 py-md-6" style="background:#F8FAFC;border-top:1px solid var(--border);border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>" id="tugas-fungsi">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v1.875m-7.5-3h6.375" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Landasan Operasional') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Tugas dan Fungsi') ?></h2>
                        <p class="section-desc mx-auto">Mandat pelaksanaan dan penyelenggaraan sistem penjaminan mutu Universitas Katolik Soegijapranata.</p>
                    </div>

                    <!-- Box Tugas Utama -->
                    <div class="mb-5">
                        <div style="background:#ffffff;border:1px solid var(--border);border-left:5px solid var(--purple);border-radius:var(--radius-lg);padding:2rem 2.25rem;box-shadow:0 4px 20px rgba(10,25,47,0.04);">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="width:44px;height:44px;background:rgba(106,27,154,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--purple);flex-shrink:0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 style="font-family:var(--font-heading);font-size:1.25rem;font-weight:800;color:var(--navy);margin:0;">
                                        TUGAS UTAMA LPM
                                    </h3>
                                    <span style="font-size:0.75rem;color:var(--text-muted);">Peraturan Universitas Katolik Soegijapranata No. 01/E.2/Per-UKS/XI/2019</span>
                                </div>
                            </div>
                            <p style="color:var(--text-main);font-size:1.025rem;line-height:1.85;margin:0;font-weight:500;">
                                <?= nl2br(htmlspecialchars($data['tugas_raw'])) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Butir Fungsi LPM -->
                    <div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:6px;height:24px;background:var(--purple);border-radius:4px;display:inline-block;"></span>
                                <h4 style="font-family:var(--font-heading);font-size:1.2rem;font-weight:800;color:var(--navy);margin:0;">
                                    Dalam melaksanakan tugas tersebut, LPM menyelenggarakan fungsi:
                                </h4>
                            </div>
                            <span class="badge" style="background:rgba(10,25,47,0.06);color:var(--navy);font-size:0.8rem;padding:0.4rem 0.8rem;border-radius:6px;font-weight:600;">
                                <?= count($data['fungsi_list']) ?> Butir Penyelenggaraan Fungsi
                            </span>
                        </div>

                        <div class="row g-3">
                            <?php foreach ($data['fungsi_list'] as $idx => $f): ?>
                            <div class="col-lg-6">
                                <div class="card-lpm p-3 p-md-4 h-100 d-flex align-items-start gap-3" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:0 2px 10px rgba(10,25,47,0.03);transition:transform 0.18s, box-shadow 0.18s;">
                                    <span style="width:32px;height:32px;min-width:32px;background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:0.85rem;font-weight:800;color:#fff;margin-top:2px;">
                                        <?= $idx + 1 ?>
                                    </span>
                                    <div style="font-size:0.915rem;color:var(--text-main);line-height:1.65;font-weight:500;">
                                        <?= htmlspecialchars($f) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'profil_struktur':
            $st_map       = $data['st_map'];
            $tim_lpm_list = $data['tim_lpm_list'];
            $tim_gpm_list = $data['tim_gpm_list'];
            ?>
            <section class="py-5 py-md-6" id="struktur" style="background:var(--bg-main);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Struktur & Tim') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Struktur Organisasi LPM') ?></h2>
                        <p class="section-desc mx-auto">Bagan struktural dan susunan personel Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata.</p>
                    </div>

                    <!-- 1. Bagan Struktur Organisasi Visual (HTML CSS) -->
                    <div class="mb-5">
                        <style>
                        .org-chart-wrap { overflow-x: auto; padding: 2rem 1rem 1.5rem; background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: 0 4px 20px rgba(10,25,47,0.04); }
                        .org-chart-inner { min-width: 820px; display: flex; flex-direction: column; align-items: center; }
                        .org-box { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.65rem 1.25rem; border-radius: 10px; font-family: var(--font-heading); font-size: 0.82rem; font-weight: 700; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.06); transition: transform 0.15s, box-shadow 0.15s; cursor: default; position: relative; }
                        .org-box:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
                        .org-box-label { font-size: 0.72rem; font-weight: 500; opacity: 0.85; margin-top: 2px; }
                        .org-box-top { background: linear-gradient(135deg, #0A192F, #1E3A8A); color: #fff; border: 2px solid #1E3A8A; min-width: 200px; }
                        .org-box-wr { background: linear-gradient(135deg, #1E3A8A, #1565C0); color: #fff; border: 2px solid #1565C0; min-width: 220px; }
                        .org-box-ka { background: linear-gradient(135deg, #7B1FA2, #6A1B9A); color: #fff; border: 2px solid #6A1B9A; min-width: 220px; }
                        .org-box-pusat { background: #EEF2FF; color: #1E3A8A; border: 2px solid #C7D2FE; min-width: 145px; }
                        .org-box-sub { background: #F8FAFC; color: #475569; border: 1.5px dashed #94A3B8; min-width: 135px; }
                        .org-box-staff { background: #FDF4FF; color: #6A1B9A; border: 1.5px solid #E879F9; min-width: 135px; }
                        .org-vline { width: 2px; background: #94A3B8; margin: 0 auto; flex-shrink: 0; }
                        .org-vline-dashed { width: 2px; border-left: 2px dashed #94A3B8; background: transparent; margin: 0 auto; flex-shrink: 0; }
                        .org-branches-row { display: flex; justify-content: space-between; width: 100%; max-width: 900px; position: relative; padding-top: 0; }
                        .org-branch-col { display: flex; flex-direction: column; align-items: center; flex: 1; padding: 0 4px; }
                        
                        /* Sub-Menu Tab Switcher Styles */
                        .tab-personel-btn {
                            border: none;
                            background: transparent;
                            padding: 10px 24px;
                            border-radius: 40px;
                            font-family: var(--font-heading);
                            font-weight: 600;
                            font-size: 0.9rem;
                            color: #64748B;
                            cursor: pointer;
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                        }
                        .tab-personel-btn:hover {
                            color: var(--navy);
                        }
                        .tab-personel-btn.active {
                            background: #ffffff !important;
                            color: var(--navy) !important;
                            font-weight: 800 !important;
                            box-shadow: 0 4px 14px rgba(10, 25, 47, 0.12) !important;
                        }

                        /* Card & Track Personel Styles */
                        .personel-scroll-track {
                            display: flex;
                            gap: 1.25rem;
                            overflow-x: auto;
                            scroll-behavior: smooth;
                            padding-bottom: 0.85rem;
                            scrollbar-width: thin;
                            scrollbar-color: #CBD5E1 #F1F5F9;
                        }
                        .personel-scroll-track::-webkit-scrollbar {
                            height: 6px;
                        }
                        .personel-scroll-track::-webkit-scrollbar-track {
                            background: #F1F5F9;
                            border-radius: 4px;
                        }
                        .personel-scroll-track::-webkit-scrollbar-thumb {
                            background: #CBD5E1;
                            border-radius: 4px;
                        }
                        .personel-scroll-track::-webkit-scrollbar-thumb:hover {
                            background: #94A3B8;
                        }

                        .personel-card {
                            flex: 0 0 235px;
                            width: 235px;
                            background: #ffffff;
                            border: 1px solid #E2E8F0;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
                            display: flex;
                            flex-direction: column;
                            cursor: pointer;
                            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                        }
                        .personel-card:hover {
                            transform: translateY(-4px);
                            box-shadow: 0 12px 24px rgba(10,25,47,0.1);
                        }

                        .personel-card-img-wrap {
                            width: 100%;
                            height: 185px;
                            overflow: hidden;
                            background: #F8FAFC;
                            position: relative;
                        }
                        .personel-card-img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                            display: block;
                            transition: transform 0.3s ease;
                        }
                        .personel-card:hover .personel-card-img {
                            transform: scale(1.04);
                        }
                        .personel-card-placeholder {
                            width: 100%;
                            height: 100%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: #EEF2F6;
                        }

                        .personel-card-body {
                            padding: 1rem;
                            display: flex;
                            flex-direction: column;
                            flex-grow: 1;
                        }
                        .personel-card-name {
                            font-family: var(--font-heading);
                            font-weight: 800;
                            font-size: 0.92rem;
                            color: var(--navy);
                            line-height: 1.35;
                            margin-bottom: 0.5rem;
                            min-height: 2.5rem;
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                        }
                        .personel-card-badge {
                            display: inline-block;
                            padding: 0.35rem 0.65rem;
                            border-radius: 6px;
                            font-size: 0.72rem;
                            font-weight: 700;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-width: 100%;
                        }
                        .personel-card-footer {
                            margin-top: auto;
                            padding-top: 0.65rem;
                            border-top: 1px solid #F1F5F9;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }
                        .personel-card-link {
                            font-size: 0.78rem;
                            font-weight: 700;
                            color: var(--purple);
                        }
                        .personel-card-plus {
                            font-size: 1rem;
                            font-weight: 800;
                            color: var(--purple);
                            line-height: 1;
                        }

                        .btn-scroll-personel {
                            width: 34px;
                            height: 34px;
                            border-radius: 50%;
                            background: #ffffff;
                            border: 1px solid #CBD5E1;
                            color: #475569;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
                            cursor: pointer;
                            transition: all 0.2s ease;
                        }
                        .btn-scroll-personel:hover {
                            background: #F8FAFC;
                            border-color: #94A3B8;
                            color: var(--navy);
                        }
                        </style>

                        <div class="org-chart-wrap">
                            <div class="org-chart-inner">
                                <!-- LEVEL 1: Rektorat -->
                                <div class="org-box org-box-top" title="<?= htmlspecialchars($st_map['rektor']) ?>">
                                    Rektorat
                                    <div class="org-box-label"><?= htmlspecialchars($st_map['rektor']) ?></div>
                                </div>
                                <div class="org-vline" style="height:24px;"></div>

                                <!-- LEVEL 2: WR SDM TK -->
                                <div class="org-box org-box-wr" title="<?= htmlspecialchars($st_map['wr1']) ?>">
                                    Wakil Rektor SDM &amp; TK
                                    <div class="org-box-label"><?= htmlspecialchars($st_map['wr1']) ?></div>
                                </div>
                                <div class="org-vline" style="height:24px;"></div>

                                <!-- LEVEL 3: Kepala LPM -->
                                <div class="org-box org-box-ka" title="<?= htmlspecialchars($st_map['kepala_lpm']) ?>">
                                    Kepala LPM
                                    <div class="org-box-label"><?= htmlspecialchars($st_map['kepala_lpm']) ?></div>
                                </div>
                                <div class="org-vline" style="height:20px;"></div>

                                <!-- Branches -->
                                <div class="org-branches-row">
                                    <div style="position:absolute;top:0;left:10%;right:10%;height:2px;background:#94A3B8;"></div>
                                    <!-- Branch 1: Tenaga Ahli -->
                                    <div class="org-branch-col">
                                        <div class="org-vline" style="height:20px;"></div>
                                        <div class="org-box org-box-staff" title="<?= htmlspecialchars($st_map['tenaga_ahli']) ?>">
                                            Tenaga Ahli
                                            <div class="org-box-label"><?= htmlspecialchars($st_map['tenaga_ahli']) ?></div>
                                        </div>
                                    </div>
                                    <!-- Branch 2: Sekretaris + Staf TU -->
                                    <div class="org-branch-col">
                                        <div class="org-vline" style="height:20px;"></div>
                                        <div class="org-box org-box-pusat" title="<?= htmlspecialchars($st_map['sekretaris']) ?>">
                                            Sekretaris LPM
                                            <div class="org-box-label"><?= htmlspecialchars($st_map['sekretaris']) ?></div>
                                        </div>
                                        <div class="org-vline" style="height:20px;"></div>
                                        <div class="org-box org-box-staff" title="<?= htmlspecialchars($st_map['staf_tu']) ?>">
                                            Staf Tata Usaha
                                            <div class="org-box-label"><?= htmlspecialchars($st_map['staf_tu']) ?></div>
                                        </div>
                                    </div>
                                    <!-- Branch 3: Ka. PSPM -->
                                    <div class="org-branch-col">
                                        <div class="org-vline" style="height:20px;"></div>
                                        <div class="org-box org-box-pusat" title="<?= htmlspecialchars($st_map['ka_ppspm']) ?>">
                                            Ka. Pusat PSPM
                                            <div class="org-box-label"><?= htmlspecialchars($st_map['ka_ppspm']) ?></div>
                                        </div>
                                    </div>
                                    <!-- Branch 4: Ka. AMI -->
                                    <div class="org-branch-col">
                                        <div class="org-vline" style="height:20px;"></div>
                                        <div class="org-box org-box-pusat" title="<?= htmlspecialchars($st_map['ka_ami']) ?>">
                                            Ka. Pusat AMI
                                            <div class="org-box-label"><?= htmlspecialchars($st_map['ka_ami']) ?></div>
                                        </div>
                                        <div class="org-vline org-vline-dashed" style="height:20px;"></div>
                                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                                            <div class="org-box org-box-sub" title="Gugus Penjaminan Mutu (GPM) Fakultas berada di bawah koordinasi Ka. Pusat AMI" style="background:#F0FDF4;border:1.5px solid #86EFAC;color:#166534;">
                                                Gugus Penjaminan Mutu
                                                <div class="org-box-label">(GPM Fakultas)</div>
                                            </div>
                                            <div class="org-box org-box-sub" title="Tim Auditor Mutu Internal">
                                                Auditor Mutu Internal
                                                <div class="org-box-label">Tim Auditor</div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Branch 5: Ka. Pemeringkatan -->
                                    <div class="org-branch-col">
                                        <div class="org-vline" style="height:20px;"></div>
                                        <div class="org-box org-box-pusat" title="<?= htmlspecialchars($st_map['ka_pemeringkatan']) ?>">
                                            Ka. Pusat Pemeringkatan
                                            <div class="org-box-label"><?= htmlspecialchars($st_map['ka_pemeringkatan']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Susunan Personel & Uraian Tugas -->
                    <div class="text-center mb-4">
                        <h3 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:800;color:var(--navy);margin-bottom:0.4rem;">
                            Susunan Personel &amp; Tanggung Jawab
                        </h3>
                        <p style="font-size:0.88rem;color:var(--text-muted);margin-bottom:1.5rem;">
                            Daftar lengkap seluruh pengelola penjaminan mutu universitas dan gugus mutu fakultas.
                        </p>

                        <!-- Sub-Menu Tab Switcher -->
                        <div class="d-flex justify-content-center mb-4">
                            <div style="background:#F1F5F9;padding:6px;border-radius:50px;display:inline-flex;gap:4px;box-shadow:inset 0 1px 3px rgba(0,0,0,0.05);max-width:100%;">
                                <button type="button" class="tab-personel-btn active" id="btn-tab-personel-lpm" onclick="switchPersonelTab('lpm')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                        <path d="M4.5 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM14.25 8.625a3.375 3.375 0 1 1 6.75 0 3.375 3.375 0 0 1-6.75 0ZM1.5 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.633 13.067 13.067 0 0 1-6.761 1.87 13.067 13.067 0 0 1-6.76-1.87.75.75 0 0 1-.364-.633l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 0 1-.233.96 10.088 10.088 0 0 0 5.06-1.604.75.75 0 0 0 .424-.675v-.054a5.25 5.25 0 0 0-5.25-5.25h-.54a7.87 7.87 0 0 1 .54 6.479Z" />
                                    </svg>
                                    1. Personel LPM UNIKA Soegijapranata (<?= count($tim_lpm_list) ?>)
                                </button>
                                <button type="button" class="tab-personel-btn" id="btn-tab-personel-gpm" onclick="switchPersonelTab('gpm')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                        <path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.838-.46a9.75 9.75 0 0 1 4.725.334l.213.076a8.25 8.25 0 0 0 4.004.283l2.844-.711a.75.75 0 0 1 .926.728v11.54a.75.75 0 0 1-.57.73l-2.458.614a9.75 9.75 0 0 1-4.725-.333l-.213-.077a8.25 8.25 0 0 0-4.004-.283L4.5 16.4v4.85a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                                    </svg>
                                    2. Gugus Penjaminan Mutu (GPM) Fakultas (<?= count($tim_gpm_list) ?>)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 1: LPM (Grid Muncul Semua Kebawah Tanpa Scroll Samping) -->
                    <div id="tab-content-personel-lpm">
                        <div class="row g-4 justify-content-center">
                            <?php foreach ($tim_lpm_list as $t): 
                                $jab = strtolower($t['jabatan'] ?? '');
                                if (strpos($jab, 'kepala') !== false) {
                                    $badge_style = 'background:#0A192F;color:#ffffff;';
                                } elseif (strpos($jab, 'sekretaris') !== false) {
                                    $badge_style = 'background:#1E3A8A;color:#ffffff;';
                                } elseif (strpos($jab, 'staf') !== false || strpos($jab, 'staff') !== false || strpos($jab, 'administrasi') !== false) {
                                    $badge_style = 'background:#FCE7F3;color:#BE185D;';
                                } else {
                                    $badge_style = 'background:#F3E8FF;color:#7E22CE;';
                                }
                            ?>
                            <?php 
                                $foto_url = getTimFotoUrl($t['foto'] ?? '');
                            ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                                <div class="personel-card h-100 w-100" onclick="openPersonelModal(this)" data-nama="<?= htmlspecialchars($t['nama']) ?>" data-jabatan="<?= htmlspecialchars($t['jabatan']) ?>" data-foto="<?= htmlspecialchars($foto_url) ?>" data-deskripsi="<?= htmlspecialchars($t['deskripsi'] ?? '') ?>" data-tanggungjawab="<?= htmlspecialchars($t['tanggung_jawab'] ?? '') ?>">
                                    <div class="personel-card-img-wrap">
                                        <?php if (!empty($foto_url)): ?>
                                            <img src="<?= htmlspecialchars($foto_url) ?>" alt="<?= htmlspecialchars($t['nama']) ?>" class="personel-card-img" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'personel-card-placeholder\'><i class=\'bi bi-person text-muted\' style=\'font-size:3.5rem;\'></i></div>';">
                                        <?php else: ?>
                                            <div class="personel-card-placeholder">
                                                <i class="bi bi-person text-muted" style="font-size:3.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="personel-card-body">
                                        <h6 class="personel-card-name" title="<?= htmlspecialchars($t['nama']) ?>"><?= htmlspecialchars($t['nama']) ?></h6>
                                        <div class="mb-3">
                                            <span class="personel-card-badge" style="<?= $badge_style ?>" title="<?= htmlspecialchars($t['jabatan']) ?>">
                                                <?= htmlspecialchars($t['jabatan']) ?>
                                            </span>
                                        </div>
                                        <div class="personel-card-footer">
                                            <span class="personel-card-link">Rincian Tugas &rarr;</span>
                                            <span class="personel-card-plus">+</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Tab 2: GPM (Grid Muncul Semua Kebawah Tanpa Scroll Samping & Block Penjelasan Tugas) -->
                    <div id="tab-content-personel-gpm" style="display:none;">
                        <?php
                        $gpm_pengantar = getPengaturan('gpm_narasi_pengantar', "Gugus Penjaminan Mutu merupakan organ fakultas yang dipimpin oleh seorang koordinator.\nDan jumlah anggota Gugus Penjaminan Mutu ditetapkan oleh Dekan dengan mempertimbangkan jumlah program studi yang dikelola.");
                        $gpm_tugas = getPengaturan('gpm_narasi_tugas', "Membantu Dekan dalam melaksanakan sistem penjaminan mutu pada Fakultas dan Program Studi.");
                        $gpm_fungsi_raw = getPengaturan('gpm_narasi_fungsi', "Pemberian saran dan rekomendasi kepada Dekan dalam perumusan kebijakan pengembangan pelaksanaan SPMI pada tingkat Fakultas\nPengoordinasian pelaksanaan SPMI pada Fakultas maupun Program Studi sesuai peraturan perundang-undangan maupun peraturan yang berlaku di Universitas\nFasilitasi penyusunan prosedur mutu, instruksi kerja maupun dokumen mutu lainnya dalam pelaksanaan SPMI\nPenyusunan instrumen monitoring dan evaluasi yang bersifat khusus untuk Fakultas dan/atau Program Studi\nMonitoring dan evaluasi pelaksanaan SPMI pada Fakultas maupun Program Studi\nPengoordinasian dalam pelaksanaan proses akreditasi Program Studi\nPengoordinasian dengan LPM dalam pelaksanaan SPMI pada Fakultas maupun pelaksanaan proses akreditasi Program Studi\nPengoordinasian pembukaan program pendidikan baru dalam memenuhi akreditasi minimal\nPelaporan pelaksanaan sistem penjaminan mutu tingkat Fakultas kepada Rektor melalui LPM.");
                        $gpm_dasar_hukum = getPengaturan('gpm_narasi_dasar_hukum', 'Buku Organisasi & Tata Kelola | Unika Soegijapranata (Pasal 31)');

                        $gpm_fungsi_items = [];
                        foreach (explode("\n", $gpm_fungsi_raw) as $f_line) {
                            $f_line = trim($f_line);
                            if ($f_line !== '') {
                                $clean_item = preg_replace('/^\d+[\.\)]\s*/', '', $f_line);
                                $gpm_fungsi_items[] = $clean_item;
                            }
                        }
                        ?>

                        <!-- Block Penjelasan Tugas & 9 Fungsi Gugus Penjaminan Mutu (GPM) Fakultas -->
                        <div class="card p-4 p-md-5 mb-4 border-0 shadow-sm" style="background:#ffffff;border:1px solid #E2E8F0;border-top:4px solid #16A34A;border-radius:18px;box-shadow:0 6px 24px rgba(10,25,47,0.05);">
                            
                            <!-- Header & Narasi Pengantar GPM -->
                            <div class="d-flex align-items-start gap-3 mb-4">
                                <div style="width:52px;height:52px;border-radius:14px;background:rgba(22,163,74,0.12);color:#16A34A;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                        <h4 class="fw-bold m-0" style="color:var(--navy);font-family:var(--font-heading);font-size:1.25rem;">
                                            Gugus Penjaminan Mutu (GPM) Fakultas
                                        </h4>
                                        <?php if ($gpm_dasar_hukum): ?>
                                        <span class="badge" style="background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;font-size:0.78rem;font-weight:700;padding:6px 12px;border-radius:20px;">
                                            <i class="bi bi-book-half me-1"></i><?= htmlspecialchars($gpm_dasar_hukum) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="color:#475569;font-size:0.94rem;line-height:1.75;margin:0;">
                                        <?= nl2br(htmlspecialchars($gpm_pengantar)) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Tugas Gugus Penjaminan Mutu (Highlight Box) -->
                            <div class="p-3 px-4 mb-4" style="background:linear-gradient(135deg, #F0FDF4, #DCFCE7);border:1px solid #BBF7D0;border-left:5px solid #16A34A;border-radius:12px;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi bi-check2-circle text-success fs-5"></i>
                                    <h6 class="m-0 fw-bold" style="color:#14532D;font-family:var(--font-heading);font-size:0.98rem;">
                                        Tugas Gugus Penjaminan Mutu
                                    </h6>
                                </div>
                                <p class="m-0" style="color:#166534;font-size:0.92rem;line-height:1.7;padding-left:1.75rem;">
                                    <?= nl2br(htmlspecialchars($gpm_tugas)) ?>
                                </p>
                            </div>

                            <!-- 9 Butir Fungsi Gugus Penjaminan Mutu -->
                            <div>
                                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:var(--navy);font-family:var(--font-heading);font-size:1rem;">
                                    <i class="bi bi-list-stars text-primary fs-5"></i>
                                    Fungsi Gugus Penjaminan Mutu (Pasal 31)
                                </h6>
                                <div class="row g-3">
                                    <?php foreach ($gpm_fungsi_items as $idx => $f_item): ?>
                                    <div class="col-lg-6">
                                        <div class="p-3 h-100 d-flex align-items-start gap-3" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;transition:all 0.2s ease;">
                                            <div style="width:28px;height:28px;border-radius:8px;background:#16A34A;color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:0.82rem;font-weight:800;flex-shrink:0;">
                                                <?= $idx + 1 ?>
                                            </div>
                                            <div style="font-size:0.875rem;color:#334155;line-height:1.65;">
                                                <?= htmlspecialchars($f_item) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>

                        <?php if (!empty($tim_gpm_list)): ?>
                        <div class="row g-4 justify-content-center">
                            <?php foreach ($tim_gpm_list as $t): 
                                $badge_style = 'background:rgba(21,101,192,0.1);color:#1565C0;font-weight:700;';
                            ?>
                            <?php 
                                $foto_url = getTimFotoUrl($t['foto'] ?? '');
                            ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                                <div class="personel-card h-100 w-100" style="cursor:default;">
                                    <div class="personel-card-img-wrap">
                                        <?php if (!empty($foto_url)): ?>
                                            <img src="<?= htmlspecialchars($foto_url) ?>" alt="<?= htmlspecialchars($t['nama']) ?>" class="personel-card-img" loading="lazy" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'personel-card-placeholder\'><i class=\'bi bi-person text-muted\' style=\'font-size:3.5rem;\'></i></div>';">
                                        <?php else: ?>
                                            <div class="personel-card-placeholder">
                                                <i class="bi bi-person text-muted" style="font-size:3.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="personel-card-body text-center">
                                        <h6 class="personel-card-name" style="min-height:auto;" title="<?= htmlspecialchars($t['nama']) ?>"><?= htmlspecialchars($t['nama']) ?></h6>
                                        <div class="mt-2">
                                            <span class="personel-card-badge" style="<?= $badge_style ?>" title="<?= htmlspecialchars($t['jabatan']) ?>">
                                                <?= htmlspecialchars($t['jabatan']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <div class="text-center p-4 text-muted w-100">Belum ada data personel GPM Fakultas yang dimasukkan.</div>
                        <?php endif; ?>
                    </div>

                    <script>
                    if (typeof window.switchPersonelTab === 'undefined') {
                        window.switchPersonelTab = function(tabName) {
                            var btnLpm = document.getElementById('btn-tab-personel-lpm');
                            var btnGpm = document.getElementById('btn-tab-personel-gpm');
                            var tabLpm = document.getElementById('tab-content-personel-lpm');
                            var tabGpm = document.getElementById('tab-content-personel-gpm');
                            if (!tabLpm || !tabGpm) return;
                            if (tabName === 'gpm') {
                                if (btnLpm) btnLpm.classList.remove('active');
                                if (btnGpm) btnGpm.classList.add('active');
                                tabLpm.style.display = 'none';
                                tabGpm.style.display = 'block';
                            } else {
                                if (btnGpm) btnGpm.classList.remove('active');
                                if (btnLpm) btnLpm.classList.add('active');
                                tabGpm.style.display = 'none';
                                tabLpm.style.display = 'block';
                            }
                        };
                    }
                    if (typeof window.scrollPersonelTrack === 'undefined') {
                        window.scrollPersonelTrack = function(tabKey, direction) {
                            var track = document.getElementById('track-personel-' + tabKey);
                            if (track) {
                                var scrollAmount = 480;
                                track.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
                            }
                        };
                    }
                    </script>
                </div>
            </section>
            <?php
            break;
    }
}
