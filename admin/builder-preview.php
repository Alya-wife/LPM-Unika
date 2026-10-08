<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('Akses ditolak. Sesi login admin belum aktif.');
}

$page_id = (int)($_GET['id'] ?? 0);
$slug    = trim($_GET['slug'] ?? '');

$blocks = [];
$page_title = 'Halaman Baru';
$is_beranda = false;
$page_mode  = 'custom';

require_once __DIR__ . '/../includes/beranda-sections.php';
require_once __DIR__ . '/../includes/profil-sections.php';
require_once __DIR__ . '/../includes/spmi-sections.php';
require_once __DIR__ . '/../includes/ami-sections.php';
require_once __DIR__ . '/../includes/akreditasi-sections.php';
require_once __DIR__ . '/../includes/mutu-data-sections.php';
require_once __DIR__ . '/../includes/dokumen-sections.php';
require_once __DIR__ . '/../includes/knowledge-sections.php';
require_once __DIR__ . '/../includes/berita-sections.php';
require_once __DIR__ . '/../includes/layanan-sections.php';
require_once __DIR__ . '/../includes/kontak-sections.php';

$p = null;
if ($page_id > 0) {
    $stmt = getDB()->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$page_id]);
    $p = $stmt->fetch();
} elseif ($slug) {
    $stmt = getDB()->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$slug]);
    $p = $stmt->fetch();
}

if ($p) {
    $page_title = $p['judul'];
    $current_slug = $p['slug'] ?? '';
    $current_url  = $p['custom_url'] ?? '';

    if ($current_slug === 'beranda' || $current_url === 'index.php') {
        $page_mode = 'beranda';
        $is_beranda = true;
    } elseif ($current_slug === 'profil' || $current_url === 'profil.php') {
        $page_mode = 'profil';
    } elseif ($current_slug === 'spmi' || $current_url === 'spmi.php') {
        $page_mode = 'spmi';
    } elseif ($current_slug === 'ami' || $current_url === 'ami.php') {
        $page_mode = 'ami';
    } elseif ($current_slug === 'akreditasi' || $current_url === 'akreditasi.php') {
        $page_mode = 'akreditasi';
    } elseif ($current_slug === 'mutu-data' || $current_url === 'mutu-data.php') {
        $page_mode = 'mutu-data';
    } elseif ($current_slug === 'dokumen' || $current_url === 'dokumen.php') {
        $page_mode = 'dokumen';
    } elseif ($current_slug === 'knowledge' || $current_url === 'knowledge.php') {
        $page_mode = 'knowledge';
    } elseif ($current_slug === 'berita' || $current_url === 'berita.php') {
        $page_mode = 'berita';
    } elseif ($current_slug === 'layanan' || $current_url === 'layanan.php') {
        $page_mode = 'layanan';
    } elseif ($current_slug === 'kontak' || $current_url === 'kontak.php') {
        $page_mode = 'kontak';
    }

    if (!empty($p['blocks_json'])) {
        $blocks = json_decode($p['blocks_json'], true) ?: [];
    }
}

