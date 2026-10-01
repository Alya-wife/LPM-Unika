<?php
/**
 * Universal PDF Viewer Modal with Gamified "Stage Unlock" & Google OAuth (@unika.ac.id)
 */
$unika_user = getUnikaUser();
$is_unika   = isUnikaLoggedIn();
?>
<!-- Google Identity Services & Mozilla PDF.js -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<style>
/* PDF Modal Styling */
#modalPdfViewer .modal-content {
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 25px 60px rgba(10, 25, 47, 0.45);
    background: #0f172a;
}

.pdf-modal-header {
    background: linear-gradient(135deg, #0A192F 0%, #172A45 100%);
    color: #ffffff;
    padding: 0.85rem 1.4rem;
    border-bottom: 2px solid #FBBF24;
}

.pdf-stage-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.35rem 0.85rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.pdf-stage-badge.locked {
    background: rgba(245, 158, 11, 0.15);
    color: #FBBF24;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.pdf-stage-badge.unlocked {
    background: rgba(16, 185, 129, 0.18);
    color: #34D399;
    border: 1px solid rgba(16, 185, 129, 0.4);
}

/* PDF Scroll Container */
.pdf-viewer-scroll {
    background: #1e293b;
    height: 80vh;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1.5rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
    position: relative;
    scroll-behavior: smooth;
}

/* Page Card Canvas */
.pdf-page-wrapper {
    position: relative;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    border-radius: 8px;
    overflow: hidden;
    max-width: 100%;
    transition: transform 0.2s ease;
}

.pdf-page-number-tag {
    position: absolute;
    bottom: 8px;
    right: 12px;
    background: rgba(15, 23, 42, 0.75);
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    pointer-events: none;
}

/* Professional Restriction Card */
.stage-lock-card {
    width: 100%;
    max-width: 780px;
    background: #0f172a;
    border: 1px solid rgba(251, 191, 36, 0.4);
    border-radius: 12px;
    padding: 2.2rem 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
    text-align: center;
    color: #ffffff;
    margin: 1rem 0 2rem;
    position: relative;
}

.stage-icon-wrap {
    width: 56px;
    height: 56px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: rgba(251, 191, 36, 0.12);
    border: 1.5px solid #FBBF24;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stage-progress-bar {
    background: rgba(255, 255, 255, 0.1);
    height: 6px;
    border-radius: 10px;
    overflow: hidden;
    max-width: 340px;
    margin: 1rem auto;
}

.stage-progress-fill {
    height: 100%;
    width: 40%;
    background: #FBBF24;
    border-radius: 10px;
}

.stage-unlocked-banner {
    width: 100%;
    max-width: 780px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.4);
    border-radius: 8px;
    padding: 0.9rem 1.25rem;
    color: #A7F3D0;
    font-size: 0.88rem;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 0.5rem;
}
</style>

