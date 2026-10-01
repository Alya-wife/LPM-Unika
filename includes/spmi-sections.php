<?php
/**
 * Modular SPMI Sections Renderer
 * Digunakan bersama oleh spmi.php (halaman publik) dan admin/builder-preview.php (visual builder)
 */
require_once __DIR__ . '/../config/database.php';

function getSpmiData($kategori_filter = '') {
    static $cache = [];
    $cache_key = 'kat_' . $kategori_filter;
    if (isset($cache[$cache_key])) return $cache[$cache_key];

    $db = getDB();
    $data = [];

    $data['spmi_intro_text'] = getPengaturan('spmi_intro_text', 'Sistem Penjaminan Mutu Internal (SPMI) Universitas Katolik Soegijapranata merupakan kegiatan sistemik penjaminan mutu pendidikan tinggi yang dilaksanakan secara mandiri oleh universitas untuk mengendalikan dan meningkatkan penyelenggaraan pendidikan tinggi secara berencana dan berkelanjutan.');

    // Dokumen Mutu
    $categories_dokumen_table = [];
    try {
        $categories_dokumen_table = $db->query("SELECT nama_kategori FROM kategori_dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
    $categories_in_db = $db->query("SELECT DISTINCT kategori FROM dokumen")->fetchAll(PDO::FETCH_COLUMN);
    $data['allowed_kategori'] = array_values(array_unique(array_filter(array_merge($categories_dokumen_table, $categories_in_db))));

    if ($kategori_filter && in_array($kategori_filter, $data['allowed_kategori'])) {
        $stmt = $db->prepare("SELECT * FROM dokumen WHERE kategori = ? ORDER BY created_at DESC");
        $stmt->execute([$kategori_filter]);
    } else {
        $stmt = $db->query("SELECT * FROM dokumen ORDER BY kategori, created_at DESC");
    }
    $dokumen_all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data['dokumen_all'] = $dokumen_all;

    $dokumen_grouped = [];
    foreach ($dokumen_all as $d) {
        $dokumen_grouped[$d['kategori']][] = $d;
    }
    $data['dokumen_grouped'] = $dokumen_grouped;

    // Hasil SPMI Kemendikti Saintek
    try {
        $data['list_spmi_kemendikti'] = $db->query("SELECT * FROM spmi_kemendikti WHERE is_published = 1 ORDER BY tahun DESC, urutan ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['list_spmi_kemendikti'] = [];
    }

    $cache[$cache_key] = $data;
    return $data;
}

function renderSpmiSection($type, $block = [], $is_builder = false, $kategori_filter = '') {
    $data = getSpmiData($kategori_filter);
    $bg   = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc   = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    switch ($type) {
        case 'spmi_pengantar':
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="max-w-950 mx-auto">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Pengantar Mutu') ?>
                        </span>
                        <h2 class="section-title mb-3"><?= htmlspecialchars($block['title'] ?? 'Mengenal SPMI di UNIKA') ?></h2>
                        <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                            <?= nl2br(htmlspecialchars($block['subtitle'] ?? $data['spmi_intro_text'])) ?>
                        </p>
                        <p style="color:var(--text-muted);line-height:1.75;font-size:0.95rem;margin-bottom:1.75rem;">
                            SPMI UNIKA dirancang berlandaskan nilai-nilai Kristiani dan semangat Santo Soegijapranata <em>(Talenta Pro Patria et Humanitate)</em> untuk memastikan lulusan memiliki integritas moral, keunggulan akademik, serta kepekaan sosial yang tinggi.
                        </p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--purple);">
                                    <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Tujuan Pokok SPMI</div>
                                    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.5;">Memelihara dan meningkatkan mutu pendidikan tinggi secara berkelanjutan <em>(continuous improvement)</em>.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid #1565C0;">
                                    <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Landasan Regulasi</div>
                                    <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.5;">UU No. 12/2012 tentang Pendidikan Tinggi dan Permendikbudristek No. 53/2023.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'spmi_ppepp':
            $ppepp_data = [
                [
                    'inisial'   => 'P',
                    'label'     => 'Penetapan',
                    'judul'     => 'Penetapan Standar Dikti (P)',
                    'tagline'   => 'Penetapan Standar Dikti;',
                    'warna'     => '#C0392B',
                    'warna_sub' => '#E74C3C',
                    'bg_light'  => 'rgba(192, 57, 43, 0.08)',
                    'deskripsi' => 'Tahap awal perumusan, penyelarasan, dan penetapan seluruh tolok ukur standar mutu akademik, non-akademik, serta standar ciri khas Universitas Katolik Soegijapranata yang disusun melampaui Standar Nasional Pendidikan Tinggi (SN Dikti).',
                    'langkah'   => [
                        'Penyusunan draf standar mutu bersama tim pakar LPM dan UPPS.',
                        'Uji publik dan review komprehensif bersama Gugus Penjaminan Mutu (GPM).',
                        'Pemberian pertimbangan dan persetujuan formal oleh Senat Akademik Universitas.',
                        'Penetapan resmi pemberlakuan standar melalui Surat Keputusan Rektor.'
                    ],
                    'dokumen'   => 'Kebijakan SPMI, Manual Penetapan Standar, dan Buku Standar SPMI (Pendidikan, Riset, PkM, & Identitas).',
                    'aktor'     => 'Rektor, Senat Akademik Universitas, & LPM'
                ],
                [
                    'inisial'   => 'P',
                    'label'     => 'Pelaksanaan',
                    'judul'     => 'Pelaksanaan Standar Dikti (P)',
                    'tagline'   => 'Pelaksanaan Standar Dikti;',
                    'warna'     => '#689F38',
                    'warna_sub' => '#7CB342',
                    'bg_light'  => 'rgba(104, 159, 56, 0.08)',
                    'deskripsi' => 'Tahap implementasi seluruh indikator capaian standar yang telah disahkan ke dalam operasional tridharma perguruan tinggi dan tata kelola unit kerja di lingkungan kampus UNIKA.',
                    'langkah'   => [
                        'Sosialisasi menyeluruh isi standar mutu kepada dosen, tendik, dan mahasiswa.',
                        'Penerapan SOP operasional dalam perkuliahan, bimbingan, penelitian, dan pengabdian.',
                        'Penyusunan dan pengarsipan portofolio pembelajaran serta bukti fisik ketercapaian standar.',
                        'Pencatatan real-time melalui sistem informasi akademik dan portal terpadu kampus.'
                    ],
                    'dokumen'   => 'SOP Pelaksanaan, Modul Perkuliahan, Rencana Pembelajaran (RPS), Kontrak Kinerja Unit, dan Bukti Tridharma.',
                    'aktor'     => 'Dekan, Ketua Program Studi, Kepala Lembaga/Biro, Dosen, & Tendik'
                ],
                [
                    'inisial'   => 'E',
                    'label'     => 'Evaluasi',
                    'judul'     => 'Evaluasi (Pelaksanaan) Standar Dikti (E)',
                    'tagline'   => 'Evaluasi (Pelaksanaan) Standar Dikti;',
                    'warna'     => '#6A4C93',
                    'warna_sub' => '#8E44AD',
                    'bg_light'  => 'rgba(106, 76, 147, 0.08)',
                    'deskripsi' => 'Tahap pemantauan, pengukuran, dan pengujian berkala terhadap kesesuaian pelaksanaan kegiatan dengan tolok ukur standar mutu, guna mendeteksi secara dini potensi hambatan maupun ketidaksesuaian.',
                    'langkah'   => [
                        'Pengisian Dokumen Evaluasi Diri (DED) berbasis data riil oleh UPPS/Unit.',
                        'Pelaksanaan Audit Mutu Internal (AMI) terjadwal oleh auditor internal bersertifikat.',
                        'Visitasi lapangan, wawancara auditee, dan uji petik bukti dukung fisik/digital.',
                        'Penyusunan Laporan Hasil Audit Mutu, rekapitulasi temuan KTS, dan potensi risiko.'
                    ],
                    'dokumen'   => 'Instrumen Audit Mutu Internal (AMI), Dokumen Evaluasi Diri (DED), Laporan Temuan KTS, dan Survei Kepuasan.',
                    'aktor'     => 'Kepala Pusat AMI, Tim Auditor Internal Tersertifikasi, & Auditee'
                ],
                [
                    'inisial'   => 'P',
                    'label'     => 'Pengendalian',
                    'judul'     => 'Pengendalian (Pelaksanaan) Standar Dikti (P)',
                    'tagline'   => 'Pengendalian (Pelaksanaan) Standar Dikti; dan',
                    'warna'     => '#00838F',
                    'warna_sub' => '#00ACC1',
                    'bg_light'  => 'rgba(0, 131, 143, 0.08)',
                    'deskripsi' => 'Tahap analisis dan perumusan tindakan korektif terhadap temuan evaluasi melalui forum resmi Rapat Tinjauan Manajemen (RTM) agar penyimpangan segera teratasi dan tidak berulang.',
                    'langkah'   => [
                        'Penyusunan Rencana Tindak Lanjut (RTL) dan tenggat waktu perbaikan oleh auditee.',
                        'Penyelenggaraan Rapat Tinjauan Manajemen (RTM) berjenjang (Prodi, Fakultas, Universitas).',
                        'Keputusan pimpinan terkait alokasi sumber daya pendukung percepatan pemenuhan standar.',
                        'Verifikasi dan monitoring penutupan status temuan KTS oleh GPM dan LPM.'
                    ],
                    'dokumen'   => 'Risalah RTM, Lembar Rencana Tindak Lanjut (RTL), Bukti Perbaikan Tindak Lanjut, dan Berita Acara RTM.',
                    'aktor'     => 'Rektor, Wakil Rektor, Dekan Fakultas, Kepala Unit Kerja, & GPM'
                ],
                [
                    'inisial'   => 'P',
                    'label'     => 'Peningkatan',
                    'judul'     => 'Peningkatan Standar Dikti (P)',
                    'tagline'   => 'Peningkatan Standar Dikti.',
                    'warna'     => '#E65100',
                    'warna_sub' => '#F57C00',
                    'bg_light'  => 'rgba(230, 81, 0, 0.08)',
                    'deskripsi' => 'Tahap menaikkan target atau memperluas kriteria standar mutu yang telah tercapai secara konsisten (kaizen berkelanjutan) agar kualitas institusi terus meningkat menuju standar internasional.',
                    'langkah'   => [
                        'Kajian kelayakan terhadap standar yang telah berhasil dipenuhi secara penuh dan konsisten.',
                        'Benchmarking mutu ke institusi mitra terbaik di tingkat nasional dan internasional.',
                        'Revisi rumusan indikator capaian standar menjadi lebih tinggi dan adaptif masa depan.',
                        'Pengesahan standar edisi terbaru sebagai titik awal siklus Penetapan berikutnya.'
                    ],
                    'dokumen'   => 'Naskah Rekomendasi Peningkatan Mutu, Laporan Studi Banding/Benchmarking, dan Draf Revisi Standar Baru.',
                    'aktor'     => 'Rektor, Senat Akademik Universitas, Dewan Pakar Mutu, & LPM'
                ]
            ];
            ?>
            <section class="py-5 py-md-6" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>" id="siklus-ppepp">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Siklus Penjaminan Mutu Berkelanjutan') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Siklus PPEPP SPMI Interaktif') ?></h2>
                        <p class="section-desc max-w-800 mx-auto" style="font-size:0.95rem;line-height:1.75;">
                            Implementasi penjaminan mutu di Universitas Katolik Soegijapranata berlandaskan pada 5 tahap siklus berkelanjutan (PPEPP). Klik salah satu lingkaran siklus di bawah ini untuk menelaah penjelasan rinci setiap tahapannya.
                        </p>
                    </div>

                    <!-- Interactive PPEPP Container (Balanced 50:50 Columns) -->
                    <div class="row align-items-center justify-content-center g-4 g-lg-5 mb-5">
                        <!-- Left: Circular Cycle Diagram (Large & Prominent) -->
                        <div class="col-lg-6 col-xl-6 text-center">
                            <div class="ppepp-cycle-stage position-relative d-inline-block" style="width:100%;max-width:510px;">
                                <svg viewBox="0 0 460 460" width="100%" height="auto" class="ppepp-cycle-svg" style="overflow:visible;filter:drop-shadow(0 10px 25px rgba(10,25,47,0.06));outline:none;outline-width:0;">
                                    <defs>
                                        <!-- Node Drop Shadows -->
                                        <filter id="ppeppShadow" x="-20%" y="-20%" width="150%" height="150%">
                                            <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#0A192F" flood-opacity="0.18" />
                                        </filter>
                                        <filter id="ppeppActiveGlow" x="-30%" y="-30%" width="160%" height="160%">
                                            <feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="#7B1FA2" flood-opacity="0.45" />
                                        </filter>

                                        <!-- Directional Arrowhead Markers -->
                                        <marker id="arrow-red" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#C0392B" />
                                        </marker>
                                        <marker id="arrow-green" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#689F38" />
                                        </marker>
                                        <marker id="arrow-purple" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#6A4C93" />
                                        </marker>
                                        <marker id="arrow-teal" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#00838F" />
                                        </marker>
                                        <marker id="arrow-orange" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                            <path d="M 0 1 L 10 5 L 0 9 z" fill="#E65100" />
                                        </marker>

                                        <!-- Node Color Gradients -->
                                        <linearGradient id="grad-p1" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#D9534F" />
                                            <stop offset="100%" stop-color="#C0392B" />
                                        </linearGradient>
                                        <linearGradient id="grad-p2" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#8BC34A" />
                                            <stop offset="100%" stop-color="#689F38" />
                                        </linearGradient>
                                        <linearGradient id="grad-e3" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#8E44AD" />
                                            <stop offset="100%" stop-color="#6A4C93" />
                                        </linearGradient>
                                        <linearGradient id="grad-p4" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#00ACC1" />
                                            <stop offset="100%" stop-color="#00838F" />
                                        </linearGradient>
                                        <linearGradient id="grad-p5" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#FB8C00" />
                                            <stop offset="100%" stop-color="#E65100" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Connecting Directional Circular Flow Arrows -->
                                    <path d="M 282 108 A 148 148 0 0 1 350 148" fill="none" stroke="#C0392B" stroke-width="4.5" stroke-linecap="round" marker-end="url(#arrow-red)" />
                                    <path d="M 374 235 A 148 148 0 0 1 346 304" fill="none" stroke="#689F38" stroke-width="4.5" stroke-linecap="round" marker-end="url(#arrow-green)" />
                                    <path d="M 284 374 A 148 148 0 0 1 190 374" fill="none" stroke="#6A4C93" stroke-width="4.5" stroke-linecap="round" marker-end="url(#arrow-purple)" />
                                    <path d="M 124 316 A 148 148 0 0 1 92 235" fill="none" stroke="#00838F" stroke-width="4.5" stroke-linecap="round" marker-end="url(#arrow-teal)" />
                                    <path d="M 118 145 A 148 148 0 0 1 182 106" fill="none" stroke="#E65100" stroke-width="4.5" stroke-linecap="round" marker-end="url(#arrow-orange)" />

                                    <!-- Center Emblem / Logo Watermark -->
                                    <circle cx="230" cy="230" r="44" fill="#F8FAFC" stroke="var(--border)" stroke-width="1.5" />
                                    <text x="230" y="226" text-anchor="middle" font-family="var(--font-heading)" font-size="11" font-weight="800" fill="var(--navy)" letter-spacing="1">SIKLUS</text>
                                    <text x="230" y="242" text-anchor="middle" font-family="var(--font-heading)" font-size="13" font-weight="900" fill="#7B1FA2" letter-spacing="1.5">PPEPP</text>

                                    <!-- Node 1: Penetapan (P) - Top Center (x:230, y:88) -->
                                    <g class="ppepp-interactive-node active" id="ppepp-node-0" onclick="selectPpeppStage(0)" style="cursor:pointer;" aria-label="Tahap 1: Penetapan">
                                        <circle cx="230" cy="88" r="48" class="node-ring" fill="none" stroke="#C0392B" stroke-width="3" stroke-dasharray="6,4" opacity="1" />
                                        <circle cx="230" cy="88" r="44" fill="url(#grad-p1)" filter="url(#ppeppShadow)" class="node-circle" />
                                        <text x="230" y="86" text-anchor="middle" font-family="var(--font-heading)" font-size="28" font-weight="900" fill="#ffffff">P</text>
                                        <text x="230" y="106" text-anchor="middle" font-family="var(--font-heading)" font-size="10" font-weight="800" fill="#ffffff" letter-spacing="0.5">Penetapan</text>
                                    </g>

                                    <!-- Node 2: Pelaksanaan (P) - Top Right (x:364, y:186) -->
                                    <g class="ppepp-interactive-node" id="ppepp-node-1" onclick="selectPpeppStage(1)" style="cursor:pointer;" aria-label="Tahap 2: Pelaksanaan">
                                        <circle cx="364" cy="186" r="48" class="node-ring" fill="none" stroke="#689F38" stroke-width="3" stroke-dasharray="6,4" opacity="0" />
                                        <circle cx="364" cy="186" r="44" fill="url(#grad-p2)" filter="url(#ppeppShadow)" class="node-circle" />
                                        <text x="364" y="184" text-anchor="middle" font-family="var(--font-heading)" font-size="28" font-weight="900" fill="#ffffff">P</text>
                                        <text x="364" y="204" text-anchor="middle" font-family="var(--font-heading)" font-size="9.5" font-weight="800" fill="#ffffff" letter-spacing="0.5">Pelaksanaan</text>
                                    </g>

                                    <!-- Node 3: Evaluasi (E) - Bottom Right (x:312, y:344) -->
                                    <g class="ppepp-interactive-node" id="ppepp-node-2" onclick="selectPpeppStage(2)" style="cursor:pointer;" aria-label="Tahap 3: Evaluasi">
                                        <circle cx="312" cy="344" r="48" class="node-ring" fill="none" stroke="#6A4C93" stroke-width="3" stroke-dasharray="6,4" opacity="0" />
                                        <circle cx="312" cy="344" r="44" fill="url(#grad-e3)" filter="url(#ppeppShadow)" class="node-circle" />
                                        <text x="312" y="342" text-anchor="middle" font-family="var(--font-heading)" font-size="28" font-weight="900" fill="#ffffff">E</text>
                                        <text x="312" y="362" text-anchor="middle" font-family="var(--font-heading)" font-size="10" font-weight="800" fill="#ffffff" letter-spacing="0.5">Evaluasi</text>
                                    </g>

                                    <!-- Node 4: Pengendalian (P) - Bottom Left (x:148, y:344) -->
                                    <g class="ppepp-interactive-node" id="ppepp-node-3" onclick="selectPpeppStage(3)" style="cursor:pointer;" aria-label="Tahap 4: Pengendalian">
                                        <circle cx="148" cy="344" r="48" class="node-ring" fill="none" stroke="#00838F" stroke-width="3" stroke-dasharray="6,4" opacity="0" />
                                        <circle cx="148" cy="344" r="44" fill="url(#grad-p4)" filter="url(#ppeppShadow)" class="node-circle" />
                                        <text x="148" y="342" text-anchor="middle" font-family="var(--font-heading)" font-size="28" font-weight="900" fill="#ffffff">P</text>
                                        <text x="148" y="362" text-anchor="middle" font-family="var(--font-heading)" font-size="9" font-weight="800" fill="#ffffff" letter-spacing="0.5">Pengendalian</text>
                                    </g>

                                    <!-- Node 5: Peningkatan (P) - Top Left (x:96, y:186) -->
                                    <g class="ppepp-interactive-node" id="ppepp-node-4" onclick="selectPpeppStage(4)" style="cursor:pointer;" aria-label="Tahap 5: Peningkatan">
                                        <circle cx="96" cy="186" r="48" class="node-ring" fill="none" stroke="#E65100" stroke-width="3" stroke-dasharray="6,4" opacity="0" />
                                        <circle cx="96" cy="186" r="44" fill="url(#grad-p5)" filter="url(#ppeppShadow)" class="node-circle" />
                                        <text x="96" y="184" text-anchor="middle" font-family="var(--font-heading)" font-size="28" font-weight="900" fill="#ffffff">P</text>
                                        <text x="96" y="204" text-anchor="middle" font-family="var(--font-heading)" font-size="9.5" font-weight="800" fill="#ffffff" letter-spacing="0.5">Peningkatan</text>
                                    </g>
                                </svg>
                                <div class="mt-2 text-muted" style="font-size:0.8rem;">
                                    <i class="bi bi-cursor-fill text-primary me-1"></i> Klik lingkaran siklus untuk melihat penjelasan
                                </div>
                            </div>
                        </div>

                        <!-- Right: Dynamic Interactive Explanation Card Directly Beside Diagram -->
                        <div class="col-lg-6 col-xl-6">
                            <div class="ppepp-card-container mx-auto" style="max-width:490px;">
                                <!-- Dynamic Detail Panel for Selected Stage -->
                                <?php foreach ($ppepp_data as $idx => $stg): ?>
                                <div class="ppepp-detail-card <?= $idx === 0 ? 'd-block' : 'd-none' ?>" id="ppepp-detail-<?= $idx ?>" style="background:#ffffff;border:1px solid var(--border);border-left:5px solid <?= $stg['warna'] ?>;border-radius:var(--radius-md);padding:1.3rem 1.45rem;box-shadow:0 8px 26px rgba(10,25,47,0.06);animation:ppeppFadeIn 0.3s ease;">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2" style="border-bottom:1px solid #F1F5F9;">
                                        <span style="font-size:0.72rem;font-weight:800;letter-spacing:0.5px;color:<?= $stg['warna'] ?>;background:<?= $stg['bg_light'] ?>;padding:0.25rem 0.65rem;border-radius:20px;text-transform:uppercase;">
                                            Tahap <?= $idx + 1 ?> dari 5 &bull; Siklus SPMI
                                        </span>
                                        <div class="d-flex align-items-center gap-1">
                                            <button class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.72rem;border-radius:6px;" onclick="navigatePpeppStage(<?= ($idx - 1 + 5) % 5 ?>)" title="Tahap Sebelumnya">
                                                &larr; Prev
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.72rem;border-radius:6px;" onclick="navigatePpeppStage(<?= ($idx + 1) % 5 ?>)" title="Tahap Selanjutnya">
                                                Next &rarr;
                                            </button>
                                        </div>
                                    </div>

                                    <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.15rem;margin-bottom:0.45rem;">
                                        <?= $stg['judul'] ?>
                                    </h3>

                                    <p style="font-size:0.85rem;color:var(--text-main);line-height:1.55;margin-bottom:0.75rem;">
                                        <?= $stg['deskripsi'] ?>
                                    </p>

                                    <div class="mb-2">
                                        <div style="font-family:var(--font-heading);font-weight:700;font-size:0.78rem;color:var(--navy);margin-bottom:0.35rem;text-transform:uppercase;letter-spacing:0.5px;">
                                            <i class="bi bi-check2-circle me-1" style="color:<?= $stg['warna'] ?>;"></i> Prosedur Operasional:
                                        </div>
                                        <ul style="margin:0;padding-left:1.15rem;font-size:0.81rem;color:var(--text-muted);line-height:1.5;">
                                            <?php foreach ($stg['langkah'] as $step_item): ?>
                                            <li class="mb-1"><?= $step_item ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <div class="row g-2 pt-2" style="border-top:1px dashed var(--border);">
                                        <div class="col-sm-7">
                                            <small class="text-muted d-block" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Dokumen Terkait:</small>
                                            <span style="font-size:0.78rem;color:var(--navy);font-weight:600;"><?= $stg['dokumen'] ?></span>
                                        </div>
                                        <div class="col-sm-5">
                                            <small class="text-muted d-block" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Penanggung Jawab:</small>
                                            <span style="font-size:0.78rem;color:var(--navy);font-weight:600;"><?= $stg['aktor'] ?></span>
                                        </div>
                                    </div>

                                    <div class="mt-2 pt-2 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-top:1px solid var(--border);">
                                        <a href="#dokumen-spmi" class="btn btn-sm btn-outline-primary fw-bold px-3 py-1" style="font-size:0.76rem;border-radius:6px;text-decoration:none;">
                                            <i class="bi bi-file-earmark-text me-1"></i> Dokumen Terkait
                                        </a>
                                        <span style="font-size:0.72rem;color:var(--text-muted);">
                                            Standar Mutu UNIKA Soegijapranata
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <style>
                        @keyframes ppeppFadeIn {
                            from { opacity: 0; transform: translateY(6px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                        .ppepp-cycle-svg,
                        .ppepp-cycle-svg *,
                        .ppepp-interactive-node,
                        .ppepp-interactive-node * {
                            outline: none !important;
                            outline-width: 0 !important;
                            outline-style: none !important;
                            outline-color: transparent !important;
                            box-shadow: none !important;
                            -webkit-tap-highlight-color: transparent !important;
                            -webkit-touch-callout: none;
                            user-select: none;
                            -webkit-user-select: none;
                        }
                        .ppepp-interactive-node {
                            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
                            transform-origin: center center;
                            cursor: pointer;
                        }
                        .ppepp-interactive-node:focus,
                        .ppepp-interactive-node:focus-visible,
                        .ppepp-interactive-node:active,
                        .ppepp-cycle-svg:focus,
                        .ppepp-cycle-svg:focus-visible {
                            outline: none !important;
                            outline-width: 0 !important;
                            outline-style: none !important;
                            outline-color: transparent !important;
                            box-shadow: none !important;
                        }
                        .node-ring {
                            opacity: 0;
                            transition: opacity 0.25s ease;
                        }
                        .ppepp-interactive-node:hover {
                            transform: scale(1.07);
                        }
                        .ppepp-interactive-node.active {
                            transform: scale(1.09);
                        }
                        .ppepp-interactive-node.active .node-ring {
                            opacity: 1 !important;
                            animation: ppeppPulse 2s infinite linear;
                        }
                        @keyframes ppeppPulse {
                            0% { stroke-dashoffset: 0; }
                            100% { stroke-dashoffset: 20; }
                        }
                    </style>

                    <script>
                        function selectPpeppStage(index) {
                            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                                document.activeElement.blur();
                            }
                            // Update SVG nodes and their rings
                            document.querySelectorAll('.ppepp-interactive-node').forEach(function(node, i) {
                                var ring = node.querySelector('.node-ring');
                                if (i === index) {
                                    node.classList.add('active');
                                    if (ring) ring.setAttribute('opacity', '1');
                                } else {
                                    node.classList.remove('active');
                                    if (ring) ring.setAttribute('opacity', '0');
                                }
                            });

                            // Update detail cards
                            document.querySelectorAll('.ppepp-detail-card').forEach(function(card, i) {
                                if (i === index) {
                                    card.classList.remove('d-none');
                                    card.classList.add('d-block');
                                } else {
                                    card.classList.add('d-none');
                                    card.classList.remove('d-block');
                                }
                            });
                        }

                        function navigatePpeppStage(index) {
                            selectPpeppStage(index);
                        }
                    </script>

                </div>
            </section>
            <?php
            break;

        case 'spmi_kemendikti':
            // Pengaturan Dinamis 3 Portal Utama SPMI
            $sista_st    = getPengaturan('portal_sista_status', 'active');
            $sista_url   = getPengaturan('portal_sista_url', 'https://sista.unika.ac.id');
            $sista_cs_t  = getPengaturan('portal_sista_cs_title', 'Tautan Sistem SISTA Belum Dibuka');
            $sista_cs_d  = getPengaturan('portal_sista_cs_desc', 'Pemantauan dan pelaporan siklus PPEPP diaktifkan sesuai jadwal.');

            $spmi_st     = getPengaturan('portal_spmi_status', 'active');
            $spmi_url    = getPengaturan('portal_spmi_url', 'https://spmi.kemdiktisaintek.go.id/auth/login');
            $spmi_cs_t   = getPengaturan('portal_spmi_cs_title', 'Tautan Sistem SPMI Kemendikti Belum Dibuka');
            $spmi_cs_d   = getPengaturan('portal_spmi_cs_desc', 'Pelaporan evaluasi pelaksanaan penjaminan mutu akan diaktifkan sesuai jadwal.');

            $eppepp_st   = getPengaturan('portal_eppepp_status', 'coming_soon');
            $eppepp_url  = getPengaturan('portal_eppepp_url', 'https://e-ppepp.unika.ac.id');
            $eppepp_cs_t = getPengaturan('portal_eppepp_cs_title', 'Tautan Sistem e-PPEPP Belum Dibuka');
            $eppepp_cs_d = getPengaturan('portal_eppepp_cs_desc', 'Pelaksanaan dan evaluasi sistem e-PPEPP akan diaktifkan sesuai jadwal.');
            ?>
            <section class="py-5" style="background:#ffffff;border-top:1px solid var(--border);border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>" id="hasil-kemendikti">
                <div class="container">
                    <div class="row g-4 justify-content-center">
                        <!-- Card 1: Portal SISTA -->
                        <div class="col-lg-4 col-md-6">
                            <div class="h-100 p-4 rounded-4 shadow-sm d-flex flex-column text-white" style="background:linear-gradient(145deg, #0A192F 0%, #132D54 100%);border:1px solid rgba(255,255,255,0.1);transition:transform 0.2s ease, box-shadow 0.2s ease;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.75rem;padding:0.35rem 0.7rem;font-weight:700;letter-spacing:0.5px;">
                                        STANDAR AKADEMIK
                                    </span>
                                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#FFD54F;">
                                        <i class="bi bi-mortarboard-fill fs-6"></i>
                                    </div>
                                </div>
                                <h4 style="font-family:var(--font-heading);font-weight:800;color:#ffffff;font-size:1.25rem;margin-bottom:0.6rem;">
                                    Portal SISTA
                                </h4>
                                <p style="color:rgba(255,255,255,0.8);font-size:0.88rem;line-height:1.65;margin-bottom:1.5rem;" class="flex-grow-1">
                                    Pemantauan &amp; pelaporan siklus PPEPP, perumusan capaian, bukti dukung pelaksanaan, dan rencana tindak lanjut termonitor secara digital terintegrasi.
                                </p>
                                <div class="pt-3 border-top mt-auto" style="border-color:rgba(255,255,255,0.12) !important;">
                                    <?php if ($sista_st === 'active' && !empty($sista_url)): ?>
                                    <a href="<?= e($sista_url) ?>" target="_blank" rel="noopener noreferrer" class="btn w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 text-white" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;font-size:0.9rem;box-shadow:0 4px 15px rgba(123,31,162,0.4);">
                                        <span>Buka Portal SISTA</span>
                                        <i class="bi bi-box-arrow-up-right" style="font-size:0.8rem;"></i>
                                    </a>
                                    <?php else: ?>
                                    <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 text-center w-100">
                                        <div class="badge bg-info text-dark px-3 py-1 rounded-pill mb-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-hourglass-split me-1"></i> Segera Hadir</div>
                                        <div class="text-white fw-bold" style="font-size:0.9rem;"><?= e($sista_cs_t) ?></div>
                                        <div class="text-white-50 small mt-1" style="font-size:0.78rem;line-height:1.45;"><?= e($sista_cs_d) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Portal SPMI Kemendikti -->
                        <div class="col-lg-4 col-md-6">
                            <div class="h-100 p-4 rounded-4 shadow-sm d-flex flex-column text-white" style="background:linear-gradient(145deg, #0A192F 0%, #132D54 100%);border:1px solid rgba(255,255,255,0.1);transition:transform 0.2s ease, box-shadow 0.2s ease;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.75rem;padding:0.35rem 0.7rem;font-weight:700;letter-spacing:0.5px;">
                                        PELAPORAN NASIONAL
                                    </span>
                                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#FFD54F;">
                                        <i class="bi bi-shield-check fs-6"></i>
                                    </div>
                                </div>
                                <h4 style="font-family:var(--font-heading);font-weight:800;color:#ffffff;font-size:1.25rem;margin-bottom:0.6rem;">
                                    Portal SPMI Kemendikti
                                </h4>
                                <p style="color:rgba(255,255,255,0.8);font-size:0.88rem;line-height:1.65;margin-bottom:1.5rem;" class="flex-grow-1">
                                    Rekapitulasi dan pelaporan evaluasi pelaksanaan penjaminan mutu perguruan tinggi secara berkala kepada Kementerian Pendidikan Tinggi, Sains, dan Teknologi.
                                </p>
                                <div class="pt-3 border-top mt-auto" style="border-color:rgba(255,255,255,0.12) !important;">
                                    <?php if ($spmi_st === 'active' && !empty($spmi_url)): ?>
                                    <a href="<?= e($spmi_url) ?>" target="_blank" rel="noopener noreferrer" class="btn w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 text-white" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;font-size:0.9rem;box-shadow:0 4px 15px rgba(123,31,162,0.4);">
                                        <span>Buka Portal SPMI</span>
                                        <i class="bi bi-box-arrow-up-right" style="font-size:0.8rem;"></i>
                                    </a>
                                    <?php else: ?>
                                    <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 text-center w-100">
                                        <div class="badge bg-info text-dark px-3 py-1 rounded-pill mb-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-hourglass-split me-1"></i> Segera Hadir</div>
                                        <div class="text-white fw-bold" style="font-size:0.9rem;"><?= e($spmi_cs_t) ?></div>
                                        <div class="text-white-50 small mt-1" style="font-size:0.78rem;line-height:1.45;"><?= e($spmi_cs_d) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Portal E-PPEPP -->
                        <div class="col-lg-4 col-md-6">
                            <div class="h-100 p-4 rounded-4 shadow-sm d-flex flex-column text-white" style="background:linear-gradient(145deg, #0A192F 0%, #132D54 100%);border:1px solid rgba(255,255,255,0.1);transition:transform 0.2s ease, box-shadow 0.2s ease;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.75rem;padding:0.35rem 0.7rem;font-weight:700;letter-spacing:0.5px;">
                                        SIKLUS MUTU PPEPP
                                    </span>
                                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#FFD54F;">
                                        <i class="bi bi-arrow-repeat fs-6"></i>
                                    </div>
                                </div>
                                <h4 style="font-family:var(--font-heading);font-weight:800;color:#ffffff;font-size:1.25rem;margin-bottom:0.6rem;">
                                    Portal E-PPEPP
                                </h4>
                                <p style="color:rgba(255,255,255,0.8);font-size:0.88rem;line-height:1.65;margin-bottom:1.5rem;" class="flex-grow-1">
                                    Sistem informasi elektronik implementasi, evaluasi pelaksanaan, dan pengendalian tahapan siklus PPEPP secara berkesinambungan.
                                </p>
                                <div class="pt-3 border-top mt-auto" style="border-color:rgba(255,255,255,0.12) !important;">
                                    <?php if ($eppepp_st === 'active' && !empty($eppepp_url)): ?>
                                    <a href="<?= e($eppepp_url) ?>" target="_blank" rel="noopener noreferrer" class="btn w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 text-white" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;font-size:0.9rem;box-shadow:0 4px 15px rgba(123,31,162,0.4);">
                                        <span>Buka Portal E-PPEPP</span>
                                        <i class="bi bi-box-arrow-up-right" style="font-size:0.8rem;"></i>
                                    </a>
                                    <?php else: ?>
                                    <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 text-center w-100">
                                        <div class="badge bg-info text-dark px-3 py-1 rounded-pill mb-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-hourglass-split me-1"></i> Segera Hadir</div>
                                        <div class="text-white fw-bold" style="font-size:0.9rem;"><?= e($eppepp_cs_t) ?></div>
                                        <div class="text-white-50 small mt-1" style="font-size:0.78rem;line-height:1.45;"><?= e($eppepp_cs_d) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'spmi_dokumen':
            $dokumen_all      = $data['dokumen_all'] ?? [];
            $allowed_kategori = $data['allowed_kategori'];
            ?>
            <section class="py-5" id="dokumen-spmi" style="<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
                        <div>
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Berkas Resmi') ?>
                            </span>
                            <div class="d-flex align-items-center gap-2">
                                <h2 class="section-title mb-0"><?= htmlspecialchars($block['title'] ?? 'Dokumen Mutu SPMI') ?></h2>
                                <span id="spmiDocCountBadge" class="badge" style="background:#E2E8F0;color:var(--navy);font-weight:700;font-size:0.8rem;padding:0.35rem 0.75rem;border-radius:20px;">
                                    <?= count($dokumen_all) ?> Dokumen
                                </span>
                            </div>
                        </div>

                        <!-- Real-time Search Box -->
                        <div style="min-width:240px;max-width:320px;width:100%;">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0" style="border-radius:50px 0 0 50px;border-color:var(--border);">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" id="spmiDocSearchInput" class="form-control border-start-0" placeholder="Cari nama dokumen..." style="border-radius:0 50px 50px 0;border-color:var(--border);font-size:0.85rem;box-shadow:none;">
                            </div>
                        </div>
                    </div>

                    <!-- Filter Kategori Tabs (AJAX Filterisasi Terpadu) -->
                    <style>
                    .btn-filter-pill {
                        display: inline-flex;
                        align-items: center;
                        padding: 0.45rem 1.15rem;
                        border-radius: 50px;
                        font-size: 0.82rem;
                        font-weight: 600;
                        text-decoration: none;
                        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                        background: #ffffff;
                        color: var(--text-muted);
                        border: 1.5px solid var(--border);
                        cursor: pointer;
                    }
                    .btn-filter-pill:hover {
                        color: var(--navy);
                        border-color: #94A3B8;
                        transform: translateY(-1px);
                    }
                    .btn-filter-pill.active {
                        background: var(--navy) !important;
                        color: #ffffff !important;
                        border-color: var(--navy) !important;
                        font-weight: 700 !important;
                        box-shadow: 0 4px 12px rgba(10, 25, 47, 0.25) !important;
                        transform: translateY(-1px);
                    }
                    </style>
                    <div class="mb-4" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <button type="button" class="btn-filter-pill spmi-ajax-filter-pill <?= $kategori_filter === '' ? 'active' : '' ?>" data-kategori="">
                            Semua Dokumen
                        </button>
                        <?php foreach ($allowed_kategori as $kat): ?>
                        <button type="button" class="btn-filter-pill spmi-ajax-filter-pill <?= $kategori_filter === $kat ? 'active' : '' ?>" data-kategori="<?= htmlspecialchars($kat) ?>">
                            <?= htmlspecialchars($kat) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Single Unified Document Table (Satu Tabel Terpadu) -->
                    <div class="doc-table-wrap mb-4" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 4px 20px rgba(10,25,47,0.04);">
                        <div class="table-responsive">
                            <table class="doc-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:55px;text-align:center;">No</th>
                                        <th>Nama Dokumen Mutu</th>
                                        <th style="width:160px;">Kategori</th>
                                        <th style="width:160px;">Tanggal Rilis</th>
                                        <th style="width:140px;text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="spmiDocTableBody" style="transition:opacity 0.2s ease;">
                                    <?php renderSpmiDokumenTableRows($dokumen_all); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <script>
            (function() {
                var activeCategory = '<?= addslashes($kategori_filter) ?>';
                var searchTimer = null;

                function fetchSpmiDocs() {
                    var q = document.getElementById('spmiDocSearchInput') ? document.getElementById('spmiDocSearchInput').value.trim() : '';
                    var tbody = document.getElementById('spmiDocTableBody');
                    var badge = document.getElementById('spmiDocCountBadge');
                    if (tbody) {
                        tbody.style.opacity = '0.4';
                    }

                    var url = 'spmi.php?ajax=dokumen&kategori=' + encodeURIComponent(activeCategory) + '&q=' + encodeURIComponent(q);
                    fetch(url)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (tbody) {
                                tbody.innerHTML = data.html;
                                tbody.style.opacity = '1';
                            }
                            if (badge) {
                                badge.innerText = data.count + ' Dokumen';
                            }
                        })
                        .catch(function(err) {
                            if (tbody) tbody.style.opacity = '1';
                            console.error('Gagal memuat dokumen via AJAX:', err);
                        });
                }

                // Filter pills click handler
                document.querySelectorAll('.spmi-ajax-filter-pill').forEach(function(pill) {
                    pill.addEventListener('click', function(e) {
                        e.preventDefault();
                        document.querySelectorAll('.spmi-ajax-filter-pill').forEach(function(p) { p.classList.remove('active'); });
                        this.classList.add('active');
                        activeCategory = this.getAttribute('data-kategori') || '';
                        fetchSpmiDocs();
                    });
                });

                // Debounced search input handler
                var searchInput = document.getElementById('spmiDocSearchInput');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        clearTimeout(searchTimer);
                        searchTimer = setTimeout(fetchSpmiDocs, 280);
                    });
                }
            })();
            </script>
            <?php
            break;
    }
}

