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

// Berita terbaru
$berita_recent = $db->query("SELECT * FROM berita ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Welcome Banner -->
<div style="background:linear-gradient(135deg, var(--navy), var(--navy-mid));border-radius:var(--radius-lg);padding:1.75rem 2rem;color:#fff;margin-bottom:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:gap;gap:1.5rem;box-shadow:var(--shadow-sm);">
    <div>
        <h2 style="font-size:1.4rem;font-weight:700;color:#fff;margin-bottom:0.35rem;">Selamat Datang di Panel Kelola LPM UNIKA</h2>
        <p style="font-size:0.875rem;color:rgba(255,255,255,0.75);margin:0;max-width:650px;">
            Kelola seluruh konten website LPM UNIKA mulai dari teks beranda, slider gambar, sambutan pimpinan, profil visi-misi, susunan tim, akreditasi, hingga dokumen SPMI dengan mudah.
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
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:#FCE4EC;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C2185B" width="26" height="26">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            <div>
                <div class="admin-stat-num">
                    <?= $jml_feedback ?>
                    <?php if ($jml_unread_fb > 0): ?>
                    <span style="font-size:0.75rem;color:#C2185B;font-weight:600;">(<?= $jml_unread_fb ?> baru)</span>
                    <?php endif; ?>
                </div>
                <div class="admin-stat-label">Kotak Masuk Layanan</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Grid -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Menu Pengelolaan Konten Website</div>
            </div>
            <div style="padding:1.5rem;">
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <a href="slider-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E3F2FD;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Slider Beranda</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Ganti gambar &amp; teks slide atas</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="sambutan.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#EDE7F6;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--purple)" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Sambutan Kepala LPM</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Edit nama, foto &amp; isi sambutan</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="profil-edit.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E0F2F1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00796B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Tentang, Visi &amp; Misi</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Kelola visi &amp; misi universitas</span>
                        </a>
                    </div>
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
                    <div class="col-md-4 col-lg-3">
                        <a href="page-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#EDE7F6;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#5E35B1" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Kelola Halaman (Pages)</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Edit teks sitemap dari Drive</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="feedback-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FCE4EC;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C2185B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Kotak Masuk Layanan</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Pesan konsultasi &amp; aspirasi</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="akreditasi-list.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FFF8E1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#F57F17" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Capaian Akreditasi</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Kelola peringkat BAN-PT &amp; LAM</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="dokumen-form.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#E8F5E9;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2E7D32" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Upload Dokumen</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Tambah berkas SPMI &amp; regulasi</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="../layanan.php" target="_blank" class="quick-link-card text-decoration-none h-100" style="background:#E3F2FD;border-color:#BBDEFB;">
                            <div class="quick-link-icon" style="background:#fff;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <span class="quick-link-label" style="color:#0D47A1;">Halaman Publik</span>
                            <span style="font-size:0.75rem;color:#1565C0;text-align:center;">Buka website LPM UNIKA &nearr;</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="berita-form.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#FCE4EC;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C2185B" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Tulis Berita Baru</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Publikasi kegiatan &amp; artikel</span>
                        </a>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <a href="pengaturan.php" class="quick-link-card text-decoration-none h-100">
                            <div class="quick-link-icon" style="background:#ECEFF1;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#455A64" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </div>
                            <span class="quick-link-label">Kontak &amp; Identitas</span>
                            <span style="font-size:0.75rem;color:var(--text-muted);text-align:center;">Alamat, email, telepon, maps</span>
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

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
