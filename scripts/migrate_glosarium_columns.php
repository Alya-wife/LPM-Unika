<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Memeriksa dan menyelaraskan kolom tabel glosarium...\n";

// Periksa kolom nama
$cols = $db->query("SHOW COLUMNS FROM glosarium LIKE 'nama'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE glosarium ADD COLUMN `nama` VARCHAR(255) NULL DEFAULT NULL AFTER `istilah`");
    echo "Kolom 'nama' berhasil ditambahkan ke glosarium.\n";
}

// Periksa kolom kategori
$cols = $db->query("SHOW COLUMNS FROM glosarium LIKE 'kategori'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE glosarium ADD COLUMN `kategori` VARCHAR(100) NOT NULL DEFAULT 'Umum' AFTER `nama`");
    echo "Kolom 'kategori' berhasil ditambahkan ke glosarium.\n";
}

// Sinkronkan data lama dari istilah_lengkap & sumber ke nama & kategori
$db->exec("UPDATE glosarium SET nama = istilah_lengkap WHERE (nama IS NULL OR nama = '') AND istilah_lengkap IS NOT NULL");
$db->exec("UPDATE glosarium SET kategori = sumber WHERE (kategori IS NULL OR kategori = 'Umum' OR kategori = '') AND sumber IS NOT NULL AND sumber != ''");

// Juga pastikan jika ada istilah_lengkap yang kosong tapi nama terisi, disinkronkan timbal balik
$db->exec("UPDATE glosarium SET istilah_lengkap = nama WHERE (istilah_lengkap IS NULL OR istilah_lengkap = '') AND nama IS NOT NULL");
$db->exec("UPDATE glosarium SET sumber = kategori WHERE (sumber IS NULL OR sumber = '') AND kategori IS NOT NULL");

echo "Sinkronisasi data tabel glosarium selesai!\n";
