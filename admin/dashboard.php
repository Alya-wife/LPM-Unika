<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Dashboard';
$db = getDB();

$jml_berita     = (int)$db->query("SELECT COUNT(*) FROM berita")->fetchColumn();
$jml_dokumen    = (int)$db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
$jml_slide      = (int)$db->query("SELECT COUNT(*) FROM hero_slides")->fetchColumn();
$jml_tim        = (int)$db->query("SELECT COUNT(*) FROM tim_lpm")->fetchColumn();
$jml_akreditasi = (int)$db->query("SELECT COUNT(*) FROM akreditasi")->fetchColumn();
$jml_pages      = (int)$db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$jml_feedback   = (int)$db->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$jml_unread_fb  = (int)$db->query("SELECT COUNT(*) FROM feedback WHERE status = 'Belum Dibaca'")->fetchColumn();

// Layanan Publik: Kunjungan Studi Banding & Survey Kepuasan Layanan LPM
$jml_kunjungan  = (int)$db->query("SELECT COUNT(*) FROM permohonan_kunjungan WHERE is_archived = 0")->fetchColumn();
$jml_pending_kj = (int)$db->query("SELECT COUNT(*) FROM permohonan_kunjungan WHERE status = 'pending' AND is_archived = 0")->fetchColumn();
$jml_survei     = 0;
$jml_survei_recent = 0;
try {
    $jml_survei = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_respon")->fetchColumn();
    $jml_survei_recent = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_respon WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Exception $e) {}

// Berita terbaru
$berita_recent = $db->query("SELECT * FROM berita ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Welcome Banner -->
<div style="background:linear-gradient(135deg, var(--navy), var(--navy-mid));border-radius:var(--radius-lg);padding:1.75rem 2rem;color:#fff;margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:gap;gap:1.5rem;box-shadow:var(--shadow-sm);">
    <div>
        <h2 style="font-size:1.4rem;font-weight:700;color:#fff;margin-bottom:0.35rem;">Selamat Datang di Panel Kelola LPM UNIKA</h2>
        <p style="font-size:0.875rem;color:rgba(255,255,255,0.75);margin:0;max-width:650px;">
            Kelola seluruh konten website LPM UNIKA mulai dari teks beranda, slider gambar, profil visi-misi, susunan tim, akreditasi program studi, dokumen SPMI, hingga permohonan kunjungan instansi luar.
        </p>
    </div>
    <a href="<?= SITE_URL ?>/" target="_blank" class="btn-hero-secondary" style="white-space:nowrap;padding:0.6rem 1.4rem;font-size:0.85rem;">
        Lihat Website Live
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
    </a>
</div>


<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:rgba(10,25,47,0.08);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--navy)" width="26" height="26">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            <div>
                <div class="admin-stat-num"><?= $jml_slide ?></div>
                <div class="admin-stat-label">Slide Beranda</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#E8F5E9;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2E7D32" width="26" height="26">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div>
                <div class="admin-stat-num"><?= $jml_dokumen ?></div>
                <div class="admin-stat-label">Dokumen SPMI</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#E3F2FD;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="26" height="26">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </div>
            <div>
                <div class="admin-stat-num"><?= $jml_tim ?></div>
                <div class="admin-stat-label">Personel Tim LPM</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#FFF3E0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#E65100" width="26" height="26">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                </svg>
            </div>
            <div>
                <div class="admin-stat-num"><?= $jml_berita ?></div>
                <div class="admin-stat-label">Artikel Berita</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#EDE7F6;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#5E35B1" width="26" height="26">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div>
                <div class="admin-stat-num"><?= $jml_pages ?></div>
                <div class="admin-stat-label">Halaman Dinamis</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="feedback-kunjungan-list.php" style="text-decoration:none;color:inherit;display:block;">
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:#FCE4EC;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C2185B" width="26" height="26">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.601a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                    </svg>
                </div>
                <div>
                    <div class="admin-stat-num">
                        <?= $jml_survei ?>
                        <?php if ($jml_survei_recent > 0): ?>
                        <span style="font-size:0.75rem;color:#C2185B;font-weight:600;">(+<?= $jml_survei_recent ?> minggu ini)</span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-stat-label">Survey Kepuasan Layanan</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="kunjungan-list.php" style="text-decoration:none;color:inherit;display:block;">
            <div class="admin-stat-card">
                <div class="admin-stat-icon" style="background:#E0F2F1;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00796B" width="26" height="26">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                </div>
                <div>
                    <div class="admin-stat-num">
                        <?= $jml_kunjungan ?>
                        <?php if ($jml_pending_kj > 0): ?>
                        <span style="font-size:0.75rem;color:#D97706;font-weight:600;">(<?= $jml_pending_kj ?> pending)</span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-stat-label">Kunjungan Studi Banding</div>
                </div>
            </div>
        </a>
    </div>
