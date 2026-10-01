<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Konten & Formulir Layanan';
$db = getDB();

$flash = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $active_tab = $_POST['tab'] ?? 'pelatihan';

    // 1. Settings: Feedback
    if ($action === 'save_feedback_setting') {
        setPengaturan('form_feedback_badge', trim($_POST['form_feedback_badge'] ?? 'Kanal Aspirasi'));
        setPengaturan('form_feedback_title', trim($_POST['form_feedback_title'] ?? 'Kritik Saran & Feedback Mutu'));
        setPengaturan('form_feedback_desc', trim($_POST['form_feedback_desc'] ?? ''));
        setPengaturan('form_feedback_categories', trim($_POST['form_feedback_categories'] ?? ''));
        setPengaturan('form_feedback_email_recipient', trim($_POST['form_feedback_email_recipient'] ?? 'lpm@unika.ac.id'));
        setPengaturan('form_feedback_success_msg', trim($_POST['form_feedback_success_msg'] ?? ''));
        setPengaturan('form_feedback_btn_text', trim($_POST['form_feedback_btn_text'] ?? 'Kirim Masukan'));
        
        $_SESSION['flash'] = 'Pengaturan Formulir Kritik & Saran berhasil disimpan.';
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=feedback');
    }

    // 2. Settings: Kunjungan
    if ($action === 'save_kunjungan_setting') {
        setPengaturan('form_kunjungan_badge', trim($_POST['form_kunjungan_badge'] ?? 'PELAYANAN KUNJUNGAN INSTANSI LUAR'));
        setPengaturan('form_kunjungan_title', trim($_POST['form_kunjungan_title'] ?? 'Pelayanan Kunjungan ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata'));
        setPengaturan('form_kunjungan_desc', trim($_POST['form_kunjungan_desc'] ?? 'Diperuntukkan bagi universitas, sekolah, atau instansi luar yang ingin melakukan studi banding, kunjungan kerja, atau benchmarking ke Lembaga Penjaminan Mutu (LPM) UNIKA Soegijapranata.'));
        setPengaturan('kunjungan_jam_mulai', trim($_POST['kunjungan_jam_mulai'] ?? '08:00'));
        setPengaturan('kunjungan_jam_selesai', trim($_POST['kunjungan_jam_selesai'] ?? '15:00'));
        setPengaturan('kunjungan_max_peserta', max(1, min(100, (int)($_POST['kunjungan_max_peserta'] ?? 20))));
        setPengaturan('kunjungan_jadwal_mode', trim($_POST['kunjungan_jadwal_mode'] ?? 'jumat_minggu_4_flexible'));
        setPengaturan('kunjungan_min_lead_days', max(1, min(90, (int)($_POST['kunjungan_min_lead_days'] ?? 14))));
        setPengaturan('kunjungan_narasi_fasilitas', trim($_POST['kunjungan_narasi_fasilitas'] ?? 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata menyediakan fasilitas bagi perguruan tinggi/lembaga yang hendak melaksanakan studi banding/benchmarking terkait pengelolaan penjaminan mutu, SPMI, AMI ataupun akreditasi.'));
        setPengaturan('kunjungan_narasi_jadwal', trim($_POST['kunjungan_narasi_jadwal'] ?? 'Studi Banding dijadwalkan di setiap Jumat Minggu ke-IV.'));
        setPengaturan('kunjungan_narasi_prosedur', trim($_POST['kunjungan_narasi_prosedur'] ?? 'Bagi perguruan tinggi/lembaga yang akan melaksanakan studi banding ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata dapat mengajukan permohonan terlebih dahulu dengan melengkapi formulir online yang disediakan di halaman ini.'));
        setPengaturan('kunjungan_narasi_catatan', trim($_POST['kunjungan_narasi_catatan'] ?? 'Penyampaian permohonan kunjungan studi banding paling lambat 2 minggu sebelum kegiatan berlangsung.'));
        setPengaturan('form_kunjungan_file_label', trim($_POST['form_kunjungan_file_label'] ?? 'Upload Surat Permohonan Kunjungan Resmi (PDF, Max 10 MB)'));
        setPengaturan('form_kunjungan_success_msg', trim($_POST['form_kunjungan_success_msg'] ?? ''));
        setPengaturan('form_kunjungan_btn_text', trim($_POST['form_kunjungan_btn_text'] ?? 'Kirim Permohonan Kunjungan Resmi'));

        $_SESSION['flash'] = 'Pengaturan Formulir Permohonan Kunjungan berhasil disimpan.';
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=kunjungan');
    }

    // 3. Pelatihan: Add / Edit / Delete
    if ($action === 'save_pelatihan') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_pelatihan'] ?? '');
        $kategori = trim($_POST['kategori'] ?? 'Pelatihan Mutu');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $sasaran = trim($_POST['sasaran_peserta'] ?? '');
        $durasi = trim($_POST['durasi'] ?? '');
        $materi = trim($_POST['materi_pokok'] ?? '');
        $metode = trim($_POST['metode'] ?? 'Luring / Hybrid');
        $fasilitas = trim($_POST['fasilitas'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($nama)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_pelatihan SET 
                    nama_pelatihan = ?, kategori = ?, deskripsi = ?, sasaran_peserta = ?, 
                    durasi = ?, materi_pokok = ?, metode = ?, fasilitas = ?, urutan = ?, is_active = ? 
                    WHERE id = ?");
                $stmt->execute([$nama, $kategori, $deskripsi, $sasaran, $durasi, $materi, $metode, $fasilitas, $urutan, $is_active, $id]);
                $_SESSION['flash'] = 'Program pelatihan "' . e($nama) . '" berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO layanan_pelatihan 
                    (nama_pelatihan, kategori, deskripsi, sasaran_peserta, durasi, materi_pokok, metode, fasilitas, urutan, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nama, $kategori, $deskripsi, $sasaran, $durasi, $materi, $metode, $fasilitas, $urutan, $is_active]);
                $_SESSION['flash'] = 'Program pelatihan baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Nama program pelatihan wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=pelatihan');
    }

    if ($action === 'delete_pelatihan') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM layanan_pelatihan WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Program pelatihan berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=pelatihan');
    }

    // 4. Biaya: Add / Edit / Delete
    if ($action === 'save_biaya') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_paket'] ?? '');
        $nominal = trim($_POST['nominal'] ?? '');
        $periode = trim($_POST['periode_satuan'] ?? 'per peserta');
        $deskripsi = trim($_POST['deskripsi_singkat'] ?? '');
        $fasilitas = trim($_POST['rincian_fasilitas'] ?? '');
        $is_populer = isset($_POST['is_populer']) ? 1 : 0;
        $urutan = (int)($_POST['urutan'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($nama) && !empty($nominal)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_biaya SET 
                    nama_paket = ?, nominal = ?, periode_satuan = ?, deskripsi_singkat = ?, 
                    rincian_fasilitas = ?, is_populer = ?, urutan = ?, is_active = ? 
                    WHERE id = ?");
                $stmt->execute([$nama, $nominal, $periode, $deskripsi, $fasilitas, $is_populer, $urutan, $is_active, $id]);
                $_SESSION['flash'] = 'Paket biaya "' . e($nama) . '" berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO layanan_biaya 
                    (nama_paket, nominal, periode_satuan, deskripsi_singkat, rincian_fasilitas, is_populer, urutan, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nama, $nominal, $periode, $deskripsi, $fasilitas, $is_populer, $urutan, $is_active]);
                $_SESSION['flash'] = 'Paket biaya baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Nama paket dan nominal biaya wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=biaya');
    }

    if ($action === 'delete_biaya') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM layanan_biaya WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Paket biaya berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=biaya');
    }

    // 5. Jadwal: Add / Edit / Delete
    if ($action === 'save_jadwal') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_kegiatan'] ?? '');
        $mulai = trim($_POST['tanggal_mulai'] ?? '');
        $selesai = trim($_POST['tanggal_selesai'] ?? '');
        if (empty($selesai)) $selesai = $mulai;
        $lokasi = trim($_POST['lokasi'] ?? 'Kampus UNIKA Soegijapranata');
        $status = trim($_POST['status'] ?? 'Terlaksana');
        $institusi = trim($_POST['institusi_peserta'] ?? '');
        $jumlah = (int)($_POST['jumlah_peserta'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);

        if (!empty($nama) && !empty($mulai)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_jadwal SET 
                    nama_kegiatan = ?, tanggal_mulai = ?, tanggal_selesai = ?, lokasi = ?, 
                    status = ?, institusi_peserta = ?, jumlah_peserta = ?, keterangan = ?, urutan = ? 
                    WHERE id = ?");
                $stmt->execute([$nama, $mulai, $selesai, $lokasi, $status, $institusi, $jumlah, $keterangan, $urutan, $id]);
                $_SESSION['flash'] = 'Agenda jadwal "' . e($nama) . '" berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO layanan_jadwal 
                    (nama_kegiatan, tanggal_mulai, tanggal_selesai, lokasi, status, institusi_peserta, jumlah_peserta, keterangan, urutan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nama, $mulai, $selesai, $lokasi, $status, $institusi, $jumlah, $keterangan, $urutan]);
                $_SESSION['flash'] = 'Agenda jadwal baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Nama kegiatan dan tanggal mulai wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=jadwal');
    }

    if ($action === 'delete_jadwal') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM layanan_jadwal WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Agenda kegiatan berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=jadwal');
    }

    // 6. Brosur: Add / Edit / Delete
    if ($action === 'save_brosur') {
        $id = (int)($_POST['id'] ?? 0);
        $judul = trim($_POST['judul_brosur'] ?? '');
        $tahun = (int)($_POST['tahun'] ?? date('Y'));
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $link = trim($_POST['link_pendaftaran'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $file_brosur = '';
        $tipe_file   = 'pdf';
        $ukuran_file = '';
        if ($id > 0) {
            $cur = $db->prepare("SELECT file_brosur, tipe_file, ukuran_file FROM layanan_brosur WHERE id = ?");
            $cur->execute([$id]);
            $row_cur = $cur->fetch(PDO::FETCH_ASSOC);
            if ($row_cur) {
                $file_brosur = $row_cur['file_brosur'] ?? '';
                $tipe_file   = $row_cur['tipe_file'] ?? 'pdf';
                $ukuran_file = $row_cur['ukuran_file'] ?? '';
            }
        }

        // Handle File Upload (PDF, DOCX, DOC, PNG, JPG, JPEG, WEBP)
        if (isset($_FILES['file_brosur']) && $_FILES['file_brosur']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/layanan/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_tmp  = $_FILES['file_brosur']['tmp_name'];
            $file_name = $_FILES['file_brosur']['name'];
            $file_size = (int)$_FILES['file_brosur']['size'];
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['pdf', 'docx', 'doc', 'png', 'jpg', 'jpeg', 'webp'];

            if (in_array($ext, $allowed_exts)) {
                // Tentukan tipe file
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $tipe_file = 'image';
                } elseif (in_array($ext, ['docx', 'doc'])) {
                    $tipe_file = 'docx';
                } else {
                    $tipe_file = 'pdf';
                }

                // Hitung ukuran file ramah pengguna
                if ($file_size >= 1048576) {
                    $ukuran_file = number_format($file_size / 1048576, 2) . ' MB';
                } else {
                    $ukuran_file = number_format(max(1, $file_size / 1024), 0) . ' KB';
                }

                $new_filename = 'brosur_lpm_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    // Hapus file lama jika ada dan merupakan file yang diupload sistem
                    if (!empty($file_brosur) && file_exists($upload_dir . $file_brosur) && strpos($file_brosur, 'brosur_lpm_') === 0) {
                        @unlink($upload_dir . $file_brosur);
                    }
                    $file_brosur = $new_filename;
                }
            } else {
                $_SESSION['flash_error'] = 'Format berkas tidak didukung. Harap unggah berkas berformat PDF, Word (DOCX/DOC), atau Foto (PNG, JPG, JPEG, WEBP).';
                redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=brosur');
            }
        }

        if (!empty($judul)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_brosur SET 
                    judul_brosur = ?, tahun = ?, deskripsi = ?, link_pendaftaran = ?, 
                    file_brosur = ?, tipe_file = ?, ukuran_file = ?, is_active = ? 
                    WHERE id = ?");
                $stmt->execute([$judul, $tahun, $deskripsi, $link, $file_brosur, $tipe_file, $ukuran_file, $is_active, $id]);
                $_SESSION['flash'] = 'Brosur "' . e($judul) . '" berhasil diperbarui.';
            } else {
                if (empty($file_brosur)) {
                    $_SESSION['flash_error'] = 'Harap pilih berkas brosur (PDF, Word, atau Foto) untuk diunggah.';
                    redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=brosur');
                }
                $stmt = $db->prepare("INSERT INTO layanan_brosur 
                    (judul_brosur, tahun, deskripsi, link_pendaftaran, file_brosur, tipe_file, ukuran_file, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $tahun, $deskripsi, $link, $file_brosur, $tipe_file, $ukuran_file, $is_active]);
                $_SESSION['flash'] = 'Brosur pelatihan baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Judul brosur wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=brosur');
    }

    if ($action === 'delete_brosur') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $cur = $db->prepare("SELECT file_brosur FROM layanan_brosur WHERE id = ?");
            $cur->execute([$id]);
            $fn = $cur->fetchColumn();
            if ($fn && file_exists(__DIR__ . '/../uploads/layanan/' . $fn) && strpos($fn, 'brosur_lpm_') === 0) {
                @unlink(__DIR__ . '/../uploads/layanan/' . $fn);
            }
            $stmt = $db->prepare("DELETE FROM layanan_brosur WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Brosur berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=brosur');
    }

    // 7. Keunggulan: Add / Edit / Delete
    if ($action === 'save_keunggulan') {
        $id = (int)($_POST['id'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $icon = trim($_POST['icon'] ?? 'bi-award-fill');
        $urutan = (int)($_POST['urutan'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($judul)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_keunggulan SET judul = ?, deskripsi = ?, icon = ?, urutan = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$judul, $deskripsi, $icon, $urutan, $is_active, $id]);
                $_SESSION['flash'] = 'Standar keunggulan berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO layanan_keunggulan (judul, deskripsi, icon, urutan, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $deskripsi, $icon, $urutan, $is_active]);
                $_SESSION['flash'] = 'Standar keunggulan baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Judul standar keunggulan wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=keunggulan');
    }

    if ($action === 'delete_keunggulan') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM layanan_keunggulan WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Standar keunggulan berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=keunggulan');
    }

    // 8. Alur: Add / Edit / Delete
    if ($action === 'save_alur') {
        $id = (int)($_POST['id'] ?? 0);
        $langkah = (int)($_POST['langkah_ke'] ?? 1);
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($judul)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_alur SET langkah_ke = ?, judul = ?, deskripsi = ?, urutan = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$langkah, $judul, $deskripsi, $urutan, $is_active, $id]);
                $_SESSION['flash'] = 'Tahapan alur berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO layanan_alur (langkah_ke, judul, deskripsi, urutan, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$langkah, $judul, $deskripsi, $urutan, $is_active]);
                $_SESSION['flash'] = 'Tahapan alur baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Judul tahapan alur wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=alur');
    }

    if ($action === 'delete_alur') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM layanan_alur WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Tahapan alur berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=alur');
    }

    // 9. FAQ: Add / Edit / Delete
    if ($action === 'save_faq') {
        $id = (int)($_POST['id'] ?? 0);
        $pertanyaan = trim($_POST['pertanyaan'] ?? '');
        $jawaban = trim($_POST['jawaban'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!empty($pertanyaan)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE layanan_faq SET pertanyaan = ?, jawaban = ?, urutan = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$pertanyaan, $jawaban, $urutan, $is_active, $id]);
                $_SESSION['flash'] = 'Pertanyaan FAQ berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO layanan_faq (pertanyaan, jawaban, urutan, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$pertanyaan, $jawaban, $urutan, $is_active]);
                $_SESSION['flash'] = 'Pertanyaan FAQ baru berhasil ditambahkan.';
            }
        } else {
            $_SESSION['flash_error'] = 'Pertanyaan FAQ wajib diisi.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=faq');
    }

    if ($action === 'delete_faq') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM layanan_faq WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Pertanyaan FAQ berhasil dihapus.';
        }
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=faq');
    }

    // 10. Pengaturan Banner Hero, Narasi & Kontak Email Pelatihan
    if ($action === 'save_pelatihan_umum') {
        setPengaturan('pelatihan_hero_title', trim($_POST['pelatihan_hero_title'] ?? ''));
        setPengaturan('pelatihan_hero_desc', trim($_POST['pelatihan_hero_desc'] ?? ''));
        setPengaturan('pelatihan_narasi_lengkap', trim($_POST['pelatihan_narasi_lengkap'] ?? ''));
        setPengaturan('pelatihan_email_kontak', trim($_POST['pelatihan_email_kontak'] ?? 'lpm@unika.ac.id'));
        setPengaturan('pelatihan_telepon', trim($_POST['pelatihan_telepon'] ?? ''));
        setPengaturan('pelatihan_jam_layanan', trim($_POST['pelatihan_jam_layanan'] ?? ''));
        setPengaturan('pelatihan_alamat', trim($_POST['pelatihan_alamat'] ?? ''));

        $_SESSION['flash'] = 'Pengaturan narasi & kontak info pelatihan berhasil disimpan.';
        redirect(SITE_URL . '/admin/layanan-form-setting.php?tab=pelatihan-umum');
    }
}

