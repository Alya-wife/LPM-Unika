<?php
/**
 * Modular Sections untuk Halaman Mutu & Data
 * Terintegrasi dengan Visual Page Builder (advance-setting.php) dan mutu-data.php publik.
 */

function renderMutuDataSection($type, $block = [], $is_builder = false) {
    $bg = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    switch ($type) {
        case 'mutu_data_dashboard':
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-7">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Sistem Data Terpadu') ?>
                            </span>
                            <h2 class="section-title mb-3"><?= htmlspecialchars($block['title'] ?? 'Dashboard Mutu & Indikator Kinerja') ?></h2>
                            <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                                <?= nl2br(htmlspecialchars($block['subtitle'] ?? 'Pengelolaan mutu berbasis data (data-driven quality assurance) merupakan pilar utama Lembaga Penjaminan Mutu UNIKA dalam memonitor pencapaian Standar Nasional Pendidikan Tinggi (SN-Dikti) serta Indikator Kinerja Utama (IKU) universitas.')) ?>
                            </p>
                            <p style="color:var(--text-muted);line-height:1.75;font-size:0.95rem;margin-bottom:1.5rem;">
                                Seluruh pelaporan disinkronkan secara berkala dengan Pangkalan Data Pendidikan Tinggi (PDDikti) Kementerian Pendidikan Tinggi, Sains, dan Teknologi untuk menjamin transparansi dan akuntabilitas publik.
                            </p>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--purple);">
                                        <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Indikator Kinerja Utama (IKU)</div>
                                        <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">8 IKU Kemendikti Saintek &amp; IKT khusus UNIKA terukur secara berkala.</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid #1565C0;">
                                        <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Sinkronisasi PDDikti</div>
                                        <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">Kepatuhan 100% pelaporan semester ganjil dan genap setiap tahun.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Metrik Card -->
                        <div class="col-lg-5">
                            <div class="card-lpm p-4" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;border-radius:var(--radius-lg);box-shadow:0 12px 35px rgba(10,25,47,0.18);">
                                <h5 style="font-family:var(--font-heading);font-weight:700;color:#fff;margin-bottom:1.25rem;">Ringkasan Capaian Mutu</h5>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                                    <span style="font-size:0.88rem;color:rgba(255,255,255,0.8);">Kepatuhan Siklus SPMI</span>
                                    <span style="font-weight:800;color:#FFD54F;font-size:1.1rem;">100%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                                    <span style="font-size:0.88rem;color:rgba(255,255,255,0.8);">Keterlaksanaan AMI Tahunan</span>
                                    <span style="font-weight:800;color:#FFD54F;font-size:1.1rem;">100%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,0.1);">
                                    <span style="font-size:0.88rem;color:rgba(255,255,255,0.8);">Pelaporan PDDikti Tepat Waktu</span>
                                    <span style="font-weight:800;color:#4CAF50;font-size:1.1rem;">Optimal</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <span style="font-size:0.88rem;color:rgba(255,255,255,0.8);">Peringkat Institusi</span>
                                    <span style="font-weight:800;color:#FFD54F;font-size:1.1rem;"><?= e(getPengaturan('akred_institusi_peringkat', 'UNGGUL')) ?></span>
                                </div>

                                <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-primary w-100 justify-content-center" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;padding:0.75rem 1rem;font-size:0.9rem;text-decoration:none;">
                                    Permintaan Data Mutu &amp; Kinerja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'mutu_data_iku':
            $iku_items = [
                ['IKU 1', 'Lulusan Mendapat Pekerjaan Layak', 'Persentase lulusan yang langsung bekerja, berwirausaha, atau studi lanjut dalam waktu < 6 bulan.'],
                ['IKU 2', 'Mahasiswa Berkegiatan di Luar Kampus', 'Mahasiswa aktif magang industri, pertukaran pelajar, proyek desa, dan riset independen.'],
                ['IKU 3', 'Dosen Berkegiatan di Luar Kampus', 'Dosen berkegiatan tridharma di industri atau perguruan tinggi mitra terkemuka.'],
                ['IKU 4', 'Praktisi Mengajar di Dalam Kampus', 'Kehadiran praktisi dan pakar profesional mengajar di mata kuliah terstruktur.'],
                ['IKU 5', 'Hasil Kerja Dosen Digunakan Masyarakat', 'Karya ilmiah, paten, prototipe, dan pengabdian yang berdampak bagi publik.'],
                ['IKU 6', 'Program Studi Bekerja Sama dengan Mitra', 'Kemitraan kurikulum dan riset bersama mitra dunia usaha dan industri (DUDI).'],
                ['IKU 7', 'Kelas yang Kolaboratif & Partisipatif', 'Metode pembelajaran berbasis kasus (Case Method) dan proyek (Team-based Project).'],
                ['IKU 8', 'Program Studi Berstandar Internasional', 'Perolehan sertifikasi dan akreditasi internasional bagi program studi.'],
            ];
            ?>
            <section class="py-5" style="background:var(--bg-main);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Tolok Ukur Mutu') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? '8 Indikator Kinerja Utama (IKU)') ?></h2>
                        <p class="section-desc mx-auto"><?= htmlspecialchars($block['subtitle'] ?? 'Sasaran strategis pemantauan mutu pendidikan tinggi di lingkungan UNIKA Soegijapranata.') ?></p>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($iku_items as $iku): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card-lpm p-4 h-100" style="background:#ffffff;border:1px solid var(--border);border-top:3px solid var(--purple);border-radius:var(--radius-md);box-shadow:0 2px 10px rgba(0,0,0,0.03);">
                                <span class="card-category-badge mb-2" style="font-weight:800;"><?= $iku[0] ?></span>
                                <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;margin-bottom:0.5rem;line-height:1.4;">
                                    <?= $iku[1] ?>
                                </h5>
                                <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.55;margin:0;">
                                    <?= $iku[2] ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