</div>

<?php
$vstats = getVisitorStats();
$traffic_datasets = [
    '14d' => $vstats['chart_14d'],
    '30d' => $vstats['chart_30d'],
    '1y'  => $vstats['chart_1y']
];
?>

<!-- Grafik Statistik Pengunjung -->
<div class="row mb-4">
    <div class="col-12">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar flex-wrap gap-3">
                <div>
                    <div class="admin-table-title d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--navy)" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                        <span id="trafficChartTitle">Trafik &amp; Aktivitas Pengunjung Website (14 Hari Terakhir)</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                        Pemantauan jumlah kunjungan publik dan IP unik pengunjung LPM UNIKA.
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                    <!-- Period Filter Tabs -->
                    <div class="btn-group btn-group-sm" role="group" style="background:#F1F5F9;padding:3px;border-radius:8px;">
                        <button type="button" class="btn btn-sm traffic-period-btn active" onclick="switchTrafficPeriod('14d', this)" style="border-radius:6px;font-size:0.78rem;font-weight:600;padding:0.35rem 0.75rem;border:none;">
                            14 Hari
                        </button>
                        <button type="button" class="btn btn-sm traffic-period-btn" onclick="switchTrafficPeriod('30d', this)" style="border-radius:6px;font-size:0.78rem;font-weight:600;padding:0.35rem 0.75rem;border:none;background:transparent;color:#475569;">
                            1 Bulan
                        </button>
                        <button type="button" class="btn btn-sm traffic-period-btn" onclick="switchTrafficPeriod('1y', this)" style="border-radius:6px;font-size:0.78rem;font-weight:600;padding:0.35rem 0.75rem;border:none;background:transparent;color:#475569;">
                            1 Tahun
                        </button>
                    </div>

                    <span class="badge" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;padding:0.45rem 0.75rem;font-size:0.78rem;border-radius:8px;">
                        Hari Ini: <strong><?= number_format($vstats['today_visitors']) ?></strong> IP (<?= number_format($vstats['today_hits']) ?> hits)
                    </span>
                    <span class="badge" style="background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;padding:0.45rem 0.75rem;font-size:0.78rem;border-radius:8px;">
                        Bulan Ini: <strong><?= number_format($vstats['month_visitors']) ?></strong> IP Unik
                    </span>
                    <span class="badge" style="background:#F8FAFC;color:#475569;border:1px solid #CBD5E1;padding:0.45rem 0.75rem;font-size:0.78rem;border-radius:8px;">
                        Total: <strong><?= number_format($vstats['total_hits']) ?></strong> Hits
                    </span>
                </div>
            </div>

            <div style="padding:1.5rem;">
                <div style="position:relative;height:300px;width:100%;">
                    <canvas id="visitorStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Grid -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Pintasan Pengelolaan Halaman Website</div>
            </div>
            <div style="padding:1.5rem;">
                <div class="row g-3">
                    <!-- 1. Slider Beranda -->
                    <div class="col-md-4 col-lg-3">
                        <a href="slider-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E3F2FD;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Hero Slider Beranda</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Ganti gambar &amp; teks banner atas</span>
                        </a>
                    </div>

                    <!-- 2. Capaian & Penghargaan -->
                    <div class="col-md-4 col-lg-3">
                        <a href="penghargaan-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FFF8E1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F57F17" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Capaian &amp; Penghargaan</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Kelola angka statistik &amp; sertifikat</span>
                        </a>
                    </div>

                    <!-- 3. Tentang, Visi & Misi -->
                    <div class="col-md-4 col-lg-3">
                        <a href="profil-edit.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E0F2F1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00796B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Tentang, Visi &amp; Misi</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Visi, misi, tujuan &amp; tupoksi</span>
                        </a>
                    </div>

                    <!-- 4. Struktur Tim LPM -->
                    <div class="col-md-4 col-lg-3">
                        <a href="tim-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#F3E5F5;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--purple-dark)" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Struktur Tim LPM</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Tambah / edit anggota struktur</span>
                        </a>
                    </div>

                    <!-- 5. Akreditasi Program Studi -->
                    <div class="col-md-4 col-lg-3">
                        <a href="akreditasi-prodi-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#EDE7F6;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#5E35B1" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-2.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Akreditasi Program Studi</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Daftar program studi &amp; SK</span>
                        </a>
                    </div>

                    <!-- 5b. Status Akreditasi Nasional -->
                    <div class="col-md-4 col-lg-3">
                        <a href="akreditasi-status-nasional.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FEF3C7;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#B45309" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.143 2.143L15.75 6" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Status Akreditasi Nasional</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Rekapitulasi mutu peringkat &amp; LAM</span>
                        </a>
                    </div>

                    <!-- 6. Dokumen & Regulasi SPMI -->
                    <div class="col-md-4 col-lg-3">
                        <a href="dokumen-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E8F5E9;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2E7D32" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Dokumen &amp; Regulasi SPMI</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Kelola berkas standar &amp; formulir</span>
                        </a>
                    </div>

                    <!-- 7. Jadwal Pelaksanaan AMI -->
                    <div class="col-md-4 col-lg-3">
                        <a href="kalender-ami-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FFF3E0;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#E65100" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Jadwal Kalender AMI</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Agenda siklus audit mutu internal</span>
                        </a>
                    </div>

                    <!-- 8. Kelola Berita & Kegiatan -->
                    <div class="col-md-4 col-lg-3">
                        <a href="berita-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FCE4EC;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C2185B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Berita &amp; Kegiatan</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Publikasi kegiatan, foto &amp; berita</span>
                        </a>
                    </div>

                    <!-- 9a. Layanan: Info Pelatihan -->
                    <div class="col-md-4 col-lg-3">
                        <a href="layanan-form-setting.php?tab=pelatihan-umum" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#EEF2FF;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#4F46E5" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-2.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                            </div>
                            <span class="quick-link-label">1. Info Pelatihan</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Narasi pelatihan &amp; upload brosur</span>
                        </a>
                    </div>

                    <!-- 9b. Layanan: Kunjungan Studi Banding -->
                    <div class="col-md-4 col-lg-3">
                        <a href="kunjungan-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E0F2F1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00796B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <span class="quick-link-label">2. Kunjungan Studi Banding</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Permohonan masuk &amp; form</span>
                        </a>
                    </div>

                    <!-- 9c. Layanan: Survey Kepuasan Layanan -->
                    <div class="col-md-4 col-lg-3">
                        <a href="feedback-kunjungan-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FCE4EC;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C2185B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.601a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">3. Survey Kepuasan Layanan</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Rekap CSAT &amp; butir kuesioner</span>
                        </a>
                    </div>

                    <!-- 10. Halaman Dinamis -->
                    <div class="col-md-4 col-lg-3">
                        <a href="page-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#EDE7F6;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#5E35B1" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Halaman Khusus (Pages)</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Kelola custom pages dinamis</span>
                        </a>
                    </div>

                    <!-- 11. Pengaturan Kontak -->
                    <div class="col-md-4 col-lg-3">
                        <a href="pengaturan.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#ECEFF1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#455A64" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Kontak &amp; Identitas</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Alamat, email, telepon &amp; maps</span>
                        </a>
                    </div>

                    <!-- 12. Buka Website Live -->
                    <div class="col-md-4 col-lg-3">
                        <a href="../index.php" target="_blank" class="quick-link-card text-decoration-none h-100" style="background:#E3F2FD;border-color:#BBDEFB;">
                            <div class="quick-link-icon" style="background:#fff;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                            </div>
                            <span class="quick-link-label" style="color:#0D47A1;">Buka Website Live</span>
                            <span style="font-size:0.75rem;color:#1565C0;text-align:center;">Preview halaman publik &nearr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Berita Table -->
