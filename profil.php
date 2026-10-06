1<?php
require_once __DIR__ . '/config/database.php';

$view = trim($_GET['view'] ?? '');
if (!in_array($view, ['sejarah', 'visi-misi', 'struktur'])) {
    $view = 'sejarah'; // Default ke Profil dan Sejarah LPM
}

if ($view === 'sejarah') {
    $page_title = 'Profil & Sejarah LPM';
    $meta_desc = 'Profil dan Sejarah Lembaga Penjaminan Mutu UNIKA Soegijapranata – Peran katalisator budaya mutu dan rekam jejak akreditasi.';
    $crumb_label = 'Profil & Sejarah';
} elseif ($view === 'visi-misi') {
    $page_title = 'Visi dan Misi LPM';
    $meta_desc = 'Visi, Misi, Tujuan, dan Tugas Pokok serta 8 Butir Fungsi LPM Universitas Katolik Soegijapranata.';
    $crumb_label = 'Visi dan Misi';
} elseif ($view === 'struktur') {
    $page_title = 'Struktur Organisasi LPM';
    $meta_desc = 'Bagan Struktural Organisasi LPM, Susunan Personel, dan Koordinator Gugus Penjaminan Mutu (GPM) Fakultas UNIKA.';
    $crumb_label = 'Struktur Organisasi';
}

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/profil-sections.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            <?= e(getPengaturan('site_title', 'LPM UNIKA')) ?>
        </div>
        <h1 class="page-banner-title"><?= htmlspecialchars($page_title) ?></h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/profil.php">Profil</a>
            <span>/</span>
            <span class="current"><?= htmlspecialchars($crumb_label) ?></span>
        </div>
    </div>
</div>

<!-- Sub-navigation Pills -->
<div class="bg-white border-bottom py-3 sticky-top"
    style="top:68px;z-index:90;box-shadow:0 2px 12px rgba(10,25,47,0.04);">
    <div class="container">
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <a href="<?= SITE_URL ?>/profil.php?view=sejarah"
                class="nav-pill-lpm <?= $view === 'sejarah' ? 'active' : 'inactive' ?>">
                <i class="bi bi-clock-history"></i> Profil &amp; Sejarah LPM
            </a>
            <a href="<?= SITE_URL ?>/profil.php?view=visi-misi"
                class="nav-pill-lpm <?= $view === 'visi-misi' ? 'active' : 'inactive' ?>">
                <i class="bi bi-compass"></i> Visi dan Misi
            </a>
            <a href="<?= SITE_URL ?>/profil.php?view=struktur"
                class="nav-pill-lpm <?= $view === 'struktur' ? 'active' : 'inactive' ?>">
                <i class="bi bi-diagram-3"></i> Struktur Organisasi
            </a>
        </div>
    </div>
</div>

<?php
// Render seksi sesuai view yang dipilih
if ($view === 'sejarah') {
    renderProfilSection('profil_tentang');
} elseif ($view === 'visi-misi') {
    renderProfilSection('profil_visi_misi');
    renderProfilSection('profil_tugas_fungsi');
} elseif ($view === 'struktur') {
    renderProfilSection('profil_struktur');
}
?>

<!-- Modal Detail Personel & Tanggung Jawab -->
<div class="modal fade" id="modalPersonelDetail" tabindex="-1" aria-labelledby="modalPersonelDetailLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content"
            style="border-radius:var(--radius-lg);border:none;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;">
            <div class="modal-header"
                style="background:linear-gradient(135deg, #0A192F, #132D54);color:#fff;padding:1.5rem 1.75rem;border-bottom:3px solid var(--purple);">
                <div class="d-flex align-items-center gap-3">
                    <div id="modalPersonelFoto"
                        style="width:68px;height:68px;border-radius:50%;overflow:hidden;background:#fff;border:3px solid rgba(255,255,255,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="modalPersonelNama"
                            style="font-family:var(--font-heading);font-weight:800;font-size:1.15rem;margin:0;line-height:1.3;color:#fff;">
                        </h5>
                        <span id="modalPersonelJabatan" class="badge"
                            style="background:#FBBF24;color:#1A1A1A;font-weight:800;font-size:0.78rem;padding:0.35rem 0.7rem;margin-top:0.4rem;display:inline-block;"></span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background:#F8FAFC;">
                <div class="mb-4" id="wrapModalDeskripsi">
                    <h6 class="fw-bold text-navy mb-2"
                        style="font-size:0.92rem;display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="var(--purple)" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                        Uraian Tugas &amp; Peran Jabatan
                    </h6>
                    <div id="modalPersonelDeskripsi" class="p-3 bg-white rounded border"
                        style="font-size:0.9rem;color:var(--text-muted);line-height:1.7;"></div>
                </div>

                <div>
                    <h6 class="fw-bold text-navy mb-2"
                        style="font-size:0.92rem;display:flex;align-items:center;gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="var(--navy)" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Rincian Tanggung Jawab Personel
                    </h6>
                    <div id="modalPersonelTanggungJawab" class="p-3 bg-white rounded border"
                        style="font-size:0.9rem;color:var(--text-main);line-height:1.75;"></div>
                </div>
            </div>
            <div class="modal-footer bg-light" style="padding:0.85rem 1.5rem;">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function scrollPersonelTrack(tabKey, direction) {
        var track = document.getElementById('track-personel-' + tabKey);
        if (track) {
            var scrollAmount = 480;
            track.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
        }
    }

    function switchPersonelTab(tabName) {
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
    }

    function openPersonelModal(el) {
        var nama = el.dataset.nama || '';
        var jabatan = el.dataset.jabatan || '';
        var fotoUrl = el.dataset.foto || '';
        var deskripsi = el.dataset.deskripsi || '';
        var tanggungJawab = el.dataset.tanggungjawab || '';

        var elNama = document.getElementById('modalPersonelNama');
        if (elNama) elNama.innerText = nama;
        var elJabatan = document.getElementById('modalPersonelJabatan');
        if (elJabatan) elJabatan.innerText = jabatan;

        var fotoContainer = document.getElementById('modalPersonelFoto');
        if (fotoContainer) {
            if (fotoUrl) {
                fotoContainer.innerHTML = '<img src="' + fotoUrl + '" style="width:100%;height:100%;object-fit:cover;">';
            } else {
                fotoContainer.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--navy)" width="32" height="32"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>';
            }
        }

        var wrapDesk = document.getElementById('wrapModalDeskripsi');
        var elDesk = document.getElementById('modalPersonelDeskripsi');
        if (wrapDesk && elDesk) {
            if (deskripsi && deskripsi.trim()) {
                wrapDesk.style.display = 'block';
                elDesk.innerText = deskripsi;
            } else {
                wrapDesk.style.display = 'none';
            }
        }

        var tjContainer = document.getElementById('modalPersonelTanggungJawab');
        if (tjContainer) {
            if (tanggungJawab && tanggungJawab.trim()) {
                var lines = tanggungJawab.split('\n');
                var html = '<ul style="margin:0;padding-left:1.2rem;">';
                lines.forEach(function (line) {
                    if (line.trim()) {
                        html += '<li style="margin-bottom:0.4rem;">' + line.replace(/^\d+\.\s*/, '') + '</li>';
                    }
                });
                html += '</ul>';
                tjContainer.innerHTML = html;
            } else {
                tjContainer.innerText = 'Tanggung jawab disusun dan diselaraskan sesuai tupoksi divisi.';
            }
        }

        var modalEl = document.getElementById('modalPersonelDetail');
        if (modalEl && typeof bootstrap !== 'undefined') {
            var myModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            myModal.show();
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>