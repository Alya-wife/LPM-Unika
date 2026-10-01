<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$stmt = $db->query("SELECT blocks_json FROM pages WHERE slug = 'akreditasi'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && !empty($row['blocks_json'])) {
    $blocks = json_decode($row['blocks_json'], true);
    if (is_array($blocks)) {
        $filtered = array_values(array_filter($blocks, function($b) {
            return ($b['type'] ?? '') !== 'akreditasi_dokumen';
        }));
        $stmt_up = $db->prepare("UPDATE pages SET blocks_json = ? WHERE slug = 'akreditasi'");
        $stmt_up->execute([json_encode($filtered)]);
        echo "Updated akreditasi blocks_json successfully! Filtered count: " . count($filtered) . "\n";
    }
}