<div class="row g-4">
    <div class="col-12">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Berita &amp; Kegiatan Terkini</div>
                <a href="<?= SITE_URL ?>/admin/berita-list.php" class="btn-action btn-edit" style="text-decoration:none;">Lihat Semua Berita</a>
            </div>
            <?php if (empty($berita_recent)): ?>
            <div style="padding:2.5rem;text-align:center;color:var(--text-muted);font-size:0.875rem;">
                Belum ada berita yang dipublikasikan. <a href="berita-form.php" class="text-purple font-weight-600">Tambah berita sekarang</a>
            </div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Judul Berita</th>
                        <th width="150">Tanggal Publikasi</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($berita_recent as $b): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:0.875rem;color:var(--navy);"><?= e(mb_substr($b['judul'], 0, 70)) ?><?= mb_strlen($b['judul']) > 70 ? '...' : '' ?></div>
                        </td>
                        <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($b['tanggal_publikasi'] ?: $b['created_at']) ?></td>
                        <td>
                            <div style="display:flex;gap:0.4rem;">
                                <a href="berita-form.php?id=<?= $b['id'] ?>" class="btn-action btn-edit">
                                    Edit
                                </a>
                                <a href="berita-list.php?delete=<?= $b['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus berita ini?')">
                                    Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js CDN & Inisialisasi Grafik Pengunjung Interaktif -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
