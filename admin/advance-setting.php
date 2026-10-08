<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$page_id = (int)($_GET['id'] ?? 0);
$current_page_data = null;

// Ambil semua daftar halaman untuk dropdown selector
$all_pages = $db->query("SELECT id, judul, slug, kategori, status, show_in_nav FROM pages ORDER BY urutan ASC, judul ASC")->fetchAll();

if ($page_id > 0) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$page_id]);
    $current_page_data = $stmt->fetch();
}

// Jika tidak ada ID atau ID tidak ditemukan, buat data template awal halaman baru atau ambil halaman pertama
if (!$current_page_data) {
    if (!empty($all_pages) && !isset($_GET['new'])) {
        $current_page_data = $all_pages[0];
        $page_id = (int)$current_page_data['id'];
        $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$page_id]);
        $current_page_data = $stmt->fetch();
    } else {
        $current_page_data = [
            'id'          => 0,
            'judul'       => 'Halaman Baru',
            'slug'        => 'halaman-baru',
            'kategori'    => 'Umum',
            'status'      => 'publish',
            'show_in_nav' => 1,
            'nav_position'=> 'main',
            'nav_label'   => 'Halaman Baru',
            'urutan'      => 0,
            'blocks_json' => ''
        ];
    }
}

// Decode blocks atau inisialisasi default blocks
$initial_blocks = [];
if (!empty($current_page_data['blocks_json'])) {
    $initial_blocks = json_decode($current_page_data['blocks_json'], true) ?: [];
}

