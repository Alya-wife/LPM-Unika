<?php
/**
 * Admin Auth Guard – Include this at top of every protected admin page
 */
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/index.php');
}
?>