<!-- Modal PDF Viewer -->
<div class="modal fade" id="modalPdfViewer" tabindex="-1" aria-labelledby="modalPdfTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:92vw;margin:1.5rem auto;">
        <div class="modal-content">
            
            <!-- Modal Header -->
            <div class="pdf-modal-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 overflow-hidden" style="min-width:0;max-width:55%;">
                    <div style="width:34px;height:34px;background:rgba(251,191,36,0.15);border:1px solid rgba(251,191,36,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#FBBF24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div style="min-width:0;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span id="modalPdfStageBadge" class="pdf-stage-badge <?= $is_unika ? 'unlocked' : 'locked' ?>">
                                <?= $is_unika ? 'Akses Penuh Civitas UNIKA' : 'Pratinjau Terbatas (Halaman 1–3)' ?>
                            </span>
                            <span id="modalPdfPageCount" style="font-size:0.75rem;color:rgba(255,255,255,0.7);">
                                Memuat...
                            </span>
                        </div>
                        <h5 class="modal-title text-truncate" id="modalPdfTitle" style="font-family:var(--font-heading);font-weight:700;font-size:0.95rem;color:#ffffff;margin:0;">
                            Nama Dokumen
                        </h5>
                    </div>
                </div>

                <!-- Right Action Buttons -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    
                    <!-- User Profile Pill (Jika sudah login) -->
                    <div id="modalUserPill" style="display:<?= $is_unika ? 'inline-flex' : 'none' ?>;align-items:center;gap:8px;background:rgba(255,255,255,0.1);padding:4px 10px;border-radius:50px;font-size:0.78rem;">
                        <span id="modalUserAvatar" style="width:22px;height:22px;border-radius:50%;background:#7C3AED;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:0.7rem;font-weight:700;">
                            <?= $unika_user ? strtoupper(substr($unika_user['name'], 0, 1)) : 'U' ?>
                        </span>
                        <span id="modalUserName" class="text-truncate text-white" style="max-width:130px;">
                            <?= $unika_user ? e($unika_user['name']) : '' ?>
                        </span>
                        <a href="javascript:void(0)" onclick="logoutCivitas()" class="text-warning text-decoration-none fw-bold ms-1" title="Keluar dari akun UNIKA">
                            (Keluar)
                        </a>
                    </div>

                    <!-- Tombol Download -->
                    <a href="javascript:void(0)" id="modalPdfDownload" class="btn btn-sm d-inline-flex align-items-center gap-1" style="background:#FBBF24;color:#4A148C;font-weight:700;border:none;font-size:0.78rem;padding:0.42rem 0.95rem;border-radius:6px;text-decoration:none;" title="Unduh Berkas Lengkap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span id="modalPdfDownloadText"><?= $is_unika ? 'Unduh Lengkap' : 'Unduh Dokumen' ?></span>
                    </a>

                    <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Tutup" style="opacity:0.85;"></button>
                </div>
            </div>

            <!-- Modal Body (PDF Viewer Canvas Container) -->
            <div class="pdf-viewer-scroll" id="pdfViewerScroll">
                
                <!-- Loading State -->
                <div id="pdfLoadingSpinner" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:60vh;color:#ffffff;gap:0.75rem;">
                    <div class="spinner-border text-warning" role="status" style="width:2.5rem;height:2.5rem;"></div>
                    <span style="font-size:0.95rem;font-weight:600;color:rgba(255,255,255,0.85);">Menyiapkan Dokumen...</span>
                </div>

                <!-- Unlocked Banner (Tampil jika user aktif) -->
                <div id="stageUnlockedBanner" class="stage-unlocked-banner" style="display:<?= $is_unika ? 'flex' : 'none' ?>;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#10B981" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div>
                        <strong>Akses Penuh Aktif:</strong> Anda masuk sebagai Civitas Akademika UNIKA (<span id="bannerUserEmail"><?= $unika_user ? e($unika_user['email']) : '' ?></span>). Seluruh halaman dokumen dan fitur unduh berkas lengkap telah tersedia.
                    </div>
                </div>

                <!-- Rendered PDF Pages Container -->
                <div id="pdfPagesContainer" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:1.5rem;"></div>

                <!-- Professional Restriction Card -->
                <div id="stageLockCard" class="stage-lock-card" style="display:none;">
                    <div class="stage-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#FBBF24" width="28" height="28">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>

                    <div style="font-size:0.75rem;font-weight:700;color:#FBBF24;letter-spacing:0.8px;text-transform:uppercase;margin-bottom:0.4rem;">
                        Akses Terbatas: Civitas Akademika UNIKA
                    </div>

                    <h3 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:700;color:#ffffff;margin:0 0 0.6rem;">
                        Autentikasi Akun Resmi untuk Membaca Dokumen Lengkap
                    </h3>

                    <p style="color:rgba(255,255,255,0.75);font-size:0.88rem;max-width:580px;margin:0 auto 1.2rem;line-height:1.6;">
                        Pratinjau publik menampilkan <strong>3 halaman pertama</strong>. Untuk membaca keseluruhan isi dokumen dan mengunduh berkas resmi SPMI ini, silakan masuk menggunakan akun Google resmi Universitas Katolik Soegijapranata (<strong>@unika.ac.id</strong>).
                    </p>

                    <div class="stage-progress-bar">
                        <div class="stage-progress-fill" id="stageProgressFill"></div>
                    </div>
                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.55);margin-bottom:1.5rem;" id="stageLockProgressText">
                        Menampilkan Halaman 1–3
                    </div>

                    <!-- Tempat Tombol Google Sign In GIS -->
                    <div class="d-flex flex-column align-items-center gap-2">
                        <div id="gsiStageBtn"></div>
                        <div id="unikaAuthAlert" style="display:none;margin-top:0.8rem;max-width:540px;background:rgba(239,68,68,0.2);border:1px solid #EF4444;color:#FCA5A5;border-radius:10px;padding:0.75rem 1rem;font-size:0.85rem;line-height:1.5;"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Konfigurasi Global Client ID & Status
