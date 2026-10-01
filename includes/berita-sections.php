<?php
/**
 * Modular Sections untuk Halaman Berita & Kegiatan
 * Terintegrasi dengan Visual Page Builder (advance-setting.php) dan berita.php publik.
 */

function renderBeritaSection($type, $block = [], $is_builder = false, $params = []) {
    $bg = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    $db = getDB();

    switch ($type) {
        case 'berita_highlight':
            // Ambil 3 berita teratas / headline
            $highlight_list = $db->query("SELECT * FROM berita ORDER BY COALESCE(tanggal_publikasi, created_at) DESC, id DESC LIMIT 3")->fetchAll();
            if (empty($highlight_list)) return;
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
                        <div>
                            <span class="section-tag mb-2"><?= htmlspecialchars($block['badge'] ?? 'Warta Utama') ?></span>
                            <h2 class="section-title mb-0"><?= htmlspecialchars($block['title'] ?? 'Sorotan Kegiatan & Berita Mutu Terkini') ?></h2>
                        </div>
                        <p class="section-desc mb-0" style="max-width:500px;">
                            <?= htmlspecialchars($block['subtitle'] ?? 'Agenda penjaminan mutu, asesmen akreditasi, dan workshop peningkatan mutu terkini.') ?>
                        </p>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($highlight_list as $h): 
                            $tgl_h = $h['tanggal_publikasi'] ?: $h['created_at'];
                            $foto_h = $h['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $h['gambar']) ? SITE_URL . '/uploads/berita/' . $h['gambar'] : '';
                        ?>
                        <div class="col-md-4">
                            <a href="<?= SITE_URL ?>/berita-detail.php?slug=<?= e($h['slug']) ?>" class="card-lpm d-block text-decoration-none h-100" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;box-shadow:0 2px 10px rgba(10,25,47,0.03);">
                                <div style="height:190px;overflow:hidden;background:var(--navy);position:relative;">
                                    <?php if ($foto_h): ?>
                                    <img src="<?= $foto_h ?>" alt="<?= htmlspecialchars($h['judul']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                    <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--navy),var(--purple));display:flex;align-items:center;justify-content:center;color:#fff;">
                                        <i class="bi bi-newspaper fs-1 opacity-50"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 d-flex flex-column" style="height:calc(100% - 190px);">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="card-category-badge" style="font-size:0.72rem;"><?= htmlspecialchars($h['tipe'] ?: 'Kegiatan') ?></span>
                                        <span style="font-size:0.75rem;color:var(--text-muted);"><?= formatTanggal($tgl_h) ?></span>
                                    </div>
                                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;line-height:1.4;margin-bottom:0.5rem;">
                                        <?= htmlspecialchars($h['judul']) ?>
                                    </h5>
                                    <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.55;margin:0;flex-grow:1;">
                                        <?= truncate($h['konten'], 90) ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'berita_grid':
            // Parameter dari script berita.php
            $search        = $params['search'] ?? '';
            $ta_filter     = $params['ta_filter'] ?? '';
            $tipe_filter   = $params['tipe_filter'] ?? '';
            $daftar_ta     = $params['daftar_ta'] ?? [];
            $allowed_tipe  = $params['allowed_tipe'] ?? ['Berita', 'Kegiatan LPM', 'Artikel Mutu', 'Sosialisasi', 'Penghargaan'];
            $all_berita    = $params['all_berita'] ?? [];
            $total_records = $params['total_records'] ?? 0;
            $offset        = $params['offset'] ?? 0;
            $per_page      = $params['per_page'] ?? 9;
            $page          = $params['page'] ?? 1;
            $total_pages   = $params['total_pages'] ?? 1;
            $filter_descs  = $params['filter_descs'] ?? [];
            ?>
            <section class="py-5 py-md-6" id="beritaSection" style="<?= $bg ?><?= $tc ?>">
                <div class="container">
                    
                    <!-- Filter Bar Container -->
                    <div class="filter-card-pro">
                        <!-- Search Bar -->
                        <div class="filter-row align-items-center mb-0">
                            <div class="filter-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#2563EB" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                Cari Kegiatan
                            </div>
                            <div class="d-flex gap-2 flex-grow-1" style="max-width:100%;">
                                <div class="position-relative flex-grow-1">
                                    <input type="text" id="searchJudulInput" class="form-control" placeholder="Ketik kata kunci judul kegiatan..." value="<?= e($search) ?>" style="border-radius:50px;padding:0.6rem 3rem 0.6rem 1.25rem;font-size:0.9rem;border:1.5px solid #CBD5E1;background:#F8FAFC;box-shadow:none;" autocomplete="off">
                                    <button type="button" id="clearSearchBtn" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 text-muted" style="border:none;background:transparent;display:<?= $search ? 'block' : 'none' ?>;cursor:pointer;" title="Hapus pencarian">
                                        <i class="bi bi-x-circle-fill" style="font-size:1.1rem;color:#94A3B8;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Tahun Akademik -->
                        <?php if (!empty($daftar_ta)): ?>
                        <div class="filter-row">
                            <div class="filter-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#7C3AED" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                Tahun Akademik
                            </div>

                            <div class="filter-options">
                                <button type="button" class="btn-ta-pill <?= empty($ta_filter) ? 'active' : '' ?>" data-val="">
                                    Semua Tahun
                                </button>
                                <?php foreach ($daftar_ta as $ta_item): ?>
                                <button type="button" class="btn-ta-pill <?= $ta_filter === $ta_item ? 'active' : '' ?>" data-val="<?= e($ta_item) ?>">
                                    TA <?= e($ta_item) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Filter Kategori -->
                        <div class="filter-row">
                            <div class="filter-label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#0F172A" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                </svg>
                                Kategori
                            </div>

                            <div class="filter-options">
                                <button type="button" class="btn-cat-pill <?= empty($tipe_filter) ? 'active' : '' ?>" data-val="">
                                    Semua Kategori
                                </button>
                                <?php foreach ($allowed_tipe as $tp): ?>
                                <button type="button" class="btn-cat-pill <?= $tipe_filter === $tp ? 'active' : '' ?>" data-val="<?= e($tp) ?>">
                                    <?= e($tp) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Status Info & Reset -->
                        <div class="d-flex align-items-center justify-content-between pt-1 flex-wrap gap-2" style="font-size:0.8rem;color:var(--text-muted);">
                            <div class="d-flex align-items-center gap-2 flex-wrap" id="countInfo">
                                <?php if ($total_records > 0): ?>
                                <span>Menampilkan <strong><?= $offset + 1 ?>–<?= min($offset + $per_page, $total_records) ?></strong> dari <strong style="color:var(--navy);"><?= $total_records ?></strong> kegiatan</span>
                                <?php else: ?>
                                <span>Ditemukan <strong style="color:var(--navy);">0</strong> kegiatan</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="resetFilterBtn" class="btn-reset-filter" style="display:<?= ($search || $ta_filter || $tipe_filter) ? 'inline-flex' : 'none' ?>;">
                                <i class="bi bi-x-circle"></i> Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Content List Grid -->
                    <div class="row g-4" id="beritaGrid" style="<?= empty($all_berita) ? 'display:none;' : '' ?>">
                        <?= function_exists('renderBeritaCardsHtml') ? renderBeritaCardsHtml($all_berita) : '' ?>
                    </div>

                    <!-- Pagination Bar Wrapper -->
                    <div id="paginationWrap" class="mt-5 d-flex justify-content-center" style="<?= ($total_pages <= 1) ? 'display:none;' : '' ?>">
                        <?= function_exists('renderBeritaPaginationHtml') ? renderBeritaPaginationHtml($page, $total_pages) : '' ?>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