if (empty($blocks)) {
    if ($page_mode === 'beranda') {
        $blocks = [
            ['id' => 'block_beranda_hero', 'type' => 'beranda_hero', 'title' => 'Mewujudkan Budaya Mutu Berkelanjutan', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_quick_links', 'type' => 'beranda_quick_links', 'title' => 'Layanan & Navigasi Cepat', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_stats', 'type' => 'beranda_stats', 'title' => 'Statistik Penjaminan Mutu', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_penghargaan', 'type' => 'beranda_penghargaan', 'title' => 'Penghargaan & Rekognisi Mutu', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_akreditasi', 'type' => 'beranda_akreditasi', 'title' => 'Akreditasi Institusi Unggul', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_berita', 'type' => 'beranda_berita', 'title' => 'Berita & Publikasi Kegiatan', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_beranda_cta', 'type' => 'beranda_cta', 'title' => 'Butuh Informasi Lebih Lanjut?', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'profil') {
        $blocks = [
            ['id' => 'block_profil_tentang', 'type' => 'profil_tentang', 'badge' => 'Tentang Kami', 'title' => 'Lembaga Penjaminan Mutu UNIKA', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_profil_visi_misi', 'type' => 'profil_visi_misi', 'badge' => 'Arah & Komitmen', 'title' => 'Visi, Misi & Tujuan LPM UNIKA', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_profil_tugas_fungsi', 'type' => 'profil_tugas_fungsi', 'badge' => 'Landasan Operasional', 'title' => 'Tugas dan Fungsi', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_profil_struktur', 'type' => 'profil_struktur', 'badge' => 'Struktur & Tim', 'title' => 'Struktur Organisasi LPM', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'spmi') {
        $blocks = [
            ['id' => 'block_spmi_pengantar', 'type' => 'spmi_pengantar', 'badge' => 'Pengantar Mutu', 'title' => 'Mengenal SPMI di UNIKA', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_spmi_ppepp', 'type' => 'spmi_ppepp', 'badge' => 'Alur Kerja Mutu', 'title' => 'Siklus PPEPP SPMI', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_spmi_kemendikti', 'type' => 'spmi_kemendikti', 'badge' => 'Publikasi & Pelaporan', 'title' => 'Hasil SPMI Kemendikti Saintek', 'bg_color' => '#f1f5f9', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_spmi_dokumen', 'type' => 'spmi_dokumen', 'badge' => 'Berkas Resmi', 'title' => 'Dokumen Mutu SPMI', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'ami') {
        $blocks = [
            ['id' => 'block_ami_pengantar', 'type' => 'ami_pengantar', 'badge' => 'Evaluasi Mutu', 'title' => 'Tentang Audit Mutu Internal (AMI)', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_ami_alur', 'type' => 'ami_alur', 'badge' => 'Prosedur Kerja', 'title' => 'Alur & Tahapan Siklus AMI', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_ami_jadwal', 'type' => 'ami_jadwal', 'badge' => 'Jadwal Pelaksanaan Resmi', 'title' => 'Jadwal Pelaksanaan Audit Mutu Internal (AMI)', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_ami_instrumen_auditor', 'type' => 'ami_instrumen_auditor', 'badge' => 'Pedoman & Instrumen', 'title' => 'Pedoman, Instrumen & Auditor Mutu', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'akreditasi') {
        $blocks = [
            ['id' => 'block_akreditasi_institusi', 'type' => 'akreditasi_institusi', 'badge' => 'Akreditasi Perguruan Tinggi', 'title' => 'Akreditasi Institusi UNIKA Soegijapranata', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_statistik', 'type' => 'akreditasi_statistik', 'badge' => 'Data Statistik', 'title' => 'Rekapitulasi Status Akreditasi Program Studi', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_lam', 'type' => 'akreditasi_lam', 'badge' => 'Lembaga Mitra', 'title' => 'Lembaga Akreditasi Mandiri & Mitra', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_prodi', 'type' => 'akreditasi_prodi', 'badge' => 'Data Resmi', 'title' => 'Daftar Status Akreditasi Program Studi', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_dokumen', 'type' => 'akreditasi_dokumen', 'badge' => 'Berkas Resmi', 'title' => 'Unduh Dokumen & Sertifikat Akreditasi Institusi', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_akreditasi_faq', 'type' => 'akreditasi_faq', 'badge' => 'Informasi Penting', 'title' => 'Tanya Jawab (FAQ) Akreditasi', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'mutu-data') {
        $blocks = [
            ['id' => 'block_mutu_data_dashboard', 'type' => 'mutu_data_dashboard', 'badge' => 'Dashboard & Capaian', 'title' => 'Statistik Mutu Pendidikan & Akreditasi', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_mutu_data_iku', 'type' => 'mutu_data_iku', 'badge' => 'Indikator Utama', 'title' => 'Indikator Kinerja Utama (IKU) Universitas', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'dokumen') {
        $blocks = [
            ['id' => 'block_dokumen_header', 'type' => 'dokumen_header', 'badge' => 'Database Dokumen Terpadu', 'title' => 'Pusat Database Dokumen & Arsip', 'bg_color' => '#0A192F', 'text_color' => '#ffffff', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_dokumen_table', 'type' => 'dokumen_table', 'badge' => 'Repositori Mutu', 'title' => 'Tabel Dokumen & Pencarian Pintar', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'knowledge') {
        $blocks = [
            ['id' => 'block_knowledge_pengantar', 'type' => 'knowledge_pengantar', 'badge' => 'Knowledge Management System', 'title' => 'Knowledge Center & Sumber Belajar Mutu', 'bg_color' => '#0A192F', 'text_color' => '#ffffff', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_kalender', 'type' => 'knowledge_kalender', 'badge' => 'Jadwal & Agenda', 'title' => 'Kalender Kegiatan SPMI & AMI', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_buletin', 'type' => 'knowledge_buletin', 'badge' => 'Publikasi Resmi', 'title' => 'Koleksi Buletin Mutu (JAMUS)', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_glosarium', 'type' => 'knowledge_glosarium', 'badge' => 'Kamus Terminologi', 'title' => 'Glosarium Penjaminan Mutu', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_knowledge_faq', 'type' => 'knowledge_faq', 'badge' => 'Pusat Bantuan', 'title' => 'Pertanyaan Sering Diajukan (FAQ)', 'bg_color' => '#F8F9FA', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'berita') {
        $blocks = [
            ['id' => 'block_berita_highlight', 'type' => 'berita_highlight', 'badge' => 'Berita Terkini & Kegiatan', 'title' => 'Publikasi Kegiatan & Berita Mutu', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_berita_grid', 'type' => 'berita_grid', 'badge' => 'Arsip Berita', 'title' => 'Daftar Artikel & Agenda Lengkap', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'layanan') {
        $blocks = [
            ['id' => 'block_layanan_cards', 'type' => 'layanan_cards', 'badge' => 'Layanan Mutu', 'title' => 'Katalog Layanan & Konsultasi SPMI', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_layanan_form', 'type' => 'layanan_form', 'badge' => 'Permohonan Digital', 'title' => 'Formulir Pengajuan Permohonan Layanan SPMI', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } elseif ($page_mode === 'kontak') {
        $blocks = [
            ['id' => 'block_kontak_info', 'type' => 'kontak_info', 'badge' => 'Sekretariat Lembaga', 'title' => 'Informasi Kontak & Lokasi Kantor LPM', 'bg_color' => '#ffffff', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true],
            ['id' => 'block_kontak_form', 'type' => 'kontak_form', 'badge' => 'Kritik & Saran', 'title' => 'Sampaikan Masukan & Aspirasi Mutu', 'bg_color' => '#F8FAFC', 'text_color' => '#0A192F', 'padding' => 'normal', 'is_visible' => true]
        ];
    } else {
        $blocks = [
            [
                'id'         => 'block_default_1',
                'type'       => 'hero',
                'badge'      => 'LPM UNIKA',
                'title'      => $page_title ?: 'Mewujudkan Budaya Mutu Berkelanjutan',
                'subtitle'   => 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berkomitmen mengawal standar mutu secara konsisten.',
                'btn_text'   => 'Pelajari Selengkapnya',
                'btn_link'   => '#konten',
                'bg_color'   => '#0A192F',
                'text_color' => '#FFFFFF',
                'align'      => 'center',
                'padding'    => 'large'
            ],
            [
                'id'         => 'block_default_2',
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
                'id'         => 'block_default_3',
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
                'id'         => 'block_default_4',
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

$theme_primary = getPengaturan('theme_primary_color', '#0A192F');
$theme_accent  = getPengaturan('theme_accent_color', '#6A1B9A');
$theme_gold    = getPengaturan('theme_gold_color', '#F59E0B');
$theme_bg      = getPengaturan('theme_bg_main', '#F8F9FA');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - <?= htmlspecialchars($page_title) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- LPM Design System -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        :root {
            --navy: <?= htmlspecialchars($theme_primary) ?>;
            --purple: <?= htmlspecialchars($theme_accent) ?>;
            --gold: <?= htmlspecialchars($theme_gold) ?>;
            --bg-main: <?= htmlspecialchars($theme_bg) ?>;
        }
        body {
            background-color: var(--bg-main);
            overflow-x: hidden;
            padding-bottom: 3rem;
        }
        .builder-block-wrap {
            position: relative;
            cursor: pointer;
            transition: outline 0.15s ease, box-shadow 0.15s ease;
        }
        .builder-block-wrap:hover {
            outline: 2px dashed rgba(30, 58, 138, 0.6) !important;
            outline-offset: -2px;
        }
        .builder-block-wrap.selected {
            outline: 2.5px solid #1E3A8A !important;
            outline-offset: -2.5px;
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.15);
        }
        .builder-block-tag {
            position: absolute;
            top: 8px;
            right: 14px;
            background: #1E3A8A;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            z-index: 100;
            display: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .builder-block-wrap:hover .builder-block-tag,
        .builder-block-wrap.selected .builder-block-tag {
            display: inline-block;
        }
        /* Penanda seksi draf di kanvas builder */
        .builder-block-wrap.is-draft {
            position: relative;
            outline: 2px dashed #F59E0B !important;
            outline-offset: -2px;
            opacity: 0.88;
        }
        .builder-block-draft-badge {
            position: absolute;
            top: 8px;
            left: 14px;
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #F59E0B;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 4px;
            z-index: 101;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }
    </style>
</head>
<body>

<div id="builderCanvasContainer">
    <?php if (empty($blocks)): ?>
        <div id="emptyCanvasNotice" class="text-center py-5">
            <div style="max-width:500px;margin:3rem auto;padding:2.5rem;background:#ffffff;border:1px dashed var(--border);border-radius:12px;">
                <i class="bi bi-layout-text-window-reverse text-muted" style="font-size:2.5rem;"></i>
                <h5 class="mt-3 mb-2" style="font-weight:700;color:var(--navy);">Kanvas Masih Kosong</h5>
                <p class="text-muted" style="font-size:0.88rem;">Tambahkan seksi pertama Anda melalui panel samping kiri atau pilih dari template seksi.</p>
            </div>
        </div>
    <?php else: ?>
        <div id="blocksRenderWrapper">
            <?php foreach ($blocks as $idx => $b): ?>
                <?php
                $btype = $b['type'] ?? 'rich_text';
                $btitle = $b['title'] ?? getBlockTypeName($btype);
                $bid = $b['id'] ?? ('block_' . $idx);
                $isDraft = (($b['status'] ?? '') === 'draft') || (isset($b['is_visible']) && !$b['is_visible']);
                ?>
                <div class="builder-block-wrap <?= $idx === 0 ? 'selected' : '' ?> <?= $isDraft ? 'is-draft' : '' ?>" id="wrap_<?= htmlspecialchars($bid) ?>" data-id="<?= htmlspecialchars($bid) ?>" data-type="<?= htmlspecialchars($btype) ?>" data-index="<?= $idx ?>" onclick="onBlockClicked(<?= $idx ?>)">
                    <?php if ($isDraft): ?>
                    <span class="builder-block-draft-badge"><i class="bi bi-eye-slash-fill"></i> DRAF (DISEMBUNYIKAN)</span>
                    <?php endif; ?>
                    <span class="builder-block-tag"><?= htmlspecialchars($btitle) ?></span>
                    <?php
                    $is_generic = in_array($btype, ['hero', 'text_image', 'cards_grid', 'cta', 'accordion', 'hyperlink', 'rich_text']);
                    if ($is_generic) {
                        echo renderPageBlocks([$b]);
                    } elseif (strpos($btype, 'beranda_') === 0 || $page_mode === 'beranda') {
                        renderBerandaSection($btype, $b, true);
                    } elseif (strpos($btype, 'profil_') === 0 || $page_mode === 'profil') {
                        renderProfilSection($btype, $b, true);
                    } elseif (strpos($btype, 'spmi_') === 0 || $page_mode === 'spmi') {
                        renderSpmiSection($btype, $b, true);
                    } elseif (strpos($btype, 'ami_') === 0 || $page_mode === 'ami') {
                        renderAmiSection($btype, $b, true);
                    } elseif (strpos($btype, 'akreditasi_') === 0 || $page_mode === 'akreditasi') {
                        renderAkreditasiSection($btype, $b, true);
                    } elseif (strpos($btype, 'mutu_data_') === 0 || $page_mode === 'mutu-data') {
                        renderMutuDataSection($btype, [], $b);
                    } elseif (strpos($btype, 'dokumen_') === 0 || $page_mode === 'dokumen') {
                        renderDokumenSection($btype, [], $b);
                    } elseif (strpos($btype, 'knowledge_') === 0 || $page_mode === 'knowledge') {
                        renderKnowledgeSection($btype, [], $b);
                    } elseif (strpos($btype, 'berita_') === 0 || $page_mode === 'berita') {
                        renderBeritaSection($btype, [], $b);
                    } elseif (strpos($btype, 'layanan_') === 0 || $page_mode === 'layanan') {
                        renderLayananSection($btype, [], $b);
                    } elseif (strpos($btype, 'kontak_') === 0 || $page_mode === 'kontak') {
                        renderKontakSection($btype, [], $b);
                    } else {
                        echo renderPageBlocks([$b]);
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
let currentBlocks = <?= json_encode($blocks) ?>;
let selectedIndex = 0;
let pageMode = '<?= $page_mode ?>';
let isModularMode = ['beranda', 'profil', 'spmi', 'ami', 'akreditasi', 'mutu-data', 'dokumen', 'knowledge', 'berita', 'layanan', 'kontak'].includes(pageMode);
const genericBlockTypes = ['hero', 'text_image', 'cards_grid', 'cta', 'accordion', 'hyperlink', 'rich_text'];

// Listen to messages from Parent Builder window
window.addEventListener('message', function(event) {
    const data = event.data;
    if (!data || !data.action) return;

    if (data.action === 'render') {
        currentBlocks = data.blocks || [];
        selectedIndex = data.selectedIndex !== undefined ? data.selectedIndex : -1;
        if (isModularMode) {
            reorderAndStyleModularBlocks();
        } else {
            renderBlocksLocally();
        }
    } else if (data.action === 'select') {
        selectedIndex = data.index;
        highlightSelectedBlock();
    }
});

// Helper cek status draft blok
function isBlockDraft(b) {
    if (!b) return false;
    if (b.status === 'draft') return true;
    if (b.is_visible === false) return true;
    return false;
}

// Reorder & update styles for modular page blocks without erasing real HTML
function reorderAndStyleModularBlocks() {
    let wrapper = document.getElementById('blocksRenderWrapper');
    const container = document.getElementById('builderCanvasContainer');

    if (!wrapper && container) {
        container.innerHTML = '<div id="blocksRenderWrapper"></div>';
        wrapper = document.getElementById('blocksRenderWrapper');
    }
    if (!wrapper) return;

    // Hapus blok yang sudah dihapus dari data
    const activeBlockIds = currentBlocks.map(b => b.id || '');
    Array.from(wrapper.children).forEach(child => {
        const cid = child.getAttribute('data-id');
        const ctype = child.getAttribute('data-type');
        if (genericBlockTypes.includes(ctype)) {
            if (!activeBlockIds.includes(cid)) {
                child.remove();
            }
        }
    });

    currentBlocks.forEach((b, idx) => {
        let wrap = document.getElementById('wrap_' + b.id) || 
                   wrapper.querySelector(`[data-id="${b.id}"]`) || 
                   wrapper.querySelector(`[data-type="${b.type}"]`);

        const isGeneric = genericBlockTypes.includes(b.type);
        const isDraft = isBlockDraft(b);

        if (!wrap && isGeneric) {
            // Blok generic baru ditambahkan secara dinamis
            wrap = document.createElement('div');
            wrap.className = 'builder-block-wrap' + (idx === selectedIndex ? ' selected' : '') + (isDraft ? ' is-draft' : '');
            wrap.id = 'wrap_' + (b.id || ('block_' + idx));
            wrap.setAttribute('data-id', b.id || ('block_' + idx));
            wrap.setAttribute('data-type', b.type);
            wrap.setAttribute('data-index', idx);
            wrap.onclick = function() { onBlockClicked(idx); };
            wrap.innerHTML = (isDraft ? '<span class="builder-block-draft-badge"><i class="bi bi-eye-slash-fill"></i> DRAF (DISEMBUNYIKAN)</span>' : '') +
                             `<span class="builder-block-tag">${getBlockTypeName(b.type)}</span>` + 
                             renderBlockInnerHtml(b, idx);
            wrapper.appendChild(wrap);
        } else if (wrap) {
            wrap.setAttribute('data-index', idx);
            wrap.onclick = function() { onBlockClicked(idx); };

            // Update status draft kelas & badge
            if (isDraft) {
                wrap.classList.add('is-draft');
                if (!wrap.querySelector('.builder-block-draft-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'builder-block-draft-badge';
                    badge.innerHTML = '<i class="bi bi-eye-slash-fill"></i> DRAF (DISEMBUNYIKAN)';
                    wrap.insertBefore(badge, wrap.firstChild);
                }
            } else {
                wrap.classList.remove('is-draft');
                const badge = wrap.querySelector('.builder-block-draft-badge');
                if (badge) badge.remove();
            }

            if (isGeneric) {
                // Selalu render ulang inner HTML agar perubahan warna, teks, link, susunan, tombol langsung realtime!
                wrap.innerHTML = (isDraft ? '<span class="builder-block-draft-badge"><i class="bi bi-eye-slash-fill"></i> DRAF (DISEMBUNYIKAN)</span>' : '') +
                                 `<span class="builder-block-tag">${getBlockTypeName(b.type)}</span>` + 
                                 renderBlockInnerHtml(b, idx);
            } else {
                // Di kanvas builder admin tetap bisa melihat & mengedit blok draf
                wrap.style.display = '';
                const sec = wrap.querySelector('section, .stats-bar, .page-banner');
                if (sec) {
                    if (b.bg_color) sec.style.setProperty('background-color', b.bg_color, 'important');
                    if (b.text_color) sec.style.setProperty('color', b.text_color, 'important');
                }
            }

            // Real-time DOM reordering
            wrapper.appendChild(wrap);
        }
    });

    highlightSelectedBlock();
}

function renderBlocksLocally() {
    const container = document.getElementById('builderCanvasContainer');
    if (!currentBlocks || currentBlocks.length === 0) {
        container.innerHTML = `
            <div id="emptyCanvasNotice" class="text-center py-5">
                <div style="max-width:500px;margin:3rem auto;padding:2.5rem;background:#ffffff;border:1px dashed #cbd5e1;border-radius:12px;">
                    <i class="bi bi-layout-text-window-reverse text-muted" style="font-size:2.5rem;"></i>
                    <h5 class="mt-3 mb-2" style="font-weight:700;color:var(--navy);">Kanvas Masih Kosong</h5>
                    <p class="text-muted" style="font-size:0.88rem;">Tambahkan seksi pertama Anda melalui panel samping kiri atau pilih dari template seksi.</p>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    currentBlocks.forEach((b, index) => {
        const type = b.type || 'rich_text';
        const isSel = (index === selectedIndex) ? 'selected' : '';
        const bid = b.id || ('block_' + index);
        const isDraft = isBlockDraft(b);

        html += `<div class="builder-block-wrap ${isSel} ${isDraft ? 'is-draft' : ''}" id="wrap_${bid}" data-id="${bid}" data-type="${type}" data-index="${index}" onclick="onBlockClicked(${index})">`;
        if (isDraft) {
            html += `<span class="builder-block-draft-badge"><i class="bi bi-eye-slash-fill"></i> DRAF (DISEMBUNYIKAN)</span>`;
        }
        html += `<span class="builder-block-tag">${getBlockTypeName(type)}</span>`;
        html += renderBlockInnerHtml(b, index);
        html += `</div>`;
    });

    container.innerHTML = html;
}

function renderBlockInnerHtml(b, index) {
    const type = b.type || 'rich_text';
    const bg = b.bg_color ? `background:${b.bg_color};` : '';
    const tc = b.text_color ? `color:${b.text_color};` : '';
    const pad = (b.padding === 'large') ? 'py-5 py-md-6' : ((b.padding === 'small') ? 'py-3 py-md-4' : 'py-4 py-md-5');
    let html = '';

        if (type === 'hero') {
            const align = (b.align === 'left') ? 'text-start' : 'text-center';
            html += `
            <section class="builder-section builder-hero ${pad} ${align}" style="${bg} ${tc}">
                <div class="container position-relative">
                    ${b.badge ? `<div class="hero-badge mb-3"><span class="hero-badge-dot"></span>${escapeHtml(b.badge)}</div>` : ''}
                    ${b.title ? `<h1 class="page-banner-title mb-3" style="${tc}">${escapeHtml(b.title)}</h1>` : ''}
                    ${b.subtitle ? `<p class="lead mb-4 mx-auto" style="max-width:750px;opacity:0.9;${tc}">${escapeHtml(b.subtitle).replace(/\n/g, '<br>')}</p>` : ''}
                    ${(b.btn_text || b.btn_secondary_text) ? `
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        ${b.btn_text ? `<a href="#" class="btn-hero-primary" onclick="return false;">${escapeHtml(b.btn_text)}</a>` : ''}
                        ${b.btn_secondary_text ? `<a href="#" class="btn-hero-secondary" onclick="return false;">${escapeHtml(b.btn_secondary_text)}</a>` : ''}
                    </div>` : ''}
                </div>
            </section>`;
        } else if (type === 'text_image') {
            const pos = b.image_pos || 'right';
            const colText = `
                <div class="col-lg-7">
                    ${b.tag ? `<span class="section-tag mb-2">${escapeHtml(b.tag)}</span>` : ''}
                    ${b.title ? `<h2 class="section-title mb-3" style="${tc}">${escapeHtml(b.title)}</h2>` : ''}
                    ${b.content ? `<div style="line-height:1.8;${tc}">${b.content}</div>` : ''}
                    ${b.btn_text ? `<div class="mt-4"><a href="#" class="btn-hero-primary" onclick="return false;">${escapeHtml(b.btn_text)}</a></div>` : ''}
                </div>`;
            const imgSrc = b.image ? (b.image.startsWith('http') || b.image.startsWith('/') ? b.image : '<?= UPLOAD_URL ?>' + b.image) : 'https://placehold.co/600x400/0A192F/ffffff?text=LPM+UNIKA';
            const colImg = `
                <div class="col-lg-5 text-center">
                    <img src="${imgSrc}" alt="Gambar" class="img-fluid rounded-4 shadow-sm" style="max-height:420px;width:100%;object-fit:cover;">
                </div>`;

            html += `
            <section class="builder-section builder-text-image ${pad}" style="${bg} ${tc}">
                <div class="container">
                    <div class="row g-5 align-items-center">
                        ${pos === 'left' ? colImg + colText : colText + colImg}
                    </div>
                </div>
            </section>`;
        } else if (type === 'cards_grid') {
            const cols = parseInt(b.columns || 3);
            const colCls = cols === 4 ? 'col-lg-3 col-md-6' : (cols === 2 ? 'col-md-6' : 'col-lg-4 col-md-6');
            let cardsHtml = '';
            (b.cards || []).forEach(c => {
                cardsHtml += `
                <div class="${colCls}">
                    <div class="card-lpm h-100 p-4" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);">
                        ${c.icon ? `<div style="width:48px;height:48px;border-radius:12px;background:rgba(106,27,154,0.1);display:flex;align-items:center;justify-content:center;color:var(--purple);font-size:1.4rem;margin-bottom:1.25rem;"><i class="bi ${escapeHtml(c.icon)}"></i></div>` : ''}
                        ${c.badge ? `<span class="badge bg-light text-primary mb-2" style="font-size:0.75rem;">${escapeHtml(c.badge)}</span>` : ''}
                        ${c.title ? `<h5 style="font-weight:700;color:var(--navy);margin-bottom:0.5rem;">${escapeHtml(c.title)}</h5>` : ''}
                        ${c.desc ? `<p style="color:var(--text-muted);font-size:0.9rem;line-height:1.6;margin:0;">${escapeHtml(c.desc).replace(/\n/g, '<br>')}</p>` : ''}
                    </div>
                </div>`;
            });

            html += `
            <section class="builder-section builder-cards ${pad}" style="${bg} ${tc}">
                <div class="container">
                    ${(b.title || b.tag) ? `
                    <div class="text-center mb-5" style="max-width:700px;margin:0 auto;">
                        ${b.tag ? `<span class="section-tag mb-2">${escapeHtml(b.tag)}</span>` : ''}
                        ${b.title ? `<h2 class="section-title mb-2" style="${tc}">${escapeHtml(b.title)}</h2>` : ''}
                        ${b.subtitle ? `<p class="text-muted">${escapeHtml(b.subtitle)}</p>` : ''}
                    </div>` : ''}
                    <div class="row g-4">
                        ${cardsHtml}
                    </div>
                </div>
            </section>`;
        } else if (type === 'cta') {
            html += `
            <section class="builder-section builder-cta ${pad} text-center" style="${bg} ${tc}">
                <div class="container" style="max-width:850px;">
                    ${b.title ? `<h2 class="mb-3" style="font-weight:800;color:${b.text_color || '#ffffff'};">${escapeHtml(b.title)}</h2>` : ''}
                    ${b.subtitle ? `<p class="lead mb-4" style="opacity:0.9;color:${b.text_color || '#ffffff'};">${escapeHtml(b.subtitle)}</p>` : ''}
                    ${b.btn_text ? `<a href="#" class="btn-hero-primary" onclick="return false;" style="background:#ffffff;color:var(--navy);font-weight:700;padding:0.75rem 2rem;">${escapeHtml(b.btn_text)}</a>` : ''}
                </div>
            </section>`;
        } else if (type === 'accordion') {
            const accId = 'builder_acc_' + index;
            let itemsHtml = '';
            (b.items || []).forEach((it, qidx) => {
                const itemId = accId + '_item_' + qidx;
                itemsHtml += `
                <div class="accordion-item mb-2" style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${qidx === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${itemId}" style="font-weight:600;font-size:0.95rem;">
                            ${escapeHtml(it.question || 'Pertanyaan')}
                        </button>
                    </h2>
                    <div id="${itemId}" class="accordion-collapse collapse ${qidx === 0 ? 'show' : ''}" data-bs-parent="#${accId}">
                        <div class="accordion-body" style="font-size:0.92rem;line-height:1.7;color:var(--text-muted);">
                            ${escapeHtml(it.answer || '').replace(/\n/g, '<br>')}
                        </div>
                    </div>
                </div>`;
            });

            html += `
            <section class="builder-section builder-faq ${pad}" style="${bg} ${tc}">
                <div class="container" style="max-width:800px;">
                    ${b.title ? `
                    <div class="text-center mb-4">
                        ${b.tag ? `<span class="section-tag mb-2">${escapeHtml(b.tag)}</span>` : ''}
                        <h2 class="section-title mb-2" style="${tc}">${escapeHtml(b.title)}</h2>
                    </div>` : ''}
                    <div class="accordion" id="${accId}">
                        ${itemsHtml}
                    </div>
                </div>
            </section>`;
        } else if (type === 'hyperlink') {
            const layout = b.layout || 'grid-3';
            const shape = b.btn_shape || 'rounded';
            const styleMode = b.btn_style || 'solid';
            const btnSize = b.btn_size || 'normal';
            const btnBg = b.btn_bg_color || '#1E3A8A';
            const btnTc = b.btn_text_color || '#FFFFFF';
            const radius = (shape === 'pill') ? '50px' : ((shape === 'square') ? '2px' : '10px');
            const padBtn = (btnSize === 'large') ? '0.9rem 1.75rem' : ((btnSize === 'small') ? '0.4rem 0.9rem' : '0.65rem 1.25rem');
            const fsBtn = (btnSize === 'large') ? '1.05rem' : ((btnSize === 'small') ? '0.82rem' : '0.92rem');
            const links = Array.isArray(b.links) ? b.links : [];

            let linksHtml = '';
            links.forEach(l => {
                const lTitle = escapeHtml(l.title || 'Tautan');
                const lUrl = escapeHtml(l.url || '#');
                const lDesc = l.desc ? escapeHtml(l.desc) : '';
                const lIcon = escapeHtml(l.icon || 'bi-link-45deg');
                const isBlank = (l.target !== '_self');

                let itemInner = '';
                if (styleMode === 'card') {
                    itemInner = `
                    <a href="${lUrl}" target="${isBlank ? '_blank' : '_self'}" class="builder-link-card" style="display:flex;align-items:center;gap:14px;padding:1.1rem 1.35rem;border-radius:${radius};background:#ffffff;border:1.5px solid #E2E8F0;text-decoration:none;transition:all 0.25s ease;box-shadow:0 2px 10px rgba(0,0,0,0.03);">
                        <div style="width:44px;height:44px;border-radius:${shape === 'pill' ? '50%' : '10px'};background:${btnBg};color:${btnTc};display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
                            <i class="bi ${lIcon}"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;color:var(--navy);font-size:${fsBtn};line-height:1.3;" class="text-truncate">${lTitle}</div>
                            ${lDesc ? `<div style="font-size:0.78rem;color:#64748B;line-height:1.4;margin-top:2px;" class="text-truncate">${lDesc}</div>` : ''}
                        </div>
                        <i class="bi bi-arrow-right text-muted" style="font-size:1.1rem;flex-shrink:0;"></i>
                    </a>`;
                } else if (styleMode === 'outline') {
                    itemInner = `<a href="${lUrl}" target="${isBlank ? '_blank' : '_self'}" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:${padBtn};border-radius:${radius};font-size:${fsBtn};font-weight:700;text-decoration:none;transition:all 0.25s ease;border:2px solid ${btnBg};color:${btnBg};background:transparent;"><i class="bi ${lIcon}"></i><span>${lTitle}</span>${isBlank ? '<i class="bi bi-box-arrow-up-right" style="font-size:0.75em;opacity:0.7;"></i>' : ''}</a>`;
                } else if (styleMode === 'soft') {
                    itemInner = `<a href="${lUrl}" target="${isBlank ? '_blank' : '_self'}" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:${padBtn};border-radius:${radius};font-size:${fsBtn};font-weight:700;text-decoration:none;transition:all 0.25s ease;background:rgba(30,58,138,0.08);color:${btnBg};border:1px solid rgba(30,58,138,0.15);"><i class="bi ${lIcon}"></i><span>${lTitle}</span>${isBlank ? '<i class="bi bi-box-arrow-up-right" style="font-size:0.75em;opacity:0.7;"></i>' : ''}</a>`;
                } else {
                    itemInner = `<a href="${lUrl}" target="${isBlank ? '_blank' : '_self'}" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:${padBtn};border-radius:${radius};font-size:${fsBtn};font-weight:700;text-decoration:none;transition:all 0.25s ease;background:${btnBg};color:${btnTc};border:none;box-shadow:0 3px 10px rgba(0,0,0,0.08);"><i class="bi ${lIcon}"></i><span>${lTitle}</span>${isBlank ? '<i class="bi bi-box-arrow-up-right" style="font-size:0.75em;opacity:0.8;"></i>' : ''}</a>`;
                }

                if (layout === 'flex-wrap' || layout === 'list') {
                    linksHtml += itemInner;
                } else {
                    const colsNum = (layout === 'grid-4') ? 4 : ((layout === 'grid-2') ? 2 : 3);
                    const colCls = (colsNum === 4) ? 'col-lg-3 col-md-6' : ((colsNum === 2) ? 'col-md-6' : 'col-lg-4 col-md-6');
                    linksHtml += `<div class="${colCls}">${styleMode !== 'card' ? `<div class="w-100">${itemInner.replace('display:inline-flex', 'display:flex;width:100%')}</div>` : itemInner}</div>`;
                }
            });

            html += `
            <section class="builder-section builder-hyperlinks ${pad}" style="${bg} ${tc}">
                <div class="container">
                    ${(b.title || b.badge) ? `
                    <div class="text-center mb-4" style="max-width:750px;margin:0 auto;">
                        ${b.badge ? `<span class="section-tag mb-2">${escapeHtml(b.badge)}</span>` : ''}
                        ${b.title ? `<h2 class="section-title mb-2" style="${tc}">${escapeHtml(b.title)}</h2>` : ''}
                        ${b.subtitle ? `<p class="text-muted">${escapeHtml(b.subtitle).replace(/\n/g, '<br>')}</p>` : ''}
                    </div>` : ''}
                    ${layout === 'flex-wrap' ? `<div class="d-flex flex-wrap justify-content-center align-items-center gap-3">${linksHtml}</div>` : 
                      (layout === 'list' ? `<div class="vstack gap-3 mx-auto" style="max-width:720px;">${linksHtml}</div>` : 
                      `<div class="row g-3 justify-content-center">${linksHtml}</div>`)}
                </div>
            </section>`;
        } else {
            // Rich Text
            html += `
            <section class="builder-section builder-rich ${pad}" style="${bg} ${tc}">
                <div class="container">
                    <div class="page-rendered-content" style="${tc};line-height:1.8;">
                        ${b.content || '<p class="text-muted">Klik untuk mengedit teks konten seksi ini.</p>'}
                    </div>
                </div>
            </section>`;
        }
    return html;
}

function onBlockClicked(index) {
    selectedIndex = index;
    highlightSelectedBlock();
    // Notify parent window to open this section in the sidebar editor
    window.parent.postMessage({ action: 'sectionSelected', index: index }, '*');
}

function highlightSelectedBlock() {
    document.querySelectorAll('.builder-block-wrap').forEach((el, idx) => {
        if (idx === selectedIndex) {
            el.classList.add('selected');
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            el.classList.remove('selected');
        }
    });
}

function getBlockTypeName(type) {
    const map = {
        'beranda_hero': 'Hero Slider Utama',
        'beranda_quick_links': 'Tautan Cepat (Quick Links)',
        'beranda_stats': 'Statistik Capaian (Stats Bar)',
        'beranda_penghargaan': 'Penghargaan & Rekognisi',
        'beranda_akreditasi': 'Akreditasi Institusi Unggul',
        'beranda_berita': 'Berita & Pengumuman',
        'beranda_cta': 'Call to Action (CTA Banner)',
        'hero': 'Hero Banner',
        'text_image': 'Teks & Media',
        'cards_grid': 'Grid Kartu',
        'hyperlink': 'Tautan Hyperlink & Tombol',
        'cta': 'Call to Action',
        'accordion': 'Accordion FAQ',
        'rich_text': 'Konten Bebas'
    };
    return map[type] || 'Seksi';
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

document.addEventListener('DOMContentLoaded', function() {
    if (!isBerandaMode && (!document.getElementById('blocksRenderWrapper') || document.getElementById('blocksRenderWrapper').children.length === 0)) {
        renderBlocksLocally();
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