$tab = $_GET['tab'] ?? 'pelatihan-umum';

// Fetch lists for tabs
$pelatihan_list  = [];
$biaya_list      = [];
$jadwal_list     = [];
$brosur_list     = [];
$keunggulan_list = [];
$alur_list       = [];
$faq_list        = [];

try {
    $pelatihan_list  = $db->query("SELECT * FROM layanan_pelatihan ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $biaya_list      = $db->query("SELECT * FROM layanan_biaya ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $jadwal_list     = $db->query("SELECT * FROM layanan_jadwal ORDER BY tanggal_mulai DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $brosur_list     = $db->query("SELECT * FROM layanan_brosur ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $keunggulan_list = $db->query("SELECT * FROM layanan_keunggulan ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $alur_list       = $db->query("SELECT * FROM layanan_alur ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $faq_list        = $db->query("SELECT * FROM layanan_faq ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Pelatihan Settings
$pelatihan_hero_title     = getPengaturan('pelatihan_hero_title', 'Info Pelatihan Lembaga Penjaminan Mutu');
$pelatihan_hero_desc      = getPengaturan('pelatihan_hero_desc', 'Program Pelatihan, Bimbingan Teknis, Sertifikasi Auditor Mutu Internal (AMI), dan Klinik Akreditasi Perguruan Tinggi Mitra');
$pelatihan_narasi_lengkap = getPengaturan('pelatihan_narasi_lengkap', 'Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata berkomitmen mengawal dan menumbuhkan budaya mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global.');
$pelatihan_email          = getPengaturan('pelatihan_email_kontak', 'lpm@unika.ac.id');
$pelatihan_telepon        = getPengaturan('pelatihan_telepon', '(024) 8441555 Ext. 1473');
$pelatihan_jam            = getPengaturan('pelatihan_jam_layanan', 'Senin – Jumat (08:00 – 15:30 WIB)');
$pelatihan_alamat         = getPengaturan('pelatihan_alamat', 'Kampus UNIKA Bendan Dhuwur, Semarang');

// Feedback Settings
$default_categories = "Kritik & Saran Pelayanan\nSaran & Masukan Perbaikan LPM\nAspirasi Peningkatan Mutu\nFeedback / Umpan Balik Kemitraan\nApresiasi Layanan & Testimoni";
$fb_badge       = getPengaturan('form_feedback_badge', 'Kanal Aspirasi');
$fb_title       = getPengaturan('form_feedback_title', 'Kritik Saran & Feedback Mutu');
$fb_desc        = getPengaturan('form_feedback_desc', 'Sampaikan kritik konstruktif, saran perbaikan, apresiasi, atau umpan balik mutu dari institusi Anda kepada Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata.');
$fb_categories  = getPengaturan('form_feedback_categories', $default_categories);
$fb_email       = getPengaturan('form_feedback_email_recipient', 'lpm@unika.ac.id');
$fb_success     = getPengaturan('form_feedback_success_msg', 'Terima kasih! Kritik, saran, atau masukan Anda telah berhasil dikirim ke tim LPM UNIKA. Kami sangat mengapresiasi kontribusi institusi Anda demi peningkatan mutu layanan.');
$fb_btn         = getPengaturan('form_feedback_btn_text', 'Kirim Masukan');

// Kunjungan Settings
$kj_badge       = getPengaturan('form_kunjungan_badge', 'PELAYANAN KUNJUNGAN INSTANSI LUAR');
$kj_title       = getPengaturan('form_kunjungan_title', 'Pelayanan Kunjungan ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata');
$kj_desc        = getPengaturan('form_kunjungan_desc', 'Diperuntukkan bagi universitas, sekolah, atau instansi luar yang ingin melakukan studi banding, kunjungan kerja, atau benchmarking ke Lembaga Penjaminan Mutu (LPM) UNIKA Soegijapranata.');
$kj_jam_mulai   = getPengaturan('kunjungan_jam_mulai', '08:00');
$kj_jam_selesai = getPengaturan('kunjungan_jam_selesai', '15:00');
$kj_max_peserta = (int)getPengaturan('kunjungan_max_peserta', '20');
$kj_jadwal_mode = getPengaturan('kunjungan_jadwal_mode', 'jumat_minggu_4_flexible');
$kj_min_lead_days = (int)getPengaturan('kunjungan_min_lead_days', '14');
$kj_narasi_fasilitas = getPengaturan('kunjungan_narasi_fasilitas', 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata menyediakan fasilitas bagi perguruan tinggi/lembaga yang hendak melaksanakan studi banding/benchmarking terkait pengelolaan penjaminan mutu, SPMI, AMI ataupun akreditasi.');
$kj_narasi_jadwal = getPengaturan('kunjungan_narasi_jadwal', 'Studi Banding dijadwalkan di setiap Jumat Minggu ke-IV.');
$kj_narasi_prosedur = getPengaturan('kunjungan_narasi_prosedur', 'Bagi perguruan tinggi/lembaga yang akan melaksanakan studi banding ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata dapat mengajukan permohonan terlebih dahulu dengan melengkapi formulir online yang disediakan di halaman ini.');
$kj_narasi_catatan = getPengaturan('kunjungan_narasi_catatan', 'Penyampaian permohonan kunjungan studi banding paling lambat 2 minggu sebelum kegiatan berlangsung.');
$kj_file_label  = getPengaturan('form_kunjungan_file_label', 'Upload Surat Permohonan Kunjungan Resmi (PDF, Max 10 MB)');
$kj_success     = getPengaturan('form_kunjungan_success_msg', 'Permohonan kunjungan resmi dari institusi Anda berhasil dikirim ke LPM UNIKA! Tim kami akan melakukan verifikasi dan mengonfirmasi via email/WhatsApp PIC.');
$kj_btn         = getPengaturan('form_kunjungan_btn_text', 'Kirim Permohonan Kunjungan Resmi');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--navy);margin:0;display:flex;align-items:center;gap:10px;">
            <i class="bi bi-briefcase-fill text-primary"></i> Pengelolaan Layanan LPM (3 Sub-Menu Publik)
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola narasi, brosur, formulir pendaftaran, dan survei mutu yang tersinkronisasi langsung dengan 3 sub-menu layanan di web publik.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= SITE_URL ?>/pelatihan.php" target="_blank" class="btn-outline" style="font-size:0.82rem;">
            <i class="bi bi-mortarboard me-1"></i> 1. Info Pelatihan
        </a>
        <a href="<?= SITE_URL ?>/kunjungan.php" target="_blank" class="btn-outline" style="font-size:0.82rem;">
            <i class="bi bi-building-fill-check me-1"></i> 2. Kunjungan Studi Banding
        </a>
        <a href="<?= SITE_URL ?>/survei-kepuasan.php" target="_blank" class="btn-outline" style="font-size:0.82rem;">
            <i class="bi bi-emoji-smile me-1"></i> 3. Survey Kepuasan
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><?= $flash ?></div>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert-lpm alert-danger mb-4 d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div><?= e($flash_error) ?></div>
</div>
<?php endif; ?>

<!-- Tabs Switcher Selaras 3 Sub-Menu Layanan Publik -->
<div class="d-flex gap-2 border-bottom mb-4 pb-2 flex-wrap" style="overflow-x:auto;">
    <a href="layanan-form-setting.php?tab=pelatihan-umum" class="btn btn-sm <?= $tab === 'pelatihan-umum' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-card-text me-1"></i> 1.1 Narasi Info Pelatihan
    </a>
    <a href="layanan-form-setting.php?tab=brosur" class="btn btn-sm <?= $tab === 'brosur' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-file-earmark-richtext me-1"></i> 1.2 Upload Brosur Pelatihan (<?= count($brosur_list) ?>)
    </a>
    <a href="layanan-form-setting.php?tab=kunjungan" class="btn btn-sm <?= $tab === 'kunjungan' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-building-check me-1"></i> 2. Form Kunjungan Studi Banding
    </a>
    <a href="layanan-form-setting.php?tab=feedback" class="btn btn-sm <?= $tab === 'feedback' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-emoji-smile me-1"></i> 3. Form Survey Kepuasan Layanan
    </a>
    <a href="layanan-form-setting.php?tab=jadwal" class="btn btn-sm <?= $tab === 'jadwal' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-calendar-check me-1"></i> Arsip Jadwal (<?= count($jadwal_list) ?>)
    </a>
    <a href="layanan-form-setting.php?tab=keunggulan" class="btn btn-sm <?= $tab === 'keunggulan' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-award me-1"></i> Standar Keunggulan (<?= count($keunggulan_list) ?>)
    </a>
    <a href="layanan-form-setting.php?tab=alur" class="btn btn-sm <?= $tab === 'alur' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-diagram-3 me-1"></i> Alur Kerjasama (<?= count($alur_list) ?>)
    </a>
    <a href="layanan-form-setting.php?tab=faq" class="btn btn-sm <?= $tab === 'faq' ? 'btn-primary fw-bold' : 'btn-light text-muted' ?>" style="border-radius:20px;padding:0.45rem 1.15rem;">
        <i class="bi bi-question-circle me-1"></i> FAQ Pelatihan (<?= count($faq_list) ?>)
    </a>
</div>

<!-- ========================================================================= -->
<!-- TAB 1: PELATIHAN EKSTERNAL                                               -->
<!-- ========================================================================= -->
<?php if ($tab === 'pelatihan'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-mortarboard-fill text-primary me-2"></i> Daftar Program Pelatihan Eksternal
            </h5>
            <small class="text-muted">Kelola program pelatihan penjaminan mutu, bimtek SPMI, dan sertifikasi auditor yang ditawarkan ke mitra luar.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalPelatihan" onclick="resetFormPelatihan()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Program Pelatihan
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:50px;">Urutan</th>
                    <th>Nama Program Pelatihan</th>
                    <th>Kategori</th>
                    <th>Sasaran &amp; Durasi</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pelatihan_list)): ?>
                    <?php foreach ($pelatihan_list as $pel): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= (int)$pel['urutan'] ?></td>
                        <td>
                            <div class="fw-bold text-navy" style="font-size:0.92rem;"><?= htmlspecialchars($pel['nama_pelatihan']) ?></div>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars(truncate($pel['deskripsi'] ?? '', 110)) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($pel['kategori']) ?></span>
                        </td>
                        <td>
                            <div style="font-size:0.82rem;"><strong>Durasi:</strong> <?= htmlspecialchars($pel['durasi'] ?? '-') ?></div>
                            <div style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars(truncate($pel['sasaran_peserta'] ?? '-', 50)) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($pel['metode'] ?? 'Luring') ?></span>
                        </td>
                        <td>
                            <?php if ($pel['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editPelatihan(<?= json_encode($pel, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit Program">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus program pelatihan ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_pelatihan">
                                    <input type="hidden" name="tab" value="pelatihan">
                                    <input type="hidden" name="id" value="<?= $pel['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Program">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data program pelatihan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Pelatihan -->
<div class="modal fade" id="modalPelatihan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="save_pelatihan">
            <input type="hidden" name="tab" value="pelatihan">
            <input type="hidden" name="id" id="pel_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalPelTitle"><i class="bi bi-mortarboard me-2"></i> Tambah Program Pelatihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nama Program Pelatihan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pelatihan" id="pel_nama" class="form-control" required placeholder="Contoh: Pelatihan & Sertifikasi Auditor Mutu Internal">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Kategori</label>
                    <input type="text" name="kategori" id="pel_kategori" class="form-control" value="Sertifikasi Kompetensi" placeholder="Sertifikasi / Tata Kelola">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi Ringkas</label>
                    <textarea name="deskripsi" id="pel_deskripsi" rows="3" class="form-control" placeholder="Tuliskan tujuan dan ikhtisar program pelatihan..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Sasaran Peserta</label>
                    <input type="text" name="sasaran_peserta" id="pel_sasaran" class="form-control" placeholder="Contoh: Dosen, Tim Penjaminan Mutu, Pimpinan PT">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Durasi Pelatihan</label>
                    <input type="text" name="durasi" id="pel_durasi" class="form-control" placeholder="Contoh: 2 Hari (16 Jam Pelajaran)">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Metode Pelaksanaan</label>
                    <input type="text" name="metode" id="pel_metode" class="form-control" value="Luring (Tatap Muka di Kampus UNIKA / In-House Mitra)">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Urutan</label>
                    <input type="number" name="urutan" id="pel_urutan" class="form-control" value="1">
                </div>
                <div class="col-md-3 d-flex align-items-center mt-4 pt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="pel_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="pel_is_active">Aktif di Web</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Materi Pokok (Satu per baris)</label>
                    <textarea name="materi_pokok" id="pel_materi" rows="4" class="form-control" placeholder="1. Regulasi SN-Dikti&#10;2. Teknik Audit AMI&#10;3. Penyusunan Laporan"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Fasilitas Peserta (Satu per baris)</label>
                    <textarea name="fasilitas" id="pel_fasilitas" rows="4" class="form-control" placeholder="Sertifikat Resmi LPM&#10;Modul Lengkap Cetak & Digital&#10;Template Berkas"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Program</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 2: BIAYA PELATIHAN                                                   -->
<!-- ========================================================================= -->
<?php if ($tab === 'biaya'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-tags-fill text-primary me-2"></i> Skema Paket &amp; Biaya Pelatihan
            </h5>
            <small class="text-muted">Kelola paket investasi pembiayaan pelatihan reguler, in-house training, dan klinik akreditasi.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalBiaya" onclick="resetFormBiaya()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Paket Biaya
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:50px;">Urutan</th>
                    <th>Nama Paket</th>
                    <th>Nominal Biaya</th>
                    <th>Deskripsi Singkat</th>
                    <th>Badge Populer</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($biaya_list)): ?>
                    <?php foreach ($biaya_list as $by): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= (int)$by['urutan'] ?></td>
                        <td class="fw-bold text-navy">
                            <?= htmlspecialchars($by['nama_paket']) ?>
                        </td>
                        <td>
                            <div class="fw-bold text-primary" style="font-size:1rem;"><?= htmlspecialchars($by['nominal']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($by['periode_satuan'] ?? '') ?></small>
                        </td>
                        <td style="max-width:300px;">
                            <?= htmlspecialchars(truncate($by['deskripsi_singkat'] ?? '', 90)) ?>
                        </td>
                        <td>
                            <?php if ($by['is_populer']): ?>
                                <span class="badge bg-warning text-dark fw-bold"><i class="bi bi-star-fill me-1"></i> Populer</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Biasa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($by['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editBiaya(<?= json_encode($by, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit Paket">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus paket biaya ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_biaya">
                                    <input type="hidden" name="tab" value="biaya">
                                    <input type="hidden" name="id" value="<?= $by['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Paket">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data paket biaya.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Biaya -->
<div class="modal fade" id="modalBiaya" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="save_biaya">
            <input type="hidden" name="tab" value="biaya">
            <input type="hidden" name="id" id="by_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalBiayaTitle"><i class="bi bi-tags me-2"></i> Tambah Paket Biaya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nama Paket <span class="text-danger">*</span></label>
                    <input type="text" name="nama_paket" id="by_nama" class="form-control" required placeholder="Contoh: Paket Reguler / In-House Training">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Nominal Biaya <span class="text-danger">*</span></label>
                    <input type="text" name="nominal" id="by_nominal" class="form-control" required placeholder="Contoh: Rp 2.500.000">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Satuan / Periode</label>
                    <input type="text" name="periode_satuan" id="by_periode" class="form-control" value="per peserta" placeholder="per peserta / per paket">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi Singkat Paket</label>
                    <input type="text" name="deskripsi_singkat" id="by_deskripsi" class="form-control" placeholder="Pelatihan tatap muka intensif di Kampus UNIKA Semarang">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Rincian Fasilitas / Keuntungan (Satu per baris)</label>
                    <textarea name="rincian_fasilitas" id="by_fasilitas" rows="4" class="form-control" placeholder="Sertifikat Pelatihan Resmi LPM&#10;Modul Pembelajaran Lengkap&#10;Makan Siang & Coffee Break&#10;Free Konsultasi 1 Bulan"></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Urutan</label>
                    <input type="number" name="urutan" id="by_urutan" class="form-control" value="1">
                </div>
                <div class="col-md-4 d-flex align-items-center mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_populer" id="by_is_populer" value="1">
                        <label class="form-check-label fw-bold" for="by_is_populer">Tandai Populer (Highlight)</label>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-center mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="by_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="by_is_active">Aktif di Web</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Paket Biaya</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 3: JADWAL TERLAKSANA                                                 -->
<!-- ========================================================================= -->
<?php if ($tab === 'jadwal'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-calendar-check-fill text-primary me-2"></i> Rekapitulasi Jadwal Kegiatan Pelatihan
            </h5>
            <small class="text-muted">Kelola riwayat agenda pelatihan terlaksana dan pengumuman batch yang sedang dibuka pendaftarannya.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalJadwal" onclick="resetFormJadwal()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Agenda Kegiatan
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th>Waktu Pelaksanaan</th>
                    <th>Nama Kegiatan</th>
                    <th>Lokasi</th>
                    <th>Peserta / Mitra</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($jadwal_list)): ?>
                    <?php foreach ($jadwal_list as $jd): ?>
                    <tr>
                        <td style="white-space:nowrap;font-weight:600;">
                            <i class="bi bi-calendar-event text-primary me-1"></i>
                            <?= date('d/m/Y', strtotime($jd['tanggal_mulai'])) ?>
                            <?php if (!empty($jd['tanggal_selesai']) && $jd['tanggal_selesai'] !== $jd['tanggal_mulai']): ?>
                            s.d. <?= date('d/m/Y', strtotime($jd['tanggal_selesai'])) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-navy"><?= htmlspecialchars($jd['nama_kegiatan']) ?></div>
                            <?php if (!empty($jd['keterangan'])): ?>
                            <small class="text-muted"><?= htmlspecialchars($jd['keterangan']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($jd['lokasi'] ?? '-') ?></td>
                        <td>
                            <?= htmlspecialchars($jd['institusi_peserta'] ?? '-') ?>
                            <?php if (!empty($jd['jumlah_peserta'])): ?>
                            <span class="badge bg-light text-dark border ms-1">(<?= (int)$jd['jumlah_peserta'] ?> Peserta)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($jd['status'] === 'Pendaftaran Dibuka'): ?>
                                <span class="badge bg-warning text-dark fw-bold"><i class="bi bi-bell-fill me-1"></i> Pendaftaran Dibuka</span>
                            <?php elseif ($jd['status'] === 'Akan Datang'): ?>
                                <span class="badge bg-info text-dark">Akan Datang</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Terlaksana</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editJadwal(<?= json_encode($jd, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit Jadwal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus agenda jadwal ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_jadwal">
                                    <input type="hidden" name="tab" value="jadwal">
                                    <input type="hidden" name="id" value="<?= $jd['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Jadwal">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada agenda jadwal pelatihan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Jadwal -->
<div class="modal fade" id="modalJadwal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="save_jadwal">
            <input type="hidden" name="tab" value="jadwal">
            <input type="hidden" name="id" id="jd_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalJadwalTitle"><i class="bi bi-calendar-check me-2"></i> Tambah Agenda Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Nama Kegiatan Pelatihan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kegiatan" id="jd_nama" class="form-control" required placeholder="Contoh: Pelatihan Auditor Mutu Internal (AMI) Batch I 2026">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" id="jd_mulai" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="jd_selesai" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Lokasi Pelaksanaan</label>
                    <input type="text" name="lokasi" id="jd_lokasi" class="form-control" value="Kampus UNIKA Soegijapranata, Semarang">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Status Kegiatan</label>
                    <select name="status" id="jd_status" class="form-select">
                        <option value="Terlaksana">Terlaksana</option>
                        <option value="Pendaftaran Dibuka">Pendaftaran Dibuka</option>
                        <option value="Akan Datang">Akan Datang</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Institusi Mitra / Peserta</label>
                    <input type="text" name="institusi_peserta" id="jd_peserta" class="form-control" placeholder="Contoh: Perwakilan 14 Perguruan Tinggi Swasta LLDIKTI VI">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Jumlah Peserta</label>
                    <input type="number" name="jumlah_peserta" id="jd_jumlah" class="form-control" value="0">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Keterangan Tambahan</label>
                    <input type="text" name="keterangan" id="jd_keterangan" class="form-control" placeholder="Contoh: Sertifikasi 32 Auditor Baru, Tingkat Kelulusan 100%">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 2: BROSUR PELATIHAN                                                  -->
<!-- ========================================================================= -->
<?php if ($tab === 'brosur'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-file-earmark-richtext-fill text-primary me-2"></i> Dokumen &amp; Poster Brosur Pelatihan
            </h5>
            <small class="text-muted">Kelola berkas brosur resmi program pelatihan mutu (PDF, Word DOCX, atau Foto/Gambar JPG/PNG) yang terpampang di halaman publik dengan aksi unduh.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalBrosur" onclick="resetFormBrosur()">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Brosur Baru
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="min-width:240px;">Judul &amp; Deskripsi Brosur</th>
                    <th style="width:90px;" class="text-center">Tahun</th>
                    <th>Format &amp; Berkas</th>
                    <th>Ukuran</th>
                    <th>Link Pendaftaran</th>
                    <th class="text-center">Status</th>
                    <th style="width:130px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($brosur_list)): ?>
                    <?php foreach ($brosur_list as $br): 
                        $t_file = strtolower($br['tipe_file'] ?? 'pdf');
                        $f_name = $br['file_brosur'] ?? '';
                        $f_ext  = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
                        if (in_array($f_ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                            $badge_format = '<span class="badge bg-success"><i class="bi bi-image me-1"></i>Gambar (' . strtoupper($f_ext) . ')</span>';
                        } elseif (in_array($f_ext, ['docx', 'doc'])) {
                            $badge_format = '<span class="badge bg-primary"><i class="bi bi-file-earmark-word me-1"></i>Word (' . strtoupper($f_ext) . ')</span>';
                        } else {
                            $badge_format = '<span class="badge bg-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</span>';
                        }
                    ?>
                    <tr>
                        <td class="fw-bold text-navy">
                            <div class="fs-6"><?= htmlspecialchars($br['judul_brosur']) ?></div>
                            <?php if (!empty($br['deskripsi'])): ?>
                            <small class="text-muted d-block mt-1 font-weight-normal"><?= htmlspecialchars(truncate($br['deskripsi'], 95)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($br['tahun']) ?></span></td>
                        <td>
                            <div class="mb-1"><?= $badge_format ?></div>
                            <a href="<?= SITE_URL ?>/uploads/layanan/<?= htmlspecialchars($br['file_brosur']) ?>" target="_blank" class="text-decoration-none fw-semibold text-secondary" style="font-size:0.8rem;word-break:break-all;">
                                <i class="bi bi-box-arrow-up-right me-1"></i><?= htmlspecialchars($br['file_brosur']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="text-muted small"><?= htmlspecialchars($br['ukuran_file'] ?: '-') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($br['link_pendaftaran'])): ?>
                            <a href="<?= htmlspecialchars($br['link_pendaftaran']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Form Daring
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($br['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="<?= SITE_URL ?>/uploads/layanan/<?= htmlspecialchars($br['file_brosur']) ?>" download class="btn btn-sm btn-outline-success" title="Unduh Berkas">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editBrosur(<?= json_encode($br, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit Brosur">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus berkas brosur ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_brosur">
                                    <input type="hidden" name="tab" value="brosur">
                                    <input type="hidden" name="id" value="<?= $br['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Brosur">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada dokumen brosur pelatihan yang diunggah.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Upload/Edit Brosur (Multi-Format) -->
<div class="modal fade" id="modalBrosur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="save_brosur">
            <input type="hidden" name="tab" value="brosur">
            <input type="hidden" name="id" id="br_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalBrosurTitle"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Upload Brosur Pelatihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-9">
                    <label class="form-label fw-bold">Judul Brosur / Panduan <span class="text-danger">*</span></label>
                    <input type="text" name="judul_brosur" id="br_judul" class="form-control" required placeholder="Contoh: Brosur Program Pelatihan & Kemitraan Mutu LPM SCU 2026">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tahun Terbit</label>
                    <input type="number" name="tahun" id="br_tahun" class="form-control" value="2026">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Unggah Berkas Brosur (PDF, DOCX, atau Foto) <span class="text-danger">*</span></label>
                    <input type="file" name="file_brosur" id="br_file" class="form-control" accept=".pdf,.docx,.doc,.png,.jpg,.jpeg,.webp">
                    <div class="form-text" id="br_file_info">
                        Format berkas yang didukung: <strong>PDF, Word (DOCX/DOC), atau Foto Poster (PNG, JPG, JPEG, WEBP)</strong>. Maksimal 20MB.
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Link Formulir Pendaftaran Online (Opsional)</label>
                    <input type="url" name="link_pendaftaran" id="br_link" class="form-control" placeholder="https://forms.gle/xxxx atau portal pendaftaran">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi Ringkas Brosur</label>
                    <textarea name="deskripsi" id="br_deskripsi" rows="3" class="form-control" placeholder="Tuliskan keterangan brosur, target pembaca, atau catatan pendaftaran..."></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="br_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="br_is_active">Aktif dan Tampilkan Brosur di Halaman Info Pelatihan Publik</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-cloud-arrow-up me-1"></i> Simpan Brosur</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 3: STANDAR KEUNGGULAN                                                 -->
<!-- ========================================================================= -->
<?php if ($tab === 'keunggulan'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-award-fill text-primary me-2"></i> Standar Keunggulan &amp; Fasilitas Peserta
            </h5>
            <small class="text-muted">Kelola pilar keunggulan, sertifikasi, toolkit, dan fasilitas pendampingan pelatihan LPM UNIKA.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalKeunggulan" onclick="resetFormKeunggulan()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Keunggulan
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;" class="text-center">Urutan</th>
                    <th style="width:60px;" class="text-center">Ikon</th>
                    <th>Judul Keunggulan</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($keunggulan_list)): ?>
                    <?php foreach ($keunggulan_list as $kg): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= (int)$kg['urutan'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary p-2 fs-6 rounded-3">
                                <i class="bi <?= htmlspecialchars($kg['icon'] ?? 'bi-award-fill') ?>"></i>
                            </span>
                        </td>
                        <td class="fw-bold text-navy" style="font-size:0.92rem;">
                            <?= htmlspecialchars($kg['judul']) ?>
                        </td>
                        <td style="max-width:380px;">
                            <small class="text-muted"><?= htmlspecialchars($kg['deskripsi']) ?></small>
                        </td>
                        <td>
                            <?php if ($kg['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editKeunggulan(<?= json_encode($kg, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit Keunggulan">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus keunggulan ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_keunggulan">
                                    <input type="hidden" name="tab" value="keunggulan">
                                    <input type="hidden" name="id" value="<?= $kg['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Keunggulan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data standar keunggulan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Keunggulan -->
<div class="modal fade" id="modalKeunggulan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="save_keunggulan">
            <input type="hidden" name="tab" value="keunggulan">
            <input type="hidden" name="id" id="kg_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalKeunggulanTitle"><i class="bi bi-award me-2"></i> Tambah Standar Keunggulan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Judul Keunggulan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="kg_judul" class="form-control" required placeholder="Contoh: Fasilitator Asesor & Pakar Nasional">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Ikon Bootstrap Icons</label>
                    <input type="text" name="icon" id="kg_icon" class="form-control" value="bi-award-fill" placeholder="bi-award-fill">
                    <small class="text-muted" style="font-size:0.75rem;">Contoh: bi-person-video3, bi-award-fill, bi-folder-check, bi-headset</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi Keunggulan <span class="text-danger">*</span></label>
                    <textarea name="deskripsi" id="kg_deskripsi" rows="3" class="form-control" required placeholder="Jelaskan secara detail keunggulan fasilitas atau benefit peserta..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Urutan Tampil</label>
                    <input type="number" name="urutan" id="kg_urutan" class="form-control" value="1">
                </div>
                <div class="col-md-6 d-flex align-items-center mt-4 pt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="kg_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="kg_is_active">Aktif di Halaman Pelatihan</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Keunggulan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 4: ALUR KERJASAMA                                                     -->
<!-- ========================================================================= -->
<?php if ($tab === 'alur'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-diagram-3-fill text-primary me-2"></i> Alur &amp; Mekanisme Kerjasama Pelatihan
            </h5>
            <small class="text-muted">Kelola 5 tahapan prosedur kemitraan pelatihan penjaminan mutu mulai dari konsultasi hingga sertifikasi.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalAlur" onclick="resetFormAlur()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Tahapan Alur
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:90px;" class="text-center">Langkah Ke</th>
                    <th>Judul Tahapan</th>
                    <th>Deskripsi Mekanisme</th>
                    <th style="width:70px;" class="text-center">Urutan</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($alur_list)): ?>
                    <?php foreach ($alur_list as $al): ?>
                    <tr>
                        <td class="text-center">
                            <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold" style="font-size:0.9rem;">
                                <?= (int)$al['langkah_ke'] ?>
                            </span>
                        </td>
                        <td class="fw-bold text-navy" style="font-size:0.92rem;">
                            <?= htmlspecialchars($al['judul']) ?>
                        </td>
                        <td style="max-width:400px;">
                            <small class="text-muted"><?= htmlspecialchars($al['deskripsi']) ?></small>
                        </td>
                        <td class="text-center fw-bold"><?= (int)$al['urutan'] ?></td>
                        <td>
                            <?php if ($al['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editAlur(<?= json_encode($al, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit Alur">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus tahapan alur ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_alur">
                                    <input type="hidden" name="tab" value="alur">
                                    <input type="hidden" name="id" value="<?= $al['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Alur">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada tahapan alur kerjasama.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Alur -->
<div class="modal fade" id="modalAlur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="save_alur">
            <input type="hidden" name="tab" value="alur">
            <input type="hidden" name="id" id="al_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalAlurTitle"><i class="bi bi-diagram-3 me-2"></i> Tambah Tahapan Alur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Nomor Langkah (Badge Angka) <span class="text-danger">*</span></label>
                    <input type="number" name="langkah_ke" id="al_langkah" class="form-control" value="1" required min="1">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Judul Tahapan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="al_judul" class="form-control" required placeholder="Contoh: Pilih Program / Konsultasi Silabus">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Deskripsi Penjelasan Tahapan <span class="text-danger">*</span></label>
                    <textarea name="deskripsi" id="al_deskripsi" rows="3" class="form-control" required placeholder="Uraikan detail SOP dan hal yang perlu dipersiapkan mitra..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Urutan Tampil</label>
                    <input type="number" name="urutan" id="al_urutan" class="form-control" value="1">
                </div>
                <div class="col-md-6 d-flex align-items-center mt-4 pt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="al_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="al_is_active">Aktif di Halaman Pelatihan</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Tahapan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- TAB 5: FAQ PELATIHAN                                                      -->
<!-- ========================================================================= -->
<?php if ($tab === 'faq'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-question-circle-fill text-primary me-2"></i> Tanya Jawab Seputar Pelatihan (FAQ)
            </h5>
            <small class="text-muted">Kelola daftar pertanyaan dan jawaban seputar sertifikasi akreditasi, in-house training, administrasi SPJ, dan investasi.</small>
        </div>
        <button type="button" class="btn-save" data-bs-toggle="modal" data-bs-target="#modalFaq" onclick="resetFormFaq()">
            <i class="bi bi-plus-lg me-1"></i> Tambah Tanya Jawab FAQ
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle" style="font-size:0.875rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;" class="text-center">Urutan</th>
                    <th>Pertanyaan &amp; Jawaban</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($faq_list)): ?>
                    <?php foreach ($faq_list as $fq): ?>
                    <tr>
                        <td class="text-center fw-bold"><?= (int)$fq['urutan'] ?></td>
                        <td>
                            <div class="fw-bold text-navy" style="font-size:0.95rem;margin-bottom:0.35rem;">
                                <i class="bi bi-question-circle text-primary me-1"></i><?= htmlspecialchars($fq['pertanyaan']) ?>
                            </div>
                            <div class="text-muted" style="font-size:0.84rem;line-height:1.55;background:#f8fafc;padding:0.6rem 0.85rem;border-radius:6px;border-left:3px solid var(--primary);">
                                <?= nl2br(htmlspecialchars(truncate($fq['jawaban'], 220))) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($fq['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editFaq(<?= json_encode($fq, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Edit FAQ">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" onsubmit="return confirm('Hapus pertanyaan FAQ ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_faq">
                                    <input type="hidden" name="tab" value="faq">
                                    <input type="hidden" name="id" value="<?= $fq['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus FAQ">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Belum ada daftar tanya jawab FAQ.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit FAQ -->
<div class="modal fade" id="modalFaq" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="save_faq">
            <input type="hidden" name="tab" value="faq">
            <input type="hidden" name="id" id="faq_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalFaqTitle"><i class="bi bi-question-circle me-2"></i> Tambah Tanya Jawab FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Pertanyaan <span class="text-danger">*</span></label>
                    <input type="text" name="pertanyaan" id="faq_pertanyaan" class="form-control" required placeholder="Contoh: Apakah sertifikat auditor AMI ini diakui untuk penilaian akreditasi?">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Jawaban Lengkap <span class="text-danger">*</span></label>
                    <textarea name="jawaban" id="faq_jawaban" rows="6" class="form-control" required placeholder="Tuliskan jawaban yang komprehensif, jelas, dan akurat..."></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Urutan Tampil</label>
                    <input type="number" name="urutan" id="faq_urutan" class="form-control" value="1">
                </div>
                <div class="col-md-6 d-flex align-items-center mt-4 pt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="faq_is_active" value="1" checked>
                        <label class="form-check-label fw-bold" for="faq_is_active">Aktif di Halaman Pelatihan</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-save me-1"></i> Simpan FAQ</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- SUB-MENU 1: NARASI & KONTAK INFO PELATIHAN                                -->
<!-- ========================================================================= -->
<?php if ($tab === 'pelatihan-umum'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-card-text text-primary me-2"></i> Sub-Menu 1: Narasi &amp; Kontak Info Pelatihan
            </h5>
            <small class="text-muted">Kelola judul header, ikhtisar singkat, narasi utama LPM UNIKA, serta narahubung resmi kemitraan pelatihan.</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Sub-Menu 1 Publik</span>
            <a href="<?= SITE_URL ?>/pelatihan.php" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Publik
            </a>
        </div>
    </div>

    <form method="post" action="layanan-form-setting.php">
        <input type="hidden" name="action" value="save_pelatihan_umum">
        <input type="hidden" name="tab" value="pelatihan-umum">

        <div class="row g-3">
            <div class="col-12">
                <div class="p-3 mb-2 rounded-3" style="background:#F0F9FF;border:1px solid #BAE6FD;">
                    <div class="fw-bold text-navy mb-1"><i class="bi bi-info-circle-fill text-info me-1"></i> Informasi Banner Utama</div>
                    <small class="text-muted">Pengaturan di bawah ini mengatur tampilan Banner Biru bagian teratas pada halaman <code>pelatihan-eksternal.php</code>.</small>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Judul Banner Hero <span class="text-danger">*</span>
                </label>
                <input type="text" name="pelatihan_hero_title" class="form-control" value="<?= e($pelatihan_hero_title) ?>" required placeholder="Info Pelatihan Lembaga Penjaminan Mutu">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Subjudul / Ikhtisar Singkat Banner <span class="text-danger">*</span>
                </label>
                <textarea name="pelatihan_hero_desc" rows="2" class="form-control" required><?= e($pelatihan_hero_desc) ?></textarea>
                <div class="form-text">Teks ikhtisar singkat yang tampil di dalam banner biru hero teratas.</div>
            </div>

            <div class="col-12 mt-3">
                <div class="p-3 mb-2 rounded-3" style="background:#F0FDF4;border:1px solid #BBF7D0;">
                    <div class="fw-bold text-success mb-1"><i class="bi bi-file-earmark-text-fill me-1"></i> Narasi Utama Info Pelatihan LPM UNIKA</div>
                    <small class="text-muted">Narasi resmi di bawah ini akan tampil sebagai konten utama di halaman publik <code>pelatihan.php</code> tepat di bawah banner dan sebelum brosur terpampang.</small>
                </div>
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Narasi Lengkap Info Pelatihan <span class="text-danger">*</span>
                </label>
                <textarea name="pelatihan_narasi_lengkap" rows="10" class="form-control" style="line-height:1.7;font-size:0.92rem;" required placeholder="Tuliskan narasi komprehensif profil pelatihan, kompetensi narasumber asesor BAN-PT/LAM, ruang lingkup pelatihan, dan panduan kemitraan..."><?= e($pelatihan_narasi_lengkap) ?></textarea>
                <div class="form-text">Pisahkan antar paragraf dengan menekan <strong>Enter (baris baru)</strong>. Format paragraf akan otomatis tersusun rapi di halaman publik.</div>
            </div>

            <div class="col-12 mt-4">
                <div class="p-3 mb-2 rounded-3" style="background:#FEF3C7;border:1px solid #FDE68A;">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-envelope-fill text-warning me-1"></i> Kontak Resmi Konsultasi Kemitraan (Narahubung Email)</div>
                    <small class="text-muted">Sesuai ketentuan, kanal konsultasi pelatihan menggunakan jalur resmi email sekretariat LPM.</small>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Email Resmi Konsultasi Pelatihan <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                    <input type="email" name="pelatihan_email_kontak" class="form-control" value="<?= e($pelatihan_email) ?>" required placeholder="lpm@unika.ac.id">
                </div>
                <div class="form-text">Email ini menjadi tujuan tombol "Hubungi via Email" pada halaman pelatihan.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Nomor Telepon Kantor / Hotline
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="text" name="pelatihan_telepon" class="form-control" value="<?= e($pelatihan_telepon) ?>" placeholder="(024) 8441555 Ext. 1473">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Jam Operasional Layanan
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <input type="text" name="pelatihan_jam_layanan" class="form-control" value="<?= e($pelatihan_jam) ?>" placeholder="Senin – Jumat (08:00 – 15:30 WIB)">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Alamat Gedung / Kampus
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                    <input type="text" name="pelatihan_alamat" class="form-control" value="<?= e($pelatihan_alamat) ?>" placeholder="Kampus UNIKA Bendan Dhuwur, Semarang">
                </div>
            </div>

            <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-end">
                <button type="submit" class="btn-save px-4 py-2">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan Banner &amp; Kontak
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- ========================================================================= -->
<!-- SUB-MENU 3: FORMULIR SURVEY KEPUASAN LAYANAN LPM                          -->
<!-- ========================================================================= -->
<?php if ($tab === 'feedback'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-emoji-smile text-primary me-2"></i> Sub-Menu 3: Pengaturan Formulir Survey Kepuasan Layanan
            </h5>
            <small class="text-muted">Konfigurasi badge, judul formulir, deskripsi instruksi, pilihan kategori, serta email penerima survei mutu.</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-success-subtle text-success border border-success-subtle">Sub-Menu 3 Publik</span>
            <a href="<?= SITE_URL ?>/admin/feedback-kunjungan-list.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-bar-chart-fill me-1"></i> Rekap Respon Survei
            </a>
            <a href="<?= SITE_URL ?>/admin/feedback-kunjungan-pertanyaan.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-ui-checks me-1"></i> 8 Butir Kuesioner
            </a>
            <a href="<?= SITE_URL ?>/survei-kepuasan.php" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="bi bi-box-arrow-up-right me-1"></i> Form Publik
            </a>
        </div>
    </div>

    <form method="post" action="layanan-form-setting.php">
        <input type="hidden" name="action" value="save_feedback_setting">
        <input type="hidden" name="tab" value="feedback">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Badge / Label Kecil Form
                </label>
                <input type="text" name="form_feedback_badge" class="form-control" value="<?= e($fb_badge) ?>" placeholder="Contoh: Kanal Aspirasi" required>
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Judul Formulir
                </label>
                <input type="text" name="form_feedback_title" class="form-control" value="<?= e($fb_title) ?>" placeholder="Kritik Saran & Feedback Mutu" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Deskripsi / Subtitle Formulir
                </label>
                <textarea name="form_feedback_desc" rows="3" class="form-control" style="line-height:1.6;"><?= e($fb_desc) ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Pilihan Kategori Masukan (Satu per baris)
                </label>
                <textarea name="form_feedback_categories" rows="6" class="form-control" style="font-family:monospace;font-size:0.85rem;line-height:1.6;"><?= e($fb_categories) ?></textarea>
                <div class="form-text" style="font-size:0.75rem;">Setiap baris akan menjadi pilihan dropdown kategori pada formulir publik.</div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                        Email Penerima Notifikasi Otomatis
                    </label>
                    <input type="email" name="form_feedback_email_recipient" class="form-control" value="<?= e($fb_email) ?>" placeholder="lpm@unika.ac.id" required>
                    <div class="form-text" style="font-size:0.75rem;">Notifikasi email setiap kali ada kritik &amp; saran baru dari pengunjung.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                        Teks Tombol Kirim
                    </label>
                    <input type="text" name="form_feedback_btn_text" class="form-control" value="<?= e($fb_btn) ?>" placeholder="Kirim Masukan" required>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Pesan Notifikasi Sukses
                </label>
                <textarea name="form_feedback_success_msg" rows="3" class="form-control" style="line-height:1.6;" required><?= e($fb_success) ?></textarea>
                <div class="form-text" style="font-size:0.75rem;">Pesan ini akan muncul kepada pengunjung setelah berhasil mengirim masukan.</div>
            </div>
        </div>

        <div class="border-top mt-4 pt-3 text-end">
            <button type="submit" class="btn-save px-4 py-2">
                <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan Formulir Kritik &amp; Saran
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- SUB-MENU 2: FORMULIR KUNJUNGAN STUDI BANDING                              -->
<!-- ========================================================================= -->
<?php if ($tab === 'kunjungan'): ?>
<div class="admin-table-wrap p-4" style="border-top:4px solid var(--navy);">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h5 style="font-weight:700;color:var(--navy);margin:0;">
                <i class="bi bi-building-check text-primary me-2"></i> Sub-Menu 2: Pengaturan Formulir Kunjungan Studi Banding
            </h5>
            <small class="text-muted">Konfigurasi narasi fasilitas studi banding, SOP pengajuan, kuota delegasi, mode jadwal kalender, dan pesan sukses.</small>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Sub-Menu 2 Publik</span>
            <a href="<?= SITE_URL ?>/admin/kunjungan-list.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-inbox-fill me-1"></i> Permohonan Masuk
            </a>
            <a href="<?= SITE_URL ?>/admin/kunjungan-unit.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-diagram-2 me-1"></i> Tujuan Unit
            </a>
            <a href="<?= SITE_URL ?>/kunjungan.php" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="bi bi-box-arrow-up-right me-1"></i> Form Publik
            </a>
        </div>
    </div>

    <form method="post" action="layanan-form-setting.php">
        <input type="hidden" name="action" value="save_kunjungan_setting">
        <input type="hidden" name="tab" value="kunjungan">

        <div class="row g-3">
            <div class="col-12">
                <div class="p-3 mb-2" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;">
                    <div style="font-weight:700;color:var(--navy);font-size:0.92rem;margin-bottom:4px;">
                        <i class="bi bi-info-circle text-primary me-1"></i> Pengaturan Narasi & Prosedur Sebelum Form
                    </div>
                    <div style="font-size:0.8rem;color:var(--text-muted);">
                        Narasi pengantar fasilitas studi banding, penetapan hari jadwal, prosedur pengajuan, dan batas waktu permohonan yang tampil sebelum formulir.
                    </div>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    1. Narasi Fasilitas Studi Banding &amp; Benchmarking
                </label>
                <textarea name="kunjungan_narasi_fasilitas" rows="2" class="form-control" style="line-height:1.6;" required><?= e($kj_narasi_fasilitas) ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    2. Ketentuan Jadwal Tetap (Highlight)
                </label>
                <input type="text" name="kunjungan_narasi_jadwal" class="form-control" value="<?= e($kj_narasi_jadwal) ?>" required>
                <div class="form-text" style="font-size:0.75rem;">Default: Studi Banding dijadwalkan di setiap Jumat Minggu ke-IV.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    3. Mode Pembatasan Pemilihan Tanggal Kalender
                </label>
                <select name="kunjungan_jadwal_mode" class="form-select">
                    <option value="jumat_minggu_4_flexible" <?= $kj_jadwal_mode === 'jumat_minggu_4_flexible' ? 'selected' : '' ?>>
                        (Rekomendasi) Fleksibel dengan Panduan + Batas Min H-<?= $kj_min_lead_days ?>
                    </option>
                    <option value="jumat_minggu_4_strict" <?= $kj_jadwal_mode === 'jumat_minggu_4_strict' ? 'selected' : '' ?>>
                        Ketat: Wajib Hanya Jumat Minggu ke-IV + Batas Min H-<?= $kj_min_lead_days ?>
                    </option>
                    <option value="bebas" <?= $kj_jadwal_mode === 'bebas' ? 'selected' : '' ?>>
                        Bebas Setiap Hari Kerja + Batas Min H-<?= $kj_min_lead_days ?>
                    </option>
                </select>
                <div class="form-text" style="font-size:0.75rem;">Atur apakah pemilihan tanggal dikunci ketat ke Jumat minggu ke-4 atau fleksibel.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    <i class="bi bi-calendar-event me-1 text-primary"></i> Batas Waktu Min Pengajuan (Hari)
                </label>
                <input type="number" name="kunjungan_min_lead_days" class="form-control" value="<?= $kj_min_lead_days ?>" min="1" max="90" required>
                <div class="form-text" style="font-size:0.75rem;">Paling lambat diajukan X hari sebelum kegiatan (default: 14 hari / 2 minggu).</div>
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    4. Catatan Batas Waktu Permohonan
                </label>
                <input type="text" name="kunjungan_narasi_catatan" class="form-control" value="<?= e($kj_narasi_catatan) ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    5. Teks Prosedur Pengajuan Studi Banding
                </label>
                <textarea name="kunjungan_narasi_prosedur" rows="2" class="form-control" style="line-height:1.6;" required><?= e($kj_narasi_prosedur) ?></textarea>
            </div>

            <div class="col-12 mt-4">
                <div class="p-3 mb-2" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;">
                    <div style="font-weight:700;color:var(--navy);font-size:0.92rem;margin-bottom:4px;">
                        <i class="bi bi-card-text text-primary me-1"></i> Tampilan Header &amp; Formulir Kunjungan
                    </div>
                    <div style="font-size:0.8rem;color:var(--text-muted);">
                        Sesuai standar tampilan resmi LPM UNIKA Soegijapranata.
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Badge / Label Kecil Form
                </label>
                <input type="text" name="form_kunjungan_badge" class="form-control" value="<?= e($kj_badge) ?>" placeholder="Contoh: PELAYANAN KUNJUNGAN INSTANSI LUAR" required>
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Judul Formulir
                </label>
                <input type="text" name="form_kunjungan_title" class="form-control" value="<?= e($kj_title) ?>" placeholder="Contoh: Pelayanan Kunjungan ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Deskripsi / Subtitle Formulir
                </label>
                <textarea name="form_kunjungan_desc" rows="2" class="form-control" style="line-height:1.6;"><?= e($kj_desc) ?></textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    <i class="bi bi-clock me-1 text-primary"></i> Jam Operasional Kunjungan
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="time" name="kunjungan_jam_mulai" class="form-control" value="<?= e($kj_jam_mulai) ?>" required>
                    <span class="text-muted">s/d</span>
                    <input type="time" name="kunjungan_jam_selesai" class="form-control" value="<?= e($kj_jam_selesai) ?>" required>
                </div>
                <div class="form-text" style="font-size:0.75rem;">Rentang jam kunjungan yang disarankan ke pemohon (08:00 - 15:00 WIB).</div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    <i class="bi bi-people me-1 text-primary"></i> Batas Maksimal Kuota Peserta di Form
                </label>
                <input type="number" name="kunjungan_max_peserta" class="form-control" value="<?= $kj_max_peserta ?>" min="1" max="100" required>
                <div class="form-text" style="font-size:0.75rem;">Maksimal slot input rincian delegasi (default: 20 orang).</div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Teks Tombol Kirim
                </label>
                <input type="text" name="form_kunjungan_btn_text" class="form-control" value="<?= e($kj_btn) ?>" placeholder="Kirim Permohonan Kunjungan Resmi" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Label Input Unggah Berkas Surat
                </label>
                <input type="text" name="form_kunjungan_file_label" class="form-control" value="<?= e($kj_file_label) ?>" required>
                <div class="form-text" style="font-size:0.75rem;">Petunjuk berkas yang harus dilampirkan pemohon (wajib PDF, maks 10 MB).</div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold" style="color:var(--navy);font-size:0.88rem;">
                    Pesan Notifikasi Sukses Permohonan Kunjungan
                </label>
                <textarea name="form_kunjungan_success_msg" rows="2" class="form-control" style="line-height:1.6;" required><?= e($kj_success) ?></textarea>
                <div class="form-text" style="font-size:0.75rem;">Pesan konfirmasi yang ditampilkan setelah pemohon berhasil mengirim berkas permohonan.</div>
            </div>
        </div>

        <div class="border-top mt-4 pt-3 text-end">
            <button type="submit" class="btn-save px-4 py-2">
                <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan Formulir Kunjungan
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
// JS Helper for Modals
function resetFormPelatihan() {
    document.getElementById('modalPelTitle').innerHTML = '<i class="bi bi-mortarboard me-2"></i> Tambah Program Pelatihan';
    document.getElementById('pel_id').value = '0';
    document.getElementById('pel_nama').value = '';
    document.getElementById('pel_kategori').value = 'Pelatihan Mutu';
    document.getElementById('pel_deskripsi').value = '';
    document.getElementById('pel_sasaran').value = '';
    document.getElementById('pel_durasi').value = '';
    document.getElementById('pel_materi').value = '';
    document.getElementById('pel_metode').value = 'Luring / Hybrid';
    document.getElementById('pel_fasilitas').value = '';
    document.getElementById('pel_urutan').value = '1';
    document.getElementById('pel_is_active').checked = true;
}

function editPelatihan(data) {
    document.getElementById('modalPelTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Program Pelatihan';
    document.getElementById('pel_id').value = data.id || '0';
    document.getElementById('pel_nama').value = data.nama_pelatihan || '';
    document.getElementById('pel_kategori').value = data.kategori || 'Pelatihan Mutu';
    document.getElementById('pel_deskripsi').value = data.deskripsi || '';
    document.getElementById('pel_sasaran').value = data.sasaran_peserta || '';
    document.getElementById('pel_durasi').value = data.durasi || '';
    document.getElementById('pel_materi').value = data.materi_pokok || '';
    document.getElementById('pel_metode').value = data.metode || 'Luring / Hybrid';
    document.getElementById('pel_fasilitas').value = data.fasilitas || '';
    document.getElementById('pel_urutan').value = data.urutan || '1';
    document.getElementById('pel_is_active').checked = (data.is_active == 1);
    
    var myModal = new bootstrap.Modal(document.getElementById('modalPelatihan'));
    myModal.show();
}

function resetFormBiaya() {
    document.getElementById('modalBiayaTitle').innerHTML = '<i class="bi bi-tags me-2"></i> Tambah Paket Biaya';
    document.getElementById('by_id').value = '0';
    document.getElementById('by_nama').value = '';
    document.getElementById('by_nominal').value = '';
    document.getElementById('by_periode').value = 'per peserta';
    document.getElementById('by_deskripsi').value = '';
    document.getElementById('by_fasilitas').value = '';
    document.getElementById('by_urutan').value = '1';
    document.getElementById('by_is_populer').checked = false;
    document.getElementById('by_is_active').checked = true;
}

function editBiaya(data) {
    document.getElementById('modalBiayaTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Paket Biaya';
    document.getElementById('by_id').value = data.id || '0';
    document.getElementById('by_nama').value = data.nama_paket || '';
    document.getElementById('by_nominal').value = data.nominal || '';
    document.getElementById('by_periode').value = data.periode_satuan || 'per peserta';
    document.getElementById('by_deskripsi').value = data.deskripsi_singkat || '';
    document.getElementById('by_fasilitas').value = data.rincian_fasilitas || '';
    document.getElementById('by_urutan').value = data.urutan || '1';
    document.getElementById('by_is_populer').checked = (data.is_populer == 1);
    document.getElementById('by_is_active').checked = (data.is_active == 1);

    var myModal = new bootstrap.Modal(document.getElementById('modalBiaya'));
    myModal.show();
}

function resetFormJadwal() {
    document.getElementById('modalJadwalTitle').innerHTML = '<i class="bi bi-calendar-check me-2"></i> Tambah Agenda Kegiatan';
    document.getElementById('jd_id').value = '0';
    document.getElementById('jd_nama').value = '';
    document.getElementById('jd_mulai').value = '';
    document.getElementById('jd_selesai').value = '';
    document.getElementById('jd_lokasi').value = 'Kampus UNIKA Soegijapranata, Semarang';
    document.getElementById('jd_status').value = 'Terlaksana';
    document.getElementById('jd_peserta').value = '';
    document.getElementById('jd_jumlah').value = '0';
    document.getElementById('jd_keterangan').value = '';
}

function editJadwal(data) {
    document.getElementById('modalJadwalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Agenda Kegiatan';
    document.getElementById('jd_id').value = data.id || '0';
    document.getElementById('jd_nama').value = data.nama_kegiatan || '';
    document.getElementById('jd_mulai').value = data.tanggal_mulai || '';
    document.getElementById('jd_selesai').value = data.tanggal_selesai || '';
    document.getElementById('jd_lokasi').value = data.lokasi || '';
    document.getElementById('jd_status').value = data.status || 'Terlaksana';
    document.getElementById('jd_peserta').value = data.institusi_peserta || '';
    document.getElementById('jd_jumlah').value = data.jumlah_peserta || '0';
    document.getElementById('jd_keterangan').value = data.keterangan || '';

    var myModal = new bootstrap.Modal(document.getElementById('modalJadwal'));
    myModal.show();
}

function resetFormBrosur() {
    document.getElementById('modalBrosurTitle').innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-2"></i> Upload Brosur Pelatihan';
    document.getElementById('br_id').value = '0';
    document.getElementById('br_judul').value = '';
    document.getElementById('br_tahun').value = '2026';
    document.getElementById('br_file').value = '';
    document.getElementById('br_link').value = '';
    document.getElementById('br_deskripsi').value = '';
    document.getElementById('br_file_info').innerHTML = 'Format berkas yang didukung: <strong>PDF, Word (DOCX/DOC), atau Foto Poster (PNG, JPG, JPEG, WEBP)</strong>. Maksimal 20MB.';
    document.getElementById('br_is_active').checked = true;
}

function editBrosur(data) {
    document.getElementById('modalBrosurTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Brosur Pelatihan';
    document.getElementById('br_id').value = data.id || '0';
    document.getElementById('br_judul').value = data.judul_brosur || '';
    document.getElementById('br_tahun').value = data.tahun || '2026';
    document.getElementById('br_file').value = '';
    document.getElementById('br_link').value = data.link_pendaftaran || '';
    document.getElementById('br_deskripsi').value = data.deskripsi || '';
    
    var fType = (data.tipe_file || 'PDF').toUpperCase();
    var fSize = data.ukuran_file ? (' &bull; ' + data.ukuran_file) : '';
    document.getElementById('br_file_info').innerHTML = 'Berkas saat ini: <code>' + (data.file_brosur || '-') + '</code> (' + fType + fSize + ')<br><small class="text-muted">Biarkan kosong jika tidak ingin mengganti file.</small>';
    document.getElementById('br_is_active').checked = (data.is_active == 1);

    var myModal = new bootstrap.Modal(document.getElementById('modalBrosur'));
    myModal.show();
}

function resetFormKeunggulan() {
    document.getElementById('modalKeunggulanTitle').innerHTML = '<i class="bi bi-award me-2"></i> Tambah Standar Keunggulan';
    document.getElementById('kg_id').value = '0';
    document.getElementById('kg_judul').value = '';
    document.getElementById('kg_icon').value = 'bi-award-fill';
    document.getElementById('kg_deskripsi').value = '';
    document.getElementById('kg_urutan').value = '1';
    document.getElementById('kg_is_active').checked = true;
}

function editKeunggulan(data) {
    document.getElementById('modalKeunggulanTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Standar Keunggulan';
    document.getElementById('kg_id').value = data.id || '0';
    document.getElementById('kg_judul').value = data.judul || '';
    document.getElementById('kg_icon').value = data.icon || 'bi-award-fill';
    document.getElementById('kg_deskripsi').value = data.deskripsi || '';
    document.getElementById('kg_urutan').value = data.urutan || '1';
    document.getElementById('kg_is_active').checked = (data.is_active == 1);

    var myModal = new bootstrap.Modal(document.getElementById('modalKeunggulan'));
    myModal.show();
}

function resetFormAlur() {
    document.getElementById('modalAlurTitle').innerHTML = '<i class="bi bi-diagram-3 me-2"></i> Tambah Tahapan Alur';
    document.getElementById('al_id').value = '0';
    document.getElementById('al_langkah').value = '1';
    document.getElementById('al_judul').value = '';
    document.getElementById('al_deskripsi').value = '';
    document.getElementById('al_urutan').value = '1';
    document.getElementById('al_is_active').checked = true;
}

function editAlur(data) {
    document.getElementById('modalAlurTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Tahapan Alur';
    document.getElementById('al_id').value = data.id || '0';
    document.getElementById('al_langkah').value = data.langkah_ke || '1';
    document.getElementById('al_judul').value = data.judul || '';
    document.getElementById('al_deskripsi').value = data.deskripsi || '';
    document.getElementById('al_urutan').value = data.urutan || '1';
    document.getElementById('al_is_active').checked = (data.is_active == 1);

    var myModal = new bootstrap.Modal(document.getElementById('modalAlur'));
    myModal.show();
}

function resetFormFaq() {
    document.getElementById('modalFaqTitle').innerHTML = '<i class="bi bi-question-circle me-2"></i> Tambah Tanya Jawab FAQ';
    document.getElementById('faq_id').value = '0';
    document.getElementById('faq_pertanyaan').value = '';
    document.getElementById('faq_jawaban').value = '';
    document.getElementById('faq_urutan').value = '1';
    document.getElementById('faq_is_active').checked = true;
}

function editFaq(data) {
    document.getElementById('modalFaqTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Tanya Jawab FAQ';
    document.getElementById('faq_id').value = data.id || '0';
    document.getElementById('faq_pertanyaan').value = data.pertanyaan || '';
    document.getElementById('faq_jawaban').value = data.jawaban || '';
    document.getElementById('faq_urutan').value = data.urutan || '1';
    document.getElementById('faq_is_active').checked = (data.is_active == 1);

    var myModal = new bootstrap.Modal(document.getElementById('modalFaq'));
    myModal.show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