// Jika belum memiliki blok sama sekali (halaman lama atau baru), sediakan template blok awal sesuai halaman
if (empty($initial_blocks)) {
    $cur_slug = $current_page_data['slug'] ?? '';
    $cur_url  = $current_page_data['custom_url'] ?? '';

    if ($cur_slug === 'beranda' || $cur_url === 'index.php') {
        $initial_blocks = [
            ['id' => 'block_beranda_hero', 'type' => 'beranda_hero', 'title' => 'Hero Slider Utama Beranda', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_quick_links', 'type' => 'beranda_quick_links', 'title' => 'Tautan Cepat (Quick Links)', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_stats', 'type' => 'beranda_stats', 'title' => 'Statistik Capaian (Stats Bar)', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_penghargaan', 'type' => 'beranda_penghargaan', 'title' => 'Penghargaan & Rekognisi', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_akreditasi', 'type' => 'beranda_akreditasi', 'badge' => 'Akreditasi Institusi', 'title' => "Capaian Mutu\nUniversitas Katolik Soegijapranata", 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_berita', 'type' => 'beranda_berita', 'title' => 'Berita & Pengumuman', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_cta', 'type' => 'beranda_cta', 'title' => 'Butuh Informasi Lebih Lanjut?', 'subtitle' => 'Hubungi sekretariat Lembaga Penjaminan Mutu untuk konsultasi akreditasi dan layanan penjaminan mutu internal.', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'profil' || $cur_url === 'profil.php') {
        $initial_blocks = [
            ['id' => 'block_profil_tentang', 'type' => 'profil_tentang', 'badge' => 'Tentang Kami', 'title' => 'Lembaga Penjaminan Mutu UNIKA', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_profil_visi_misi', 'type' => 'profil_visi_misi', 'badge' => 'Arah & Komitmen', 'title' => 'Visi, Misi & Tujuan LPM UNIKA', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_profil_tugas_fungsi', 'type' => 'profil_tugas_fungsi', 'badge' => 'Landasan Operasional', 'title' => 'Tugas dan Fungsi', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_profil_struktur', 'type' => 'profil_struktur', 'badge' => 'Struktur & Tim', 'title' => 'Struktur Organisasi LPM', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'spmi' || $cur_url === 'spmi.php') {
        $initial_blocks = [
            ['id' => 'block_spmi_pengantar', 'type' => 'spmi_pengantar', 'badge' => 'Pengantar Mutu', 'title' => 'Mengenal SPMI di UNIKA', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_spmi_ppepp', 'type' => 'spmi_ppepp', 'badge' => 'Alur Kerja Mutu', 'title' => 'Siklus PPEPP SPMI', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_spmi_kemendikti', 'type' => 'spmi_kemendikti', 'badge' => 'Publikasi & Pelaporan', 'title' => 'Hasil SPMI Kemendikti Saintek', 'bg_color' => '#f1f5f9', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_spmi_dokumen', 'type' => 'spmi_dokumen', 'badge' => 'Berkas Resmi', 'title' => 'Dokumen Mutu SPMI', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'ami' || $cur_url === 'ami.php') {
        $initial_blocks = [
            ['id' => 'block_ami_pengantar', 'type' => 'ami_pengantar', 'badge' => 'Evaluasi Mutu', 'title' => 'Tentang Audit Mutu Internal (AMI)', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_ami_alur', 'type' => 'ami_alur', 'badge' => 'Prosedur Kerja', 'title' => 'Alur & Tahapan Siklus AMI', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_ami_jadwal', 'type' => 'ami_jadwal', 'badge' => 'Jadwal Pelaksanaan Resmi', 'title' => 'Jadwal Pelaksanaan Audit Mutu Internal (AMI)', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_ami_instrumen_auditor', 'type' => 'ami_instrumen_auditor', 'badge' => 'Pedoman & Instrumen', 'title' => 'Pedoman, Instrumen & Auditor Mutu', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'akreditasi' || $cur_url === 'akreditasi.php') {
        $initial_blocks = [
            ['id' => 'block_akreditasi_institusi', 'type' => 'akreditasi_institusi', 'badge' => 'Akreditasi Perguruan Tinggi', 'title' => 'Akreditasi Institusi UNIKA Soegijapranata', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_statistik', 'type' => 'akreditasi_statistik', 'badge' => 'Data Statistik', 'title' => 'Rekapitulasi Status Akreditasi Program Studi', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_lam', 'type' => 'akreditasi_lam', 'badge' => 'Lembaga Mitra', 'title' => 'Lembaga Akreditasi Mandiri & Mitra', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_prodi', 'type' => 'akreditasi_prodi', 'badge' => 'Data Resmi', 'title' => 'Daftar Status Akreditasi Program Studi', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_dokumen', 'type' => 'akreditasi_dokumen', 'badge' => 'Berkas Resmi', 'title' => 'Unduh Dokumen & Sertifikat Akreditasi Institusi', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_faq', 'type' => 'akreditasi_faq', 'badge' => 'Informasi Penting', 'title' => 'Tanya Jawab (FAQ) Akreditasi', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'mutu-data' || $cur_url === 'mutu-data.php') {
        $initial_blocks = [
            ['id' => 'block_mutu_data_dashboard', 'type' => 'mutu_data_dashboard', 'badge' => 'Dashboard & Capaian', 'title' => 'Statistik Mutu Pendidikan & Akreditasi', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_mutu_data_iku', 'type' => 'mutu_data_iku', 'badge' => 'Indikator Utama', 'title' => 'Indikator Kinerja Utama (IKU) Universitas', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'dokumen' || $cur_url === 'dokumen.php') {
        $initial_blocks = [
            ['id' => 'block_dokumen_header', 'type' => 'dokumen_header', 'badge' => 'Database Dokumen Terpadu', 'title' => 'Pusat Database Dokumen & Arsip', 'bg_color' => '#0A192F', 'text_color' => '#ffffff', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_dokumen_table', 'type' => 'dokumen_table', 'badge' => 'Repositori Mutu', 'title' => 'Tabel Dokumen & Pencarian Pintar', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'knowledge' || $cur_url === 'knowledge.php') {
        $initial_blocks = [
            ['id' => 'block_knowledge_pengantar', 'type' => 'knowledge_pengantar', 'badge' => 'Knowledge Management System', 'title' => 'Knowledge Center & Sumber Belajar Mutu', 'bg_color' => '#0A192F', 'text_color' => '#ffffff', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_kalender', 'type' => 'knowledge_kalender', 'badge' => 'Jadwal & Agenda', 'title' => 'Kalender Kegiatan SPMI & AMI', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_buletin', 'type' => 'knowledge_buletin', 'badge' => 'Publikasi Resmi', 'title' => 'Koleksi Buletin Mutu (JAMUS)', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_glosarium', 'type' => 'knowledge_glosarium', 'badge' => 'Kamus Terminologi', 'title' => 'Glosarium Penjaminan Mutu', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_faq', 'type' => 'knowledge_faq', 'badge' => 'Pusat Bantuan', 'title' => 'Pertanyaan Sering Diajukan (FAQ)', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'berita' || $cur_url === 'berita.php') {
        $initial_blocks = [
            ['id' => 'block_berita_highlight', 'type' => 'berita_highlight', 'badge' => 'Berita Terkini & Kegiatan', 'title' => 'Publikasi Kegiatan & Berita Mutu', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_berita_grid', 'type' => 'berita_grid', 'badge' => 'Arsip Berita', 'title' => 'Daftar Artikel & Agenda Lengkap', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'layanan' || $cur_url === 'layanan.php') {
        $initial_blocks = [
            ['id' => 'block_layanan_cards', 'type' => 'layanan_cards', 'badge' => 'Layanan Mutu', 'title' => 'Katalog Layanan & Konsultasi SPMI', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_layanan_form', 'type' => 'layanan_form', 'badge' => 'Permohonan Digital', 'title' => 'Formulir Pengajuan Permohonan Layanan SPMI', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($cur_slug === 'kontak' || $cur_url === 'kontak.php') {
        $initial_blocks = [
            ['id' => 'block_kontak_info', 'type' => 'kontak_info', 'badge' => 'Sekretariat Lembaga', 'title' => 'Informasi Kontak & Lokasi Kantor LPM', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_kontak_form', 'type' => 'kontak_form', 'badge' => 'Kritik & Saran', 'title' => 'Sampaikan Masukan & Aspirasi Mutu', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } else {
        $initial_blocks = [
            [
                'id'         => 'block_' . time() . '_1',
                'type'       => 'hero',
                'badge'      => $current_page_data['kategori'] ?? 'LPM UNIKA',
                'title'      => $current_page_data['judul'] ?? 'Mewujudkan Budaya Mutu Berkelanjutan',
                'subtitle'   => 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berkomitmen mengawal standar mutu secara konsisten.',
                'btn_text'   => 'Pelajari Selengkapnya',
                'btn_link'   => '#konten',
                'bg_color'   => '#0A192F',
                'text_color' => '#FFFFFF',
                'align'      => 'center',
                'padding'    => 'large'
            ],
            [
                'id'         => 'block_' . time() . '_2',
                'type'       => 'text_image',
                'tag'        => 'Penjaminan Mutu',
                'title'      => 'Komitmen Mutu & Keunggulan',
                'content'    => '<p>Sistem Penjaminan Mutu dilaksanakan secara sistemik dan berkelanjutan untuk memastikan ketercapaian visi misi institusi dan kepuasan pemangku kepentingan.</p>',
                'image'      => '',
                'image_pos'  => 'right',
                'btn_text'   => 'Lihat Dokumen',
                'btn_link'   => 'dokumen.php',
                'bg_color'   => '#FFFFFF',
                'text_color' => '#0A192F',
                'padding'    => 'normal'
            ],
            [
                'id'         => 'block_' . time() . '_3',
                'type'       => 'cards_grid',
                'tag'        => 'Layanan Mutu',
                'title'      => 'Pilar Tata Kelola Penjaminan Mutu',
                'subtitle'   => 'Fokus utama pelaksanaan penjaminan mutu di lingkungan universitas.',
                'columns'    => 3,
                'bg_color'   => '#F8F9FA',
                'text_color' => '#0A192F',
                'padding'    => 'normal',
                'cards'      => [
                    ['title' => 'Siklus PPEPP', 'desc' => 'Penetapan, pelaksanaan, evaluasi, pengendalian, dan peningkatan standar mutu secara berkelanjutan.', 'icon' => 'bi-shield-check', 'badge' => 'Standar Mutu'],
                    ['title' => 'Audit Mutu Internal', 'desc' => 'Evaluasi independen berkala oleh auditor mutu internal bersertifikasi.', 'icon' => 'bi-search', 'badge' => 'Evaluasi AMI'],
                    ['title' => 'Akreditasi Unggul', 'desc' => 'Rekognisi mutu kelembagaan dan program studi dari lembaga akreditasi nasional dan internasional.', 'icon' => 'bi-award', 'badge' => 'Akreditasi']
                ]
            ],
            [
                'id'         => 'block_' . time() . '_4',
                'type'       => 'cta',
                'title'      => 'Konsultasi & Permohonan Layanan SPMI',
                'subtitle'   => 'Hubungi tim Lembaga Penjaminan Mutu untuk permohonan kunjungan atau koordinasi mutu.',
                'btn_text'   => 'Ajukan Permohonan',
                'btn_link'   => 'layanan.php',
                'bg_color'   => '#6A1B9A',
                'text_color' => '#FFFFFF',
                'padding'    => 'normal'
            ]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advance Visual Builder - <?= htmlspecialchars($current_page_data['judul']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --builder-topbar-bg: #0A192F;
            --builder-sidebar-bg: #FFFFFF;
            --builder-border: #E2E8F0;
            --builder-primary: #1E3A8A;
            --builder-primary-hover: #172554;
            --builder-accent: #F59E0B;
            --builder-navy: #0A192F;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #061224;
            overflow: hidden;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Bar */
        .builder-topbar {
            height: 56px;
            background: var(--builder-topbar-bg);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            color: #ffffff;
            z-index: 1000;
            flex-shrink: 0;
        }

        /* Workspace Main Body */
        .builder-workspace {
            display: flex;
            flex: 1;
            height: calc(100vh - 56px);
            overflow: hidden;
        }

        /* Left Control Panel (Elementor Sidebar) */
        .builder-sidebar {
            width: 380px;
            background: var(--builder-sidebar-bg);
            border-right: 1px solid var(--builder-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 900;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        /* Sidebar Tabs */
        .builder-tabs {
            display: flex;
            background: #F8FAFC;
            border-bottom: 1px solid var(--builder-border);
        }
        .builder-tab-btn {
            flex: 1;
            padding: 0.75rem 0.5rem;
            border: none;
            background: transparent;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748B;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: center;
        }
        .builder-tab-btn:hover {
            color: #0F172A;
        }
        .builder-tab-btn.active {
            color: var(--builder-primary);
            border-bottom-color: var(--builder-primary);
            background: #ffffff;
        }

        .builder-tab-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem;
        }

        /* Right Canvas Area */
        .builder-canvas-wrapper {
            flex: 1;
            background: #1E293B;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 1.5rem;
            overflow: hidden;
        }
        .builder-iframe-frame {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .builder-iframe-frame iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* Section Item Card in Left List */
        .section-tree-item {
            background: #FFFFFF;
            border: 1.5px solid var(--builder-border);
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }
        .section-tree-item:hover {
            border-color: #CBD5E1;
            background: #F8FAFC;
        }
        .section-tree-item.active {
            border-color: var(--builder-primary);
            background: #EFF6FF;
        }
        /* Tampilan Seksi Berstatus Draf */
        .section-tree-item.is-draft {
            background: #FFFDF5;
            border-color: #FCD34D;
            border-left: 3.5px solid #F59E0B;
        }
        .section-tree-item.is-draft:hover {
            background: #FEF9C3;
            border-color: #FBBF24;
        }
        .section-tree-item.is-draft.active {
            border-color: #F59E0B;
            background: #FEF3C7;
        }
        /* Hold and Drag styling */
        .section-tree-item.dragging {
            opacity: 0.45;
            border: 2px dashed var(--builder-primary) !important;
            background: #EFF6FF !important;
            cursor: grabbing !important;
        }
        .section-tree-item.drag-over {
            border-top: 3px solid var(--builder-primary) !important;
            transform: translateY(2px);
            box-shadow: 0 -4px 10px rgba(30, 58, 138, 0.15);
        }
        .drag-handle {
            cursor: grab;
            user-select: none;
            color: #94A3B8;
            font-size: 1.15rem;
            line-height: 1;
            padding: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s;
        }
        .drag-handle:hover {
            color: var(--builder-primary);
        }
        .drag-handle:active {
            cursor: grabbing;
        }

        /* Device Buttons in Topbar */
        .device-btn {
            background: transparent;
            border: none;
            color: #94A3B8;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.15s;
        }
        .device-btn:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.1);
        }
        .device-btn.active {
            color: #ffffff;
            background: var(--builder-primary);
        }

        /* Form Labels & Controls */
        .builder-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 0.35rem;
            display: block;
        }
        .builder-input {
            width: 100%;
            padding: 0.55rem 0.75rem;
            font-size: 0.85rem;
            border: 1.5px solid var(--builder-border);
            border-radius: 6px;
            color: #0F172A;
            transition: border-color 0.15s;
        }
        .builder-input:focus {
            outline: none;
            border-color: var(--builder-primary);
        }

        /* Toast notification */
        .builder-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0F172A;
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 9999;
            display: none;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>

<!-- 1. Top Header Bar -->
<header class="builder-topbar">
    <div class="d-flex align-items-center gap-3">
        <a href="page-list.php" class="btn btn-sm btn-dark text-white-50 d-inline-flex align-items-center gap-1 border-secondary" style="border-radius:6px;font-size:0.8rem;">
            <i class="bi bi-chevron-left"></i> Dashboard
        </a>
        <div class="vr bg-secondary" style="height:20px;"></div>
        
        <!-- Page Selector Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-dark dropdown-toggle d-flex align-items-center gap-2 border-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:6px;font-weight:700;font-size:0.85rem;">
                <i class="bi bi-file-earmark-text text-primary"></i>
                <span id="topbarPageTitle"><?= htmlspecialchars($current_page_data['judul']) ?></span>
                <span id="topbarPageStatusBadge" class="badge <?= ($current_page_data['status'] ?? 'publish') === 'draft' ? 'bg-warning text-dark' : 'bg-success' ?>" style="font-size:0.68rem;padding:0.25rem 0.5rem;">
                    <?= ($current_page_data['status'] ?? 'publish') === 'draft' ? 'Draf' : 'Live' ?>
                </span>
            </button>
            <ul class="dropdown-menu shadow-lg" style="min-width:260px;max-height:350px;overflow-y:auto;font-size:0.85rem;">
                <li><h6 class="dropdown-header text-uppercase" style="font-size:0.7rem;letter-spacing:0.5px;">Pilih Halaman Yang Diedit</h6></li>
                <?php foreach ($all_pages as $ap): ?>
                <li>
                    <a class="dropdown-item py-2 d-flex align-items-center justify-content-between <?= $ap['id'] == $current_page_data['id'] ? 'active font-weight-bold' : '' ?>" href="advance-setting.php?id=<?= $ap['id'] ?>">
                        <span><?= htmlspecialchars($ap['judul']) ?></span>
                        <span class="badge <?= $ap['status'] === 'publish' ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size:0.65rem;"><?= $ap['status'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2 text-primary fw-bold" href="advance-setting.php?new=1">
                        <i class="bi bi-plus-circle me-1"></i> Buat Halaman Baru
                    </a>
                </li>
            </ul>
        </div>

        <a href="advance-setting.php?new=1" class="btn btn-sm btn-outline-light d-none d-md-inline-flex align-items-center gap-1" style="border-radius:6px;font-size:0.8rem;">
            <i class="bi bi-plus"></i> Halaman Baru
        </a>
    </div>

    <!-- Center: Device Switcher -->
    <div class="d-none d-md-flex align-items-center gap-1 bg-dark p-1 rounded-3 border border-secondary">
        <button type="button" class="device-btn active" data-width="100%" title="Tampilan Komputer (Desktop)">
            <i class="bi bi-display"></i>
        </button>
        <button type="button" class="device-btn" data-width="768px" title="Tampilan Tablet">
            <i class="bi bi-tablet"></i>
        </button>
        <button type="button" class="device-btn" data-width="375px" title="Tampilan HP (Mobile)">
            <i class="bi bi-phone"></i>
        </button>
    </div>

    <!-- Right: Save & Live Preview Buttons -->
    <div class="d-flex align-items-center gap-2">
        <?php
        $topbar_live_url = !empty($current_page_data['custom_url'])
            ? (SITE_URL . '/' . ($current_page_data['custom_url'] === 'index.php' ? '' : $current_page_data['custom_url']))
            : (SITE_URL . '/page.php?slug=' . urlencode($current_page_data['slug']));
        ?>
        <a id="livePageBtn" href="<?= $topbar_live_url ?>" target="_blank" class="btn btn-sm btn-dark text-white-50 border-secondary" style="border-radius:6px;font-size:0.8rem;">
            <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Live
        </a>
        <button type="button" id="savePublishBtn" class="btn btn-sm btn-primary px-3 d-inline-flex align-items-center gap-1" style="background:var(--builder-primary);border:none;font-weight:700;border-radius:6px;font-size:0.85rem;">
            <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            <i class="bi bi-check2-circle" id="saveBtnIcon"></i>
            <span id="saveBtnText"><?= ($current_page_data['status'] ?? 'publish') === 'draft' ? 'Simpan sebagai Draf' : 'Simpan &amp; Publikasikan' ?></span>
        </button>
    </div>
</header>

<!-- 2. Workspace Body -->
<main class="builder-workspace">
    <!-- Left Sidebar: Elementor Inspector -->
    <aside class="builder-sidebar">
        <!-- Tabs Nav -->
        <nav class="builder-tabs">
            <button type="button" class="builder-tab-btn active" data-tab="tab-sections">
                <i class="bi bi-layers me-1"></i> Susunan Seksi
            </button>
            <button type="button" class="builder-tab-btn" data-tab="tab-editor">
                <i class="bi bi-palette me-1"></i> Gaya &amp; Konten
            </button>
            <button type="button" class="builder-tab-btn" data-tab="tab-settings">
                <i class="bi bi-gear me-1"></i> Pengaturan
            </button>
        </nav>

        <!-- Tab 1: Susunan Seksi (Tree & Add) -->
        <div id="tab-sections" class="builder-tab-content">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span style="font-size:0.75rem;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.5px;">Daftar Seksi Halaman</span>
                <span id="sectionCountBadge" class="badge bg-light text-dark border">4 Seksi</span>
            </div>

            <div id="sectionTreeList">
                <!-- Dynamically populated -->
            </div>

            <!-- Tombol Tambah Seksi -->
            <div class="mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#addBlockModal" style="border-radius:8px;font-weight:700;font-size:0.85rem;">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Seksi / Blok Baru
                </button>
            </div>
        </div>

        <!-- Tab 2: Gaya & Konten Seksi Terpilih -->
        <div id="tab-editor" class="builder-tab-content" style="display:none;">
            <div id="editorEmptyState" class="text-center py-4 text-muted" style="font-size:0.85rem;">
                <i class="bi bi-cursor text-secondary" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                Pilih salah satu seksi di kanvas atau di tab <strong>Susunan Seksi</strong> untuk mengedit konten dan warnanya.
            </div>

            <div id="editorFormContainer" style="display:none;">
                <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                    <div>
                        <span id="activeBlockTypeBadge" class="badge bg-primary" style="font-size:0.7rem;">Hero Banner</span>
                        <div id="activeBlockIndexLabel" style="font-size:0.8rem;font-weight:700;color:#1E293B;margin-top:2px;">Seksi #1</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchTab('tab-sections')" title="Kembali ke susunan seksi">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                </div>

                <!-- Kontrol Status Penerbitan Seksi -->
                <div class="mb-3 p-3 rounded-3" style="background:#F8FAFC;border:1.5px solid #E2E8F0;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:0.75rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.5px;">
                            <i class="bi bi-toggle2-on me-1 text-primary"></i> Status Seksi Ini
                        </span>
                        <span id="activeBlockStatusBadge" class="badge bg-success" style="font-size:0.7rem;">Live</span>
                    </div>
                    <select id="activeBlockStatusSelect" class="builder-input" onchange="updateActiveBlockStatus(this.value)">
                        <option value="publish">Publikasikan (Tampil di Website)</option>
                        <option value="draft">Draf (Sembunyikan dari Publik)</option>
                    </select>
                    <small id="activeBlockStatusHint" class="text-muted d-block mt-2" style="font-size:0.73rem;line-height:1.4;">
                        Seksi ini sedang <strong>Diterbitkan</strong> dan dapat dilihat oleh pengunjung umum website.
                    </small>
                </div>

                <!-- Kontrol Tampilan & Warna -->
                <div class="mb-4 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <span style="font-size:0.75rem;font-weight:800;color:#475569;text-transform:uppercase;display:block;margin-bottom:0.75rem;">
                        Warna &amp; Tampilan Seksi
                    </span>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="builder-label">Latar Belakang</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="color" id="blockBgColorPicker" style="width:36px;height:34px;border:none;border-radius:6px;cursor:pointer;">
                                <input type="text" id="blockBgColorText" class="builder-input text-uppercase" style="font-family:monospace;font-size:0.75rem;padding:0.4rem;" placeholder="#0A192F">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="builder-label">Warna Teks</label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="color" id="blockTextColorPicker" style="width:36px;height:34px;border:none;border-radius:6px;cursor:pointer;">
                                <input type="text" id="blockTextColorText" class="builder-input text-uppercase" style="font-family:monospace;font-size:0.75rem;padding:0.4rem;" placeholder="#FFFFFF">
                            </div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="builder-label">Jarak Padding</label>
                            <select id="blockPaddingSelect" class="builder-input">
                                <option value="small">Kecil</option>
                                <option value="normal" selected>Sedang (Normal)</option>
                                <option value="large">Besar (Longgar)</option>
                            </select>
                        </div>
                        <div class="col-6" id="alignControlGroup">
                            <label class="builder-label">Perataan Teks</label>
                            <select id="blockAlignSelect" class="builder-input">
                                <option value="left">Rata Kiri</option>
                                <option value="center" selected>Rata Tengah</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Kontrol Isi Konten Teks & Media -->
                <div id="blockFieldsContainer">
                    <!-- Injected dynamically based on block type -->
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="deleteActiveBlock()">
                        <i class="bi bi-trash"></i> Hapus Seksi Ini
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="duplicateActiveBlock()">
                        <i class="bi bi-copy"></i> Duplikat
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab 3: Pengaturan Halaman & Navigasi -->
        <div id="tab-settings" class="builder-tab-content" style="display:none;">
            <div class="mb-3">
                <label class="builder-label">Judul Halaman <span class="text-danger">*</span></label>
                <input type="text" id="pageTitleInput" class="builder-input" value="<?= htmlspecialchars($current_page_data['judul']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="builder-label">Slug URL</label>
                <input type="text" id="pageSlugInput" class="builder-input" value="<?= htmlspecialchars($current_page_data['slug']) ?>">
                <small class="text-muted" style="font-size:0.75rem;">URL web: /page.php?slug=<strong><?= htmlspecialchars($current_page_data['slug']) ?></strong></small>
            </div>

            <div class="mb-3">
                <label class="builder-label">Kategori Halaman</label>
                <select id="pageKategoriInput" class="builder-input">
                    <?php
                    $kats = ['Profil', 'SPMI', 'AMI', 'Akreditasi', 'Mutu & Data', 'Dokumen', 'Knowledge Center', 'Layanan', 'Umum'];
                    foreach ($kats as $k):
                    ?>
                    <option value="<?= $k ?>" <?= ($current_page_data['kategori'] ?? 'Umum') === $k ? 'selected' : '' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="builder-label">Status Penerbitan</label>
                <select id="pageStatusInput" class="builder-input">
                    <option value="publish" <?= ($current_page_data['status'] ?? 'publish') === 'publish' ? 'selected' : '' ?>>Publikasikan (Live)</option>
                    <option value="draft" <?= ($current_page_data['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Simpan Sebagai Draf</option>
                </select>
            </div>

            <!-- Pengaturan Menu Navbar -->
            <div class="p-3 rounded-3 mb-3" style="background:#F8FAFC;border:1.5px solid var(--builder-border);">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="showInNavCheck" <?= !empty($current_page_data['show_in_nav']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="showInNavCheck" style="font-size:0.85rem;color:var(--builder-navy);cursor:pointer;">
                        Tampilkan di Menu Navbar
                    </label>
                </div>
                <small class="text-muted d-block mb-3" style="font-size:0.75rem;">
                    Bila diaktifkan, halaman ini akan otomatis muncul pada bilah navigasi utama website.
                </small>

                <div id="navOptionsBox" style="<?= empty($current_page_data['show_in_nav']) ? 'display:none;' : '' ?>">
                    <div class="mb-2">
                        <label class="builder-label">Posisi di Navbar</label>
                        <select id="navPositionSelect" class="builder-input">
                            <option value="main" <?= ($current_page_data['nav_position'] ?? 'main') === 'main' ? 'selected' : '' ?>>Menu Utama (Sejajar di Atas)</option>
                            <option value="dropdown" <?= ($current_page_data['nav_position'] ?? '') === 'dropdown' ? 'selected' : '' ?>>Menu Dropdown (Lainnya)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="builder-label">Label Teks Menu</label>
                        <input type="text" id="navLabelInput" class="builder-input" value="<?= htmlspecialchars($current_page_data['nav_label'] ?? $current_page_data['judul']) ?>" placeholder="Misal: Kerjasama">
                    </div>

                    <div class="mb-1">
                        <label class="builder-label">Urutan Nomor Menu</label>
                        <input type="number" id="pageUrutanInput" class="builder-input" value="<?= (int)($current_page_data['urutan'] ?? 0) ?>">
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Right Live Preview Canvas Area -->
    <section class="builder-canvas-wrapper">
        <div class="builder-iframe-frame" id="iframeFrameWrapper" style="width:100%;">
            <iframe id="previewIframe" src="builder-preview.php?id=<?= $current_page_data['id'] ?>"></iframe>
        </div>
    </section>
</main>

<!-- Modal: Pilihan Template Tambah Seksi / Blok Baru -->
<div class="modal fade" id="addBlockModal" tabindex="-1" aria-labelledby="addBlockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px;border:1px solid var(--builder-border);overflow:hidden;">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="addBlockModalLabel" style="color:var(--builder-navy);">
                    Pilih Jenis Seksi / Blok Untuk Ditambahkan
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <!-- 1. Hero Banner -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('hero')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;color:#7C3AED;">
                                    <i class="bi bi-badge-ad fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Hero Banner Utama</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Banner pembuka besar dengan judul tebal, pengantar, dan tombol ajakan aksi ganda.</p>
                        </div>
                    </div>

                    <!-- 2. Teks 2 Kolom & Media -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('text_image')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#E0F2FE;display:flex;align-items:center;justify-content:center;color:#0284C7;">
                                    <i class="bi bi-layout-sidebar-inset fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Teks 2-Kolom &amp; Media</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Tata letak dua kolom: uraian teks di satu sisi dan gambar pendukung di sisi lainnya.</p>
                        </div>
                    </div>

                    <!-- 3. Grid Kartu Fitur -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('cards_grid')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#DCFCE7;display:flex;align-items:center;justify-content:center;color:#15803D;">
                                    <i class="bi bi-grid-3x3-gap fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Grid Kartu Fitur (2, 3, 4 Kolom)</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Deretan kartu materi atau layanan dengan ikon, badge kategori, dan penjelasan ringkas.</p>
                        </div>
                    </div>

                    <!-- 4. Call to Action Strip -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('cta')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#FEE2E2;display:flex;align-items:center;justify-content:center;color:#DC2626;">
                                    <i class="bi bi-megaphone fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Call to Action (CTA Strip)</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Pita sorotan dengan latar belakang warna tegas untuk ajakan pendaftaran atau permohonan.</p>
                        </div>
                    </div>

                    <!-- 5. Accordion FAQ -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('accordion')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;color:#D97706;">
                                    <i class="bi bi-question-circle fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Accordion Tanya Jawab (FAQ)</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Daftar pertanyaan dan jawaban yang dapat diklik untuk dibuka-tutup secara ringkas.</p>
                        </div>
                    </div>

                    <!-- 6. Konten Bebas / Rich Text -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('rich_text')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:#475569;">
                                    <i class="bi bi-text-paragraph fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Konten Teks Bebas (HTML)</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Blok bebas untuk menuliskan paragraf, tabel informasi, kutipan, atau materi panjang.</p>
                        </div>
                    </div>

                    <!-- 7. Tautan Hyperlink & Tombol Aksi -->
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 h-100 hover-shadow cursor-pointer" onclick="addNewBlock('hyperlink')">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:#E0E7FF;display:flex;align-items:center;justify-content:center;color:#4338CA;">
                                    <i class="bi bi-link-45deg fs-4"></i>
                                </div>
                                <h6 class="mb-0 fw-bold" style="color:var(--builder-navy);">Tautan Hyperlink &amp; Tombol</h6>
                            </div>
                            <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.5;">Koleksi tombol tautan / hyperlink ke website atau sistem lain dengan kustomisasi susunan, bentuk, gaya, warna, dan teks.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Feedback -->
<div id="builderToast" class="builder-toast">
    <i class="bi bi-check-circle-fill text-success" id="toastIcon"></i>
    <span id="toastMessage">Perubahan berhasil disimpan</span>
</div>

<script>
let currentPageId = <?= (int)$current_page_data['id'] ?>;
let blocks = <?= json_encode($initial_blocks) ?>;
let activeIndex = -1;

// Device switcher (Desktop, Tablet, Mobile)
document.querySelectorAll('.device-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const width = this.getAttribute('data-width');
        document.getElementById('iframeFrameWrapper').style.width = width;
    });
});

// Toggle navbar options visibility
document.getElementById('showInNavCheck').addEventListener('change', function() {
    document.getElementById('navOptionsBox').style.display = this.checked ? 'block' : 'none';
});

// Tab Switcher in Left Sidebar
document.querySelectorAll('.builder-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        switchTab(this.getAttribute('data-tab'));
    });
});

function switchTab(tabId) {
    document.querySelectorAll('.builder-tab-btn').forEach(b => {
        b.classList.toggle('active', b.getAttribute('data-tab') === tabId);
    });
    document.querySelectorAll('.builder-tab-content').forEach(c => {
        c.style.display = (c.id === tabId) ? 'block' : 'none';
    });
}

// Drag & Drop State
let draggedIndex = null;

// Helper cek status draft seksi
function isBlockDraft(b) {
    if (!b) return false;
    if (b.status === 'draft') return true;
    if (b.is_visible === false) return true;
    return false;
}

// Render the tree list of sections in Tab 1
function renderSectionTree() {
    const list = document.getElementById('sectionTreeList');
    document.getElementById('sectionCountBadge').innerText = blocks.length + ' Seksi';

    if (blocks.length === 0) {
        list.innerHTML = '<div class="text-center py-4 text-muted" style="font-size:0.85rem;">Belum ada seksi pada halaman ini.</div>';
        return;
    }

    let html = '';
    blocks.forEach((b, idx) => {
        const isAct = (idx === activeIndex) ? 'active' : '';
        const isDraft = isBlockDraft(b);
        const draftClass = isDraft ? 'is-draft' : '';
        const title = b.title || b.badge || getBlockTypeName(b.type);
        const statusBtn = isDraft
            ? `<button type="button" class="btn btn-xs py-1 px-2 text-warning-emphasis fw-bold" style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:4px;font-size:0.72rem;" title="Status: Draf (Klik untuk Publikasikan seksi ini)" onclick="toggleBlockStatus(${idx}, event)">
                <i class="bi bi-eye-slash-fill me-1"></i>Draf
               </button>`
            : `<button type="button" class="btn btn-xs py-1 px-2 text-success fw-bold" style="background:#DCFCE7;border:1px solid #86EFAC;border-radius:4px;font-size:0.72rem;" title="Status: Live (Klik untuk jadikan Draf)" onclick="toggleBlockStatus(${idx}, event)">
                <i class="bi bi-check-circle-fill me-1"></i>Live
               </button>`;

        html += `
        <div class="section-tree-item ${isAct} ${draftClass}" 
             draggable="true" 
             data-index="${idx}"
             ondragstart="handleDragStart(event, ${idx})"
             ondragover="handleDragOver(event)"
             ondragenter="handleDragEnter(event, ${idx})"
             ondragleave="handleDragLeave(event)"
             ondrop="handleDrop(event, ${idx})"
             ondragend="handleDragEnd(event)"
             onclick="selectBlock(${idx}, false)">
            <div class="d-flex align-items-center gap-2" style="overflow:hidden;flex:1;">
                <span class="drag-handle" title="Tahan dan geser (Hold & Drag) untuk mengubah susunan seksi" onclick="event.stopPropagation()">
                    <i class="bi bi-grip-vertical"></i>
                </span>
                <span class="text-muted fw-bold" style="font-size:0.75rem;width:18px;">#${idx + 1}</span>
                <i class="bi ${getBlockTypeIcon(b.type)} ${isDraft ? 'text-warning' : 'text-primary'}" style="font-size:0.9rem;"></i>
                <span style="font-size:0.82rem;font-weight:600;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;max-width:130px;" title="${escapeHtml(title)}">
                    ${escapeHtml(title)} ${isDraft ? '<small class="text-warning fw-bold">(Draf)</small>' : ''}
                </span>
            </div>
            <div class="d-flex align-items-center gap-1" onclick="event.stopPropagation()">
                ${statusBtn}
                <button type="button" class="btn btn-xs py-1 px-2 text-primary" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:4px;font-size:0.75rem;font-weight:600;" title="Edit Gaya & Konten Seksi Ini" onclick="selectBlock(${idx}, true)">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </button>
                <button type="button" class="btn btn-xs p-1 text-danger ms-1" title="Hapus Seksi" onclick="deleteBlock(${idx})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>`;
    });
    list.innerHTML = html;
}

// Toggle status publish/draft per seksi
function toggleBlockStatus(idx, e) {
    if (e) e.stopPropagation();
    if (idx < 0 || idx >= blocks.length) return;
    const b = blocks[idx];
    const wasDraft = isBlockDraft(b);
    const newStatus = wasDraft ? 'publish' : 'draft';
    b.status = newStatus;
    b.is_visible = (newStatus === 'publish');
    renderSectionTree();
    if (activeIndex === idx) {
        openBlockEditor(idx);
    }
    sendUpdateToIframe();
    showToast(newStatus === 'draft' 
        ? `Seksi #${idx + 1} dijadikan Draf (Disembunyikan dari publik)` 
        : `Seksi #${idx + 1} Diterbitkan (Ditampilkan ke publik)`);
}

// Update status seksi dari panel editor (Tab 2)
function updateActiveBlockStatus(val) {
    if (activeIndex < 0 || !blocks[activeIndex]) return;
    const b = blocks[activeIndex];
    b.status = val;
    b.is_visible = (val === 'publish');
    renderSectionTree();
    openBlockEditor(activeIndex);
    sendUpdateToIframe();
    showToast(val === 'draft' 
        ? `Seksi #${activeIndex + 1} dijadikan Draf (Disembunyikan dari publik)` 
        : `Seksi #${activeIndex + 1} Diterbitkan (Ditampilkan ke publik)`);
}

// Drag & Drop Handlers (Hold and Drag)
function handleDragStart(e, index) {
    draggedIndex = index;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', index);
    setTimeout(() => {
        const item = document.querySelector(`.section-tree-item[data-index="${index}"]`);
        if (item) item.classList.add('dragging');
    }, 0);
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e, targetIndex) {
    const item = document.querySelector(`.section-tree-item[data-index="${targetIndex}"]`);
    if (item && targetIndex !== draggedIndex) {
        item.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

function handleDrop(e, targetIndex) {
    e.preventDefault();
    document.querySelectorAll('.section-tree-item').forEach(el => el.classList.remove('drag-over', 'dragging'));

    if (draggedIndex === null || draggedIndex === targetIndex) return;

    // Geser urutan seksi dalam array
    const moved = blocks.splice(draggedIndex, 1)[0];
    blocks.splice(targetIndex, 0, moved);
    activeIndex = targetIndex;
    draggedIndex = null;

    // Render ulang daftar seksi & sinkronisasi ke preview tanpa berpindah tab
    renderSectionTree();
    sendUpdateToIframe();
    showToast('Urutan seksi berhasil dipindahkan');
}

function syncIframePreview() {
    sendUpdateToIframe();
}

function handleDragEnd(e) {
    draggedIndex = null;
    document.querySelectorAll('.section-tree-item').forEach(el => el.classList.remove('drag-over', 'dragging'));
}

// Select a block to open in the inspector
// shouldSwitchTab = true hanya jika dipicu oleh klik edit atau klik pada kanvas live halaman
function selectBlock(idx, shouldSwitchTab = false) {
    if (idx < 0 || idx >= blocks.length) return;
    activeIndex = idx;
    renderSectionTree();
    openBlockEditor(idx);

    // Notify iframe preview to highlight this block
    const iframe = document.getElementById('previewIframe');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.postMessage({ action: 'select', index: idx }, '*');
    }

    if (shouldSwitchTab) {
        switchTab('tab-editor');
    }
}

// Open Editor for the active block
function openBlockEditor(idx) {
    const b = blocks[idx];
    if (!b) return;

    document.getElementById('editorEmptyState').style.display = 'none';
    document.getElementById('editorFormContainer').style.display = 'block';

    document.getElementById('activeBlockTypeBadge').innerText = getBlockTypeName(b.type);
    document.getElementById('activeBlockIndexLabel').innerText = `Seksi #${idx + 1}: ${b.title || getBlockTypeName(b.type)}`;

    // Set status seksi (Publish vs Draft)
    const isDraft = isBlockDraft(b);
    const statusSelect = document.getElementById('activeBlockStatusSelect');
    const statusBadge  = document.getElementById('activeBlockStatusBadge');
    const statusHint   = document.getElementById('activeBlockStatusHint');

    if (statusSelect && statusBadge && statusHint) {
        statusSelect.value = isDraft ? 'draft' : 'publish';
        if (isDraft) {
            statusBadge.className = 'badge bg-warning text-dark';
            statusBadge.innerText = 'Draf (Disembunyikan)';
            statusHint.innerHTML = '<i class="bi bi-eye-slash me-1 text-warning"></i> Seksi ini berstatus <strong>Draf</strong> dan tidak akan muncul di website publik.';
        } else {
            statusBadge.className = 'badge bg-success';
            statusBadge.innerText = 'Live (Terbit)';
            statusHint.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i> Seksi ini sedang <strong>Diterbitkan</strong> dan dapat dilihat oleh pengunjung umum website.';
        }
    }

    // Set colors & appearance
    const bg = b.bg_color || (b.type === 'hero' ? '#0A192F' : (b.type === 'cta' ? '#6A1B9A' : '#FFFFFF'));
    const tc = b.text_color || ((b.type === 'hero' || b.type === 'cta') ? '#FFFFFF' : '#0A192F');

    document.getElementById('blockBgColorPicker').value = bg;
    document.getElementById('blockBgColorText').value   = bg;
    document.getElementById('blockTextColorPicker').value = tc;
    document.getElementById('blockTextColorText').value   = tc;
    document.getElementById('blockPaddingSelect').value = b.padding || 'normal';

    const alignGroup = document.getElementById('alignControlGroup');
    if (b.type === 'hero') {
        alignGroup.style.display = 'block';
        document.getElementById('blockAlignSelect').value = b.align || 'center';
    } else {
        alignGroup.style.display = 'none';
    }

    // Build specific input fields based on block type
    const container = document.getElementById('blockFieldsContainer');
    let fieldsHtml = '';

    if (b.type === 'beranda_hero') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Ini adalah <strong>Hero Slider Utama Beranda</strong> yang memuat slide aktif dan tombol dari database. Anda dapat menambah atau mengganti gambar & slide melalui menu <strong>Kelola Hero Slider</strong>.
            </div>
            <div class="mb-3">
                <label class="builder-label">Nama Seksi di Builder</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Hero Slider Utama')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="p-2 border rounded-3 bg-light text-muted" style="font-size:0.8rem;">
                <i class="bi bi-sliders me-1"></i> Warna latar dan padding di atas akan menyesuaikan container slider secara langsung.
            </div>
        `;
    } else if (b.type === 'beranda_quick_links') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Tautan Cepat (Quick Links)</strong> menampilkan 6 kartu pintasan SPMI (Kebijakan, Manual, Standar, Formulir, Akreditasi, Hubungi Kami).
            </div>
            <div class="mb-3">
                <label class="builder-label">Nama Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Tautan Cepat (Quick Links)')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'beranda_stats') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Statistik Capaian Mutu (Stats Bar)</strong> menampilkan data 40+ Tahun Berdiri, 27+ Program Studi, 100% Akreditasi, dan Dokumen Mutu.
            </div>
            <div class="mb-3">
                <label class="builder-label">Nama Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Statistik Capaian Mutu')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'beranda_penghargaan') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Penghargaan & Rekognisi Mutu</strong> menampilkan slider/grid sertifikat penghargaan yang diunggah di menu Penghargaan.
            </div>
            <div class="mb-3">
                <label class="builder-label">Nama Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Penghargaan & Rekognisi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'beranda_akreditasi') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Badge Akreditasi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Akreditasi Institusi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Capaian Mutu</label>
                <textarea class="builder-input" rows="2" oninput="updateActiveField('title', this.value)">${escapeHtml(b.title || "Capaian Mutu\\nUniversitas Katolik Soegijapranata")}</textarea>
            </div>
            <div class="mb-3">
                <label class="builder-label">Teks Komitmen Mutu</label>
                <textarea class="builder-input" rows="3" oninput="updateActiveField('content', this.value)">${escapeHtml(b.content || '')}</textarea>
            </div>
        `;
    } else if (b.type === 'beranda_berita') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Berita & Kegiatan Terkini</strong> menampilkan 3 rilis berita terbaru dari database portal.
            </div>
            <div class="mb-3">
                <label class="builder-label">Nama Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Berita & Kegiatan Terkini')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'beranda_cta') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Judul Ajakan (Heading)</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Butuh Informasi Lebih Lanjut?')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Subjudul Penjelasan</label>
                <textarea class="builder-input" rows="2" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="builder-label">Teks Tombol 1</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_text || 'Hubungi Kami')}" oninput="updateActiveField('btn_text', this.value)">
                </div>
                <div class="col-6">
                    <label class="builder-label">Link Tombol 1</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_link || 'kontak.php')}" oninput="updateActiveField('btn_link', this.value)">
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="builder-label">Teks Tombol 2</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn2_text || 'Akses Dokumen SPMI')}" oninput="updateActiveField('btn2_text', this.value)">
                </div>
                <div class="col-6">
                    <label class="builder-label">Link Tombol 2</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn2_link || 'spmi.php')}" oninput="updateActiveField('btn2_link', this.value)">
                </div>
            </div>
        `;
    } else if (b.type === 'profil_tentang') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Tentang LPM & Statistik Mutu</strong> menampilkan perkenalan resmi lembaga dan 4 kartu metrik akreditasi A, prodi, dan tahun pengalaman.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Tentang Kami')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Lembaga Penjaminan Mutu UNIKA')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Ringkasan Pengantar</label>
                <textarea class="builder-input" rows="3" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
        `;
    } else if (b.type === 'profil_visi_misi') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Visi, Misi & Tujuan LPM</strong> memuat banner Visi, daftar butir Misi dan Tujuan penjaminan mutu.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Arah & Komitmen')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Visi, Misi & Tujuan LPM UNIKA')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'profil_tugas_fungsi') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Tugas & 8 Butir Fungsi LPM</strong> menampilkan mandat peraturan universitas dan daftar butir penyelenggaraan fungsi kerja.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Landasan Operasional')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Tugas dan Fungsi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'profil_struktur') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Struktur Organisasi & Tim LPM</strong> memuat bagan visual interaktif hirarki, tab personel LPM, dan tim GPM Fakultas.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Struktur & Tim')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Struktur Organisasi LPM')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'spmi_pengantar') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Pengantar Mengenal SPMI</strong> memuat uraian orientasi mutu, landasan regulasi, dan kartu akses ke Portal SISTA.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Pengantar Mutu')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Mengenal SPMI di UNIKA')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Uraian Teks Pengantar</label>
                <textarea class="builder-input" rows="3" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
        `;
    } else if (b.type === 'spmi_ppepp') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Siklus 5 PPEPP Terpadu</strong> memuat 5 kartu tahapan PPEPP dan callout banner pemantauan digital.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Alur Kerja Mutu')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Siklus PPEPP SPMI')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'spmi_kemendikti') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Hasil SPMI Kemendikti Saintek</strong> menampilkan berkas resmi pelaporan mutu kepada kementerian.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Publikasi & Pelaporan')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Hasil SPMI Kemendikti Saintek')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'spmi_dokumen') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Dokumen Mutu Resmi SPMI</strong> menampilkan tabel katalog dokumen (Kebijakan, Manual, Standar, Formulir).
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Berkas Resmi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Dokumen Mutu SPMI')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'ami_pengantar') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Tentang Audit Mutu Internal (AMI)</strong> menampilkan prinsip evaluasi dan kartu aksi siklus aktif.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Evaluasi Mutu')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Tentang Audit Mutu Internal (AMI)')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Teks Pengantar</label>
                <textarea class="builder-input" rows="3" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
        `;
    } else if (b.type === 'ami_alur') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Alur & 5 Tahapan Siklus AMI</strong> menampilkan 5 langkah dari sosialisasi, evaluasi diri, visitasi, hingga RTM.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Prosedur Kerja')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Alur & Tahapan Siklus AMI')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'ami_jadwal') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Jadwal Pelaksanaan AMI Dinamis</strong> menampilkan jadwal per tahun akademik dan dokumentasi kegiatan.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Jadwal Pelaksanaan Resmi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Jadwal Pelaksanaan Audit Mutu Internal (AMI)')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'ami_instrumen_auditor') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Pedoman, Instrumen & Auditor Mutu</strong> memuat berkas instrumen dan kualifikasi tim auditor internal.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Pedoman & Instrumen')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Pedoman, Instrumen & Auditor Mutu')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'akreditasi_institusi') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Akreditasi Institusi Unggul</strong> menampilkan status akreditasi tertinggi BAN-PT dan kartu unduh sertifikat.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Akreditasi Perguruan Tinggi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Akreditasi Institusi UNIKA Soegijapranata')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Teks Pengantar</label>
                <textarea class="builder-input" rows="3" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
        `;
    } else if (b.type === 'akreditasi_statistik') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Statistik & Rekapitulasi Akreditasi</strong> menampilkan metrik peringkat prodi, lembaga LAM, dan jenjang strata.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Data Statistik')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Rekapitulasi Status Akreditasi Program Studi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'akreditasi_lam') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Lembaga Akreditasi Mandiri (LAM)</strong> memuat kartu profil BAN-PT dan seluruh LAM mitra.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Lembaga Mitra')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Lembaga Akreditasi Mandiri & Mitra')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'akreditasi_prodi') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Daftar Akreditasi Program Studi</strong> memuat filter pencarian interaktif dan accordion fakultas prodi.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Data Resmi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Daftar Status Akreditasi Program Studi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'akreditasi_dokumen') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Unduh Dokumen & Sertifikat</strong> menampilkan ajakan dan akses katalog dokumen mutu institusi.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Berkas Resmi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Unduh Dokumen & Sertifikat Akreditasi Institusi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'akreditasi_faq') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Tanya Jawab (FAQ) Akreditasi</strong> memuat accordion tanya jawab seputar status mutu dan legalisir.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Informasi Penting')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Tanya Jawab (FAQ) Akreditasi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'mutu_data_dashboard') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Dashboard Metrik & Capaian Mutu</strong> menampilkan 4 kartu statistik utama, visual status akreditasi institusi, dan progress capaian SPMI.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Dashboard & Capaian')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Statistik Mutu Pendidikan & Akreditasi')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'mutu_data_iku') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Indikator Kinerja Utama (IKU)</strong> menampilkan 8 pilar IKU Kemendikbudristek dengan progress bar persentase capaian.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Indikator Utama')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Indikator Kinerja Utama (IKU) Universitas')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'dokumen_header') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Banner Pusat Dokumen</strong> menampilkan banner judul terpadu dan navigasi breadcrumb repositori berkas.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Database Dokumen Terpadu')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Banner</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Pusat Database Dokumen & Arsip')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Deskripsi Banner</label>
                <textarea class="builder-input" rows="2" oninput="updateActiveField('desc', this.value)">${escapeHtml(b.desc || '')}</textarea>
            </div>
        `;
    } else if (b.type === 'dokumen_table') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Tabel Pencarian & Repositori</strong> memuat kotak pencarian real-time, filter kategori sumber dokumen, dan tabel paginasi berkas.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Repositori Mutu')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Tabel Dokumen & Pencarian Pintar')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'knowledge_pengantar') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Pengantar Knowledge Center</strong> menampilkan banner portal pengetahuan mutu dan tautan cepat ke rubrik kalender, buletin, dan glosarium.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Knowledge Management System')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Banner</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Knowledge Center & Sumber Belajar Mutu')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'knowledge_kalender') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Kalender Kegiatan SPMI & AMI</strong> menampilkan agenda siklus mutu tahunan dalam grid kalender interaktif.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Jadwal & Agenda')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Kalender Kegiatan SPMI & AMI')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'knowledge_buletin') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Koleksi Buletin Mutu JAMUS</strong> menampilkan kartu terbitan berkala buletin LPM beserta tombol unduh dan preview PDF.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Publikasi Resmi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Koleksi Buletin Mutu (JAMUS)')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'knowledge_glosarium') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Glosarium Penjaminan Mutu</strong> menampilkan kamus terminologi mutu (SPMI, AMI, PPEPP, LAM, dll).
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Kamus Terminologi')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Glosarium Penjaminan Mutu')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'knowledge_faq') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Pertanyaan Sering Diajukan (FAQ)</strong> menampilkan accordion tanya jawab pengetahuan umum SPMI.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Pusat Bantuan')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Pertanyaan Sering Diajukan (FAQ)')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'berita_highlight') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Headline & Berita Terpopuler</strong> menampilkan banner berita kegiatan utama dan kartu artikel terpopuler di sisi kanan.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Berita Terkini & Kegiatan')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Publikasi Kegiatan & Berita Mutu')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'berita_grid') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Daftar Artikel & Agenda Lengkap</strong> menampilkan grid berita kegiatan terbaru dengan filter kategori dan pencarian.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Arsip Berita')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Daftar Artikel & Agenda Lengkap')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'layanan_cards') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Katalog Layanan & Konsultasi</strong> memuat 4 kartu layanan resmi LPM (Konsultasi SPMI, Pendampingan Akreditasi, Verifikasi Dokumen, Pelatihan Auditor).
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Layanan Mutu')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Katalog Layanan & Konsultasi SPMI')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'layanan_form') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Formulir Permohonan Layanan SPMI</strong> memuat formulir daring pengajuan bantuan layanan mutu untuk fakultas/unit kerja.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Permohonan Digital')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Formulir Pengajuan Permohonan Layanan SPMI')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'kontak_info') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Informasi Kontak & Lokasi</strong> menampilkan kartu alamat kantor LPM di Gedung Mikael, telepon, email resmi, jam operasional, dan peta lokasi.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Sekretariat Lembaga')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Informasi Kontak & Lokasi Kantor LPM')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'kontak_form') {
        fieldsHtml += `
            <div class="alert alert-info py-2 px-3 mb-3" style="font-size:0.8rem;border-radius:6px;line-height:1.5;">
                <i class="bi bi-info-circle me-1"></i> Seksi <strong>Formulir Pesan & Aspirasi</strong> memuat formulir daring kritik dan saran mutu beserta kartu FAQ cepat.
            </div>
            <div class="mb-3">
                <label class="builder-label">Badge Tag</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || 'Kritik & Saran')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || 'Sampaikan Masukan & Aspirasi Mutu')}" oninput="updateActiveField('title', this.value)">
            </div>
        `;
    } else if (b.type === 'hero') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Badge Atas</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || '')}" oninput="updateActiveField('badge', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Utama (Headline)</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || '')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Subjudul / Deskripsi</label>
                <textarea class="builder-input" rows="3" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="builder-label">Teks Tombol 1</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_text || '')}" oninput="updateActiveField('btn_text', this.value)">
                </div>
                <div class="col-6">
                    <label class="builder-label">Link Tombol 1</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_link || '')}" oninput="updateActiveField('btn_link', this.value)">
                </div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="builder-label">Teks Tombol 2</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_secondary_text || '')}" oninput="updateActiveField('btn_secondary_text', this.value)">
                </div>
                <div class="col-6">
                    <label class="builder-label">Link Tombol 2</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_secondary_link || '')}" oninput="updateActiveField('btn_secondary_link', this.value)">
                </div>
            </div>
        `;
    } else if (b.type === 'text_image') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Tag / Kategori Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.tag || '')}" oninput="updateActiveField('tag', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || '')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Uraian Isi Konten</label>
                <textarea class="builder-input" rows="5" oninput="updateActiveField('content', this.value)">${escapeHtml(b.content || '')}</textarea>
            </div>
            <div class="mb-3">
                <label class="builder-label">URL Gambar (atau nama file uploads)</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.image || '')}" oninput="updateActiveField('image', this.value)" placeholder="assets/images/... atau URL">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="builder-label">Posisi Gambar</label>
                    <select class="builder-input" onchange="updateActiveField('image_pos', this.value)">
                        <option value="right" ${(b.image_pos === 'right' || !b.image_pos) ? 'selected' : ''}>Kanan</option>
                        <option value="left" ${b.image_pos === 'left' ? 'selected' : ''}>Kiri</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="builder-label">Teks Tombol Aksi</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_text || '')}" oninput="updateActiveField('btn_text', this.value)">
                </div>
            </div>
        `;
    } else if (b.type === 'cards_grid') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Tag / Kategori</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.tag || '')}" oninput="updateActiveField('tag', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || '')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Subjudul Penjelasan</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.subtitle || '')}" oninput="updateActiveField('subtitle', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Jumlah Kolom</label>
                <select class="builder-input" onchange="updateActiveField('columns', this.value)">
                    <option value="2" ${b.columns == 2 ? 'selected' : ''}>2 Kolom</option>
                    <option value="3" ${b.columns == 3 || !b.columns ? 'selected' : ''}>3 Kolom</option>
                    <option value="4" ${b.columns == 4 ? 'selected' : ''}>4 Kolom</option>
                </select>
            </div>

            <div class="border-top pt-3 mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="builder-label mb-0">Daftar Kartu (${(b.cards || []).length})</span>
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addCardToGrid(${idx})">+ Tambah Kartu</button>
                </div>
                <div id="cardsListSub">`;
        
        (b.cards || []).forEach((c, cidx) => {
            fieldsHtml += `
            <div class="p-2 border rounded-3 mb-2 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:0.75rem;font-weight:700;">Kartu #${cidx + 1}</span>
                    <button type="button" class="btn btn-xs text-danger p-0" onclick="removeCardFromGrid(${idx}, ${cidx})">&times; Hapus</button>
                </div>
                <input type="text" class="builder-input mb-1" placeholder="Judul Kartu" value="${escapeHtml(c.title || '')}" oninput="updateCardField(${idx}, ${cidx}, 'title', this.value)">
                <textarea class="builder-input mb-1" rows="2" placeholder="Uraian Singkat" oninput="updateCardField(${idx}, ${cidx}, 'desc', this.value)">${escapeHtml(c.desc || '')}</textarea>
                <div class="row g-1">
                    <div class="col-6">
                        <input type="text" class="builder-input" placeholder="Ikon (bi-shield)" value="${escapeHtml(c.icon || '')}" oninput="updateCardField(${idx}, ${cidx}, 'icon', this.value)">
                    </div>
                    <div class="col-6">
                        <input type="text" class="builder-input" placeholder="Badge" value="${escapeHtml(c.badge || '')}" oninput="updateCardField(${idx}, ${cidx}, 'badge', this.value)">
                    </div>
                </div>
            </div>`;
        });

        fieldsHtml += `</div></div>`;
    } else if (b.type === 'cta') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Judul Ajakan (Heading)</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || '')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="mb-3">
                <label class="builder-label">Subjudul Penjelasan</label>
                <textarea class="builder-input" rows="2" oninput="updateActiveField('subtitle', this.value)">${escapeHtml(b.subtitle || '')}</textarea>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="builder-label">Teks Tombol</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_text || '')}" oninput="updateActiveField('btn_text', this.value)">
                </div>
                <div class="col-6">
                    <label class="builder-label">Link Tombol</label>
                    <input type="text" class="builder-input" value="${escapeHtml(b.btn_link || '')}" oninput="updateActiveField('btn_link', this.value)">
                </div>
            </div>
        `;
    } else if (b.type === 'accordion') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Judul Seksi FAQ</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || '')}" oninput="updateActiveField('title', this.value)">
            </div>
            <div class="border-top pt-3 mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="builder-label mb-0">Daftar Pertanyaan (${(b.items || []).length})</span>
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addFaqItem(${idx})">+ Tambah Tanya Jawab</button>
                </div>
                <div id="faqListSub">`;
        
        (b.items || []).forEach((it, qidx) => {
            fieldsHtml += `
            <div class="p-2 border rounded-3 mb-2 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:0.75rem;font-weight:700;">FAQ #${qidx + 1}</span>
                    <button type="button" class="btn btn-xs text-danger p-0" onclick="removeFaqItem(${idx}, ${qidx})">&times; Hapus</button>
                </div>
                <input type="text" class="builder-input mb-1" placeholder="Pertanyaan" value="${escapeHtml(it.question || '')}" oninput="updateFaqField(${idx}, ${qidx}, 'question', this.value)">
                <textarea class="builder-input" rows="2" placeholder="Jawaban" oninput="updateFaqField(${idx}, ${qidx}, 'answer', this.value)">${escapeHtml(it.answer || '')}</textarea>
            </div>`;
        });

        fieldsHtml += `</div></div>`;
    } else if (b.type === 'hyperlink') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Badge Tag (Opsional)</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.badge || '')}" oninput="updateActiveField('badge', this.value)" placeholder="Contoh: Akses Cepat">
            </div>
            <div class="mb-3">
                <label class="builder-label">Judul Seksi Tautan</label>
                <input type="text" class="builder-input" value="${escapeHtml(b.title || '')}" oninput="updateActiveField('title', this.value)" placeholder="Contoh: Portal & Tautan Eksternal">
            </div>
            <div class="mb-3">
                <label class="builder-label">Subjudul Penjelasan</label>
                <textarea class="builder-input" rows="2" oninput="updateActiveField('subtitle', this.value)" placeholder="Keterangan singkat...">${escapeHtml(b.subtitle || '')}</textarea>
            </div>

            <!-- Pengaturan Bentuk & Susunan -->
            <div class="p-3 mb-3 border rounded-3 bg-light">
                <span class="builder-label fw-bold d-block mb-2 text-primary" style="font-size:0.8rem;">
                    <i class="bi bi-grid-1x2-fill me-1"></i> Susunan &amp; Bentuk Tombol
                </span>
                
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="builder-label">Susunan Layout</label>
                        <select class="builder-input" onchange="updateActiveField('layout', this.value)">
                            <option value="grid-2" ${b.layout === 'grid-2' ? 'selected' : ''}>Grid 2 Kolom</option>
                            <option value="grid-3" ${(b.layout === 'grid-3' || !b.layout) ? 'selected' : ''}>Grid 3 Kolom</option>
                            <option value="grid-4" ${b.layout === 'grid-4' ? 'selected' : ''}>Grid 4 Kolom</option>
                            <option value="flex-wrap" ${b.layout === 'flex-wrap' ? 'selected' : ''}>Berjajar (Flex Wrap)</option>
                            <option value="list" ${b.layout === 'list' ? 'selected' : ''}>Daftar List Vertikal</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="builder-label">Bentuk Tombol</label>
                        <select class="builder-input" onchange="updateActiveField('btn_shape', this.value)">
                            <option value="rounded" ${(b.btn_shape === 'rounded' || !b.btn_shape) ? 'selected' : ''}>Melengkung (Rounded)</option>
                            <option value="pill" ${b.btn_shape === 'pill' ? 'selected' : ''}>Kapsul / Pill (Oval)</option>
                            <option value="square" ${b.btn_shape === 'square' ? 'selected' : ''}>Kotak Tegas (Square)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="builder-label">Gaya Tombol</label>
                        <select class="builder-input" onchange="updateActiveField('btn_style', this.value)">
                            <option value="solid" ${(b.btn_style === 'solid' || !b.btn_style) ? 'selected' : ''}>Solid (Warna Penuh)</option>
                            <option value="outline" ${b.btn_style === 'outline' ? 'selected' : ''}>Outline (Garis Tepi)</option>
                            <option value="soft" ${b.btn_style === 'soft' ? 'selected' : ''}>Soft Tint (Pastel Halus)</option>
                            <option value="card" ${b.btn_style === 'card' ? 'selected' : ''}>Kartu Modern (Card)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="builder-label">Ukuran Tombol</label>
                        <select class="builder-input" onchange="updateActiveField('btn_size', this.value)">
                            <option value="small" ${b.btn_size === 'small' ? 'selected' : ''}>Kecil (Compact)</option>
                            <option value="normal" ${(b.btn_size === 'normal' || !b.btn_size) ? 'selected' : ''}>Normal / Sedang</option>
                            <option value="large" ${b.btn_size === 'large' ? 'selected' : ''}>Besar (Prominent)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label class="builder-label">Warna Tombol</label>
                        <div class="d-flex gap-1">
                            <input type="color" class="form-control form-control-color p-1" style="width:36px;height:32px;" value="${b.btn_bg_color || '#1E3A8A'}" oninput="updateActiveField('btn_bg_color', this.value)">
                            <input type="text" class="builder-input py-1" style="font-size:0.75rem;" value="${b.btn_bg_color || '#1E3A8A'}" oninput="updateActiveField('btn_bg_color', this.value)">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="builder-label">Warna Teks Tombol</label>
                        <div class="d-flex gap-1">
                            <input type="color" class="form-control form-control-color p-1" style="width:36px;height:32px;" value="${b.btn_text_color || '#FFFFFF'}" oninput="updateActiveField('btn_text_color', this.value)">
                            <input type="text" class="builder-input py-1" style="font-size:0.75rem;" value="${b.btn_text_color || '#FFFFFF'}" oninput="updateActiveField('btn_text_color', this.value)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Item Link -->
            <div class="border-top pt-3 mt-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="builder-label mb-0 fw-bold">Daftar Link (${(b.links || []).length})</span>
                    <button type="button" class="btn btn-xs btn-outline-primary" onclick="addHyperlinkItem(${idx})">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Link
                    </button>
                </div>
                <div id="hyperlinkListSub">`;

        (b.links || []).forEach((l, lidx) => {
            fieldsHtml += `
            <div class="p-2 border rounded-3 mb-2 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:0.75rem;font-weight:700;color:var(--builder-navy);"><i class="bi bi-link-45deg"></i> Link #${lidx + 1}</span>
                    <button type="button" class="btn btn-xs text-danger p-0" onclick="removeHyperlinkItem(${idx}, ${lidx})">&times; Hapus</button>
                </div>
                <div class="mb-1">
                    <input type="text" class="builder-input" placeholder="Teks Tombol / Judul Link" value="${escapeHtml(l.title || '')}" oninput="updateHyperlinkField(${idx}, ${lidx}, 'title', this.value)">
                </div>
                <div class="mb-1">
                    <input type="url" class="builder-input" placeholder="https://example.com" value="${escapeHtml(l.url || '')}" oninput="updateHyperlinkField(${idx}, ${lidx}, 'url', this.value)">
                </div>
                <div class="mb-1">
                    <input type="text" class="builder-input" placeholder="Keterangan singkat (opsional)" value="${escapeHtml(l.desc || '')}" oninput="updateHyperlinkField(${idx}, ${lidx}, 'desc', this.value)">
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-7">
                        <input type="text" class="builder-input" placeholder="Ikon (bi-globe, bi-box-arrow-up-right)" value="${escapeHtml(l.icon || 'bi-link-45deg')}" oninput="updateHyperlinkField(${idx}, ${lidx}, 'icon', this.value)">
                    </div>
                    <div class="col-5">
                        <label class="small text-muted mb-0 d-flex align-items-center gap-1" style="font-size:0.72rem;cursor:pointer;">
                            <input type="checkbox" ${l.target !== '_self' ? 'checked' : ''} onchange="updateHyperlinkField(${idx}, ${lidx}, 'target', this.checked ? '_blank' : '_self')"> Tab Baru
                        </label>
                    </div>
                </div>
            </div>`;
        });

        fieldsHtml += `</div></div>`;
    } else {
        // Rich text
        fieldsHtml += `
            <div class="mb-3">
                <label class="builder-label">Isi Konten (HTML / Teks Bebas)</label>
                <textarea class="builder-input" rows="10" oninput="updateActiveField('content', this.value)">${escapeHtml(b.content || '')}</textarea>
            </div>
        `;
    }

    container.innerHTML = fieldsHtml;
}