window.UNIKA_CONFIG = {
    clientId: "<?= GOOGLE_CLIENT_ID ?>",
    siteUrl:  "<?= SITE_URL ?>",
    isLoggedIn: <?= $is_unika ? 'true' : 'false' ?>,
    currentUser: <?= json_encode($unika_user) ?>
};

// Konfigurasi PDF.js Worker
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

let currentPdfDoc = null;
let currentPdfUrl = '';
let currentPdfTitle = '';
let currentTotalPages = 0;
let isRendering = false;

/**
 * Buka Universal PDF Viewer
 */
function openPdfViewer(fileUrl, docTitle) {
    currentPdfUrl   = fileUrl;
    currentPdfTitle = docTitle || 'Dokumen LPM';

    const modalEl = document.getElementById('modalPdfViewer');
    if (!modalEl) {
        window.open(fileUrl, '_blank');
        return;
    }

    document.getElementById('modalPdfTitle').textContent = currentPdfTitle;
    document.getElementById('pdfLoadingSpinner').style.display = 'flex';
    document.getElementById('pdfPagesContainer').innerHTML = '';
    document.getElementById('stageLockCard').style.display = 'none';
    document.getElementById('modalPdfPageCount').textContent = 'Memuat dokumen...';
    
    updateDownloadBtnState();

    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();

    // Inisialisasi Google Auth Button di dalam Card
    initGoogleAuth();

    // Muat Dokumen via PDF.js
    loadPdfDocument(fileUrl);
}

/**
 * Load PDF via PDF.js
 */
function loadPdfDocument(fileUrl) {
    if (typeof pdfjsLib === 'undefined') {
        // Fallback jika CDN gagal dimuat
        document.getElementById('pdfLoadingSpinner').innerHTML = `
            <div class="text-center p-4">
                <p>Viewer interaktif sedang tidak tersedia. Buka dokumen di tab baru:</p>
                <a href="${fileUrl}" target="_blank" class="btn btn-warning fw-bold">Buka PDF</a>
            </div>
        `;
        return;
    }

    const loadingTask = pdfjsLib.getDocument({
        url: fileUrl,
        cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
        cMapPacked: true
    });

    loadingTask.promise.then(function(pdf) {
        currentPdfDoc = pdf;
        currentTotalPages = pdf.numPages;
        document.getElementById('pdfLoadingSpinner').style.display = 'none';

        // Tentukan jumlah halaman yang dirender:
        // Jika logged in sebagai civitas UNIKA -> Render SEMUA halaman
        // Jika belum login -> Render maksimal 3 halaman pertama
        const maxPagesToRender = window.UNIKA_CONFIG.isLoggedIn ? currentTotalPages : Math.min(3, currentTotalPages);

        document.getElementById('modalPdfPageCount').textContent = window.UNIKA_CONFIG.isLoggedIn 
            ? `${currentTotalPages} Halaman (Akses Penuh)`
            : `Menampilkan ${maxPagesToRender} dari ${currentTotalPages} Halaman`;

        renderPages(1, maxPagesToRender, function() {
            // Jika dokumen lebih dari 3 halaman dan user belum login -> Tampilkan Stage Locked Card
            if (!window.UNIKA_CONFIG.isLoggedIn && currentTotalPages > 3) {
                const stageCard = document.getElementById('stageLockCard');
                stageCard.style.display = 'block';
                document.getElementById('stageLockProgressText').textContent = 
                    `Pratinjau 3 Halaman Selesai • Halaman 4 s/d ${currentTotalPages} Terkunci`;
                const percent = Math.min(100, Math.round((3 / currentTotalPages) * 100));
                document.getElementById('stageProgressFill').style.width = percent + '%';
            }
        });

    }).catch(function(error) {
        console.error('Error memuat PDF:', error);
        document.getElementById('pdfLoadingSpinner').innerHTML = `
            <div class="text-center p-4">
                <p class="text-danger fw-bold">Gagal memuat pratinjau dokumen PDF.</p>
                <a href="${fileUrl}" target="_blank" class="btn btn-warning btn-sm fw-bold">Buka Langsung Berkas</a>
            </div>
        `;
    });
}

