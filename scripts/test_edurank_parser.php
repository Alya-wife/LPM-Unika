<?php
require_once __DIR__ . '/../config/database.php';

$res = getEduRankRankings(true);
echo "=== HASIL getEduRankRankings(true) ===\n";
echo "Indonesia: " . $res['indonesia']['formatted'] . "\n";
echo "Semarang: " . $res['semarang']['formatted'] . "\n";
echo "Jumlah Kategori Topics: " . count($res['topics']) . "\n";
foreach ($res['topics'] as $t) {
    echo "• " . $t['category'] . " (" . count($t['subfields']) . " subfields)\n";
    foreach (array_slice($t['subfields'], 0, 2) as $sf) {
        echo "   - " . $sf['name'] . ": #" . $sf['indonesia_rank'] . " di Indonesia\n";
    }
}