// Real-time synchronization
function updateActiveField(field, value) {
    if (activeIndex < 0 || !blocks[activeIndex]) return;
    blocks[activeIndex][field] = value;
    sendUpdateToIframe();
    if (field === 'title' || field === 'badge') {
        renderSectionTree();
    }
}

// Styling sync (color pickers)
document.getElementById('blockBgColorPicker').addEventListener('input', function() {
    document.getElementById('blockBgColorText').value = this.value.toUpperCase();
    updateActiveField('bg_color', this.value);
});
document.getElementById('blockBgColorText').addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        document.getElementById('blockBgColorPicker').value = this.value;
        updateActiveField('bg_color', this.value);
    }
});

document.getElementById('blockTextColorPicker').addEventListener('input', function() {
    document.getElementById('blockTextColorText').value = this.value.toUpperCase();
    updateActiveField('text_color', this.value);
});
document.getElementById('blockTextColorText').addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        document.getElementById('blockTextColorPicker').value = this.value;
        updateActiveField('text_color', this.value);
    }
});

document.getElementById('blockPaddingSelect').addEventListener('change', function() {
    updateActiveField('padding', this.value);
});
document.getElementById('blockAlignSelect').addEventListener('change', function() {
    updateActiveField('align', this.value);
});

// Grid cards sub-items
function addCardToGrid(blockIdx) {
    if (!blocks[blockIdx].cards) blocks[blockIdx].cards = [];
    blocks[blockIdx].cards.push({
        title: 'Kartu Baru',
        desc: 'Deskripsi singkat kartu materi ini.',
        icon: 'bi-check-circle',
        badge: 'Mutu'
    });
    openBlockEditor(blockIdx);
    sendUpdateToIframe();
}