/**
 * Render serangkaian halaman secara berurutan
 */
function renderPages(startPage, endPage, onComplete) {
    if (!currentPdfDoc || startPage > endPage) {
        if (typeof onComplete === 'function') onComplete();
        return;
    }

    renderSinglePage(startPage, function() {
        renderPages(startPage + 1, endPage, onComplete);
    });
}

/**
 * Render single page ke canvas beresolusi tinggi
 */
function renderSinglePage(pageNum, callback) {
    currentPdfDoc.getPage(pageNum).then(function(page) {
        const container = document.getElementById('pdfPagesContainer');

        const pageWrap = document.createElement('div');
        pageWrap.className = 'pdf-page-wrapper';
        pageWrap.id = `pdf-page-${pageNum}`;

        const canvas = document.createElement('canvas');
        pageWrap.appendChild(canvas);

        const pageTag = document.createElement('div');
        pageTag.className = 'pdf-page-number-tag';
        pageTag.textContent = `Halaman ${pageNum} / ${currentTotalPages}`;
        pageWrap.appendChild(pageTag);

        container.appendChild(pageWrap);

        // Hitung scaling responsif
        const containerWidth = Math.min(container.clientWidth || 800, 850) - 40;
        const unscaledViewport = page.getViewport({ scale: 1 });
        const scale = containerWidth / unscaledViewport.width;
        const viewport = page.getViewport({ scale: Math.max(1, scale) });

        // High-DPI support
        const pixelRatio = window.devicePixelRatio || 1;
        canvas.width = Math.floor(viewport.width * pixelRatio);
        canvas.height = Math.floor(viewport.height * pixelRatio);
        canvas.style.width = Math.floor(viewport.width) + 'px';
        canvas.style.height = Math.floor(viewport.height) + 'px';

        const ctx = canvas.getContext('2d');
        ctx.scale(pixelRatio, pixelRatio);

        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };

        page.render(renderContext).promise.then(function() {
            if (typeof callback === 'function') callback();
        });
    });
}

/**
 * Update status tombol Download
 */
function updateDownloadBtnState() {
    const downloadBtn = document.getElementById('modalPdfDownload');
    const downloadText = document.getElementById('modalPdfDownloadText');
    const stageBadge   = document.getElementById('modalPdfStageBadge');
    const userPill     = document.getElementById('modalUserPill');
    const unlockedBanner = document.getElementById('stageUnlockedBanner');

    if (window.UNIKA_CONFIG.isLoggedIn) {
        downloadBtn.href = currentPdfUrl;
        downloadBtn.setAttribute('download', '');
        downloadBtn.onclick = null;
        downloadText.textContent = 'Unduh Lengkap';
        stageBadge.className = 'pdf-stage-badge unlocked';
        stageBadge.textContent = 'Akses Penuh Civitas UNIKA';
        userPill.style.display = 'inline-flex';
        unlockedBanner.style.display = 'flex';
        if (window.UNIKA_CONFIG.currentUser) {
            document.getElementById('modalUserName').textContent = window.UNIKA_CONFIG.currentUser.name || 'Civitas UNIKA';
            document.getElementById('bannerUserEmail').textContent = window.UNIKA_CONFIG.currentUser.email || '';
        }
    } else {
        downloadBtn.removeAttribute('download');
        downloadBtn.href = 'javascript:void(0)';
        downloadBtn.onclick = function() {
            const stageCard = document.getElementById('stageLockCard');
            if (stageCard && stageCard.style.display !== 'none') {
                stageCard.scrollIntoView({ behavior: 'smooth' });
            }
            alert('Akses Terbatas: Dokumen lengkap SPMI dan hak unduh berkas hanya dapat diakses oleh Civitas Akademika Universitas Katolik Soegijapranata (@unika.ac.id).\n\nSilakan masuk menggunakan akun Google UNIKA Anda pada formulir autentikasi di bawah.');
        };
        downloadText.textContent = 'Unduh Dokumen';
        stageBadge.className = 'pdf-stage-badge locked';
        stageBadge.textContent = 'Pratinjau Terbatas (Halaman 1–3)';
        userPill.style.display = 'none';
        unlockedBanner.style.display = 'none';
    }
}

