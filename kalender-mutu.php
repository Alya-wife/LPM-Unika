<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Kalender Mutu 2026–2027 – LPM UNIKA';
$meta_desc  = 'Kalender Mutu Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata Tahun Akademik 2026–2027.';

$db = getDB();
$kalender_list = $db->query("SELECT * FROM kalender_mutu WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Jadwal &amp; Agenda Resmi Mutu
        </div>
        <h1 class="page-banner-title">Kalender Mutu 2026–2027</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/knowledge.php">Knowledge</a>
            <span>/</span>
            <span class="current">Kalender Mutu</span>
        </div>
    </div>
</div>

<section class="py-5 py-md-6" style="background:#F8FAFC;">
    <div class="container">
        <!-- Main Poster Card -->
        <div class="card-lpm mb-5" style="border-radius:var(--radius-xl);overflow:hidden;box-shadow:0 15px 40px rgba(10,25,47,0.08);border:1px solid var(--border);">
            <!-- Poster Header (Purple Banner inspired by official poster) -->
            <div style="background:linear-gradient(135deg, #4A148C 0%, #6A1B9A 50%, #7B1FA2 100%);color:#ffffff;padding:2.5rem 2rem;text-align:center;position:relative;overflow:hidden;">
                <!-- Decorative Gold Stripe at bottom -->
                <div style="position:absolute;bottom:0;left:0;right:0;height:5px;background:linear-gradient(90deg, #F59E0B, #FBBF24, #F59E0B);"></div>
                
                <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:68px;height:68px;background:rgba(255,255,255,0.15);border-radius:50%;border:2px solid rgba(255,255,255,0.3);box-shadow:0 6px 20px rgba(0,0,0,0.15);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#FBBF24" width="34" height="34">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                    </svg>
                </div>
                <h2 style="font-family:var(--font-heading);font-weight:900;font-size:clamp(1.7rem, 3.5vw, 2.5rem);color:#FBBF24;margin-bottom:0.4rem;letter-spacing:1px;text-transform:uppercase;">
                    KALENDER MUTU 2026–2027
                </h2>
                <div style="font-family:var(--font-heading);font-weight:700;font-size:1.15rem;color:#ffffff;margin-bottom:0.25rem;">
                    Lembaga Penjaminan Mutu (LPM)
                </div>
                <div style="font-size:0.95rem;color:rgba(255,255,255,0.85);font-weight:500;">
                    Universitas Katolik Soegijapranata
                </div>
            </div>

            <!-- Content Area (Monthly Agenda List) -->
            <div style="background:#ffffff;padding:2.5rem 2rem;">
                <div class="row g-4">
                    <?php if (empty($kalender_list)): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Agenda kalender mutu belum tersedia.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($kalender_list as $item): 
                        $agenda_lines = array_filter(array_map('trim', explode("\n", $item['kegiatan'])));
                        $parts = explode(' ', trim($item['bulan_tahun']), 2);
                        $nama_bulan = $parts[0] ?? $item['bulan_tahun'];
                        $tahun_str  = $parts[1] ?? '';
                    ?>
                    <div class="col-lg-6">
                        <div class="p-3 p-md-4 h-100 d-flex align-items-start gap-3" style="background:#F8FAFC;border:1.5px solid var(--border);border-radius:var(--radius-md);transition:all 0.2s;">
                            <!-- Month Badge -->
                            <div style="min-width:115px;max-width:130px;text-align:center;background:#ffffff;border:1.5px solid rgba(106,27,154,0.18);border-radius:12px;padding:0.75rem 0.5rem;box-shadow:0 4px 12px rgba(106,27,154,0.06);flex-shrink:0;">
                                <div style="font-family:var(--font-heading);font-weight:900;color:var(--navy);font-size:0.95rem;line-height:1.2;letter-spacing:0.5px;">
                                    <?= e($nama_bulan) ?>
                                </div>
                                <?php if ($tahun_str): ?>
                                <div style="font-family:var(--font-heading);font-weight:800;color:var(--purple);font-size:0.85rem;margin-top:2px;">
                                    <?= e($tahun_str) ?>
                                </div>
                                <?php endif; ?>
                                <div style="width:28px;height:28px;margin:6px auto 0;background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 2px 6px rgba(123,31,162,0.3);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Bulleted Activity Points -->
                            <div style="flex-grow:1;padding-top:0.25rem;">
                                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.5rem;">
                                    <?php foreach ($agenda_lines as $act): ?>
                                    <li style="display:flex;align-items:flex-start;gap:8px;font-size:0.875rem;color:var(--text-main);line-height:1.55;font-weight:500;">
                                        <span style="color:var(--purple);font-size:1.1rem;line-height:1;margin-top:2px;">•</span>
                                        <span><?= e($act) ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Footer Note inside card -->
                <div class="mt-4 pt-3 text-center" style="border-top:1px solid var(--border);font-size:0.825rem;color:var(--text-muted);">
                    Jadwal dan agenda di atas dapat disesuaikan sewaktu-waktu sesuai dengan ketetapan Rapat Pimpinan Lembaga Penjaminan Mutu UNIKA.
                </div>
            </div>
        </div>

        <!-- Callout Action Banner -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-4" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 4px 20px rgba(10,25,47,0.04);">
            <div>
                <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.25rem;">
                    Perlu Informasi Lebih Lanjut Mengenai Agenda Mutu?
                </h5>
                <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
                    Hubungi tim sekretariat LPM UNIKA untuk koordinasi jadwal visitasi AMI, penyusunan DED, atau pendampingan akreditasi.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= SITE_URL ?>/kontak.php" class="btn-hero-primary" style="padding:0.65rem 1.4rem;font-size:0.88rem;text-decoration:none;">
                    Hubungi LPM
                </a>
                <a href="<?= SITE_URL ?>/spmi.php" class="btn-hero-secondary" style="background:var(--bg-main);color:var(--navy);border-color:var(--border);padding:0.65rem 1.4rem;font-size:0.88rem;text-decoration:none;">
                    Lihat Dokumen SPMI
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