function removeCardFromGrid(blockIdx, cardIdx) {
    blocks[blockIdx].cards.splice(cardIdx, 1);
    openBlockEditor(blockIdx);
    sendUpdateToIframe();
}

function updateCardField(blockIdx, cardIdx, field, val) {
    blocks[blockIdx].cards[cardIdx][field] = val;
    sendUpdateToIframe();
}

// FAQ sub-items
function addFaqItem(blockIdx) {
    if (!blocks[blockIdx].items) blocks[blockIdx].items = [];
    blocks[blockIdx].items.push({
        question: 'Pertanyaan baru?',
        answer: 'Jawaban penjelasan untuk pertanyaan ini.'
    });
    openBlockEditor(blockIdx);
    sendUpdateToIframe();
}

function removeFaqItem(blockIdx, faqIdx) {
    blocks[blockIdx].items.splice(faqIdx, 1);
    openBlockEditor(blockIdx);
    sendUpdateToIframe();
}

function updateFaqField(blockIdx, faqIdx, field, val) {
    blocks[blockIdx].items[faqIdx][field] = val;
    sendUpdateToIframe();
}

// Hyperlink sub-items
function addHyperlinkItem(blockIdx) {
    if (!blocks[blockIdx].links) blocks[blockIdx].links = [];
    blocks[blockIdx].links.push({
        title: 'Tautan Baru',
        url: 'https://',
        desc: '',
        icon: 'bi-link-45deg',
        target: '_blank'
    });
    openBlockEditor(blockIdx);
    sendUpdateToIframe();
}

