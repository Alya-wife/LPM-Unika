<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$c4 = $db->query("SHOW COLUMNS FROM ami_siklus4_dokumentasi LIKE 'file_undangan'")->fetchAll();
if (empty($c4)) {
    $db->exec("ALTER TABLE ami_siklus4_dokumentasi ADD COLUMN file_undangan VARCHAR(255) DEFAULT '' AFTER file_daftar_hadir");
    echo "Added file_undangan to ami_siklus4_dokumentasi.\n";
} else {
    echo "file_undangan already exists in ami_siklus4_dokumentasi.\n";
}

$c5 = $db->query("SHOW COLUMNS FROM ami_siklus5_rtm LIKE 'file_undangan'")->fetchAll();
if (empty($c5)) {
    $db->exec("ALTER TABLE ami_siklus5_rtm ADD COLUMN file_undangan VARCHAR(255) DEFAULT '' AFTER file_daftar_hadir");
    echo "Added file_undangan to ami_siklus5_rtm.\n";
} else {
    echo "file_undangan already exists in ami_siklus5_rtm.\n";
}

// Pastikan file dummy surat undangan tersedia
$samplePdf = __DIR__ . '/../uploads/ami/siklus1/panduan_ami_2025_2026.pdf';
$undangan4 = __DIR__ . '/../uploads/ami/siklus4/undangan_audit_2025.pdf';
$undangan5 = __DIR__ . '/../uploads/ami/siklus5/undangan_rtm_2025.pdf';

if (file_exists($samplePdf)) {
    if (!file_exists($undangan4)) copy($samplePdf, $undangan4);
    if (!file_exists($undangan5)) copy($samplePdf, $undangan5);
}

// Update existing dummy rows to populate file_undangan
$db->exec("UPDATE ami_siklus4_dokumentasi SET file_undangan = 'ami/siklus4/undangan_audit_2025.pdf' WHERE file_undangan = '' OR file_undangan IS NULL");
$db->exec("UPDATE ami_siklus5_rtm SET file_undangan = 'ami/siklus5/undangan_rtm_2025.pdf' WHERE file_undangan = '' OR file_undangan IS NULL");
echo "Updated file_undangan values for existing records.\n";