function renderSpmiDokumenTableRows($docs) {
    if (empty($docs)) {
        ?>
        <tr>
            <td colspan="5" class="text-center py-5 text-muted">
                <i class="bi bi-file-earmark-x" style="font-size:2.2rem;opacity:0.4;"></i>
                <div class="mt-2" style="font-size:0.92rem;font-weight:600;">Tidak ada dokumen mutu yang sesuai dengan filter atau kata kunci pencarian.</div>
            </td>
        </tr>
        <?php
        return;
    }

    foreach ($docs as $i => $d) {
        $file_full = __DIR__ . '/../uploads/dokumen/' . ($d['file_path'] ?? '');
        $file_url  = SITE_URL . '/uploads/dokumen/' . htmlspecialchars($d['file_path'] ?? '');
        $ext       = strtolower(pathinfo($d['file_path'] ?? '', PATHINFO_EXTENSION));
        $is_pdf    = ($ext === 'pdf');
        $has_file  = !empty($d['file_path']) && file_exists($file_full);

        $kat_class = 'cat-standar';
        $k_lower = strtolower($d['kategori'] ?? '');
        if (strpos($k_lower, 'kebijakan') !== false) $kat_class = 'cat-kebijakan';
        elseif (strpos($k_lower, 'manual') !== false) $kat_class = 'cat-manual';
        elseif (strpos($k_lower, 'formulir') !== false) $kat_class = 'cat-formulir';
        elseif (strpos($k_lower, 'standar') !== false) $kat_class = 'cat-standar';
        ?>
        <tr>
            <td style="color:var(--text-muted);font-size:0.85rem;text-align:center;font-weight:600;"><?= $i + 1 ?></td>
            <td>
                <?php if ($has_file && $is_pdf): ?>
                <a href="javascript:void(0)" onclick="openPdfViewer('<?= $file_url ?>', '<?= addslashes(htmlspecialchars($d['nama_dokumen'])) ?>')" style="font-weight:700;color:var(--navy);text-decoration:none;display:inline-block;line-height:1.4;">
                    <?= htmlspecialchars($d['nama_dokumen']) ?>
                </a>
                <?php elseif ($has_file): ?>
                <a href="<?= $file_url ?>" download style="font-weight:700;color:var(--navy);text-decoration:none;display:inline-block;line-height:1.4;">
                    <?= htmlspecialchars($d['nama_dokumen']) ?>
                </a>
                <?php else: ?>
                <div style="font-weight:700;color:var(--navy);line-height:1.4;"><?= htmlspecialchars($d['nama_dokumen']) ?></div>
                <?php endif; ?>
                <?php if (!empty($d['deskripsi'])): ?>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;" class="text-truncate"><?= htmlspecialchars($d['deskripsi']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <span class="doc-cat-badge <?= $kat_class ?>"><?= htmlspecialchars($d['kategori']) ?></span>
            </td>
            <td style="font-size:0.85rem;color:var(--text-muted);white-space:nowrap;"><?= formatTanggal($d['created_at']) ?></td>
            <td style="text-align:center;">
                <?php if ($has_file): ?>
                    <?php if ($is_pdf): ?>
                    <button type="button" class="btn-download" onclick="openPdfViewer('<?= $file_url ?>', '<?= addslashes(htmlspecialchars($d['nama_dokumen'])) ?>')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Buka PDF
                    </button>
                    <?php else: ?>
                    <a href="<?= $file_url ?>" class="btn-download" download>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Unduh
                    </a>
                    <?php endif; ?>
                <?php else: ?>
                <span style="font-size:0.8rem;color:var(--text-muted);font-style:italic;">Tersedia di LPM</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

function handleSpmiDokumenAjax() {
    $db = getDB();
    $kategori = trim($_GET['kategori'] ?? '');
    $q = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM dokumen WHERE 1=1";
    $params = [];

    if ($kategori !== '' && $kategori !== 'Semua') {
        $sql .= " AND (kategori = ? OR kategori LIKE ?)";
        $params[] = $kategori;
        $params[] = "%$kategori%";
    }

    if ($q !== '') {
        $sql .= " AND (nama_dokumen LIKE ? OR deskripsi LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    $sql .= " ORDER BY created_at DESC, id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    renderSpmiDokumenTableRows($docs);
    $html = ob_get_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'count'   => count($docs),
        'html'    => $html
    ]);
    exit;
}
