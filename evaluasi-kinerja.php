<?php
/**
 * Halaman Portal Evaluasi Kinerja LPM UNIKA
 */
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

// 1. Pengaturan Halaman
$evaluasi_aktif = getPengaturan('evaluasi_kinerja_aktif', '1');
$tahun_akademik = getPengaturan('evaluasi_tahun_akademik_aktif', '2025/2026');
$petunjuk_umum  = getPengaturan('evaluasi_petunjuk_umum', 'Sistem evaluasi kinerja internal berkala Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata.');

// Cek apakah admin sedang login
$isAdmin = !empty($_SESSION['admin_id']);

// Jika non-aktif dan bukan admin, tampilkan layar penutupan
if ($evaluasi_aktif !== '1' && !$isAdmin) {
    $page_title = 'Evaluasi Kinerja Ditutup';
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    ?>
    <div class="page-banner">
        <div class="container position-relative">
            <h1 class="page-banner-title">Evaluasi Kinerja LPM</h1>
            <div class="breadcrumb-lpm">
                <a href="<?= SITE_URL ?>/">Beranda</a>
                <span>/</span>
                <span class="current">Layanan</span>
                <span>/</span>
                <span class="current">Evaluasi Kinerja</span>
            </div>
        </div>
    </div>
    <section class="py-5 text-center">
        <div class="container">
            <div style="max-width:560px;margin:3rem auto;padding:2.5rem;background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border);">
                <div style="width:75px;height:75px;border-radius:50%;background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                    <i class="bi bi-clock-history text-warning fs-1"></i>
                </div>
                <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.75rem;">Periode Evaluasi Belum Dibuka</h3>
                <p style="font-size:0.92rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.5rem;">
                    Portal Sistem Evaluasi Kinerja Personel LPM UNIKA saat ini sedang ditutup atau belum dijadwalkan oleh Administrator LPM. Silakan hubungi bagian Sekretariat LPM untuk jadwal pembukaan periode pengisian.
                </p>
                <a href="<?= SITE_URL ?>/" class="btn-hero-primary" style="display:inline-flex;padding:0.65rem 1.8rem;">
                    <i class="bi bi-arrow-left me-2"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 2. Status Environment
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// 3. Handle Form Submission
$flash_success = $_GET['msg'] ?? '';
$flash_error   = $_GET['err'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['evaluasi_user'])) {
    $cur_user_id = (int)$_SESSION['evaluasi_user']['id'];
    $form_action = $_POST['form_action'] ?? '';

    // A. Submit Evaluasi Diri (Penilaian Pribadi)
    if ($form_action === 'submit_evaluasi_diri') {
        $answers = $_POST['jawaban_diri'] ?? [];
        try {
            $db->beginTransaction();
            $stmtUpsert = $db->prepare("INSERT INTO evaluasi_diri_jawaban (peserta_id, tahun_akademik, pertanyaan_id, jawaban_teks) 
                                        VALUES (?, ?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE jawaban_teks = VALUES(jawaban_teks), updated_at = NOW()");
            foreach ($answers as $qId => $val) {
                $stmtUpsert->execute([$cur_user_id, $tahun_akademik, (int)$qId, trim($val)]);
            }

            // Simpan status submission
            $stmtSub = $db->prepare("INSERT INTO evaluasi_submission (tahun_akademik, penilai_id, dinilai_id, tipe_evaluasi, status, submitted_at) 
                                     VALUES (?, ?, NULL, 'diri', 'submitted', NOW()) 
                                     ON DUPLICATE KEY UPDATE status = 'submitted', submitted_at = NOW()");
            $stmtSub->execute([$tahun_akademik, $cur_user_id]);

            $db->commit();
            $flash_success = 'Evaluasi Diri (Penilaian Pribadi) Anda berhasil disimpan dan dikirimkan.';
        } catch (Exception $e) {
            $db->rollBack();
            $flash_error = 'Terjadi kendala saat menyimpan evaluasi diri: ' . $e->getMessage();
        }
    }

    // B. Submit Evaluasi Antar Personel (Rekan Kerja)
    if ($form_action === 'submit_evaluasi_antar') {
        $target_dinilai_id = (int)($_POST['dinilai_id'] ?? 0);
        $frekuensi_kerja   = isset($_POST['frekuensi_kerja']) ? (int)$_POST['frekuensi_kerja'] : 0;
        $skor_items        = $_POST['skor_antar'] ?? [];
        $teks_items        = $_POST['teks_antar'] ?? [];

        if ($target_dinilai_id > 0 && $target_dinilai_id !== $cur_user_id) {
            try {
                $db->beginTransaction();
                $stmtUpsert = $db->prepare("INSERT INTO evaluasi_antar_jawaban (penilai_id, dinilai_id, tahun_akademik, pertanyaan_id, skor, jawaban_teks) 
                                            VALUES (?, ?, ?, ?, ?, ?) 
                                            ON DUPLICATE KEY UPDATE skor = VALUES(skor), jawaban_teks = VALUES(jawaban_teks), updated_at = NOW()");
                
                // Simpan pertanyaan skala
                foreach ($skor_items as $qId => $skorVal) {
                    $stmtUpsert->execute([$cur_user_id, $target_dinilai_id, $tahun_akademik, (int)$qId, (int)$skorVal, null]);
                }
                // Simpan pertanyaan terbuka
                foreach ($teks_items as $qId => $teksVal) {
                    $stmtUpsert->execute([$cur_user_id, $target_dinilai_id, $tahun_akademik, (int)$qId, null, trim($teksVal)]);
                }

                // Update submission status
                $stmtSub = $db->prepare("INSERT INTO evaluasi_submission (tahun_akademik, penilai_id, dinilai_id, tipe_evaluasi, frekuensi_kerja, status, submitted_at) 
                                         VALUES (?, ?, ?, 'antar_personel', ?, 'submitted', NOW()) 
                                         ON DUPLICATE KEY UPDATE frekuensi_kerja = VALUES(frekuensi_kerja), status = 'submitted', submitted_at = NOW()");
                $stmtSub->execute([$tahun_akademik, $cur_user_id, $target_dinilai_id, $frekuensi_kerja]);

                $db->commit();
                $flash_success = 'Penilaian untuk rekan kerja berhasil disimpan.';
            } catch (Exception $e) {
                $db->rollBack();
                $flash_error = 'Gagal menyimpan penilaian rekan kerja: ' . $e->getMessage();
            }
        }
    }

    // C. Submit Penilaian oleh Kepala LPM
    if ($form_action === 'submit_evaluasi_kepala' && ($_SESSION['evaluasi_user']['peran'] ?? '') === 'kepala') {
        $target_dinilai_id = (int)($_POST['dinilai_id'] ?? 0);
        $skor_kepala       = $_POST['skor_kepala'] ?? [];
        $catatan_kepala    = $_POST['catatan_kepala'] ?? [];

        if ($target_dinilai_id > 0) {
            try {
                $db->beginTransaction();
                $stmtUpsert = $db->prepare("INSERT INTO evaluasi_kepala_jawaban (penilai_id, dinilai_id, tahun_akademik, pertanyaan_id, skor, catatan) 
                                            VALUES (?, ?, ?, ?, ?, ?) 
                                            ON DUPLICATE KEY UPDATE skor = VALUES(skor), catatan = VALUES(catatan), updated_at = NOW()");

                foreach ($skor_kepala as $qId => $skorVal) {
                    $catatanVal = trim($catatan_kepala[$qId] ?? '');
                    $stmtUpsert->execute([$cur_user_id, $target_dinilai_id, $tahun_akademik, (int)$qId, (int)$skorVal, $catatanVal]);
                }

                // Update submission status
                $stmtSub = $db->prepare("INSERT INTO evaluasi_submission (tahun_akademik, penilai_id, dinilai_id, tipe_evaluasi, status, submitted_at) 
                                         VALUES (?, ?, ?, 'kepala', 'submitted', NOW()) 
                                         ON DUPLICATE KEY UPDATE status = 'submitted', submitted_at = NOW()");
                $stmtSub->execute([$tahun_akademik, $cur_user_id, $target_dinilai_id]);

                $db->commit();
                $flash_success = 'Penilaian kinerja personel berhasil disimpan oleh Kepala LPM.';
            } catch (Exception $e) {
                $db->rollBack();
                $flash_error = 'Gagal menyimpan penilaian Kepala LPM: ' . $e->getMessage();
            }
        }
    }
}

// 4. Data untuk Render Tampilan
$currentUser = $_SESSION['evaluasi_user'] ?? null;
$isLoggedIn  = !empty($currentUser);

// Load Data Pertanyaan
$pertanyaanDiri = [];
$pertanyaanAntar = [];
$pertanyaanKepala = [];
if ($isLoggedIn) {
    $allQ = $db->query("SELECT * FROM evaluasi_pertanyaan WHERE is_active = 1 ORDER BY kategori, nomor_urut ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allQ as $q) {
        if ($q['kategori'] === 'diri') $pertanyaanDiri[] = $q;
        elseif ($q['kategori'] === 'antar_personel') $pertanyaanAntar[] = $q;
        elseif ($q['kategori'] === 'kepala') $pertanyaanKepala[] = $q;
    }
}

$page_title = 'Evaluasi Kinerja Personel LPM';
$current_page = 'evaluasi-kinerja';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* UNIKA Brand Theme Styles for Evaluasi Kinerja */
.nav-pills .nav-link {
    color: var(--navy);
    font-weight: 600;
    transition: all 0.2s ease;
}
.nav-pills .nav-link.active, 
.nav-pills .show > .nav-link {
    background: linear-gradient(135deg, var(--purple) 0%, #5B21B6 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(106, 27, 154, 0.25) !important;
}
.nav-pills .nav-link:hover:not(.active) {
    background: rgba(106, 27, 154, 0.06);
    color: var(--purple);
}
.btn-evaluasi-primary {
    background: linear-gradient(135deg, var(--purple) 0%, #5B21B6 100%);
    border: none;
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(106, 27, 154, 0.22);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-evaluasi-primary:hover {
    background: linear-gradient(135deg, #5B21B6 0%, #4C1D95 100%);
    color: #ffffff !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(106, 27, 154, 0.32);
}
.btn-evaluasi-outline {
    border: 1.5px solid var(--purple);
    color: var(--purple) !important;
    font-weight: 600;
    background: #ffffff;
    transition: all 0.2s;
}
.btn-evaluasi-outline:hover {
    background: var(--purple);
    color: #ffffff !important;
}
.section-evaluasi-header {
    background: rgba(106, 27, 154, 0.05);
    border-left: 4px solid var(--purple);
    border-radius: 6px;
    padding: 0.75rem 1rem;
    color: var(--purple);
    font-weight: 700;
}
.google-signin-btn-styled {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: #ffffff;
    border: 1.5px solid #DADCE0;
    border-radius: 50px;
    padding: 10px 24px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #3C4043;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none !important;
}
.google-signin-btn-styled:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
    color: #1E293B;
}
</style>


<!-- Banner Utama -->
<div class="page-banner" style="background:linear-gradient(135deg, var(--navy) 0%, #152238 60%, var(--purple) 100%);">
    <div class="container position-relative">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-2 rounded-pill" style="background:rgba(255,255,255,0.12);color:var(--gold);font-size:0.8rem;font-weight:600;letter-spacing:0.5px;">
            <i class="bi bi-shield-check"></i> INTERNAL PENJAMINAN MUTU
        </div>
        <h1 class="page-banner-title">Evaluasi Kinerja Personel LPM</h1>
        <p class="text-white-50 mb-0" style="max-width:680px;font-size:0.95rem;">
            Universitas Katolik Soegijapranata &bull; Periode Evaluasi: <strong>Tahun Akademik <?= e($tahun_akademik) ?></strong>
        </p>
        <div class="breadcrumb-lpm mt-3">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Layanan</span>
            <span>/</span>
            <span class="current">Evaluasi Kinerja</span>
        </div>
    </div>
</div>

<div class="container py-5">

    <!-- Flash Alert -->
    <?php if ($flash_success): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= e($flash_success) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= e($flash_error) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!$isLoggedIn): ?>
    <!-- ========================================== -->
    <!-- 1. TAMPILAN BELUM LOGIN (GOOGLE SIGN-IN)   -->
    <!-- ========================================== -->
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-sm border-0" style="border-radius:var(--radius-lg);overflow:hidden;">
                <div style="height:6px;background:linear-gradient(90deg, var(--gold), var(--purple));"></div>
                <div class="card-body p-4 p-md-5 text-center">
                    
                    <div style="width:75px;height:75px;border-radius:50%;background:rgba(106,27,154,0.08);color:var(--purple);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;">
                        <i class="bi bi-person-bounding-box"></i>
                    </div>

                    <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.5rem;">
                        Autentikasi Akun Google
                    </h3>
                    <p style="color:var(--text-muted);font-size:0.92rem;line-height:1.6;margin-bottom:1.75rem;">
                        Portal evaluasi ini diperuntukkan khusus bagi seluruh <strong>Kepala &amp; Personel LPM UNIKA</strong>. 
                        Silakan login menggunakan akun Google resmi yang telah terdaftar dalam sistem.
                    </p>

                    <!-- Alert Box Login -->
                    <div id="evaluasiAuthAlert" class="alert alert-danger mb-4 text-start" style="display:none;font-size:0.88rem;border-radius:8px;"></div>

                    <!-- GIS Google Sign-in Button Container -->
                    <div class="d-flex flex-column align-items-center justify-content-center mb-4">
                        <div id="g_id_signin_evaluasi" style="min-height:48px;display:flex;align-items:center;justify-content:center;">
                            <!-- Pre-rendered visible Google Button -->
                            <button type="button" id="btnGoogleEvaluasiFallback" onclick="triggerEvaluasiGoogleSignIn()" class="google-signin-btn-styled">
                                <svg width="20" height="20" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
                                    <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                                    <path d="M9 3.58c1.321 0 2.508.454 3.44 1.346l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                                </svg>
                                <span>Lanjutkan dengan Akun Google</span>
                            </button>
                        </div>
                        <div id="evaluasiLoginLoading" style="display:none;font-size:0.83rem;color:var(--purple);font-weight:600;margin-top:8px;">
                            <span class="spinner-border spinner-border-sm me-1"></span> Menghubungkan ke Google...
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Script Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
    const googleClientId = "<?= GOOGLE_CLIENT_ID ?>";
    let googleInitRetries = 0;

    function initEvaluasiGoogleAuth() {
        if (typeof google === "undefined" || !google.accounts || !google.accounts.id) {
            googleInitRetries++;
            if (googleInitRetries < 50) {
                setTimeout(initEvaluasiGoogleAuth, 200);
            }
            return;
        }

        try {
            google.accounts.id.initialize({
                client_id: googleClientId,
                callback: handleEvaluasiCredentialResponse,
                auto_select: false
            });

            const btnContainer = document.getElementById("g_id_signin_evaluasi");
            if (btnContainer && googleClientId) {
                btnContainer.innerHTML = "";
                google.accounts.id.renderButton(btnContainer, {
                    theme: "outline",
                    size: "large",
                    text: "continue_with",
                    shape: "pill",
                    logo_alignment: "left",
                    width: 280
                });
            }

            // Prompt One Tap jika tersedia
            try { google.accounts.id.prompt(); } catch(e) {}
        } catch (e) {
            console.warn("Google init error:", e);
        }
    }

    function triggerEvaluasiGoogleSignIn() {
        const alertBox   = document.getElementById("evaluasiAuthAlert");
        const loadingBox = document.getElementById("evaluasiLoginLoading");

        if (!googleClientId) {
            if (alertBox) {
                alertBox.innerHTML = `<strong>Google Client ID Belum Dikonfigurasi</strong><br>
                Aplikasi belum memiliki <code>GOOGLE_CLIENT_ID</code>. Silakan hubungi Administrator untuk melengkapi kredensial Google OAuth.`;
                alertBox.style.display = "block";
            }
            return;
        }

        if (typeof google !== "undefined" && google.accounts && google.accounts.id) {
            try {
                google.accounts.id.prompt();
            } catch (e) {
                console.warn(e);
            }
        } else {
            if (loadingBox) loadingBox.style.display = "block";
            initEvaluasiGoogleAuth();
        }
    }

    document.addEventListener("DOMContentLoaded", initEvaluasiGoogleAuth);
    window.addEventListener("load", initEvaluasiGoogleAuth);

    function handleEvaluasiCredentialResponse(response) {
        const alertBox   = document.getElementById("evaluasiAuthAlert");
        const loadingBox = document.getElementById("evaluasiLoginLoading");
        if (alertBox) alertBox.style.display = "none";
        if (loadingBox) loadingBox.style.display = "block";

        if (!response || !response.credential) {
            if (loadingBox) loadingBox.style.display = "none";
            if (alertBox) {
                alertBox.textContent = "Gagal menerima kredensial dari Google. Silakan coba kembali.";
                alertBox.style.display = "block";
            }
            return;
        }

        fetch("<?= SITE_URL ?>/auth-evaluasi.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ credential: response.credential })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                if (loadingBox) loadingBox.style.display = "none";
                if (alertBox) {
                    alertBox.innerHTML = `<strong>Akses Ditolak:</strong><br>${data.message || "Akun Google Anda tidak memiliki wewenang akses."}`;
                    alertBox.style.display = "block";
                }
            }
        })
        .catch(err => {
            if (loadingBox) loadingBox.style.display = "none";
            if (alertBox) {
                alertBox.textContent = "Terjadi gangguan jaringan saat memverifikasi kredensial Google.";
                alertBox.style.display = "block";
            }
        });
    }
    </script>

    <?php else: ?>
    <!-- ========================================== -->
    <!-- 2. TAMPILAN SUDAH LOGIN                    -->
    <!-- ========================================== -->

    <?php
    $cur_peran = $currentUser['peran']; // 'kepala' atau 'personel'
    $cur_id    = (int)$currentUser['id'];

    // Cek Status Evaluasi Diri
    $subDiri = $db->prepare("SELECT * FROM evaluasi_submission WHERE penilai_id = ? AND tahun_akademik = ? AND tipe_evaluasi = 'diri' LIMIT 1");
    $subDiri->execute([$cur_id, $tahun_akademik]);
    $isDiriSubmitted = (bool)$subDiri->fetch();

    // Jawaban Diri Sebelumnya
    $stmtAnsDiri = $db->prepare("SELECT pertanyaan_id, jawaban_teks FROM evaluasi_diri_jawaban WHERE peserta_id = ? AND tahun_akademik = ?");
    $stmtAnsDiri->execute([$cur_id, $tahun_akademik]);
    $savedAnsDiri = $stmtAnsDiri->fetchAll(PDO::FETCH_KEY_PAIR);

    // Ambil Daftar Rekan Kerja untuk Antar Personel (termasuk Kepala LPM jika penilai adalah personel)
    $stmtRekan = $db->prepare("SELECT p.*, s.status as sub_status, s.frekuensi_kerja 
                               FROM evaluasi_peserta p 
                               LEFT JOIN evaluasi_submission s ON s.dinilai_id = p.id AND s.penilai_id = ? AND s.tahun_akademik = ? AND s.tipe_evaluasi = 'antar_personel'
                               WHERE p.status = 1 AND p.id != ?
                               ORDER BY (p.peran = 'kepala') DESC, p.id ASC");
    $stmtRekan->execute([$cur_id, $tahun_akademik, $cur_id]);
    $listRekan = $stmtRekan->fetchAll(PDO::FETCH_ASSOC);

    $rekanTotal    = count($listRekan);
    $rekanDoneCount = count(array_filter($listRekan, fn($r) => !empty($r['sub_status']) && $r['sub_status'] === 'submitted'));

    // Jika Kepala LPM: Ambil daftar seluruh personel untuk dinilai
    $listPersonelKepala = [];
    if ($cur_peran === 'kepala') {
        $stmtPK = $db->prepare("SELECT p.*, s.status as sub_status, 
                                (SELECT ROUND(AVG(skor), 2) FROM evaluasi_kepala_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ? AND skor > 0) as avg_skor
                                FROM evaluasi_peserta p 
                                LEFT JOIN evaluasi_submission s ON s.dinilai_id = p.id AND s.penilai_id = ? AND s.tahun_akademik = ? AND s.tipe_evaluasi = 'kepala'
                                WHERE p.status = 1 AND p.peran = 'personel'
                                ORDER BY p.id ASC");
        $stmtPK->execute([$tahun_akademik, $cur_id, $tahun_akademik]);
        $listPersonelKepala = $stmtPK->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>

    <!-- Kartu Profil Pengguna & Switcher -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);background:#fff;">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg, var(--purple), var(--navy));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.4rem;box-shadow:0 3px 10px rgba(0,0,0,0.1);">
                    <?php if (!empty($currentUser['avatar'])): ?>
                        <img src="<?= e($currentUser['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h5 class="mb-0 fw-bold" style="color:var(--navy);"><?= e($currentUser['nama']) ?></h5>
                        <?php if ($cur_peran === 'kepala'): ?>
                            <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;font-size:0.75rem;"><i class="bi bi-star-fill me-1"></i> Kepala LPM</span>
                        <?php else: ?>
                            <span class="badge" style="background:#E0E7FF;color:#3730A3;border:1px solid #C7D2FE;font-size:0.75rem;"><i class="bi bi-person-fill me-1"></i> Personel LPM</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.85rem;color:var(--text-muted);">
                        <i class="bi bi-briefcase me-1"></i> <?= e($currentUser['jabatan']) ?> &bull; <i class="bi bi-envelope me-1"></i> <?= e($currentUser['email']) ?>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= SITE_URL ?>/auth-evaluasi.php?action=logout" class="btn btn-sm btn-outline-danger" style="font-size:0.8rem;border-radius:8px;">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </a>
            </div>
        </div>
    </div>

    <?php if ($cur_peran === 'personel'): ?>
    <!-- ============================================================ -->
    <!-- ALUR PERSONEL: 2 PART (1: DIRI SENDIRI, 2: ANTAR PERSONEL)   -->
    <!-- ============================================================ -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-fill p-2 rounded-3 shadow-sm bg-white border" id="personelTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 d-flex align-items-center justify-content-center gap-2" id="tab-diri" data-bs-toggle="pill" data-bs-target="#pane-diri" type="button" role="tab" style="border-radius:8px;">
                        <span class="badge <?= $isDiriSubmitted ? 'bg-success' : 'bg-secondary' ?>" style="font-size:0.75rem;">Part 1</span>
                        <span>Penilaian Pribadi (Diri Sendiri)</span>
                        <?php if ($isDiriSubmitted): ?>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2" id="tab-antar" data-bs-toggle="pill" data-bs-target="#pane-antar" type="button" role="tab" style="border-radius:8px;">
                        <span class="badge bg-primary" style="font-size:0.75rem;">Part 2</span>
                        <span>Penilaian Antar Personel (Rekan Kerja)</span>
                        <span class="badge rounded-pill <?= $rekanDoneCount === $rekanTotal && $rekanTotal > 0 ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size:0.75rem;">
                            <?= $rekanDoneCount ?> / <?= $rekanTotal ?> Selesai
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" id="personelTabsContent">
        
        <!-- PANE PART 1: EVALUASI DIRI SENDIRI -->
        <div class="tab-pane fade show active" id="pane-diri" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius:var(--radius-lg);">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h4 class="fw-bold mb-1" style="color:var(--navy);">Formulir Evaluasi Diri (Penilaian Pribadi)</h4>
                            <p class="text-muted mb-0" style="font-size:0.88rem;">
                                <strong>Petunjuk:</strong> Berikan penilaian berdasarkan pelaksanaan tugas selama periode evaluasi dan tuliskan bukti atau contoh konkret untuk mendukung jawaban Anda.
                            </p>
                        </div>
                        <?php if ($isDiriSubmitted): ?>
                            <span class="badge bg-success p-2 px-3" style="font-size:0.85rem;"><i class="bi bi-check-circle-fill me-1"></i> Telah Terkirim</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark p-2 px-3" style="font-size:0.85rem;"><i class="bi bi-hourglass-split me-1"></i> Belum Dikirim</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="">
                        <input type="hidden" name="form_action" value="submit_evaluasi_diri">

                        <?php
                        $groupedDiri = [];
                        foreach ($pertanyaanDiri as $pd) {
                            $grp = $pd['bagian'] ?: 'Pertanyaan Evaluasi Diri';
                            $groupedDiri[$grp][] = $pd;
                        }

                        $noUrutGlobal = 1;
                        foreach ($groupedDiri as $bagianJudul => $items):
                        ?>
                            <div class="p-3 mb-4 rounded-3" style="background:rgba(106,27,154,0.04);border-left:4px solid var(--purple);">
                                <h5 class="fw-bold mb-0" style="color:var(--purple);font-size:1.05rem;"><?= e($bagianJudul) ?></h5>
                            </div>

                            <?php foreach ($items as $item): 
                                $ansVal = $savedAnsDiri[$item['id']] ?? '';
                            ?>
                            <div class="mb-4 pb-3 border-bottom">
                                <label class="form-label fw-bold d-flex align-items-start gap-2 mb-2" style="font-size:0.95rem;color:var(--navy);">
                                    <span class="badge bg-purple text-white" style="background:var(--purple);font-size:0.8rem;border-radius:6px;"><?= $noUrutGlobal++ ?></span>
                                    <span><?= e($item['pertanyaan']) ?> <span class="text-danger">*</span></span>
                                </label>
                                <?php if (!empty($item['keterangan'])): ?>
                                    <div class="text-muted small mb-2" style="margin-left:2rem;"><?= e($item['keterangan']) ?></div>
                                <?php endif; ?>
                                <div style="margin-left:2rem;">
                                    <textarea name="jawaban_diri[<?= $item['id'] ?>]" class="form-control" rows="4" placeholder="Tuliskan penjelasan dan bukti konkret pelaksanaan tugas Anda..." required style="border-radius:8px;font-size:0.9rem;"><?= e($ansVal) ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        <?php endforeach; ?>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-evaluasi-primary px-4 py-2" style="border-radius:8px;">
                                <i class="bi bi-send-fill me-1"></i> Simpan &amp; Kirim Evaluasi Diri
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- PANE PART 2: EVALUASI ANTAR PERSONEL (REKAN KERJA) -->
        <div class="tab-pane fade" id="pane-antar" role="tabpanel">
            <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
                <div class="card-header bg-white border-bottom p-4">
                    <h4 class="fw-bold mb-1" style="color:var(--navy);">Penilaian Kinerja Antar Personel</h4>
                    <p class="text-muted mb-0" style="font-size:0.88rem;">
                        Silakan berikan penilaian objektif untuk rekan kerja di bawah ini. Klik tombol <strong>Beri Penilaian</strong> pada masing-masing rekan kerja untuk mengisi kuesioner.
                    </p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($listRekan as $rekan): 
                            $isDone = (!empty($rekan['sub_status']) && $rekan['sub_status'] === 'submitted');
                        ?>
                        <div class="col-lg-6">
                            <div class="p-3 rounded-3 border h-100 d-flex align-items-center justify-content-between gap-3 <?= $isDone ? 'bg-light' : 'bg-white' ?>" style="box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:48px;height:48px;border-radius:50%;background:#EEF2F6;color:var(--navy);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;">
                                        <?php if (!empty($rekan['avatar'])): ?>
                                            <img src="<?= e($rekan['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($rekan['nama'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2 flex-wrap" style="font-size:0.95rem;">
                                            <span><?= e($rekan['nama']) ?></span>
                                            <?php if ($rekan['peran'] === 'kepala'): ?>
                                                <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;font-size:0.7rem;"><i class="bi bi-star-fill text-warning me-1"></i> Kepala LPM</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.8rem;"><?= e($rekan['jabatan']) ?></div>
                                        <div class="mt-1">
                                            <?php if ($isDone): ?>
                                                <span class="badge bg-success" style="font-size:0.7rem;"><i class="bi bi-check-circle-fill me-1"></i> Sudah Dinilai</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" style="font-size:0.7rem;"><i class="bi bi-clock me-1"></i> Belum Dinilai</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm <?= $isDone ? 'btn-evaluasi-outline' : 'btn-evaluasi-primary' ?> px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalNilaiRekan_<?= $rekan['id'] ?>" style="border-radius:8px;font-size:0.83rem;">
                                        <i class="bi <?= $isDone ? 'bi-pencil-square' : 'bi-pencil-fill' ?> me-1"></i> <?= $isDone ? 'Ubah' : 'Nilai' ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL PENILAIAN REKAN KERJA -->
                        <?php
                        // Ambil jawaban sebelumnya untuk rekan ini jika ada
                        $stmtAnsRekan = $db->prepare("SELECT pertanyaan_id, skor, jawaban_teks FROM evaluasi_antar_jawaban WHERE penilai_id = ? AND dinilai_id = ? AND tahun_akademik = ?");
                        $stmtAnsRekan->execute([$cur_id, $rekan['id'], $tahun_akademik]);
                        $prevAnsRekan = [];
                        while ($rRow = $stmtAnsRekan->fetch(PDO::FETCH_ASSOC)) {
                            $prevAnsRekan[$rRow['pertanyaan_id']] = [
                                'skor' => $rRow['skor'],
                                'teks' => $rRow['jawaban_teks']
                            ];
                        }
                        $curFrekuensi = (isset($rekan['frekuensi_kerja']) && $rekan['frekuensi_kerja'] !== null && $rekan['frekuensi_kerja'] !== '') ? (int)$rekan['frekuensi_kerja'] : null;
                        ?>
                        <div class="modal fade" id="modalNilaiRekan_<?= $rekan['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;">
                                    <div class="modal-header bg-navy text-white" style="background:var(--navy);">
                                        <h5 class="modal-title fw-bold" style="font-size:1.05rem;">
                                            <i class="bi bi-people-fill me-2"></i> Penilaian <?= $rekan['peran'] === 'kepala' ? 'Kepala LPM' : 'Rekan Kerja' ?>: <?= e($rekan['nama']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <form method="POST" action="">
                                            <input type="hidden" name="form_action" value="submit_evaluasi_antar">
                                            <input type="hidden" name="dinilai_id" value="<?= $rekan['id'] ?>">

                                            <!-- Petunjuk & Frekuensi Bekerja Sama -->
                                            <div class="p-3 mb-4 rounded-3" style="background:#F1F5F9;border:1px solid #CBD5E1;">
                                                <div class="fw-bold mb-2 text-dark"><i class="bi bi-clock-history me-1"></i> Frekuensi Bekerja Sama Selama Periode Ini:</div>
                                                <div class="d-flex flex-wrap gap-4" style="font-size:0.9rem;">
                                                    <label class="form-check-label d-flex align-items-center gap-1 cursor-pointer">
                                                        <input class="form-check-input" type="radio" name="frekuensi_kerja" value="1" <?= $curFrekuensi === 1 ? 'checked' : '' ?> required> (1) Sering
                                                    </label>
                                                    <label class="form-check-label d-flex align-items-center gap-1 cursor-pointer">
                                                        <input class="form-check-input" type="radio" name="frekuensi_kerja" value="2" <?= $curFrekuensi === 2 ? 'checked' : '' ?>> (2) Kadang-kadang
                                                    </label>
                                                    <label class="form-check-label d-flex align-items-center gap-1 cursor-pointer">
                                                        <input class="form-check-input" type="radio" name="frekuensi_kerja" value="3" <?= $curFrekuensi === 3 ? 'checked' : '' ?>> (3) Terbatas
                                                    </label>
                                                    <label class="form-check-label d-flex align-items-center gap-1 cursor-pointer">
                                                        <input class="form-check-input" type="radio" name="frekuensi_kerja" value="0" <?= $curFrekuensi === 0 ? 'checked' : '' ?>> N/A
                                                    </label>
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    *Jika pengalaman bekerja bersama tidak cukup untuk menilai suatu aspek, pilih opsi <strong>N/A</strong>.
                                                </div>
                                            </div>

                                            <?php
                                            $groupedAntar = [];
                                            foreach ($pertanyaanAntar as $pa) {
                                                $grp = $pa['bagian'] ?: 'Pertanyaan Evaluasi';
                                                $groupedAntar[$grp][] = $pa;
                                            }

                                            $noUrutAntar = 1;
                                            foreach ($groupedAntar as $bJudul => $bItems):
                                            ?>
                                                <div class="p-2 px-3 mb-3 rounded-2 fw-bold" style="background:rgba(106,27,154,0.06);color:var(--purple);border-left:3px solid var(--purple);font-size:0.95rem;">
                                                    <?= e($bJudul) ?>
                                                </div>

                                                <?php foreach ($bItems as $qAntar): 
                                                    $prevVal = $prevAnsRekan[$qAntar['id']] ?? null;
                                                    $isSkala = ($qAntar['tipe_jawaban'] === 'skala_3');
                                                ?>
                                                <div class="mb-4 pb-3 border-bottom">
                                                    <div class="fw-bold mb-2 d-flex align-items-start gap-2" style="font-size:0.92rem;color:var(--navy);">
                                                        <span class="badge bg-secondary" style="font-size:0.75rem;"><?= $noUrutAntar++ ?></span>
                                                        <span><?= e($qAntar['pertanyaan']) ?> <span class="text-danger">*</span></span>
                                                    </div>

                                                    <?php if ($isSkala): 
                                                        $selectedSkor = (isset($prevVal['skor']) && $prevVal['skor'] !== null && $prevVal['skor'] !== '') ? (int)$prevVal['skor'] : null;
                                                    ?>
                                                        <div class="d-flex flex-wrap gap-4 ms-4 mt-2" style="font-size:0.88rem;">
                                                            <label class="form-check-label cursor-pointer">
                                                                <input class="form-check-input me-1" type="radio" name="skor_antar[<?= $qAntar['id'] ?>]" value="1" <?= $selectedSkor === 1 ? 'checked' : '' ?> required> 1 - Sering
                                                            </label>
                                                            <label class="form-check-label cursor-pointer">
                                                                <input class="form-check-input me-1" type="radio" name="skor_antar[<?= $qAntar['id'] ?>]" value="2" <?= $selectedSkor === 2 ? 'checked' : '' ?>> 2 - Kadang-kadang
                                                            </label>
                                                            <label class="form-check-label cursor-pointer">
                                                                <input class="form-check-input me-1" type="radio" name="skor_antar[<?= $qAntar['id'] ?>]" value="3" <?= $selectedSkor === 3 ? 'checked' : '' ?>> 3 - Terbatas
                                                            </label>
                                                            <label class="form-check-label cursor-pointer">
                                                                <input class="form-check-input me-1" type="radio" name="skor_antar[<?= $qAntar['id'] ?>]" value="0" <?= $selectedSkor === 0 ? 'checked' : '' ?>> N/A
                                                            </label>
                                                        </div>
                                                    <?php else: 
                                                        $savedTeks = $prevVal['teks'] ?? '';
                                                    ?>
                                                        <div class="ms-4 mt-2">
                                                            <textarea name="teks_antar[<?= $qAntar['id'] ?>]" class="form-control" rows="3" placeholder="Tuliskan jawaban atau masukan konstruktif Anda..." required style="border-radius:8px;font-size:0.88rem;"><?= e($savedTeks) ?></textarea>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>

                                            <?php endforeach; ?>

                                            <div class="modal-footer border-top px-0 pb-0 mt-4">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-evaluasi-primary px-4">
                                                    <i class="bi bi-save-fill me-1"></i> Simpan Penilaian Rekan Kerja
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END MODAL -->

                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php else: ?>
    <!-- ============================================================ -->
    <!-- ALUR KEPALA LPM: PENILAIAN SELURUH PERSONEL & EVALUASI DIRI  -->
    <!-- ============================================================ -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-fill p-2 rounded-3 shadow-sm bg-white border" id="kepalaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 d-flex align-items-center justify-content-center gap-2" id="tab-kepala-penilaian" data-bs-toggle="pill" data-bs-target="#pane-kepala-penilaian" type="button" role="tab" style="border-radius:8px;">
                        <span class="badge bg-warning text-dark" style="font-size:0.75rem;"><i class="bi bi-award-fill me-1"></i> Penilaian Pimpinan</span>
                        <span>Penilaian Kinerja Personel oleh Kepala LPM</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 d-flex align-items-center justify-content-center gap-2" id="tab-kepala-diri" data-bs-toggle="pill" data-bs-target="#pane-kepala-diri" type="button" role="tab" style="border-radius:8px;">
                        <span class="badge bg-secondary" style="font-size:0.75rem;">Refleksi</span>
                        <span>Evaluasi Diri Kepala LPM</span>
                        <?php if ($isDiriSubmitted): ?>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" id="kepalaTabsContent">
        
        <!-- PANE PENILAIAN PERSONEL OLEH KEPALA -->
        <div class="tab-pane fade show active" id="pane-kepala-penilaian" role="tabpanel">
            <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
                <div class="card-header bg-white border-bottom p-4">
                    <h4 class="fw-bold mb-1" style="color:var(--navy);">Form Penilaian Kinerja Personel oleh Kepala LPM</h4>
                    <p class="text-muted mb-0" style="font-size:0.88rem;">
                        Periode Penilaian: <strong>Tahun Akademik <?= e($tahun_akademik) ?></strong>. Gunakan skala 1–5 atau N/A serta tuliskan bukti pendukung pada masing-masing butir.
                    </p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php foreach ($listPersonelKepala as $pkItem): 
                            $isDone = (!empty($pkItem['sub_status']) && $pkItem['sub_status'] === 'submitted');
                        ?>
                        <div class="col-lg-6">
                            <div class="p-3 rounded-3 border h-100 d-flex align-items-center justify-content-between gap-3 <?= $isDone ? 'bg-light' : 'bg-white' ?>" style="box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:50px;height:50px;border-radius:50%;background:#F3E8FF;color:var(--purple);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.15rem;">
                                        <?php if (!empty($pkItem['avatar'])): ?>
                                            <img src="<?= e($pkItem['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($pkItem['nama'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size:0.95rem;"><?= e($pkItem['nama']) ?></div>
                                        <div class="text-muted" style="font-size:0.8rem;"><?= e($pkItem['jabatan']) ?></div>
                                        <div class="mt-1">
                                            <?php if ($isDone): ?>
                                                <span class="badge bg-success" style="font-size:0.7rem;"><i class="bi bi-check-circle-fill me-1"></i> Skor: <?= number_format((float)$pkItem['avg_skor'], 2) ?> / 5.00</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary" style="font-size:0.7rem;"><i class="bi bi-clock me-1"></i> Belum Dinilai</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm <?= $isDone ? 'btn-outline-purple' : 'btn-purple' ?> px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalNilaiKepala_<?= $pkItem['id'] ?>" style="background:var(--purple);color:#fff;border-radius:8px;font-size:0.83rem;">
                                        <i class="bi <?= $isDone ? 'bi-pencil-square' : 'bi-award-fill' ?> me-1"></i> <?= $isDone ? 'Edit Nilai' : 'Beri Nilai' ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL PENILAIAN KEPALA LPM -->
                        <?php
                        $stmtAnsK = $db->prepare("SELECT pertanyaan_id, skor, catatan FROM evaluasi_kepala_jawaban WHERE dinilai_id = ? AND tahun_akademik = ?");
                        $stmtAnsK->execute([$pkItem['id'], $tahun_akademik]);
                        $prevAnsK = [];
                        while ($kRow = $stmtAnsK->fetch(PDO::FETCH_ASSOC)) {
                            $prevAnsK[$kRow['pertanyaan_id']] = [
                                'skor'    => $kRow['skor'],
                                'catatan' => $kRow['catatan']
                            ];
                        }
                        ?>
                        <div class="modal fade" id="modalNilaiKepala_<?= $pkItem['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;">
                                    <div class="modal-header bg-purple text-white" style="background:var(--purple);color:#fff;">
                                        <h5 class="modal-title fw-bold" style="font-size:1.05rem;">
                                            <i class="bi bi-award-fill me-2"></i> Penilaian Kinerja: <?= e($pkItem['nama']) ?> (<?= e($pkItem['jabatan']) ?>)
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4 p-md-5">
                                        <form method="POST" action="">
                                            <input type="hidden" name="form_action" value="submit_evaluasi_kepala">
                                            <input type="hidden" name="dinilai_id" value="<?= $pkItem['id'] ?>">

                                            <!-- Petunjuk Skala Skor -->
                                            <div class="p-3 mb-4 rounded-3" style="background:#FAF5FF;border:1px solid #E9D5FF;font-size:0.86rem;color:#581C87;">
                                                <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> Panduan Skala Skor Penilaian Kepala LPM:</div>
                                                <div class="row row-cols-1 row-cols-md-3 g-2 mt-1">
                                                    <div><strong>Skor 5:</strong> Konsisten melampaui harapan</div>
                                                    <div><strong>Skor 4:</strong> Memenuhi harapan dengan baik</div>
                                                    <div><strong>Skor 3:</strong> Memenuhi sebagian harapan (perlu perbaikan)</div>
                                                    <div><strong>Skor 2:</strong> Beberapa tanggung jawab penting belum terpenuhi</div>
                                                    <div><strong>Skor 1:</strong> Tanggung jawab utama tidak terlaksana memadai</div>
                                                    <div><strong>Skor 0 (N/A):</strong> Tidak cukup informasi untuk menilai</div>
                                                </div>
                                            </div>

                                            <?php 
                                            $noUrutKepala = 1;
                                            foreach ($pertanyaanKepala as $qK): 
                                                $prevK = $prevAnsK[$qK['id']] ?? null;
                                                $selSkorK = (isset($prevK['skor']) && $prevK['skor'] !== null && $prevK['skor'] !== '') ? (int)$prevK['skor'] : null;
                                                $catatanK = $prevK['catatan'] ?? '';
                                            ?>
                                            <div class="mb-4 pb-3 border-bottom">
                                                <div class="fw-bold mb-2 d-flex align-items-start gap-2" style="font-size:0.95rem;color:var(--navy);">
                                                    <span class="badge bg-purple text-white" style="background:var(--purple);font-size:0.8rem;border-radius:6px;"><?= $noUrutKepala++ ?></span>
                                                    <span><?= e($qK['pertanyaan']) ?> <span class="text-danger">*</span></span>
                                                </div>
                                                <?php if (!empty($qK['keterangan'])): ?>
                                                    <div class="text-muted small mb-2 ms-4"><?= e($qK['keterangan']) ?></div>
                                                <?php endif; ?>

                                                <!-- Skala Skor Radio -->
                                                <div class="d-flex flex-wrap gap-3 ms-4 my-2 p-2 rounded-2" style="background:#F8FAFC;">
                                                    <label class="form-check-label cursor-pointer d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                        <input class="form-check-input" type="radio" name="skor_kepala[<?= $qK['id'] ?>]" value="1" <?= $selSkorK === 1 ? 'checked' : '' ?> required> 1 (Kurang)
                                                    </label>
                                                    <label class="form-check-label cursor-pointer d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                        <input class="form-check-input" type="radio" name="skor_kepala[<?= $qK['id'] ?>]" value="2" <?= $selSkorK === 2 ? 'checked' : '' ?>> 2 (Cukup)
                                                    </label>
                                                    <label class="form-check-label cursor-pointer d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                        <input class="form-check-input" type="radio" name="skor_kepala[<?= $qK['id'] ?>]" value="3" <?= $selSkorK === 3 ? 'checked' : '' ?>> 3 (Perlu Perbaikan)
                                                    </label>
                                                    <label class="form-check-label cursor-pointer d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                        <input class="form-check-input" type="radio" name="skor_kepala[<?= $qK['id'] ?>]" value="4" <?= $selSkorK === 4 ? 'checked' : '' ?>> 4 (Baik)
                                                    </label>
                                                    <label class="form-check-label cursor-pointer d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                        <input class="form-check-input" type="radio" name="skor_kepala[<?= $qK['id'] ?>]" value="5" <?= $selSkorK === 5 ? 'checked' : '' ?>> 5 (Sangat Memuaskan)
                                                    </label>
                                                    <label class="form-check-label cursor-pointer d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                                        <input class="form-check-input" type="radio" name="skor_kepala[<?= $qK['id'] ?>]" value="0" <?= $selSkorK === 0 ? 'checked' : '' ?>> 0 (N/A)
                                                    </label>
                                                </div>

                                                <!-- Catatan / Bukti Pendukung -->
                                                <div class="ms-4 mt-2">
                                                    <input type="text" name="catatan_kepala[<?= $qK['id'] ?>]" class="form-control" placeholder="Tuliskan catatan evaluasi atau bukti pendukung..." value="<?= e($catatanK) ?>" style="border-radius:6px;font-size:0.88rem;">
                                                </div>
                                            </div>
                                            <?php endforeach; ?>

                                            <div class="modal-footer border-top px-0 pb-0 mt-4">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-purple text-white px-4" style="background:var(--purple);">
                                                    <i class="bi bi-check2-circle me-1"></i> Simpan Penilaian Kepala LPM
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END MODAL -->

                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANE REFLEKSI DIRI KEPALA LPM -->
        <div class="tab-pane fade" id="pane-kepala-diri" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius:var(--radius-lg);">
                <div class="card-header bg-white border-bottom p-4">
                    <h4 class="fw-bold mb-1" style="color:var(--navy);">Evaluasi &amp; Refleksi Diri Kepala LPM</h4>
                    <p class="text-muted mb-0" style="font-size:0.88rem;">
                        Tuliskan refleksi kinerja pimpinan selama Tahun Akademik <?= e($tahun_akademik) ?>.
                    </p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="">
                        <input type="hidden" name="form_action" value="submit_evaluasi_diri">

                        <?php 
                        $noUrutKDiri = 1;
                        foreach ($pertanyaanDiri as $item): 
                            $ansVal = $savedAnsDiri[$item['id']] ?? '';
                        ?>
                        <div class="mb-4 pb-3 border-bottom">
                            <label class="form-label fw-bold d-flex align-items-start gap-2 mb-2" style="font-size:0.95rem;color:var(--navy);">
                                <span class="badge bg-secondary" style="font-size:0.8rem;border-radius:6px;"><?= $noUrutKDiri++ ?></span>
                                <span><?= e($item['pertanyaan']) ?></span>
                            </label>
                            <div class="ms-4">
                                <textarea name="jawaban_diri[<?= $item['id'] ?>]" class="form-control" rows="3" placeholder="Tuliskan refleksi capaian kinerja..." style="border-radius:8px;font-size:0.9rem;"><?= e($ansVal) ?></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-evaluasi-primary px-4 py-2" style="border-radius:8px;">
                                <i class="bi bi-send-fill me-1"></i> Simpan Evaluasi Diri
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<?php if (!empty($_GET['tab']) || !empty($_GET['open_modal'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (($_GET['tab'] ?? '') === 'antar'): ?>
    var tabBtn = document.getElementById('tab-antar');
    if (tabBtn && typeof bootstrap !== 'undefined') {
        bootstrap.Tab.getOrCreateInstance(tabBtn).show();
    }
    <?php endif; ?>
    <?php if (!empty($_GET['open_modal'])): ?>
    setTimeout(function() {
        var mEl = document.getElementById('modalNilaiRekan_<?= (int)$_GET['open_modal'] ?>');
        if (mEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(mEl).show();
        }
    }, 300);
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