function removeHyperlinkItem(blockIdx, linkIdx) {
    blocks[blockIdx].links.splice(linkIdx, 1);
    openBlockEditor(blockIdx);
    sendUpdateToIframe();
}

function updateHyperlinkField(blockIdx, linkIdx, field, val) {
    blocks[blockIdx].links[linkIdx][field] = val;
    sendUpdateToIframe();
}

// Reordering blocks
function moveBlock(index, direction) {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= blocks.length) return;
    const temp = blocks[index];
    blocks[index] = blocks[newIndex];
    blocks[newIndex] = temp;
    activeIndex = newIndex;
    renderSectionTree();
    openBlockEditor(activeIndex);
    sendUpdateToIframe();
}

// Delete block
function deleteBlock(index) {
    if (confirm('Yakin ingin menghapus seksi ini?')) {
        blocks.splice(index, 1);
        activeIndex = blocks.length > 0 ? Math.min(index, blocks.length - 1) : -1;
        renderSectionTree();
        if (activeIndex >= 0) {
            openBlockEditor(activeIndex);
        } else {
            document.getElementById('editorEmptyState').style.display = 'block';
            document.getElementById('editorFormContainer').style.display = 'none';
        }
        sendUpdateToIframe();
    }
}

function deleteActiveBlock() {
    if (activeIndex >= 0) deleteBlock(activeIndex);
}

