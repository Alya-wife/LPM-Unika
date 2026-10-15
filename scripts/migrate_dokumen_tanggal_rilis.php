<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// 1. Cek apakah kolom tanggal_rilis sudah ada
$check = $db->query("SHOW COLUMNS FROM dokumen LIKE 'tanggal_rilis'")->fetchAll();
if (empty($check)) {
    $db->exec("ALTER TABLE dokumen ADD COLUMN tanggal_rilis DATE NULL DEFAULT NULL AFTER kategori");
    echo "Kolom tanggal_rilis berhasil ditambahkan ke tabel dokumen.\n";
    
    // Backfill nilai default dari created_at
    $db->exec("UPDATE dokumen SET tanggal_rilis = DATE(created_at) WHERE tanggal_rilis IS NULL");
    echo "Backfill tanggal_rilis dari created_at selesai.\n";
} else {
    echo "Kolom tanggal_rilis sudah ada.\n";
}

// 2. Tampilkan semua dokumen
$docs = $db->query("SELECT id, nama_dokumen, kategori, tanggal_rilis, created_at FROM dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total dokumen: " . count($docs) . "\n";
foreach ($docs as $d) {
    echo "ID {$d['id']} | {$d['kategori']} | Rilis: {$d['tanggal_rilis']} | Created: {$d['created_at']} | {$d['nama_dokumen']}\n";
}
