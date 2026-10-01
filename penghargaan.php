<?php
/**
 * Pengalihan (Redirect) Sub-Menu Penghargaan menuju Pemeringkatan EduRank
 */
require_once __DIR__ . '/config/database.php';
header("Location: " . SITE_URL . "/pemeringkatan.php", true, 301);
exit;