function duplicateActiveBlock() {
    if (activeIndex < 0) return;
    const clone = JSON.parse(JSON.stringify(blocks[activeIndex]));
    clone.id = 'block_' + Date.now();
    clone.title = (clone.title || '') + ' (Salinan)';
    blocks.splice(activeIndex + 1, 0, clone);
    selectBlock(activeIndex + 1);
    sendUpdateToIframe();
}

// Add new block from Modal
function addNewBlock(type) {
    const newBlock = {
        id: 'block_' + Date.now(),
        type: type,
        padding: 'normal'
    };

    if (type.startsWith('profil_') || type.startsWith('spmi_') || type.startsWith('ami_') || type.startsWith('akreditasi_') || type.startsWith('beranda_')) {
        newBlock.title = getBlockTypeName(type);
        newBlock.badge = '';
        newBlock.bg_color = '';
        newBlock.text_color = '';
    } else if (type === 'hero') {
        newBlock.badge = 'LPM UNIKA';
        newBlock.title = 'Judul Hero Banner';
        newBlock.subtitle = 'Tuliskan deskripsi ringkas atau pengantar utama halaman ini.';
        newBlock.btn_text = 'Aksi Utama';
        newBlock.btn_link = '#';
        newBlock.bg_color = '#0A192F';
        newBlock.text_color = '#FFFFFF';
        newBlock.align = 'center';
    } else if (type === 'text_image') {
        newBlock.tag = 'Pengantar Mutu';
        newBlock.title = 'Uraian Materi & Gambaran';
        newBlock.content = '<p>Tuliskan penjelasan komprehensif mengenai kebijakan atau standar mutu di sini.</p>';
        newBlock.image = '';
        newBlock.image_pos = 'right';
        newBlock.bg_color = '#FFFFFF';
        newBlock.text_color = '#0A192F';
    } else if (type === 'cards_grid') {
        newBlock.tag = 'Layanan Mutu';
        newBlock.title = 'Daftar Komponen & Layanan';
        newBlock.subtitle = 'Berbagai fokus dan pilar yang diselenggarakan oleh unit.';
        newBlock.columns = 3;
        newBlock.bg_color = '#F8F9FA';
        newBlock.text_color = '#0A192F';
        newBlock.cards = [
            { title: 'Layanan Pertama', desc: 'Penjelasan layanan atau materi pertama.', icon: 'bi-check-circle', badge: 'Utama' },
            { title: 'Layanan Kedua', desc: 'Penjelasan layanan atau materi kedua.', icon: 'bi-shield-check', badge: 'Standar' },
            { title: 'Layanan Ketiga', desc: 'Penjelasan layanan atau materi ketiga.', icon: 'bi-award', badge: 'Unggul' }
        ];
    } else if (type === 'cta') {
        newBlock.title = 'Mulai Koordinasi Penjaminan Mutu';
        newBlock.subtitle = 'Hubungi kami untuk informasi selengkapnya mengenai pedoman ini.';
        newBlock.btn_text = 'Hubungi Tim LPM';
        newBlock.btn_link = 'layanan.php';
        newBlock.bg_color = '#6A1B9A';
        newBlock.text_color = '#FFFFFF';
    } else if (type === 'accordion') {
        newBlock.title = 'Pertanyaan Yang Sering Diajukan (FAQ)';
        newBlock.bg_color = '#FFFFFF';
        newBlock.items = [
            { question: 'Apa saja dokumen yang dipersyaratkan?', answer: 'Dokumen panduan, SK penugasan, dan formulir pengisian evaluasi.' },
            { question: 'Kapan jadwal pelaksanaan evaluasi?', answer: 'Pelaksanaan evaluasi diselenggarakan secara periodik setiap semester ganjil dan genap.' }
        ];
    } else if (type === 'hyperlink') {
        newBlock.badge = 'Akses Cepat';
        newBlock.title = 'Tautan & Rujukan Eksternal';
        newBlock.subtitle = 'Koleksi tautan rujukan dan portal eksternal penjaminan mutu.';
        newBlock.layout = 'grid-3';
        newBlock.btn_shape = 'rounded';
        newBlock.btn_style = 'solid';
        newBlock.btn_size = 'normal';
        newBlock.bg_color = '#FFFFFF';
        newBlock.text_color = '#0A192F';
        newBlock.btn_bg_color = '#1E3A8A';
        newBlock.btn_text_color = '#FFFFFF';
        newBlock.links = [
            { title: 'BAN-PT Resmi', url: 'https://www.banpt.or.id/', desc: 'Badan Akreditasi Nasional Perguruan Tinggi', icon: 'bi-box-arrow-up-right', target: '_blank' },
            { title: 'LAMEMBA', url: 'https://lamemba.or.id/', desc: 'Lembaga Akreditasi Bidang Ekonomi & Bisnis', icon: 'bi-box-arrow-up-right', target: '_blank' },
            { title: 'LAMINFOKOM', url: 'https://laminfokom.or.id/', desc: 'Lembaga Akreditasi Bidang Informatika & Komputer', icon: 'bi-box-arrow-up-right', target: '_blank' }
        ];
    } else {
        newBlock.content = '<p>Ketikkan paragraf atau materi teks Anda secara leluasa di sini.</p>';
        newBlock.bg_color = '#FFFFFF';
        newBlock.text_color = '#0A192F';
    }

    blocks.push(newBlock);
    const modalEl = document.getElementById('addBlockModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    selectBlock(blocks.length - 1);
    sendUpdateToIframe();
}

// Send updated blocks array to the preview iframe
function sendUpdateToIframe() {
    const iframe = document.getElementById('previewIframe');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.postMessage({
            action: 'render',
            blocks: blocks,
            selectedIndex: activeIndex
        }, '*');
    }
}

