<?php
/**
 * Admin Auth Guard – Include this at top of every protected admin page
 */
require_once __DIR__ . '/../../config/database.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/index.php');
}
?>
