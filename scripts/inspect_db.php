<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "=== PAGES ===\n";
$stmt = $db->query("SELECT slug, blocks_json FROM pages WHERE slug IN ('spmi', 'akreditasi')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Slug: " . $row['slug'] . "\n";
    echo "Blocks: " . $row['blocks_json'] . "\n\n";
}

echo "=== SPMI KEMENDIKTI ===\n";
$stmt2 = $db->query("SELECT * FROM spmi_kemendikti");
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($rows2);

echo "=== LEMBAGA AKREDITASI ===\n";
$stmt3 = $db->query("SELECT id, kode, nama, logo, link_website FROM lembaga_akreditasi");
$rows3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
print_r($rows3);