// Listen to message from iframe when a section is clicked directly in preview
window.addEventListener('message', function(event) {
    if (event.data && event.data.action === 'sectionSelected') {
        const idx = event.data.index;
        if (idx !== undefined && idx >= 0 && idx < blocks.length) {
            selectBlock(idx);
        }
    }
});

// Save & Publish Action
document.getElementById('savePublishBtn').addEventListener('click', function() {
    const title = document.getElementById('pageTitleInput').value.trim();
    if (!title) {
        alert('Judul halaman tidak boleh kosong!');
        switchTab('tab-settings');
        document.getElementById('pageTitleInput').focus();
        return;
    }

    const payload = {
        page_id: currentPageId,
        judul: title,
        slug: document.getElementById('pageSlugInput').value.trim(),
        kategori: document.getElementById('pageKategoriInput').value,
        status: document.getElementById('pageStatusInput').value,
        show_in_nav: document.getElementById('showInNavCheck').checked ? 1 : 0,
        nav_position: document.getElementById('navPositionSelect').value,
        nav_label: document.getElementById('navLabelInput').value.trim(),
        urutan: parseInt(document.getElementById('pageUrutanInput').value || 0),
        blocks: blocks
    };

    // UI loading state
    const btn = document.getElementById('savePublishBtn');
    const spinner = document.getElementById('saveBtnSpinner');
    const icon = document.getElementById('saveBtnIcon');
    const text = document.getElementById('saveBtnText');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    text.innerText = (payload.status === 'draft') ? 'Menyimpan Draf...' : 'Menyimpan...';

    fetch('builder-save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        updatePageStatusUI(payload.status);

        if (data.success) {
            currentPageId = data.page_id;
            document.getElementById('topbarPageTitle').innerText = title;
            if (data.url) {
                document.getElementById('livePageBtn').href = data.url;
            }

            showToast(data.message || (payload.status === 'draft' ? 'Halaman berhasil disimpan sebagai Draf!' : 'Halaman berhasil disimpan dan dipublikasikan!'));
        } else {
            alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        updatePageStatusUI(payload.status);
        alert('Koneksi terputus atau terjadi kesalahan: ' + err);
    });
});