/**
 * Inisialisasi Google Identity Services (GIS)
 */
function initGoogleAuth() {
    if (typeof google === 'undefined' || !google.accounts || !google.accounts.id) {
        setTimeout(initGoogleAuth, 300);
        return;
    }

    try {
        google.accounts.id.initialize({
            client_id: window.UNIKA_CONFIG.clientId,
            callback: handleGoogleCredentialResponse,
            auto_select: false,
            cancel_on_tap_outside: true
        });

        // Render Tombol Resmi Google Sign In
        const btnContainer = document.getElementById('gsiStageBtn');
        if (btnContainer && !window.UNIKA_CONFIG.isLoggedIn) {
            btnContainer.innerHTML = '';
            google.accounts.id.renderButton(btnContainer, {
                theme: 'filled_blue',
                size: 'large',
                text: 'continue_with',
                shape: 'pill',
                logo_alignment: 'left',
                width: 280
            });
        }
    } catch (e) {
        console.warn('Google Identity initialization error:', e);
    }
}

/**
 * Handle respon kredensial dari Google
 */
function handleGoogleCredentialResponse(response) {
    const alertBox = document.getElementById('unikaAuthAlert');
    if (alertBox) alertBox.style.display = 'none';

    if (!response || !response.credential) {
        if (alertBox) {
            alertBox.textContent = 'Gagal menerima kredensial dari Google. Silakan coba lagi.';
            alertBox.style.display = 'block';
        }
        return;
    }

    // Kirim token ke backend PHP untuk verifikasi domain @unika.ac.id
    fetch(window.UNIKA_CONFIG.siteUrl + '/auth-google.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.is_unika) {
            // SUKSES: Akun Civitas UNIKA Terverifikasi
            window.UNIKA_CONFIG.isLoggedIn = true;
            window.UNIKA_CONFIG.currentUser = data.user;

            // Sembunyikan Card Pembatas
            const stageCard = document.getElementById('stageLockCard');
            if (stageCard) stageCard.style.display = 'none';

            // Update status UI
            updateDownloadBtnState();

            // Render sisa halaman (halaman 4 sampai selesai)
            if (currentPdfDoc && currentTotalPages > 3) {
                renderPages(4, currentTotalPages, function() {
                    document.getElementById('modalPdfPageCount').textContent = 
                        `${currentTotalPages} Halaman (Akses Penuh Terbuka)`;
                });
            }

            // Notifikasi Sukses Formal
            alert(`Autentikasi Berhasil\n\nSelamat datang, ${data.user.name}!\nAkses dokumen lengkap (${currentTotalPages} halaman) dan fitur unduh telah aktif.`);

        } else {
            // DITOLAK: Bukan akun @unika.ac.id
            if (alertBox) {
                alertBox.innerHTML = `
                    <strong>Akses Ditolak:</strong><br>
                    ${data.message || 'Hanya akun resmi @unika.ac.id yang memiliki hak akses penuh terhadap dokumen ini.'}
                `;
                alertBox.style.display = 'block';
            }
        }
    })
    .catch(err => {
        console.error('Auth request error:', err);
        if (alertBox) {
            alertBox.textContent = 'Terjadi gangguan jaringan saat memverifikasi akun Google.';
            alertBox.style.display = 'block';
        }
    });
}

/**
 * Logout Civitas UNIKA
 */
function logoutCivitas() {
    if (!confirm('Apakah Anda ingin keluar dari sesi Civitas UNIKA? Dokumen akan kembali ke mode pratinjau 3 halaman.')) {
        return;
    }

    fetch(window.UNIKA_CONFIG.siteUrl + '/auth-google.php?action=logout', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(() => {
        window.UNIKA_CONFIG.isLoggedIn = false;
        window.UNIKA_CONFIG.currentUser = null;
        // Muat ulang pratinjau dalam modal
        if (currentPdfUrl) {
            openPdfViewer(currentPdfUrl, currentPdfTitle);
        }
    });
}

// Reset viewer saat modal ditutup
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalPdfViewer');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            document.getElementById('pdfPagesContainer').innerHTML = '';
            document.getElementById('stageLockCard').style.display = 'none';
            document.getElementById('pdfLoadingSpinner').style.display = 'flex';
            currentPdfDoc = null;
        });
    }
});
</script>
