<?php
require_once __DIR__ . '/../config/database.php';

$pengantar = "Gugus Penjaminan Mutu merupakan organ fakultas yang dipimpin oleh seorang koordinator.\nDan jumlah anggota Gugus Penjaminan Mutu ditetapkan oleh Dekan dengan mempertimbangkan jumlah program studi yang dikelola.";

$tugas = "Membantu Dekan dalam melaksanakan sistem penjaminan mutu pada Fakultas dan Program Studi.";

$fungsi = "Pemberian saran dan rekomendasi kepada Dekan dalam perumusan kebijakan pengembangan pelaksanaan SPMI pada tingkat Fakultas
Pengoordinasian pelaksanaan SPMI pada Fakultas maupun Program Studi sesuai peraturan perundang-undangan maupun peraturan yang berlaku di Universitas
Fasilitasi penyusunan prosedur mutu, instruksi kerja maupun dokumen mutu lainnya dalam pelaksanaan SPMI
Penyusunan instrumen monitoring dan evaluasi yang bersifat khusus untuk Fakultas dan/atau Program Studi
Monitoring dan evaluasi pelaksanaan SPMI pada Fakultas maupun Program Studi
Pengoordinasian dalam pelaksanaan proses akreditasi Program Studi
Pengoordinasian dengan LPM dalam pelaksanaan SPMI pada Fakultas maupun pelaksanaan proses akreditasi Program Studi
Pengoordinasian pembukaan program pendidikan baru dalam memenuhi akreditasi minimal
Pelaporan pelaksanaan sistem penjaminan mutu tingkat Fakultas kepada Rektor melalui LPM.";

$dasar_hukum = "Buku Organisasi & Tata Kelola | Unika Soegijapranata (Pasal 31)";

setPengaturan('gpm_narasi_pengantar', $pengantar);
setPengaturan('gpm_narasi_tugas', $tugas);
setPengaturan('gpm_narasi_fungsi', $fungsi);
setPengaturan('gpm_narasi_dasar_hukum', $dasar_hukum);

echo "Pengaturan Narasi GPM berhasil disimpan!\n";