// Update UI status penerbitan halaman (Topbar dan Tombol Simpan)
function updatePageStatusUI(status) {
    const isDraft = (status === 'draft');
    const saveBtnText = document.getElementById('saveBtnText');
    const badge = document.getElementById('topbarPageStatusBadge');

    if (saveBtnText) {
        saveBtnText.innerText = isDraft ? 'Simpan sebagai Draf' : 'Simpan & Publikasikan';
    }
    if (badge) {
        badge.className = isDraft ? 'badge bg-warning text-dark' : 'badge bg-success';
        badge.innerText = isDraft ? 'Draf' : 'Live';
    }
}

// Pantau perubahan pilihan status halaman di Tab Pengaturan
document.getElementById('pageStatusInput').addEventListener('change', function() {
    updatePageStatusUI(this.value);
});

function showToast(msg) {
    const toast = document.getElementById('builderToast');
    document.getElementById('toastMessage').innerText = msg;
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3500);
}

// Helpers
function getBlockTypeName(type) {
    const map = {
        // Beranda
        'beranda_hero': 'Hero Slider Utama',
        'beranda_quick_links': 'Tautan Cepat (Quick Links)',
        'beranda_stats': 'Statistik Capaian (Stats Bar)',
        'beranda_penghargaan': 'Penghargaan & Rekognisi',
        'beranda_akreditasi': 'Akreditasi Institusi Unggul',
        'beranda_berita': 'Berita & Pengumuman',
        'beranda_cta': 'Call to Action (CTA Banner)',

        // Profil
        'profil_tentang': 'Tentang LPM & Statistik Mutu',
        'profil_visi_misi': 'Visi, Misi & Tujuan LPM',
        'profil_tugas_fungsi': 'Tugas & 8 Butir Fungsi LPM',
        'profil_struktur': 'Struktur Organisasi & Tim LPM',

        // SPMI
        'spmi_pengantar': 'Pengantar Mengenal SPMI',
        'spmi_ppepp': 'Siklus 5 PPEPP Terpadu',
        'spmi_kemendikti': 'Hasil SPMI Kemendikti Saintek',
        'spmi_dokumen': 'Dokumen Mutu Resmi SPMI',

        // AMI
        'ami_pengantar': 'Pengantar & Tentang AMI',
        'ami_alur': 'Alur & 5 Tahapan Siklus AMI',
        'ami_jadwal': 'Jadwal Pelaksanaan AMI Dinamis',
        'ami_instrumen_auditor': 'Pedoman, Instrumen & Auditor Mutu',

        // Akreditasi
        'akreditasi_institusi': 'Akreditasi Institusi Unggul BAN-PT',
        'akreditasi_statistik': 'Statistik & Ringkasan Akreditasi',
        'akreditasi_lam': 'Lembaga Akreditasi Mandiri (LAM)',
        'akreditasi_prodi': 'Daftar Akreditasi Program Studi',
        'akreditasi_dokumen': 'Unduh Dokumen & Sertifikat',
        'akreditasi_faq': 'Tanya Jawab (FAQ) Akreditasi',

        // Mutu & Data
        'mutu_data_dashboard': 'Dashboard Metrik & Capaian Mutu',
        'mutu_data_iku': 'Indikator Kinerja Utama (IKU) & Data',

        // Dokumen
        'dokumen_header': 'Banner Pusat Dokumen & Regulasi',
        'dokumen_table': 'Tabel Repositori & Pencarian Dokumen',

        // Knowledge Management
        'knowledge_pengantar': 'Banner Portal Knowledge Center',
        'knowledge_kalender': 'Kalender Kegiatan SPMI & AMI',
        'knowledge_buletin': 'Koleksi Buletin Mutu JAMUS',
        'knowledge_glosarium': 'Glosarium Penjaminan Mutu',
        'knowledge_faq': 'Tanya Jawab (FAQ) Pengetahuan Mutu',

        // Berita & Kegiatan
        'berita_highlight': 'Headline & Berita Terpopuler',
        'berita_grid': 'Daftar Artikel & Agenda Kegiatan',

        // Layanan SPMI
        'layanan_cards': 'Daftar Layanan Mutu & Konsultasi',
        'layanan_form': 'Formulir Permohonan Layanan SPMI',

        // Kontak
        'kontak_info': 'Informasi Sekretariat & Lokasi LPM',
        'kontak_form': 'Formulir Pesan & Aspirasi Mutu',

        // Generic
        'hero': 'Hero Banner Utama',
        'text_image': 'Teks & Media',
        'cards_grid': 'Grid Kartu Fitur',
        'hyperlink': 'Tautan Hyperlink & Tombol',
        'cta': 'Call to Action',
        'accordion': 'Accordion FAQ',
        'rich_text': 'Konten Bebas'
    };
    return map[type] || 'Seksi';
}

function getBlockTypeIcon(type) {
    const map = {
        // Beranda
        'beranda_hero': 'bi-images',
        'beranda_quick_links': 'bi-grid-fill',
        'beranda_stats': 'bi-bar-chart-line-fill',
        'beranda_penghargaan': 'bi-trophy-fill',
        'beranda_akreditasi': 'bi-award-fill',
        'beranda_berita': 'bi-newspaper',
        'beranda_cta': 'bi-megaphone-fill',

        // Profil
        'profil_tentang': 'bi-info-circle-fill',
        'profil_visi_misi': 'bi-compass-fill',
        'profil_tugas_fungsi': 'bi-card-checklist',
        'profil_struktur': 'bi-diagram-3-fill',

        // SPMI
        'spmi_pengantar': 'bi-book-fill',
        'spmi_ppepp': 'bi-arrow-repeat',
        'spmi_kemendikti': 'bi-file-earmark-check-fill',
        'spmi_dokumen': 'bi-folder-fill',

        // AMI
        'ami_pengantar': 'bi-shield-check',
        'ami_alur': 'bi-bezier2',
        'ami_jadwal': 'bi-calendar-event-fill',
        'ami_instrumen_auditor': 'bi-people-fill',

        // Akreditasi
        'akreditasi_institusi': 'bi-patch-check-fill',
        'akreditasi_statistik': 'bi-bar-chart-fill',
        'akreditasi_lam': 'bi-building-fill',
        'akreditasi_prodi': 'bi-table',
        'akreditasi_dokumen': 'bi-download',
        'akreditasi_faq': 'bi-question-circle-fill',

        // Mutu & Data
        'mutu_data_dashboard': 'bi-speedometer2',
        'mutu_data_iku': 'bi-graph-up-arrow',

        // Dokumen
        'dokumen_header': 'bi-folder2-open',
        'dokumen_table': 'bi-search',

        // Knowledge Management
        'knowledge_pengantar': 'bi-lightbulb-fill',
        'knowledge_kalender': 'bi-calendar3',
        'knowledge_buletin': 'bi-journal-bookmark-fill',
        'knowledge_glosarium': 'bi-spellcheck',
        'knowledge_faq': 'bi-question-diamond-fill',

        // Berita & Kegiatan
        'berita_highlight': 'bi-star-fill',
        'berita_grid': 'bi-newspaper',

        // Layanan SPMI
        'layanan_cards': 'bi-card-checklist',
        'layanan_form': 'bi-pencil-square',

        // Kontak
        'kontak_info': 'bi-geo-alt-fill',
        'kontak_form': 'bi-envelope-paper-fill',

        // Generic
        'hero': 'bi-badge-ad',
        'text_image': 'bi-layout-sidebar-inset',
        'cards_grid': 'bi-grid-3x3-gap',
        'hyperlink': 'bi-link-45deg',
        'cta': 'bi-megaphone',
        'accordion': 'bi-question-circle',
        'rich_text': 'bi-text-paragraph'
    };
    return map[type] || 'bi-layers';
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Listen to messages from preview iframe (ketika seksi/halaman pada kanvas live preview di-klik)
window.addEventListener('message', function(e) {
    if (e.data && (e.data.action === 'sectionSelected' || e.data.action === 'select')) {
        const idx = parseInt(e.data.index);
        if (!isNaN(idx) && idx >= 0 && idx < blocks.length) {
            // Admin baru pindah ke Gaya & Konten bila seksi di kanvas halaman di-klik!
            selectBlock(idx, true);
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    renderSectionTree();
    // Pada saat awal buka halaman, tetap berada di tab Susunan Seksi (tidak otomatis pindah)
    if (blocks.length > 0) {
        selectBlock(0, false);
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