let visitorChartInstance = null;
const trafficDataMap = <?= json_encode($traffic_datasets) ?>;

function switchTrafficPeriod(period, btn) {
    if (!visitorChartInstance || !trafficDataMap[period]) return;

    // Update styling tombol tab
    const container = btn.closest('.btn-group');
    if (container) {
        container.querySelectorAll('.traffic-period-btn').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'transparent';
            b.style.color = '#475569';
            b.style.boxShadow = 'none';
        });
    }
    btn.classList.add('active');
    btn.style.background = '#FFFFFF';
    btn.style.color = 'var(--navy)';
    btn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';

    // Update judul
    const titleEl = document.getElementById('trafficChartTitle');
    if (titleEl) {
        if (period === '14d') {
            titleEl.textContent = 'Trafik & Aktivitas Pengunjung Website (14 Hari Terakhir)';
        } else if (period === '30d') {
            titleEl.textContent = 'Trafik & Aktivitas Pengunjung Website (30 Hari / 1 Bulan Terakhir)';
        } else if (period === '1y') {
            titleEl.textContent = 'Trafik & Aktivitas Pengunjung Website (12 Bulan / 1 Tahun Terakhir)';
        }
    }

    // Update dataset chart
    const dataObj = trafficDataMap[period];
    visitorChartInstance.data.labels = dataObj.labels || [];
    visitorChartInstance.data.datasets[0].data = dataObj.hits || [];
    visitorChartInstance.data.datasets[1].data = dataObj.visitors || [];
    visitorChartInstance.update();
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('visitorStatsChart');
    if (!ctx) return;

    if (typeof Chart === 'undefined') {
        ctx.parentElement.innerHTML = '<div class="alert-lpm alert-warning">Gagal memuat pustaka grafik Chart.js. Mohon periksa koneksi internet Anda.</div>';
        return;
    }

    const initData = trafficDataMap['14d'] || { labels: [], hits: [], visitors: [] };

    visitorChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: initData.labels,
            datasets: [
                {
                    label: 'Total Hits (Pageviews)',
                    data: initData.hits,
                    borderColor: '#1D4ED8',
                    backgroundColor: 'rgba(29, 78, 216, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#1D4ED8',
                    pointHoverRadius: 6,
                    pointRadius: 3.5
                },
                {
                    label: 'Pengunjung Unik (IP)',
                    data: initData.visitors,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#10B981',
                    pointHoverRadius: 6,
                    pointRadius: 3.5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 12,
                            weight: '600'
                        },
                        color: '#475569'
                    }
                },
                tooltip: {
                    backgroundColor: '#0A192F',
                    titleFont: {
                        family: "'Plus Jakarta Sans', sans-serif",
                        size: 13,
                        weight: '700'
                    },
                    bodyFont: {
                        family: "'Plus Jakarta Sans', sans-serif",
                        size: 12
                    },
                    padding: 10,
                    cornerRadius: 8
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748B',
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#F1F5F9'
                    },
                    ticks: {
                        precision: 0,
                        color: '#64748B',
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
