<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Visual Builder Bagan Struktur Organisasi';

// Handle AJAX Save & Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_POST['action'] === 'save_struktur') {
        $json_str = $_POST['data'] ?? '';
        $decoded = json_decode($json_str, true);
        if (!$decoded || !isset($decoded['nodes']) || !is_array($decoded['nodes'])) {
            echo json_encode(['success' => false, 'message' => 'Format data bagan tidak valid.']);
            exit;
        }

        // Simpan JSON bagan ke pengaturan
        setPengaturan('struktur_organisasi_json', json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        setPengaturan('struktur_organisasi_mode', 'custom');

        // Sinkronisasi otomatis jika ada node rektor & wr
        foreach ($decoded['nodes'] as $n) {
            if (($n['id'] ?? '') === 'node_rektor') {
                if (!empty($n['title'])) setPengaturan('struktur_rektor_jabatan', trim($n['title']));
                if (!empty($n['subtitle'])) setPengaturan('struktur_rektor_nama', trim($n['subtitle']));
            } elseif (($n['id'] ?? '') === 'node_wr') {
                if (!empty($n['title'])) setPengaturan('struktur_wr_jabatan', trim($n['title']));
                if (!empty($n['subtitle'])) setPengaturan('struktur_wr_nama', trim($n['subtitle']));
            }
        }

        echo json_encode(['success' => true, 'message' => 'Susunan bagan struktur organisasi berhasil disimpan!']);
        exit;
    }

    if ($_POST['action'] === 'reset_default') {
        $default_data = getDefaultStrukturOrganisasiData();
        setPengaturan('struktur_organisasi_json', json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        setPengaturan('struktur_organisasi_mode', 'preset');

        echo json_encode([
            'success' => true,
            'message' => 'Bagan organisasi berhasil direset ke susunan resmi standar.',
            'data' => $default_data
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}

$organisasi_data = getStrukturOrganisasiData();
$organisasi_json = json_encode($organisasi_data, JSON_UNESCAPED_UNICODE);

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
/* Builder Workspace & Canvas Styles */
.chart-builder-container {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(10, 25, 47, 0.05);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.builder-toolbar {
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    padding: 0.85rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.canvas-outer-wrap {
    position: relative;
    width: 100%;
    height: 720px;
    background-color: #F8FAFC;
    background-image: radial-gradient(#CBD5E1 1px, transparent 1px);
    background-size: 20px 20px;
    overflow: auto;
    cursor: default;
    user-select: none;
}

.canvas-board {
    position: relative;
    width: 1120px;
    height: 760px;
    min-width: 100%;
    transform-origin: 0 0;
}

.canvas-svg-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 5;
}

.canvas-svg-layer path {
    pointer-events: stroke;
    cursor: pointer;
    transition: stroke 0.15s, stroke-width 0.15s;
}

.canvas-svg-layer path:hover {
    stroke: #EF4444 !important;
    stroke-width: 4px !important;
}

/* Node / Box Styles */
.chart-node {
    position: absolute;
    z-index: 10;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 1.15rem;
    border-radius: 10px;
    font-family: inherit;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    cursor: grab;
    transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s;
    user-select: none;
    box-sizing: border-box;
}

.chart-node:active {
    cursor: grabbing;
}

.chart-node.is-dragging {
    z-index: 30;
    box-shadow: 0 12px 28px rgba(10, 25, 47, 0.22);
    opacity: 0.95;
}

.chart-node.is-selected {
    outline: 3px solid #3B82F6 !important;
    outline-offset: 3px;
    box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.25);
}

.node-title {
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 2px;
    pointer-events: none;
}

.node-subtitle {
    font-size: 0.72rem;
    font-weight: 500;
    opacity: 0.88;
    line-height: 1.2;
    pointer-events: none;
}

/* Preset Themes */
.theme-top {
    background: linear-gradient(135deg, #0A192F, #1E3A8A);
    color: #ffffff;
    border: 2px solid #1E3A8A;
}
.theme-wr {
    background: linear-gradient(135deg, #1E3A8A, #1565C0);
    color: #ffffff;
    border: 2px solid #1565C0;
}
.theme-ka {
    background: linear-gradient(135deg, #7B1FA2, #6A1B9A);
    color: #ffffff;
    border: 2px solid #6A1B9A;
}
.theme-pusat {
    background: #EEF2FF;
    color: #1E3A8A;
    border: 2px solid #C7D2FE;
}
.theme-staff {
    background: #FDF4FF;
    color: #6A1B9A;
    border: 1.5px solid #E879F9;
}
.theme-sub {
    background: #F0FDF4;
    color: #166534;
    border: 1.5px solid #86EFAC;
}
.theme-auditor {
    background: #F8FAFC;
    color: #475569;
    border: 1.5px dashed #94A3B8;
}

/* 4 Directional Connector Handles (Top, Bottom, Left, Right) */
.node-port {
    position: absolute;
    width: 14px;
    height: 14px;
    background: #ffffff;
    border: 2px solid #3B82F6;
    border-radius: 50%;
    cursor: crosshair;
    z-index: 20;
    opacity: 0;
    transition: opacity 0.15s, transform 0.15s, background-color 0.15s, box-shadow 0.15s;
}

.chart-node:hover .node-port,
.chart-node.is-selected .node-port,
body.is-connecting-mode .node-port {
    opacity: 0.85;
}

.node-port:hover {
    opacity: 1 !important;
    transform: scale(1.4) !important;
    background: #2563EB !important;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.35);
}

.port-top    { top: -7px; left: 50%; transform: translateX(-50%); }
.port-bottom { bottom: -7px; left: 50%; transform: translateX(-50%); }
.port-left   { left: -7px; top: 50%; transform: translateY(-50%); }
.port-right  { right: -7px; top: 50%; transform: translateY(-50%); }

/* Action buttons on node hover */
.node-actions-quick {
    position: absolute;
    top: -12px;
    right: -10px;
    display: none;
    gap: 3px;
    z-index: 25;
}
.chart-node:hover .node-actions-quick,
.chart-node.is-selected .node-actions-quick {
    display: flex;
}
.btn-node-quick {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid #CBD5E1;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-node-quick:hover {
    background: #1E3A8A;
    color: #ffffff;
    border-color: #1E3A8A;
}
.btn-node-delete:hover {
    background: #EF4444 !important;
    color: #ffffff !important;
    border-color: #EF4444 !important;
}

/* Connect Mode Banner */
#connect-banner {
    display: none;
    background: #FEF3C7;
    border-bottom: 2px solid #F59E0B;
    color: #92400E;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.6rem 1.25rem;
    align-items: center;
    justify-content: space-between;
}

/* Selected Node Alignment Bar */
.selected-node-bar {
    background: #EFF6FF;
    border-bottom: 2px solid #3B82F6;
    color: #1E3A8A;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.5rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.3rem;font-weight:700;color:var(--navy);margin:0;">
            <i class="bi bi-diagram-3-fill me-2 text-primary"></i>Visual Builder Bagan Struktur Organisasi
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Geser kotak bebas (dengan auto-snap magnetik), klik blok untuk meratakan otomatis, tarik garis rapi, lalu klik Simpan.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= SITE_URL ?>/profil.php?view=struktur" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-eye"></i> Lihat di Web
        </a>
        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" onclick="resetToDefaultLayout()" style="border-radius:8px;">
            <i class="bi bi-arrow-counterclockwise"></i> Reset ke Standar
        </button>
        <button type="button" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 fw-bold px-3" onclick="saveChartData()" style="border-radius:8px;">
            <i class="bi bi-check2-circle"></i> Simpan Susunan Bagan
        </button>
    </div>
</div>

<!-- Banner Connect Mode -->
<div id="connect-banner">
    <div class="d-flex align-items-center gap-2">
        <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
        <span>Mode Tarik Garis Aktif: Klik <strong>port/kotak tujuan</strong> untuk menghubungkan garis dari <span id="connect-source-title" class="badge bg-warning text-dark"></span></span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2 fw-bold" onclick="cancelConnectMode()">Batal (ESC)</button>
</div>

<!-- Main Builder Container -->
<div class="chart-builder-container mb-4">
    <!-- Toolbar -->
    <div class="builder-toolbar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" onclick="openAddNodeModal()" style="border-radius:6px;font-size:0.82rem;font-weight:600;">
                <i class="bi bi-plus-circle"></i> Tambah Kotak / Blok
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" onclick="openConnectModal()" style="border-radius:6px;font-size:0.82rem;font-weight:600;">
                <i class="bi bi-bezier2"></i> Hubungkan Garis Blok
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" onclick="openLinesListModal()" style="border-radius:6px;font-size:0.82rem;">
                <i class="bi bi-list-check"></i> Kelola Garis (<span id="count-lines">0</span>)
            </button>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="form-check form-switch m-0" style="font-size:0.82rem;" title="Otomatis magnetik snap ke blok lain saat digeser">
                <input class="form-check-input" type="checkbox" id="snap-magnetic" checked>
                <label class="form-check-label fw-semibold text-secondary" for="snap-magnetic"><i class="bi bi-magnet text-primary"></i> Snap Magnetik</label>
            </div>
            <div class="form-check form-switch m-0" style="font-size:0.82rem;">
                <input class="form-check-input" type="checkbox" id="snap-grid" checked>
                <label class="form-check-label fw-semibold text-secondary" for="snap-grid">Grid (10px)</label>
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="zoomCanvas(0.1)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                <button type="button" class="btn btn-outline-secondary" onclick="zoomCanvas(-0.1)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetZoom()" title="Reset Zoom"><i class="bi bi-aspect-ratio"></i> 100%</button>
            </div>
        </div>
    </div>

    <!-- Selected Node Alignment Action Bar -->
    <div id="selected-node-bar" class="selected-node-bar" style="display:none;">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white"><i class="bi bi-bounding-box"></i> Blok Terpilih:</span>
            <span id="sel-node-title" class="fw-bold text-dark"></span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-light border shadow-sm px-2 py-1 text-primary fw-bold" onclick="alignSelectedNodeY()" title="Ratakan koordinat Y agar sejajar horizontal dengan blok tetangga terdekat">
                <i class="bi bi-distribute-horizontal me-1"></i> Ratakan Samping (Y)
            </button>
            <button type="button" class="btn btn-sm btn-light border shadow-sm px-2 py-1 text-primary fw-bold" onclick="alignSelectedNodeX()" title="Ratakan koordinat tengah X dengan blok induk / anak vertikal">
                <i class="bi bi-distribute-vertical me-1"></i> Ratakan Tengah (X)
            </button>
            <button type="button" class="btn btn-sm btn-light border shadow-sm px-2 py-1 text-secondary fw-semibold" onclick="alignRowLevel()" title="Samakan posisi Y seluruh blok yang ada di baris/level yang sama">
                <i class="bi bi-text-center me-1"></i> Ratakan Baris Ini
            </button>
            <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2" onclick="clearSelection()" title="Batal Pilih">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Canvas Workspace -->
    <div class="canvas-outer-wrap" id="canvas-outer">
        <div class="canvas-board" id="canvas-board">
            <svg class="canvas-svg-layer" id="svg-layer">
                <defs>
                    <marker id="arrow" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 1 L 8 5 L 0 9 z" fill="#94A3B8" />
                    </marker>
                </defs>
                <g id="guide-lines-layer"></g>
            </svg>
            <div id="nodes-container"></div>
        </div>
    </div>
</div>

<div class="alert alert-light border d-flex align-items-center gap-3 p-3 rounded-3" style="font-size:0.85rem;background:#F8FAFC;">
    <i class="bi bi-lightbulb-fill text-warning fs-4"></i>
    <div>
        <strong>Petunjuk Penarikan Garis:</strong>
        <ul class="m-0 ps-3 mt-1 text-muted">
            <li><strong>Tarik Garis:</strong> Klik salah satu bulatan port biru (Atas, Bawah, Kiri, atau Kanan) pada kotak asal, lalu klik bulatan port atau kotak tujuan.</li>
            <li><strong>Garis Saling Tumpuk / Cabang Rapi:</strong> Semua kotak anak yang ditarik dari bawah kotak induk yang sama otomatis membentuk <em>satu garis horizontal utama (trunk bus)</em> yang menyatu dan rapi.</li>
            <li><strong>Hapus Garis:</strong> Arahkan kursor ke garis penghubung (garis akan berwarna merah), lalu klik untuk menghapusnya, atau gunakan tombol <em>"Kelola Garis"</em>.</li>
        </ul>
    </div>
</div>

<!-- Modal: Edit / Tambah Node -->
<div class="modal fade" id="nodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-3 px-4 bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="nodeModalTitle" style="color:var(--navy);font-size:1.05rem;">Edit Blok Bagan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="nodeForm" onsubmit="event.preventDefault(); saveNodeForm();">
                    <input type="hidden" id="edit-node-id">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;color:var(--navy);">Judul / Nama Jabatan</label>
                        <input type="text" class="form-control" id="edit-node-title" placeholder="Contoh: Rektorat / Kepala LPM / Koordinator" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;color:var(--navy);">Nama Pejabat / Subtitle</label>
                        <input type="text" class="form-control" id="edit-node-subtitle" placeholder="Contoh: Dr. Nama Pejabat, M.Si. / (GPM Fakultas)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;color:var(--navy);">Tema Warna Kotak</label>
                        <select class="form-select" id="edit-node-theme">
                            <option value="top">Navy Gelap (Pimpinan Utama / Rektorat)</option>
                            <option value="wr">Royal Blue (Wakil Rektor)</option>
                            <option value="ka">Ungu Elegan (Kepala LPM)</option>
                            <option value="pusat">Soft Blue (Kepala Pusat / Sekretaris)</option>
                            <option value="staff">Soft Pink (Tata Usaha / Pendukung)</option>
                            <option value="sub">Hijau Muda (GPM Fakultas)</option>
                            <option value="auditor">Abu-abu Putus-Putus (Auditor Internal)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:0.8rem;">Lebar Kotak (px)</label>
                            <input type="number" class="form-control form-control-sm" id="edit-node-width" min="120" max="400" step="5">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:0.8rem;">Tinggi Kotak (px)</label>
                            <input type="number" class="form-control form-control-sm" id="edit-node-height" min="50" max="200" step="2">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete-node" onclick="deleteCurrentNode()">
                            <i class="bi bi-trash"></i> Hapus Blok
                        </button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            Simpan Blok
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Hubungkan Garis (Dropdown selector) -->
<div class="modal fade" id="connectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-3 px-4 bg-light border-bottom">
                <h5 class="modal-title fw-bold" style="color:var(--navy);font-size:1.05rem;">
                    <i class="bi bi-bezier2 me-1 text-primary"></i> Hubungkan Garis Antar Blok
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="connectForm" onsubmit="event.preventDefault(); saveConnectionForm();">
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-bold" style="font-size:0.85rem;">Blok Asal (Pangkal)</label>
                            <select class="form-select" id="conn-from-node" required></select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold" style="font-size:0.85rem;">Port Asal</label>
                            <select class="form-select" id="conn-from-port">
                                <option value="auto">Otomatis</option>
                                <option value="bottom" selected>Bawah</option>
                                <option value="top">Atas</option>
                                <option value="right">Kanan</option>
                                <option value="left">Kiri</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center my-1 text-muted">
                        <i class="bi bi-arrow-down fs-5"></i>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-bold" style="font-size:0.85rem;">Blok Tujuan (Ujung)</label>
                            <select class="form-select" id="conn-to-node" required></select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold" style="font-size:0.85rem;">Port Tujuan</label>
                            <select class="form-select" id="conn-to-port">
                                <option value="auto">Otomatis</option>
                                <option value="top" selected>Atas</option>
                                <option value="bottom">Bawah</option>
                                <option value="left">Kiri</option>
                                <option value="right">Kanan</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Tipe Garis</label>
                        <select class="form-select" id="conn-style">
                            <option value="solid">Garis Lurus Solid (Utama / Komando)</option>
                            <option value="dashed">Garis Putus-Putus (Koordinasi / Ad-hoc)</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            Hubungkan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kelola Daftar Garis -->
<div class="modal fade" id="linesListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-3 px-4 bg-light border-bottom">
                <h5 class="modal-title fw-bold" style="color:var(--navy);font-size:1.05rem;">
                    <i class="bi bi-list-check me-1 text-primary"></i> Kelola Garis Penghubung
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="lines-list-container" class="list-group"></div>
            </div>
        </div>
    </div>
</div>

<script>
// Data Store
let chartData = <?= $organisasi_json ?>;
let isConnecting = false;
let connectSourceId = null;
let connectSourcePort = 'bottom';
let currentZoom = 1;
let draggedNode = null;
let dragOffset = { x: 0, y: 0 };

const canvasOuter = document.getElementById('canvas-outer');
const canvasBoard = document.getElementById('canvas-board');
const svgLayer    = document.getElementById('svg-layer');
const nodesContainer = document.getElementById('nodes-container');

// Inisialisasi
document.addEventListener('DOMContentLoaded', () => {
    renderCanvas();

    // Mouse move di canvas untuk preview wire saat tarik garis
    canvasOuter.addEventListener('mousemove', (e) => {
        if (!isConnecting || !connectSourceId) return;
        updatePreviewWire(e);
    });

    // Klik area kosong untuk membatalkan pilihan blok
    canvasOuter.addEventListener('click', (e) => {
        if (!e.target.closest('.chart-node') && !e.target.closest('.node-port') && !e.target.closest('#selected-node-bar')) {
            clearSelection();
        }
    });

    // ESC to cancel connecting
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (isConnecting) cancelConnectMode();
            else clearSelection();
        }
    });
});

function renderCanvas() {
    nodesContainer.innerHTML = '';
    
    // Sesuaikan ukuran canvas jika ada node di luar batas
    let maxX = 1100, maxY = 680;
    chartData.nodes.forEach(n => {
        if (n.x + n.width + 80 > maxX) maxX = n.x + n.width + 80;
        if (n.y + n.height + 80 > maxY) maxY = n.y + n.height + 80;
    });
    canvasBoard.style.width = maxX + 'px';
    canvasBoard.style.height = maxY + 'px';

    // Render nodes
    chartData.nodes.forEach(node => {
        const el = document.createElement('div');
        el.className = `chart-node theme-${node.theme || 'pusat'}`;
        el.id = `dom-${node.id}`;
        el.style.left = `${node.x}px`;
        el.style.top  = `${node.y}px`;
        el.style.width = `${node.width}px`;
        if (node.height) el.style.height = `${node.height}px`;

        el.innerHTML = `
            <div class="node-title">${escapeHtml(node.title)}</div>
            <div class="node-subtitle">${escapeHtml(node.subtitle || '')}</div>
            
            <!-- 4 Directional Ports -->
            <div class="node-port port-top" title="Port Atas" data-node="${node.id}" data-port="top"></div>
            <div class="node-port port-bottom" title="Port Bawah" data-node="${node.id}" data-port="bottom"></div>
            <div class="node-port port-left" title="Port Kiri" data-node="${node.id}" data-port="left"></div>
            <div class="node-port port-right" title="Port Kanan" data-node="${node.id}" data-port="right"></div>

            <div class="node-actions-quick">
                <button type="button" class="btn-node-quick" title="Ratakan Sejajar Samping (Y)" onclick="alignNodeWithNeighbor('${node.id}', event)">
                    <i class="bi bi-distribute-horizontal"></i>
                </button>
                <button type="button" class="btn-node-quick" title="Edit Blok" onclick="openEditNodeModal('${node.id}', event)">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button type="button" class="btn-node-quick" title="Tarik Garis" onclick="startConnectMode('${node.id}', 'auto', event)">
                    <i class="bi bi-bezier2"></i>
                </button>
                <button type="button" class="btn-node-quick btn-node-delete" title="Hapus Blok" onclick="deleteNodeById('${node.id}', event)">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;

        // Event listener port khusus (memastikan klik port langsung menyambung tanpa gagal)
        el.querySelectorAll('.node-port').forEach(portEl => {
            portEl.addEventListener('click', (e) => {
                e.stopPropagation();
                const portName = portEl.getAttribute('data-port');
                if (!isConnecting) {
                    startConnectMode(node.id, portName, e);
                } else {
                    finishConnect(node.id, portName);
                }
            });
        });

        // Event drag pada node
        el.addEventListener('mousedown', (e) => onNodeMouseDown(e, node));

        // Event klik pada bodi node
        el.addEventListener('click', (e) => {
            if (isConnecting) {
                e.stopPropagation();
                finishConnect(node.id, 'auto');
            } else {
                selectNode(node.id);
            }
        });

        nodesContainer.appendChild(el);
    });

    renderLines();
    updateLinesCount();
}

function getNodePortPos(node, portName) {
    const w = node.width || 180;
    const h = node.height || 68;
    switch (portName) {
        case 'top':    return { x: node.x + w / 2, y: node.y };
        case 'bottom': return { x: node.x + w / 2, y: node.y + h };
        case 'left':   return { x: node.x,         y: node.y + h / 2 };
        case 'right':  return { x: node.x + w,     y: node.y + h / 2 };
        default:       return { x: node.x + w / 2, y: node.y + h };
    }
}

function computeConnectorPath(a, b, line = {}) {
    const aW = a.width || 180;
    const aH = a.height || 68;
    const bW = b.width || 180;
    const bH = b.height || 68;

    let fromPort = line.fromPort || 'auto';
    let toPort   = line.toPort   || 'auto';

    // Auto-resolve jika auto
    if (fromPort === 'auto' || toPort === 'auto') {
        if (b.y >= a.y + aH - 12) {
            if (fromPort === 'auto') fromPort = 'bottom';
            if (toPort === 'auto')   toPort = 'top';
        } else if (b.y + bH <= a.y + 12) {
            if (fromPort === 'auto') fromPort = 'top';
            if (toPort === 'auto')   toPort = 'bottom';
        } else if (b.x >= a.x + aW - 10) {
            if (fromPort === 'auto') fromPort = 'right';
            if (toPort === 'auto')   toPort = 'left';
        } else if (b.x + bW <= a.x + 10) {
            if (fromPort === 'auto') fromPort = 'left';
            if (toPort === 'auto')   toPort = 'right';
        } else {
            if (fromPort === 'auto') fromPort = 'bottom';
            if (toPort === 'auto')   toPort = 'top';
        }
    }

    const p1 = getNodePortPos(a, fromPort);
    const p2 = getNodePortPos(b, toPort);

    // 1. Bottom to Top (Hirarki Standar - Mendukung Garis Bus Bersatu / Saling Tumpuk)
    if (fromPort === 'bottom' && toPort === 'top') {
        if (Math.abs(p1.x - p2.x) < 4) {
            return `M ${p1.x} ${p1.y} V ${p2.y}`;
        }
        // Garis horizontal konstan (branch bus) 25px di bawah node asal (atau tengah jika jarak sempit)
        const branchY = (p2.y > p1.y + 35) ? (p1.y + 25) : ((p1.y + p2.y) * 0.5);
        return `M ${p1.x} ${p1.y} V ${branchY} H ${p2.x} V ${p2.y}`;
    }

    // 2. Top to Bottom (Hirarki Terbalik)
    if (fromPort === 'top' && toPort === 'bottom') {
        if (Math.abs(p1.x - p2.x) < 4) {
            return `M ${p1.x} ${p1.y} V ${p2.y}`;
        }
        const branchY = (p1.y > p2.y + 35) ? (p1.y - 25) : ((p1.y + p2.y) * 0.5);
        return `M ${p1.x} ${p1.y} V ${branchY} H ${p2.x} V ${p2.y}`;
    }

    // 3. Right to Left (Mendatar ke Kanan)
    if (fromPort === 'right' && toPort === 'left') {
        if (Math.abs(p1.y - p2.y) < 4) {
            return `M ${p1.x} ${p1.y} H ${p2.x}`;
        }
        const midX = (p1.x + p2.x) * 0.5;
        return `M ${p1.x} ${p1.y} H ${midX} V ${p2.y} H ${p2.x}`;
    }

    // 4. Left to Right (Mendatar ke Kiri)
    if (fromPort === 'left' && toPort === 'right') {
        if (Math.abs(p1.y - p2.y) < 4) {
            return `M ${p1.x} ${p1.y} H ${p2.x}`;
        }
        const midX = (p1.x + p2.x) * 0.5;
        return `M ${p1.x} ${p1.y} H ${midX} V ${p2.y} H ${p2.x}`;
    }

    // 5. Bottom to Left / Bottom to Right
    if (fromPort === 'bottom' && (toPort === 'left' || toPort === 'right')) {
        return `M ${p1.x} ${p1.y} V ${p2.y} H ${p2.x}`;
    }

    // 6. Top to Left / Top to Right
    if (fromPort === 'top' && (toPort === 'left' || toPort === 'right')) {
        return `M ${p1.x} ${p1.y} V ${p2.y} H ${p2.x}`;
    }

    // 7. Right to Top / Right to Bottom
    if (fromPort === 'right' && (toPort === 'top' || toPort === 'bottom')) {
        return `M ${p1.x} ${p1.y} H ${p2.x} V ${p2.y}`;
    }

    // 8. Left to Top / Left to Bottom
    if (fromPort === 'left' && (toPort === 'top' || toPort === 'bottom')) {
        return `M ${p1.x} ${p1.y} H ${p2.x} V ${p2.y}`;
    }

    // Fallback orthogonal
    const midY = (p1.y + p2.y) * 0.5;
    return `M ${p1.x} ${p1.y} V ${midY} H ${p2.x} V ${p2.y}`;
}

function renderLines() {
    const defs = svgLayer.querySelector('defs');
    svgLayer.innerHTML = '';
    if (defs) svgLayer.appendChild(defs);

    // Preview wire
    const pw = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    pw.setAttribute('id', 'preview-wire');
    pw.setAttribute('stroke', '#3B82F6');
    pw.setAttribute('stroke-width', '2.5');
    pw.setAttribute('stroke-dasharray', '6,4');
    pw.setAttribute('fill', 'none');
    pw.style.display = 'none';
    svgLayer.appendChild(pw);

    chartData.lines.forEach((line) => {
        const fromNode = chartData.nodes.find(n => n.id === line.from);
        const toNode   = chartData.nodes.find(n => n.id === line.to);
        if (!fromNode || !toNode) return;

        const pathD = computeConnectorPath(fromNode, toNode, line);
        const pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        pathEl.setAttribute('d', pathD);
        pathEl.setAttribute('stroke', line.color || '#94A3B8');
        pathEl.setAttribute('stroke-width', '2');
        pathEl.setAttribute('fill', 'none');
        if (line.style === 'dashed') {
            pathEl.setAttribute('stroke-dasharray', '5,4');
        }
        pathEl.setAttribute('title', `Klik untuk menghapus garis: ${fromNode.title} -> ${toNode.title}`);
        pathEl.addEventListener('click', (e) => {
            e.stopPropagation();
            if (confirm(`Hapus garis koneksi dari "${fromNode.title}" ke "${toNode.title}"?`)) {
                chartData.lines = chartData.lines.filter(l => l.id !== line.id);
                renderLines();
                updateLinesCount();
            }
        });

        svgLayer.appendChild(pathEl);
    });
}

function updatePreviewWire(e) {
    const pw = document.getElementById('preview-wire');
    if (!pw || !connectSourceId) return;

    const srcNode = chartData.nodes.find(n => n.id === connectSourceId);
    if (!srcNode) return;

    const boardRect = canvasBoard.getBoundingClientRect();
    const curX = (e.clientX - boardRect.left) / currentZoom;
    const curY = (e.clientY - boardRect.top) / currentZoom;

    const p1 = getNodePortPos(srcNode, connectSourcePort || 'bottom');
    pw.setAttribute('d', `M ${p1.x} ${p1.y} L ${curX} ${curY}`);
    pw.style.display = 'block';
}

// Selection Management
let selectedNodeId = null;

function selectNode(nodeId) {
    selectedNodeId = nodeId;
    document.querySelectorAll('.chart-node').forEach(el => el.classList.remove('is-selected'));
    const el = document.getElementById(`dom-${nodeId}`);
    if (el) el.classList.add('is-selected');

    const node = chartData.nodes.find(n => n.id === nodeId);
    const selBar = document.getElementById('selected-node-bar');
    if (selBar && node) {
        document.getElementById('sel-node-title').textContent = node.title;
        selBar.style.display = 'flex';
    }
}

function clearSelection() {
    selectedNodeId = null;
    document.querySelectorAll('.chart-node').forEach(el => el.classList.remove('is-selected'));
    const selBar = document.getElementById('selected-node-bar');
    if (selBar) selBar.style.display = 'none';
}

// 1. Ratakan Horizontal (Y) dengan blok tetangga terdekat
function alignSelectedNodeY() {
    if (!selectedNodeId) return;
    alignNodeWithNeighbor(selectedNodeId);
}

function alignNodeWithNeighbor(nodeId, e) {
    if (e) e.stopPropagation();
    const cur = chartData.nodes.find(n => n.id === nodeId);
    if (!cur) return;

    let bestNeighbor = null;
    let minDist = Infinity;

    chartData.nodes.forEach(other => {
        if (other.id === cur.id) return;
        const diffY = Math.abs(other.y - cur.y);
        const diffX = Math.abs(other.x - cur.x);
        // Tetangga sebaris jika selisih Y <= 60px dan terdekat di X
        if (diffY <= 60 && diffX < minDist) {
            minDist = diffX;
            bestNeighbor = other;
        }
    });

    if (bestNeighbor) {
        cur.y = bestNeighbor.y;
        renderCanvas();
        selectNode(cur.id);
    } else {
        alert('Tidak ditemukan blok tetangga di baris yang sama untuk diratakan.');
    }
}

// 2. Ratakan Vertikal Center (X) dengan blok induk / anak
function alignSelectedNodeX() {
    if (!selectedNodeId) return;
    const cur = chartData.nodes.find(n => n.id === selectedNodeId);
    if (!cur) return;

    const curCenterX = cur.x + (cur.width || 180) / 2;
    let bestNeighbor = null;
    let minDiffY = Infinity;

    chartData.nodes.forEach(other => {
        if (other.id === cur.id) return;
        const otherCenterX = other.x + (other.width || 180) / 2;
        const diffX = Math.abs(otherCenterX - curCenterX);
        const diffY = Math.abs(other.y - cur.y);

        if (diffX <= 100 && diffY > 30 && diffY < minDiffY) {
            minDiffY = diffY;
            bestNeighbor = other;
        }
    });

    if (bestNeighbor) {
        const otherCenterX = bestNeighbor.x + (bestNeighbor.width || 180) / 2;
        cur.x = Math.round(otherCenterX - (cur.width || 180) / 2);
        renderCanvas();
        selectNode(cur.id);
    } else {
        alert('Tidak ditemukan blok vertikal (atas/bawah) untuk dijadikan acuan perataan tengah.');
    }
}

// 3. Ratakan Semua Blok di Level/Baris yang Sama
function alignRowLevel() {
    if (!selectedNodeId) return;
    const cur = chartData.nodes.find(n => n.id === selectedNodeId);
    if (!cur) return;

    const targetY = cur.y;
    const rowNodes = chartData.nodes.filter(n => Math.abs(n.y - targetY) <= 50);

    if (rowNodes.length > 1) {
        rowNodes.forEach(n => {
            n.y = targetY;
        });
        renderCanvas();
        selectNode(cur.id);
    }
}

// Smart Magnetic Snapping with Guide Lines
function updateSmartGuides(dragged, targetX, targetY) {
    const guideLayer = document.getElementById('guide-lines-layer');
    if (guideLayer) guideLayer.innerHTML = '';

    const snapMag = document.getElementById('snap-magnetic') ? document.getElementById('snap-magnetic').checked : true;
    if (!snapMag) return { x: targetX, y: targetY };

    let resX = targetX;
    let resY = targetY;
    const w = dragged.width || 180;
    const h = dragged.height || 66;
    const curCx = targetX + w / 2;
    const curCy = targetY + h / 2;

    const SNAP_DIST = 10;
    let guideY = null;
    let guideX = null;

    chartData.nodes.forEach(other => {
        if (other.id === dragged.id) return;
        const ow = other.width || 180;
        const oh = other.height || 66;
        const ocx = other.x + ow / 2;
        const ocy = other.y + oh / 2;

        // Snap Top Y
        if (Math.abs(targetY - other.y) < SNAP_DIST) {
            resY = other.y;
            guideY = other.y;
        }
        // Snap Center Y
        else if (Math.abs(curCy - ocy) < SNAP_DIST) {
            resY = Math.round(ocy - h / 2);
            guideY = ocy;
        }

        // Snap Center X
        if (Math.abs(curCx - ocx) < SNAP_DIST) {
            resX = Math.round(ocx - w / 2);
            guideX = ocx;
        }
        // Snap Left X
        else if (Math.abs(targetX - other.x) < SNAP_DIST) {
            resX = other.x;
            guideX = other.x;
        }
    });

    if (guideLayer) {
        if (guideY !== null) {
            const l = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            l.setAttribute('x1', '0');
            l.setAttribute('y1', guideY);
            l.setAttribute('x2', '2400');
            l.setAttribute('y2', guideY);
            l.setAttribute('stroke', '#3B82F6');
            l.setAttribute('stroke-width', '1.5');
            l.setAttribute('stroke-dasharray', '4,3');
            guideLayer.appendChild(l);
        }
        if (guideX !== null) {
            const l = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            l.setAttribute('x1', guideX);
            l.setAttribute('y1', '0');
            l.setAttribute('x2', guideX);
            l.setAttribute('y2', '2400');
            l.setAttribute('stroke', '#3B82F6');
            l.setAttribute('stroke-width', '1.5');
            l.setAttribute('stroke-dasharray', '4,3');
            guideLayer.appendChild(l);
        }
    }

    return { x: resX, y: resY };
}

// Drag & Drop Handling
function onNodeMouseDown(e, node) {
    if (e.target.closest('.node-actions-quick') || e.target.classList.contains('node-port')) return;
    if (isConnecting) return;

    selectNode(node.id);

    draggedNode = node;
    const domEl = document.getElementById(`dom-${node.id}`);
    domEl.classList.add('is-dragging');

    const rect = domEl.getBoundingClientRect();
    dragOffset.x = (e.clientX - rect.left) / currentZoom;
    dragOffset.y = (e.clientY - rect.top) / currentZoom;

    document.addEventListener('mousemove', onNodeMouseMove);
    document.addEventListener('mouseup', onNodeMouseUp);
}

function onNodeMouseMove(e) {
    if (!draggedNode) return;
    const boardRect = canvasBoard.getBoundingClientRect();
    let rawX = (e.clientX - boardRect.left) / currentZoom - dragOffset.x;
    let rawY = (e.clientY - boardRect.top) / currentZoom - dragOffset.y;

    const snap = document.getElementById('snap-grid') ? document.getElementById('snap-grid').checked : false;
    if (snap) {
        rawX = Math.round(rawX / 10) * 10;
        rawY = Math.round(rawY / 10) * 10;
    }

    // Apply Smart Magnetic Snapping with Neighbor Nodes
    const snapped = updateSmartGuides(draggedNode, rawX, rawY);
    let newX = snapped.x;
    let newY = snapped.y;

    if (newX < 10) newX = 10;
    if (newY < 10) newY = 10;

    draggedNode.x = Math.round(newX);
    draggedNode.y = Math.round(newY);

    const domEl = document.getElementById(`dom-${draggedNode.id}`);
    if (domEl) {
        domEl.style.left = `${draggedNode.x}px`;
        domEl.style.top  = `${draggedNode.y}px`;
    }

    renderLines();
}

function onNodeMouseUp() {
    const guideLayer = document.getElementById('guide-lines-layer');
    if (guideLayer) guideLayer.innerHTML = '';

    if (draggedNode) {
        const domEl = document.getElementById(`dom-${draggedNode.id}`);
        if (domEl) domEl.classList.remove('is-dragging');
        draggedNode = null;
    }
    document.removeEventListener('mousemove', onNodeMouseMove);
    document.removeEventListener('mouseup', onNodeMouseUp);
}

// Mode Tarik Garis (Connect Mode)
function startConnectMode(nodeId, portName = 'bottom', e) {
    if (e) e.stopPropagation();
    isConnecting = true;
    connectSourceId = nodeId;
    connectSourcePort = portName || 'bottom';
    document.body.classList.add('is-connecting-mode');

    const srcNode = chartData.nodes.find(n => n.id === nodeId);
    const pLabel = (connectSourcePort === 'top') ? 'Port Atas' : ((connectSourcePort === 'bottom') ? 'Port Bawah' : ((connectSourcePort === 'left') ? 'Port Kiri' : 'Port Kanan'));
    document.getElementById('connect-source-title').textContent = (srcNode ? srcNode.title : nodeId) + ` (${pLabel})`;
    document.getElementById('connect-banner').style.display = 'flex';

    document.querySelectorAll('.chart-node').forEach(el => el.classList.remove('is-selected'));
    const srcEl = document.getElementById(`dom-${nodeId}`);
    if (srcEl) srcEl.classList.add('is-selected');
}

function cancelConnectMode() {
    isConnecting = false;
    connectSourceId = null;
    connectSourcePort = 'bottom';
    document.body.classList.remove('is-connecting-mode');
    document.getElementById('connect-banner').style.display = 'none';
    document.querySelectorAll('.chart-node').forEach(el => el.classList.remove('is-selected'));
    const pw = document.getElementById('preview-wire');
    if (pw) pw.style.display = 'none';
}

function finishConnect(targetNodeId, targetPort = 'auto') {
    if (!isConnecting || !connectSourceId) return;

    if (targetNodeId === connectSourceId) {
        alert('Tidak dapat menghubungkan blok ke dirinya sendiri.');
        return;
    }

    // Cek apakah garis sudah ada (baik dari A->B atau B->A)
    const existing = chartData.lines.find(l => 
        (l.from === connectSourceId && l.to === targetNodeId) ||
        (l.from === targetNodeId && l.to === connectSourceId)
    );
    if (existing) {
        alert('Garis penghubung antara kedua blok ini sudah ada.');
        cancelConnectMode();
        return;
    }

    // Tambah garis baru
    const newLine = {
        id: 'line_' + Date.now(),
        from: connectSourceId,
        to: targetNodeId,
        style: 'solid',
        fromPort: connectSourcePort || 'auto',
        toPort: targetPort || 'auto'
    };

    chartData.lines.push(newLine);
    cancelConnectMode();
    renderLines();
    updateLinesCount();
}

// Modal Edit / Tambah Node
function openAddNodeModal() {
    document.getElementById('nodeModalTitle').textContent = 'Tambah Blok Baru';
    document.getElementById('edit-node-id').value = '';
    document.getElementById('edit-node-title').value = '';
    document.getElementById('edit-node-subtitle').value = '';
    document.getElementById('edit-node-theme').value = 'pusat';
    document.getElementById('edit-node-width').value = '180';
    document.getElementById('edit-node-height').value = '66';
    document.getElementById('btn-delete-node').style.display = 'none';

    new bootstrap.Modal(document.getElementById('nodeModal')).show();
}

function openEditNodeModal(nodeId, e) {
    if (e) e.stopPropagation();
    const node = chartData.nodes.find(n => n.id === nodeId);
    if (!node) return;

    document.getElementById('nodeModalTitle').textContent = 'Edit Blok: ' + node.title;
    document.getElementById('edit-node-id').value = node.id;
    document.getElementById('edit-node-title').value = node.title;
    document.getElementById('edit-node-subtitle').value = node.subtitle || '';
    document.getElementById('edit-node-theme').value = node.theme || 'pusat';
    document.getElementById('edit-node-width').value = node.width || 180;
    document.getElementById('edit-node-height').value = node.height || 66;
    document.getElementById('btn-delete-node').style.display = 'inline-block';

    new bootstrap.Modal(document.getElementById('nodeModal')).show();
}

function saveNodeForm() {
    const id = document.getElementById('edit-node-id').value;
    const title = document.getElementById('edit-node-title').value.trim();
    const subtitle = document.getElementById('edit-node-subtitle').value.trim();
    const theme = document.getElementById('edit-node-theme').value;
    const width = parseInt(document.getElementById('edit-node-width').value) || 180;
    const height = parseInt(document.getElementById('edit-node-height').value) || 66;

    if (!title) return;

    if (id) {
        const node = chartData.nodes.find(n => n.id === id);
        if (node) {
            node.title = title;
            node.subtitle = subtitle;
            node.theme = theme;
            node.width = width;
            node.height = height;
        }
    } else {
        const newId = 'node_' + Date.now();
        chartData.nodes.push({
            id: newId,
            title: title,
            subtitle: subtitle,
            theme: theme,
            x: 430,
            y: 340,
            width: width,
            height: height
        });
    }

    bootstrap.Modal.getInstance(document.getElementById('nodeModal')).hide();
    renderCanvas();
}

function deleteCurrentNode() {
    const id = document.getElementById('edit-node-id').value;
    if (!id) return;
    if (confirm('Yakin ingin menghapus blok ini beserta seluruh garis koneksinya?')) {
        deleteNodeById(id);
        bootstrap.Modal.getInstance(document.getElementById('nodeModal')).hide();
    }
}

function deleteNodeById(id, e) {
    if (e) e.stopPropagation();
    const node = chartData.nodes.find(n => n.id === id);
    if (!node) return;

    if (confirm(`Hapus blok "${node.title}"?`)) {
        chartData.nodes = chartData.nodes.filter(n => n.id !== id);
        chartData.lines = chartData.lines.filter(l => l.from !== id && l.to !== id);
        renderCanvas();
    }
}

// Modal Hubungkan Garis (Dropdown selector)
function openConnectModal() {
    const fromSel = document.getElementById('conn-from-node');
    const toSel   = document.getElementById('conn-to-node');
    fromSel.innerHTML = '';
    toSel.innerHTML = '';

    chartData.nodes.forEach(n => {
        const opt1 = document.createElement('option');
        opt1.value = n.id;
        opt1.textContent = `${n.title} (${n.subtitle || '-'})`;
        fromSel.appendChild(opt1);

        const opt2 = document.createElement('option');
        opt2.value = n.id;
        opt2.textContent = `${n.title} (${n.subtitle || '-'})`;
        toSel.appendChild(opt2);
    });

    if (toSel.options.length > 1) {
        toSel.selectedIndex = 1;
    }

    new bootstrap.Modal(document.getElementById('connectModal')).show();
}

function saveConnectionForm() {
    const from = document.getElementById('conn-from-node').value;
    const to   = document.getElementById('conn-to-node').value;
    const fromPort = document.getElementById('conn-from-port').value;
    const toPort   = document.getElementById('conn-to-port').value;
    const style    = document.getElementById('conn-style').value;

    if (from === to) {
        alert('Blok asal dan tujuan tidak boleh sama.');
        return;
    }

    chartData.lines.push({
        id: 'line_' + Date.now(),
        from: from,
        to: to,
        fromPort: fromPort,
        toPort: toPort,
        style: style
    });

    bootstrap.Modal.getInstance(document.getElementById('connectModal')).hide();
    renderLines();
    updateLinesCount();
}

// Modal Kelola Garis
function openLinesListModal() {
    const container = document.getElementById('lines-list-container');
    container.innerHTML = '';

    if (chartData.lines.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">Belum ada garis koneksi.</div>';
    } else {
        chartData.lines.forEach(l => {
            const f = chartData.nodes.find(n => n.id === l.from);
            const t = chartData.nodes.find(n => n.id === l.to);
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3';
            item.innerHTML = `
                <div>
                    <strong>${f ? f.title : l.from}</strong> [${l.fromPort || 'auto'}] &rarr; <strong>${t ? t.title : l.to}</strong> [${l.toPort || 'auto'}]
                    <div style="font-size:0.75rem;" class="text-muted mt-1">
                        Tipe: <span class="badge ${l.style === 'dashed' ? 'bg-secondary' : 'bg-primary'}">${l.style === 'dashed' ? 'Putus-Putus' : 'Lurus'}</span>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Ganti Tipe" onclick="toggleLineStyle('${l.id}')">
                        <i class="bi bi-arrow-repeat"></i> Ganti Tipe
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="deleteLineById('${l.id}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(item);
        });
    }

    new bootstrap.Modal(document.getElementById('linesListModal')).show();
}

function toggleLineStyle(lineId) {
    const l = chartData.lines.find(x => x.id === lineId);
    if (l) {
        l.style = (l.style === 'dashed') ? 'solid' : 'dashed';
        renderLines();
        openLinesListModal();
    }
}

function deleteLineById(lineId) {
    chartData.lines = chartData.lines.filter(l => l.id !== lineId);
    renderLines();
    updateLinesCount();
    openLinesListModal();
}

function updateLinesCount() {
    document.getElementById('count-lines').textContent = chartData.lines.length;
}

// Zoom Controls
function zoomCanvas(delta) {
    currentZoom = Math.max(0.5, Math.min(1.8, currentZoom + delta));
    canvasBoard.style.transform = `scale(${currentZoom})`;
}

function resetZoom() {
    currentZoom = 1;
    canvasBoard.style.transform = 'scale(1)';
}

// Simpan Data Bagan ke Server
function saveChartData() {
    const btn = event ? event.target.closest('button') : null;
    if (btn) btn.disabled = true;

    fetch('struktur-organisasi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'save_struktur',
            data: JSON.stringify(chartData)
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Berhasil! Susunan bagan struktur organisasi telah disimpan.');
        } else {
            alert('Gagal menyimpan: ' + res.message);
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan jaringan saat menyimpan.');
    })
    .finally(() => {
        if (btn) btn.disabled = false;
    });
}

// Reset ke Default Layout
function resetToDefaultLayout() {
    if (!confirm('Apakah Anda yakin ingin mengembalikan seluruh posisi kotak dan garis ke susunan standar resmi SCU LPM?')) {
        return;
    }

    fetch('struktur-organisasi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'reset_default' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            chartData = res.data;
            renderCanvas();
            alert('Bagan berhasil direset ke tata letak standar!');
        } else {
            alert('Gagal reset: ' + res.message);
        }
    })
    .catch(() => {
        alert('Terjadi kesalahan saat mereset bagan.');
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
