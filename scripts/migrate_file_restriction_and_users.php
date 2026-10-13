<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Starting migration for users roles and file protection...\n";

// 1. Update USERS table
$cols_users = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('role', $cols_users)) {
    $db->exec("ALTER TABLE users ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER `nama_lengkap`");
    echo "Added column 'role' to users.\n";
}
if (!in_array('akses_halaman', $cols_users)) {
    $db->exec("ALTER TABLE users ADD COLUMN `akses_halaman` LONGTEXT NULL AFTER `role`");
    echo "Added column 'akses_halaman' to users.\n";
}
if (!in_array('status', $cols_users)) {
    $db->exec("ALTER TABLE users ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `akses_halaman`");
    echo "Added column 'status' to users.\n";
}

// Make sure existing user 1 is superadmin
$db->exec("UPDATE users SET `role` = 'superadmin', `status` = 1 WHERE id = 1 OR username = 'admin'");
echo "Updated default admin to superadmin.\n";

// 2. Add file restriction columns to tables
$tables = [
    'ami_siklus1_kegiatan',
    'ami_siklus4_dokumen',
    'ami_siklus4_dokumentasi',
    'ami_siklus5_rtm',
    'dokumen',
    'spmi_kemendikti',
    'buletin'
];

foreach ($tables as $table) {
    try {
        $cols = $db->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('is_restricted', $cols)) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `is_restricted` TINYINT(1) NOT NULL DEFAULT 0");
            echo "Added 'is_restricted' to {$table}.\n";
        }
        if (!in_array('max_preview_pages', $cols)) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `max_preview_pages` INT NOT NULL DEFAULT 3");
            echo "Added 'max_preview_pages' to {$table}.\n";
        }
        if (!in_array('allowed_domains', $cols)) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `allowed_domains` VARCHAR(255) NOT NULL DEFAULT '@unika.ac.id,@student.unika.ac.id'");
            echo "Added 'allowed_domains' to {$table}.\n";
        }
    } catch (Exception $e) {
        echo "Error on table {$table}: " . $e->getMessage() . "\n";
    }
}

echo "Migration finished successfully!\n";
