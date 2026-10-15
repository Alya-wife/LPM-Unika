<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Running migration: Separate file restrictions for AMI...\n";

// Helper check column exists
function addColumnIfNotExists($db, $table, $column, $definition) {
    $cols = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "Added {$column} to {$table}.\n";
    } else {
        echo "Column {$column} already exists in {$table}.\n";
    }
}

// 1. ami_siklus5_rtm
$s5_columns = [
    'is_restricted_undangan'          => "TINYINT(1) NOT NULL DEFAULT 0",
    'max_preview_pages_undangan'      => "INT NOT NULL DEFAULT 3",
    'allowed_domains_undangan'        => "VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'",
    'is_restricted_daftar_hadir'      => "TINYINT(1) NOT NULL DEFAULT 0",
    'max_preview_pages_daftar_hadir'  => "INT NOT NULL DEFAULT 3",
    'allowed_domains_daftar_hadir'    => "VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'",
    'is_restricted_notulensi'         => "TINYINT(1) NOT NULL DEFAULT 0",
    'max_preview_pages_notulensi'     => "INT NOT NULL DEFAULT 3",
    'allowed_domains_notulensi'       => "VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'"
];

foreach ($s5_columns as $col => $def) {
    addColumnIfNotExists($db, 'ami_siklus5_rtm', $col, $def);
}

// Copy nilai is_restricted lama ke kolom baru jika ada data yang sudah berstatus restricted
$db->exec("
    UPDATE ami_siklus5_rtm 
    SET is_restricted_undangan = is_restricted,
        max_preview_pages_undangan = max_preview_pages,
        allowed_domains_undangan = allowed_domains,
        is_restricted_daftar_hadir = is_restricted,
        max_preview_pages_daftar_hadir = max_preview_pages,
        allowed_domains_daftar_hadir = allowed_domains,
        is_restricted_notulensi = is_restricted,
        max_preview_pages_notulensi = max_preview_pages,
        allowed_domains_notulensi = allowed_domains
    WHERE is_restricted = 1
");

// 2. ami_siklus4_dokumentasi
$s4_columns = [
    'is_restricted_undangan'          => "TINYINT(1) NOT NULL DEFAULT 0",
    'max_preview_pages_undangan'      => "INT NOT NULL DEFAULT 3",
    'allowed_domains_undangan'        => "VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'",
    'is_restricted_daftar_hadir'      => "TINYINT(1) NOT NULL DEFAULT 0",
    'max_preview_pages_daftar_hadir'  => "INT NOT NULL DEFAULT 3",
    'allowed_domains_daftar_hadir'    => "VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'",
    'is_restricted_berita_acara'      => "TINYINT(1) NOT NULL DEFAULT 0",
    'max_preview_pages_berita_acara'  => "INT NOT NULL DEFAULT 3",
    'allowed_domains_berita_acara'    => "VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'"
];

foreach ($s4_columns as $col => $def) {
    addColumnIfNotExists($db, 'ami_siklus4_dokumentasi', $col, $def);
}

$db->exec("
    UPDATE ami_siklus4_dokumentasi 
    SET is_restricted_undangan = is_restricted,
        max_preview_pages_undangan = max_preview_pages,
        allowed_domains_undangan = allowed_domains,
        is_restricted_daftar_hadir = is_restricted,
        max_preview_pages_daftar_hadir = max_preview_pages,
        allowed_domains_daftar_hadir = allowed_domains,
        is_restricted_berita_acara = is_restricted,
        max_preview_pages_berita_acara = max_preview_pages,
        allowed_domains_berita_acara = allowed_domains
    WHERE is_restricted = 1
");

echo "Migration finished successfully.\n";
