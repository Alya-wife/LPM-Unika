<!-- Modal PDF Viewer (Universal Preview Modal) -->
<div class="modal fade" id="modalPdfViewer" tabindex="-1" aria-labelledby="modalPdfTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:92vw;margin:1.5rem auto;">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.2);box-shadow:0 25px 60px rgba(10,25,47,0.35);background:#ffffff;">
            <!-- Modal Header -->
            <div class="modal-header" style="background:linear-gradient(135deg, #0A192F 0%, #172A45 100%);color:#ffffff;padding:0.9rem 1.4rem;border-bottom:2px solid #FBBF24;">
                <div class="d-flex align-items-center gap-2 overflow-hidden me-3" style="min-width:0;">
                    <div style="width:34px;height:34px;background:rgba(251,191,36,0.15);border:1px solid rgba(251,191,36,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#FBBF24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:0.7rem;color:#FBBF24;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">Pratinjau Dokumen LPM</div>
                        <h5 class="modal-title text-truncate" id="modalPdfTitle" style="font-family:var(--font-heading);font-weight:700;font-size:0.98rem;color:#ffffff;margin:0;">
                            Nama Dokumen
                        </h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a href="#" id="modalPdfOpenNewTab" target="_blank" class="btn btn-sm d-none d-sm-inline-flex align-items-center gap-1" style="background:rgba(255,255,255,0.12);color:#ffffff;border:1px solid rgba(255,255,255,0.25);font-size:0.78rem;font-weight:600;padding:0.38rem 0.8rem;border-radius:6px;text-decoration:none;" title="Buka di Tab Baru">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                        <span>Tab Baru</span>
                    </a>
                    <a href="#" id="modalPdfDownload" download class="btn btn-sm d-inline-flex align-items-center gap-1" style="background:#FBBF24;color:#4A148C;font-weight:700;border:none;font-size:0.78rem;padding:0.38rem 0.85rem;border-radius:6px;text-decoration:none;" title="Simpan / Unduh Berkas">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span>Unduh</span>
                    </a>
                    <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Tutup" style="opacity:0.85;"></button>
                </div>
            </div>

            <!-- Modal Body (PDF iFrame) -->
            <div class="modal-body p-0" style="background:#323639;height:80vh;position:relative;">
                <div id="modalPdfLoading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#ffffff;background:#323639;z-index:1;font-size:0.9rem;gap:0.5rem;">
                    <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                    <span>Memuat dokumen PDF...</span>
                </div>
                <iframe id="modalPdfIframe" src="" style="width:100%;height:100%;border:none;" title="Pratinjau PDF" onload="document.getElementById('modalPdfLoading').style.display='none';"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openPdfViewer(fileUrl, docTitle) {
    var modalEl = document.getElementById('modalPdfViewer');
    if (!modalEl) {
        window.open(fileUrl, '_blank');
        return;
    }
    document.getElementById('modalPdfTitle').textContent = docTitle || 'Pratinjau Dokumen';
    document.getElementById('modalPdfLoading').style.display = 'flex';
    document.getElementById('modalPdfIframe').src = fileUrl;
    document.getElementById('modalPdfOpenNewTab').href = fileUrl;
    document.getElementById('modalPdfDownload').href = fileUrl;

    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('modalPdfViewer');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalPdfIframe').src = '';
            document.getElementById('modalPdfLoading').style.display = 'flex';
        });
    }
});
</script>
