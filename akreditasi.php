<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Akreditasi Institusi & Program Studi';
$meta_desc  = 'Status Akreditasi Institusi dan Program Studi Universitas Katolik Soegijapranata (UNIKA) oleh BAN-PT dan Lembaga Akreditasi Mandiri (LAM).';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/akreditasi-sections.php';

$akreditasi_banner_sub = getPengaturan('akreditasi_banner_sub', '');
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pengakuan Mutu Nasional
        </div>
        <h1 class="page-banner-title">Akreditasi Institusi &amp; Program Studi</h1>
        <?php if ($akreditasi_banner_sub): ?>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;"><?= htmlspecialchars($akreditasi_banner_sub) ?></p>
        <?php endif; ?>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Akreditasi</span>
        </div>
    </div>
</div>

<?php
// Ambil susunan seksi dari Visual Page Builder
$akreditasi_blocks = null;
try {
    $stmt_ak = $db->query("SELECT blocks_json FROM pages WHERE slug = 'akreditasi'");
    $row_ak = $stmt_ak->fetch();
    if (!empty($row_ak['blocks_json'])) {
        $akreditasi_blocks = json_decode($row_ak['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($akreditasi_blocks) && is_array($akreditasi_blocks)) {
    foreach ($akreditasi_blocks as $block) {
        if (isset($block['is_visible']) && !$block['is_visible']) continue;
        renderAkreditasiSection($block['type'], $block);
    }
} else {
    // Alur Default Seksi Akreditasi
    renderAkreditasiSection('akreditasi_institusi');
    renderAkreditasiSection('akreditasi_statistik');
    renderAkreditasiSection('akreditasi_lam');
    renderAkreditasiSection('akreditasi_prodi');
    renderAkreditasiSection('akreditasi_faq');
}

// Render Modal Dokumen Institusi & Modal Dokumen Masing-masing LAM
renderAkreditasiModals();
?>

<!-- Styling Tambahan Khusus Accordion & Filter -->
<style>
.accordion-lpm .accordion-button {
    font-weight: 700;
    color: var(--navy);
    transition: all 0.2s ease;
}
.accordion-lpm .accordion-button:not(.collapsed) {
    background-color: #f1f5f9 !important;
    color: var(--navy);
}
.accordion-lpm .accordion-button:focus {
    box-shadow: none;
}
.btn-filter {
    background: #ffffff;
    border: 1px solid #CBD5E1;
    color: #475569;
    border-radius: 20px;
    padding: 0.35rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.15s ease;
    cursor: pointer;
}
.btn-filter:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
    color: #0F172A;
}
.btn-filter.active {
    background: var(--navy) !important;
    border-color: var(--navy) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(10,25,47,0.25);
}
</style>

<!-- JavaScript Interaktif untuk Search Bar & Filter Prodi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('prodiSearchInput');
    const clearSearchBtn = document.getElementById('prodiClearSearch');
    const resetSearchBtn = document.getElementById('btnResetSearch');
    const filterButtons = document.querySelectorAll('.btn-filter');
    const counterBadge = document.getElementById('prodiCounterBadge');
    const toggleAllBtn = document.getElementById('btnToggleAllAccordions');
    const toggleAllText = document.getElementById('toggleAllText');
    const noResultsAlert = document.getElementById('noProdiFoundAlert');
    const fakultasItems = document.querySelectorAll('.fakultas-accordion-item');

    const totalProdiRows = document.querySelectorAll('.prodi-row').length;
    let currentFilter = 'all';
    let areAllExpanded = false;

    function applyFilterAndSearch() {
        if (!searchInput) return;
        const query = searchInput.value.toLowerCase().trim();
        
        // Tampilkan/sembunyikan tombol clear
        if (clearSearchBtn) {
            if (query.length > 0) {
                clearSearchBtn.classList.remove('d-none');
            } else {
                clearSearchBtn.classList.add('d-none');
            }
        }

        let totalVisibleProdi = 0;

        fakultasItems.forEach(function(item) {
            const rows = item.querySelectorAll('.prodi-row');
            let visibleInFakultas = 0;

            rows.forEach(function(row) {
                const prodiName = row.getAttribute('data-prodi') || '';
                const fakultasName = row.getAttribute('data-fakultas') || '';
                const strata = row.getAttribute('data-strata') || '';
                const peringkat = row.getAttribute('data-peringkat') || '';
                const lembaga = row.getAttribute('data-lembaga') || '';
                const nosk = row.getAttribute('data-nosk') || '';

                // Cek query pencarian
                const matchSearch = (query === '') || 
                    prodiName.includes(query) || 
                    fakultasName.includes(query) || 
                    strata.includes(query) || 
                    peringkat.includes(query) || 
                    lembaga.includes(query) || 
                    nosk.includes(query);

                // Cek filter cepat
                let matchFilter = false;
                if (currentFilter === 'all') {
                    matchFilter = true;
                } else if (currentFilter === 's1') {
                    matchFilter = strata.includes('s1') || strata.includes('sarjana');
                } else if (currentFilter === 's2') {
                    matchFilter = strata.includes('s2') || strata.includes('magister');
                } else if (currentFilter === 's3') {
                    matchFilter = strata.includes('s3') || strata.includes('doktor');
                } else if (currentFilter === 'profesi') {
                    matchFilter = strata.includes('profesi');
                } else {
                    matchFilter = peringkat.includes(currentFilter);
                }

                if (matchSearch && matchFilter) {
                    row.style.display = '';
                    visibleInFakultas++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update badge jumlah prodi di header fakultas
            const countBadge = item.querySelector('.prodi-count-badge');
            if (countBadge) {
                countBadge.textContent = visibleInFakultas + ' Prodi';
            }

            const collapseEl = item.querySelector('.accordion-collapse');

            if (visibleInFakultas > 0) {
                item.style.display = '';
                totalVisibleProdi += visibleInFakultas;

                // Jika sedang melakukan pencarian atau filter aktif, buka accordion yang memiliki hasil
                if (query.length > 0 || currentFilter !== 'all') {
                    if (collapseEl && !collapseEl.classList.contains('show') && typeof bootstrap !== 'undefined') {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                        bsCollapse.show();
                    }
                }
            } else {
                item.style.display = 'none';
            }
        });

        // Update badge total
        if (counterBadge) {
            counterBadge.textContent = 'Menampilkan ' + totalVisibleProdi + ' dari ' + totalProdiRows + ' Prodi';
        }

        // Tampilkan/sembunyikan alert jika tidak ada hasil
        if (noResultsAlert) {
            if (totalVisibleProdi === 0) {
                noResultsAlert.classList.remove('d-none');
            } else {
                noResultsAlert.classList.add('d-none');
            }
        }
    }

    // Event listener search input
    if (searchInput) {
        searchInput.addEventListener('input', applyFilterAndSearch);
    }

    // Tombol clear search
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            applyFilterAndSearch();
            searchInput.focus();
        });
    }

    // Tombol reset search di alert
    if (resetSearchBtn) {
        resetSearchBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            currentFilter = 'all';
            filterButtons.forEach(btn => btn.classList.remove('active'));
            const defaultFilterBtn = document.querySelector('.btn-filter[data-filter="all"]');
            if (defaultFilterBtn) defaultFilterBtn.classList.add('active');
            applyFilterAndSearch();
        });
    }

    // Event filter chips
    filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            applyFilterAndSearch();
        });
    });

    // Tombol buka/tutup semua accordion
    if (toggleAllBtn) {
        toggleAllBtn.addEventListener('click', function() {
            areAllExpanded = !areAllExpanded;
            fakultasItems.forEach(function(item) {
                if (item.style.display !== 'none') {
                    const collapseEl = item.querySelector('.accordion-collapse');
                    if (collapseEl && typeof bootstrap !== 'undefined') {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                        if (areAllExpanded) {
                            bsCollapse.show();
                        } else {
                            bsCollapse.hide();
                        }
                    }
                }
            });
            if (toggleAllText) toggleAllText.textContent = areAllExpanded ? 'Tutup Semua' : 'Buka Semua';
            const icon = toggleAllBtn.querySelector('i');
            if (icon) {
                icon.className = areAllExpanded ? 'bi bi-arrows-collapse me-1' : 'bi bi-arrows-expand me-1';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
