<?php
require_once __DIR__ . '/admin/includes/auth.php';
$db = getDB();
$dates = $db->query("SELECT DISTINCT tanggal_kunjungan FROM kunjungan_feedback_respon ORDER BY tanggal_kunjungan DESC")->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($dates, JSON_PRETTY_PRINT);
unlink(__FILE__);
