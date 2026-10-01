<?php
/**
 * Modular Sections for Pusat Dokumen (dokumen.php)
 * Integrates with Visual Page Builder
 */

function renderDokumenSection($section_name, $ctx = [], $custom_data = []) {
    // Unpack context variables
    $all_docs        = $ctx['all_docs'] ?? [];
    $daftar_sumber   = $ctx['daftar_sumber'] ?? [];
    $sumber_filter   = $ctx['sumber_filter'] ?? '';
    $search          = $ctx['search'] ?? '';
    $paginated_docs  = $ctx['paginated_docs'] ?? [];
    $total_records   = $ctx['total_records'] ?? count($all_docs);
    $total_pages     = $ctx['total_pages'] ?? 1;
    $page            = $ctx['page'] ?? 1;
    $offset          = $ctx['offset'] ?? 0;
    $per_page        = $ctx['per_page'] ?? 15;

    switch ($section_name) {
        case 'dokumen_header':
            $badge = $custom_data['badge'] ?? 'Database Dokumen Terpadu';
            $title = $custom_data['title'] ?? 'Pusat Database Dokumen & Arsip';
            $desc  = $custom_data['desc'] ?? 'Akses repositori berkas regulasi, instrumen akreditasi, dan panduan mutu LPM UNIKA';
            ?>
            <!-- Page Banner -->
            <div class="page-banner" style="background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 60%, #3B82F6 100%); padding: 3rem 0; color: #fff; position: relative;">
                <div class="container position-relative">
                    <div class="hero-badge mb-3" style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,0.12); padding:0.35rem 1rem; border-radius:50px; font-size:0.85rem; font-weight:600; border:1px solid rgba(255,255,255,0.2);">
                        <span class="hero-badge-dot" style="width:8px; height:8px; background:#F59E0B; border-radius:50%;"></span>
                        <?= htmlspecialchars($badge) ?>
                    </div>
                    <h1 class="page-banner-title" style="font-size:2.2rem; font-weight:800; margin-bottom:0.5rem; color:#fff;"><?= htmlspecialchars($title) ?></h1>
                    <?php if ($desc): ?>
                    <p style="color:rgba(255,255,255,0.8); font-size:1rem; max-width:700px; margin-bottom:1rem;"><?= htmlspecialchars($desc) ?></p>
                    <?php endif; ?>
                    <div class="breadcrumb-lpm" style="font-size:0.85rem; opacity:0.85;">
                        <a href="<?= SITE_URL ?>/" style="color:#fff; text-decoration:none;">Beranda</a>
                        <span style="margin: 0 0.5rem;">/</span>
                        <span class="current" style="color:#93C5FD;">Database Dokumen</span>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'dokumen_table':
            ?>
            <section class="py-5" style="background:var(--bg-main, #F8FAFC);">
                <div class="container">

                    <!-- Search Box Container -->
                    <div class="doc-search-box">
                        
                        <!-- Quick Filter Tabs (Sumber Dokumen) -->
                        <div class="doc-source-pills" id="sourcePillsContainer">
                            <button type="button" class="source-pill-btn <?= empty($sumber_filter) ? 'active' : '' ?>" data-sumber="">
                                <i class="bi bi-collection-fill" style="font-size:0.75rem;"></i> Semua Sumber Dokumen
                                <span class="pill-count"><?= count($all_docs) ?></span>
                            </button>
                            <?php foreach ($daftar_sumber as $src_name => $src_count): ?>
                            <button type="button" class="source-pill-btn <?= $sumber_filter === $src_name ? 'active' : '' ?>" data-sumber="<?= htmlspecialchars($src_name) ?>">
                                <?= htmlspecialchars($src_name) ?>
                                <span class="pill-count"><?= $src_count ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Instant Real-Time Search Form -->
                        <div class="row g-2 align-items-center">
                            <div class="col-12">
                                <div class="position-relative">
                                    <input type="text" id="searchDocInput" class="form-control" placeholder="Ketik kata kunci untuk mencari langsung (cth: Arsitektur, DKV, Standar, LAMEMBA, 2026, SK)..." value="<?= htmlspecialchars($search) ?>" style="border-radius:50px;padding:0.7rem 3.5rem 0.7rem 2.8rem;font-size:0.92rem;border:1.5px solid #CBD5E1;background:#F8FAFC;box-shadow:none;transition:all 0.2s;" autocomplete="off">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#64748B" width="18" height="18" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    <button type="button" id="clearSearchBtn" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-3 text-muted" style="border:none;background:transparent;display:<?= $search ? 'block' : 'none' ?>;cursor:pointer;" title="Hapus pencarian">
                                        <i class="bi bi-x-circle-fill" style="font-size:1.15rem;color:#94A3B8;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Status Info Bar -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top flex-wrap gap-2" style="font-size:0.82rem;color:var(--text-muted, #64748B);">
                            <div id="docStatusDesc">
                                Ditemukan <strong style="color:var(--navy, #0A192F);"><?= $total_records ?></strong> berkas dokumen
                                <?php if ($search): ?>
                                    untuk kata kunci <span style="color:var(--purple, #7B1FA2);font-weight:700;">"<?= htmlspecialchars($search) ?>"</span>
                                <?php endif; ?>
                                <?php if ($sumber_filter): ?>
                                    pada sumber <span style="color:var(--navy, #0A192F);font-weight:700;">(<?= htmlspecialchars($sumber_filter) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span id="docPageCounter">
                                    <?php if ($total_records > 0): ?>
                                    Menampilkan data <strong><?= $offset + 1 ?>–<?= min($offset + $per_page, $total_records) ?></strong>
                                    <?php endif; ?>
                                </span>
                                <button type="button" id="resetAllFiltersBtn" class="btn btn-sm text-danger p-0" style="display:<?= ($search || $sumber_filter) ? 'inline-flex' : 'none' ?>;border:none;background:none;font-weight:600;font-size:0.8rem;text-decoration:underline;cursor:pointer;">
                                    Reset Filter
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Table Card of Unified Documents -->
                    <div class="doc-table-card" id="docTableCard">
                        <div id="docTableWrap" style="overflow-x:auto;<?= empty($paginated_docs) ? 'display:none;' : '' ?>">
                            <table class="doc-table mb-0">
                                <thead>
                                    <tr>
                                        <th width="50" style="text-align:center;">#</th>
                                        <th>Nama Dokumen &amp; Deskripsi Detail</th>
                                        <th width="200">Sumber &amp; Kategori</th>
                                        <th width="120">Tanggal</th>
                                        <th width="130" style="text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="docTableBody">
                                    <?php foreach ($paginated_docs as $i => $d): 
                                        $safe_title = addslashes(htmlspecialchars($d['judul'], ENT_QUOTES));
                                        $src_class = 'instrumen';
                                        if (strpos($d['sumber'], 'SPMI') !== false) $src_class = 'spmi';
                                        elseif (strpos($d['sumber'], 'Program Studi') !== false) $src_class = 'akreditasi';
                                        elseif (strpos($d['sumber'], 'JAMUS') !== false) $src_class = 'buletin';

                                        $icon_class = 'file-icon-other';
                                        $icon_label = strtoupper($d['ext'] ?: 'DOC');
                                        if ($d['is_pdf']) {
                                            $icon_class = 'file-icon-pdf';
                                            $icon_label = 'PDF';
                                        } elseif (in_array($d['ext'], ['doc', 'docx'])) {
                                            $icon_class = 'file-icon-doc';
                                            $icon_label = 'DOC';
                                        } elseif (in_array($d['ext'], ['xls', 'xlsx'])) {
                                            $icon_class = 'file-icon-xls';
                                            $icon_label = 'XLS';
                                        }
                                    ?>
                                    <tr>
                                        <td style="color:var(--text-muted, #64748B);font-size:0.85rem;text-align:center;">
                                            <?= $offset + $i + 1 ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="file-icon-badge <?= $icon_class ?>">
                                                    <?= $icon_label ?>
                                                </div>
                                                <div>
                                                    <?php if ($d['has_file'] && $d['is_pdf']): ?>
                                                    <a href="javascript:void(0)" onclick="if(typeof openPdfViewer==='function'){openPdfViewer('<?= $d['file_url'] ?>', '<?= $safe_title ?>');}" style="font-weight:700;color:var(--navy, #0A192F);text-decoration:none;font-size:0.92rem;" class="doc-title-link" title="Klik untuk membuka dokumen PDF">
                                                        <?= htmlspecialchars($d['judul']) ?>
                                                    </a>
                                                    <?php elseif ($d['has_file']): ?>
                                                    <a href="<?= $d['file_url'] ?>" download style="font-weight:700;color:var(--navy, #0A192F);text-decoration:none;font-size:0.92rem;" class="doc-title-link">
                                                        <?= htmlspecialchars($d['judul']) ?>
                                                    </a>
                                                    <?php else: ?>
                                                    <div style="font-weight:700;color:var(--navy, #0A192F);font-size:0.92rem;">
                                                        <?= htmlspecialchars($d['judul']) ?>
                                                    </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($d['info_tambahan'])): ?>
                                                    <div style="font-size:0.78rem;color:var(--text-muted, #64748B);margin-top:3px;">
                                                        <?= htmlspecialchars($d['info_tambahan']) ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1 align-items-start">
                                                <span class="doc-badge-source <?= $src_class ?>"><?= htmlspecialchars($d['sumber']) ?></span>
                                                <span class="card-category-badge" style="font-size:0.7rem;padding:0.15rem 0.5rem;"><?= htmlspecialchars($d['kategori']) ?></span>
                                            </div>
                                        </td>
                                        <td style="font-size:0.8rem;color:var(--text-muted, #64748B);white-space:nowrap;">
                                            <?= htmlspecialchars($d['formatted_date']) ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php if ($d['has_file']): ?>
                                                <?php if ($d['is_pdf']): ?>
                                                <button type="button" class="btn-download" onclick="if(typeof openPdfViewer==='function'){openPdfViewer('<?= $d['file_url'] ?>', '<?= $safe_title ?>');}" style="border:none;background:rgba(123,31,162,0.1);color:var(--purple, #7B1FA2);font-weight:700;cursor:pointer;display:inline-flex;padding:0.4rem 0.85rem;" title="Buka dan baca PDF">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="15" height="15">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                    Buka PDF
                                                </button>
                                                <?php else: ?>
                                                <a href="<?= $d['file_url'] ?>" class="btn-download" download style="display:inline-flex;padding:0.4rem 0.85rem;background:#F1F5F9;color:#334155;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="15" height="15">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                    </svg>
                                                    Unduh
                                                </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                            <span style="font-size:0.75rem;color:var(--text-muted, #64748B);">Berkas digital siap diunggah</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State Container -->
                        <div id="docEmptyState" class="text-center py-5 px-3" style="background:#fff;<?= !empty($paginated_docs) ? 'display:none;' : '' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="var(--text-muted, #64748B)" width="54" height="54" style="opacity:0.3;margin-bottom:1rem;display:block;margin-left:auto;margin-right:auto;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <h5 style="color:var(--navy, #0A192F);font-weight:700;margin-bottom:0.4rem;">Tidak Ada Dokumen yang Sesuai</h5>
                            <p style="color:var(--text-muted, #64748B);font-size:0.875rem;max-width:450px;margin:0 auto 1.5rem;">
                                Tidak ditemukan berkas dokumen yang cocok dengan kata kunci atau filter yang Anda masukkan di database sistem.
                            </p>
                            <button type="button" id="btnEmptyReset" class="btn btn-sm btn-purple px-4 py-2" style="border-radius:50px;background:#7B1FA2;color:#fff;">
                                Tampilkan Semua Dokumen
                            </button>
                        </div>

                        <!-- Table Pagination Bar -->
                        <div id="docPaginationWrap" style="padding:1.25rem 1.75rem;background:#F8FAFC;border-top:1px solid #E2E8F0;<?= ($total_pages <= 1) ? 'display:none;' : 'display:flex;' ?>justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                            <div id="docPageSummary" style="font-size:0.82rem;color:var(--text-muted, #64748B);">
                                Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total <?= $total_records ?> dokumen)
                            </div>
                            <div class="pagination-lpm" id="docPaginationList" style="margin:0;gap:4px;">
                                <!-- Rendered by JS seamlessly -->
                            </div>
                        </div>

                    </div>

                </div>
            </section>

            <!-- Embedded Master Document Dataset for Instant Seamless Search & Pagination -->
            <script>
            const ALL_DOCUMENTS = <?= json_encode($all_docs, JSON_UNESCAPED_UNICODE) ?>;

            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchDocInput');
                const clearBtn = document.getElementById('clearSearchBtn');
                const sourceBtns = document.querySelectorAll('.source-pill-btn');
                const tableWrap = document.getElementById('docTableWrap');
                const tableBody = document.getElementById('docTableBody');
                const emptyState = document.getElementById('docEmptyState');
                const paginationWrap = document.getElementById('docPaginationWrap');
                const paginationList = document.getElementById('docPaginationList');
                const pageSummary = document.getElementById('docPageSummary');
                const statusDesc = document.getElementById('docStatusDesc');
                const pageCounter = document.getElementById('docPageCounter');
                const resetAllBtn = document.getElementById('resetAllFiltersBtn');
                const emptyResetBtn = document.getElementById('btnEmptyReset');

                if (!searchInput || !tableBody) return;

                const perPage = 15;
                let state = {
                    q: "<?= addslashes($search) ?>",
                    sumber: "<?= addslashes($sumber_filter) ?>",
                    page: <?= $page ?>
                };

                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function render() {
                    const query = state.q.trim().toLowerCase();
                    
                    // 1. Filter data
                    const filtered = ALL_DOCUMENTS.filter(item => {
                        if (state.sumber && item.sumber !== state.sumber) return false;
                        if (query) {
                            const space = (
                                (item.judul || '') + ' ' +
                                (item.kategori || '') + ' ' +
                                (item.sumber || '') + ' ' +
                                (item.info_tambahan || '') + ' ' +
                                (item.file_path || '')
                            ).toLowerCase();
                            if (!space.includes(query)) return false;
                        }
                        return true;
                    });

                    // 2. Pagination calculation
                    const total = filtered.length;
                    const totalPages = Math.max(1, Math.ceil(total / perPage));
                    if (state.page > totalPages) state.page = totalPages;
                    if (state.page < 1) state.page = 1;

                    const offset = (state.page - 1) * perPage;
                    const pageItems = filtered.slice(offset, offset + perPage);

                    // 3. Update Status Texts
                    let descHtml = `Ditemukan <strong style="color:var(--navy, #0A192F);">${total}</strong> berkas dokumen`;
                    if (query) descHtml += ` untuk kata kunci <span style="color:var(--purple, #7B1FA2);font-weight:700;">"${escapeHtml(state.q)}"</span>`;
                    if (state.sumber) descHtml += ` pada sumber <span style="color:var(--navy, #0A192F);font-weight:700;">(${escapeHtml(state.sumber)})</span>`;
                    if (statusDesc) statusDesc.innerHTML = descHtml;

                    if (pageCounter) {
                        if (total > 0) {
                            pageCounter.innerHTML = `Menampilkan data <strong>${offset + 1}–${Math.min(offset + perPage, total)}</strong>`;
                            pageCounter.style.display = 'inline';
                        } else {
                            pageCounter.style.display = 'none';
                        }
                    }

                    const hasActiveFilter = Boolean(query || state.sumber);
                    if (resetAllBtn) resetAllBtn.style.display = hasActiveFilter ? 'inline-flex' : 'none';
                    if (clearBtn) clearBtn.style.display = query ? 'block' : 'none';

                    // 4. Update Table Rows
                    if (total === 0) {
                        if (tableWrap) tableWrap.style.display = 'none';
                        if (emptyState) emptyState.style.display = 'block';
                        if (paginationWrap) paginationWrap.style.display = 'none';
                    } else {
                        if (tableWrap) tableWrap.style.display = 'block';
                        if (emptyState) emptyState.style.display = 'none';

                        let rowsHtml = '';
                        pageItems.forEach((d, idx) => {
                            const rowNum = offset + idx + 1;
                            const safeTitle = (d.judul || '').replace(/'/g, "\\'");

                            let srcClass = 'instrumen';
                            if (d.sumber && d.sumber.includes('SPMI')) srcClass = 'spmi';
                            else if (d.sumber && d.sumber.includes('Program Studi')) srcClass = 'akreditasi';
                            else if (d.sumber && d.sumber.includes('JAMUS')) srcClass = 'buletin';

                            let iconClass = 'file-icon-other';
                            let iconLabel = (d.ext || 'DOC').toUpperCase();
                            if (d.is_pdf) {
                                iconClass = 'file-icon-pdf';
                                iconLabel = 'PDF';
                            } else if (['doc', 'docx'].includes(d.ext)) {
                                iconClass = 'file-icon-doc';
                                iconLabel = 'DOC';
                            } else if (['xls', 'xlsx'].includes(d.ext)) {
                                iconClass = 'file-icon-xls';
                                iconLabel = 'XLS';
                            }

                            let titleHtml = '';
                            if (d.has_file && d.is_pdf) {
                                titleHtml = `<a href="javascript:void(0)" onclick="if(typeof openPdfViewer==='function'){openPdfViewer('${d.file_url}', '${escapeHtml(safeTitle)}');}" style="font-weight:700;color:var(--navy, #0A192F);text-decoration:none;font-size:0.92rem;" class="doc-title-link" title="Klik untuk membuka dokumen PDF">${escapeHtml(d.judul)}</a>`;
                            } else if (d.has_file) {
                                titleHtml = `<a href="${d.file_url}" download style="font-weight:700;color:var(--navy, #0A192F);text-decoration:none;font-size:0.92rem;" class="doc-title-link">${escapeHtml(d.judul)}</a>`;
                            } else {
                                titleHtml = `<div style="font-weight:700;color:var(--navy, #0A192F);font-size:0.92rem;">${escapeHtml(d.judul)}</div>`;
                            }

                            let actionHtml = '';
                            if (d.has_file) {
                                if (d.is_pdf) {
                                    actionHtml = `<button type="button" class="btn-download" onclick="if(typeof openPdfViewer==='function'){openPdfViewer('${d.file_url}', '${escapeHtml(safeTitle)}');}" style="border:none;background:rgba(123,31,162,0.1);color:var(--purple, #7B1FA2);font-weight:700;cursor:pointer;display:inline-flex;padding:0.4rem 0.85rem;" title="Buka dan baca PDF">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="15" height="15">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg> Buka PDF
                                    </button>`;
                                } else {
                                    actionHtml = `<a href="${d.file_url}" class="btn-download" download style="display:inline-flex;padding:0.4rem 0.85rem;background:#F1F5F9;color:#334155;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="15" height="15">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg> Unduh
                                    </a>`;
                                }
                            } else {
                                actionHtml = `<span style="font-size:0.75rem;color:var(--text-muted, #64748B);">Berkas siap diunggah</span>`;
                            }

                            rowsHtml += `<tr>
                                <td style="color:var(--text-muted, #64748B);font-size:0.85rem;text-align:center;">${rowNum}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="file-icon-badge ${iconClass}">${iconLabel}</div>
                                        <div>
                                            ${titleHtml}
                                            ${d.info_tambahan ? `<div style="font-size:0.78rem;color:var(--text-muted, #64748B);margin-top:3px;">${escapeHtml(d.info_tambahan)}</div>` : ''}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 align-items-start">
                                        <span class="doc-badge-source ${srcClass}">${escapeHtml(d.sumber)}</span>
                                        <span class="card-category-badge" style="font-size:0.7rem;padding:0.15rem 0.5rem;">${escapeHtml(d.kategori)}</span>
                                    </div>
                                </td>
                                <td style="font-size:0.8rem;color:var(--text-muted, #64748B);white-space:nowrap;">${escapeHtml(d.formatted_date)}</td>
                                <td style="text-align:center;">${actionHtml}</td>
                            </tr>`;
                        });

                        tableBody.innerHTML = rowsHtml;

                        // 5. Update Pagination Bar
                        if (totalPages > 1) {
                            if (paginationWrap) paginationWrap.style.display = 'flex';
                            if (pageSummary) pageSummary.innerHTML = `Halaman <strong>${state.page}</strong> dari <strong>${totalPages}</strong> (Total ${total} dokumen)`;

                            let pagHtml = '';
                            if (state.page > 1) {
                                pagHtml += `<button type="button" class="page-btn" data-page="${state.page - 1}" title="Sebelumnya">&laquo;</button>`;
                            }

                            const startP = Math.max(1, state.page - 2);
                            const endP = Math.min(totalPages, state.page + 2);

                            if (startP > 1) {
                                pagHtml += `<button type="button" class="page-btn" data-page="1">1</button>`;
                                if (startP > 2) pagHtml += `<span class="px-2 align-self-center text-muted" style="font-weight:bold;">...</span>`;
                            }

                            for (let p = startP; p <= endP; p++) {
                                pagHtml += `<button type="button" class="page-btn ${p === state.page ? 'active' : ''}" data-page="${p}">${p}</button>`;
                            }

                            if (endP < totalPages) {
                                if (endP < totalPages - 1) pagHtml += `<span class="px-2 align-self-center text-muted" style="font-weight:bold;">...</span>`;
                                pagHtml += `<button type="button" class="page-btn" data-page="${totalPages}">${totalPages}</button>`;
                            }

                            if (state.page < totalPages) {
                                pagHtml += `<button type="button" class="page-btn" data-page="${state.page + 1}" title="Selanjutnya">&raquo;</button>`;
                            }

                            if (paginationList) paginationList.innerHTML = pagHtml;
                        } else {
                            if (paginationWrap) paginationWrap.style.display = 'none';
                        }
                    }

                    // 6. Update URL if not in iframe builder
                    if (window.self === window.top) {
                        try {
                            const url = new URL(window.location.href);
                            if (state.q) url.searchParams.set('q', state.q);
                            else url.searchParams.delete('q');

                            if (state.sumber) url.searchParams.set('sumber', state.sumber);
                            else url.searchParams.delete('sumber');

                            if (state.page > 1) url.searchParams.set('page', state.page);
                            else url.searchParams.delete('page');

                            window.history.replaceState({}, '', url.toString());
                        } catch(e) {}
                    }
                }

                // Event Listeners
                let debounceTimer = null;
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        state.q = this.value;
                        state.page = 1;
                        render();
                    }, 120);
                });

                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        state.q = '';
                        state.page = 1;
                        render();
                        searchInput.focus();
                    });
                }

                sourceBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        sourceBtns.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        state.sumber = this.dataset.sumber || '';
                        state.page = 1;
                        render();
                    });
                });

                if (resetAllBtn) {
                    resetAllBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        state.q = '';
                        state.sumber = '';
                        state.page = 1;
                        sourceBtns.forEach(b => {
                            if (b.dataset.sumber === '') b.classList.add('active');
                            else b.classList.remove('active');
                        });
                        render();
                    });
                }

                if (emptyResetBtn) {
                    emptyResetBtn.addEventListener('click', function() {
                        if (resetAllBtn) resetAllBtn.click();
                    });
                }

                document.addEventListener('click', function(e) {
                    const pageBtn = e.target.closest('.page-btn');
                    if (pageBtn && pageBtn.dataset.page) {
                        e.preventDefault();
                        state.page = parseInt(pageBtn.dataset.page, 10);
                        render();
                        const card = document.getElementById('docTableCard');
                        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
            </script>
            <?php
            break;
    }
}
